<?php
/**
 * MODEL / INTEGRATION: CongThanhToan.php
 * Lớp tích hợp cổng thanh toán VietQR (NAPAS 247) & xác thực giao dịch
 * Khớp Bảng 3.7 & Biểu đồ lớp Hình 3.16 (Chương 3 Báo cáo)
 */
require_once __DIR__ . '/ThanhToanModel.php';

class CongThanhToan {
    public static function getConfig() {
        static $cfg = null;
        if ($cfg === null) {
            $configFile = __DIR__ . '/../config/vietqr.php';
            if (file_exists($configFile)) {
                $cfg = require $configFile;
            } else {
                $cfg = [
                    'bank_bin'     => '970407',
                    'bank_name'    => 'Techcombank',
                    'account_no'   => '29062004555',
                    'account_name' => 'NGUYEN LE QUYNH MAI',
                    'template'     => 'compact2'
                ];
            }
        }
        return $cfg;
    }

    public static function taoMaVietQR($maDonHang, $soTien) {
        $amount = max(0, (int)round((float)$soTien));
        $payosQr = self::taoMaPayOS($maDonHang, $amount);
        if (!empty($payosQr)) {
            return $payosQr;
        }

        $cfg = self::getConfig();
        $info = urlencode('KIBO ' . trim((string)$maDonHang));
        $accName = urlencode($cfg['account_name'] ?? '');
        $bin = $cfg['bank_bin'] ?? '970426';
        $accNo = $cfg['account_no'] ?? '96886693102640';
        $template = $cfg['template'] ?? 'compact2';
        return "https://api.vietqr.io/image/{$bin}-{$accNo}-{$template}.jpg?amount={$amount}&addInfo={$info}&accountName={$accName}";
    }

