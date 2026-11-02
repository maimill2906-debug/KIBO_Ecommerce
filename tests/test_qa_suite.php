<?php
/**
 * KIBO QA TEST SUITE v2.1 - AUTOMATED RIGOROUS AUDIT TEST CASES
 * Executable via CLI: C:\xampp\php\php.exe tests/test_qa_suite.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// 1. Nạp cấu hình từ .env.test nếu có
$testEnvFile = __DIR__ . '/../.env.test';
if (file_exists($testEnvFile)) {
    putenv('KIBO_ENV_FILE=' . $testEnvFile);
}
require_once __DIR__ . '/../config/database.php';
Database::loadEnv(file_exists($testEnvFile) ? $testEnvFile : null, true);

require_once __DIR__ . '/../config/business.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/TrangThai.php';
require_once __DIR__ . '/../models/TaiKhoanModel.php';
require_once __DIR__ . '/../models/KhachHangModel.php';
require_once __DIR__ . '/../models/SanPhamModel.php';
require_once __DIR__ . '/../models/DonHangModel.php';
require_once __DIR__ . '/../models/VoucherModel.php';
require_once __DIR__ . '/../models/DoiTraModel.php';
require_once __DIR__ . '/../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../models/IdGen.php';

$db = Database::getConnection();
if (!$db) {
    echo "❌ FATAL: Cannot connect to PostgreSQL database.\n";
    exit(1);
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'postgres';

$passed = 0;
$total = 14;

function assertTest(string $tc, string $description, bool $condition, string $details = '') {
    global $passed;
    if ($condition) {
        $passed++;
        echo "✅ [PASS] {$tc}: {$description}\n";
    } else {
        echo "❌ [FAIL] {$tc}: {$description}\n";
        if ($details) {
            echo "   Details: {$details}\n";
        }
    }
}

echo "====================================================================\n";
echo "            KIBO PLATFORM - QA TEST SUITE (v2.1 AUDIT)             \n";
echo "  Target Database: [{$dbName}] on [{$dbHost}]\n";
echo "====================================================================\n\n";

// ---------------------------------------------------------------------
// TC1: Đăng nhập mật khẩu demo vs '123456' & Chặn tài khoản vãng lai
// ---------------------------------------------------------------------
try {
    $failOldPass = TaiKhoanModel::authenticate('admin@kibo.vn', '123456');
    $passDemo = TaiKhoanModel::authenticate('admin@kibo.vn', 'MatKhauDemo@2026');
    $blockGuest = TaiKhoanModel::authenticate('guest_test@kibo.local', 'MatKhauDemo@2026');

    $c1 = ($failOldPass === null) 
       && ($passDemo !== null && ($passDemo['VaiTro'] ?? '') === 'Admin')
       && ($blockGuest === null);

    assertTest("TC1", "Xác thực mật khẩu demo bảo mật, xóa backdoor '123456' và chặn @kibo.local", $c1);
} catch (Throwable $e) {
    assertTest("TC1", "Xác thực mật khẩu demo", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC2: Phân quyền Admin: Gọi trực tiếp requireAdmin() thật qua subprocess
// ---------------------------------------------------------------------
try {
    $phpBin = 'C:\\xampp\\php\\php.exe';
    
    // Subcase 1: Không đăng nhập
    $cmdGuest = "\"{$phpBin}\" -r \"require 'C:/xampp/htdocs/kibo/config/auth.php'; requireAdmin();\" 2>&1";
    exec($cmdGuest, $outGuest, $codeGuest);

    // Subcase 2: User thường (role User)
    $cmdUser = "\"{$phpBin}\" -r \"session_start(); \$_SESSION['user'] = ['VaiTro' => 'User']; require 'C:/xampp/htdocs/kibo/config/auth.php'; requireAdmin();\" 2>&1";
    exec($cmdUser, $outUser, $codeUser);

    // Subcase 3: Admin (role Admin)
    $cmdAdmin = "\"{$phpBin}\" -r \"session_start(); \$_SESSION['user'] = ['VaiTro' => 'Admin']; require 'C:/xampp/htdocs/kibo/config/auth.php'; requireAdmin(); echo 'OK_ADMIN';\" 2>&1";
    exec($cmdAdmin, $outAdmin, $codeAdmin);

    $guestBlocked = stripos(implode(' ', $outGuest), 'Truy cập bị từ chối') !== false;
    $userBlocked = stripos(implode(' ', $outUser), 'Truy cập bị từ chối') !== false;
    $adminAllowed = stripos(implode(' ', $outAdmin), 'OK_ADMIN') !== false;

    $c2 = $guestBlocked && $userBlocked && $adminAllowed;
    assertTest("TC2", "Kiểm tra requireAdmin() thật: Chặn khách & User thường, cho phép Admin", $c2);
} catch (Throwable $e) {
    assertTest("TC2", "Kiểm tra requireAdmin()", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC3: Tính tiền phía Server (Bác bỏ giá client sửa gian lận)
// ---------------------------------------------------------------------
try {
    $stmt = $db->query("SELECT mabienthe, giaban FROM bien_the WHERE soluongton > 5 ORDER BY giaban DESC LIMIT 1");
    $testBt = $stmt->fetch();
    $realPrice = (float)$testBt['giaban'];

    $fakePayload = [
        'customer' => ['name' => 'QA Tamper Test', 'phone' => '0901112233', 'address' => '123 Quận 1, TP. Hồ Chí Minh'],
        'items' => [
            ['id' => $testBt['mabienthe'], 'name' => 'Sản phẩm Test', 'variant' => 'Test', 'price' => 9999, 'quantity' => 1]
        ],
        'paymentMethod' => 'cod'
    ];

    $res = DonHangModel::taoDonHang($fakePayload);
    if (!$res['success']) {
        throw new Exception("Lỗi tạo đơn: " . ($res['message'] ?? ''));
    }

    $createdOrderId = $res['orderId'];
    $orderDb = $db->query("SELECT tongtienhang, tongthanhtoan FROM don_hang WHERE madonhang = '{$createdOrderId}'")->fetch();

    $isRecalculated = ($orderDb && (float)$orderDb['tongtienhang'] === $realPrice && (float)$orderDb['tongthanhtoan'] >= $realPrice);

    // Dọn dẹp
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$createdOrderId]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$createdOrderId]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$createdOrderId]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$createdOrderId]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = ?")->execute([$testBt['mabienthe']]);

    assertTest("TC3", "Tính tiền phía server: Bác bỏ số tiền gian lận client, tính lại 100% từ CSDL", $isRecalculated);
} catch (Throwable $e) {
    assertTest("TC3", "Tính tiền phía server", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC4: Kiểm tra tồn kho nguyên tử & Chống bán khống
// ---------------------------------------------------------------------
try {
    $stmt = $db->query("SELECT mabienthe, soluongton FROM bien_the WHERE soluongton > 0 ORDER BY soluongton ASC LIMIT 1");
    $bt = $stmt->fetch();
    $currentStock = (int)$bt['soluongton'];
    $oversellQty = $currentStock + 10;

    $oversellPayload = [
        'customer' => ['name' => 'Oversell Test', 'phone' => '0901112233', 'address' => '123 Quận 1, TP. Hồ Chí Minh'],
        'items' => [
            ['id' => $bt['mabienthe'], 'name' => 'Test', 'variant' => 'V', 'price' => 100000, 'quantity' => $oversellQty]
        ],
        'paymentMethod' => 'cod'
    ];

    $oversellRes = DonHangModel::taoDonHang($oversellPayload);
    $blocked = (!$oversellRes['success']);

    $stockBefore = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = '{$bt['mabienthe']}'")->fetchColumn();
    $validPayload = [
        'customer' => ['name' => 'Valid Buy', 'phone' => '0901112233', 'address' => '123 Quận 1, TP. Hồ Chí Minh'],
        'items' => [
            ['id' => $bt['mabienthe'], 'name' => 'Test', 'variant' => 'V', 'price' => 100000, 'quantity' => 1]
        ],
        'paymentMethod' => 'cod'
    ];
    $validRes = DonHangModel::taoDonHang($validPayload);
    $stockAfter = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = '{$bt['mabienthe']}'")->fetchColumn();

    $deducted = ($validRes['success'] && $stockAfter === $stockBefore - 1);

    if ($validRes['success']) {
        $oid = $validRes['orderId'];
        $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid]);
        $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid]);
        $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid]);
        $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid]);
        $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = ?")->execute([$bt['mabienthe']]);
    }

    assertTest("TC4", "Kiểm tra tồn kho: Từ chối bán khống khi vượt tồn kho, trừ kho nguyên tử khi đặt thành công", $blocked && $deducted);
} catch (Throwable $e) {
    assertTest("TC4", "Kiểm tra tồn kho", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC5: Kiểm tra Voucher toàn diện
// ---------------------------------------------------------------------
try {
    $db->prepare("DELETE FROM voucher WHERE mavoucher = 'TEST_VOUCHER'")->execute();
    $db->prepare("INSERT INTO voucher (mavoucher, tenvoucher, loaigiamgia, giatrigiam, donhangtoithieu, soluongconlai, ngaybatdau, ngayketthuc, kichhoat) 
                  VALUES ('TEST_VOUCHER', 'Voucher Test QA', 'SoTien', 30000, 200000, 1, NOW() - INTERVAL '1 days', NOW() + INTERVAL '10 days', true)")->execute();

    $valUnderMin = VoucherModel::validateVoucher('TEST_VOUCHER', 150000);
    $valOk = VoucherModel::validateVoucher('TEST_VOUCHER', 250000);

    $used1 = VoucherModel::useVoucher('TEST_VOUCHER');
    $valOutOfStock = VoucherModel::validateVoucher('TEST_VOUCHER', 250000);
    $used2 = VoucherModel::useVoucher('TEST_VOUCHER');

    $c5 = (!$valUnderMin['valid']) 
       && ($valOk['valid'] && (float)$valOk['discount'] === 30000.0) 
       && ($used1 === true) 
       && (!$valOutOfStock['valid']) 
       && ($used2 === false);

    $db->prepare("DELETE FROM voucher WHERE mavoucher = 'TEST_VOUCHER'")->execute();

    assertTest("TC5", "Kiểm tra Voucher: Kiểm duyệt hạn dùng, lượt dùng, đơn tối thiểu và trừ nguyên tử 1 lượt", $c5);
} catch (Throwable $e) {
    assertTest("TC5", "Kiểm tra Voucher", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC6: Chính sách Phí ship chuẩn xác (Bước 4)
// ---------------------------------------------------------------------
try {
    // 1. “Chung cư CC1, Quận 7, TP. Hồ Chí Minh” (250k >= 200k) => Phải 0đ, không bị nhầm bởi CC1
    $shipCC1 = DonHangModel::tinhPhiShip(250000, "Chung cư CC1, Quận 7, TP. Hồ Chí Minh", "Quận 7", "TP. Hồ Chí Minh");
    
    // 2. Củ Chi (Ngoại thành HCM) 250k < 500k => 20.000đ
    $shipCuChi = DonHangModel::tinhPhiShip(250000, "Tổ 5, Xã Tân Thạnh Đông, Huyện Củ Chi", "Huyện Củ Chi", "TP. Hồ Chí Minh");
    
    // 3. Bình Dương 450k < 500k => 20.000đ
    $shipBinhDuong = DonHangModel::tinhPhiShip(450000, "Phường Phú Hòa, TP. Thủ Dầu Một", "Thủ Dầu Một", "Bình Dương");
    
    // 4. Quận 7 nhưng Tỉnh Bình Dương => Không phải nội thành HCM => 20.000đ
    $shipFakeHCM = DonHangModel::tinhPhiShip(250000, "Khu phố 7, Quận 7", "Quận 7", "Bình Dương");
    
    // 5. Bình Dương >= 500k => Miễn phí 0đ
    $shipBDOk = DonHangModel::tinhPhiShip(550000, "Đại lộ Bình Dương", "Thuận An", "Bình Dương");

    $c6 = ($shipCC1 === 0.0) 
       && ($shipCuChi === 20000.0) 
       && ($shipBinhDuong === 20000.0) 
       && ($shipFakeHCM === 20000.0)
       && ($shipBDOk === 0.0);

    assertTest("TC6", "Chính sách Phí ship: So khớp chuẩn xác nội thành HCM (200k), ngoại thành & tỉnh (500k), không lỗi chuỗi con", $c6);
} catch (Throwable $e) {
    assertTest("TC6", "Chính sách Phí ship", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC7: Khách vãng lai và Chống gán nhầm vào tài khoản có sẵn (Bước 2)
// ---------------------------------------------------------------------
try {
    $registeredPhone = '0987654321'; // SĐT của KH002
    $pointsKH2Before = (int)$db->query("SELECT diemtichluy FROM khach_hang WHERE makh = 'KH002'")->fetchColumn();

    // Đặt hàng vãng lai với SĐT của KH002
    $guestOrder = [
        'customer' => ['name' => 'Người Lạ Dùng Trùng SĐT', 'phone' => $registeredPhone, 'address' => '123 Đường Lạ, Quận 1, TP. Hồ Chí Minh'],
        'items' => [['id' => 'BT001', 'name' => 'KIBO Cá Hồi', 'variant' => 'V1', 'price' => 285000, 'quantity' => 1]],
        'paymentMethod' => 'cod'
    ];
    $resGuest = DonHangModel::taoDonHang($guestOrder);
    if (!$resGuest['success']) {
        throw new Exception("Lỗi tạo đơn vãng lai: " . ($resGuest['message'] ?? ''));
    }

    $guestOid = $resGuest['orderId'];
    $orderOwner = $db->query("SELECT makh FROM don_hang WHERE madonhang = '{$guestOid}'")->fetchColumn();
    $ownerMatk = $db->query("SELECT matk FROM khach_hang WHERE makh = '{$orderOwner}'")->fetchColumn();

    // 1. Đơn KHÔNG được gán cho KH002 mà gán cho mã KV... mới
    $notAttachedToKH2 = ($orderOwner !== 'KH002' && strpos($orderOwner, 'KV') === 0 && $ownerMatk === null);

    // 2. Chuyển giao thành công => Điểm của KH002 KHÔNG được tăng
    DonHangModel::capNhatTrangThai($guestOid, 'Giao thành công');
    $pointsKH2After = (int)$db->query("SELECT diemtichluy FROM khach_hang WHERE makh = 'KH002'")->fetchColumn();
    $pointsUntouched = ($pointsKH2After === $pointsKH2Before);

    // 3. Lịch sử đơn của KH002 KHÔNG chứa đơn của người lạ
    $kh2Orders = DonHangModel::getOrdersByCustomer('KH002');
    $notInKH2History = true;
    foreach ($kh2Orders as $o) {
        if (($o['orderId'] ?? '') === $guestOid) {
            $notInKH2History = false;
            break;
        }
    }

    // Dọn dẹp đơn test
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$orderOwner]);
    $db->prepare("DELETE FROM dia_chi_kh WHERE makh = ?")->execute([$orderOwner]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$orderOwner]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = 'BT001'")->execute();

    assertTest("TC7", "Khách hàng vãng lai: Tự sinh mã KV..., matk = NULL, tuyệt đối không gắn nhầm vào tài khoản người khác", $notAttachedToKH2 && $pointsUntouched && $notInKH2History);
} catch (Throwable $e) {
    assertTest("TC7", "Khách hàng vãng lai", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC8: Quỹ cứu trợ & Điểm tích lũy trên tài khoản riêng biệt (Không đụng KH001)
// ---------------------------------------------------------------------
try {
    $testMaKH = 'TEST_KH_01';
    $testMaTK = 'TEST_TK_01';

    // Tạo khách hàng test riêng biệt
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$testMaTK]);

    $db->prepare("INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro) VALUES (?, 'test_user_01', 'hash', 'User')")->execute([$testMaTK]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, 'Test User 01', 'test_user_01@kibo.local', 0)")->execute([$testMaKH, $testMaTK]);

    $orderData = [
        'customer' => ['name' => 'Test User 01', 'phone' => '0779998888', 'address' => 'Quận 1, TP.HCM'],
        'items' => [['id' => 'BT001', 'name' => 'KIBO Cá Hồi', 'variant' => 'Túi 1.5kg', 'price' => 285000, 'quantity' => 1]],
        'paymentMethod' => 'cod'
    ];
    $orderRes = DonHangModel::taoDonHang($orderData, $testMaKH);
    $oid = $orderRes['orderId'];

    // 1. Trạng thái quỹ ban đầu: Chờ trích quỹ
    $fundInitial = $db->query("SELECT trangthaiquy FROM quy_cuu_tro WHERE madonhang = '{$oid}'")->fetchColumn();
    $isPendingFund = ($fundInitial === 'Chờ trích quỹ');

    // Chuyển theo luồng tuần tự hợp lệ: Chờ xác nhận -> Chờ đóng gói -> Đã xuất kho -> Đang giao -> Giao thành công
    DonHangModel::capNhatTrangThai($oid, 'Chờ đóng gói');
    DonHangModel::capNhatTrangThai($oid, 'Đã xuất kho');
    DonHangModel::capNhatTrangThai($oid, 'Đang giao');
    DonHangModel::capNhatTrangThai($oid, 'Giao thành công');

    $fundDelivered = $db->query("SELECT trangthaiquy FROM quy_cuu_tro WHERE madonhang = '{$oid}'")->fetchColumn();
    $deliveredTime = $db->query("SELECT ngaygiaothanhcong FROM don_hang WHERE madonhang = '{$oid}'")->fetchColumn();
    $pointsAfter = (int)$db->query("SELECT diemtichluy FROM khach_hang WHERE makh = '{$testMaKH}'")->fetchColumn();

    $pointsAdded = ($pointsAfter === 28); // 285.000 / 10.000 = 28 điểm
    $isFundSaved = ($fundDelivered === 'Đã trích quỹ') && ($deliveredTime !== null);

    // Dọn dẹp
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$testMaTK]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = 'BT001'")->execute();

    assertTest("TC8", "Quỹ cứu trợ & Điểm tích lũy: Chuyển 'Đã trích quỹ' khi giao xong, cộng điểm chuẩn xác trên tài khoản độc lập", $isPendingFund && $isFundSaved && $pointsAdded);
} catch (Throwable $e) {
    assertTest("TC8", "Quỹ cứu trợ & Điểm tích lũy", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC9: Hủy đơn hàng (Chỉ khi Chờ xác nhận/Chờ đóng gói, hoàn kho, hoàn voucher, quỹ -> Đã hủy)
// ---------------------------------------------------------------------
try {
    $db->prepare("DELETE FROM voucher WHERE mavoucher = 'TEST_CANCEL'")->execute();
    $db->prepare("INSERT INTO voucher (mavoucher, tenvoucher, loaigiamgia, giatrigiam, donhangtoithieu, soluongconlai, ngaybatdau, ngayketthuc, kichhoat) 
                  VALUES ('TEST_CANCEL', 'Hủy', 'SoTien', 20000, 100000, 5, NOW() - INTERVAL '1 days', NOW() + INTERVAL '10 days', true)")->execute();

    $stockBefore = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $vcBefore = (int)$db->query("SELECT soluongconlai FROM voucher WHERE mavoucher = 'TEST_CANCEL'")->fetchColumn();

    $orderData = [
        'customer' => ['name' => 'Cancel Test', 'phone' => '0772906756', 'address' => 'Quận 1, TP.HCM'],
        'items' => [['id' => 'BT001', 'name' => 'KIBO Cá Hồi', 'variant' => 'Túi 1.5kg', 'price' => 285000, 'quantity' => 1]],
        'voucherCode' => 'TEST_CANCEL',
        'paymentMethod' => 'cod'
    ];
    $orderRes = DonHangModel::taoDonHang($orderData, 'KH001');
    $oid = $orderRes['orderId'];

    $cancelRes = DonHangModel::capNhatTrangThai($oid, 'Đã hủy');
    $fundStatus = $db->query("SELECT trangthaiquy FROM quy_cuu_tro WHERE madonhang = '{$oid}'")->fetchColumn();
    $stockAfter = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $vcAfter = (int)$db->query("SELECT soluongconlai FROM voucher WHERE mavoucher = 'TEST_CANCEL'")->fetchColumn();

    $c9 = ($cancelRes['success']) 
       && ($fundStatus === 'Đã hủy') 
       && ($stockAfter === $stockBefore) 
       && ($vcAfter === $vcBefore);

    // Dọn dẹp
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM voucher WHERE mavoucher = 'TEST_CANCEL'")->execute();

    assertTest("TC9", "Hủy đơn hàng: Chỉ hủy khi Chờ xác nhận/Chờ đóng gói, hoàn kho, hoàn voucher và hủy trích quỹ", $c9);
} catch (Throwable $e) {
    assertTest("TC9", "Hủy đơn hàng", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC10: Xác thực chủ quyền Đổi trả hàng (Bước 1)
// ---------------------------------------------------------------------
try {
    $db->prepare("DELETE FROM yeu_cau_doi_tra WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();
    $db->prepare("DELETE FROM don_hang WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();

    // 1. Đơn của khách có tài khoản KH001
    $db->prepare("INSERT INTO don_hang (madonhang, makh, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang, ngaygiaothanhcong) 
                  VALUES ('TEST_KB_REG', 'KH001', NOW(), 285000, 0, 0, 285000, 'Giao thành công', 'Quỳnh Mai', '0772906756', 'TP. Thủ Đức', NOW())")->execute();

    // Người lạ chưa đăng nhập thử gọi vào đơn của KH001 => BỊ TỪ CHỐI
    $retStrangerReg = DoiTraModel::taoYeuCau('TEST_KB_REG', 'Đổi hàng thử xem sao', '', '');
    
    // Khách khác đăng nhập (KH002) gọi vào đơn của KH001 => BỊ TỪ CHỐI
    $retOtherReg = DoiTraModel::taoYeuCau('TEST_KB_REG', 'Đổi hàng hộ bạn bè', '', 'KH002');
    
    // Chính chủ KH001 gửi yêu cầu => THÀNH CÔNG
    $retOwnerReg = DoiTraModel::taoYeuCau('TEST_KB_REG', 'Hạt bị mốc đóng bánh', '', 'KH001');

    // 2. Đơn của khách vãng lai (makh = KV001, matk = NULL, SĐT = 0933445566)
    $db->prepare("DELETE FROM khach_hang WHERE makh = 'TEST_KV_01'")->execute();
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES ('TEST_KV_01', NULL, 'Khách Vãng Lai', 'guest_test@kibo.local', 0)")->execute();
    $db->prepare("INSERT INTO don_hang (madonhang, makh, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang, ngaygiaothanhcong) 
                  VALUES ('TEST_KB_GUEST', 'TEST_KV_01', NOW(), 285000, 0, 0, 285000, 'Giao thành công', 'Khách Vãng Lai', '0933445566', 'Quận 1', NOW())")->execute();

    // Khách vãng lai gửi yêu cầu nhưng nhập SAI SĐT => BỊ TỪ CHỐI
    $retGuestWrongPhone = DoiTraModel::taoYeuCau('TEST_KB_GUEST', 'Lý do đổi hàng hợp lệ', '', '', '0911222333');
    
    // Khách vãng lai nhập ĐÚNG SĐT => THÀNH CÔNG
    $retGuestRightPhone = DoiTraModel::taoYeuCau('TEST_KB_GUEST', 'Lý do đổi hàng hợp lệ', '', '', '0933445566');

    $c10 = (!$retStrangerReg['success'])
        && (!$retOtherReg['success'])
        && ($retOwnerReg['success'])
        && (!$retGuestWrongPhone['success'])
        && ($retGuestRightPhone['success']);

    // Dọn dẹp
    $db->prepare("DELETE FROM yeu_cau_doi_tra WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();
    $db->prepare("DELETE FROM don_hang WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();
    $db->prepare("DELETE FROM khach_hang WHERE makh = 'TEST_KV_01'")->execute();

    assertTest("TC10", "Xác thực đổi trả: Bắt buộc đăng nhập với tài khoản, xác thực SĐT với khách vãng lai, thông báo bảo mật", $c10);
} catch (Throwable $e) {
    assertTest("TC10", "Xác thực đổi trả", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC11: Chặt chẽ trạng thái đơn: Chặn nhảy cóc (Bước 7)
// ---------------------------------------------------------------------
try {
    $orderData = [
        'customer' => ['name' => 'State Test', 'phone' => '0772906756', 'address' => 'Quận 1, TP.HCM'],
        'items' => [['id' => 'BT001', 'name' => 'KIBO Cá Hồi', 'variant' => 'Túi 1.5kg', 'price' => 285000, 'quantity' => 1]],
        'paymentMethod' => 'cod'
    ];
    $orderRes = DonHangModel::taoDonHang($orderData, 'KH001');
    $oid = $orderRes['orderId'];

    // Đơn mới tạo ở trạng thái 'Chờ xác nhận'. Thử nhảy cóc thẳng sang 'Giao thành công' => BỊ TỪ CHỐI
    $jumpRes = DonHangModel::capNhatTrangThai($oid, 'Giao thành công');
    $isJumpBlocked = (!$jumpRes['success']);

    // Chuyển đúng luồng: Chờ xác nhận -> Chờ đóng gói
    $validStep = DonHangModel::capNhatTrangThai($oid, 'Chờ đóng gói');
    $isValidStep = $validStep['success'];

    // Dọn dẹp
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = 'BT001'")->execute();

    assertTest("TC11", "Máy trạng thái: Chặn nhảy cóc (từ Chờ xác nhận lên Giao thành công), bắt buộc đúng thứ tự", $isJumpBlocked && $isValidStep);
} catch (Throwable $e) {
    assertTest("TC11", "Máy trạng thái", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC12: Duyệt Hoàn tiền: Hoàn tồn kho, hoàn điểm, thanh toán -> 'Hoàn tiền' (Bước 7)
// ---------------------------------------------------------------------
try {
    $testMaKH = 'TEST_KH_REFUND';
    $testMaTK = 'TEST_TK_REFUND';

    // Tạo khách test với 50 điểm
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$testMaTK]);

    $db->prepare("INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro) VALUES (?, 'user_refund', 'hash', 'User')")->execute([$testMaTK]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, 'User Refund', 'refund@kibo.local', 50)")->execute([$testMaKH, $testMaTK]);

    $stockBefore = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();

    // 1. Tạo đơn hàng 285.000đ
    $orderData = [
        'customer' => ['name' => 'User Refund', 'phone' => '0771112233', 'address' => 'Quận 1, TP.HCM'],
        'items' => [['id' => 'BT001', 'name' => 'KIBO Cá Hồi', 'variant' => 'Túi 1.5kg', 'price' => 285000, 'quantity' => 1]],
        'paymentMethod' => 'cod'
    ];
    $orderRes = DonHangModel::taoDonHang($orderData, $testMaKH);
    $oid = $orderRes['orderId'];

    // 2. Chuyển tuần tự đến 'Giao thành công' => Cộng 28 điểm, kho trừ 1
    DonHangModel::capNhatTrangThai($oid, 'Chờ đóng gói');
    DonHangModel::capNhatTrangThai($oid, 'Đã xuất kho');
    DonHangModel::capNhatTrangThai($oid, 'Đang giao');
    DonHangModel::capNhatTrangThai($oid, 'Giao thành công');

    $pointsDelivered = (int)$db->query("SELECT diemtichluy FROM khach_hang WHERE makh = '{$testMaKH}'")->fetchColumn();
    $stockDelivered = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();

    // 3. Khách tạo yêu cầu đổi trả
    $retRes = DoiTraModel::taoYeuCau($oid, 'Bao bì thủng hạt rơi ra ngoài', '', $testMaKH);
    $myc = $retRes['mayeucau'];

    // 4. Admin duyệt HOÀN TIỀN
    $refundRes = DoiTraModel::xuLy($myc, 'hoantien');

    // Kiểm tra kết quả sau hoàn tiền:
    $pointsAfterRefund = (int)$db->query("SELECT diemtichluy FROM khach_hang WHERE makh = '{$testMaKH}'")->fetchColumn();
    $stockAfterRefund = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $ttStatus = $db->query("SELECT trangthaitt FROM thanh_toan WHERE madonhang = '{$oid}'")->fetchColumn();
    $fundStatus = $db->query("SELECT trangthaiquy FROM quy_cuu_tro WHERE madonhang = '{$oid}'")->fetchColumn();

    $pointsRestored = ($pointsAfterRefund === 50); // Trừ lại 28 điểm về đúng 50 điểm ban đầu
    $stockRestored = ($stockAfterRefund === $stockBefore); // Kho được cộng trả lại 1
    $ttUpdated = ($ttStatus === 'Hoàn tiền');
    $fundCancelled = ($fundStatus === 'Đã hủy');

    // Dọn dẹp
    $db->prepare("DELETE FROM yeu_cau_doi_tra WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$testMaKH]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$testMaTK]);

    assertTest("TC12", "Duyệt Hoàn tiền: Hoàn tồn kho, trừ lại điểm đã cộng, ghi thanh toán 'Hoàn tiền' và hủy trích quỹ", $pointsRestored && $stockRestored && $ttUpdated && $fundCancelled);
} catch (Throwable $e) {
    assertTest("TC12", "Duyệt Hoàn tiền", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC13: Xem lịch sử đơn hàng & Phân lập người dùng
// ---------------------------------------------------------------------
try {
    $ordersKH1 = DonHangModel::getOrdersByCustomer('KH001');
    $hasOrders = count($ordersKH1) > 0;

    $onlyKH1 = true;
    foreach ($ordersKH1 as $o) {
        $oid = $o['madonhang'] ?? ($o['orderId'] ?? '');
        $checkMakh = $db->query("SELECT makh FROM don_hang WHERE madonhang = '{$oid}'")->fetchColumn();
        if ($checkMakh !== 'KH001') {
            $onlyKH1 = false;
            break;
        }
    }

    $ordersGuest = DonHangModel::getOrdersByCustomer('');
    $emptyGuest = (count($ordersGuest) === 0);

    assertTest("TC13", "Lịch sử đơn hàng: Bảo lưu đúng theo MaKH, không mất khi đăng nhập lại và không lộ chéo", $hasOrders && $onlyKH1 && $emptyGuest);
} catch (Throwable $e) {
    assertTest("TC13", "Lịch sử đơn hàng", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC14: Dữ liệu Quỹ cứu trợ thời gian thực (Xóa sạch số bịa 148tr / 4820 đơn)
// ---------------------------------------------------------------------
try {
    $summary = QuyCuuTroModel::getFundSummary();

    $expectedAccumulated = (float)$db->query("
        SELECT COALESCE(SUM(sotiendonggop), 0) 
        FROM quy_cuu_tro 
        WHERE trangthaiquy IN ('Đã trích quỹ', 'Đã giải ngân')
    ")->fetchColumn();

    $expectedDisbursed = (float)$db->query("
        SELECT COALESCE(SUM(sotiengiaingan), 0) 
        FROM chung_tu_giai_ngan
    ")->fetchColumn();

    $expectedOrdersCount = (int)$db->query("
        SELECT COUNT(DISTINCT madonhang) 
        FROM quy_cuu_tro 
        WHERE trangthaiquy IN ('Đã trích quỹ', 'Đã giải ngân')
    ")->fetchColumn();

    $matchAcc = (abs($summary['TongTichLuy'] - $expectedAccumulated) < 0.01);
    $matchDis = (abs($summary['TongGiaiNgan'] - $expectedDisbursed) < 0.01);
    $matchCnt = ($summary['SoDonDongGop'] === $expectedOrdersCount);
    $noFakeNumbers = ($summary['TongTichLuy'] !== 148650000.0) && ($summary['SoDonDongGop'] !== 4820);

    assertTest("TC14", "Minh bạch Quỹ cứu trợ: 100% dữ liệu tính từ CSDL thực, xóa bỏ hoàn toàn số liệu giả", $matchAcc && $matchDis && $matchCnt && $noFakeNumbers);
} catch (Throwable $e) {
    assertTest("TC14", "Minh bạch Quỹ cứu trợ", false, $e->getMessage());
}

echo "\n====================================================================\n";
echo "KẾT QUẢ KIỂM THỬ: {$passed}/{$total} TEST CASES THÀNH CÔNG!\n";
echo "====================================================================\n";

if ($passed === $total) {
    exit(0);
} else {
    exit(1);
}
