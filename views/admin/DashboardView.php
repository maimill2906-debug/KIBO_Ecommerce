<?php
/**
 * VIEW: views/admin/DashboardView.php
 * KIBO OPERATIONS & INVENTORY ADMIN PLATFORM
 * Chuyển thể 100% chuẩn xác từ Figma Make App (AdminPage.tsx)
 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}
$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser || ($currentUser['VaiTro'] ?? '') !== 'Admin') {
    header('Location: index.php?action=login');
    exit;
}

require_once __DIR__ . '/../components/helpers.php';
require_once __DIR__ . '/../../models/SanPhamModel.php';
require_once __DIR__ . '/../../models/DonHangModel.php';
require_once __DIR__ . '/../../models/DoiTraModel.php';
require_once __DIR__ . '/../../models/VoucherModel.php';
require_once __DIR__ . '/../../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../../models/TramCuuHoModel.php';
require_once __DIR__ . '/../../models/KhachHangModel.php';
require_once __DIR__ . '/../../models/ThanhToanModel.php';
require_once __DIR__ . '/../../models/NhatKyModel.php';

$dbReturns = DoiTraModel::getAll();
$dbVouchers = VoucherModel::getAllAdmin();
$fundSummary = QuyCuuTroModel::getFundSummary();
$fundTransactions = QuyCuuTroModel::getRecentTransactions(30);
$shelters = TramCuuHoModel::getShelters();
$disbursements = TramCuuHoModel::getDisbursements();
$customersList = KhachHangModel::layDanhSachKhachHang();
$paymentsList = ThanhToanModel::getAllWithOrders();
$reconciliationList = ThanhToanModel::layDanhSachDoiSoat();
$auditLogs = NhatKyModel::layDanhSach(50);

// Dữ liệu Tồn kho & Đã bán tính trực tiếp từ CSDL thật (bien_the & chi_tiet_don_hang)
$initialInventory = SanPhamModel::getInventoryWithSales();
if (empty($initialInventory)) {
    $initialInventory = [];
}

// Dữ liệu Đơn hàng đọc trực tiếp từ Model / Supabase
$dbOrders = DonHangModel::getAll();
$initialOrders = [];

$totalRevenueDelivered = 0;
$totalOrdersCount = count($dbOrders);
$newOrdersCount = 0;
$deliveredOrdersCount = 0;
$shippingOrdersCount = 0;
$cancelledOrdersCount = 0;

foreach ($dbOrders as $o) {
    $firstItem = !empty($o['items']) ? $o['items'][0]['name'] : 'Thức ăn hạt & phụ kiện KIBO';
    $itemList = !empty($o['items']) ? $o['items'] : [['name' => $firstItem, 'variant' => 'Tiêu chuẩn', 'qty' => 1, 'price' => $o['total']]];
    $st = $o['status'];
    $tot = (float)($o['total'] ?? 0);

    if ($st === 'Giao thành công' || $st === 'Giao hàng thành công' || $st === 'Đã giao') {
        $totalRevenueDelivered += $tot;
        $deliveredOrdersCount++;
    } elseif ($st === 'Chờ xác nhận') {
        $newOrdersCount++;
    } elseif ($st === 'Đang giao' || $st === 'Đang giao hàng') {
        $shippingOrdersCount++;
    } elseif (strpos($st, 'hủy') !== false || strpos($st, 'Hủy') !== false) {
        $cancelledOrdersCount++;
    }

    $initialOrders[] = [
        'id' => $o['orderId'],
        'customer' => $o['customerName'],
        'phone' => $o['phone'] ?: '0772 906 756',
        'address' => $o['address'] ?: '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh',
        'items' => $firstItem,
        'itemsList' => $itemList,
        'date' => $o['date'] ?? date('d/m/Y'),
        'rawDate' => $o['rawDate'] ?? null,
        'subtotal' => number_format($o['subtotal'] ?? $o['total'], 0, ',', '.') . 'đ',
        'shipping' => number_format($o['shipping'] ?? 0, 0, ',', '.') . 'đ',
        'discount' => number_format($o['discount'] ?? 0, 0, ',', '.') . 'đ',
        'total' => number_format($o['total'], 0, ',', '.') . 'đ',
        'fund' => number_format($o['fundAmount'], 0, ',', '.') . 'đ',
        'shelter' => $o['allocatedStation'] ?? 'Trạm Cứu Hộ Động Vật Nông Lâm',
        'status' => $o['status']
    ];
}
if (empty($initialOrders)) {
    $initialOrders = [];
}

$totalStockQty = array_reduce($initialInventory, function($carry, $item) { return $carry + (int)($item['stock'] ?? $item['soluongton'] ?? 0); }, 0);
$lowStockCount = count(array_filter($initialInventory, function($item) { return (int)($item['stock'] ?? $item['soluongton'] ?? 0) <= 5; }));
$pendingReturnsCount = count(array_filter($dbReturns ?? [], function($r) { 
    $st = $r['trangthai'] ?? $r['TrangThai'] ?? '';
    return $st === 'Chờ xử lý'; 
}));
$totalNotifs = ($lowStockCount > 0 ? 1 : 0) + ($newOrdersCount > 0 ? 1 : 0) + ($pendingReturnsCount > 0 ? 1 : 0) + ($deliveredOrdersCount > 0 ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KIBO Operations & Inventory Management — Admin Dashboard</title>

  <!-- Google Fonts: Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body class="ops-admin">

  <!-- ====================================================================
       1. HEADER QUẢN TRỊ TRÊN CÙNG (STICKY)
       ==================================================================== -->
  <header class="ops-header">
    <div class="ops-header-inner">
      <!-- Logo KIBO chính thức -->
      <a href="index.php?action=home" class="logo-button" title="Về trang chủ KIBO">
        <?= kibo_logo_svg('default', 42) ?>
      </a>

      <!-- Ô tìm kiếm tổng hợp -->
      <label class="ops-search">
        <?= kibo_icon('search', 18) ?>
        <input type="text" id="adminGlobalSearch" placeholder="Tìm sản phẩm, SKU hoặc đơn hàng..." oninput="handleGlobalSearch(this.value)">
      </label>

      <!-- Bộ lọc thời gian -->
      <label class="ops-range">
        <?= kibo_icon('clock', 17) ?>
        <select id="adminTimeRange" onchange="handleTimeRangeChange(this.value)">
          <option value="7">7 ngày gần nhất</option>
          <option value="30" selected>30 ngày gần nhất</option>
          <option value="90">Quý này</option>
        </select>
      </label>

      <!-- Chuông thông báo -->
      <div class="ops-notification-wrap">
        <button type="button" class="ops-icon-button" onclick="toggleNotifications()" aria-label="Thông báo">
          <?= kibo_icon('bell', 20) ?>
          <b id="notifBadge"><?= max(1, $totalNotifs) ?></b>
        </button>
        <div class="ops-notification-menu" id="notifDropdown" style="display: none;">
          <strong>Thông báo hệ thống</strong>
          <?php if ($lowStockCount > 0): ?>
            <p id="notifLowStockMsg" style="color: #b45309; font-weight: 600;">⚠️ Có <?= $lowStockCount ?> sản phẩm sắp hết hàng (tồn kho &le; 5).</p>
          <?php endif; ?>
          <?php if ($newOrdersCount > 0): ?>
            <p style="color: #1d4ed8; font-weight: 600;">🛒 Có <?= $newOrdersCount ?> đơn hàng mới đang chờ duyệt.</p>
          <?php endif; ?>
          <?php if ($pendingReturnsCount > 0): ?>
            <p style="color: #c2410c; font-weight: 600;">🔄 Có <?= $pendingReturnsCount ?> yêu cầu đổi trả đang chờ xử lý.</p>
          <?php endif; ?>
          <p style="color: #15803d;">✅ <?= $deliveredOrdersCount ?> đơn hàng đã hoàn tất giao hàng.</p>
          <p style="color: #b91c1c;">❤️ <?= number_format($fundSummary['TongTichLuy'] ?? 0, 0, ',', '.') ?>đ đã trích vào Quỹ Cứu Trợ.</p>
        </div>
      </div>

      <!-- Profile Admin -->
      <div class="ops-profile">
        <span>AD</span>
        <p>
          <strong>Admin KIBO</strong>
          <small>Quản trị viên</small>
        </p>
        <a href="index.php?action=home" title="Về trang chủ">
          <?= kibo_icon('logout', 17) ?>
        </a>
      </div>
    </div>
  </header>

  <!-- ====================================================================
       2. WORKSPACE: SIDEBAR + NỘI DUNG CHÍNH (5 TABS)
       ==================================================================== -->
  <div class="ops-workspace">
    <!-- SIDEBAR BÊN TRÁI -->
    <aside class="ops-sidebar">
      <p class="ops-sidebar-label">QUẢN TRỊ CỬA HÀNG</p>
      <nav id="adminNav">
        <button type="button" class="active" onclick="switchAdminTab('overview')">
          <?= kibo_icon('chart', 17) ?> <span>Tổng quan</span>
        </button>
        <button type="button" onclick="switchAdminTab('orders')">
          <?= kibo_icon('bag', 17) ?> <span>Đơn hàng</span>
        </button>
        <button type="button" onclick="switchAdminTab('inventory')">
          <?= kibo_icon('box', 17) ?> <span>Quản lý sản phẩm &amp; Tồn kho</span>
        </button>
        <button type="button" onclick="switchAdminTab('fund')">
          <?= kibo_icon('heart', 17) ?> <span>Quỹ cứu trợ</span>
        </button>
        <button type="button" onclick="switchAdminTab('vouchers')">
          <?= kibo_icon('ticket', 17) ?> <span>Mã giảm giá</span>
        </button>
        <button type="button" onclick="switchAdminTab('reports')">
          <?= kibo_icon('chart', 17) ?> <span>Báo cáo</span>
        </button>
        <button type="button" onclick="switchAdminTab('returns')">
          <?= kibo_icon('truck', 17) ?> <span>Yêu cầu đổi trả</span>
        </button>
        <button type="button" onclick="switchAdminTab('customers')">
          <?= kibo_icon('user', 17) ?> <span>Khách hàng</span>
        </button>
        <button type="button" onclick="switchAdminTab('payments')">
          <?= kibo_icon('card', 17) ?> <span>Giao dịch thanh toán</span>
        </button>
      </nav>

      <div class="ops-sidebar-bottom">
        <p>
          <strong>Kho trung tâm</strong>
          <small>TP.HCM · Đang hoạt động</small>
        </p>
        <a href="index.php?action=home">
          <?= kibo_icon('logout', 15) ?> Về cửa hàng
        </a>
      </div>
    </aside>

    <!-- KHU VỰC NỘI DUNG CHÍNH -->
    <section class="ops-content">
      <!-- Tiêu đề trang động -->
      <div class="ops-page-heading">
        <div>
          <h1 id="tabHeadingTitle">Tổng quan cửa hàng</h1>
          <p id="tabHeadingDesc">Các chỉ số kinh doanh chính trong kỳ.</p>
        </div>
        <span>Cập nhật <?= date('H:i') ?> hôm nay</span>
      </div>

      <!-- ================================================================
           TAB 1: TỔNG QUAN (OVERVIEW)
           ================================================================ -->
      <div id="tabContent_overview" class="tab-pane">
        <!-- Hàng 4 thẻ số liệu tóm tắt (KPIs thực tế 100% từ CSDL) -->
        <section class="ops-summary-row">
          <div>
            <small>Doanh thu (Đã giao)</small>
            <strong id="kpiRevenueVal"><?= number_format($totalRevenueDelivered, 0, ',', '.') ?>đ</strong>
            <p class="positive" id="kpiRevenueSub"><?= $deliveredOrdersCount ?> đơn hoàn tất giao</p>
          </div>
          <div>
            <small>Quỹ cứu trợ 5%</small>
            <strong id="kpiFundVal"><?= number_format($fundSummary['TongTichLuy'], 0, ',', '.') ?>đ</strong>
            <p id="kpiFundSub"><?= number_format($fundSummary['DangChoTrich'], 0, ',', '.') ?>đ chờ trích quỹ</p>
          </div>
          <div>
            <small>Đơn hàng</small>
            <strong id="kpiOrdersVal"><?= $totalOrdersCount ?></strong>
            <p id="kpiOrdersSub"><?= $newOrdersCount ?> mới · <?= $deliveredOrdersCount ?> đã giao</p>
          </div>
          <div>
            <small>Tồn kho</small>
            <strong id="kpiTotalStock"><?= number_format($totalStockQty, 0, ',', '.') ?></strong>
            <p class="<?= $lowStockCount > 0 ? 'warning-text' : 'positive' ?>" id="kpiLowStockMsg">
              <?= $lowStockCount > 0 ? ($lowStockCount . ' sản phẩm tồn thấp (≤ 5)') : 'Tất cả sản phẩm đủ hàng' ?>
            </p>
          </div>
        </section>

        <!-- Lưới Biểu đồ Doanh thu & Đơn gần đây -->
        <div class="ops-overview-grid">
          <!-- Biểu đồ Doanh thu -->
          <article class="ops-card ops-chart-card">
            <div class="ops-card-head">
              <div>
                <h2>Doanh thu &amp; đơn hàng</h2>
                <p id="chartSubtext">30 ngày gần nhất</p>
              </div>
              <div class="ops-legend">
                <span><i class="revenue"></i> Doanh thu</span>
                <span><i class="orders"></i> Đơn hàng</span>
              </div>
            </div>
            <div class="ops-chart">
              <div class="ops-y-axis">
                <span>30tr</span>
                <span>20tr</span>
                <span>10tr</span>
                <span>0</span>
              </div>
              <svg viewBox="0 0 760 245" role="img" aria-label="Biểu đồ doanh thu">
                <g class="grid-lines">
                  <path d="M0 20H760M0 87H760M0 154H760M0 221H760" />
                </g>
                <rect x="42" y="116" width="34" height="105" rx="2" />
                <rect x="132" y="83" width="34" height="138" rx="2" />
                <rect x="222" y="133" width="34" height="88" rx="2" />
                <rect x="312" y="53" width="34" height="168" rx="2" />
                <rect x="402" y="79" width="34" height="142" rx="2" />
                <rect x="492" y="29" width="34" height="192" rx="2" />
                <rect x="582" y="65" width="34" height="156" rx="2" />
                <rect x="672" y="16" width="34" height="205" rx="2" />
                <path class="order-line" d="M59 165 149 139 239 158 329 102 419 125 509 76 599 98 689 52" />
              </svg>
              <div class="ops-x-axis">
                <span>01/06</span>
                <span>05/06</span>
                <span>09/06</span>
                <span>13/06</span>
                <span>17/06</span>
                <span>21/06</span>
                <span>25/06</span>
                <span>29/06</span>
              </div>
            </div>
          </article>

          <!-- Đơn hàng gần đây -->
          <article class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Đơn hàng gần đây</h2>
                <p>5 giao dịch mới nhất</p>
              </div>
              <button type="button" onclick="switchAdminTab('orders')">Xem tất cả</button>
            </div>
            <div class="ops-order-feed" id="overviewRecentOrders">
              <!-- Render tự động bằng JS -->
            </div>
          </article>
        </div>
      </div>

      <!-- ================================================================
           TAB 2: QUẢN LÝ ĐƠN HÀNG (ORDERS)
           ================================================================ -->
      <div id="tabContent_orders" class="tab-pane" style="display: none;">
        <article class="ops-card" style="margin-top: 18px;">
          <div class="ops-card-head">
            <div>
              <h2>Danh sách đơn hàng</h2>
              <p id="ordersCountDesc">5 đơn trong kỳ đã chọn</p>
            </div>
            <button type="button" class="ops-secondary-button" onclick="exportOrdersCSV()">Xuất danh sách</button>
          </div>
          <div class="ops-table-wrap">
            <table class="ops-table ops-orders-table" id="ordersMainTable">
              <thead>
                <tr>
                  <th>Mã đơn</th>
                  <th>Khách hàng</th>
                  <th>Sản phẩm</th>
                  <th>Tổng tiền</th>
                  <th>Quỹ 5%</th>
                  <th>Trạng thái</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody id="ordersTableBody">
                <!-- Render từ JS -->
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- ================================================================
           TAB 3: QUẢN LÝ TỒN KHO (INVENTORY)
           ================================================================ -->
      <div id="tabContent_inventory" class="tab-pane" style="display: none;">
        <div class="ops-inventory-layout">
          <!-- Bảng danh sách tồn kho -->
          <article class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Danh sách sản phẩm</h2>
                <p id="inventoryCountDesc">8 sản phẩm đang quản lý</p>
              </div>
              <div style="display: flex; gap: 8px;">
                <button type="button" onclick="openAddCategoryModal()" style="height: 38px; padding: 0 16px; background: #2c1a0e; color: #ffffff; border: none; border-radius: 4px; font-weight: bold; font-size: 14px; cursor: pointer;">+ Thêm danh mục</button>
                <button type="button" onclick="openAddProductModal()" style="height: 38px; padding: 0 16px; background: #2c1a0e; color: #ffffff; border: none; border-radius: 4px; font-weight: bold; font-size: 14px; cursor: pointer;">+ Thêm sản phẩm</button>
              </div>
            </div>
            <div class="ops-table-wrap">
              <table class="ops-table" id="inventoryMainTable">
                <thead>
                  <tr>
                    <th>Sản phẩm</th>
                    <th>SKU</th>
                    <th>Danh mục</th>
                    <th>Đã bán</th>
                    <th>Tồn kho</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                  </tr>
                </thead>
                <tbody id="inventoryTableBody">
                  <!-- Render từ JS -->
                </tbody>
              </table>
            </div>
          </article>

          <!-- Cột Cần nhập kho (Low stock < 10) -->
          <aside class="ops-card ops-flat-alerts">
            <div class="ops-card-head">
              <div>
                <h2>Cần nhập kho</h2>
                <p>Dưới 10 sản phẩm</p>
              </div>
            </div>
            <div id="lowStockAlertList">
              <!-- Render từ JS -->
            </div>
          </aside>
        </div>
      </div>

      <!-- ================================================================
           TAB 4: ĐỐI SOÁT & GIẢI NGÂN QUỸ CỨU TRỢ (FUND)
           ================================================================ -->
      <div id="tabContent_fund" class="tab-pane" style="display: none;">
        <?php 
          $choGiaiNgan = max(0, $fundSummary['TongTichLuy'] - $fundSummary['TongGiaiNgan']);
        ?>
        <section class="ops-summary-row fund-summary" style="margin-top: 18px;">
          <div>
            <small>Tổng quỹ đã trích</small>
            <strong style="color: var(--green);"><?= number_format($fundSummary['TongTichLuy'], 0, ',', '.') ?>đ</strong>
            <p>Từ <?= (int)$fundSummary['SoDonDongGop'] ?> đơn giao thành công</p>
          </div>
          <div>
            <small>Đã giải ngân thực tế</small>
            <strong style="color: #1565c0;"><?= number_format($fundSummary['TongGiaiNgan'], 0, ',', '.') ?>đ</strong>
            <p>Kỳ gần nhất: <?= htmlspecialchars($fundSummary['KyGiaiNgan']) ?></p>
          </div>
          <div>
            <small>Quỹ sẵn sàng giải ngân</small>
            <strong style="color: #c94336;"><?= number_format($choGiaiNgan, 0, ',', '.') ?>đ</strong>
            <p><?= (int)$fundSummary['SoTramDoiTac'] ?> trạm đối tác liên kết</p>
          </div>
        </section>

        <!-- KHỐI 1: DANH SÁCH CHỨNG TỪ GIẢI NGÂN QUỸ -->
        <article class="ops-card" style="margin-top: 20px;">
          <div class="ops-card-head" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h2>Chứng từ Giải ngân Quỹ</h2>
              <p>Danh sách các đợt chuyển khoản thực tế cho các Trạm cứu hộ đối tác</p>
            </div>
            <button type="button" class="ops-primary-button" onclick="openDisbursementModal()" style="display: flex; align-items: center; gap: 6px;">
              <?= kibo_icon('plus', 16) ?> Lập chứng từ giải ngân
            </button>
          </div>
          <div class="ops-table-wrap">
            <table class="ops-table">
              <thead>
                <tr>
                  <th>Mã CT</th>
                  <th>Trạm nhận</th>
                  <th>Số tiền giải ngân</th>
                  <th>Ngày chuyển</th>
                  <th>Mục đích sử dụng</th>
                  <th>Chứng từ / Sao kê</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($disbursements)): ?>
                  <?php foreach ($disbursements as $ct): ?>
                    <tr>
                      <td><code><?= htmlspecialchars($ct['MaChungTu']) ?></code></td>
                      <td><strong><?= htmlspecialchars($ct['TenTram']) ?></strong></td>
                      <td><strong style="color: #1565c0;"><?= number_format($ct['SoTienGiaiNgan'], 0, ',', '.') ?>đ</strong></td>
                      <td><?= date('d/m/Y', strtotime($ct['NgayChuyenKhoan'])) ?></td>
                      <td style="font-size: 13px; color: #555;"><?= htmlspecialchars($ct['MucDichSuDung']) ?></td>
                      <td>
                        <?php if (!empty($ct['FileHoaDonChungTu'])): ?>
                          <a href="<?= htmlspecialchars($ct['FileHoaDonChungTu']) ?>" target="_blank" style="color: #1565c0; text-decoration: underline; font-size: 13px;">
                            Xem chứng từ ↗
                          </a>
                        <?php else: ?>
                          <span style="color: #999; font-size: 12px;">Chưa đính kèm</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 25px; color: #777;">
                      Chưa có chứng từ giải ngân nào được lập.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>

        <!-- KHỐI 2: CHI TIẾT TỪNG KHOẢN TRÍCH QUỸ TỪ ĐƠN HÀNG -->
        <article class="ops-card" style="margin-top: 24px;">
          <div class="ops-card-head">
            <div>
              <h2>Chi tiết các khoản trích 5% từ Đơn hàng</h2>
              <p>Minh bạch 100% dòng tiền từ cơ sở dữ liệu thật</p>
            </div>
            <button type="button" class="ops-secondary-button" onclick="showAdminToast('Tải báo cáo', 'Đang kết xuất báo cáo Quỹ Cứu Trợ...')">Tải báo cáo Excel</button>
          </div>
          <div class="ops-table-wrap">
            <table class="ops-table">
              <thead>
                <tr>
                  <th>Thời gian</th>
                  <th>Mã đơn hàng</th>
                  <th>Số tiền 5%</th>
                  <th>Trạng thái Quỹ</th>
                  <th>Trạm liên kết</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($fundTransactions)): ?>
                  <?php foreach ($fundTransactions as $ft): 
                    $stBadge = 'ops-stock-status ';
                    if ($ft['TrangThaiQuy'] === 'Đã giải ngân') $stBadge .= 'good';
                    elseif ($ft['TrangThaiQuy'] === 'Đã trích quỹ') $stBadge .= 'good';
                    elseif ($ft['TrangThaiQuy'] === 'Chờ trích quỹ') $stBadge .= 'low';
                    else $stBadge .= 'out';
                  ?>
                    <tr>
                      <td style="color: #666; font-size: 13px;"><?= htmlspecialchars($ft['NgayGhiNhan']) ?></td>
                      <td><code><?= htmlspecialchars($ft['MaDonHang']) ?></code></td>
                      <td><strong style="color: var(--green);"><?= number_format($ft['SoTienDongGop'], 0, ',', '.') ?>đ</strong></td>
                      <td><span class="<?= $stBadge ?>"><?= htmlspecialchars($ft['TrangThaiQuy']) ?></span></td>
                      <td style="font-size: 13px;"><?= htmlspecialchars($ft['TramNhan']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" style="text-align: center; padding: 25px; color: #777;">
                      Chưa có khoản trích quỹ nào.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- ================================================================
           TAB 5: BÁO CÁO KINH DOANH (REPORTS)
           ================================================================ -->
      <div id="tabContent_reports" class="tab-pane" style="display: none;">
        <div class="ops-report-layout" style="display: flex; flex-direction: column; gap: 20px;">
          <article class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Xuất báo cáo theo kỳ</h2>
                <p>Xuất dữ liệu thực tế từ hệ thống ra file định dạng Excel CSV (hỗ trợ tiếng Việt UTF-8 BOM)</p>
              </div>
            </div>
            <div class="ops-report-list">
              <div class="ops-report-row">
                <span><?= kibo_icon('chart', 17) ?></span>
                <p>
                  <strong>Doanh thu &amp; lợi nhuận</strong>
                  <small id="reportRange1">Tổng hợp đơn hàng đã giao thành công và doanh thu thực nhận</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="exportRevenueReportCSV()">Tải xuống CSV</button>
              </div>

              <div class="ops-report-row">
                <span><?= kibo_icon('bag', 17) ?></span>
                <p>
                  <strong>Đơn hàng theo trạng thái</strong>
                  <small id="reportRange2">Toàn bộ danh sách đơn hàng và trạng thái vòng đời</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="exportOrdersStatusCSV()">Tải xuống CSV</button>
              </div>

              <div class="ops-report-row">
                <span><?= kibo_icon('box', 17) ?></span>
                <p>
                  <strong>Tồn kho &amp; nhập hàng</strong>
                  <small id="reportRange3">Danh sách sản phẩm, quy cách, số lượng tồn và cảnh báo nhập hàng</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="exportInventoryReportCSV()">Tải xuống CSV</button>
              </div>

              <div class="ops-report-row">
                <span><?= kibo_icon('heart', 17) ?></span>
                <p>
                  <strong>Đối soát Quỹ cứu trợ 5%</strong>
                  <small id="reportRange4">Chi tiết các khoản trích 5% theo đơn và tình trạng giải ngân</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="exportFundReportCSV()">Tải xuống CSV</button>
              </div>
            </div>
          </article>

          <!-- KHỐI BÁO CÁO TỔNG HỢP & PHÂN BỔ TRẠNG THÁI -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <article class="ops-card">
              <div class="ops-card-head">
                <div>
                  <h2>Phân bổ trạng thái đơn hàng</h2>
                  <p>Số lượng và tỷ lệ % trên tổng <?= $totalOrdersCount ?> đơn hàng</p>
                </div>
              </div>
              <div class="ops-table-wrap">
                <table class="ops-table">
                  <thead>
                    <tr>
                      <th>Trạng thái</th>
                      <th style="text-align: right;">Số đơn</th>
                      <th style="text-align: right;">Tỷ lệ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><span class="ops-status-badge status-cho-xac-nhan">Chờ xác nhận</span></td>
                      <td style="text-align: right; font-weight: bold;"><?= $newOrdersCount ?></td>
                      <td style="text-align: right; color: #666;"><?= $totalOrdersCount > 0 ? round(($newOrdersCount / $totalOrdersCount) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                      <td><span class="ops-status-badge status-cho-dong-goi">Chờ đóng gói</span></td>
                      <td style="text-align: right; font-weight: bold;">
                        <?php 
                        $pkgCount = count(array_filter($dbOrders, fn($o) => $o['status'] === 'Chờ đóng gói'));
                        echo $pkgCount;
                        ?>
                      </td>
                      <td style="text-align: right; color: #666;"><?= $totalOrdersCount > 0 ? round(($pkgCount / $totalOrdersCount) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                      <td><span class="ops-status-badge status-da-xuat-kho">Đã xuất kho</span></td>
                      <td style="text-align: right; font-weight: bold;">
                        <?php 
                        $outCount = count(array_filter($dbOrders, fn($o) => $o['status'] === 'Đã xuất kho'));
                        echo $outCount;
                        ?>
                      </td>
                      <td style="text-align: right; color: #666;"><?= $totalOrdersCount > 0 ? round(($outCount / $totalOrdersCount) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                      <td><span class="ops-status-badge status-dang-giao">Đang giao</span></td>
                      <td style="text-align: right; font-weight: bold;"><?= $shippingOrdersCount ?></td>
                      <td style="text-align: right; color: #666;"><?= $totalOrdersCount > 0 ? round(($shippingOrdersCount / $totalOrdersCount) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                      <td><span class="ops-status-badge status-giao-thanh-cong">Giao thành công</span></td>
                      <td style="text-align: right; font-weight: bold; color: #16a34a;"><?= $deliveredOrdersCount ?></td>
                      <td style="text-align: right; color: #16a34a; font-weight: bold;"><?= $totalOrdersCount > 0 ? round(($deliveredOrdersCount / $totalOrdersCount) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                      <td><span class="ops-status-badge status-da-huy">Đã hủy</span></td>
                      <td style="text-align: right; font-weight: bold; color: #dc2626;"><?= $cancelledOrdersCount ?></td>
                      <td style="text-align: right; color: #dc2626;"><?= $totalOrdersCount > 0 ? round(($cancelledOrdersCount / $totalOrdersCount) * 100, 1) : 0 ?>%</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>

            <article class="ops-card">
              <div class="ops-card-head">
                <div>
                  <h2>Đối soát tài chính &amp; Quỹ cứu trợ 5%</h2>
                  <p>Số liệu đối soát tự động từ CSDL PostgreSQL</p>
                </div>
              </div>
              <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #fbf9f6; border-radius: 6px; border-left: 4px solid #16a34a;">
                  <div>
                    <strong style="display: block; font-size: 14.5px; color: #2c1a0e;">Doanh thu giao thành công</strong>
                    <small style="color: #666;">Chỉ tính đơn đã hoàn tất</small>
                  </div>
                  <strong style="font-size: 17px; color: #16a34a;"><?= number_format($totalRevenueDelivered, 0, ',', '.') ?>đ</strong>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #fbf9f6; border-radius: 6px; border-left: 4px solid #b91c1c;">
                  <div>
                    <strong style="display: block; font-size: 14.5px; color: #2c1a0e;">Tổng quỹ đã trích (5%)</strong>
                    <small style="color: #666;">Được trích theo đơn thành công</small>
                  </div>
                  <strong style="font-size: 17px; color: #b91c1c;"><?= number_format($fundSummary['TongTichLuy'] ?? 0, 0, ',', '.') ?>đ</strong>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #fbf9f6; border-radius: 6px; border-left: 4px solid #2563eb;">
                  <div>
                    <strong style="display: block; font-size: 14.5px; color: #2c1a0e;">Tổng quỹ đã giải ngân</strong>
                    <small style="color: #666;">Có chứng từ ủy nhiệm chi</small>
                  </div>
                  <strong style="font-size: 17px; color: #2563eb;"><?= number_format($fundSummary['TongGiaiNgan'] ?? 0, 0, ',', '.') ?>đ</strong>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #fbf9f6; border-radius: 6px; border-left: 4px solid #d97706;">
                  <div>
                    <strong style="display: block; font-size: 14.5px; color: #2c1a0e;">Quỹ còn lại sẵn sàng giải ngân</strong>
                    <small style="color: #666;">Đã trích trừ đi đã chuyển khoản</small>
                  </div>
                  <strong style="font-size: 17px; color: #d97706;"><?= number_format(max(0, ($fundSummary['TongTichLuy'] ?? 0) - ($fundSummary['TongGiaiNgan'] ?? 0)), 0, ',', '.') ?>đ</strong>
                </div>
              </div>
            </article>
          </div>
        </div>
      </div>

      <!-- ====================================================================
           TAB 6: QUẢN LÝ YÊU CẦU ĐỔI TRẢ (RETURNS - BẢNG YEU_CAU_DOI_TRA)
           ==================================================================== -->
      <div id="tabContent_returns" class="tab-pane" style="display: none;">
        <article class="ops-card" style="margin-top: 18px;">
          <div class="ops-card-head">
            <div>
              <h2>Danh sách yêu cầu đổi trả hàng</h2>
              <p>Chính sách đổi trả trong vòng 3 ngày theo quy định CSDL KIBO</p>
            </div>
            <span style="font-size: 13.5px; color: #666;">Tổng cộng: <strong><?= count($dbReturns) ?></strong> yêu cầu</span>
          </div>
          <div class="ops-table-wrap">
            <table class="ops-table">
              <thead>
                <tr>
                  <th style="width: 12%;">Mã yêu cầu</th>
                  <th style="width: 12%;">Mã đơn hàng</th>
                  <th style="width: 26%;">Lý do đổi trả</th>
                  <th style="width: 13%;">Ngày gửi</th>
                  <th style="width: 13%;">Trạng thái</th>
                  <th style="width: 10%;">Hướng xử lý</th>
                  <th style="width: 14%;">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($dbReturns)): ?>
                  <?php foreach ($dbReturns as $ret): 
                    $st = $ret['TrangThaiYeuCau'] ?? 'Chờ xử lý';
                    $stClass = ($st === 'Đã hoàn tất' || $st === 'Đã đồng ý đổi' || $st === 'Đã hoàn tiền') ? 'good' : (($st === 'Đã từ chối' || $st === 'Từ chối') ? 'out' : 'low');
                    $isPending = ($st === 'Chờ xử lý');
                  ?>
                    <tr>
                      <td>
                        <a href="javascript:void(0)" onclick="openReturnDetailModal('<?= htmlspecialchars($ret['MaYeuCau']) ?>')" style="color: #2c1a0e; font-weight: bold; text-decoration: underline;" title="Bấm để xem chi tiết & hình ảnh minh chứng">
                          <code><?= htmlspecialchars($ret['MaYeuCau']) ?></code>
                        </a>
                      </td>
                      <td>
                        <strong style="color: #2c1a0e;"><?= htmlspecialchars($ret['MaDonHang']) ?></strong>
                        <?php if (!empty($ret['TenNguoiNhan'])): ?>
                          <div style="font-size: 11.5px; color: #777; margin-top: 2px;"><?= htmlspecialchars($ret['TenNguoiNhan']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td style="color: #333; line-height: 1.5;">
                        <div><?= htmlspecialchars($ret['LyDoDoiTra']) ?></div>
                        <?php if (!empty($ret['HinhAnhMinhChung'])): ?>
                          <a href="javascript:void(0)" onclick="openReturnDetailModal('<?= htmlspecialchars($ret['MaYeuCau']) ?>')" style="display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; font-size: 11.5px; color: #2e7d32; font-weight: 600; text-decoration: none;">
                            📷 <span>Xem hình ảnh minh chứng</span>
                          </a>
                        <?php endif; ?>
                      </td>
                      <td style="color: #666; font-size: 13px;">
                        <?= date('d/m/Y H:i', strtotime($ret['NgayYeuCau'])) ?>
                      </td>
                      <td>
                        <span class="ops-stock-status <?= $stClass ?>"><?= htmlspecialchars($st) ?></span>
                      </td>
                      <td style="font-size: 13px; color: #555;">
                        <?= htmlspecialchars($ret['HuongXuLy'] ?: ($isPending ? 'Chưa xử lý' : 'Đã duyệt')) ?>
                      </td>
                      <td>
                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                          <button type="button" onclick="openReturnDetailModal('<?= htmlspecialchars($ret['MaYeuCau']) ?>')" style="padding: 4px 8px; font-size: 12px; background: #2c1a0e; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-weight: 600;" title="Xem chi tiết & ảnh minh chứng">Chi tiết</button>
                          <?php if ($isPending): ?>
                            <button type="button" onclick="adminProcessReturn('<?= htmlspecialchars($ret['MaYeuCau']) ?>', 'doi')" style="padding: 4px 8px; font-size: 12px; background: #2e7d32; color: #fff; border: none; border-radius: 3px; cursor: pointer;" title="Đồng ý đổi hàng">Đổi</button>
                            <button type="button" onclick="adminProcessReturn('<?= htmlspecialchars($ret['MaYeuCau']) ?>', 'hoantien')" style="padding: 4px 8px; font-size: 12px; background: #1565c0; color: #fff; border: none; border-radius: 3px; cursor: pointer;" title="Hoàn tiền và hủy phần trích quỹ">Hoàn tiền</button>
                            <button type="button" onclick="adminProcessReturn('<?= htmlspecialchars($ret['MaYeuCau']) ?>', 'tuchoi')" style="padding: 4px 6px; font-size: 12px; background: #c94336; color: #fff; border: none; border-radius: 3px; cursor: pointer;" title="Từ chối yêu cầu">Từ chối</button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #777;">
                      Chưa có yêu cầu đổi trả nào được ghi nhận.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- ====================================================================
           TAB 7: QUẢN LÝ MÃ GIẢM GIÁ (VOUCHERS - BẢNG VOUCHER)
           ==================================================================== -->
      <div id="tabContent_vouchers" class="tab-pane" style="display: none;">
        <article class="ops-card" style="margin-top: 18px;">
          <div class="ops-card-head">
            <div>
              <h2>Danh sách Mã giảm giá (Vouchers)</h2>
              <p>Quản lý mã khuyến mãi, tỷ lệ giảm, đơn tối thiểu và lượt sử dụng</p>
            </div>
            <button type="button" class="ops-primary-button" onclick="openAddVoucherModal()">+ Thêm mã giảm giá</button>
          </div>
          <div class="ops-table-wrap">
            <table class="ops-table">
              <thead>
                <tr>
                  <th style="width: 14%;">Mã Voucher</th>
                  <th style="width: 20%;">Tên ưu đãi</th>
                  <th style="width: 14%;">Mức giảm</th>
                  <th style="width: 13%;">Đơn tối thiểu</th>
                  <th style="width: 12%;">Lượt còn lại</th>
                  <th style="width: 13%;">Hạn dùng</th>
                  <th style="width: 14%;">Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($dbVouchers)): ?>
                  <?php foreach ($dbVouchers as $vc): 
                    $isExpired = (!empty($vc['ngayketthuc']) && strtotime($vc['ngayketthuc']) < time());
                    $soLuongConLai = (int)($vc['soluongconlai'] ?? 0);
                    $isOutOfStock = ($soLuongConLai <= 0);
                    $isActive = !empty($vc['kichhoat']) && !$isExpired && !$isOutOfStock;
                    
                    $loaiGiam = $vc['loaigiamgia'] ?? ($vc['loai'] ?? 'SoTien');
                    $giaTriGiam = (float)($vc['giatrigiam'] ?? ($vc['giatri'] ?? 0));
                    $donHangToiThieu = (float)($vc['donhangtoithieu'] ?? ($vc['dontoithieu'] ?? 0));
                    
                    $discountStr = '';
                    if ($loaiGiam === 'PhanTram' || $loaiGiam === 'Phần trăm') {
                      $discountStr = $giaTriGiam . '%';
                      if (!empty($vc['giamtoida'])) {
                        $discountStr .= ' (tối đa ' . number_format($vc['giamtoida'], 0, ',', '.') . 'đ)';
                      }
                    } else {
                      $discountStr = number_format($giaTriGiam, 0, ',', '.') . 'đ';
                    }
                  ?>
                    <tr>
                      <td><strong style="color: #2c1a0e; font-size: 13.5px;"><code><?= htmlspecialchars($vc['mavoucher']) ?></code></strong></td>
                      <td style="color: #333;"><?= htmlspecialchars($vc['tenvoucher'] ?: $vc['mavoucher']) ?></td>
                      <td style="color: #c94336; font-weight: bold;"><?= $discountStr ?></td>
                      <td><?= number_format($donHangToiThieu, 0, ',', '.') ?>đ</td>
                      <td><?= $soLuongConLai ?> lượt</td>
                      <td style="color: <?= $isExpired ? '#c94336' : '#666' ?>; font-size: 12.5px;">
                        <?= !empty($vc['ngayketthuc']) ? date('d/m/Y', strtotime($vc['ngayketthuc'])) : 'Không hạn' ?>
                        <?= $isExpired ? '<br><small style="color: #c94336;">(Hết hạn)</small>' : '' ?>
                      </td>
                      <td>
                        <?php if ($isExpired): ?>
                          <span style="display: inline-block; padding: 4px 8px; font-size: 12px; border-radius: 3px; background: #ffebee; color: #c94336; font-weight: bold;">Hết hạn</span>
                        <?php elseif ($isOutOfStock): ?>
                          <span style="display: inline-block; padding: 4px 8px; font-size: 12px; border-radius: 3px; background: #fff3e0; color: #e65100; font-weight: bold;">Hết lượt</span>
                        <?php else: ?>
                          <button type="button" onclick="adminToggleVoucher('<?= htmlspecialchars($vc['mavoucher']) ?>', this)" 
                                  style="padding: 4px 10px; font-size: 12px; border-radius: 3px; border: 1px solid <?= !empty($vc['kichhoat']) ? '#2e7d32' : '#999' ?>; background: <?= !empty($vc['kichhoat']) ? '#e8f5e9' : '#f5f5f5' ?>; color: <?= !empty($vc['kichhoat']) ? '#2e7d32' : '#666' ?>; font-weight: bold; cursor: pointer;">
                            <?= !empty($vc['kichhoat']) ? 'Đang bật' : 'Đã tắt' ?>
                          </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #777;">
                      Chưa có mã giảm giá nào được tạo.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- ====================================================================
           TAB 8: QUẢN LÝ KHÁCH HÀNG (CUSTOMERS - BẢNG KHACH_HANG & TAI_KHOAN)
           ==================================================================== -->
      <div id="tabContent_customers" class="tab-pane" style="display: none;">
        <article class="ops-card ops-table-card">
          <header class="ops-card-header">
            <div>
              <h2>Danh sách khách hàng</h2>
              <p>Quản lý tài khoản, lịch sử tích lũy và kiểm soát trạng thái truy cập.</p>
            </div>
            <div style="font-size: 13px; color: #666;">
              Tổng cộng: <strong><?= count($customersList ?? []) ?></strong> khách hàng
            </div>
          </header>
          <div class="ops-table-wrap">
            <table class="ops-table">
              <thead>
                <tr>
                  <th style="width: 10%;">Mã KH</th>
                  <th style="width: 18%;">Họ và tên</th>
                  <th style="width: 18%;">Email / Tài khoản</th>
                  <th style="width: 12%;">Số điện thoại</th>
                  <th style="width: 10%;">Tổng đơn</th>
                  <th style="width: 14%;">Tổng chi tiêu</th>
                  <th style="width: 10%;">Trạng thái</th>
                  <th style="width: 8%;">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($customersList)): ?>
                  <?php foreach ($customersList as $cust): 
                    $isLocked = ($cust['trangthai'] === 'Bị khóa');
                    $isAdmin = (($cust['vaitro'] ?? '') === 'Admin');
                  ?>
                    <tr>
                      <td><code><?= htmlspecialchars($cust['makh']) ?></code></td>
                      <td>
                        <strong><?= htmlspecialchars($cust['hoten']) ?></strong>
                        <?php if ($isAdmin): ?>
                          <span style="display: inline-block; padding: 2px 6px; background: #2c1a0e; color: #fff; border-radius: 3px; font-size: 10px; font-weight: bold; margin-left: 4px;">ADMIN</span>
                        <?php endif; ?>
                      </td>
                      <td style="color: #444;"><?= htmlspecialchars($cust['email'] ?? 'Chưa cập nhật') ?></td>
                      <td><?= htmlspecialchars($cust['sodienthoai'] ?: 'Chưa có SĐT') ?></td>
                      <td><strong><?= (int)$cust['tongdon'] ?></strong> đơn</td>
                      <td style="color: #2e7d32; font-weight: 600;"><?= number_format((float)$cust['tongchitieu'], 0, ',', '.') ?>đ</td>
                      <td>
                        <span class="ops-stock-status <?= $isLocked ? 'out' : 'good' ?>">
                          <?= htmlspecialchars($cust['trangthai'] ?: 'Hoạt động') ?>
                        </span>
                      </td>
                      <td>
                        <?php if (!$isAdmin && !empty($cust['matk'])): ?>
                          <button type="button" 
                                  onclick="adminToggleUserStatus('<?= htmlspecialchars($cust['matk']) ?>', '<?= $isLocked ? 'Bị khóa' : 'Hoạt động' ?>')"
                                  style="padding: 4px 8px; font-size: 11.5px; border: 1px solid <?= $isLocked ? '#2e7d32' : '#c94336' ?>; background: <?= $isLocked ? '#eaf4e6' : '#fdf2f2' ?>; color: <?= $isLocked ? '#2e7d32' : '#c94336' ?>; border-radius: 3px; cursor: pointer; font-weight: 600;">
                            <?= $isLocked ? 'Mở khóa' : 'Khóa' ?>
                          </button>
                        <?php else: ?>
                          <span style="color: #999; font-size: 11.5px;">--</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="8" style="text-align: center; padding: 30px; color: #777;">Chưa có dữ liệu khách hàng.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- ====================================================================
           TAB 9: GIAO DỊCH THANH TOÁN (PAYMENTS - BẢNG THANH_TOAN & ĐỐI SOÁT)
           ==================================================================== -->
      <div id="tabContent_payments" class="tab-pane" style="display: none;">
        
        <!-- Khối Cảnh báo Đối soát -->
        <?php if (!empty($reconciliationList)): ?>
          <div style="background: #fff8e1; border: 1px solid #ffe082; border-left: 4px solid #f59e0b; padding: 14px 18px; border-radius: 4px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 6px; font-size: 14.5px; color: #b45309; display: flex; align-items: center; gap: 6px;">
              ⚠️ Cần đối soát thanh toán (<?= count($reconciliationList) ?> giao dịch)
            </h3>
            <p style="margin: 0 0 10px; font-size: 13px; color: #78350f;">
              Các đơn hàng giao thành công nhưng chưa nhận tiền, hoặc đơn chuyển khoản VietQR quá hạn 24h chưa được xác nhận.
            </p>
            <div style="display: flex; flex-direction: column; gap: 6px;">
              <?php foreach ($reconciliationList as $rec): ?>
                <div style="background: #ffffff; border: 1px solid #fed7aa; padding: 8px 12px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; font-size: 12.5px;">
                  <div>
                    Đơn <strong>#<?= htmlspecialchars($rec['madonhang']) ?></strong> (<?= htmlspecialchars($rec['tennguoinhan'] ?? '') ?>) 
                    - Số tiền: <strong style="color: #2c1a0e;"><?= number_format((float)$rec['sotien'], 0, ',', '.') ?>đ</strong> 
                    - PT: <strong><?= htmlspecialchars($rec['phuongthuctt']) ?></strong>
                    <span style="color: #c2410c; margin-left: 6px;">[<?= htmlspecialchars($rec['lydodoisoat']) ?>]</span>
                  </div>
                  <button type="button" onclick="adminConfirmPayment('<?= htmlspecialchars($rec['magiaodich']) ?>')" style="padding: 4px 10px; background: #2e7d32; color: #fff; border: none; border-radius: 3px; font-size: 12px; font-weight: bold; cursor: pointer;">
                    ✓ Đã nhận tiền
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Bảng tất cả giao dịch thanh toán -->
        <article class="ops-card ops-table-card">
          <header class="ops-card-header">
            <div>
              <h2>Nhật ký giao dịch thanh toán</h2>
              <p>Lịch sử thanh toán VietQR và thu tiền khi giao hàng (COD).</p>
            </div>
            <div style="font-size: 13px; color: #666;">
              Tổng: <strong><?= count($paymentsList ?? []) ?></strong> giao dịch
            </div>
          </header>
          <div class="ops-table-wrap">
            <table class="ops-table">
              <thead>
                <tr>
                  <th style="width: 12%;">Mã giao dịch</th>
                  <th style="width: 14%;">Mã đơn hàng</th>
                  <th style="width: 18%;">Khách hàng / SĐT</th>
                  <th style="width: 12%;">Phương thức</th>
                  <th style="width: 14%;">Số tiền</th>
                  <th style="width: 14%;">Thời gian</th>
                  <th style="width: 10%;">Trạng thái</th>
                  <th style="width: 6%;">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($paymentsList)): ?>
                  <?php foreach ($paymentsList as $pay): 
                    $isSuccess = ($pay['trangthaitt'] === 'Thành công');
                    $stClass = $isSuccess ? 'good' : (($pay['trangthaitt'] === 'Hoàn tiền') ? 'out' : 'low');
                  ?>
                    <tr>
                      <td><code><?= htmlspecialchars($pay['magiaodich']) ?></code></td>
                      <td><strong style="color: #2c1a0e;"><?= htmlspecialchars($pay['madonhang']) ?></strong></td>
                      <td>
                        <div><?= htmlspecialchars($pay['tennguoinhan'] ?? 'Khách vãng lai') ?></div>
                        <small style="color: #777;"><?= htmlspecialchars($pay['sodienthoainhan'] ?? '') ?></small>
                      </td>
                      <td>
                        <span style="font-weight: 600; color: <?= $pay['phuongthuctt'] === 'VietQR' ? '#1565c0' : '#4e342e' ?>;">
                          <?= htmlspecialchars($pay['phuongthuctt']) ?>
                        </span>
                      </td>
                      <td style="font-weight: bold; color: #2e7d32;"><?= number_format((float)$pay['sotien'], 0, ',', '.') ?>đ</td>
                      <td style="color: #666; font-size: 12.5px;">
                        <?= !empty($pay['thoigiantt']) ? date('d/m/Y H:i', strtotime($pay['thoigiantt'])) : '--' ?>
                      </td>
                      <td>
                        <span class="ops-stock-status <?= $stClass ?>"><?= htmlspecialchars($pay['trangthaitt']) ?></span>
                      </td>
                      <td>
                        <?php if (!$isSuccess && $pay['trangthaitt'] !== 'Hoàn tiền'): ?>
                          <button type="button" onclick="adminConfirmPayment('<?= htmlspecialchars($pay['magiaodich']) ?>')" style="padding: 3px 8px; font-size: 11px; background: #2e7d32; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-weight: bold;" title="Xác nhận nhận tiền">Xác nhận</button>
                        <?php else: ?>
                          <span style="color: #999; font-size: 11px;">✓ Xong</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="8" style="text-align: center; padding: 30px; color: #777;">Chưa có giao dịch thanh toán nào.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </div>

    </section>
  </div>

  <!-- JAVASCRIPT ĐIỀU KHIỂN ĐA TÍNH NĂNG -->
  <script>
    // 1. DỮ LIỆU TỒN KHO & ĐƠN HÀNG BAN ĐẦU
    let inventoryData = <?= json_encode($initialInventory, JSON_UNESCAPED_UNICODE) ?>;
    let ordersData = <?= json_encode($initialOrders, JSON_UNESCAPED_UNICODE) ?>;
    let returnsData = <?= json_encode($dbReturns, JSON_UNESCAPED_UNICODE) ?>;
    let currentTab = 'overview';
    let searchQuery = '';
    let selectedRange = '30';

    const tabDescriptions = {
      overview: { title: 'Tổng quan cửa hàng', desc: 'Các chỉ số kinh doanh chính trong kỳ.' },
      orders: { title: 'Quản lý đơn hàng', desc: 'Theo dõi và cập nhật trạng thái đơn hàng.' },
      inventory: { title: 'Quản lý tồn kho', desc: 'Kiểm tra số lượng và bổ sung hàng hóa.' },
      fund: { title: 'Đối soát Quỹ cứu trợ', desc: 'Theo dõi khoản trích 5% và lịch sử giải ngân.' },
      vouchers: { title: 'Quản lý mã giảm giá', desc: 'Tạo mới, kích hoạt hoặc vô hiệu hóa các mã ưu đãi khách hàng.' },
      reports: { title: 'Báo cáo kinh doanh', desc: 'Tổng hợp số liệu theo thời gian và danh mục.' },
      returns: { title: 'Quản lý yêu cầu đổi trả', desc: 'Tiếp nhận và xử lý yêu cầu đổi trả 3 ngày theo quy định CSDL.' },
      customers: { title: 'Quản lý khách hàng', desc: 'Danh sách khách hàng, theo dõi chi tiêu và phân quyền khóa tài khoản.' },
      payments: { title: 'Giao dịch thanh toán & Đối soát', desc: 'Theo dõi dòng tiền thanh toán VietQR / COD và xác nhận nhận tiền.' }
    };

    // 2. CHUYỂN ĐỔI TAB MƯỢT MÀ
    function switchAdminTab(tab) {
      currentTab = tab;
      try { sessionStorage.setItem('kibo_active_admin_tab', tab); } catch (e) {}
      
      // Update sidebar active buttons
      const navButtons = document.querySelectorAll('#adminNav button');
      const tabs = ['overview', 'orders', 'inventory', 'fund', 'vouchers', 'reports', 'returns', 'customers', 'payments'];
      navButtons.forEach((btn, index) => {
        btn.className = (tabs[index] === tab) ? 'active' : '';
      });

      // Update Heading
      if (tabDescriptions[tab]) {
        document.getElementById('tabHeadingTitle').textContent = tabDescriptions[tab].title;
        document.getElementById('tabHeadingDesc').textContent = tabDescriptions[tab].desc;
      }

      // Show/Hide Panes
      tabs.forEach(t => {
        const pane = document.getElementById('tabContent_' + t);
        if (pane) pane.style.display = (t === tab) ? 'block' : 'none';
      });

      // Re-render
      renderInventory();
      renderOrders();
      renderOverview();
    }

    // 3. TÍNH TOÁN TRẠNG THÁI KHO
    function getStockStatus(stock) {
      if (stock === 0) return { label: 'Hết hàng', className: 'out' };
      if (stock < 10) return { label: 'Sắp hết', className: 'low' };
      return { label: 'Còn hàng', className: 'good' };
    }

    // 4. RENDER BẢNG TỒN KHO & CẢNH BÁO
    function renderInventory() {
      const q = searchQuery.toLowerCase().trim();
      const filtered = q ? inventoryData.filter(item => 
        (item.name + ' ' + item.sku + ' ' + item.category).toLowerCase().includes(q)
      ) : inventoryData;

      const tbody = document.getElementById('inventoryTableBody');
      if (tbody) {
        tbody.innerHTML = filtered.map(item => {
          const st = getStockStatus(item.stock);
          return `
            <tr>
              <td>
                <div class="ops-product">
                  <span style="border-left: 4px solid ${item.color};"></span>
                  <strong>${item.name}</strong>
                </div>
              </td>
              <td><code>${item.sku}</code></td>
              <td>${item.category}</td>
              <td><strong>${item.sold}</strong></td>
              <td><strong style="font-size: 13px;">${item.stock}</strong></td>
              <td><span class="ops-stock-status ${st.className}">${st.label}</span></td>
              <td>
                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                  <button type="button" class="ops-restock" onclick="restockItem('${item.id}')">+ Nhập</button>
                  <button type="button" class="ops-restock" style="background: #2c1a0e; color: #fff;" onclick="openEditProductModal('${item.id}', '${item.name.replace(/'/g, "\\'")}', '${item.stock}')">✏️ Sửa</button>
                  <button type="button" class="ops-restock" style="background: ${item.trangthai === 'Ngừng bán' ? '#1565c0' : '#c94336'}; color: #fff;" onclick="adminToggleProductStatus('${item.id}', '${item.trangthai || 'Đang bán'}')">${item.trangthai === 'Ngừng bán' ? 'Mở bán' : 'Ẩn'}</button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
      }

      // Cập nhật cảnh báo low stock
      const lowStock = inventoryData.filter(item => item.stock < 10);
      const totalStock = inventoryData.reduce((sum, item) => sum + item.stock, 0);

      const alertList = document.getElementById('lowStockAlertList');
      if (alertList) {
        if (lowStock.length === 0) {
          alertList.innerHTML = '<p style="padding: 16px; color: #887e76; font-size: 11px;">Mọi sản phẩm đều đủ hàng trong kho.</p>';
        } else {
          alertList.innerHTML = lowStock.map(item => `
            <div class="ops-flat-alert-row">
              <p>
                <strong>${item.name}</strong>
                <small>${item.stock === 0 ? '❌ Đã hết hàng' : '⚠️ Còn ' + item.stock + ' sản phẩm'}</small>
              </p>
              <button type="button" onclick="restockItem('${item.id}')">Nhập kho (+20)</button>
            </div>
          `).join('');
        }
      }

      // Cập nhật KPI & thông báo
      const kpiTotal = document.getElementById('kpiTotalStock');
      if (kpiTotal) kpiTotal.textContent = totalStock;

      const kpiLow = document.getElementById('kpiLowStockMsg');
      if (kpiLow) kpiLow.textContent = lowStock.length + ' sản phẩm cần xử lý';

      const notifBadge = document.getElementById('notifBadge');
      if (notifBadge) notifBadge.textContent = lowStock.length;

      const notifMsg = document.getElementById('notifLowStockMsg');
      if (notifMsg) notifMsg.textContent = `⚠️ ${lowStock.length} sản phẩm cần nhập kho ngay.`;

      const invDesc = document.getElementById('inventoryCountDesc');
      if (invDesc) invDesc.textContent = `${inventoryData.length} sản phẩm đang quản lý`;
    }

    // THÔNG BÁO TRỰC TIẾP TRÊN WEB (DẠNG CƠ BẢN NHẤT)
    function showAdminToast(title, message, type = 'success') {
      let toastContainer = document.getElementById('adminToastContainer');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'adminToastContainer';
        toastContainer.style.cssText = 'position: fixed; top: 16px; right: 16px; z-index: 999999999; display: flex; flex-direction: column; gap: 8px; max-width: 360px;';
        document.body.appendChild(toastContainer);
      }

      const toast = document.createElement('div');
      const isSuccess = (type === 'success');
      const bgCol = isSuccess ? '#2e7d32' : '#c94336';

      toast.style.cssText = `
        background: ${bgCol};
        color: #ffffff;
        border-radius: 4px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        font-family: Arial, sans-serif;
        font-size: 13.5px;
      `;

      toast.innerHTML = `
        <div>
          <strong>${title}:</strong> ${message}
        </div>
        <button type="button" style="background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; padding: 0 4px; line-height: 1;">✕</button>
      `;

      const closeBtn = toast.querySelector('button');
      closeBtn.onclick = () => toast.remove();
      toastContainer.appendChild(toast);

      setTimeout(() => {
        if (toast.parentNode) toast.remove();
      }, 3500);
    }

    // 5. THAO TÁC NHẬP KHO (+20 MÓN)
    function restockItem(id) {
      const item = inventoryData.find(i => i.id === id);
      if (item) {
        item.stock += 20;
        renderInventory();
        renderOverview();
        showAdminToast('Nhập kho thành công', `Đã cộng thêm +20 sản phẩm vào kho cho: "${item.name}".`);
      }
    }

    function openEditProductModal(id, name, stock) {
      document.getElementById('editProductMasp').value = id;
      document.getElementById('editProductTensp').value = name;
      document.getElementById('editProductSoluongton').value = stock;
      document.getElementById('modalEditProductTitle').textContent = 'Chỉnh sửa sản phẩm #' + id;
      document.getElementById('modalEditProductBackdrop').style.display = 'grid';
    }

    function closeEditProductModal() {
      document.getElementById('modalEditProductBackdrop').style.display = 'none';
    }

    async function handleEditProductSubmit(e) {
      e.preventDefault();
      const masp = document.getElementById('editProductMasp').value;
      const tensp = document.getElementById('editProductTensp').value;
      const giaban = document.getElementById('editProductGiaban').value;
      const soluongton = document.getElementById('editProductSoluongton').value;
      const btn = document.getElementById('btnSaveEditProduct');

      btn.disabled = true;
      btn.textContent = '⏳ Đang lưu...';

      try {
        const resp = await fetch('index.php?action=admin_edit_product', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ masp, tensp, giaban, soluongton })
        });
        const res = await resp.json();
        if (res.success) {
          showAdminToast('Thành công', res.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAdminToast('Lỗi', res.error || 'Không thể lưu sản phẩm.', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Lưu thay đổi';
      }
    }

    async function adminToggleProductStatus(masp, currentStatus) {
      const nextStatus = (currentStatus === 'Ngừng bán') ? 'Đang bán' : 'Ngừng bán';
      if (!confirm(`Xác nhận chuyển trạng thái sản phẩm ${masp} sang "${nextStatus}"?`)) return;
      try {
        const resp = await fetch('index.php?action=admin_toggle_product_status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ masp, trangthai: nextStatus })
        });
        const res = await resp.json();
        if (res.success) {
          showAdminToast('Thành công', res.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAdminToast('Thất bại', res.error || 'Không thể đổi trạng thái sản phẩm.', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', err.message, 'error');
      }
    }

    function updateImagePreview(url) {
      const img = document.getElementById('imgPreview');
      const placeholder = document.getElementById('imgPlaceholder');
      if (!img) return;
      if (url && url.trim() !== '') {
        img.src = url.trim();
        img.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
      } else {
        img.src = '';
        img.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
      }
    }

    function setPresetImg(url) {
      const inp = document.getElementById('newProdImage');
      if (inp) inp.value = url;
      updateImagePreview(url);
    }

    function openAddCategoryModal() {
      const form = document.getElementById('formAddCategory');
      if (form) form.reset();
      const modal = document.getElementById('addCategoryModalBackdrop');
      if (modal) {
        modal.style.display = 'grid';
        setTimeout(() => {
          const inp = document.getElementById('newCategoryName');
          if (inp) inp.focus();
        }, 50);
      }
    }

    function closeAddCategoryModal() {
      const modal = document.getElementById('addCategoryModalBackdrop');
      if (modal) modal.style.display = 'none';
    }

    async function submitNewCategory(e) {
      e.preventDefault();
      const catName = document.getElementById('newCategoryName').value.trim();
      const catDesc = document.getElementById('newCategoryDesc').value.trim();
      if (!catName) return;

      const btn = document.getElementById('btnSaveNewCategory');
      btn.disabled = true;
      btn.innerHTML = '⏳ Vui lòng chờ...';

      try {
        const resp = await fetch('index.php?action=admin_create_category', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tendanhmuc: catName, mota: catDesc })
        });
        const res = await resp.json();
        if (res.success) {
          const sel = document.getElementById('newProdCategory');
          if (sel) {
            const opt = document.createElement('option');
            opt.value = res.madanhmuc;
            opt.textContent = res.tendanhmuc;
            opt.selected = true;
            sel.appendChild(opt);
          }
          closeAddCategoryModal();
          showAdminToast('Thêm danh mục thành công', `Đã tạo danh mục mới "${res.tendanhmuc}" vào hệ thống.`, 'success');
        } else {
          showAdminToast('Không thể tạo danh mục', res.error || 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', 'Không thể kết nối đến máy chủ: ' + err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Lưu danh mục';
      }
    }

    function openAddProductModal() {
      const form = document.getElementById('formAddProduct');
      if (form) form.reset();
      updateImagePreview('');
      const modal = document.getElementById('addProductModalBackdrop');
      if (modal) modal.style.display = 'grid';
    }

    function closeAddProductModal() {
      const modal = document.getElementById('addProductModalBackdrop');
      if (modal) modal.style.display = 'none';
    }

    async function submitNewProduct(e) {
      e.preventDefault();
      const name = document.getElementById('newProdName').value.trim();
      if (!name) {
        alert('Vui lòng nhập tên sản phẩm.');
        return;
      }
      const catCode = document.getElementById('newProdCategory').value;
      if (!catCode) {
        alert('Vui lòng chọn danh mục sản phẩm.');
        return;
      }
      const rawPrice = document.getElementById('newProdPrice').value;
      const price = parseFloat(rawPrice);
      if (isNaN(price) || price <= 0) {
        alert('Giá bán phải là số hợp lệ và lớn hơn 0đ.');
        return;
      }
      const catText = document.getElementById('newProdCategory').selectedOptions[0].text;
      const brand = document.getElementById('newProdBrand').value.trim() || 'KIBO Original';
      const variant = document.getElementById('newProdVariant').value.trim() || 'Tiêu chuẩn';
      const stock = parseInt(document.getElementById('newProdStock').value) || 0;
      const desc = document.getElementById('newProdDesc').value.trim();
      const image = document.getElementById('newProdImage') ? document.getElementById('newProdImage').value.trim() : 'assets/images/products/sp_kibo_cahoi.png';

      const btnSubmit = document.getElementById('btnSaveNewProduct');
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '⏳ Vui lòng chờ...';

      try {
        const payload = {
          tensp: name,
          madanhmuc: catCode,
          thuonghieu: brand,
          tenbienthe: variant,
          giaban: price,
          soluongton: stock,
          motachitiet: desc,
          hinhanhchinh: image
        };

        const resp = await fetch('index.php?action=admin_create_product', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const res = await resp.json();

        if (!res.success) {
          showAdminToast('Lỗi tạo sản phẩm', res.error || 'Không thể thêm sản phẩm.', 'error');
          return;
        }

        const newId = res.masp;
        const newSku = 'KBO-' + newId;

        // Cập nhật danh sách tồn kho trên giao diện ngay
        inventoryData.unshift({
          id: newId,
          name: name,
          sku: newSku,
          category: catText,
          sold: 0,
          stock: stock,
          color: '#589442',
          art: 'royal'
        });

        renderInventory();
        renderOverview();
        closeAddProductModal();
        document.getElementById('formAddProduct').reset();
        showAdminToast('Thêm sản phẩm thành công', `Đã lưu sản phẩm "${name}" vào danh sách kho hàng.`, 'success');
      } catch (err) {
        showAdminToast('Lỗi kết nối', 'Không thể lưu sản phẩm: ' + err.message, 'error');
      } finally {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Lưu sản phẩm';
      }
    }

    // --- VOUCHER MANAGEMENT FUNCTIONS ---
    function handleVoucherTypeChange(val) {
      const isPercent = (val === 'Phần trăm');
      const lbl = document.getElementById('lblVoucherValue');
      if (lbl) lbl.textContent = isPercent ? 'Tỷ lệ giảm (%) *' : 'Số tiền giảm (₫) *';
      const wrap = document.getElementById('wrapVoucherMaxDiscount');
      if (wrap) wrap.style.display = isPercent ? 'block' : 'none';
    }

    function openAddVoucherModal() {
      const form = document.getElementById('formAddVoucher');
      if (form) form.reset();
      handleVoucherTypeChange('Cố định');
      const modal = document.getElementById('addVoucherModalBackdrop');
      if (modal) modal.style.display = 'grid';
    }

    function closeAddVoucherModal() {
      const modal = document.getElementById('addVoucherModalBackdrop');
      if (modal) modal.style.display = 'none';
    }

    async function submitNewVoucher(e) {
      e.preventDefault();
      const code = document.getElementById('newVoucherCode').value.trim().toUpperCase();
      const name = document.getElementById('newVoucherName').value.trim();
      const type = document.getElementById('newVoucherType').value;
      const value = parseFloat(document.getElementById('newVoucherValue').value) || 0;
      const minSpend = parseFloat(document.getElementById('newVoucherMinSpend').value) || 0;
      const maxDiscInp = document.getElementById('newVoucherMaxDiscount');
      const maxDisc = (maxDiscInp && maxDiscInp.value) ? parseFloat(maxDiscInp.value) : null;
      const qty = parseInt(document.getElementById('newVoucherQty').value) || 100;
      const endDate = document.getElementById('newVoucherEndDate').value || null;

      const btn = document.getElementById('btnSaveNewVoucher');
      btn.disabled = true;
      btn.textContent = '⏳ Đang lưu...';

      try {
        const resp = await fetch('index.php?action=admin_voucher_save', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            mavoucher: code,
            tenvoucher: name,
            loai: type,
            giatri: value,
            dontoithieu: minSpend,
            giamtoida: maxDisc,
            soluongphathanh: qty,
            ngayketthuc: endDate
          })
        });
        const res = await resp.json();
        if (res.success) {
          closeAddVoucherModal();
          showAdminToast('Thành công', 'Đã lưu voucher ' + code, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAdminToast('Lỗi', res.message || 'Không thể tạo voucher', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Lưu Voucher';
      }
    }

    async function adminToggleVoucher(code, btn) {
      if (btn) btn.disabled = true;
      try {
        const resp = await fetch('index.php?action=admin_voucher_toggle', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mavoucher: code })
        });
        const res = await resp.json();
        if (res.success) {
          showAdminToast('Cập nhật thành công', res.message, 'success');
          if (btn) {
            const isActive = !!res.kichhoat;
            btn.textContent = isActive ? 'Đang bật' : 'Đã tắt';
            btn.style.borderColor = isActive ? '#2e7d32' : '#999';
            btn.style.background = isActive ? '#e8f5e9' : '#f5f5f5';
            btn.style.color = isActive ? '#2e7d32' : '#666';
            btn.disabled = false;
          }
        } else {
          showAdminToast('Lỗi', res.message || 'Không thể thay đổi', 'error');
          if (btn) btn.disabled = false;
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', err.message, 'error');
        if (btn) btn.disabled = false;
      }
    }

    // --- RETURN ORDER PROCESSING FUNCTION & DETAIL MODAL ---
    let currentViewingReturnId = null;

    function openReturnDetailModal(maYeuCau) {
      currentViewingReturnId = maYeuCau;
      const ret = (returnsData || []).find(r => r.MaYeuCau === maYeuCau);
      if (!ret) return;

      document.getElementById('modalReturnIdTitle').textContent = 'Yêu cầu đổi trả #' + ret.MaYeuCau;
      
      const badge = document.getElementById('modalReturnStatusBadge');
      const st = ret.TrangThaiYeuCau || 'Chờ xử lý';
      badge.textContent = st;
      if (st === 'Đã hoàn tất' || st === 'Đã đồng ý đổi' || st === 'Đã hoàn tiền') {
        badge.style.background = '#2e7d32';
      } else if (st === 'Đã từ chối' || st === 'Từ chối') {
        badge.style.background = '#c94336';
      } else {
        badge.style.background = '#d97706';
      }

      document.getElementById('modalReturnOrderId').textContent = '#' + ret.MaDonHang;
      const totalVal = ret.TongTienDonHang ? parseInt(ret.TongTienDonHang).toLocaleString('vi-VN') + 'đ' : 'Đơn ' + ret.MaDonHang;
      document.getElementById('modalReturnOrderTotal').textContent = totalVal;
      
      const custName = ret.TenNguoiNhan || 'Khách hàng';
      const custPhone = ret.SoDienThoaiNhan || 'Chưa có SĐT';
      document.getElementById('modalReturnCustomer').textContent = `${custName} (${custPhone})`;
      document.getElementById('modalReturnAddress').textContent = ret.DiaChiGiaoHang || 'Chưa cập nhật địa chỉ';
      document.getElementById('modalReturnDate').textContent = ret.NgayYeuCau || 'Gần đây';
      document.getElementById('modalReturnReason').textContent = ret.LyDoDoiTra || 'Không có ghi chú thêm.';

      // Gallery hình ảnh
      const gallery = document.getElementById('modalReturnImagesGallery');
      let rawImg = ret.HinhAnhMinhChung ? ret.HinhAnhMinhChung.trim() : '';
      let imgList = [];

      if (rawImg) {
        if (rawImg.startsWith('[') && rawImg.endsWith(']')) {
          try { imgList = JSON.parse(rawImg); } catch(e) { imgList = [rawImg]; }
        } else if (rawImg.includes(',')) {
          imgList = rawImg.split(',').map(s => s.trim()).filter(Boolean);
        } else {
          imgList = [rawImg];
        }
      }

      if (imgList.length === 0) {
        gallery.innerHTML = `
          <div style="padding: 14px 18px; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 4px; color: #777; font-size: 13px; width: 100%;">
            ℹ️ Khách hàng không đính kèm tệp hình ảnh khi gửi yêu cầu này.
          </div>
        `;
      } else {
        gallery.innerHTML = imgList.map((imgSrc, idx) => {
          return `
            <div style="position: relative; border: 1px solid #dcdcdc; border-radius: 6px; overflow: hidden; width: 140px; cursor: pointer; transition: transform 0.15s; background: #fff;" 
                 onclick="openImageLightbox('${imgSrc}')" title="Bấm để phóng to">
              <img src="${imgSrc}" alt="Ảnh ${idx + 1}" style="width: 100%; height: 110px; object-fit: cover; display: block;" 
                   onerror="this.onerror=null; this.src='https://placehold.co/400x300/f5ebe0/4a3525?text=Ảnh+Minh+Chứng';">
              <div style="text-align: center; font-size: 11px; padding: 4px; background: #fafafa; color: #555; border-top: 1px solid #eee;">
                🔍 Xem ảnh ${idx + 1} (phóng to)
              </div>
            </div>
          `;
        }).join('');
      }

      // Khối ra quyết định
      const decisionSection = document.getElementById('modalReturnDecisionSection');
      const isPending = (st === 'Chờ xử lý');

      if (isPending) {
        decisionSection.innerHTML = `
          <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; margin-top: 6px;">
            <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: bold; color: #1e293b;">Đưa ra quyết định xử lý:</h4>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px;">
              <button type="button" onclick="confirmModalReturn('doi')" 
                      style="padding: 8px 16px; background: #2e7d32; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 13px; cursor: pointer;">
                ✓ Duyệt Đổi hàng
              </button>

              <div style="display: flex; align-items: center; gap: 8px; background: #fff; padding: 4px 10px; border: 1px solid #93c5fd; border-radius: 4px;">
                <button type="button" onclick="confirmModalReturn('hoantien')" 
                        style="padding: 6px 14px; background: #1565c0; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 13px; cursor: pointer;">
                  ✓ Duyệt Hoàn tiền
                </button>
                <label style="font-size: 12.5px; color: #1e3a8a; cursor: pointer; user-select: none; display: flex; align-items: center; gap: 4px;">
                  <input type="checkbox" id="modalReturnRestockCheckbox" checked> Nhập lại kho (bao bì còn nguyên)
                </label>
              </div>
            </div>

            <div style="border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 10px;">
              <div style="font-size: 12.5px; color: #64748b; margin-bottom: 6px;">Hoặc từ chối yêu cầu đổi trả (bắt buộc nhập lý do gửi khách):</div>
              <div style="display: flex; gap: 8px;">
                <input type="text" id="modalReturnRejectReason" placeholder="Nhập lý do từ chối gửi cho khách..." 
                       style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px;">
                <button type="button" onclick="confirmModalReturn('tuchoi')" 
                        style="padding: 8px 14px; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 13px; cursor: pointer;">
                  ✕ Từ chối
                </button>
              </div>
            </div>
          </div>
        `;
      } else {
        decisionSection.innerHTML = `
          <div style="padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; font-size: 13.5px; color: #166534;">
            <strong>Trạng thái xử lý:</strong> ${ret.HuongXuLy || ret.TrangThaiYeuCau}<br>
            <span style="font-size: 12.5px; color: #15803d;">Thời điểm duyệt: ${ret.NgayXuLy || 'Đã ghi nhận'}</span>
          </div>
        `;
      }

      const modal = document.getElementById('returnDetailModalBackdrop');
      if (modal) modal.style.display = 'grid';
    }

    function closeReturnDetailModal() {
      const modal = document.getElementById('returnDetailModalBackdrop');
      if (modal) modal.style.display = 'none';
      currentViewingReturnId = null;
    }

    function openImageLightbox(src) {
      const img = document.getElementById('lightboxImage');
      if (img) img.src = src;
      const lb = document.getElementById('imageLightboxBackdrop');
      if (lb) lb.style.display = 'grid';
    }

    function closeImageLightbox() {
      const lb = document.getElementById('imageLightboxBackdrop');
      if (lb) lb.style.display = 'none';
    }

    function confirmModalReturn(action) {
      if (!currentViewingReturnId) return;
      
      if (action === 'tuchoi') {
        const reasonInput = document.getElementById('modalReturnRejectReason');
        const reason = reasonInput ? reasonInput.value.trim() : '';
        if (!reason) {
          alert('Vui lòng nhập lý do từ chối để thông báo đến khách hàng.');
          if (reasonInput) reasonInput.focus();
          return;
        }
        adminProcessReturn(currentViewingReturnId, 'tuchoi', reason);
      } else if (action === 'hoantien') {
        const cb = document.getElementById('modalReturnRestockCheckbox');
        const nhapKho = cb ? cb.checked : true;
        adminProcessReturn(currentViewingReturnId, 'hoantien', '', nhapKho);
      } else {
        adminProcessReturn(currentViewingReturnId, 'doi', '', false);
      }
    }

    async function adminProcessReturn(maYeuCau, huongXuLy, lyDoArg = null, nhapKhoArg = null) {
      let lyDo = lyDoArg !== null ? lyDoArg : '';
      let nhapKho = nhapKhoArg !== null ? nhapKhoArg : false;

      if (lyDoArg === null && huongXuLy === 'tuchoi') {
        lyDo = prompt('Nhập lý do từ chối yêu cầu đổi trả:');
        if (lyDo === null) return;
        if (!lyDo.trim()) {
          alert('Vui lòng nhập lý do từ chối.');
          return;
        }
      } else if (nhapKhoArg === null && huongXuLy === 'hoantien') {
        if (!confirm(`Xác nhận duyệt hoàn tiền và hủy phần trích quỹ cho yêu cầu ${maYeuCau}?`)) return;
        nhapKho = confirm("Bạn có muốn NHẬP LẠI KHO số lượng sản phẩm này không?\n\n- Nhấn [OK]: Nhập lại kho (nếu bao bì còn nguyên seal, chưa mở).\n- Nhấn [Cancel]: KHÔNG nhập kho (nếu thức ăn bị hỏng/ẩm mốc cần tiêu hủy).");
      } else if (huongXuLy === 'doi' && lyDoArg === null && nhapKhoArg === null) {
        if (!confirm(`Xác nhận duyệt đồng ý đổi hàng cho yêu cầu ${maYeuCau}?`)) return;
      }

      try {
        const resp = await fetch('index.php?action=admin_process_return', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mayeucau: maYeuCau, huongxuly: huongXuLy, lydotuchoi: lyDo, nhaplaikho: nhapKho })
        });
        const res = await resp.json();
        if (res.success) {
          showAdminToast('Thành công', res.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAdminToast('Thất bại', res.message || 'Không thể xử lý', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', err.message, 'error');
      }
    }

    // 6. RENDER BẢNG ĐƠN HÀNG
    function renderOrders() {
      const q = searchQuery.toLowerCase().trim();
      const filtered = q ? ordersData.filter(o => 
        (o.id + ' ' + o.customer + ' ' + o.phone + ' ' + o.items).toLowerCase().includes(q)
      ) : ordersData;

      const tbody = document.getElementById('ordersTableBody');
      if (tbody) {
        tbody.innerHTML = filtered.map(order => {
          const isDone = (order.status === 'Đã giao' || order.status.includes('thành công'));
          const isCancelled = order.status.includes('hủy');
          const isPending = (order.status === 'Chờ xác nhận');
          const statusClass = isDone ? 'done' : (isCancelled ? 'cancelled' : 'processing');

          let quickBtn = '';
          if (order.status === 'Chờ xác nhận') {
            quickBtn = `
              <button type="button" class="ops-row-action" style="background: #2e7d32; color: #ffffff; border: none; font-weight: 700; margin-left: 6px;" onclick="updateOrderStatus('${order.id}', 'Chờ đóng gói')">Duyệt đơn</button>
              <button type="button" class="ops-row-action" style="background: #ffffff; color: #c94336; border: 1px solid #dcdcdc; font-weight: 600; margin-left: 4px;" onclick="updateOrderStatus('${order.id}', 'Đã hủy')">Hủy đơn</button>
            `;
          } else if (order.status === 'Chờ đóng gói') {
            quickBtn = `
              <button type="button" class="ops-row-action" style="background: #0284c7; color: #ffffff; border: none; font-weight: 700; margin-left: 6px;" onclick="updateOrderStatus('${order.id}', 'Đã xuất kho')">Đã xuất kho</button>
              <button type="button" class="ops-row-action" style="background: #ffffff; color: #c94336; border: 1px solid #dcdcdc; font-weight: 600; margin-left: 4px;" onclick="updateOrderStatus('${order.id}', 'Đã hủy')">Hủy đơn</button>
            `;
          } else if (order.status === 'Đã xuất kho') {
            quickBtn = `
              <button type="button" class="ops-row-action" style="background: #d97706; color: #ffffff; border: none; font-weight: 700; margin-left: 6px;" onclick="updateOrderStatus('${order.id}', 'Đang giao')">Bắt đầu giao</button>
            `;
          } else if (order.status === 'Đang giao' || order.status === 'Đang giao hàng') {
            quickBtn = `
              <button type="button" class="ops-row-action" style="background: #1565c0; color: #ffffff; border: none; font-weight: 700; margin-left: 6px;" onclick="updateOrderStatus('${order.id}', 'Giao thành công')">Giao thành công</button>
            `;
          }

          return `
            <tr>
              <td><code>${order.id}</code></td>
              <td>
                <strong>${order.customer}</strong>
                <small>${order.phone}</small>
              </td>
              <td>${order.items}</td>
              <td><strong>${order.total}</strong></td>
              <td class="ops-fund-cell">${order.fund}</td>
              <td><span class="ops-order-status ${statusClass}">${order.status}</span></td>
              <td>
                <div style="display: flex; align-items: center; gap: 4px;">
                  <button type="button" class="ops-row-action" onclick="viewOrderDetail('${order.id}')">Chi tiết</button>
                  ${quickBtn}
                </div>
              </td>
            </tr>
          `;
        }).join('');
      }

      const ordersDesc = document.getElementById('ordersCountDesc');
      if (ordersDesc) ordersDesc.textContent = `${filtered.length} đơn trong kỳ đã chọn`;
    }

    let currentViewingOrderId = null;

    function openOrderDetailModal(orderId) {
      currentViewingOrderId = orderId;
      const order = ordersData.find(o => o.id === orderId);
      if (!order) return;

      document.getElementById('modalOrderId').textContent = 'Đơn hàng #' + order.id;
      document.getElementById('modalOrderDate').textContent = 'Ngày đặt hàng: ' + (order.date || 'Gần đây');
      const printBtn = document.getElementById('modalOrderPrintBtn');
      if (printBtn) printBtn.href = 'index.php?action=admin_print_order&id=' + encodeURIComponent(order.id);

      const badge = document.getElementById('modalOrderStatusBadge');
      badge.textContent = order.status;
      if (order.status.includes('thành công') || order.status === 'Đã giao') {
        badge.style.background = '#2e7d32';
        badge.style.color = '#ffffff';
      } else if (order.status.includes('hủy')) {
        badge.style.background = '#c94336';
        badge.style.color = '#ffffff';
      } else {
        badge.style.background = '#d97706';
        badge.style.color = '#ffffff';
      }

      document.getElementById('modalCustomerName').textContent = order.customer;
      document.getElementById('modalCustomerPhone').textContent = order.phone;
      document.getElementById('modalCustomerAddress').textContent = order.address || 'Chưa cập nhật';

      // Danh sách sản phẩm
      const itemsContainer = document.getElementById('modalOrderItemsList');
      const items = (Array.isArray(order.itemsList) && order.itemsList.length > 0) 
        ? order.itemsList 
        : [{ name: order.items, variant: 'Tiêu chuẩn', qty: 1, price: order.total }];

      itemsContainer.innerHTML = items.map(item => `
        <div style="padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0ede8; background: #ffffff;">
          <div>
            <div style="font-size: 14px; font-weight: 700; color: #2c1a0e;">${item.name}</div>
            <div style="font-size: 12.5px; color: #887e76; margin-top: 2px;">Quy cách: ${item.variant || 'Tiêu chuẩn'} · SL: <strong>${item.qty || 1}</strong></div>
          </div>
          <strong style="font-size: 14px; color: #2c1a0e;">${typeof item.price === 'number' ? item.price.toLocaleString('vi-VN') + 'đ' : item.price}</strong>
        </div>
      `).join('');

      document.getElementById('modalSubtotal').textContent = order.subtotal || order.total;
      document.getElementById('modalShipping').textContent = (order.shipping && order.shipping !== '0đ') ? order.shipping : 'Miễn phí';
      document.getElementById('modalDiscount').textContent = order.discount || '0đ';
      document.getElementById('modalTotal').textContent = order.total;
      document.getElementById('modalFund').textContent = order.fund;
      document.getElementById('modalShelterDesc').textContent = 'Trực tiếp phân bổ đến: ' + (order.shelter || 'Trạm cứu hộ liên kết');

      // Bước kế tiếp hợp lệ
      const actionsContainer = document.getElementById('modalOrderNextActions');
      if (actionsContainer) {
        let btns = '';
        if (order.status === 'Chờ xác nhận') {
          btns = `
            <button type="button" onclick="updateCurrentOrderModalStatus('Chờ đóng gói')" style="padding: 6px 12px; background: #2e7d32; color: #fff; border: none; border-radius: 3px; font-size: 12.5px; font-weight: bold; cursor: pointer;">Duyệt đơn (Chờ đóng gói)</button>
            <button type="button" onclick="updateCurrentOrderModalStatus('Đã hủy')" style="padding: 6px 10px; background: #fff; color: #c94336; border: 1px solid #ccc; border-radius: 3px; font-size: 12.5px; cursor: pointer;">Hủy đơn</button>
          `;
        } else if (order.status === 'Chờ đóng gói') {
          btns = `
            <button type="button" onclick="updateCurrentOrderModalStatus('Đã xuất kho')" style="padding: 6px 12px; background: #0284c7; color: #fff; border: none; border-radius: 3px; font-size: 12.5px; font-weight: bold; cursor: pointer;">Đã xuất kho</button>
            <button type="button" onclick="updateCurrentOrderModalStatus('Đã hủy')" style="padding: 6px 10px; background: #fff; color: #c94336; border: 1px solid #ccc; border-radius: 3px; font-size: 12.5px; cursor: pointer;">Hủy đơn</button>
          `;
        } else if (order.status === 'Đã xuất kho') {
          btns = `
            <button type="button" onclick="updateCurrentOrderModalStatus('Đang giao')" style="padding: 6px 12px; background: #d97706; color: #fff; border: none; border-radius: 3px; font-size: 12.5px; font-weight: bold; cursor: pointer;">Bắt đầu giao</button>
          `;
        } else if (order.status === 'Đang giao' || order.status === 'Đang giao hàng') {
          btns = `
            <button type="button" onclick="updateCurrentOrderModalStatus('Giao thành công')" style="padding: 6px 12px; background: #1565c0; color: #fff; border: none; border-radius: 3px; font-size: 12.5px; font-weight: bold; cursor: pointer;">Giao thành công</button>
          `;
        } else {
          btns = `<span style="font-size: 12px; color: #666; font-style: italic;">Đơn hàng đã kết thúc chu trình (${order.status})</span>`;
        }
        actionsContainer.innerHTML = btns;
      }

      const modal = document.getElementById('orderDetailModalBackdrop');
      if (modal) modal.style.display = 'grid';
    }

    function closeOrderDetailModal() {
      const modal = document.getElementById('orderDetailModalBackdrop');
      if (modal) modal.style.display = 'none';
      currentViewingOrderId = null;
    }

    async function updateOrderStatus(orderId, newStatus) {
      let maVanDon = null;
      if (newStatus === 'Đã xuất kho') {
        const inputVD = prompt('Nhập mã vận đơn (Tracking Code) đơn vị vận chuyển (để trống nếu sinh tự động):');
        if (inputVD !== null && inputVD.trim()) {
          maVanDon = inputVD.trim();
        }
      }
      try {
        const resp = await fetch('index.php?action=admin_update_order_status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ madonhang: orderId, trangthai: newStatus, mavandon: maVanDon })
        });
        const res = await resp.json();
        if (res.success) {
          const order = ordersData.find(o => o.id === orderId);
          if (order) {
            order.status = (newStatus === 'Giao thành công' ? 'Giao hàng thành công' : (newStatus === 'Đang giao' ? 'Đang giao hàng' : newStatus));
          }
          renderOrders();
          renderOverview();
          showAdminToast('Cập nhật thành công', `Đơn hàng #${orderId} đã chuyển sang trạng thái: "${newStatus}".`, 'success');
        } else {
          showAdminToast('Bị từ chối', res.message || 'Không thể cập nhật trạng thái đơn hàng.', 'error');
        }
      } catch (e) {
        showAdminToast('Lỗi kết nối', e.message, 'error');
      }
    }

    async function adminToggleUserStatus(matk, currentStatus) {
      const nextStatus = (currentStatus === 'Bị khóa') ? 'Hoạt động' : 'Bị khóa';
      if (!confirm(`Xác nhận chuyển trạng thái tài khoản ${matk} sang "${nextStatus}"?`)) return;
      try {
        const resp = await fetch('index.php?action=admin_toggle_user_status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ matk, trangthai: nextStatus })
        });
        const res = await resp.json();
        if (res.success) {
          showAdminToast('Thành công', res.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAdminToast('Thất bại', res.message || 'Không thể cập nhật trạng thái tài khoản.', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', err.message, 'error');
      }
    }

    async function adminConfirmPayment(id) {
      if (!confirm(`Xác nhận đã nhận đủ tiền chuyển khoản cho giao dịch ${id}?`)) return;
      try {
        const resp = await fetch('index.php?action=admin_confirm_payment', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const res = await resp.json();
        if (res.success) {
          showAdminToast('Thành công', res.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAdminToast('Thất bại', res.message || 'Không thể xác nhận thanh toán.', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi kết nối', err.message, 'error');
      }
    }

    async function updateCurrentOrderModalStatus(newStatus) {
      if (!currentViewingOrderId) return;
      await updateOrderStatus(currentViewingOrderId, newStatus);
      openOrderDetailModal(currentViewingOrderId);
    }

    function viewOrderDetail(orderId) {
      openOrderDetailModal(orderId);
    }

    function exportOrdersCSV() {
      showAdminToast('Xuất dữ liệu', 'Đang kết xuất và tải file CSV danh sách đơn hàng...');
    }

    // 7. RENDER TAB OVERVIEW & BIỂU ĐỒ DOANH THU THỰC TẾ
    function renderOverview() {
      renderOverviewKPIs();
      renderRevenueChart(selectedRange);

      const feed = document.getElementById('overviewRecentOrders');
      if (feed) {
        feed.innerHTML = ordersData.slice(0, 5).map(order => {
          const initial = order.customer ? (order.customer.trim().split(' ').slice(-1)[0][0] || 'K') : 'K';
          return `
            <div>
              <span>${initial}</span>
              <p>
                <strong>${order.customer}</strong>
                <small>${order.id} · ${order.items}</small>
                <em>${order.status}</em>
              </p>
              <b>${order.total}</b>
            </div>
          `;
        }).join('');
      }
    }

    function renderOverviewKPIs() {
      let deliveredRev = 0;
      let deliveredCount = 0;
      let newCount = 0;
      let totalOrders = ordersData.length;

      ordersData.forEach(o => {
        const st = o.status;
        const tot = parseInt(String(o.total).replace(/\D/g, '')) || 0;
        if (st === 'Giao thành công' || st === 'Giao hàng thành công' || st === 'Đã giao') {
          deliveredRev += tot;
          deliveredCount++;
        } else if (st === 'Chờ xác nhận') {
          newCount++;
        }
      });

      const revEl = document.getElementById('kpiRevenueVal');
      if (revEl) revEl.textContent = deliveredRev.toLocaleString('vi-VN') + 'đ';
      const revSub = document.getElementById('kpiRevenueSub');
      if (revSub) revSub.textContent = `${deliveredCount} đơn hoàn tất giao`;

      const ordEl = document.getElementById('kpiOrdersVal');
      if (ordEl) ordEl.textContent = totalOrders;
      const ordSub = document.getElementById('kpiOrdersSub');
      if (ordSub) ordSub.textContent = `${newCount} mới · ${deliveredCount} đã giao`;
    }

    function renderRevenueChart(days = 30) {
      const chartContainer = document.querySelector('.ops-chart');
      if (!chartContainer) return;

      const agg = {};
      ordersData.forEach(o => {
        let dStr = o.date;
        if (!dStr) return;
        const parts = dStr.split('/');
        if (parts.length >= 2) {
          const key = `${parts[0].padStart(2, '0')}/${parts[1].padStart(2, '0')}`;
          if (!agg[key]) {
            const y = parts[2] ? parseInt(parts[2]) : 2026;
            agg[key] = { 
              date: key, 
              revenue: 0, 
              orders: 0, 
              ts: new Date(y, parseInt(parts[1]) - 1, parseInt(parts[0])).getTime() 
            };
          }
          agg[key].orders++;
          const st = o.status;
          if (st === 'Giao thành công' || st === 'Giao hàng thành công' || st === 'Đã giao') {
            const tot = parseInt(String(o.total).replace(/\D/g, '')) || 0;
            agg[key].revenue += tot;
          }
        }
      });

      let points = Object.values(agg).sort((a, b) => a.ts - b.ts);

      if (points.length === 0) {
        const today = new Date();
        for (let i = 6; i >= 0; i--) {
          const d = new Date(today.getTime() - i * 86400000);
          const dStr = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`;
          points.push({ date: dStr, revenue: 0, orders: 0 });
        }
      }

      // Giữ tối đa 8 cột để biểu đồ thoáng đẹp
      if (points.length > 8) {
        points = points.slice(-8);
      }

      // Tìm max revenue & max orders
      let maxRev = Math.max(...points.map(p => p.revenue), 500000);
      if (maxRev <= 500000) maxRev = 500000;
      else if (maxRev <= 1000000) maxRev = 1000000;
      else if (maxRev <= 2000000) maxRev = 2000000;
      else if (maxRev <= 5000000) maxRev = 5000000;
      else maxRev = Math.ceil(maxRev / 5000000) * 5000000;

      let maxOrders = Math.max(...points.map(p => p.orders), 3);
      maxOrders = Math.ceil(maxOrders / 3) * 3;

      // Cập nhật trục Y
      const yAxis = chartContainer.querySelector('.ops-y-axis');
      if (yAxis) {
        const step = maxRev / 3;
        const formatY = (v) => {
          if (v === 0) return '0';
          if (v >= 1000000) return (v / 1000000).toFixed(v % 1000000 === 0 ? 0 : 1) + 'tr';
          return (v / 1000).toFixed(0) + 'k';
        };
        yAxis.innerHTML = `
          <span>${formatY(maxRev)}</span>
          <span>${formatY(step * 2)}</span>
          <span>${formatY(step)}</span>
          <span>0</span>
        `;
      }

      // Kích thước SVG
      const W = 760;
      const baseline = 221;
      const maxBarH = 190;
      const maxLineH = 150;

      const N = points.length;
      const stepX = W / (N + 1);

      let rectsSvg = '';
      let linePoints = [];
      let circlesSvg = '';
      let xAxisHtml = '';

      points.forEach((p, idx) => {
        const cx = stepX * (idx + 1);
        const barW = Math.min(38, Math.max(26, Math.floor(stepX * 0.42)));
        const x = cx - (barW / 2);
        const barH = Math.max(p.revenue > 0 ? 8 : 2, Math.round((p.revenue / maxRev) * maxBarH));
        const y = baseline - barH;

        const lineY = baseline - Math.max(4, Math.round((p.orders / maxOrders) * maxLineH));
        linePoints.push(`${Math.round(cx)} ${Math.round(lineY)}`);

        rectsSvg += `
          <rect x="${Math.round(x)}" y="${y}" width="${barW}" height="${barH}" rx="3" style="cursor: pointer;">
            <title>Ngày ${p.date}: Doanh thu ${p.revenue.toLocaleString('vi-VN')}đ (${p.orders} đơn hàng)</title>
          </rect>
        `;

        circlesSvg += `
          <circle cx="${Math.round(cx)}" cy="${Math.round(lineY)}" r="4" style="cursor: pointer;">
            <title>Ngày ${p.date}: ${p.orders} đơn hàng</title>
          </circle>
        `;

        xAxisHtml += `<span>${p.date}</span>`;
      });

      const svg = chartContainer.querySelector('svg');
      if (svg) {
        svg.innerHTML = `
          <g class="grid-lines">
            <path d="M0 20H760M0 87H760M0 154H760M0 221H760" />
          </g>
          ${rectsSvg}
          <path class="order-line" d="M ${linePoints.join(' L ')}" />
          ${circlesSvg}
        `;
      }

      const xAxis = chartContainer.querySelector('.ops-x-axis');
      if (xAxis) {
        xAxis.innerHTML = xAxisHtml;
      }
    }

    // 8. TÌM KIẾM TOÀN CỤC & BỘ LỌC
    function handleGlobalSearch(val) {
      searchQuery = val;
      if (currentTab === 'inventory') renderInventory();
      if (currentTab === 'orders') renderOrders();
    }

    function handleTimeRangeChange(val) {
      selectedRange = val;
      const chartSub = document.getElementById('chartSubtext');
      if (chartSub) chartSub.textContent = `${val} ngày gần nhất`;
      for (let i = 1; i <= 4; i++) {
        const rEl = document.getElementById('reportRange' + i);
        if (rEl) rEl.textContent = `Dữ liệu ${val} ngày gần nhất`;
      }
      renderRevenueChart(val);
    }

    function toggleNotifications() {
      const dd = document.getElementById('notifDropdown');
      if (dd) dd.style.display = (dd.style.display === 'none' || !dd.style.display) ? 'block' : 'none';
    }

    <?php
      $adminLoginSuccess = $_SESSION['login_success'] ?? null;
      unset($_SESSION['login_success']);
    ?>

    // KHỞI CHẠY LẦN ĐẦU
    document.addEventListener('DOMContentLoaded', () => {
      renderInventory();
      renderOrders();
      renderOverview();

      const savedTab = sessionStorage.getItem('kibo_active_admin_tab');
      if (savedTab && ['overview', 'orders', 'inventory', 'fund', 'vouchers', 'reports', 'returns'].includes(savedTab)) {
        switchAdminTab(savedTab);
      }

      const adminFlash = <?= json_encode($adminLoginSuccess) ?>;
      if (adminFlash) {
        showAdminToast('Đăng nhập thành công', adminFlash, 'success');
      }

      // Đóng dropdown thông báo khi click bên ngoài
      document.addEventListener('click', (e) => {
        const wrap = document.querySelector('.ops-notification-wrap');
        const dd = document.getElementById('notifDropdown');
        if (wrap && dd && !wrap.contains(e.target)) {
          dd.style.display = 'none';
        }
      });
    });

    // --- CSV EXPORT UTILITIES (BÁO CÁO KINH DOANH) ---
    function downloadCSV(filename, rows) {
      const processRow = function (row) {
        let finalVal = '';
        for (let j = 0; j < row.length; j++) {
          let innerValue = row[j] === null || row[j] === undefined ? '' : row[j].toString();
          let result = innerValue.replace(/"/g, '""');
          if (result.search(/("|,|\n)/g) >= 0)
            result = '"' + result + '"';
          if (j > 0)
            finalVal += ',';
          finalVal += result;
        }
        return finalVal + '\r\n';
      };

      let csvFile = '\uFEFF'; // UTF-8 BOM
      for (let i = 0; i < rows.length; i++) {
        csvFile += processRow(rows[i]);
      }

      const blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement("a");
      if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    }

    function exportRevenueReportCSV() {
      const rows = [
        ['MÃ ĐƠN HÀNG', 'NGÀY ĐẶT', 'KHÁCH HÀNG', 'SỐ ĐIỆN THOẠI', 'TỔNG TIỀN (VNĐ)', 'QUỸ CỨU TRỢ 5% (VNĐ)', 'TRẠNG THÁI']
      ];
      ordersData.forEach(o => {
        rows.push([
          o.id,
          o.date,
          o.customer,
          o.phone,
          o.total ? o.total.replace(/[^\d]/g, '') : '0',
          o.fund ? o.fund.replace(/[^\d]/g, '') : '0',
          o.status
        ]);
      });
      downloadCSV(`KIBO_BaoCao_DoanhThu_${new Date().toISOString().slice(0,10)}.csv`, rows);
      showAdminToast('Tải thành công', 'Đã xuất file báo cáo doanh thu CSV thành công.', 'success');
    }

    function exportOrdersStatusCSV() {
      const rows = [
        ['MÃ ĐƠN HÀNG', 'NGÀY ĐẶT', 'KHÁCH HÀNG', 'SẢN PHẨM', 'ĐỊA CHỈ GIAO HÀNG', 'TỔNG TIỀN', 'TRẠNG THÁI']
      ];
      ordersData.forEach(o => {
        rows.push([
          o.id,
          o.date,
          o.customer,
          o.items,
          o.address,
          o.total,
          o.status
        ]);
      });
      downloadCSV(`KIBO_BaoCao_DonHang_${new Date().toISOString().slice(0,10)}.csv`, rows);
      showAdminToast('Tải thành công', 'Đã xuất danh sách đơn hàng CSV thành công.', 'success');
    }

    function exportInventoryReportCSV() {
      const rows = [
        ['MÃ BIẾN THỂ / SKU', 'TÊN SẢN PHẨM', 'DANH MỤC', 'SỐ LƯỢNG TỒN', 'ĐÃ BÁN', 'TÌNH TRẠNG KHO']
      ];
      inventoryData.forEach(item => {
        const stock = parseInt(item.stock || item.soluongton || 0);
        rows.push([
          item.sku || item.id,
          item.name,
          item.category,
          stock,
          item.sold || 0,
          stock <= 5 ? 'Cảnh báo sắp hết (<= 5)' : 'Đủ hàng an toàn'
        ]);
      });
      downloadCSV(`KIBO_BaoCao_TonKho_${new Date().toISOString().slice(0,10)}.csv`, rows);
      showAdminToast('Tải thành công', 'Đã xuất danh sách tồn kho CSV thành công.', 'success');
    }

    function exportFundReportCSV() {
      const rows = [
        ['MÃ ĐƠN HÀNG', 'NGÀY ĐẶT', 'TỔNG ĐƠN HÀNG (VNĐ)', 'KHOẢN TRÍCH QUỸ 5% (VNĐ)', 'TRẠM CỨU HỘ THỤ HƯỞNG', 'TRẠNG THÁI ĐƠN']
      ];
      ordersData.forEach(o => {
        rows.push([
          o.id,
          o.date,
          o.total ? o.total.replace(/[^\d]/g, '') : '0',
          o.fund ? o.fund.replace(/[^\d]/g, '') : '0',
          o.shelter,
          o.status
        ]);
      });
      downloadCSV(`KIBO_DoiSoat_QuyCuuTro_${new Date().toISOString().slice(0,10)}.csv`, rows);
      showAdminToast('Tải thành công', 'Đã xuất file đối soát Quỹ cứu trợ 5% CSV thành công.', 'success');
    }
  </script>

  <!-- MODAL THÊM DANH MỤC MỚI (DẠNG BASIC) -->
  <div id="addCategoryModalBackdrop" onclick="if(event.target === this) closeAddCategoryModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 9999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(440px, 94vw); border-radius: 4px; border: 1px solid #dcdcdc; box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; font-family: Arial, sans-serif;">
      <!-- Header -->
      <div style="background: #f7f7f7; padding: 12px 16px; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 15px; font-weight: bold; color: #333;">Thêm danh mục mới</h3>
        <button type="button" onclick="closeAddCategoryModal()" style="background: none; border: none; font-size: 18px; color: #666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
      </div>

      <!-- Form Body -->
      <form id="formAddCategory" onsubmit="submitNewCategory(event)">
        <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Tên danh mục mới *</label>
            <input type="text" id="newCategoryName" required placeholder="Nhập tên danh mục..." 
                   style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
          </div>

          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Mô tả danh mục</label>
            <textarea id="newCategoryDesc" rows="3" placeholder="Nhập mô tả ngắn gọn..." 
                      style="width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none; resize: vertical;"></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div style="padding: 10px 16px; background: #f7f7f7; border-top: 1px solid #ddd; display: flex; justify-content: flex-end; gap: 8px;">
          <button type="button" onclick="closeAddCategoryModal()" style="padding: 6px 14px; border: 1px solid #ccc; background: #fff; color: #333; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
          <button type="submit" id="btnSaveNewCategory" style="padding: 6px 16px; border: none; background: #2c1a0e; color: #fff; border-radius: 4px; font-size: 13.5px; font-weight: bold; cursor: pointer;">Lưu danh mục</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL THÊM SẢN PHẨM MỚI (DẠNG BASIC) -->
  <div id="addProductModalBackdrop" onclick="if(event.target === this) closeAddProductModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(600px, 95vw); border-radius: 4px; border: 1px solid #dcdcdc; box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; font-family: Arial, sans-serif;">
      <!-- Header -->
      <div style="background: #f7f7f7; padding: 12px 16px; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 15px; font-weight: bold; color: #333;">Thêm sản phẩm mới</h3>
        <button type="button" onclick="closeAddProductModal()" style="background: none; border: none; font-size: 18px; color: #666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
      </div>

      <form id="formAddProduct" onsubmit="submitNewProduct(event)">
        <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Tên sản phẩm *</label>
            <input type="text" id="newProdName" required placeholder="Nhập tên sản phẩm..." 
                   style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <label style="font-weight: bold; font-size: 13px; color: #333; margin: 0;">Danh mục *</label>
                <button type="button" onclick="openAddCategoryModal()" style="background: none; border: none; color: #2e7d32; font-size: 12.5px; cursor: pointer; text-decoration: underline; padding: 0;">+ Thêm danh mục</button>
              </div>
              <select id="newProdCategory" style="width: 100%; height: 36px; padding: 0 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
                <?php 
                  $allCats = SanPhamModel::getDanhSachDanhMuc();
                  foreach ($allCats as $cat): 
                ?>
                  <option value="<?= htmlspecialchars($cat['madanhmuc']) ?>"><?= htmlspecialchars($cat['tendanhmuc']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Thương hiệu</label>
              <input type="text" id="newProdBrand" placeholder="Thương hiệu sản phẩm..." 
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Biến thể</label>
              <input type="text" id="newProdVariant" placeholder="Quy cách / trọng lượng..." 
                     style="width: 100%; height: 36px; padding: 0 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Giá bán (₫) *</label>
              <input type="number" id="newProdPrice" required placeholder="Giá bán..." min="1000" step="1000" 
                     style="width: 100%; height: 36px; padding: 0 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Tồn kho</label>
              <input type="number" id="newProdStock" required placeholder="Số lượng..." min="0" 
                     style="width: 100%; height: 36px; padding: 0 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
          </div>

          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">
              Hình ảnh sản phẩm (Link ảnh hoặc đường dẫn nội bộ)
            </label>
            <div style="display: flex; gap: 8px; align-items: center;">
              <input type="text" id="newProdImage" placeholder="Đường dẫn hoặc link ảnh..." 
                     oninput="updateImagePreview(this.value)"
                     style="flex: 1; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; background: #fff; box-sizing: border-box; outline: none;">
              <div id="imgPreviewWrap" style="width: 36px; height: 36px; border-radius: 4px; border: 1px solid #ccc; overflow: hidden; background: #f8f8f8; display: grid; place-items: center; flex-shrink: 0;">
                <span id="imgPlaceholder" style="font-size: 16px; color: #999;">🖼</span>
                <img id="imgPreview" src="" alt="Preview" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain;">
              </div>
            </div>
          </div>

          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Mô tả sản phẩm</label>
            <textarea id="newProdDesc" rows="3" placeholder="Nhập mô tả sản phẩm..." 
                      style="width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; resize: vertical; box-sizing: border-box; outline: none;"></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div style="padding: 10px 16px; background: #f7f7f7; border-top: 1px solid #ddd; display: flex; justify-content: flex-end; gap: 8px;">
          <button type="button" onclick="closeAddProductModal()" style="padding: 6px 14px; border: 1px solid #ccc; background: #fff; color: #333; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
          <button type="submit" id="btnSaveNewProduct" style="padding: 6px 16px; border: none; background: #2c1a0e; color: #ffffff; border-radius: 4px; font-weight: bold; font-size: 13.5px; cursor: pointer;">
            Lưu sản phẩm
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CHỈNH SỬA SẢN PHẨM -->
  <div id="modalEditProductBackdrop" onclick="if(event.target === this) closeEditProductModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 9999999; place-items: center; padding: 16px;">
    <div style="background: #ffffff; width: min(480px, 95vw); border-radius: 4px; border: 1px solid #dcdcdc; box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; font-family: Arial, sans-serif;">
      <div style="background: #2c1a0e; color: #ffffff; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;">
        <h3 id="modalEditProductTitle" style="margin: 0; font-size: 15px; font-weight: bold; color: #fff;">Chỉnh sửa sản phẩm</h3>
        <button type="button" onclick="closeEditProductModal()" style="background: none; border: none; font-size: 18px; color: #fff; cursor: pointer;">✕</button>
      </div>
      <form id="formEditProduct" onsubmit="handleEditProductSubmit(event)" style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
        <input type="hidden" id="editProductMasp">
        <div>
          <label style="display: block; font-size: 12.5px; font-weight: bold; margin-bottom: 4px; color: #333;">Tên sản phẩm *</label>
          <input type="text" id="editProductTensp" required style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </div>
        <div>
          <label style="display: block; font-size: 12.5px; font-weight: bold; margin-bottom: 4px; color: #333;">Giá bán (VNĐ) *</label>
          <input type="number" id="editProductGiaban" required min="1000" step="1000" style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </div>
        <div>
          <label style="display: block; font-size: 12.5px; font-weight: bold; margin-bottom: 4px; color: #333;">Số lượng tồn kho</label>
          <input type="number" id="editProductSoluongton" min="0" style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
          <button type="button" onclick="closeEditProductModal()" style="padding: 6px 14px; background: #eee; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">Hủy</button>
          <button type="submit" id="btnSaveEditProduct" style="padding: 6px 18px; background: #2c1a0e; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CHI TIẾT ĐƠN HÀNG (DẠNG BASIC) -->
  <div id="orderDetailModalBackdrop" onclick="if(event.target === this) closeOrderDetailModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 9999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(560px, 95vw); border-radius: 4px; border: 1px solid #dcdcdc; box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; font-family: Arial, sans-serif;">
      <!-- Header -->
      <div style="background: #f7f7f7; padding: 12px 16px; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <h3 id="modalOrderId" style="margin: 0; font-size: 15px; font-weight: bold; color: #333;">Đơn hàng #KB-0000</h3>
          <span id="modalOrderStatusBadge" style="display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #d97706; color: #ffffff;">Chờ xác nhận</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <a id="modalOrderPrintBtn" href="#" target="_blank" style="padding: 4px 10px; font-size: 12px; background: #2e7d32; color: #fff; border-radius: 3px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 4px;">🖨️ In phiếu</a>
          <button type="button" onclick="closeOrderDetailModal()" style="background: none; border: none; font-size: 18px; color: #666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
        </div>
      </div>

      <!-- Body -->
      <div style="padding: 16px; display: flex; flex-direction: column; gap: 14px; max-height: 70vh; overflow-y: auto;">
        <!-- Khối chuyển trạng thái đơn (Dành cho Admin - Đúng thứ tự máy trạng thái) -->
        <div style="background: #fdfdfd; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
          <span style="font-size: 13px; font-weight: bold; color: #333;">Bước kế tiếp hợp lệ:</span>
          <div id="modalOrderNextActions" style="display: flex; gap: 6px; flex-wrap: wrap;">
            <!-- Render tự động theo quy trình 5 bước -->
          </div>
        </div>

        <!-- Khối người nhận -->
        <div style="border: 1px solid #e0e0e0; border-radius: 4px; padding: 12px; font-size: 13.5px; line-height: 1.5;">
          <div style="font-weight: bold; color: #333; margin-bottom: 4px;">Thông tin khách hàng &amp; Giao nhận:</div>
          <div>Người nhận: <strong id="modalCustomerName">--</strong> (<span id="modalCustomerPhone">--</span>)</div>
          <div>Địa chỉ: <span id="modalCustomerAddress">--</span></div>
          <div id="modalOrderDate" style="color: #666; font-size: 12.5px; margin-top: 2px;">Ngày đặt: --/--/----</div>
        </div>

        <!-- Khối danh sách sản phẩm -->
        <div>
          <div style="font-weight: bold; font-size: 13px; color: #333; margin-bottom: 6px;">Sản phẩm đặt mua:</div>
          <div id="modalOrderItemsList" style="border: 1px solid #e0e0e0; border-radius: 4px; overflow: hidden;">
            <!-- Render items qua JS -->
          </div>
        </div>

        <!-- Khối thanh toán -->
        <div style="border: 1px solid #e0e0e0; border-radius: 4px; padding: 12px; display: flex; flex-direction: column; gap: 6px; font-size: 13.5px;">
          <div style="display: flex; justify-content: space-between; color: #555;">
            <span>Tiền hàng:</span>
            <strong id="modalSubtotal" style="color: #333;">--</strong>
          </div>
          <div style="display: flex; justify-content: space-between; color: #555;">
            <span>Phí vận chuyển:</span>
            <span id="modalShipping" style="color: #2e7d32; font-weight: bold;">Miễn phí</span>
          </div>
          <div style="display: flex; justify-content: space-between; color: #555;">
            <span>Giảm giá voucher:</span>
            <span id="modalDiscount">0đ</span>
          </div>
          <div style="display: flex; justify-content: space-between; padding-top: 6px; border-top: 1px solid #eee; font-size: 15px; font-weight: bold; color: #333;">
            <span>Tổng thanh toán:</span>
            <span id="modalTotal" style="color: #c94336;">--</span>
          </div>
        </div>

        <!-- Quỹ Cứu Trợ 5% -->
        <div style="background: #f5fbf5; border: 1px solid #c8e6c9; border-radius: 4px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: #2e7d32;">
          <div>
            <strong>Trích Quỹ Cứu Trợ 5%:</strong>
            <div id="modalShelterDesc" style="font-size: 12px; color: #555; margin-top: 2px;">Trạm cứu hộ liên kết</div>
          </div>
          <strong id="modalFund" style="font-size: 14.5px;">--</strong>
        </div>
      </div>

      <!-- Footer -->
      <div style="padding: 10px 16px; background: #f7f7f7; border-top: 1px solid #ddd; display: flex; justify-content: flex-end;">
        <button type="button" onclick="closeOrderDetailModal()" style="padding: 6px 16px; border: 1px solid #ccc; background: #fff; color: #333; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Đóng</button>
      </div>
    </div>
  </div>

  <!-- MODAL THÊM VOUCHER MỚI -->
  <div id="addVoucherModalBackdrop" onclick="if(event.target === this) closeAddVoucherModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(520px, 95vw); border-radius: 4px; border: 1px solid #dcdcdc; box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; font-family: Arial, sans-serif;">
      <div style="background: #f7f7f7; padding: 12px 16px; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 15px; font-weight: bold; color: #333;">Thêm mã giảm giá mới</h3>
        <button type="button" onclick="closeAddVoucherModal()" style="background: none; border: none; font-size: 18px; color: #666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
      </div>
      <form id="formAddVoucher" onsubmit="submitNewVoucher(event)">
        <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Mã Voucher (Code) *</label>
              <input type="text" id="newVoucherCode" required placeholder="VD: TET2026, FREESHIP"
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; font-weight: bold; background: #fff; box-sizing: border-box; outline: none; text-transform: uppercase;">
            </div>
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Tên ưu đãi</label>
              <input type="text" id="newVoucherName" placeholder="VD: Giảm 20k đơn từ 200k" 
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Loại giảm giá *</label>
              <select id="newVoucherType" onchange="handleVoucherTypeChange(this.value)" style="width: 100%; height: 36px; padding: 0 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
                <option value="Cố định">Số tiền cố định (VNĐ)</option>
                <option value="Phần trăm">Phần trăm (%)</option>
              </select>
            </div>
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;" id="lblVoucherValue">Số tiền giảm (₫) *</label>
              <input type="number" id="newVoucherValue" required min="1" step="1" placeholder="20000"
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Đơn tối thiểu (₫)</label>
              <input type="number" id="newVoucherMinSpend" min="0" step="1000" value="0"
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
            <div id="wrapVoucherMaxDiscount" style="display: none;">
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Giảm tối đa (₫)</label>
              <input type="number" id="newVoucherMaxDiscount" min="0" step="1000" placeholder="Để trống nếu không giới hạn"
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Số lượng phát hành *</label>
              <input type="number" id="newVoucherQty" required min="1" step="1" value="100"
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Ngày kết thúc</label>
              <input type="date" id="newVoucherEndDate"
                     style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box; outline: none;">
            </div>
          </div>
        </div>
        <div style="padding: 10px 16px; background: #f7f7f7; border-top: 1px solid #ddd; display: flex; justify-content: flex-end; gap: 8px;">
          <button type="button" onclick="closeAddVoucherModal()" style="padding: 6px 14px; border: 1px solid #ccc; background: #fff; color: #333; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
          <button type="submit" id="btnSaveNewVoucher" style="padding: 6px 16px; border: none; background: #2c1a0e; color: #ffffff; border-radius: 4px; font-weight: bold; font-size: 13.5px; cursor: pointer;">
            Lưu Voucher
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL LẬP CHỨNG TỪ GIẢI NGÂN QUỸ -->
  <div id="disbursementModalBackdrop" onclick="if(event.target === this) closeDisbursementModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(520px, 95vw); border-radius: 6px; border: 1px solid #dcdcdc; box-shadow: 0 4px 20px rgba(0,0,0,0.18); overflow: hidden; font-family: Arial, sans-serif;">
      <div style="background: #f7f7f7; padding: 14px 18px; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: bold; color: #1565c0;">Lập chứng từ giải ngân Quỹ Cứu Trợ</h3>
        <button type="button" onclick="closeDisbursementModal()" style="background: none; border: none; font-size: 18px; color: #666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
      </div>
      <form id="formDisbursement" onsubmit="adminSubmitDisbursement(event)">
        <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px;">
          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Trạm cứu hộ đối tác *</label>
            <select id="disburseStation" required style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; background: #fff; box-sizing: border-box;">
              <option value="">-- Chọn trạm nhận giải ngân --</option>
              <?php foreach ($shelters as $s): ?>
                <option value="<?= htmlspecialchars($s['MaTram']) ?>"><?= htmlspecialchars($s['TenTram']) ?> (<?= htmlspecialchars($s['KhuVuc']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Số tiền giải ngân (VNĐ) *</label>
              <input type="number" id="disburseAmount" required min="1000" step="1000" placeholder="VD: 500000"
                     style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; font-weight: bold; color: #1565c0; box-sizing: border-box;">
            </div>
            <div>
              <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Ngày chuyển khoản *</label>
              <input type="date" id="disburseDate" required value="<?= date('Y-m-d') ?>"
                     style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box;">
            </div>
          </div>

          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">Mục đích sử dụng quỹ *</label>
            <textarea id="disbursePurpose" required rows="2" placeholder="VD: Chi phí thức ăn hạt và cứu trợ y tế cho chó mèo cơ nhỡ..."
                      style="width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; box-sizing: border-box; resize: vertical;"></textarea>
          </div>

          <div>
            <label style="display: block; font-weight: bold; font-size: 13px; color: #333; margin-bottom: 5px;">File / Link chứng từ sao kê (PDF, ảnh)</label>
            <input type="text" id="disburseFile" placeholder="VD: assets/docs/chung_tu_nong_lam_112026.pdf"
                   style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; box-sizing: border-box;">
          </div>
        </div>

        <div style="padding: 12px 18px; background: #f7f7f7; border-top: 1px solid #ddd; display: flex; justify-content: flex-end; gap: 8px;">
          <button type="button" onclick="closeDisbursementModal()" style="padding: 8px 16px; border: 1px solid #ccc; background: #fff; color: #333; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
          <button type="submit" id="btnSubmitDisburse" style="padding: 8px 20px; border: none; background: #1565c0; color: #ffffff; border-radius: 4px; font-weight: bold; font-size: 13.5px; cursor: pointer;">
            Xác nhận giải ngân
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openDisbursementModal() {
      const modal = document.getElementById('disbursementModalBackdrop');
      if (modal) {
        modal.style.display = 'grid';
      }
    }

    function closeDisbursementModal() {
      const modal = document.getElementById('disbursementModalBackdrop');
      if (modal) {
        modal.style.display = 'none';
      }
    }

    async function adminSubmitDisbursement(event) {
      event.preventDefault();
      const btn = document.getElementById('btnSubmitDisburse');
      const matram = document.getElementById('disburseStation').value;
      const sotien = document.getElementById('disburseAmount').value;
      const ngayck = document.getElementById('disburseDate').value;
      const mucdich = document.getElementById('disbursePurpose').value;
      const filect = document.getElementById('disburseFile').value;

      if (!matram || !sotien) {
        alert('Vui lòng điền đầy đủ các thông tin bắt buộc.');
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Đang xử lý...';

      try {
        const resp = await fetch('index.php?action=admin_disburse', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            matram: matram,
            sotien: sotien,
            ngaychuyenkhoan: ngayck,
            mucdich: mucdich,
            filechungtu: filect
          })
        });
        const res = await resp.json();
        if (res.success) {
          showAdminToast('Thành công', res.message, 'success');
          closeDisbursementModal();
          setTimeout(() => location.reload(), 1000);
        } else {
          showAdminToast('Thất bại', res.message || 'Lỗi khi giải ngân quỹ', 'error');
        }
      } catch (err) {
        showAdminToast('Lỗi mạng', err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Xác nhận giải ngân';
      }
    }
  </script>
  <!-- MODAL CHI TIẾT YÊU CẦU ĐỔI TRẢ & HÌNH ẢNH MINH CHỨNG -->
  <div id="returnDetailModalBackdrop" onclick="if(event.target === this) closeReturnDetailModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.55); z-index: 9999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(640px, 95vw); border-radius: 6px; border: 1px solid #dcdcdc; box-shadow: 0 8px 30px rgba(0,0,0,0.2); overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
      <!-- Header -->
      <div style="background: #2c1a0e; color: #ffffff; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <h3 id="modalReturnIdTitle" style="margin: 0; font-size: 16px; font-weight: bold; color: #ffffff;">Yêu cầu đổi trả #DT000</h3>
          <span id="modalReturnStatusBadge" style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11.5px; font-weight: bold; background: #d97706; color: #ffffff;">Chờ xử lý</span>
        </div>
        <button type="button" onclick="closeReturnDetailModal()" style="background: none; border: none; font-size: 20px; color: #f0ede8; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
      </div>

      <!-- Body -->
      <div style="padding: 18px; display: flex; flex-direction: column; gap: 16px; max-height: 75vh; overflow-y: auto;">
        
        <!-- Khối Thông tin đơn hàng & Khách hàng -->
        <div style="background: #fbf9f6; border: 1px solid #ebd9c8; border-radius: 6px; padding: 12px 14px; font-size: 13.5px; line-height: 1.6;">
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #e2cfbd; padding-bottom: 6px; margin-bottom: 8px;">
            <div>Mã đơn hàng: <strong id="modalReturnOrderId" style="color: #2c1a0e; font-size: 14.5px;">--</strong></div>
            <div style="font-weight: bold; color: #2e7d32;" id="modalReturnOrderTotal">0đ</div>
          </div>
          <div>Khách hàng: <strong id="modalReturnCustomer">--</strong></div>
          <div>Địa chỉ nhận hàng: <span id="modalReturnAddress" style="color: #555;">--</span></div>
          <div style="color: #777; font-size: 12.5px; margin-top: 4px;">Thời điểm yêu cầu: <span id="modalReturnDate">--</span></div>
        </div>

        <!-- Khối Lý do đổi trả -->
        <div>
          <label style="display: block; font-weight: bold; font-size: 13px; color: #2c1a0e; margin-bottom: 6px;">Lý do khách hàng yêu cầu đổi trả:</label>
          <div id="modalReturnReason" style="padding: 12px 14px; background: #fff8f0; border-left: 4px solid #ea580c; border-radius: 4px; font-size: 14px; color: #333; line-height: 1.5;">
            --
          </div>
        </div>

        <!-- Khối Hình ảnh minh chứng (Proof Images) -->
        <div>
          <label style="display: block; font-weight: bold; font-size: 13px; color: #2c1a0e; margin-bottom: 6px;">Hình ảnh minh chứng thực tế từ khách hàng:</label>
          <div id="modalReturnImagesGallery" style="display: flex; gap: 12px; flex-wrap: wrap;">
            <!-- Render ảnh minh chứng -->
          </div>
        </div>

        <!-- Khối Ra quyết định / Hướng xử lý -->
        <div id="modalReturnDecisionSection">
          <!-- Render form ra quyết định hoặc thông tin đã xử lý -->
        </div>

      </div>

      <!-- Footer -->
      <div style="background: #f7f5f2; padding: 12px 18px; border-top: 1px solid #ebd9c8; display: flex; justify-content: flex-end;">
        <button type="button" onclick="closeReturnDetailModal()" style="padding: 7px 16px; background: #e5e0d8; color: #333; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: 600;">Đóng</button>
      </div>
    </div>
  </div>

  <!-- LIGHTBOX XEM ẢNH PHÓNG TO -->
  <div id="imageLightboxBackdrop" onclick="closeImageLightbox()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); z-index: 99999999; place-items: center; padding: 20px;">
    <div style="position: relative; max-width: 90vw; max-height: 90vh;">
      <button type="button" onclick="closeImageLightbox()" style="position: absolute; top: -36px; right: 0; background: none; border: none; font-size: 30px; color: #fff; cursor: pointer;">✕</button>
      <img id="lightboxImage" src="" alt="Hình ảnh phóng to" style="max-width: 90vw; max-height: 85vh; border-radius: 6px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); object-fit: contain;">
    </div>
  </div>
</body>
</html>
