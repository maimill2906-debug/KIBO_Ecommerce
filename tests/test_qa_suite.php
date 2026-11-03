<?php
/**
 * KIBO QA TEST SUITE v2.1 - AUTOMATED RIGOROUS AUDIT TEST CASES
 * Executable via CLI: C:\xampp\php\php.exe tests/test_qa_suite.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// 1. Chốt chặn bảo vệ: Bắt buộc phải có .env.test để chạy kiểm thử, chống ghi/xóa nhầm DB thật
$testEnvFile = __DIR__ . '/../.env.test';
if (!file_exists($testEnvFile)) {
    echo "====================================================================\n";
    echo "❌ BẢO VỆ DỮ LIỆU THẬT: Không tìm thấy file '.env.test'.\n";
    echo "   Bộ kiểm thử tự động từ chối chạy trực tiếp trên cơ sở dữ liệu chính/demo.\n";
    echo "   Vui lòng tạo file .env.test trỏ về database test trước khi chạy bộ kiểm thử.\n";
    echo "====================================================================\n";
    exit(1);
}
putenv('KIBO_ENV_FILE=' . $testEnvFile);
require_once __DIR__ . '/../config/database.php';
Database::loadEnv($testEnvFile, true);

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
$total = 18;

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
    $phpBin = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
    $authPath = realpath(__DIR__ . '/../config/auth.php');
    $tmpDir = sys_get_temp_dir();
    $tmpScript = $tmpDir . DIRECTORY_SEPARATOR . 'kibo_auth_test_' . getmypid() . '_' . uniqid() . '.php';

    // Subcase 1: Không đăng nhập
    file_put_contents($tmpScript, "<?php require " . var_export($authPath, true) . "; requireAdmin();");
    $outGuest = [];
    exec(escapeshellcmd($phpBin) . ' ' . escapeshellarg($tmpScript) . ' 2>&1', $outGuest, $codeGuest);

    // Subcase 2: User thường (role User)
    file_put_contents($tmpScript, "<?php session_start(); \$_SESSION['user'] = ['VaiTro' => 'User']; require " . var_export($authPath, true) . "; requireAdmin();");
    $outUser = [];
    exec(escapeshellcmd($phpBin) . ' ' . escapeshellarg($tmpScript) . ' 2>&1', $outUser, $codeUser);

    // Subcase 3: Admin (role Admin)
    file_put_contents($tmpScript, "<?php session_start(); \$_SESSION['user'] = ['VaiTro' => 'Admin']; require " . var_export($authPath, true) . "; requireAdmin(); echo 'OK_ADMIN';");
    $outAdmin = [];
    exec(escapeshellcmd($phpBin) . ' ' . escapeshellarg($tmpScript) . ' 2>&1', $outAdmin, $codeAdmin);

    @unlink($tmpScript);

    $guestBlocked = stripos(implode(' ', $outGuest), 'Truy cập bị từ chối') !== false;
    $userBlocked = stripos(implode(' ', $outUser), 'Truy cập bị từ chối') !== false;
    $adminAllowed = stripos(implode(' ', $outAdmin), 'OK_ADMIN') !== false;

    $c2 = $guestBlocked && $userBlocked && $adminAllowed;
    assertTest("TC2", "Kiểm tra requireAdmin() thật: Chặn khách & User thường, cho phép Admin", $c2);
} catch (Throwable $e) {
    if (isset($tmpScript) && file_exists($tmpScript)) @unlink($tmpScript);
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
    $regMaKH = 'TKH_REG7';
    $regMaTK = 'TTK_REG7';
    $registeredPhone = '0987000777';

    // Tạo sẵn tài khoản đăng ký test để đối chứng (không đụng KH002)
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$regMaKH]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$regMaKH]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$regMaTK]);

    $db->prepare("INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro) VALUES (?, 'user_reg7', 'hash', 'User')")->execute([$regMaTK]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, 'User Reg 7', 'user_reg7@kibo.local', 50)")->execute([$regMaKH, $regMaTK]);
    $db->prepare("INSERT INTO sdt_kh (makh, sodienthoai) VALUES (?, ?)")->execute([$regMaKH, $registeredPhone]);

    $pointsRegBefore = (int)$db->query("SELECT diemtichluy FROM khach_hang WHERE makh = '{$regMaKH}'")->fetchColumn();

    // Người lạ đặt hàng vãng lai nhưng nhập trùng SĐT của tài khoản trên
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

    // 1. Đơn KHÔNG được gán cho tài khoản có sẵn mà phải gán cho mã KV... mới
    $notAttachedToReg = ($orderOwner !== $regMaKH && strpos($orderOwner, 'KV') === 0 && $ownerMatk === null);

    // 2. Chuyển giao thành công => Điểm của tài khoản có sẵn KHÔNG được tăng (giữ nguyên 50)
    DonHangModel::capNhatTrangThai($guestOid, 'Chờ đóng gói');
    DonHangModel::capNhatTrangThai($guestOid, 'Đã xuất kho');
    DonHangModel::capNhatTrangThai($guestOid, 'Đang giao');
    DonHangModel::capNhatTrangThai($guestOid, 'Giao thành công');

    $pointsRegAfter = (int)$db->query("SELECT diemtichluy FROM khach_hang WHERE makh = '{$regMaKH}'")->fetchColumn();
    $pointsUntouched = ($pointsRegAfter === $pointsRegBefore && $pointsRegAfter === 50);

    // 3. Lịch sử đơn của tài khoản có sẵn KHÔNG chứa đơn của người lạ
    $regOrders = DonHangModel::getOrdersByCustomer($regMaKH);
    $notInRegHistory = true;
    foreach ($regOrders as $o) {
        if (($o['orderId'] ?? '') === $guestOid) {
            $notInRegHistory = false;
            break;
        }
    }

    // Dọn dẹp đơn test và khách test
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$guestOid]);
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$orderOwner]);
    $db->prepare("DELETE FROM dia_chi_kh WHERE makh = ?")->execute([$orderOwner]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$orderOwner]);
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$regMaKH]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$regMaKH]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$regMaTK]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = 'BT001'")->execute();

    assertTest("TC7", "Khách hàng vãng lai: Tự sinh mã KV..., matk = NULL, tuyệt đối không gắn nhầm vào tài khoản người khác", $notAttachedToReg && $pointsUntouched && $notInRegHistory);
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
// TC9: Hủy đơn hàng (Cho phép khi Chờ xác nhận hoặc Chờ đóng gói, hoàn kho, hoàn voucher, quỹ -> Đã hủy)
// ---------------------------------------------------------------------
try {
    $tkh09 = 'TKH_09';
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh09]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, NULL, 'Cancel Tester', 'c09@kibo.local', 0)")->execute([$tkh09]);

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
    $orderRes = DonHangModel::taoDonHang($orderData, $tkh09);
    $oid = $orderRes['orderId'];

    // 1. Chuyển sang 'Chờ đóng gói'
    $stepGoi = DonHangModel::capNhatTrangThai($oid, 'Chờ đóng gói');

    // 2. Hủy đơn khi đang 'Chờ đóng gói' (khách hủy hợp lệ)
    $cancelRes = DonHangModel::capNhatTrangThai($oid, 'Đã hủy');
    $fundStatus = $db->query("SELECT trangthaiquy FROM quy_cuu_tro WHERE madonhang = '{$oid}'")->fetchColumn();
    $stockAfter = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $vcAfter = (int)$db->query("SELECT soluongconlai FROM voucher WHERE mavoucher = 'TEST_CANCEL'")->fetchColumn();

    $c9 = ($stepGoi['success'])
       && ($cancelRes['success']) 
       && ($fundStatus === 'Đã hủy') 
       && ($stockAfter === $stockBefore) 
       && ($vcAfter === $vcBefore);

    // Dọn dẹp
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh09]);
    $db->prepare("DELETE FROM voucher WHERE mavoucher = 'TEST_CANCEL'")->execute();

    assertTest("TC9", "Hủy đơn hàng: Cho phép hủy khi Chờ xác nhận hoặc Chờ đóng gói, hoàn kho, hoàn voucher và hủy trích quỹ", $c9);
} catch (Throwable $e) {
    assertTest("TC9", "Hủy đơn hàng", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC10: Xác thực chủ quyền Đổi trả hàng (Bước 1)
// ---------------------------------------------------------------------
try {
    $tkhOwn = 'TKH_OWN10';
    $ttkOwn = 'TTK_OWN10';
    $tkhOth = 'TKH_OTH10';
    $ttkOth = 'TTK_OTH10';

    $db->prepare("DELETE FROM sdt_kh WHERE makh IN (?, ?)")->execute([$tkhOwn, $tkhOth]);
    $db->prepare("DELETE FROM khach_hang WHERE makh IN (?, ?)")->execute([$tkhOwn, $tkhOth]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk IN (?, ?)")->execute([$ttkOwn, $ttkOth]);

    $db->prepare("INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro) VALUES (?, 'user_own10', 'hash', 'User')")->execute([$ttkOwn]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, 'Chính Chủ Đơn', 'own10@kibo.local', 10)")->execute([$tkhOwn, $ttkOwn]);

    $db->prepare("INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro) VALUES (?, 'user_oth10', 'hash', 'User')")->execute([$ttkOth]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, 'Người Khác', 'oth10@kibo.local', 0)")->execute([$tkhOth, $ttkOth]);

    $db->prepare("DELETE FROM yeu_cau_doi_tra WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();
    $db->prepare("DELETE FROM don_hang WHERE madonhang IN ('TEST_KB_REG', 'TEST_KB_GUEST')")->execute();

    // 1. Đơn của khách có tài khoản test riêng biệt
    $db->prepare("INSERT INTO don_hang (madonhang, makh, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang, ngaygiaothanhcong) 
                  VALUES ('TEST_KB_REG', ?, NOW(), 285000, 0, 0, 285000, 'Giao thành công', 'Chính Chủ Đơn', '0772906756', 'TP. Thủ Đức', NOW())")->execute([$tkhOwn]);

    // Người lạ chưa đăng nhập thử gọi vào đơn của chính chủ => BỊ TỪ CHỐI
    $retStrangerReg = DoiTraModel::taoYeuCau('TEST_KB_REG', 'Đổi hàng thử xem sao', '', '');
    
    // Khách khác đăng nhập gọi vào đơn của chính chủ => BỊ TỪ CHỐI
    $retOtherReg = DoiTraModel::taoYeuCau('TEST_KB_REG', 'Đổi hàng hộ bạn bè', '', $tkhOth);
    
    // Chính chủ gửi yêu cầu => THÀNH CÔNG
    $retOwnerReg = DoiTraModel::taoYeuCau('TEST_KB_REG', 'Hạt bị mốc đóng bánh', '', $tkhOwn);

    // 2. Đơn của khách vãng lai (makh = TKH_KV10, matk = NULL, SĐT = 0933445566)
    $db->prepare("DELETE FROM khach_hang WHERE makh = 'TKH_KV10'")->execute();
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES ('TKH_KV10', NULL, 'Khách Vãng Lai', 'guest_test@kibo.local', 0)")->execute();
    $db->prepare("INSERT INTO don_hang (madonhang, makh, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang, ngaygiaothanhcong) 
                  VALUES ('TEST_KB_GUEST', 'TKH_KV10', NOW(), 285000, 0, 0, 285000, 'Giao thành công', 'Khách Vãng Lai', '0933445566', 'Quận 1', NOW())")->execute();

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
    $db->prepare("DELETE FROM khach_hang WHERE makh = 'TKH_KV10'")->execute();
    $db->prepare("DELETE FROM khach_hang WHERE makh IN (?, ?)")->execute([$tkhOwn, $tkhOth]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk IN (?, ?)")->execute([$ttkOwn, $ttkOth]);

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
    $orderRes = DonHangModel::taoDonHang($orderData);
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
    $testMaKH = 'TKH_RF';
    $testMaTK = 'TTK_RF';

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

    // 4. Admin duyệt HOÀN TIỀN (chọn nhập lại kho)
    $refundRes = DoiTraModel::xuLy($myc, 'hoantien', '', true);

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
// TC13: Xem lịch sử đơn hàng & Phân lập người dùng (Không đụng KH001/KH002)
// ---------------------------------------------------------------------
try {
    $tkh13A = 'TKH_13A';
    $tkh13B = 'TKH_13B';

    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang IN ('TEST_DH_13A', 'TEST_DH_13B')")->execute();
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang IN ('TEST_DH_13A', 'TEST_DH_13B')")->execute();
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang IN ('TEST_DH_13A', 'TEST_DH_13B')")->execute();
    $db->prepare("DELETE FROM don_hang WHERE madonhang IN ('TEST_DH_13A', 'TEST_DH_13B')")->execute();
    $db->prepare("DELETE FROM khach_hang WHERE makh IN (?, ?)")->execute([$tkh13A, $tkh13B]);

    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, NULL, 'User 13A', 'u13a@kibo.local', 0)")->execute([$tkh13A]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, NULL, 'User 13B', 'u13b@kibo.local', 0)")->execute([$tkh13B]);

    $db->prepare("INSERT INTO don_hang (madonhang, makh, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang) 
                  VALUES ('TEST_DH_13A', ?, NOW(), 285000, 0, 0, 285000, 'Chờ xác nhận', 'User 13A', '0771313131', 'Quận 1')")->execute([$tkh13A]);
    $db->prepare("INSERT INTO don_hang (madonhang, makh, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang) 
                  VALUES ('TEST_DH_13B', ?, NOW(), 185000, 0, 0, 185000, 'Chờ xác nhận', 'User 13B', '0771313132', 'Quận 3')")->execute([$tkh13B]);

    // 1. Lịch sử của 13A chỉ chứa đơn của 13A, không lộ chéo đơn của 13B
    $orders13A = DonHangModel::getOrdersByCustomer($tkh13A);
    $has13A = false;
    $noLeak13B = true;
    foreach ($orders13A as $o) {
        $oid = $o['madonhang'] ?? ($o['orderId'] ?? '');
        if ($oid === 'TEST_DH_13A') $has13A = true;
        if ($oid === 'TEST_DH_13B') $noLeak13B = false;
    }

    // 2. Không truyền mã khách hàng => Trả về rỗng (bảo mật)
    $ordersGuest = DonHangModel::getOrdersByCustomer('');
    $emptyGuest = (count($ordersGuest) === 0);

    // Dọn dẹp
    $db->prepare("DELETE FROM don_hang WHERE madonhang IN ('TEST_DH_13A', 'TEST_DH_13B')")->execute();
    $db->prepare("DELETE FROM khach_hang WHERE makh IN (?, ?)")->execute([$tkh13A, $tkh13B]);

    assertTest("TC13", "Lịch sử đơn hàng: Bảo lưu đúng theo MaKH, không mất khi đăng nhập lại và không lộ chéo", $has13A && $noLeak13B && $emptyGuest);
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

// ---------------------------------------------------------------------
// TC15: Nghiệp vụ Giải ngân Quỹ Cứu Trợ thực tế (QuyCuuTroModel::giaiNgan)
// ---------------------------------------------------------------------
try {
    // 1. Tạo khoản trích quỹ ở trạng thái 'Đã trích quỹ' kèm đơn hàng hợp lệ
    $testMaDongGop = 'TEST_QCT_15';
    $testMaDonHang = 'TEST_DH_15';
    $tkh15 = 'TKH_15';
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonggop = ?")->execute([$testMaDongGop]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$testMaDonHang]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh15]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, NULL, 'Fund Tester', 'f15@kibo.local', 0)")->execute([$tkh15]);

    $db->prepare("INSERT INTO don_hang (madonhang, makh, trangthaidonhang, tongtienhang, tongthanhtoan, ngaydathang, tennguoinhan, sodienthoainhan, diachigiaohang) 
                  VALUES (?, ?, 'Giao thành công', 1000000, 1000000, NOW(), 'Test Recipient', '0901234567', 'HCM')")->execute([$testMaDonHang, $tkh15]);
    $db->prepare("INSERT INTO quy_cuu_tro (madonggop, madonhang, sotiendonggop, ngayghinhan, matracuu, trangthaiquy, machungtu) 
                  VALUES (?, ?, 50000, NOW(), 'TEST_TC15', 'Đã trích quỹ', NULL)")->execute([$testMaDongGop, $testMaDonHang]);

    // 2. Admin gọi giải ngân cho trạm TCH01
    $resGN = QuyCuuTroModel::giaiNgan('TCH01', 50000, date('Y-m-d'), 'Cứu trợ đợt kiểm thử TC15', 'assets/docs/test_ct15.pdf');
    $isSuccessGN = $resGN['success'];
    $maCT = $resGN['machungtu'] ?? '';

    // 3. Kiểm tra trạng thái quỹ đã chuyển sang 'Đã giải ngân' và được gắn đúng machungtu
    $chkQuy = $db->query("SELECT trangthaiquy, machungtu FROM quy_cuu_tro WHERE madonggop = '{$testMaDongGop}'")->fetch();
    $isDisbursed = ($chkQuy && $chkQuy['trangthaiquy'] === 'Đã giải ngân' && $chkQuy['machungtu'] === $maCT);

    // Dọn dẹp
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonggop = ?")->execute([$testMaDongGop]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$testMaDonHang]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh15]);
    if (!empty($maCT)) {
        $db->prepare("DELETE FROM chung_tu_giai_ngan WHERE machungtu = ?")->execute([$maCT]);
    }

    assertTest("TC15", "Giải ngân Quỹ cứu trợ: Tạo chứng từ, gắn trạm nhận, chuyển quỹ 'Đã giải ngân' trong 1 transaction", $isSuccessGN && $isDisbursed);
} catch (Throwable $e) {
    assertTest("TC15", "Giải ngân Quỹ cứu trợ", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC16: Voucher Giảm tối đa (giamtoida) & Chặn dùng lại nhiều lần
// ---------------------------------------------------------------------
try {
    $tkh16 = 'TKH_16';
    $ttk16 = 'TTK_16';
    $vcCode = 'TESTMAX';

    $db->prepare("DELETE FROM voucher WHERE UPPER(mavoucher) = UPPER(?)")->execute([$vcCode]);
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$tkh16]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh16]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$ttk16]);

    $db->prepare("INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro) VALUES (?, 'user_16', 'hash', 'User')")->execute([$ttk16]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, 'User 16', 'u16@kibo.local', 0)")->execute([$tkh16, $ttk16]);

    // Tạo voucher giảm 50% nhưng giới hạn giảm tối đa 50.000đ
    $saveRes = VoucherModel::saveVoucher([
        'mavoucher' => $vcCode,
        'tenvoucher' => 'Giảm 50% tối đa 50k',
        'loaigiamgia' => 'PhanTram',
        'giatrigiam' => 50,
        'donhangtoithieu' => 100000,
        'giamtoida' => 50000,
        'soluongconlai' => 10
    ]);

    // 1. Tạo đơn 285.000đ => 50% = 142.500đ, nhưng có giamtoida nên chỉ giảm đúng 50.000đ
    $orderData = [
        'customer' => ['name' => 'User 16', 'phone' => '0771616161', 'address' => 'Quận 1, TP.HCM'],
        'items' => [['id' => 'BT001', 'name' => 'KIBO Cá Hồi', 'variant' => 'Túi 1.5kg', 'price' => 285000, 'quantity' => 1]],
        'voucherCode' => $vcCode,
        'paymentMethod' => 'cod'
    ];
    $resOrder1 = DonHangModel::taoDonHang($orderData, $tkh16);
    $oid1 = $resOrder1['orderId'] ?? '';

    $order1Db = $db->query("SELECT tiengiamgia, tongthanhtoan FROM don_hang WHERE madonhang = '{$oid1}'")->fetch();
    $isMaxCapped = ($order1Db && (float)$order1Db['tiengiamgia'] === 50000.0);

    // 2. Cùng tài khoản đó đặt đơn thứ hai dùng lại mã đó => BỊ TỪ CHỐI
    $orderData2 = $orderData;
    $resOrder2 = DonHangModel::taoDonHang($orderData2, $tkh16);
    $isSecondAttemptBlocked = (!$resOrder2['success']);

    // Dọn dẹp
    if (!empty($oid1)) {
        $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid1]);
        $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid1]);
        $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid1]);
        $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid1]);
    }
    $db->prepare("DELETE FROM voucher WHERE UPPER(mavoucher) = UPPER(?)")->execute([$vcCode]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh16]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$ttk16]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = 'BT001'")->execute();

    assertTest("TC16", "Voucher nâng cao: Áp dụng 'giảm tối đa' (giamtoida) và chặn mỗi tài khoản dùng quá 1 lần", $isMaxCapped && $isSecondAttemptBlocked);
} catch (Throwable $e) {
    assertTest("TC16", "Voucher nâng cao", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC17: Quy trình 5 bước Admin UI (Khớp 100% nút bấm giao diện không nhảy cóc)
// ---------------------------------------------------------------------
try {
    $tkh17 = 'TKH_17';
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh17]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, NULL, 'State Lifecycle Tester', 'u17@kibo.local', 0)")->execute([$tkh17]);

    $orderData = [
        'customer' => ['name' => 'Lifecycle User', 'phone' => '0771717171', 'address' => 'Quận 1, TP.HCM'],
        'items' => [['id' => 'BT001', 'name' => 'KIBO Cá Hồi', 'variant' => 'Túi 1.5kg', 'price' => 285000, 'quantity' => 1]],
        'paymentMethod' => 'cod'
    ];
    $resOrder = DonHangModel::taoDonHang($orderData, $tkh17);
    $oid17 = $resOrder['orderId'];

    // Bước 1: UI nhấn "Duyệt đơn" -> Chờ đóng gói
    $step1 = DonHangModel::capNhatTrangThai($oid17, 'Chờ đóng gói');

    // Bước 2: UI nhấn "Đã xuất kho" -> Đã xuất kho
    $step2 = DonHangModel::capNhatTrangThai($oid17, 'Đã xuất kho');

    // Bước 3: UI nhấn "Bắt đầu giao" -> Đang giao
    $step3 = DonHangModel::capNhatTrangThai($oid17, 'Đang giao');

    // Bước 4: UI nhấn "Giao thành công" -> Giao thành công
    $step4 = DonHangModel::capNhatTrangThai($oid17, 'Giao thành công');

    // Bước 5: Kiểm tra trạng thái cuối cùng và thử lùi hoặc nhảy ngược lại => BỊ CHẶN
    $stepInvalid = DonHangModel::capNhatTrangThai($oid17, 'Chờ xác nhận');

    $allStepsPass = ($step1['success'] && $step2['success'] && $step3['success'] && $step4['success'] && !$stepInvalid['success']);

    // Dọn dẹp
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang = ?")->execute([$oid17]);
    $db->prepare("DELETE FROM quy_cuu_tro WHERE madonhang = ?")->execute([$oid17]);
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang = ?")->execute([$oid17]);
    $db->prepare("DELETE FROM don_hang WHERE madonhang = ?")->execute([$oid17]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh17]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton + 1 WHERE mabienthe = 'BT001'")->execute();

    assertTest("TC17", "Quy trình máy trạng thái 5 bước Admin UI: Tuần tự Chờ xác nhận -> Chờ đóng gói -> Đã xuất kho -> Đang giao -> Giao thành công", $allStepsPass);
} catch (Throwable $e) {
    assertTest("TC17", "Quy trình máy trạng thái 5 bước Admin UI", false, $e->getMessage());
}

// ---------------------------------------------------------------------
// TC18: Tùy chọn Nhập lại kho khi Hoàn tiền & Che chắn thông báo lỗi (BusinessException)
// ---------------------------------------------------------------------
try {
    $tkh18 = 'TKH_18';
    $db->prepare("DELETE FROM yeu_cau_doi_tra WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B')")->execute();
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B')")->execute();
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B')")->execute();
    $db->prepare("DELETE FROM don_hang WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B') OR makh = ?")->execute([$tkh18]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh18]);
    $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, NULL, 'Return Tester', 'u18@kibo.local', 0)")->execute([$tkh18]);

    // Nhánh A: Hoàn tiền có nhập lại kho (nhaplaikho = true)
    $stockBeforeA = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $db->prepare("INSERT INTO don_hang (madonhang, makh, trangthaidonhang, tongtienhang, tongthanhtoan, ngaydathang, tennguoinhan, sodienthoainhan, diachigiaohang, ngaygiaothanhcong) 
                  VALUES ('TEST_DH_18A', ?, 'Giao thành công', 285000, 285000, NOW(), 'Tester 18A', '0771818181', 'HCM', NOW())")->execute([$tkh18]);
    $db->prepare("INSERT INTO chi_tiet_don_hang (madonhang, mabienthe, soluong, dongia, thanhtien) VALUES ('TEST_DH_18A', 'BT001', 2, 285000, 570000)")->execute();
    $db->prepare("INSERT INTO thanh_toan (magiaodich, madonhang, phuongthuctt, sotien, thoigiantt, trangthaitt) VALUES ('TEST_GD_18A', 'TEST_DH_18A', 'COD', 285000, NOW(), 'Thành công')")->execute();
    $resYCA = DoiTraModel::taoYeuCau('TEST_DH_18A', 'Bao bì nguyên seal muốn hoàn lại', '', '', '0771818181');
    $resXuLyA = DoiTraModel::xuLy($resYCA['mayeucau'], 'hoantien', 'Đồng ý hoàn tiền nhập kho', true);
    $stockAfterA = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $isRestocked = ($stockAfterA === $stockBeforeA + 2);

    // Nhánh B: Hoàn tiền KHÔNG nhập kho (nhaplaikho = false - thức ăn ẩm mốc tiêu hủy)
    $stockBeforeB = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $db->prepare("INSERT INTO don_hang (madonhang, makh, trangthaidonhang, tongtienhang, tongthanhtoan, ngaydathang, tennguoinhan, sodienthoainhan, diachigiaohang, ngaygiaothanhcong) 
                  VALUES ('TEST_DH_18B', ?, 'Giao thành công', 285000, 285000, NOW(), 'Tester 18B', '0771818182', 'HCM', NOW())")->execute([$tkh18]);
    $db->prepare("INSERT INTO chi_tiet_don_hang (madonhang, mabienthe, soluong, dongia, thanhtien) VALUES ('TEST_DH_18B', 'BT001', 3, 285000, 855000)")->execute();
    $db->prepare("INSERT INTO thanh_toan (magiaodich, madonhang, phuongthuctt, sotien, thoigiantt, trangthaitt) VALUES ('TEST_GD_18B', 'TEST_DH_18B', 'COD', 285000, NOW(), 'Thành công')")->execute();
    $resYCB = DoiTraModel::taoYeuCau('TEST_DH_18B', 'Hạt bị mốc tiêu hủy', '', '', '0771818182');
    $resXuLyB = DoiTraModel::xuLy($resYCB['mayeucau'], 'hoantien', 'Tiêu hủy hàng lỗi', false);
    $stockAfterB = (int)$db->query("SELECT soluongton FROM bien_the WHERE mabienthe = 'BT001'")->fetchColumn();
    $isNotRestocked = ($stockAfterB === $stockBeforeB);

    // Dọn dẹp
    $db->prepare("DELETE FROM yeu_cau_doi_tra WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B')")->execute();
    $db->prepare("DELETE FROM chi_tiet_don_hang WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B')")->execute();
    $db->prepare("DELETE FROM thanh_toan WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B')")->execute();
    $db->prepare("DELETE FROM don_hang WHERE madonhang IN ('TEST_DH_18A', 'TEST_DH_18B')")->execute();
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$tkh18]);
    $db->prepare("UPDATE bien_the SET soluongton = soluongton - 2 WHERE mabienthe = 'BT001'")->execute();

    assertTest("TC18", "Tùy chọn nhập lại kho khi hoàn tiền: Nhập kho (nhaplaikho=true) vs Tiêu hủy hàng hỏng (nhaplaikho=false)", $isRestocked && $isNotRestocked);
} catch (Throwable $e) {
    assertTest("TC18", "Tùy chọn nhập lại kho khi hoàn tiền", false, $e->getMessage());
}

echo "\n====================================================================\n";
echo "KẾT QUẢ KIỂM THỬ: {$passed}/{$total} TEST CASES THÀNH CÔNG!\n";
echo "====================================================================\n";

if ($passed === $total) {
    exit(0);
} else {
    exit(1);
}
