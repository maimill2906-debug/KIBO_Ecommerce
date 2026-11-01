<?php
/**
 * KIBO AUTOMATED QA TEST SUITE
 * Comprehensive end-to-end verification of all 28 test cases
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/SanPhamModel.php';
require_once __DIR__ . '/models/DonHangModel.php';
require_once __DIR__ . '/models/KhachHangModel.php';
require_once __DIR__ . '/models/TaiKhoanModel.php';
require_once __DIR__ . '/models/QuyCuuTroModel.php';
require_once __DIR__ . '/models/VoucherModel.php';
require_once __DIR__ . '/models/DoiTraModel.php';
require_once __DIR__ . '/models/GioHangModel.php';

$results = [];
function recordTest($id, $name, $pass, $details = '') {
    global $results;
    $results[] = [
        'id' => $id,
        'name' => $name,
        'pass' => $pass,
        'details' => $details
    ];
    $status = $pass ? "✓ PASS" : "✗ FAIL";
    echo sprintf("[%s] %s: %s - %s\n", $status, $id, $name, $details);
}

echo "========================================================\n";
echo "   KIBO SYSTEM TEST SUITE: 28 END-TO-END TEST CASES     \n";
echo "========================================================\n\n";

// --- SUITE 1: DATABASE & TABLES ---
echo "--- GROUP 1: Database & 17 Tables Verification ---\n";
$db = Database::getConnection();
recordTest('TC01', 'Database Connection', $db !== null, 'Connected to Supabase PostgreSQL');

$tables = [
    'tai_khoan', 'khach_hang', 'sdt_kh', 'dia_chi_kh', 'danh_muc', 
    'san_pham', 'bien_the', 'gio_hang', 'chi_tiet_gio_hang', 'voucher', 
    'don_hang', 'chi_tiet_don_hang', 'thanh_toan', 'quy_cuu_tro', 
    'tram_cuu_ho', 'chung_tu_giai_ngan', 'yeu_cau_doi_tra'
];
$activeTables = 0;
foreach ($tables as $t) {
    try {
        $count = $db->query("SELECT count(*) FROM $t")->fetchColumn();
        $activeTables++;
    } catch (Exception $e) {}
}
recordTest('TC02', '17 Tables Active in PostgreSQL', $activeTables === 17, "Active: $activeTables/17 tables");

// --- SUITE 2: AUTHENTICATION ---
echo "\n--- GROUP 2: Authentication Flows ---\n";
$adminAuth = TaiKhoanModel::authenticate('admin@kibo.vn', '123456');
recordTest('TC03', 'Admin Login Authenticate', $adminAuth && ($adminAuth['VaiTro'] === 'Admin'), 'Admin user authenticated with role Admin');

$userAuth = TaiKhoanModel::authenticate('user4@gmail.com', '123456');
recordTest('TC04', 'Customer Login Authenticate', $userAuth && !empty($userAuth['MaKH']), "Customer authenticated: MaKH={$userAuth['MaKH']}");

$badAuth = TaiKhoanModel::authenticate('user4@gmail.com', 'wrongpassword999');
recordTest('TC05', 'Wrong Password Rejection', $badAuth === null, 'Correctly rejected invalid password');

// Register test with random email (password >= 8 chars)
$testEmail = 'test_qa_' . rand(1000, 9999) . '@kibo.vn';
$regRes = TaiKhoanModel::registerUser([
    'email' => $testEmail,
    'password' => 'kibopass123',
    'hoten' => 'QA Tester',
    'phone' => '0772906756',
    'address' => '123 Test Street'
]);
recordTest('TC06', 'Register New Customer Account', !empty($regRes['success']) && !empty($regRes['user']['MaKH']), "Created account {$testEmail} with MaKH=" . ($regRes['user']['MaKH'] ?? ''));

$dupRes = TaiKhoanModel::registerUser(['email' => $testEmail, 'password' => 'kibopass123']);
recordTest('TC07', 'Duplicate Email Prevention', empty($dupRes['success']), 'Rejected duplicate registration with existing email');

// Clean up test account in proper foreign key order
if (!empty($regRes['user']['MaKH']) && !empty($regRes['user']['MaTK'])) {
    $db->prepare("DELETE FROM sdt_kh WHERE makh = ?")->execute([$regRes['user']['MaKH']]);
    $db->prepare("DELETE FROM dia_chi_kh WHERE makh = ?")->execute([$regRes['user']['MaKH']]);
    $db->prepare("DELETE FROM khach_hang WHERE makh = ?")->execute([$regRes['user']['MaKH']]);
    $db->prepare("DELETE FROM tai_khoan WHERE matk = ?")->execute([$regRes['user']['MaTK']]);
}

// --- SUITE 3: CUSTOMER ORDER HISTORY INTEGRITY ---
echo "\n--- GROUP 3: Customer Profile & Order History ---\n";
$ordersUser4 = DonHangModel::getOrdersByCustomer('KH007', '0772906756', 'user4@gmail.com');
recordTest('TC08', 'Order History Lookup (user4)', count($ordersUser4) > 0, "Retrieved " . count($ordersUser4) . " orders for user4");

$ordersUser1 = DonHangModel::getOrdersByCustomer('KH001', '0772906756', 'user1@gmail.com');
recordTest('TC09', 'Order History Lookup (user1)', count($ordersUser1) > 0, "Retrieved " . count($ordersUser1) . " orders for user1");

$custDetail = KhachHangModel::getCustomerDetail('user4@gmail.com');
recordTest('TC10', 'Customer Detail Profile Fetch', !empty($custDetail['HoTen']), "HoTen: {$custDetail['HoTen']}, Phone: {$custDetail['SoDienThoaiChinh']}");

$profUpdate = KhachHangModel::updateProfile('KH007', 'Quỳnh Giao', '0772906756', '171 quốc lộ 13, Phường Tăng Nhơn Phú B, TP. Thủ Đức');
recordTest('TC11', 'Profile Update API', $profUpdate['success'], 'Customer profile successfully updated');

// --- SUITE 4: PRODUCTS & CATALOG ---
echo "\n--- GROUP 4: Products & Categories ---\n";
$allProducts = SanPhamModel::getAllForView();
recordTest('TC12', 'Get All Products for View', count($allProducts) >= 8, "Count: " . count($allProducts) . " products in DB");

$pdp = SanPhamModel::getProductById('SP001');
recordTest('TC13', 'Product Detail PDP (SP001)', $pdp !== null, "Loaded SP001: " . ($pdp['tensp'] ?? ''));

$featured = SanPhamModel::getFeaturedProducts(4);
recordTest('TC14', 'Featured Products on Home', count($featured) === 4, "Loaded 4 featured deals for homepage");

// --- SUITE 5: RESCUE FUND (5%) ---
echo "\n--- GROUP 5: Rescue Fund & Transparency ---\n";
$fund = QuyCuuTroModel::getFundSummary();
recordTest('TC15', 'Fund Summary Metrics', isset($fund['TongTichLuy']), "Disbursed: " . number_format($fund['TongGiaiNgan']) . "đ, Accumulated: " . number_format($fund['TongTichLuy']) . "đ");

$sheltersCount = $db->query("SELECT count(*) FROM tram_cuu_ho")->fetchColumn();
recordTest('TC16', 'Rescue Shelters in DB', (int)$sheltersCount >= 2, "Loaded $sheltersCount shelters in tram_cuu_ho");

$disbursedCount = $db->query("SELECT count(*) FROM chung_tu_giai_ngan")->fetchColumn();
recordTest('TC17', 'Disbursement Statements in DB', (int)$disbursedCount >= 1, "Loaded $disbursedCount disbursement statements in chung_tu_giai_ngan");

// --- SUITE 6: VOUCHERS & CART ---
echo "\n--- GROUP 6: Vouchers & Cart Sync ---\n";
$voucherCheck = VoucherModel::validateVoucher('KIBO20', 500000);
recordTest('TC18', 'Voucher Check (KIBO20)', $voucherCheck['valid'] && $voucherCheck['discount'] > 0, "Discount: " . number_format($voucherCheck['discount'] ?? 0) . "đ");

$cartSync = GioHangModel::syncCart('KH007', [
    ['id' => 'BT001', 'name' => 'Hạt Royal Canin Mini Puppy', 'price' => 110000, 'quantity' => 2]
]);
recordTest('TC19', 'Cart Database Sync', $cartSync, 'Synced 2 items to gio_hang and chi_tiet_gio_hang');

$dbCart = GioHangModel::getCartFromDb('KH007');
recordTest('TC20', 'Read Cart from DB', count($dbCart) === 1 && $dbCart[0]['quantity'] === 2, "Verified DB cart contains 2 units of BT001");

// --- SUITE 7: CHECKOUT & ORDER CREATION ---
echo "\n--- GROUP 7: Checkout & Points Accumulation ---\n";
$testOrderCode = 'KB-QA' . rand(100, 999);
$createdOrder = DonHangModel::taoDonHang([
    'madonhang' => $testOrderCode,
    'makh' => 'KH007',
    'tongtienhang' => 220000,
    'tiengiamgia' => 22000,
    'phivanchuyen' => 25000,
    'tongthanhtoan' => 223000,
    'tennguoinhan' => 'Quỳnh Giao QA',
    'sodienthoainhan' => '0772906756',
    'diachigiaohang' => '171 quốc lộ 13, TP. Thủ Đức',
    'ghichu' => 'Test order QA',
    'phuongthuc' => 'Thanh toán khi nhận hàng (COD)',
    'items' => [
        ['id' => 'BT001', 'name' => 'Hạt Royal Canin', 'price' => 110000, 'quantity' => 2]
    ]
]);
recordTest('TC21', 'Create New Order', $createdOrder === $testOrderCode, "Order #{$testOrderCode} created successfully");

$custAfterOrder = KhachHangModel::getCustomerDetail('user4@gmail.com');
recordTest('TC22', 'Accumulated Points Awarded', (int)$custAfterOrder['DiemTichLuy'] > 0, "Current Points: {$custAfterOrder['DiemTichLuy']}");

$orderLookup = DonHangModel::traCuu($testOrderCode);
recordTest('TC23', 'Order Tracking Lookup', $orderLookup && $orderLookup['orderId'] === $testOrderCode, "Lookup succeeded for #{$testOrderCode}");

// --- SUITE 8: RETURN REQUESTS (3 DAYS) ---
echo "\n--- GROUP 8: Returns & Refunds (yeu_cau_doi_tra) ---\n";
$returnRes = DoiTraModel::taoYeuCau($testOrderCode, 'QA Test đổi hàng do bao bì móp', '');
recordTest('TC24', 'Create Return Request', $returnRes['success'], "Return request created: " . ($returnRes['mayeucau'] ?? ''));

$retCheck = DoiTraModel::getByOrder($testOrderCode);
recordTest('TC25', 'Check Return by Order', count($retCheck) > 0, "Found return request for order #{$testOrderCode}");

$allReturns = DoiTraModel::getAll();
recordTest('TC26', 'Admin List All Returns', count($allReturns) > 0, "Total return requests: " . count($allReturns));

// --- SUITE 9: ADMIN PRIVILEGES & MANAGEMENT ---
echo "\n--- GROUP 9: Admin Operations ---\n";
$adminOrders = DonHangModel::getAll();
recordTest('TC27', 'Admin View All Orders', count($adminOrders) >= 15, "Admin can view all " . count($adminOrders) . " customer orders");

$updateStatus = DonHangModel::capNhatTrangThai($testOrderCode, 'Đang giao hàng');
recordTest('TC28', 'Admin Update Order Status', $updateStatus, "Order status updated to 'Đang giao hàng'");

echo "\n========================================================\n";
$passCount = count(array_filter($results, fn($r) => $r['pass']));
$totalCount = count($results);
echo "   QA SUMMARY: $passCount / $totalCount TEST CASES PASSED (" . round(($passCount / $totalCount) * 100, 1) . "%)\n";
echo "========================================================\n";
