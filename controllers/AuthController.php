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

        $user = TaiKhoanModel::authenticate($email, $pass);

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
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Email hoặc mật khẩu không chính xác'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $_SESSION['login_error'] = 'Email hoặc mật khẩu không chính xác. Vui lòng thử lại!';
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

        $res = TaiKhoanModel::registerUser($data);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                  (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        if ($res['success']) {
            $_SESSION['register_success'] = 'Tạo tài khoản thành công! Vui lòng nhập mật khẩu để đăng nhập.';
            $_SESSION['registered_email'] = $data['email'] ?? '';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Tạo tài khoản thành công! Vui lòng nhập mật khẩu để đăng nhập.',
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