    public static function taoMaPayOS($maDonHang, $amount) {
        require_once __DIR__ . '/../config/database.php';
        Database::loadEnv();
        $clientId = getenv('PAYOS_CLIENT_ID') ?: ($_ENV['PAYOS_CLIENT_ID'] ?? '');
        $apiKey = getenv('PAYOS_API_KEY') ?: ($_ENV['PAYOS_API_KEY'] ?? '');
        $checksumKey = getenv('PAYOS_CHECKSUM_KEY') ?: ($_ENV['PAYOS_CHECKSUM_KEY'] ?? '');
        if (empty($clientId) || empty($apiKey) || empty($checksumKey) || $amount < 1000) {
            return null;
        }
        if (!function_exists('curl_init')) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string)$maDonHang);
        $orderCodeInt = (int)($digits ?: time());
        $description = 'KIBO ' . str_replace('-', '', trim((string)$maDonHang));
        if (strlen($description) > 25) {
            $description = substr($description, 0, 25);
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host . '/kibo/SourceCode/index.php?action=cart&step=checkout';
        $returnUrl = $baseUrl;
        $cancelUrl = $baseUrl;

        $sigStr = "amount={$amount}&cancelUrl={$cancelUrl}&description={$description}&orderCode={$orderCodeInt}&returnUrl={$returnUrl}";
        $signature = hash_hmac('sha256', $sigStr, $checksumKey);

        $body = json_encode([
            'orderCode' => $orderCodeInt,
            'amount' => $amount,
            'description' => $description,
            'returnUrl' => $returnUrl,
            'cancelUrl' => $cancelUrl,
            'signature' => $signature
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init('https://api-merchant.payos.vn/v2/payment-requests');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-client-id: ' . $clientId,
                'x-api-key: ' . $apiKey
            ]
        ]);
        $resp = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($resp)) {
            $json = json_decode($resp, true);
            if (!empty($json['data']['qrCode'])) {
                return 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=10&data=' . urlencode($json['data']['qrCode']);
            }
        }
        return null;
    }

    public static function kiemTraGiaoDich($payload = []) {
        // Hỗ trợ định dạng Webhook chuẩn PayOS (nằm trong $payload['data'])
        if (!empty($payload['data']) && is_array($payload['data'])) {
            $d = $payload['data'];
            if (empty($payload['madonhang']) && !empty($d['orderCode'])) {
                $payload['madonhang'] = 'KB-' . $d['orderCode'];
            }
            if (empty($payload['content']) && !empty($d['description'])) {
                $payload['content'] = $d['description'];
            }
            if (!isset($payload['transferAmount']) && isset($d['amount'])) {
                $payload['transferAmount'] = $d['amount'];
            }
        }

        $maDonHang = trim($payload['madonhang'] ?? ($payload['orderId'] ?? ''));

        // Hỗ trợ định dạng Webhook chuẩn từ SePay / PayOS (tách mã đơn kể cả khi ngân hàng bỏ dấu gạch ngang KB-...)
        if (empty($maDonHang) && !empty($payload['content'])) {
            if (preg_match('/KB-?(\d{4,12})/i', (string)$payload['content'], $m)) {
                $maDonHang = 'KB-' . $m[1];
            }
        }

        if (empty($maDonHang)) {
            return ['success' => false, 'message' => 'Thiếu mã đơn hàng cần kiểm tra giao dịch.'];
        }

        // Nếu Webhook gửi kèm số tiền thực nhận (transferAmount / amount), đối chiếu chống chuyển thiếu
        $receivedAmount = isset($payload['transferAmount']) ? (float)$payload['transferAmount']
                        : (isset($payload['amount']) ? (float)$payload['amount'] : null);
        if ($receivedAmount !== null) {
            require_once __DIR__ . '/DonHangModel.php';
            $status = DonHangModel::getPaymentAndOrderStatus($maDonHang);
            if (!empty($status['success']) && $receivedAmount < (float)$status['tongthanhtoan']) {
                return ['success' => false, 'message' => 'Số tiền chuyển khoản chưa đủ giá trị đơn hàng.'];
            }
        }

        $res = ThanhToanModel::xacNhanNhanTien($maDonHang);
        if (!empty($res['success'])) {
            require_once __DIR__ . '/DonHangModel.php';
            require_once __DIR__ . '/TrangThai.php';
            // Tự động duyệt đơn từ Chờ xác nhận -> Chờ đóng gói & cấp mã vận đơn tự động
            $st = DonHangModel::getPaymentAndOrderStatus($maDonHang);
            if (!empty($st['success']) && ($st['trangthaidonhang'] ?? '') === TrangThai::DH_CHO_XAC_NHAN) {
                $upd = DonHangModel::capNhatTrangThai($maDonHang, TrangThai::DH_CHO_DONG_GOI);
                $stAfter = DonHangModel::getPaymentAndOrderStatus($maDonHang);
                $res['mavandon'] = $stAfter['mavandon'] ?? null;
                $res['auto_approved'] = !empty($upd['success']);
            }
        }
        return $res;
    }

    public static function dongBoTrangThaiPayOS($maDonHang) {
        require_once __DIR__ . '/../config/database.php';
        Database::loadEnv();
        $clientId = getenv('PAYOS_CLIENT_ID') ?: ($_ENV['PAYOS_CLIENT_ID'] ?? '');
        $apiKey = getenv('PAYOS_API_KEY') ?: ($_ENV['PAYOS_API_KEY'] ?? '');
        if (empty($clientId) || empty($apiKey) || !function_exists('curl_init')) {
            return ['success' => false];
        }

        $digits = preg_replace('/\D/', '', (string)$maDonHang);
        if (empty($digits)) {
            return ['success' => false];
        }

        $ch = curl_init('https://api-merchant.payos.vn/v2/payment-requests/' . $digits);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'x-client-id: ' . $clientId,
                'x-api-key: ' . $apiKey
            ]
        ]);
        $resp = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode < 200 || $httpCode >= 300) {
            error_log("PayOS sync error ({$maDonHang}): HTTP={$httpCode} ERR={$curlErr}");
        }

        if ($httpCode >= 200 && $httpCode < 300 && !empty($resp)) {
            $json = json_decode($resp, true);
            $data = $json['data'] ?? null;
            if (is_array($data) && strtoupper((string)($data['status'] ?? '')) === 'PAID') {
                $paidAmount = (float)($data['amountPaid'] ?? ($data['amount'] ?? 0));
                return self::kiemTraGiaoDich([
                    'madonhang' => $maDonHang,
                    'transferAmount' => $paidAmount
                ]);
            }
        }
        return ['success' => false];
    }
}
