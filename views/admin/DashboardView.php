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

$dbReturns = DoiTraModel::getAll();
$dbVouchers = VoucherModel::getAllAdmin();

// Dữ liệu Tồn kho & Đã bán tính trực tiếp từ CSDL thật (bien_the & chi_tiet_don_hang)
$initialInventory = SanPhamModel::getInventoryWithSales();
if (empty($initialInventory)) {
    $initialInventory = [];
}

// Dữ liệu Đơn hàng đọc trực tiếp từ Model / Supabase
$dbOrders = DonHangModel::getAll();
$initialOrders = [];
foreach ($dbOrders as $o) {
    $firstItem = !empty($o['items']) ? $o['items'][0]['name'] : 'Thức ăn hạt & phụ kiện KIBO';
    $itemList = !empty($o['items']) ? $o['items'] : [['name' => $firstItem, 'variant' => 'Tiêu chuẩn', 'qty' => 1, 'price' => $o['total']]];
    $initialOrders[] = [
        'id' => $o['orderId'],
        'customer' => $o['customerName'],
        'phone' => $o['phone'] ?: '0772 906 756',
        'address' => $o['address'] ?: '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh',
        'items' => $firstItem,
        'itemsList' => $itemList,
        'date' => $o['date'] ?? date('d/m/Y'),
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
          <b id="notifBadge">3</b>
        </button>
        <div class="ops-notification-menu" id="notifDropdown" style="display: none;">
          <strong>Thông báo mới</strong>
          <p id="notifLowStockMsg">⚠️ 3 sản phẩm cần nhập kho ngay.</p>
          <p>✅ Đơn KB-8921 đã giao thành công.</p>
          <p>❤️ Đã trích 27.000đ vào Quỹ Cứu Trợ.</p>
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
        <!-- Hàng 4 thẻ số liệu tóm tắt (KPIs) -->
        <section class="ops-summary-row">
          <div>
            <small>Doanh thu hòa vốn (Tháng)</small>
            <strong>46.800.000đ</strong>
            <p class="positive">Mục tiêu hòa vốn 156 đơn / tháng</p>
          </div>
          <div>
            <small>Quỹ cứu trợ 5%</small>
            <strong>2.340.000đ</strong>
            <p>124.000đ chờ đối soát</p>
          </div>
          <div>
            <small>Đơn hàng</small>
            <strong>128</strong>
            <p>18 mới · 96 đã giao</p>
          </div>
          <div>
            <small>Tồn kho</small>
            <strong id="kpiTotalStock">0</strong>
            <p class="warning-text" id="kpiLowStockMsg">0 sản phẩm cần xử lý</p>
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
           TAB 4: ĐỐI SOÁT QUỸ CỨU TRỢ (FUND)
           ================================================================ -->
      <div id="tabContent_fund" class="tab-pane" style="display: none;">
        <section class="ops-summary-row fund-summary" style="margin-top: 18px;">
          <div>
            <small>Tổng quỹ tích lũy</small>
            <strong>2.340.000đ</strong>
            <p>Từ 128 đơn thành công</p>
          </div>
          <div>
            <small>Đã giải ngân</small>
            <strong>2.180.000đ</strong>
            <p>UNC-KIBO-0625</p>
          </div>
          <div>
            <small>Chờ đối soát</small>
            <strong>160.000đ</strong>
            <p>Đối soát ngày 30/06</p>
          </div>
        </section>

        <article class="ops-card">
          <div class="ops-card-head">
            <div>
              <h2>Lịch sử Quỹ cứu trợ</h2>
              <p>Các khoản trích và giải ngân gần nhất</p>
            </div>
            <button type="button" class="ops-secondary-button" onclick="showAdminToast('Tải báo cáo', 'Đang kết xuất báo cáo Quỹ Cứu Trợ...')">Tải báo cáo</button>
          </div>
          <div class="ops-table-wrap">
            <table class="ops-table">
              <thead>
                <tr>
                  <th>Ngày</th>
                  <th>Nội dung</th>
                  <th>Tham chiếu</th>
                  <th>Thu</th>
                  <th>Chi</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>30/06/2025</td>
                  <td>Giải ngân Trạm Nông Lâm</td>
                  <td><code>UNC-KIBO-0625</code></td>
                  <td>-</td>
                  <td><strong style="color: #c94336;">2.180.000đ</strong></td>
                  <td><span class="ops-stock-status good">Đã đối soát</span></td>
                </tr>
                <tr>
                  <td>27/06/2025</td>
                  <td>Trích quỹ từ đơn KB-8921</td>
                  <td><code>KB-8921</code></td>
                  <td><strong style="color: var(--green);">27.000đ</strong></td>
                  <td>-</td>
                  <td><span class="ops-stock-status low">Chờ đối soát</span></td>
                </tr>
                <tr>
                  <td>26/06/2025</td>
                  <td>Trích quỹ từ đơn KB-8918</td>
                  <td><code>KB-8918</code></td>
                  <td><strong style="color: var(--green);">18.500đ</strong></td>
                  <td>-</td>
                  <td><span class="ops-stock-status good">Đã đối soát</span></td>
                </tr>
                <tr>
                  <td>25/06/2025</td>
                  <td>Trích quỹ từ đơn KB-8912</td>
                  <td><code>KB-8912</code></td>
                  <td><strong style="color: var(--green);">36.400đ</strong></td>
                  <td>-</td>
                  <td><span class="ops-stock-status good">Đã đối soát</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- ================================================================
           TAB 5: BÁO CÁO KINH DOANH (REPORTS)
           ================================================================ -->
      <div id="tabContent_reports" class="tab-pane" style="display: none;">
        <div class="ops-report-layout">
          <article class="ops-card">
            <div class="ops-card-head">
              <div>
                <h2>Báo cáo theo kỳ</h2>
                <p>Chọn loại báo cáo cần xuất dữ liệu</p>
              </div>
            </div>
            <div class="ops-report-list">
              <div class="ops-report-row">
                <span><?= kibo_icon('chart', 17) ?></span>
                <p>
                  <strong>Doanh thu &amp; lợi nhuận</strong>
                  <small id="reportRange1">Dữ liệu 30 ngày gần nhất</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="showAdminToast('Tải báo cáo', 'Đang kết xuất file Doanh thu & lợi nhuận (Excel/PDF)...')">Tải xuống</button>
              </div>

              <div class="ops-report-row">
                <span><?= kibo_icon('bag', 17) ?></span>
                <p>
                  <strong>Đơn hàng theo trạng thái</strong>
                  <small id="reportRange2">Dữ liệu 30 ngày gần nhất</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="showAdminToast('Tải báo cáo', 'Đang kết xuất file Đơn hàng (Excel/PDF)...')">Tải xuống</button>
              </div>

              <div class="ops-report-row">
                <span><?= kibo_icon('box', 17) ?></span>
                <p>
                  <strong>Tồn kho &amp; nhập hàng</strong>
                  <small id="reportRange3">Dữ liệu 30 ngày gần nhất</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="showAdminToast('Tải báo cáo', 'Đang kết xuất file Tồn kho & Nhập hàng (Excel/PDF)...')">Tải xuống</button>
              </div>

              <div class="ops-report-row">
                <span><?= kibo_icon('heart', 17) ?></span>
                <p>
                  <strong>Đối soát Quỹ cứu trợ 5%</strong>
                  <small id="reportRange4">Dữ liệu 30 ngày gần nhất</small>
                </p>
                <button type="button" class="ops-secondary-button" onclick="showAdminToast('Tải báo cáo', 'Đang kết xuất file Quỹ Cứu Trợ 5% (Excel/PDF)...')">Tải xuống</button>
              </div>
            </div>
          </article>
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
                      <td><code><?= htmlspecialchars($ret['MaYeuCau']) ?></code></td>
                      <td>
                        <strong style="color: #2c1a0e;"><?= htmlspecialchars($ret['MaDonHang']) ?></strong>
                      </td>
                      <td style="color: #333; line-height: 1.5;">
                        <?= htmlspecialchars($ret['LyDoDoiTra']) ?>
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
                        <?php if ($isPending): ?>
                          <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                            <button type="button" onclick="adminProcessReturn('<?= htmlspecialchars($ret['MaYeuCau']) ?>', 'doi')" style="padding: 4px 8px; font-size: 12px; background: #2e7d32; color: #fff; border: none; border-radius: 3px; cursor: pointer;" title="Đồng ý đổi hàng">Đổi</button>
                            <button type="button" onclick="adminProcessReturn('<?= htmlspecialchars($ret['MaYeuCau']) ?>', 'hoantien')" style="padding: 4px 8px; font-size: 12px; background: #1565c0; color: #fff; border: none; border-radius: 3px; cursor: pointer;" title="Hoàn tiền và hủy phần trích quỹ">Hoàn tiền</button>
                            <button type="button" onclick="adminProcessReturn('<?= htmlspecialchars($ret['MaYeuCau']) ?>', 'tuchoi')" style="padding: 4px 6px; font-size: 12px; background: #c94336; color: #fff; border: none; border-radius: 3px; cursor: pointer;" title="Từ chối yêu cầu">Từ chối</button>
                          </div>
                        <?php else: ?>
                          <span style="font-size: 12px; color: #888;">Đã đóng</span>
                        <?php endif; ?>
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
                        <button type="button" onclick="adminToggleVoucher('<?= htmlspecialchars($vc['mavoucher']) ?>', this)" 
                                style="padding: 4px 10px; font-size: 12px; border-radius: 3px; border: 1px solid <?= !empty($vc['kichhoat']) ? '#2e7d32' : '#999' ?>; background: <?= !empty($vc['kichhoat']) ? '#e8f5e9' : '#f5f5f5' ?>; color: <?= !empty($vc['kichhoat']) ? '#2e7d32' : '#666' ?>; font-weight: bold; cursor: pointer;">
                          <?= !empty($vc['kichhoat']) ? 'Đang bật' : 'Đã tắt' ?>
                        </button>
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

    </section>
  </div>

  <!-- JAVASCRIPT ĐIỀU KHIỂN ĐA TÍNH NĂNG -->
  <script>
    // 1. DỮ LIỆU TỒN KHO & ĐƠN HÀNG BAN ĐẦU
    let inventoryData = <?= json_encode($initialInventory, JSON_UNESCAPED_UNICODE) ?>;
    let ordersData = <?= json_encode($initialOrders, JSON_UNESCAPED_UNICODE) ?>;
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
      returns: { title: 'Quản lý yêu cầu đổi trả', desc: 'Tiếp nhận và xử lý yêu cầu đổi trả 3 ngày theo quy định CSDL.' }
    };

    // 2. CHUYỂN ĐỔI TAB MƯỢT MÀ
    function switchAdminTab(tab) {
      currentTab = tab;
      try { sessionStorage.setItem('kibo_active_admin_tab', tab); } catch (e) {}
      
      // Update sidebar active buttons
      const navButtons = document.querySelectorAll('#adminNav button');
      const tabs = ['overview', 'orders', 'inventory', 'fund', 'vouchers', 'reports', 'returns'];
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
                <button type="button" class="ops-restock" onclick="restockItem('${item.id}')">+ Nhập thêm</button>
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
      const catCode = document.getElementById('newProdCategory').value;
      const catText = document.getElementById('newProdCategory').selectedOptions[0].text;
      const brand = document.getElementById('newProdBrand').value.trim() || 'KIBO Original';
      const variant = document.getElementById('newProdVariant').value.trim() || 'Tiêu chuẩn';
      const price = parseFloat(document.getElementById('newProdPrice').value) || 100000;
      const stock = parseInt(document.getElementById('newProdStock').value) || 50;
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

        const newId = res.masp || ('SP' + Date.now());
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

    // --- RETURN ORDER PROCESSING FUNCTION ---
    async function adminProcessReturn(maYeuCau, huongXuLy) {
      let lyDo = '';
      if (huongXuLy === 'tuchoi') {
        lyDo = prompt('Nhập lý do từ chối yêu cầu đổi trả:');
        if (lyDo === null) return;
        if (!lyDo.trim()) {
          alert('Vui lòng nhập lý do từ chối.');
          return;
        }
      } else {
        const actionLabel = (huongXuLy === 'doi') ? 'đồng ý đổi hàng' : 'hoàn tiền và hủy phần trích quỹ';
        if (!confirm(`Xác nhận duyệt ${actionLabel} cho yêu cầu ${maYeuCau}?`)) return;
      }

      try {
        const resp = await fetch('index.php?action=admin_process_return', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mayeucau: maYeuCau, huongxuly: huongXuLy, lydotuchoi: lyDo })
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
          if (isPending) {
            quickBtn = `<button type="button" class="ops-row-action" style="background: #2e7d32; color: #ffffff; border: none; font-weight: 700; margin-left: 6px;" onclick="updateOrderStatus('${order.id}', 'Đang giao')">Duyệt đơn</button>`;
          } else if (order.status === 'Đang giao' || order.status === 'Đang giao hàng') {
            quickBtn = `<button type="button" class="ops-row-action" style="background: #1565c0; color: #ffffff; border: none; font-weight: 700; margin-left: 6px;" onclick="updateOrderStatus('${order.id}', 'Giao thành công')">Giao xong</button>`;
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

      const modal = document.getElementById('orderDetailModalBackdrop');
      if (modal) modal.style.display = 'grid';
    }

    function closeOrderDetailModal() {
      const modal = document.getElementById('orderDetailModalBackdrop');
      if (modal) modal.style.display = 'none';
      currentViewingOrderId = null;
    }

    async function updateOrderStatus(orderId, newStatus) {
      try {
        const resp = await fetch('index.php?action=admin_update_order_status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ madonhang: orderId, trangthai: newStatus })
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

    // 7. RENDER TAB OVERVIEW
    function renderOverview() {
      const feed = document.getElementById('overviewRecentOrders');
      if (feed) {
        feed.innerHTML = ordersData.slice(0, 4).map(order => {
          const initial = order.customer.trim().split(' ').slice(-1)[0][0] || 'K';
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

  <!-- MODAL CHI TIẾT ĐƠN HÀNG (DẠNG BASIC) -->
  <div id="orderDetailModalBackdrop" onclick="if(event.target === this) closeOrderDetailModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 9999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(560px, 95vw); border-radius: 4px; border: 1px solid #dcdcdc; box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden; font-family: Arial, sans-serif;">
      <!-- Header -->
      <div style="background: #f7f7f7; padding: 12px 16px; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <h3 id="modalOrderId" style="margin: 0; font-size: 15px; font-weight: bold; color: #333;">Đơn hàng #KB-0000</h3>
          <span id="modalOrderStatusBadge" style="display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #d97706; color: #ffffff;">Chờ xác nhận</span>
        </div>
        <button type="button" onclick="closeOrderDetailModal()" style="background: none; border: none; font-size: 18px; color: #666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
      </div>

      <!-- Body -->
      <div style="padding: 16px; display: flex; flex-direction: column; gap: 14px; max-height: 70vh; overflow-y: auto;">
        <!-- Khối chuyển trạng thái đơn (Dành cho Admin) -->
        <div style="background: #fdfdfd; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
          <span style="font-size: 13px; font-weight: bold; color: #333;">Chuyển trạng thái:</span>
          <div style="display: flex; gap: 6px;">
            <button type="button" onclick="updateCurrentOrderModalStatus('Đang giao')" style="padding: 5px 10px; background: #2e7d32; color: #fff; border: none; border-radius: 3px; font-size: 12.5px; font-weight: bold; cursor: pointer;">Duyệt &amp; Giao hàng</button>
            <button type="button" onclick="updateCurrentOrderModalStatus('Giao thành công')" style="padding: 5px 10px; background: #1565c0; color: #fff; border: none; border-radius: 3px; font-size: 12.5px; font-weight: bold; cursor: pointer;">Giao thành công</button>
            <button type="button" onclick="updateCurrentOrderModalStatus('Đã hủy')" style="padding: 5px 8px; background: #fff; color: #c94336; border: 1px solid #ccc; border-radius: 3px; font-size: 12.5px; cursor: pointer;">Hủy đơn</button>
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
</body>
</html>
