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
        $cfg = self::getConfig();
        $amount = max(0, (int)round((float)$soTien));
        $info = urlencode('KIBO ' . trim((string)$maDonHang));
        $accName = urlencode($cfg['account_name'] ?? '');
        $bin = $cfg['bank_bin'] ?? '970407';
        $accNo = $cfg['account_no'] ?? '29062004555';
        $template = $cfg['template'] ?? 'compact2';
        return "https://api.vietqr.io/image/{$bin}-{$accNo}-{$template}.jpg?amount={$amount}&addInfo={$info}&accountName={$accName}";
    }

    public static function kiemTraGiaoDich($payload = []) {
        $maDonHang = trim($payload['madonhang'] ?? ($payload['orderId'] ?? ''));
        if (empty($maDonHang)) {
            return ['success' => false, 'message' => 'Thiếu mã đơn hàng cần kiểm tra giao dịch.'];
        }
        return ThanhToanModel::xacNhanNhanTien($maDonHang);
    }
}
