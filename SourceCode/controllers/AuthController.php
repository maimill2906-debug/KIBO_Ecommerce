<?php
require_once __DIR__ . '/../models/TaiKhoanModel.php';

class AuthController {
    public function index() {
        require __DIR__ . '/../views/customer/AuthView.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->index();
            return;
        }

        // Nếu gửi mode = register thì chuyển sang xử lý đăng ký
        if (($_POST['mode'] ?? '') === 'register') {
            $this->register();
            return;
        }

        // Hỗ trợ cả Form POST chuẩn và JSON AJAX
        $email = $_POST['email'] ?? '';
        $pass  = $_POST['password'] ?? '';

        if (empty($email)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
            $pass  = $input['password'] ?? '';
            if (($input['mode'] ?? '') === 'register') {
                $this->register();
                return;
            }
        }

        $authResult = TaiKhoanModel::authenticateWithStatus($email, $pass);
        $user = $authResult['user'] ?? null;

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                  (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            $welcomeName = !empty($user['HoTen']) ? $user['HoTen'] : $user['TenDangNhap'];
            $_SESSION['login_success'] = 'Đăng nhập thành công! Xin chào ' . $welcomeName;

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Đăng nhập thành công! Xin chào ' . $welcomeName,
                    'user' => $user
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (($user['VaiTro'] ?? '') === 'Admin') {
                header('Location: index.php?action=admin');
            } else {
                header('Location: index.php?action=home');
            }
            exit;
        } else {
            $errorMsg = $authResult['error'] ?? 'Email/Số điện thoại hoặc mật khẩu không chính xác.';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
                return;
            }

            $_SESSION['login_error'] = $errorMsg;
            header('Location: index.php?action=login');
            exit;
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->index();
            return;
        }

        $data = $_POST;
        if (empty($data['email'])) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!empty($input)) {
                $data = $input;
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                  (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        require_once __DIR__ . '/../models/MailHelper.php';
        if (MailHelper::isConfigured()) {
            $otp = trim((string)($data['otp'] ?? ''));
            $regEmail = strtolower(trim((string)($data['email'] ?? '')));
            $savedOtp = $_SESSION['kibo_register_otp'] ?? null;

            $otpError = null;
            if (!$savedOtp || ($savedOtp['email'] ?? '') !== $regEmail) {
                $otpError = 'Vui lòng bấm gửi mã xác thực OTP về email trước khi tạo tài khoản.';
            } elseif (time() > (int)($savedOtp['expires'] ?? 0)) {
                unset($_SESSION['kibo_register_otp']);
                $otpError = 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.';
            } elseif ((int)($savedOtp['attempts'] ?? 0) >= 5) {
                unset($_SESSION['kibo_register_otp']);
                $otpError = 'Bạn đã nhập sai OTP quá 5 lần. Vui lòng yêu cầu mã mới.';
            } elseif ($otp === '' || !password_verify($otp, (string)($savedOtp['otp_hash'] ?? ''))) {
                $_SESSION['kibo_register_otp']['attempts'] = (int)($savedOtp['attempts'] ?? 0) + 1;
                $otpError = 'Mã xác thực OTP không chính xác.';
            }

            if ($otpError !== null) {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $otpError], JSON_UNESCAPED_UNICODE);
                    return;
                }
                $_SESSION['register_error'] = $otpError;
                header('Location: index.php?action=register');
                exit;
            }
            unset($_SESSION['kibo_register_otp']);
        }

        $res = TaiKhoanModel::registerUser($data);

        if ($res['success']) {
            require_once __DIR__ . '/../models/VoucherModel.php';
            $activeVouchers = VoucherModel::getAll();
            $welcomeHint = '🎉 Tạo tài khoản thành công! Bạn nhận được mã ưu đãi Chào bạn mới: KIBO20 (Giảm 20.000đ cho đơn đầu tiên). Vui lòng nhập mật khẩu để đăng nhập.';
            if (!empty($activeVouchers)) {
                $codes = array_map(function($v) { return $v['MaVoucher'] . ' (' . $v['TenVoucher'] . ')'; }, array_slice($activeVouchers, 0, 2));
                $welcomeHint = '🎉 Tạo tài khoản thành công! Ưu đãi dành riêng cho bạn: ' . implode(' · ', $codes) . '. Vui lòng nhập mật khẩu để đăng nhập.';
            }
            $_SESSION['register_success'] = $welcomeHint;
            $_SESSION['registered_email'] = $data['email'] ?? '';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true, 
                    'message' => $welcomeHint,
                    'email' => $data['email'] ?? ''
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            header('Location: index.php?action=login');
            exit;
        } else {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $res['message']], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['register_error'] = $res['message'];
            header('Location: index.php?action=register');
            exit;
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        unset($_SESSION['user']);
        session_destroy();

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true]);
            return;
        }

        header('Location: index.php?action=home');
        exit;
    }
}
