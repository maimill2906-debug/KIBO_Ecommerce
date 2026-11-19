<?php
/**
 * Front Controller & Router chính của hệ thống KIBO (kibo.vn)
 * Mô hình kiến trúc: MVC (Model - View - Controller)
 * 100% Khớp theo Figma KIBO Pet E-Commerce Platform
 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    @session_start();
}

require_once __DIR__ . '/config/auth.php';
startSecureSession();

$action = $_GET['action'] ?? 'home';

try {
switch ($action) {
    case 'about':
        require __DIR__ . '/views/customer/AboutView.php';
        break;

    case 'policy':
        require __DIR__ . '/views/customer/PolicyView.php';
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
        if (!verifyCsrfToken($input)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc không hợp lệ (CSRF Token mismatch). Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (($_SESSION['user']['VaiTro'] ?? '') === 'Admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Tài khoản Quản trị viên không hỗ trợ đặt hàng. Vui lòng chuyển sang tài khoản Khách hàng.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $targetKH = $_SESSION['user']['MaKH'] ?? '';

        $result = DonHangModel::taoDonHang($input);
        if (!empty($result['success']) && empty($result['is_online'])) {
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
        if (!verifyCsrfToken($input)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc không hợp lệ (CSRF Token mismatch).'], JSON_UNESCAPED_UNICODE);
            exit;
        }
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
        if (!in_array($ord['trangthaidonhang'], [TrangThai::DH_CHO_XAC_NHAN, TrangThai::DH_CHO_DONG_GOI], true)) {
            echo json_encode(['success' => false, 'message' => "Chỉ có thể hủy khi đơn hàng ở trạng thái 'Chờ xác nhận' hoặc 'Chờ đóng gói'."]);
            exit;
        }

        // Kiểm tra xem đơn hàng đã thanh toán thành công chưa (VD: VietQR)
        $stmtTT = $db->prepare("SELECT trangthaitt, phuongthuctt FROM thanh_toan WHERE madonhang = ? LIMIT 1");
        $stmtTT->execute([$orderId]);
        $ttInfo = $stmtTT->fetch();
        $isPaid = ($ttInfo && ($ttInfo['trangthaitt'] === TrangThai::TT_THANH_CONG || $ttInfo['trangthaitt'] === 'Thành công'));

        if ($isPaid) {
            $refundBank = is_array($input['refund_bank'] ?? null) ? $input['refund_bank'] : [];
            $nganHang = trim((string)($refundBank['ngan_hang'] ?? ''));
            $soTK     = trim((string)($refundBank['so_tai_khoan'] ?? ''));
            $chuTK    = trim((string)($refundBank['chu_tai_khoan'] ?? ''));
            if ($nganHang === '' || $soTK === '' || $chuTK === '') {
                echo json_encode(['success' => false, 'message' => 'Đơn hàng đã thanh toán. Vui lòng nhập đầy đủ Tên ngân hàng, Số tài khoản và Chủ tài khoản để nhận hoàn tiền.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $refundNote = "[Hoàn tiền: {$nganHang} - STK: {$soTK} - Chủ TK: {$chuTK}]";
            $stmtNote = $db->prepare("UPDATE don_hang SET ghichu = CASE WHEN COALESCE(ghichu, '') = '' THEN ? ELSE ghichu || E'\\n' || ? END WHERE madonhang = ?");
            $stmtNote->execute([$refundNote, $refundNote, $orderId]);
        }

        $res = DonHangModel::capNhatTrangThai($orderId, TrangThai::DH_DA_HUY);
        if (!empty($res['success']) && $isPaid) {
            $res['message'] = "Hủy đơn #{$orderId} thành công! Yêu cầu hoàn tiền của bạn đã được ghi nhận và chuyển cho cửa hàng xử lý.";
        }
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
        $phone = trim($input['phone'] ?? ($_GET['phone'] ?? ''));
        $res = VoucherModel::validateVoucher($code, $subtotal, $phone);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'get_available_vouchers':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/VoucherModel.php';
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $maKH = $_SESSION['user']['MaKH'] ?? '';
        $phone = trim($_GET['phone'] ?? '');
        $vouchers = VoucherModel::getAvailableForCustomer($maKH, $phone);
        echo json_encode(['success' => true, 'vouchers' => $vouchers], JSON_UNESCAPED_UNICODE);
        exit;

    case 'get_provinces':
        header('Content-Type: application/json; charset=utf-8');
        try {
            require_once __DIR__ . '/config/database.php';
            $pdo = Database::getConnection();
            $stmt = $pdo->query("
                SELECT code, name, full_name, code_name, administrative_unit_short_name
                FROM provinces
                ORDER BY CASE WHEN code = '79' THEN 0 WHEN code = '01' THEN 1 ELSE 2 END, name ASC
            ");
            $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'provinces' => $provinces], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'provinces' => [], 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'get_wards':
        header('Content-Type: application/json; charset=utf-8');
        $provinceCode = trim($_GET['province_code'] ?? '');
        if ($provinceCode === '') {
            echo json_encode(['success' => true, 'wards' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        try {
            require_once __DIR__ . '/config/database.php';
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT code, name, full_name, code_name, province_code, administrative_unit_short_name
                FROM wards
                WHERE province_code = ?
                ORDER BY name ASC
            ");
            $stmt->execute([$provinceCode]);
            $wards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'wards' => $wards], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'wards' => [], 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;

    case 'submit_return':
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $maKH = $_SESSION['user']['MaKH'] ?? '';
        require_once __DIR__ . '/models/DoiTraModel.php';
        require_once __DIR__ . '/models/UploadHelper.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        if (!verifyCsrfToken($input)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn (CSRF). Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $maDH = trim($input['madonhang'] ?? '');
        $lyDo = trim($input['lydo'] ?? '');
        $hinhAnh = trim($input['hinhanh'] ?? '');
        $sdt = trim($input['sdt'] ?? ($input['sodienthoai'] ?? ''));

        // Hỗ trợ upload ảnh thật nếu có gửi file
        $hasUpload = false;
        if (!empty($_FILES['hinhanh_file']) && (!isset($_FILES['hinhanh_file']['error']) || $_FILES['hinhanh_file']['error'] !== UPLOAD_ERR_NO_FILE)) {
            $up = UploadHelper::uploadFile('hinhanh_file', 'returns', ['image/jpeg', 'image/png', 'image/webp']);
            if (!$up['success']) {
                echo json_encode(['success' => false, 'message' => $up['message']], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $hinhAnh = $up['path'];
            $hasUpload = true;
        } elseif (!empty($_FILES['hinhanh']) && is_array($_FILES['hinhanh']) && (!isset($_FILES['hinhanh']['error']) || $_FILES['hinhanh']['error'] !== UPLOAD_ERR_NO_FILE)) {
            $up = UploadHelper::uploadFile('hinhanh', 'returns', ['image/jpeg', 'image/png', 'image/webp']);
            if (!$up['success']) {
                echo json_encode(['success' => false, 'message' => $up['message']], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $hinhAnh = $up['path'];
            $hasUpload = true;
        }

        if (!$hasUpload) {
            if (!empty($hinhAnh)) {
                if (preg_match('#^(javascript:|data:|vbscript:|<script)#i', $hinhAnh) || strpos($hinhAnh, '..') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Đường dẫn hình ảnh minh chứng không hợp lệ hoặc chứa mã độc.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (!file_exists(__DIR__ . '/' . ltrim($hinhAnh, '/\\'))) {
                    echo json_encode(['success' => false, 'message' => 'Tệp hình ảnh minh chứng không tồn tại trên hệ thống.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } else {
                $hinhAnh = 'assets/images/returns/default.png';
            }
        }

        $res = DoiTraModel::taoYeuCau($maDH, $lyDo, $hinhAnh, $maKH, $sdt);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_process_return':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DoiTraModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maYC = trim($input['mayeucau'] ?? '');
        $actionType = trim($input['action_type'] ?? ($input['hanhdong'] ?? ($input['huongxuly'] ?? '')));
        $note = trim($input['ghichu'] ?? ($input['lydotuchoi'] ?? ''));
        $nhapLaiKho = !empty($input['nhaplaikho']) && ($input['nhaplaikho'] === true || $input['nhaplaikho'] === '1' || $input['nhaplaikho'] === 'true' || $input['nhaplaikho'] === 'on');
        $res = DoiTraModel::xuLy($maYC, $actionType, $note, $nhapLaiKho);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_create_shelter':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/TramCuuHoModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $res = TramCuuHoModel::themTram($input);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_disburse':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/QuyCuuTroModel.php';
        require_once __DIR__ . '/models/UploadHelper.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maTram = trim($input['matram'] ?? '');
        $soTien = (float)($input['sotien'] ?? 0);
        $ngayCK = trim($input['ngaychuyenkhoan'] ?? '');
        $mucDich = trim($input['mucdich'] ?? '');
        $fileCT = trim($input['filechungtu'] ?? '');
        $selectedDonations = $input['selected_donations'] ?? ($input['danhsachmadonggop'] ?? []);
        if (is_string($selectedDonations)) {
            $selectedDonations = json_decode($selectedDonations, true) ?: explode(',', $selectedDonations);
        }

        // Bắt buộc xử lý file chứng từ: Phải upload file thật hoặc dùng file chứng từ thực tế có sẵn
        $uploaded = false;
        if (!empty($_FILES['filechungtu']) && (!isset($_FILES['filechungtu']['error']) || $_FILES['filechungtu']['error'] !== UPLOAD_ERR_NO_FILE)) {
            $up = UploadHelper::uploadFile('filechungtu', 'disbursements', ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
            if (!$up['success']) {
                echo json_encode(['success' => false, 'message' => $up['message']], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $fileCT = $up['path'];
            $uploaded = true;
        } elseif (!empty($_FILES['filechungtu_upload']) && (!isset($_FILES['filechungtu_upload']['error']) || $_FILES['filechungtu_upload']['error'] !== UPLOAD_ERR_NO_FILE)) {
            $up = UploadHelper::uploadFile('filechungtu_upload', 'disbursements', ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
            if (!$up['success']) {
                echo json_encode(['success' => false, 'message' => $up['message']], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $fileCT = $up['path'];
            $uploaded = true;
        }

        if (!$uploaded) {
            if (empty($fileCT)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng đính kèm file chứng từ sao kê thực tế (PDF, JPG, PNG hoặc WEBP).'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if (preg_match('#^(javascript:|data:|vbscript:|<script)#i', $fileCT) || strpos($fileCT, '..') !== false) {
                echo json_encode(['success' => false, 'message' => 'Đường dẫn file chứng từ không hợp lệ hoặc chứa mã độc.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $diskPath = realpath(__DIR__ . '/' . ltrim($fileCT, '/\\'));
            if (!$diskPath || !file_exists($diskPath)) {
                echo json_encode(['success' => false, 'message' => "File chứng từ '{$fileCT}' không tồn tại trên hệ thống."], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $res = QuyCuuTroModel::giaiNgan($maTram, $soTien, $ngayCK, $mucDich, $fileCT, $selectedDonations);
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

    case 'admin_edit_product':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maSP = $input['masp'] ?? '';
        $res = SanPhamModel::suaSanPham($maSP, $input);
        echo json_encode($res);
        exit;

    case 'admin_upload_image':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/UploadHelper.php';
        $res = UploadHelper::uploadFile('image_file', 'products', ['image/jpeg', 'image/png', 'image/webp']);
        if (!empty($res['success'])) {
            echo json_encode(['success' => true, 'url' => $res['path'], 'message' => 'Tải ảnh lên thành công!']);
        } else {
            echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Không thể tải ảnh']);
        }
        exit;

    case 'admin_toggle_product_status':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maSP = $input['masp'] ?? '';
        $trangThai = $input['trangthai'] ?? null;
        $res = SanPhamModel::doiTrangThaiSanPham($maSP, $trangThai);
        echo json_encode($res);
        exit;

    case 'admin_restock_product':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maSP = $input['masp'] ?? ($input['id'] ?? '');
        $soLuong = $input['soluong'] ?? 20;
        $res = SanPhamModel::nhapKhoNhanh($maSP, $soLuong);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_toggle_user_status':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/KhachHangModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maTK = $input['matk'] ?? '';
        $trangThai = $input['trangthai'] ?? null;
        $res = KhachHangModel::doiTrangThaiTaiKhoan($maTK, $trangThai);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_confirm_payment':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/ThanhToanModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $id = $input['id'] ?? ($input['magiaodich'] ?? ($input['madonhang'] ?? ''));
        $res = ThanhToanModel::xacNhanNhanTien($id);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_confirm_refund':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/ThanhToanModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $id = $input['id'] ?? ($input['magiaodich'] ?? ($input['madonhang'] ?? ''));
        $res = ThanhToanModel::xacNhanHoanTien($id);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'chung_tu':
        $ctId = trim($_GET['id'] ?? '');
        $db = Database::getConnection();
        $stmtCT = $db->prepare("SELECT ct.*, t.tentram, t.diachi AS diachitram, t.nguoidaidien, t.sotaikhoan, t.nganhang
                                FROM chung_tu_giai_ngan ct
                                LEFT JOIN tram_cuu_ho t ON ct.matram = t.matram
                                WHERE ct.machungtu = ? LIMIT 1");
        $stmtCT->execute([$ctId]);
        $ct = $stmtCT->fetch();
        if (!$ct) {
            http_response_code(404);
            echo 'Không tìm thấy chứng từ giải ngân.';
            exit;
        }
        $ngayFmt = !empty($ct['ngaychuyenkhoan']) ? date('d/m/Y', strtotime($ct['ngaychuyenkhoan'])) : '--';
        $tienFmt = number_format((float)$ct['sotiengiaingan'], 0, ',', '.') . ' VNĐ';
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
          <meta charset="UTF-8">
          <title>Chứng từ giải ngân #<?= htmlspecialchars($ct['machungtu']) ?> - KIBO</title>
          <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 24px; color: #222; }
            .sheet { max-width: 720px; margin: 0 auto; background: #fff; border: 1px solid #d4d4d4; padding: 28px 32px; }
            .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2c1a0e; padding-bottom: 14px; margin-bottom: 18px; }
            table { width: 100%; border-collapse: collapse; font-size: 14px; }
            td { padding: 9px 6px; border-bottom: 1px solid #eee; vertical-align: top; }
            .lbl { color: #555; width: 34%; }
            .signs { display: flex; justify-content: space-between; text-align: center; margin-top: 32px; font-size: 13px; }
            .btn-bar { max-width: 720px; margin: 0 auto 14px; display: flex; justify-content: flex-end; gap: 10px; }
            @media print { .btn-bar { display: none; } body { background: #fff; padding: 0; } .sheet { border: none; } }
          </style>
        </head>
        <body>
          <div class="btn-bar">
            <button type="button" onclick="window.print()" style="padding: 8px 16px; background: #2c1a0e; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">In / Lưu PDF</button>
          </div>
          <div class="sheet">
            <div class="head">
              <div>
                <h2 style="margin: 0 0 4px; font-size: 18px;">ỦY NHIỆM CHI / CHỨNG TỪ GIẢI NGÂN QUỸ CỨU TRỢ 5%</h2>
                <div style="font-size: 13px; color: #555;">KIBO PET'S HOME · Quỹ cứu trợ động vật minh bạch</div>
              </div>
              <div style="text-align: right;">
                <strong>Mã chứng từ: #<?= htmlspecialchars($ct['machungtu']) ?></strong><br>
                <span style="font-size: 13px; color: #555;">Ngày: <?= htmlspecialchars($ngayFmt) ?></span>
              </div>
            </div>
            <table>
              <tr><td class="lbl">Đơn vị thụ hưởng:</td><td><strong><?= htmlspecialchars($ct['tentram'] ?? 'Trạm cứu hộ đối tác') ?></strong></td></tr>
              <tr><td class="lbl">Người đại diện:</td><td><?= htmlspecialchars($ct['nguoidaidien'] ?? '--') ?></td></tr>
              <tr><td class="lbl">Ngân hàng / STK nhận:</td><td><?= htmlspecialchars(($ct['nganhang'] ?? '--') . ' - ' . ($ct['sotaikhoan'] ?? '--')) ?></td></tr>
              <tr><td class="lbl">Số tiền giải ngân:</td><td><strong style="font-size: 16px;"><?= $tienFmt ?></strong></td></tr>
              <tr><td class="lbl">Nội dung / Mục đích chi:</td><td><?= htmlspecialchars($ct['mucdichsudung'] ?? '') ?></td></tr>
              <tr><td class="lbl">Trạng thái:</td><td><strong>Đã chuyển khoản &amp; đối soát công khai</strong></td></tr>
            </table>
            <div class="signs">
              <div style="width: 45%;"><strong>KẾ TOÁN QUỸ KIBO</strong><br><br><br>Đã xác nhận chi</div>
              <div style="width: 45%;"><strong>ĐẠI DIỆN TRẠM CỨU HỘ</strong><br><br><br>Đã xác nhận nhận tiền</div>
            </div>
          </div>
        </body>
        </html>
        <?php
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
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_edit_category':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maDM = $input['madanhmuc'] ?? '';
        $name = $input['tendanhmuc'] ?? '';
        $desc = $input['mota'] ?? '';
        $res = SanPhamModel::suaDanhMuc($maDM, $name, $desc);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_delete_category':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/SanPhamModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $maDM = $input['madanhmuc'] ?? '';
        $res = SanPhamModel::xoaDanhMuc($maDM);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_update_order_status':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonHangModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $orderId = $input['madonhang'] ?? '';
        $newStatus = $input['trangthai'] ?? '';
        $maVanDon = $input['mavandon'] ?? null;
        $res = DonHangModel::capNhatTrangThai($orderId, $newStatus, $maVanDon);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_print_order':
        requireAdmin();
        require_once __DIR__ . '/models/DonHangModel.php';
        $orderId = $_GET['id'] ?? ($_GET['madonhang'] ?? '');
        $order = DonHangModel::getOrderDetail($orderId);
        if (!$order) {
            die("Không tìm thấy đơn hàng #{$orderId}");
        }
        require __DIR__ . '/views/admin/PrintPackingSlipView.php';
        exit;

    case 'admin_print_batch_orders':
        requireAdmin();
        require_once __DIR__ . '/models/DonHangModel.php';
        require_once __DIR__ . '/models/TrangThai.php';
        $allOrders = DonHangModel::getAll();
        $batchOrders = [];
        foreach ($allOrders as $ao) {
            if (($ao['status'] ?? '') === TrangThai::DH_CHO_DONG_GOI) {
                $det = DonHangModel::getOrderDetail($ao['orderId']);
                if ($det) $batchOrders[] = $det;
            }
        }
        if (empty($batchOrders)) {
            die("Hiện không có đơn hàng nào ở trạng thái 'Chờ đóng gói' để in hàng loạt.");
        }
        $isBatchPrint = true;
        foreach ($batchOrders as $idx => $order) {
            if ($idx > 0) echo '<div style="page-break-before: always;"></div>';
            require __DIR__ . '/views/admin/PrintPackingSlipView.php';
        }
        exit;

    case 'admin_auto_process_orders':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonHangModel.php';
        $res = DonHangModel::autoProcessPendingOrders();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'admin_batch_dispatch_orders':
        requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonHangModel.php';
        $res = DonHangModel::batchDispatchReadyOrders();
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'check_payment_status':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonHangModel.php';
        $orderId = trim($_GET['order'] ?? ($_GET['madonhang'] ?? ''));
        if (empty($orderId)) {
            echo json_encode(['success' => false, 'paid' => false, 'message' => 'Thiếu mã đơn hàng'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $st = DonHangModel::getPaymentAndOrderStatus($orderId);
        if (!empty($st['success']) && empty($st['paid'])) {
            $pm = (string)($st['phuongthuctt'] ?? '');
            if (stripos($pm, 'VietQR') !== false || stripos($pm, 'Online') !== false || stripos($pm, 'PayOS') !== false) {
                require_once __DIR__ . '/models/CongThanhToan.php';
                CongThanhToan::dongBoTrangThaiPayOS($orderId);
                $st = DonHangModel::getPaymentAndOrderStatus($orderId);
            }
        }
        echo json_encode($st, JSON_UNESCAPED_UNICODE);
        exit;

    case 'payment_webhook':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/models/CongThanhToan.php';
        Database::loadEnv();
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        // Cho phép PayOS ping kiểm tra URL webhook khi lưu cấu hình
        $isTestPing = empty($payload['content']) && empty($payload['madonhang']) && empty($payload['orderId'])
                   && empty($payload['data']['description']) && empty($payload['data']['orderCode']);
        if ($isTestPing) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Webhook ready'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Xác thực chữ ký PayOS bằng PAYOS_CHECKSUM_KEY (HMAC-SHA256)
        $payosChecksum = getenv('PAYOS_CHECKSUM_KEY') ?: ($_ENV['PAYOS_CHECKSUM_KEY'] ?? '');
        if (!empty($payosChecksum)) {
            $sig = trim((string)($payload['signature'] ?? ''));
            $d = (isset($payload['data']) && is_array($payload['data'])) ? $payload['data'] : [];
            ksort($d);
            $pairs = [];
            foreach ($d as $k => $v) {
                if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (in_array($v, [null, 'undefined', 'null'], true)) $v = '';
                $pairs[] = $k . '=' . $v;
            }
            $expectedPayosSig = hash_hmac('sha256', implode('&', $pairs), $payosChecksum);
            if (empty($d) || empty($sig) || !hash_equals($expectedPayosSig, strtolower($sig))) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized PayOS Signature'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $res = CongThanhToan::kiemTraGiaoDich($payload);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'shipping_webhook':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/DonViVanChuyen.php';
        $expectedKey = getenv('SHIPPING_WEBHOOK_KEY') ?: ($_ENV['SHIPPING_WEBHOOK_KEY'] ?? '');
        if (!empty($expectedKey)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (stripos($authHeader, $expectedKey) === false) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized Shipping Webhook'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;
        $maDH = trim($payload['madonhang'] ?? ($payload['order_code'] ?? ''));
        $status = trim($payload['trangthai'] ?? ($payload['status'] ?? ''));
        $maVD = trim($payload['mavandon'] ?? ($payload['tracking_code'] ?? ''));
        if (empty($maDH) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu madonhang hoặc trangthai'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $res = DonViVanChuyen::capNhatHanhTrinh($maDH, $status, $maVD ?: null);
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
        $code = trim($input['mavoucher'] ?? ($input['code'] ?? ''));
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

    case 'register_send_otp':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/TaiKhoanModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        echo json_encode(TaiKhoanModel::guiMaOtpDangKy($input), JSON_UNESCAPED_UNICODE);
        exit;

    case 'register_submit':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->register();
        break;

    case 'forgot_password_send_otp':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/TaiKhoanModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        if (!verifyCsrfToken($input)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn (CSRF). Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $identifier = trim($input['identifier'] ?? ($input['email'] ?? ''));
        $res = TaiKhoanModel::guiMaOtpQuenMatKhau($identifier);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'forgot_password_reset':
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/models/TaiKhoanModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        if (!verifyCsrfToken($input)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn (CSRF). Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $identifier = trim($input['identifier'] ?? ($input['email'] ?? ''));
        $otp = trim($input['otp'] ?? '');
        $newPass = $input['new_password'] ?? ($input['password'] ?? '');
        $res = TaiKhoanModel::datLaiMatKhauBangOtp($identifier, $otp, $newPass);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

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
        if (!verifyCsrfToken($input)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn (CSRF). Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // Bảo mật: Chỉ lấy MaKH từ session hiện tại
        $maKH = $currentUser['MaKH'] ?? '';
        $hoten = $input['hoten'] ?? '';
        $phone = $input['phone'] ?? '';
        $address = $input['address'] ?? '';
        $ngaysinh = $input['ngaysinh'] ?? null;
        $phuongxa = $input['phuongxa'] ?? null;
        $tinhthanh = $input['tinhthanh'] ?? null;

        $res = KhachHangModel::updateProfile($maKH, $hoten, $phone, $address, $ngaysinh, $phuongxa, $tinhthanh);
        if ($res['success']) {
            $_SESSION['user']['HoTen'] = $hoten;
            if (!empty($phone)) $_SESSION['user']['SoDienThoai'] = $phone;
            $_SESSION['user']['DiaChi'] = $address;
            if ($phuongxa !== null) $_SESSION['user']['PhuongXa'] = trim((string)$phuongxa);
            if ($tinhthanh !== null) $_SESSION['user']['TinhThanh'] = trim((string)$tinhthanh);
            if (!empty($ngaysinh)) $_SESSION['user']['NgaySinh'] = $ngaysinh;
            if (!empty($res['makh'])) $_SESSION['user']['MaKH'] = $res['makh'];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'change_password':
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (empty($_SESSION['user']['MaTK'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đổi mật khẩu.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require_once __DIR__ . '/models/TaiKhoanModel.php';
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        if (!verifyCsrfToken($input)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn (CSRF). Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $oldPass = $input['old_password'] ?? ($input['matkhaucu'] ?? '');
        $newPass = $input['new_password'] ?? ($input['matkhaumoi'] ?? '');
        $res = TaiKhoanModel::doiMatKhau($_SESSION['user']['MaTK'], $oldPass, $newPass);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;

    case 'logout':
        require_once __DIR__ . '/controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    case 'home':
        require __DIR__ . '/views/customer/HomeView.php';
        break;

    default:
        http_response_code(404);
        $pageTitle = '404 - Không tìm thấy trang | KIBO PET\'S HOME';
        $activeMenu = '';
        require __DIR__ . '/views/customer/header.php';
        echo '<main class="page" style="padding: 80px 20px; text-align: center; min-height: 55vh; display: flex; flex-direction: column; align-items: center; justify-content: center;">';
        echo '<div style="max-width: 520px; background: #fff; padding: 36px 28px; border-radius: 10px; border: 1px solid #e5e1dc;">';
        echo '<h1 style="font-size: 48px; margin: 0 0 8px; color: #2c1a0e;">404</h1>';
        echo '<h2 style="font-size: 20px; margin: 0 0 10px; color: #3c2f27;">Không tìm thấy trang bạn yêu cầu</h2>';
        echo '<p style="color: #6b625a; font-size: 14px; margin: 0 0 22px; line-height: 1.5;">Đường dẫn có thể đã thay đổi hoặc không tồn tại trên hệ thống KIBO.</p>';
        echo '<a href="index.php?action=home" class="btn primary" style="display: inline-block; padding: 10px 22px; text-decoration: none; border-radius: 6px;">Về trang chủ</a>';
        echo '</div></main>';
        require __DIR__ . '/views/customer/footer.php';
        break;
}
} catch (Throwable $e) {
    error_log("Unhandled Exception [action={$action}]: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Kiểm tra request là JSON hay HTML
    $isJson = (
        strpos($action, 'admin_') === 0 || 
        in_array($action, ['create_order', 'check_voucher', 'submit_return', 'sync_cart', 'cancel_order', 'update_profile', 'login_submit'], true) ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
    );

    http_response_code(503);
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Hệ thống đang bảo trì</title><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#faf7f2;color:#2c1a0e;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);text-align:center;max-width:480px;} h1{color:#c94336;margin-top:0;} a{display:inline-block;margin-top:16px;background:#c94336;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;font-weight:600;}</style></head><body><div class="box"><h1>Hệ thống đang bận</h1><p>Hệ thống đang được bảo trì hoặc quá tải tạm thời. Vui lòng thử lại sau ít phút.</p><a href="index.php?action=home">Về trang chủ KIBO</a></div></body></html>';
    }
    exit;
}
