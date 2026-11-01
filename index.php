<?php
/**
 * Front Controller & Router chính của hệ thống KIBO (kibo.vn)
 * Mô hình kiến trúc: MVC (Model - View - Controller)
 * 100% Khớp theo Figma KIBO Pet E-Commerce Platform
 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

/**
 * Kiểm tra phân quyền Quản trị viên (Admin)
 * Trả về HTTP 403 JSON nếu người dùng chưa đăng nhập hoặc không phải Admin
 */
function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        @session_start();
    }
    if (empty($_SESSION['user']) || ($_SESSION['user']['VaiTro'] ?? '') !== 'Admin') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Truy cập bị từ chối: Yêu cầu quyền quản trị viên (Admin).'], JSON_UNESCAPED_UNICODE);
        exit;
    }
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
        $targetKH = $_SESSION['user']['MaKH'] ?? '';

        $result = DonHangModel::taoDonHang($input);
        if ($result['success']) {
            require_once __DIR__ . '/models/GioHangModel.php';
            if (!empty($targetKH)) {
                GioHangModel::clearCartInDb($targetKH);
            }
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;

    case 'cancel_order':
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || empty($currentUser['MaKH'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện hủy đơn.']);
            exit;
        }
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $orderId = trim($input['madonhang'] ?? '');
        if (empty($orderId)) {
            echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ.']);
            exit;
        }

        require_once __DIR__ . '/models/DonHangModel.php';
        require_once __DIR__ . '/models/TrangThai.php';
        $db = Database::getConnection();
        $stmtChk = $db->prepare("SELECT makh, trangthaidonhang FROM don_hang WHERE madonhang = ?");
        $stmtChk->execute([$orderId]);
        $ord = $stmtChk->fetch();

        if (!$ord) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.']);
            exit;
        }
        if ($ord['makh'] !== $currentUser['MaKH']) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thao tác trên đơn hàng này.']);
            exit;
        }
        if ($ord['trangthaidonhang'] !== TrangThai::DH_CHO_XAC_NHAN) {
            echo json_encode(['success' => false, 'message' => 'Chỉ có thể hủy khi đơn hàng ở trạng thái \'Chờ xác nhận\'.']);
            exit;
        }

        $res = DonHangModel::capNhatTrangThai($orderId, TrangThai::DH_DA_HUY);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'get_cart':
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $maKH = $_SESSION['user']['MaKH'] ?? '';
        if ($maKH) {
            require_once __DIR__ . '/models/GioHangModel.php';
            $cart = GioHangModel::getCartFromDb($maKH);
            echo json_encode(['success' => true, 'cart' => $cart], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'cart' => []]);
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
        // Bảo mật: Chỉ lấy MaKH từ Session, tuyệt đối không lấy MaKH từ client gửi lên
        $maKH = $_SESSION['user']['MaKH'] ?? '';
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
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $maKH = $_SESSION['user']['MaKH'] ?? '';
        require_once __DIR__ . '/models/DoiTraModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maDH = trim($input['madonhang'] ?? '');
        $lyDo = trim($input['lydo'] ?? '');
        $hinhAnh = trim($input['hinhanh'] ?? '');
        $res = DoiTraModel::taoYeuCau($maDH, $lyDo, $hinhAnh, $maKH);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_process_return':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DoiTraModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maYC = trim($input['mayeucau'] ?? '');
        $actionType = trim($input['action_type'] ?? ($input['hanhdong'] ?? ''));
        $note = trim($input['ghichu'] ?? '');
        $res = DoiTraModel::xuLy($maYC, $actionType, $note);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin':
        require __DIR__ . '/views/admin/DashboardView.php';
        break;

    case 'admin_create_product':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $res = SanPhamModel::themSanPham($input);
        echo json_encode($res);
        exit;

    case 'admin_create_category':
        requireAdmin();
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
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonHangModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $orderId = $input['madonhang'] ?? '';
        $newStatus = $input['trangthai'] ?? '';
        $res = DonHangModel::capNhatTrangThai($orderId, $newStatus);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_voucher_list':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/VoucherModel.php';
        $list = VoucherModel::getAllAdmin();
        echo json_encode(['success' => true, 'vouchers' => $list], JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_voucher_save':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/VoucherModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $res = VoucherModel::saveVoucher($input);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_voucher_toggle':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/VoucherModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $code = trim($input['code'] ?? '');
        $res = VoucherModel::toggleActive($code);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
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
        // Bảo mật: Chỉ lấy MaKH từ session hiện tại
        $maKH = $currentUser['MaKH'] ?? '';
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
