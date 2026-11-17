<?php
/**
 * HELPER: MailHelper.php
 * Gửi email thật qua giao thức SMTP (STARTTLS / SSL) không cần thư viện ngoài.
 * Đọc cấu hình từ .env: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM_NAME, SMTP_FROM_EMAIL
 */
require_once __DIR__ . '/../config/database.php';

class MailHelper {
    private static function getConfig(): array {
        Database::loadEnv();
        $host = trim((string)(getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? '')));
        $port = (int)(getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587));
        $user = trim((string)(getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? '')));
        $pass = (string)(getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? ''));
        $fromName = trim((string)(getenv('SMTP_FROM_NAME') ?: ($_ENV['SMTP_FROM_NAME'] ?? "KIBO Pet's Home")));
        $fromEmail = trim((string)(getenv('SMTP_FROM_EMAIL') ?: ($_ENV['SMTP_FROM_EMAIL'] ?? $user)));
        $webhookUrl = trim((string)(getenv('MAIL_WEBHOOK_URL') ?: ($_ENV['MAIL_WEBHOOK_URL'] ?? '')));
        $webhookSecret = trim((string)(getenv('MAIL_WEBHOOK_SECRET') ?: ($_ENV['MAIL_WEBHOOK_SECRET'] ?? '')));
        $brevoKey = trim((string)(getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? '')));

        return [
            'host' => $host,
            'port' => $port > 0 ? $port : 587,
            'user' => $user,
            'pass' => $pass,
            'from_name' => $fromName !== '' ? $fromName : "KIBO Pet's Home",
            'from_email' => $fromEmail !== '' ? $fromEmail : $user,
            'webhook_url' => $webhookUrl,
            'webhook_secret' => $webhookSecret,
            'brevo_key' => $brevoKey,
        ];
    }

    public static function isConfigured(): bool {
        $cfg = self::getConfig();
        if ($cfg['webhook_url'] !== '' || $cfg['brevo_key'] !== '') {
            return true;
        }
        return $cfg['host'] !== '' && $cfg['user'] !== '' && $cfg['pass'] !== '';
    }

    private static function readResponse($fp): string {
        $data = '';
        while (!feof($fp)) {
            $line = fgets($fp, 515);
            if ($line === false) break;
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $data;
    }

    private static function expectCode($fp, array $allowedCodes): bool {
        $resp = self::readResponse($fp);
        $code = (int)substr(trim($resp), 0, 3);
        if (!in_array($code, $allowedCodes, true)) {
            error_log('SMTP unexpected response: ' . trim($resp));
            return false;
        }
        return true;
    }

    private static function postJsonHttps(string $url, array $payload, array $extraHeaders = []): array {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $headers = array_merge(['Content-Type: application/json'], $extraHeaders);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            return [$code, (string)$resp, $err];
        }
        $headerStr = "Content-Type: application/json\r\n" . implode("\r\n", $extraHeaders);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headerStr,
                'content' => $json,
                'timeout' => 15,
                'ignore_errors' => true,
            ]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $code = (int)$m[1];
        }
        return [$code, (string)$resp, $resp === false ? 'HTTP request failed' : ''];
    }

    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): array {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Địa chỉ email người nhận không hợp lệ.'];
        }
        $cfg = self::getConfig();

        // Ưu tiên gửi qua HTTPS Webhook (Google Apps Script) để chạy được trên Render Free (vốn chặn cổng SMTP 587/465)
        if ($cfg['webhook_url'] !== '') {
            [$code, $resp, $err] = self::postJsonHttps($cfg['webhook_url'], [
                'secret' => $cfg['webhook_secret'],
                'to' => $toEmail,
                'name' => $toName,
                'subject' => $subject,
                'html' => $htmlBody,
                'fromName' => $cfg['from_name'],
            ]);
            if ($code >= 200 && $code < 300) {
                $decoded = json_decode($resp, true);
                if (!is_array($decoded) || !empty($decoded['success'])) {
                    return ['success' => true, 'message' => 'Đã gửi email thành công.'];
                }
                return ['success' => false, 'message' => $decoded['message'] ?? 'Lỗi gửi email qua Webhook.'];
            }
            error_log("Mail webhook error HTTP={$code} ERR={$err} RESP=" . substr($resp, 0, 200));
            return ['success' => false, 'message' => 'Không thể gửi email qua máy chủ HTTPS.'];
        }

        // Hỗ trợ Brevo HTTP API (cổng 443)
        if ($cfg['brevo_key'] !== '') {
            $senderEmail = $cfg['from_email'] ?: $cfg['user'];
            [$code, $resp, $err] = self::postJsonHttps(
                'https://api.brevo.com/v3/smtp/email',
                [
                    'sender' => ['name' => $cfg['from_name'], 'email' => $senderEmail],
                    'to' => [['email' => $toEmail, 'name' => $toName ?: $toEmail]],
                    'subject' => $subject,
                    'htmlContent' => $htmlBody,
                ],
                ['api-key: ' . $cfg['brevo_key'], 'accept: application/json']
            );
            if ($code >= 200 && $code < 300) {
                return ['success' => true, 'message' => 'Đã gửi email thành công.'];
            }
            error_log("Brevo API error HTTP={$code} ERR={$err} RESP=" . substr($resp, 0, 200));
            return ['success' => false, 'message' => 'Không thể gửi email qua Brevo API.'];
        }

        if ($cfg['host'] === '' || $cfg['user'] === '' || $cfg['pass'] === '') {
            return ['success' => false, 'not_configured' => true, 'message' => 'Chưa cấu hình SMTP trong .env'];
        }

        $transport = ($cfg['port'] === 465) ? 'ssl://' . $cfg['host'] : 'tcp://' . $cfg['host'];
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $fp = @stream_socket_client($transport . ':' . $cfg['port'], $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            error_log("SMTP connect error ({$errno}): {$errstr}");
            return ['success' => false, 'message' => 'Không thể kết nối máy chủ gửi email.'];
        }

        stream_set_timeout($fp, 10);

        $closeAndFail = function (string $msg) use ($fp): array {
            @fclose($fp);
            return ['success' => false, 'message' => $msg];
        };

        if (!self::expectCode($fp, [220])) {
            return $closeAndFail('Máy chủ SMTP không phản hồi hợp lệ.');
        }

        $ehloHost = $_SERVER['HTTP_HOST'] ?? 'kibo.vn';
        fwrite($fp, "EHLO {$ehloHost}\r\n");
        if (!self::expectCode($fp, [250])) {
            return $closeAndFail('Lỗi bắt tay EHLO với SMTP.');
        }

        if ($cfg['port'] !== 465) {
            fwrite($fp, "STARTTLS\r\n");
            if (!self::expectCode($fp, [220])) {
                return $closeAndFail('Không thể bật mã hóa STARTTLS.');
            }
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return $closeAndFail('Không thể thiết lập kết nối TLS tới SMTP.');
            }
            fwrite($fp, "EHLO {$ehloHost}\r\n");
            if (!self::expectCode($fp, [250])) {
                return $closeAndFail('Lỗi EHLO sau STARTTLS.');
            }
        }

        fwrite($fp, "AUTH LOGIN\r\n");
        if (!self::expectCode($fp, [334])) {
            return $closeAndFail('Máy chủ SMTP từ chối AUTH LOGIN.');
        }

        fwrite($fp, base64_encode($cfg['user']) . "\r\n");
        if (!self::expectCode($fp, [334])) {
            return $closeAndFail('Tài khoản SMTP không hợp lệ.');
        }

        fwrite($fp, base64_encode($cfg['pass']) . "\r\n");
        if (!self::expectCode($fp, [235])) {
            return $closeAndFail('Sai mật khẩu ứng dụng SMTP.');
        }

        fwrite($fp, "MAIL FROM:<{$cfg['from_email']}>\r\n");
        if (!self::expectCode($fp, [250])) {
            return $closeAndFail('Lỗi MAIL FROM.');
        }

        fwrite($fp, "RCPT TO:<{$toEmail}>\r\n");
        if (!self::expectCode($fp, [250, 251])) {
            return $closeAndFail('Email người nhận không tồn tại hoặc bị từ chối.');
        }

        fwrite($fp, "DATA\r\n");
        if (!self::expectCode($fp, [354])) {
            return $closeAndFail('Lỗi khởi tạo nội dung email.');
        }

        $encodedFromName = '=?UTF-8?B?' . base64_encode($cfg['from_name']) . '?=';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedToName = $toName !== '' ? ('=?UTF-8?B?' . base64_encode($toName) . '?= ') : '';

        $headers = [
            "Date: " . date('r'),
            "From: {$encodedFromName} <{$cfg['from_email']}>",
            "To: {$encodedToName}<{$toEmail}>",
            "Subject: {$encodedSubject}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "X-Mailer: KIBO-Mailer/1.0"
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.\r\n";
        fwrite($fp, $message);
        if (!self::expectCode($fp, [250])) {
            return $closeAndFail('Gửi nội dung email thất bại.');
        }

        fwrite($fp, "QUIT\r\n");
        @fclose($fp);
        return ['success' => true, 'message' => 'Đã gửi email thành công.'];
    }

    public static function sendOtpEmail(string $toEmail, string $toName, string $otp, string $purpose = 'register'): array {
        $title = ($purpose === 'reset') ? 'Mã xác minh khôi phục mật khẩu KIBO' : 'Mã xác minh tài khoản KIBO';
        $line = ($purpose === 'reset')
            ? 'Mã xác minh đặt lại mật khẩu KIBO của bạn là:'
            : 'Mã xác minh tài khoản KIBO của bạn là:';

        $safeName = htmlspecialchars($toName ?: 'bạn', ENT_QUOTES, 'UTF-8');
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

        $html = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 560px; margin: 0 auto; padding: 24px 20px; color: #222222; font-size: 14px; line-height: 1.6; background: #ffffff;'>
          <div style='text-align: center; margin-bottom: 24px;'>
            <span style='font-size: 22px; font-weight: 800; letter-spacing: 0.5px; color: #2c1a0e;'>KIBO</span>
            <span style='font-size: 13px; color: #666666; margin-left: 4px;'>Pet's Home</span>
          </div>
          <p style='margin: 0 0 14px;'>Chào {$safeName},</p>
          <p style='margin: 0 0 6px;'>{$line}</p>
          <p style='margin: 0 0 8px; font-size: 20px; font-weight: 700; letter-spacing: 1px; color: #111111;'>{$safeOtp}</p>
          <p style='margin: 0 0 22px;'>Có hiệu lực trong 5 phút. KHÔNG chia sẻ mã này với người khác, kể cả nhân viên KIBO.</p>
          <hr style='border: none; border-top: 1px solid #e5e5e5; margin: 20px 0;'>
          <p style='margin: 0; color: #444444;'>Trân trọng,<br>Đội ngũ KIBO</p>
        </div>";

        return self::send($toEmail, $toName, $title, $html);
    }

    private static function getBaseUrl(): string {
        Database::loadEnv();
        $envUrl = trim((string)(getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '')));
        if ($envUrl !== '') {
            return rtrim($envUrl, '/');
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            return 'http://localhost/kibo/SourceCode';
        }
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $scheme = $isHttps ? 'https' : 'http';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return $scheme . '://' . $host . $scriptDir;
    }

    public static function sendOrderConfirmation(string $toEmail, string $customerName, string $orderId, float $total, float $fundAmount, string $paymentMethod): array {
        $safeName = htmlspecialchars($customerName ?: 'bạn', ENT_QUOTES, 'UTF-8');
        $safeId = htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8');
        $safePm = htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8');
        $fmtTotal = number_format($total, 0, ',', '.') . 'đ';
        $fmtFund = number_format($fundAmount, 0, ',', '.') . 'đ';
        $trackingUrl = self::getBaseUrl() . '/index.php?action=tracking&order=' . urlencode($orderId);
        $safeTrackingUrl = htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8');

        $html = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 560px; margin: 0 auto; padding: 24px 20px; color: #222222; font-size: 14px; line-height: 1.6; background: #ffffff;'>
          <div style='text-align: center; margin-bottom: 24px;'>
            <span style='font-size: 22px; font-weight: 800; letter-spacing: 0.5px; color: #2c1a0e;'>KIBO</span>
            <span style='font-size: 13px; color: #666666; margin-left: 4px;'>Pet's Home</span>
          </div>
          <p style='margin: 0 0 14px;'>Chào {$safeName},</p>
          <p style='margin: 0 0 12px;'>Đơn hàng <strong>#{$safeId}</strong> của bạn đã được tiếp nhận.</p>
          <p style='margin: 0 0 4px;'>Phương thức thanh toán: {$safePm}</p>
          <p style='margin: 0 0 4px;'>Tổng thanh toán: <strong>{$fmtTotal}</strong></p>
          <p style='margin: 0 0 14px;'>Trích Quỹ cứu trợ (5%): {$fmtFund}</p>
          <p style='margin: 0 0 8px; color: #555555;'>Bạn có thể dùng mã đơn hàng <strong>#{$safeId}</strong> tại mục <a href='{$safeTrackingUrl}' style='color: #2c1a0e; font-weight: 700;'>Tra cứu đơn</a> trên website để theo dõi trạng thái đơn hàng bất cứ lúc nào.</p>
          <p style='margin: 0 0 20px; font-size: 13px; color: #555555;'>Đường dẫn tra cứu nhanh: <a href='{$safeTrackingUrl}' style='color: #1a73e8;'>{$safeTrackingUrl}</a></p>
          <hr style='border: none; border-top: 1px solid #e5e5e5; margin: 20px 0;'>
          <p style='margin: 0; color: #444444;'>Trân trọng,<br>Đội ngũ KIBO</p>
        </div>";

        return self::send($toEmail, $customerName, "Xác nhận đơn hàng #{$safeId} - KIBO", $html);
    }
}
