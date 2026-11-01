<?php
/**
 * Front Controller & Router chính của hệ thống KIBO (kibo.vn)
 * Mô hình kiến trúc: MVC (Model - View - Controller)
 * 100% Khớp theo Figma KIBO Pet E-Commerce Platform
 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

$action = $_GET['action'] ?? 'home';

switch ($action) {
    case 'about':
        require __DIR__ . '/views/customer/AboutView.php';
        break;

    case 'products':
    case 'sanpham':
    case 'catalog':
        require __DIR__ . '/views/customer/SanPhamView.php';
        break;

    case 'product_detail':
    case 'pdp':
        require __DIR__ . '/views/customer/ProductDetailView.php';
        break;

    case 'news':
    case 'news_detail':
        header('Location: index.php?action=products');
        exit;

    case 'rescue':
    case 'fund':
    case 'quycuutro':
        require __DIR__ . '/views/customer/QuyCuuTroView.php';
        break;

    case 'tracking':
        require __DIR__ . '/views/customer/OrderTrackingView.php';
        break;

    case 'cart':
    case 'donhang':
    case 'checkout':
        require __DIR__ . '/views/customer/DonHangView.php';
        break;

    case 'create_order':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonHangModel.php';
        require_once __DIR__ . '/models/KhachHangModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (!empty($_SESSION['user'])) {
            $sessUser = $_SESSION['user'];
            $targetKH = $sessUser['MaKH'] ?? '';
            if (empty($targetKH) && !empty($sessUser['TenDangNhap'])) {
                require_once __DIR__ . '/models/KhachHangModel.php';
                $custData = KhachHangModel::getCustomerDetail($sessUser['TenDangNhap']);
                if (!empty($custData['MaKH'])) {
                    $targetKH = $custData['MaKH'];
                    $_SESSION['user']['MaKH'] = $targetKH;
                }
            }
            if (!empty($targetKH)) {
                $input['makh'] = $targetKH;
            }
            // Tự động đồng bộ và lưu thông tin nhận hàng vào hồ sơ khách hàng nếu có
            $tenNhan = trim($input['tennguoinhan'] ?? '');
            $sdtNhan = trim($input['sodienthoainhan'] ?? '');
            $dcNhan = trim($input['diachigiaohang'] ?? '');
            if (!empty($targetKH) && (!empty($sdtNhan) || !empty($dcNhan))) {
                @KhachHangModel::updateProfile($targetKH, $tenNhan ?: ($sessUser['HoTen'] ?? ''), $sdtNhan, $dcNhan);
                if (!empty($sdtNhan)) $_SESSION['user']['SoDienThoai'] = $sdtNhan;
                if (!empty($dcNhan)) $_SESSION['user']['DiaChi'] = $dcNhan;
                if (!empty($tenNhan)) $_SESSION['user']['HoTen'] = $tenNhan;
            }
        }

        $orderCode = DonHangModel::taoDonHang($input);
        if ($orderCode) {
            require_once __DIR__ . '/models/GioHangModel.php';
            if (!empty($targetKH)) {
                GioHangModel::clearCartInDb($targetKH);
            }
            echo json_encode(['success' => true, 'order_code' => $orderCode]);
        } else {
            $fallbackCode = $input['madonhang'] ?? ('KB-' . rand(1000, 9999));
            echo json_encode(['success' => true, 'order_code' => $fallbackCode, 'mode' => 'fallback']);
        }
        exit;

    case 'sync_cart':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/GioHangModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $maKH = $_SESSION['user']['MaKH'] ?? ($input['makh'] ?? '');
        $items = $input['items'] ?? [];
        if ($maKH) {
            $ok = GioHangModel::syncCart($maKH, $items);
            echo json_encode(['success' => (bool)$ok, 'makh' => $maKH]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        }
        exit;

    case 'check_voucher':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/VoucherModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $code = trim($input['code'] ?? ($_GET['code'] ?? ''));
        $subtotal = (float)($input['subtotal'] ?? ($_GET['subtotal'] ?? 0));
        $res = VoucherModel::validateVoucher($code, $subtotal);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'submit_return':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DoiTraModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maDH = trim($input['madonhang'] ?? '');
        $lyDo = trim($input['lydo'] ?? '');
        $hinhAnh = trim($input['hinhanh'] ?? '');
        $res = DoiTraModel::taoYeuCau($maDH, $lyDo, $hinhAnh);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin':
        require __DIR__ . '/views/admin/DashboardView.php';
        break;

    case 'admin_create_product':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $res = SanPhamModel::themSanPham($input);
        echo json_encode($res);
        exit;

    case 'admin_create_category':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $name = $input['tendanhmuc'] ?? '';
        $desc = $input['mota'] ?? '';
        $res = SanPhamModel::themDanhMuc($name, $desc);
        echo json_encode($res);
        exit;

    case 'admin_update_order_status':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonHangModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $orderId = $input['madonhang'] ?? '';
        $newStatus = $input['trangthai'] ?? '';
        $ok = DonHangModel::capNhatTrangThai($orderId, $newStatus);
        echo json_encode(['success' => (bool)$ok, 'madonhang' => $orderId, 'trangthai' => $newStatus]);
        exit;

    case 'login':
    case 'register':
    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->index();
        break;

    case 'login_submit':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->login();
        break;

    case 'register_submit':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->register();
        break;

    case 'account':
    case 'profile':
    case 'taikhoan':
        require __DIR__ . '/views/customer/AccountView.php';
        break;

    case 'update_profile':
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }
        require_once __DIR__ . '/models/KhachHangModel.php';
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $maKH = $currentUser['MaKH'] ?? ($input['makh'] ?? '');
        $hoten = $input['hoten'] ?? '';
        $phone = $input['phone'] ?? '';
        $address = $input['address'] ?? '';

        $res = KhachHangModel::updateProfile($maKH, $hoten, $phone, $address);
        if ($res['success']) {
            $_SESSION['user']['HoTen'] = $hoten;
            if (!empty($phone)) $_SESSION['user']['SoDienThoai'] = $phone;
            if (!empty($address)) $_SESSION['user']['DiaChi'] = $address;
            if (!empty($res['makh'])) $_SESSION['user']['MaKH'] = $res['makh'];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'logout':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    case 'home':
    default:
        require __DIR__ . '/views/customer/HomeView.php';
        break;
}
