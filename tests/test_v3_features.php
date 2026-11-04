<?php
// ====================================================================
// TEST SUITE: test_v3_features.php
// Kiểm thử các tính năng và bản vá theo kết quả audit của Claude
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/BusinessException.php';
require_once __DIR__ . '/../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../models/SanPhamModel.php';
require_once __DIR__ . '/../models/TaiKhoanModel.php';
require_once __DIR__ . '/../models/KhachHangModel.php';
require_once __DIR__ . '/../models/ThanhToanModel.php';
require_once __DIR__ . '/../models/DonHangModel.php';
require_once __DIR__ . '/../models/NhatKyModel.php';

$db = Database::getConnection();
if (!$db) {
    die("Không thể kết nối CSDL.\n");
}

function checkAssert($name, $desc, $cond) {
    if ($cond) {
        echo "✅ [PASS] {$name}: {$desc}\n";
    } else {
        echo "❌ [FAIL] {$name}: {$desc}\n";
    }
}

echo "====================================================================\n";
echo "   KIBO PLATFORM - VERIFY AUDIT FIXES (v2.3+)\n";
echo "====================================================================\n";

// 1. Kiểm tra Chống giải ngân vượt quỹ
$resOverDisburse = QuyCuuTroModel::giaiNgan('TCH01', 999999999, date('Y-m-d'), 'Test giải ngân quá mức');
checkAssert("FIX-1", "Chống giải ngân vượt quỹ: Từ chối giải ngân vượt quá số dư khả dụng", !$resOverDisburse['success'] && (strpos($resOverDisburse['message'] ?? '', 'số dư') !== false));

// 2. Kiểm tra Tạo sản phẩm chặt chẽ
$resEmptyName = SanPhamModel::themSanPham(['tensp' => '', 'giaban' => 100000, 'madanhmuc' => 'DM01']);
checkAssert("FIX-2A", "Tạo sản phẩm: Chặn tên rỗng", !$resEmptyName['success'] && strpos($resEmptyName['error'], 'Tên sản phẩm không được để trống') !== false);

$resNegPrice = SanPhamModel::themSanPham(['tensp' => 'Hạt Test', 'giaban' => -5000, 'madanhmuc' => 'DM01']);
checkAssert("FIX-2B", "Tạo sản phẩm: Chặn giá âm hoặc bằng 0", !$resNegPrice['success'] && strpos($resNegPrice['error'], 'Giá bán phải là số hợp lệ') !== false);

$resBadCat = SanPhamModel::themSanPham(['tensp' => 'Hạt Test', 'giaban' => 150000, 'madanhmuc' => 'DM_FAKE_999']);
checkAssert("FIX-2C", "Tạo sản phẩm: Báo lỗi thân thiện khi danh mục không tồn tại (không lộ SQL)", !$resBadCat['success'] && strpos($resBadCat['error'], 'không tồn tại') !== false);

// 3. Kiểm tra Trùng tên danh mục
$resDupCat = SanPhamModel::themDanhMuc('Thức Ăn Hạt', 'Mô tả test trùng');
checkAssert("FIX-3", "Tạo danh mục: Chặn trùng tên danh mục", !$resDupCat['success'] && strpos($resDupCat['error'], 'đã tồn tại') !== false);

// 4. Kiểm tra Chặn trùng số điện thoại khi đăng ký
$dupPhoneReg = TaiKhoanModel::registerUser([
    'name' => 'Test User Trùng SĐT',
    'email' => 'test_unique_' . time() . '@test.com',
    'password' => 'MatKhauMoi@2026',
    'phone' => '0772906756' // sdt của KH001
]);
// Nếu sdt đã gắn vào một tài khoản có matk
checkAssert("FIX-4", "Đăng ký: Chặn trùng số điện thoại đã thuộc về tài khoản khác", !$dupPhoneReg['success'] || strpos($dupPhoneReg['message'] ?? '', 'số điện thoại') !== false);

// 5. Kiểm tra Đăng nhập & Thông báo tài khoản bị khóa
// Tạo tài khoản test bị khóa
$testLockedTK = 'TK_LOCK_TEST';
$db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$testLockedTK]);
$db->prepare("INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro, trangthai) VALUES (?, 'locked_user@test.com', ?, 'User', 'Bị khóa')")
   ->execute([$testLockedTK, password_hash('MatKhau@2026', PASSWORD_BCRYPT)]);

$authLocked = TaiKhoanModel::authenticateWithStatus('locked_user@test.com', 'MatKhau@2026');
checkAssert("FIX-5", "Đăng nhập: Báo rõ tài khoản đang bị tạm khóa thay vì báo sai mật khẩu", !$authLocked['success'] && !empty($authLocked['is_banned']) && strpos($authLocked['error'], 'tạm khóa') !== false);

// Dọn dẹp TK test khóa
$db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$testLockedTK]);

// 6. Kiểm tra Sửa sản phẩm & Đổi trạng thái
$allProducts = SanPhamModel::getAllForView(false);
$firstSP = !empty($allProducts) ? $allProducts[0]['id'] : null;
if ($firstSP) {
    $resToggle = SanPhamModel::doiTrangThaiSanPham($firstSP);
    if (!$resToggle['success']) echo "Debug resToggle: " . json_encode($resToggle, JSON_UNESCAPED_UNICODE) . "\n";
    $resToggleBack = SanPhamModel::doiTrangThaiSanPham($firstSP, 'Đang bán');
    if (!$resToggleBack['success']) echo "Debug resToggleBack: " . json_encode($resToggleBack, JSON_UNESCAPED_UNICODE) . "\n";
    checkAssert("FIX-6", "Quản trị sản phẩm: Đổi trạng thái Đang bán <-> Ngừng bán", $resToggle['success'] && $resToggleBack['success']);
} else {
    checkAssert("FIX-6", "Quản trị sản phẩm: Không tìm thấy sản phẩm", false);
}

// 7. Kiểm tra Xác nhận thanh toán
$resConfirm = ThanhToanModel::xacNhanNhanTien('GD_FAKE_NONEXIST');
checkAssert("FIX-7", "Thanh toán: Xử lý an toàn khi giao dịch không tồn tại", !$resConfirm['success']);

echo "====================================================================\n";
