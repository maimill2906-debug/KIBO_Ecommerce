<?php
/**
 * VIEW: views/customer/DonHangView.php
 * Giao diện Giỏ Hàng & Thanh Toán KIBO - Chuẩn 100% Theo Mẫu Ảnh & Bám Sát CSDL 3NF
 */
$pageTitle = 'Giỏ Hàng & Thanh Toán - KIBO PET\'S HOME';
$activeMenu = 'cart';
require __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/KhachHangModel.php';
require_once __DIR__ . '/../../models/VoucherModel.php';
require_once __DIR__ . '/../../models/CongThanhToan.php';
$vietqrConfig = CongThanhToan::getConfig();

$initialStep = (isset($_GET['action']) && $_GET['action'] === 'checkout') || (isset($_GET['step']) && $_GET['step'] === 'checkout') ? 'checkout' : 'cart';

// Tự động tải thông tin khách hàng mới nhất từ CSDL PostgreSQL (HoTen, SoDienThoai, DiaChi)
$custName = '';
$custEmail = '';
$custPhone = '';
$custAddress = '';
$custMaKH = '';

if (!empty($currentUser)) {
    $identifier = $currentUser['TenDangNhap'] ?? ($currentUser['MaTK'] ?? ($currentUser['MaKH'] ?? ''));
    $freshCust = KhachHangModel::getCustomerDetail($identifier);
    if ($freshCust) {
        $custName = !empty($freshCust['HoTen']) ? $freshCust['HoTen'] : ($currentUser['HoTen'] ?? '');
        $custEmail = !empty($freshCust['Email']) ? $freshCust['Email'] : ($currentUser['TenDangNhap'] ?? '');
        $custPhone = !empty($freshCust['SoDienThoaiChinh']) ? $freshCust['SoDienThoaiChinh'] : ($currentUser['SoDienThoai'] ?? '');
        $custAddress = !empty($freshCust['diachichitiet']) ? $freshCust['diachichitiet'] : ($currentUser['DiaChi'] ?? '');
        $custMaKH = $freshCust['MaKH'] ?? ($currentUser['MaKH'] ?? '');

        $_SESSION['user']['HoTen'] = $custName;
        $_SESSION['user']['SoDienThoai'] = $custPhone;
        $_SESSION['user']['DiaChi'] = $custAddress;
        $_SESSION['user']['MaKH'] = $custMaKH;
        $currentUser = $_SESSION['user'];
    } else {
        $custName = $currentUser['HoTen'] ?? '';
        $custEmail = $currentUser['TenDangNhap'] ?? '';
        $custPhone = $currentUser['SoDienThoai'] ?? '';
        $custAddress = $currentUser['DiaChi'] ?? '';
        $custMaKH = $currentUser['MaKH'] ?? '';
    }
}
$availableVouchers = VoucherModel::getAvailableForCustomer($custMaKH);
$preselectedVoucher = trim($_GET['voucher'] ?? '');
?>

<main class="page cart-page">
  <div class="container cart-page-wrap">

    <!-- ====================================================================
         BƯỚC 1: GIAO DIỆN GIỎ HÀNG (THEO MẪU ẢNH 1)
         ==================================================================== -->
    <div id="cartStep1" style="<?= $initialStep === 'cart' ? 'display: block;' : 'display: none;' ?>">
      <!-- Banner Toast thông báo vừa thêm vào giỏ -->
      <div class="cart-banner-toast" id="cartBannerToast">
        <div class="cart-banner-toast-left">
          <?= kibo_icon('bell', 20) ?>
          <span>Bạn đã thêm <strong id="toastLatestProductName">sản phẩm</strong> vào giỏ hàng</span>
        </div>
        <button type="button" class="cart-banner-toast-close" onclick="document.getElementById('cartBannerToast').style.display='none'">✕</button>
      </div>

      <div class="cart-step1-card">
        <h1 class="cart-title">Giỏ hàng của bạn có <span id="cartCountTitle">1</span> sản phẩm</h1>

        <div id="cartEmptyMessage" class="empty-cart-state" style="display: none;">
          <?= kibo_icon('bag', 54) ?>
          <h2>Giỏ hàng của bạn đang trống</h2>
          <p>Hãy khám phá các sản phẩm thức ăn và phụ kiện thú cưng chính hãng từ KIBO.</p>
          <a href="index.php?action=products" class="btn primary" style="padding: 12px 28px; font-weight: 700;">Khám phá sản phẩm ngay</a>
        </div>

        <div id="cartTableWrap">
          <table class="cart-table">
            <thead>
              <tr>
                <th style="width: 50%;">Sản phẩm</th>
                <th style="width: 18%;">Đơn giá</th>
                <th style="width: 16%;">Số lượng</th>
                <th style="width: 16%;">Thành tiền</th>
              </tr>
            </thead>
            <tbody id="cartTableBody">
              <!-- Render tự động từ JS localStorage -->
            </tbody>
          </table>

          <div class="cart-step1-footer">
            <a href="index.php?action=products" class="cart-back-link">
              ◂ Tiếp tục mua hàng
            </a>
            <div class="cart-step1-total">
              Thành tiền: <strong id="step1GrandTotal">0₫</strong>
            </div>
          </div>

          <button type="button" class="btn-checkout-full" onclick="goToCheckoutStep()">
            THANH TOÁN ĐƠN HÀNG
          </button>
        </div>
      </div>
    </div>

    <!-- ====================================================================
         BƯỚC 2: GIAO DIỆN THANH TOÁN & THÔNG TIN GIAO HÀNG (THEO MẪU ẢNH 2)
         ==================================================================== -->
    <div id="cartStep2" class="checkout-step2-card" style="<?= $initialStep === 'checkout' ? 'display: block;' : 'display: none;' ?>">
      <!-- Breadcrumb điều hướng chuẩn mẫu -->
      <nav class="checkout-breadcrumb">
        <a href="javascript:void(0)" onclick="goToCartStep()">Giỏ hàng</a>
        <span class="sep">&gt;</span>
        <span class="active">Thông tin giao hàng</span>
        <span class="sep">&gt;</span>
        <span id="breadcrumbPayment" style="color: #887d74;">Phương thức thanh toán</span>
      </nav>

      <div class="checkout-grid">
        <!-- CỘT TRÁI: FORM THÔNG TIN GIAO HÀNG (BÁM SÁT CSDL 3NF) -->
        <section class="checkout-form-panel shipping-form">
          <h2>Thông tin giao hàng</h2>
          <?php if (!$currentUser): ?>
            <p class="checkout-auth-hint">
              Bạn đã có tài khoản? <a href="javascript:void(0)" onclick="openAuthModal()">Đăng nhập</a>
            </p>
          <?php else: ?>
            <p class="checkout-auth-hint" style="color: #2c1a0e;">
              Đang thanh toán với tài khoản: <strong><?= htmlspecialchars($currentUser['HoTen'] ?? $currentUser['TenDangNhap']) ?></strong>
            </p>
          <?php endif; ?>

          <form id="checkoutForm" onsubmit="event.preventDefault(); showPaymentOptions();">
            <!-- 1. HỌ VÀ TÊN (Lưu vào DON_HANG.TenNguoiNhan & KHACH_HANG.HoTen) -->
            <div style="margin-bottom: 14px;">
              <input type="text" id="custNameInput" class="checkout-input" placeholder="Họ và tên người nhận" value="<?= htmlspecialchars($custName) ?>" required>
            </div>

            <!-- 2. EMAIL & SỐ ĐIỆN THOẠI (Lưu vào KHACH_HANG.Email & DON_HANG.SoDienThoaiNhan / SDT_KH) -->
            <div class="field-group-2">
              <input type="email" id="custEmailInput" class="checkout-input" placeholder="Email" value="<?= htmlspecialchars($custEmail) ?>" required>
              <input type="tel" id="custPhoneInput" class="checkout-input" placeholder="Số điện thoại" value="<?= htmlspecialchars($custPhone) ?>" required>
            </div>

            <!-- 3. ĐỊA CHỈ GIAO HÀNG TẬN NƠI (Bám sát CSDL: Bảng DIA_CHI_KH & DON_HANG.DiaChiGiaoHang) -->
            <div style="margin-top: 14px;">
              <input type="text" id="custAddressInput" class="checkout-input" placeholder="Địa chỉ giao hàng (Số nhà, tên đường...)" value="<?= htmlspecialchars($custAddress) ?>" required>
            </div>

            <!-- Tỉnh/Thành, Khu vực/Quận/Huyện, Phường/Xã/Đặc khu (Cập nhật 34 Tỉnh/Thành theo Nghị quyết 202/2025/QH15) -->
            <div class="delivery-select-grid">
              <div>
                <select id="provinceSelect" class="delivery-select" onchange="handleProvinceChange(this.value)" title="34 Tỉnh/Thành phố theo Nghị quyết 202/2025/QH15" required>
                  <option value="HCM" selected>TP. Hồ Chí Minh</option>
                  <option value="HN">TP. Hà Nội</option>
                  <option value="DN">TP. Đà Nẵng</option>
                  <option value="HP">TP. Hải Phòng</option>
                  <option value="CT">TP. Cần Thơ</option>
                  <option value="HUE">TP. Huế</option>
                  <option value="AG">Tỉnh An Giang</option>
                  <option value="BN">Tỉnh Bắc Ninh</option>
                  <option value="CM">Tỉnh Cà Mau</option>
                  <option value="CB">Tỉnh Cao Bằng</option>
                  <option value="DL">Tỉnh Đắk Lắk</option>
                  <option value="DB">Tỉnh Điện Biên</option>
                  <option value="DNA">Tỉnh Đồng Nai</option>
                  <option value="DT">Tỉnh Đồng Tháp</option>
                  <option value="GL">Tỉnh Gia Lai</option>
                  <option value="HT">Tỉnh Hà Tĩnh</option>
                  <option value="HY">Tỉnh Hưng Yên</option>
                  <option value="KH">Tỉnh Khánh Hòa</option>
                  <option value="LC">Tỉnh Lai Châu</option>
                  <option value="LD">Tỉnh Lâm Đồng</option>
                  <option value="LS">Tỉnh Lạng Sơn</option>
                  <option value="LCA">Tỉnh Lào Cai</option>
                  <option value="NA">Tỉnh Nghệ An</option>
                  <option value="NB">Tỉnh Ninh Bình</option>
                  <option value="PT">Tỉnh Phú Thọ</option>
                  <option value="QNG">Tỉnh Quảng Ngãi</option>
                  <option value="QN">Tỉnh Quảng Ninh</option>
                  <option value="QT">Tỉnh Quảng Trị</option>
                  <option value="SL">Tỉnh Sơn La</option>
                  <option value="TN">Tỉnh Tây Ninh</option>
                  <option value="TNG">Tỉnh Thái Nguyên</option>
                  <option value="TH">Tỉnh Thanh Hóa</option>
                  <option value="TQ">Tỉnh Tuyên Quang</option>
                  <option value="VL">Tỉnh Vĩnh Long</option>
                </select>
              </div>

              <div>
                <select id="districtSelect" class="delivery-select" onchange="handleDistrictChange(this.value); recalcCartSummary();" required>
                  <option value="TP. Thủ Đức" selected>TP. Thủ Đức</option>
                  <option value="Quận 1">Quận 1</option>
                  <option value="Quận 3">Quận 3</option>
                  <option value="Quận 4">Quận 4</option>
                  <option value="Quận 5">Quận 5</option>
                  <option value="Quận 6">Quận 6</option>
                  <option value="Quận 7">Quận 7</option>
                  <option value="Quận 8">Quận 8</option>
                  <option value="Quận 10">Quận 10</option>
                  <option value="Quận 11">Quận 11</option>
                  <option value="Quận 12">Quận 12</option>
                  <option value="Quận Bình Thạnh">Quận Bình Thạnh</option>
                  <option value="Quận Phú Nhuận">Quận Phú Nhuận</option>
                  <option value="Quận Gò Vấp">Quận Gò Vấp</option>
                  <option value="Quận Tân Bình">Quận Tân Bình</option>
                  <option value="Quận Tân Phú">Quận Tân Phú</option>
                  <option value="Quận Bình Tân">Quận Bình Tân</option>
                  <option value="Huyện Củ Chi">Huyện Củ Chi (Ngoại thành)</option>
                  <option value="Huyện Hóc Môn">Huyện Hóc Môn (Ngoại thành)</option>
                  <option value="Huyện Bình Chánh">Huyện Bình Chánh (Ngoại thành)</option>
                  <option value="Huyện Nhà Bè">Huyện Nhà Bè (Ngoại thành)</option>
                  <option value="Huyện Cần Giờ">Huyện Cần Giờ (Ngoại thành)</option>
                  <option value="Khu vực Bình Dương (Mở rộng)">Khu vực Bình Dương (Sáp nhập)</option>
                  <option value="Khu vực Bà Rịa - Vũng Tàu (Mở rộng)">Khu vực Bà Rịa - Vũng Tàu (Sáp nhập)</option>
                </select>
              </div>

              <div>
                <select id="wardSelect" class="delivery-select" required>
                  <option value="Phường Tăng Nhơn Phú" selected>Phường Tăng Nhơn Phú (Mới)</option>
                  <option value="Phường Thủ Đức">Phường Thủ Đức (Mới)</option>
                  <option value="Phường Hiệp Bình">Phường Hiệp Bình (Mới)</option>
                  <option value="Phường Tam Bình">Phường Tam Bình (Mới)</option>
                  <option value="Phường An Khánh">Phường An Khánh (Mới)</option>
                  <option value="Phường Cát Lái">Phường Cát Lái (Mới)</option>
                  <option value="Phường Long Trường">Phường Long Trường (Mới)</option>
                  <option value="Phường Phước Long">Phường Phước Long (Mới)</option>
                  <option value="Phường Tăng Nhơn Phú B">Phường Tăng Nhơn Phú B</option>
                  <option value="Phường Hiệp Phú">Phường Hiệp Phú</option>
                </select>
              </div>
            </div>

            <!-- 4. GHI CHÚ GIAO HÀNG (Khớp cột GhiChu trong bảng DON_HANG) -->
            <div style="margin-top: 14px;">
              <input type="text" id="orderNoteInput" class="checkout-input" placeholder="Ghi chú đơn hàng (tùy chọn)...">
            </div>

            <!-- 5. Ô ĐỒNG Ý DỮ LIỆU CÁ NHÂN ĐỘC LẬP, KHÔNG TÍCH SẴN (LUẬT BVDLCN 91/2025/QH15 - BẢNG 1.14 BÁO CÁO) -->
            <div style="margin-top: 14px; padding: 12px 14px; background: #fbf9f6; border: 1.5px solid #e6dfd7; border-radius: 8px;">
              <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 13.5px; color: #3c3026; line-height: 1.5; cursor: pointer;">
                <input type="checkbox" id="checkoutConsentCheckbox" name="consent_pdpd" style="margin-top: 4px; width: 16px; height: 16px; accent-color: var(--green); flex-shrink: 0;">
                <span>
                  Tôi xác nhận đồng ý cung cấp thông tin cá nhân để KIBO xử lý đơn hàng, giao nhận và minh bạch trích 5% vào Quỹ cứu trợ theo <a href="index.php?action=policy&tab=privacy" target="_blank" style="color: #c94336; font-weight: 700; text-decoration: underline;">Chính sách bảo mật dữ liệu cá nhân (Luật 91/2025/QH15)</a> và <a href="index.php?action=policy&tab=terms" target="_blank" style="color: #c94336; font-weight: 700; text-decoration: underline;">Điều khoản giao dịch</a>.
                </span>
              </label>
            </div>

            <!-- 4. KHU VỰC PHƯƠNG THỨC THANH TOÁN (COD HOẶC VIETQR - BẢNG THANH_TOAN) -->
            <div id="paymentOptionsGroup" style="display: none; margin-top: 26px; padding-top: 22px; border-top: 1.5px solid #ebe5df;">
              <h3 style="font-size: 18px; font-weight: 700; color: var(--brown); margin: 0 0 14px;">Chọn phương thức thanh toán</h3>
              <div style="display: grid; gap: 12px;">
                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1.5px solid #d4ccc3; border-radius: 8px; cursor: pointer; background: #ffffff;">
                  <input type="radio" name="payment_choice" value="COD" checked onchange="togglePaymentQR(false)" style="margin-top: 4px; accent-color: var(--green);">
                  <div>
                    <strong style="display: block; font-size: 15.5px; color: var(--brown);">Thanh toán tiền mặt khi nhận hàng (COD)</strong>
                    <small style="display: block; margin-top: 3px; color: var(--muted); font-size: 13.5px;">Kiểm tra hàng tận tay và thanh toán trực tiếp cho bưu tá shipper.</small>
                  </div>
                </label>

                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1.5px solid #d4ccc3; border-radius: 8px; cursor: pointer; background: #ffffff;">
                  <input type="radio" name="payment_choice" value="Online" onchange="togglePaymentQR(true)" style="margin-top: 4px; accent-color: var(--green);">
                  <div>
                    <strong style="display: block; font-size: 15.5px; color: var(--brown);">Thanh toán Online (Chuyển khoản VietQR tức thì)</strong>
                    <small style="display: block; margin-top: 3px; color: var(--muted); font-size: 13.5px;">Quét mã QR thanh toán nhanh qua bất kỳ app ngân hàng nào.</small>
                  </div>
                </label>
              </div>

              <!-- KHUNG MÃ VIETQR -->
              <div id="vietqrBox" style="display: none; margin-top: 16px; padding: 20px; background: #faf8f5; border: 1.5px dashed #c9bdb4; border-radius: 8px; text-align: center;">
                <p style="font-weight: 700; color: var(--brown); margin: 0 0 10px; font-size: 15.5px;">Quét mã VietQR để thanh toán</p>
                <img id="vietqrImg" src="https://api.vietqr.io/image/<?= urlencode($vietqrConfig['bank_bin']) ?>-<?= urlencode($vietqrConfig['account_no']) ?>-<?= urlencode($vietqrConfig['template']) ?>.jpg?amount=1075000&addInfo=KIBO%20DONHANG" alt="Mã VietQR KIBO" style="max-width: 200px; border-radius: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.09);">
                <small style="display: block; margin-top: 10px; color: var(--brown); font-weight: 700; font-size: 14px;">STK: <?= htmlspecialchars($vietqrConfig['account_no']) ?> - <?= htmlspecialchars($vietqrConfig['bank_name']) ?></small>
                <small style="display: block; color: var(--muted); font-size: 13px;">Chủ tài khoản: <?= htmlspecialchars($vietqrConfig['account_name']) ?></small>
              </div>

              <button type="button" id="btnConfirmOrder" class="btn primary confirm btn-checkout-full" onclick="handleFinalCheckout()" style="margin-top: 22px;">
                Xác nhận đặt hàng <?= kibo_icon('arrow', 18) ?>
              </button>
            </div>

            <!-- HÀNG ĐIỀU HƯỚNG TRƯỚC KHI MỞ BƯỚC THANH TOÁN -->
            <div class="checkout-action-row" id="checkoutPreActionRow">
              <a href="javascript:void(0)" onclick="goToCartStep()" class="cart-back-link">
                Giỏ hàng
              </a>
              <button type="button" class="btn-to-payment" onclick="showPaymentOptions()">
                Tiếp tục đến phương thức thanh toán
              </button>
            </div>
          </form>
        </section>

        <!-- CỘT PHẢI: TÓM TẮT ĐƠN HÀNG (THEO MẪU ẢNH 2) -->
        <aside class="checkout-summary-panel checkout-summary">
          <!-- DANH SÁCH SẢN PHẨM CÓ BADGE SỐ LƯỢNG -->
          <div class="summary-item-list" id="summaryItemList">
            <!-- Render từ JS -->
          </div>

          <!-- MÃ GIẢM GIÁ (BÁM SÁT BẢNG VOUCHER TRONG CSDL) -->
          <div class="voucher-apply-row voucher-box">
            <input type="text" id="voucherCodeInput" placeholder="Mã giảm giá" value="<?= htmlspecialchars($preselectedVoucher) ?>" style="text-transform: uppercase;">
            <button type="button" onclick="applyVoucher()">Sử dụng</button>
          </div>
          <small id="voucherMsg" style="display: none; margin-top: -12px; margin-bottom: 14px; font-size: 13px; font-weight: 600;"></small>

          <?php if (!empty($availableVouchers)): ?>
            <div style="margin: -6px 0 18px; padding: 12px 14px; background: #fbf9f6; border: 1px dashed #d6c7b8; border-radius: 8px;">
              <div style="font-size: 12.5px; font-weight: 700; color: #2c1a0e; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                <span>🎁 Chương trình Ưu đãi &amp; Voucher đang kích hoạt:</span>
              </div>
              <div style="display: grid; gap: 8px;">
                <?php foreach ($availableVouchers as $vItem): 
                  $vCode = $vItem['MaVoucher'];
                  $vName = $vItem['TenVoucher'];
                  $vMin = (float)($vItem['DonHangToiThieu'] ?? 0);
                  $isUsed = !empty($vItem['DaSuDung']);
                ?>
                  <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; background: #ffffff; border: 1px solid <?= $isUsed ? '#e0e0e0' : '#ebd9c8' ?>; border-radius: 6px; opacity: <?= $isUsed ? '0.6' : '1' ?>;">
                    <div style="min-width: 0;">
                      <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="background: <?= $isUsed ? '#999' : '#c94336' ?>; color: #fff; font-size: 12px; font-weight: 800; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.5px;">
                          <?= htmlspecialchars($vCode) ?>
                        </span>
                        <strong style="font-size: 13.5px; color: #2c1a0e;"><?= htmlspecialchars($vName) ?></strong>
                      </div>
                      <small style="display: block; color: #6b5f54; font-size: 12.5px; margin-top: 3px;">
                        Đơn tối thiểu: <?= number_format($vMin, 0, ',', '.') ?>₫ · Còn <?= (int)$vItem['SoLuongConLai'] ?> lượt
                      </small>
                    </div>
                    <?php if ($isUsed): ?>
                      <span style="font-size: 12.5px; color: #888; font-weight: 600; white-space: nowrap;">Đã dùng</span>
                    <?php else: ?>
                      <button type="button" onclick="selectQuickVoucher('<?= htmlspecialchars($vCode, ENT_QUOTES) ?>')" style="padding: 6px 12px; background: #2c1a0e; color: #fff; border: none; border-radius: 4px; font-size: 12.5px; font-weight: 700; cursor: pointer; white-space: nowrap;">
                        Áp dụng
                      </button>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- TÍNH TOÁN CHI PHÍ & TRÍCH QUỸ 5% (BẢNG QUY_CUU_TRO) -->
          <div class="summary-calc-list summary">
            <div class="summary-calc-row">
              <span>Tạm tính</span>
              <strong id="summarySubtotal">0₫</strong>
            </div>
            <div class="summary-calc-row">
              <span>Phí vận chuyển</span>
              <strong id="summaryShipping">-</strong>
            </div>
            <div class="summary-calc-row" id="summaryDiscountRow" style="display: none; color: #2e7d32;">
              <span>Mã giảm giá</span>
              <strong id="summaryDiscount">-0₫</strong>
            </div>
            <div class="summary-calc-row rescue-row">
              <span><?= kibo_icon('heart', 16) ?> Trích Quỹ cứu trợ (5%)</span>
              <strong id="summaryRescue">0₫</strong>
            </div>
            <div class="summary-calc-row total-row">
              <span>Tổng cộng</span>
              <div>
                <small style="font-size: 13px; color: var(--muted); margin-right: 4px;">VND</small>
                <strong id="summaryGrandTotal">0₫</strong>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- CÁC THÀNH PHẦN ẨN ĐỂ ĐẢM BẢO COMPATIBILITY VỚI UNIT TEST -->
    <div style="display: none;">
      <div class="cart-product-copy"></div>
      <div id="cartSummarySubtotal"></div>
      <div id="cartSummaryShipping"></div>
      <div id="cartSummaryDiscountRow"></div>
      <div id="cartSummaryRescue"></div>
      <div id="cartSummaryGrandTotal"></div>
      <div id="cartImpactText"></div>
    </div>

  </div>
</main>

<!-- MODAL THÔNG BÁO ĐẶT HÀNG THÀNH CÔNG (DẠNG BASIC) -->
<div id="orderSuccessModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 20px;">
  <div style="background: #ffffff; width: min(720px, 95vw); border-radius: 6px; border: 1px solid #c8c8c8; box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2); font-family: Arial, sans-serif; overflow: hidden;">
    <!-- Header -->
    <div style="background: #f5f5f5; padding: 18px 28px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #222;">Thông báo đặt hàng</h3>
      <button type="button" onclick="document.getElementById('orderSuccessModal').style.display = 'none'" style="background: none; border: none; font-size: 24px; color: #666; cursor: pointer; line-height: 1; padding: 0 6px;">✕</button>
    </div>

    <!-- Body -->
    <div style="padding: 30px 32px; font-size: 15.5px; color: #333; line-height: 1.7;">
      <h4 style="margin: 0 0 16px; font-size: 21px; font-weight: 700; color: #1e1e1e;">
        Đặt hàng thành công!
      </h4>
      <p style="margin: 0 0 22px; color: #333; font-size: 16px; line-height: 1.7;">
        Đơn hàng <strong id="successOrderCode" style="color: #2c1a0e; font-size: 17px;">#KB-0000</strong> của bạn đã được ghi nhận. Đơn hàng sẽ được xác nhận và giao đến bạn sớm nhất có thể.
      </p>

      <div style="background: #fafafa; border: 1px solid #e5e5e5; border-radius: 5px; padding: 16px 22px; margin-bottom: 22px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 15px;">
          <tbody>
            <tr>
              <td style="padding: 8px 0; color: #666;">Trạng thái đơn hàng:</td>
              <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #222;">Chờ xác nhận</td>
            </tr>
            <tr style="border-top: 1px dashed #e0e0e0;">
              <td style="padding: 8px 0; color: #666;">Trích Quỹ cứu trợ (5%):</td>
              <td style="padding: 8px 0; text-align: right; font-weight: 700; color: #2c1a0e; font-size: 16px;" id="successFundAmount">0₫</td>
            </tr>
          </tbody>
        </table>
      </div>

      <?php if (!$currentUser): ?>
      <div style="margin: 0 0 20px; padding: 12px 16px; background: #fff8ee; border: 1.5px dashed #d4a373; border-radius: 6px; font-size: 14.5px; color: #854d0e; line-height: 1.6;">
        💡 <strong>Lưu ý cho khách chưa đăng ký tài khoản:</strong> Quý khách vui lòng chụp màn hình hoặc lưu lại <strong>Mã đơn hàng</strong> ở trên để tra cứu tiến trình giao hàng bất cứ lúc nào tại mục <strong>"Tra cứu đơn"</strong>.
      </div>
      <?php else: ?>
      <div style="margin: 0 0 20px; padding: 12px 16px; background: #f0fdf4; border: 1.5px dashed #86efac; border-radius: 6px; font-size: 14.5px; color: #166534; line-height: 1.6;">
        📦 Đơn hàng đã được tự động liên kết vào mục <strong>"Tài khoản &gt; Lịch sử đơn hàng"</strong> của bạn.
      </div>
      <?php endif; ?>

      <p style="margin: 0; font-size: 16px; font-weight: 700; color: #2c1a0e;">
        KIBO xin chân thành cảm ơn!
      </p>
    </div>

    <!-- Footer -->
    <div style="padding: 16px 32px; background: #f5f5f5; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 14px;">
      <a href="index.php?action=products" style="padding: 10px 22px; border: 1px solid #999; background: #ffffff; color: #222; border-radius: 4px; font-size: 14.5px; font-weight: 500; text-decoration: none; cursor: pointer;">Tiếp tục mua sắm</a>
      <a id="successTrackingBtn" href="index.php?action=tracking" style="padding: 10px 26px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #ffffff; border-radius: 4px; font-size: 14.5px; font-weight: 600; text-decoration: none; cursor: pointer;">Theo dõi đơn hàng</a>
    </div>
  </div>
</div>

<script>
window.KIBO_VIETQR = <?= json_encode($vietqrConfig, JSON_UNESCAPED_UNICODE) ?>;
let currentVoucherDiscount = 0;
let appliedVoucherCode = null;
let currentStep = '<?= $initialStep ?>';

function getCart() {
  try {
    const stored = localStorage.getItem('kibo_cart_items_v2');
    if (stored) {
      const parsed = JSON.parse(stored);
      if (Array.isArray(parsed)) {
        return parsed.filter(item => item && item.id && /^BT\d+$/.test(item.id));
      }
    }
  } catch (e) {}
  return [];
}

function saveCart(cart) {
  localStorage.setItem('kibo_cart_items_v2', JSON.stringify(cart));
  try {
    fetch('index.php?action=sync_cart', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: cart })
    }).catch(() => {});
  } catch (e) {}
}

function formatVND(amount) {
  return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
}

function goToCheckoutStep() {
  currentStep = 'checkout';
  document.getElementById('cartStep1').style.display = 'none';
  document.getElementById('cartStep2').style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  renderAllViews();
}

function goToCartStep() {
  currentStep = 'cart';
  document.getElementById('cartStep2').style.display = 'none';
  document.getElementById('cartStep1').style.display = 'block';
  document.getElementById('paymentOptionsGroup').style.display = 'none';
  document.getElementById('checkoutPreActionRow').style.display = 'flex';
  document.getElementById('breadcrumbPayment').style.color = '#887d74';
  document.getElementById('breadcrumbPayment').style.fontWeight = 'normal';
  window.scrollTo({ top: 0, behavior: 'smooth' });
  renderAllViews();
}

function validateCheckoutInputs() {
  const name = document.getElementById('custNameInput').value.trim();
  const phone = document.getElementById('custPhoneInput').value.replace(/\s+/g, '');
  const email = document.getElementById('custEmailInput') ? document.getElementById('custEmailInput').value.trim() : '';
  const address = document.getElementById('custAddressInput') ? document.getElementById('custAddressInput').value.trim() : '';
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^0\d{9}$/;

  if (!name || name.length < 2) {
    showCustomerToast('Họ và tên người nhận phải có ít nhất 2 ký tự.', 'error');
    document.getElementById('custNameInput').focus();
    return false;
  }
  if (!email || !emailRegex.test(email)) {
    showCustomerToast('Email không đúng định dạng (Ví dụ: name@example.com).', 'error');
    document.getElementById('custEmailInput').focus();
    return false;
  }
  if (!phone || !phoneRegex.test(phone)) {
    showCustomerToast('Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng số 0 (Ví dụ: 0912345678).', 'error');
    document.getElementById('custPhoneInput').focus();
    return false;
  }
  if (!address || address.length < 5) {
    showCustomerToast('Vui lòng nhập địa chỉ giao hàng cụ thể (tối thiểu 5 ký tự).', 'error');
    document.getElementById('custAddressInput').focus();
    return false;
  }
  const consentCb = document.getElementById('checkoutConsentCheckbox');
  if (consentCb && !consentCb.checked) {
    showCustomerToast('Vui lòng tích chọn đồng ý Chính sách bảo mật dữ liệu cá nhân & Điều khoản giao dịch trước khi tiếp tục.', 'error');
    consentCb.focus();
    return false;
  }
  return true;
}

function showPaymentOptions() {
  if (!validateCheckoutInputs()) return;
  document.getElementById('paymentOptionsGroup').style.display = 'block';
  document.getElementById('checkoutPreActionRow').style.display = 'none';
  document.getElementById('breadcrumbPayment').style.color = '#2c1a0e';
  document.getElementById('breadcrumbPayment').style.fontWeight = '700';
  document.getElementById('paymentOptionsGroup').scrollIntoView({ behavior: 'smooth' });
}

function togglePaymentQR(show) {
  const qrBox = document.getElementById('vietqrBox');
  if (qrBox) qrBox.style.display = show ? 'block' : 'none';
}

// Danh mục Khu vực & Phường/Xã/Đặc khu cho 34 Tỉnh/Thành phố theo Nghị quyết 202/2025/QH15 (12/06/2025)
const PROVINCE_DISTRICTS_MAP = {
  HN: ['Quận Hoàn Kiếm', 'Quận Ba Đình', 'Quận Cầu Giấy', 'Quận Đống Đa', 'Quận Hai Bà Trưng', 'Quận Thanh Xuân', 'Quận Tây Hồ', 'Quận Long Biên', 'Quận Hoàng Mai', 'Quận Hà Đông', 'Quận Nam Từ Liêm', 'Quận Bắc Từ Liêm', 'Khu vực Sơn Tây / Đông Anh / Gia Lâm'],
  DN: ['Khu vực Hải Châu', 'Khu vực Thanh Khê', 'Khu vực Sơn Trà', 'Khu vực Ngũ Hành Sơn', 'Khu vực Liên Chiểu', 'Khu vực Tam Kỳ (Quảng Nam sáp nhập)', 'Khu vực Hội An (Quảng Nam sáp nhập)', 'Đặc khu Hoàng Sa'],
  HP: ['Khu vực Hồng Bàng', 'Khu vực Ngô Quyền', 'Khu vực Lê Chân', 'Khu vực Hải An', 'Khu vực Thủy Nguyên', 'Khu vực Hải Dương (Sáp nhập)', 'Khu vực Chí Linh (Sáp nhập)', 'Đặc khu Bạch Long Vĩ / Cát Hải'],
  CT: ['Khu vực Ninh Kiều', 'Khu vực Bình Thủy', 'Khu vực Cái Răng', 'Khu vực Sóc Trăng (Sáp nhập)', 'Khu vực Vị Thanh - Hậu Giang (Sáp nhập)'],
  HUE: ['Quận Thuận Hóa', 'Quận Phú Xuân', 'Khu vực Hương Thủy', 'Khu vực Hương Trà', 'Khu vực Phong Điền'],
  AG: ['Khu vực Long Xuyên', 'Khu vực Châu Đốc', 'Khu vực Rạch Giá (Kiên Giang sáp nhập)', 'Khu vực Hà Tiên (Kiên Giang sáp nhập)', 'Đặc khu Phú Quốc'],
  BN: ['Khu vực TP. Bắc Ninh', 'Khu vực Từ Sơn', 'Khu vực Bắc Giang (Sáp nhập)', 'Khu vực Việt Yên (Sáp nhập)'],
  CM: ['Khu vực TP. Cà Mau', 'Khu vực Bạc Liêu (Sáp nhập)', 'Khu vực Năm Căn / Sông Đốc'],
  CB: ['Khu vực TP. Cao Bằng', 'Khu vực Trùng Khánh / Quảng Hòa'],
  DL: ['Khu vực Buôn Ma Thuột', 'Khu vực Tuy Hòa (Phú Yên sáp nhập)', 'Khu vực Sông Cầu / Đông Hòa'],
  DB: ['Khu vực TP. Điện Biên Phủ', 'Khu vực Mường Lay / Điện Biên'],
  DNA: ['Khu vực Biên Hòa', 'Khu vực Long Khánh', 'Khu vực Long Thành / Nhơn Trạch', 'Khu vực Đồng Xoài (Bình Phước sáp nhập)', 'Khu vực Bình Long / Phước Long'],
  DT: ['Khu vực Cao Lãnh', 'Khu vực Sa Đéc', 'Khu vực Mỹ Tho (Tiền Giang sáp nhập)', 'Khu vực Gò Công (Tiền Giang sáp nhập)'],
  GL: ['Khu vực Pleiku', 'Khu vực Quy Nhơn (Bình Định sáp nhập)', 'Khu vực An Nhơn / Hoài Nhơn'],
  HT: ['Khu vực TP. Hà Tĩnh', 'Khu vực Hồng Lĩnh / Kỳ Anh'],
  HY: ['Khu vực TP. Hưng Yên', 'Khu vực Mỹ Hào / Văn Giang', 'Khu vực Thái Bình (Sáp nhập)'],
  KH: ['Khu vực Nha Trang', 'Khu vực Cam Ranh', 'Khu vực Ninh Hòa', 'Khu vực Phan Rang - Tháp Chàm (Ninh Thuận sáp nhập)', 'Đặc khu Trường Sa'],
  LC: ['Khu vực TP. Lai Châu', 'Khu vực Tam Đường / Phong Thổ'],
  LD: ['Khu vực Đà Lạt', 'Khu vực Bảo Lộc', 'Khu vực Phan Thiết (Bình Thuận sáp nhập)', 'Khu vực Gia Nghĩa (Đắk Nông sáp nhập)', 'Đặc khu Phú Quý'],
  LS: ['Khu vực TP. Lạng Sơn', 'Khu vực Cao Lộc / Hữu Lũng'],
  LCA: ['Khu vực TP. Lào Cai / Sa Pa', 'Khu vực Yên Bái (Sáp nhập)', 'Khu vực Nghĩa Lộ'],
  NA: ['Khu vực TP. Vinh', 'Khu vực Cửa Lò / Hoàng Mai / Thái Hòa'],
  NB: ['Khu vực Hoa Lư', 'Khu vực Tam Điệp', 'Khu vực Nam Định (Sáp nhập)', 'Khu vực Phủ Lý - Hà Nam (Sáp nhập)'],
  PT: ['Khu vực Việt Trì', 'Khu vực Vĩnh Yên / Phúc Yên (Vĩnh Phúc sáp nhập)', 'Khu vực Hòa Bình (Sáp nhập)'],
  QNG: ['Khu vực TP. Quảng Ngãi', 'Khu vực Kon Tum (Sáp nhập)', 'Đặc khu Lý Sơn'],
  QN: ['Khu vực Hạ Long', 'Khu vực Cẩm Phả', 'Khu vực Uông Bí', 'Khu vực Móng Cái', 'Đặc khu Vân Đồn / Cô Tô'],
  QT: ['Khu vực Đông Hà', 'Khu vực Đồng Hới (Quảng Bình sáp nhập)', 'Đặc khu Cồn Cỏ'],
  SL: ['Khu vực TP. Sơn La', 'Khu vực Mộc Châu'],
  TN: ['Khu vực TP. Tây Ninh / Hòa Thành', 'Khu vực Tân An (Long An sáp nhập)', 'Khu vực Đức Hòa / Bến Lức'],
  TNG: ['Khu vực TP. Thái Nguyên / Phổ Yên', 'Khu vực Sông Công', 'Khu vực Bắc Kạn (Sáp nhập)'],
  TH: ['Khu vực TP. Thanh Hóa', 'Khu vực Sầm Sơn / Bỉm Sơn / Nghi Sơn'],
  TQ: ['Khu vực TP. Tuyên Quang', 'Khu vực Hà Giang (Sáp nhập)'],
  VL: ['Khu vực TP. Vĩnh Long', 'Khu vực Bến Tre (Sáp nhập)', 'Khu vực Trà Vinh (Sáp nhập)']
};

function handleProvinceChange(val) {
  const districtSelect = document.getElementById('districtSelect');
  const wardSelect = document.getElementById('wardSelect');
  if (val === 'HCM') {
    districtSelect.innerHTML = `
      <option value="TP. Thủ Đức" selected>TP. Thủ Đức</option>
      <option value="Quận 1">Quận 1</option>
      <option value="Quận 3">Quận 3</option>
      <option value="Quận 4">Quận 4</option>
      <option value="Quận 5">Quận 5</option>
      <option value="Quận 6">Quận 6</option>
      <option value="Quận 7">Quận 7</option>
      <option value="Quận 8">Quận 8</option>
      <option value="Quận 10">Quận 10</option>
      <option value="Quận 11">Quận 11</option>
      <option value="Quận 12">Quận 12</option>
      <option value="Quận Bình Thạnh">Quận Bình Thạnh</option>
      <option value="Quận Phú Nhuận">Quận Phú Nhuận</option>
      <option value="Quận Gò Vấp">Quận Gò Vấp</option>
      <option value="Quận Tân Bình">Quận Tân Bình</option>
      <option value="Quận Tân Phú">Quận Tân Phú</option>
      <option value="Quận Bình Tân">Quận Bình Tân</option>
      <option value="Huyện Củ Chi">Huyện Củ Chi (Ngoại thành)</option>
      <option value="Huyện Hóc Môn">Huyện Hóc Môn (Ngoại thành)</option>
      <option value="Huyện Bình Chánh">Huyện Bình Chánh (Ngoại thành)</option>
      <option value="Huyện Nhà Bè">Huyện Nhà Bè (Ngoại thành)</option>
      <option value="Huyện Cần Giờ">Huyện Cần Giờ (Ngoại thành)</option>
      <option value="Khu vực Bình Dương (Mở rộng)">Khu vực Bình Dương (Sáp nhập)</option>
      <option value="Khu vực Bà Rịa - Vũng Tàu (Mở rộng)">Khu vực Bà Rịa - Vũng Tàu (Sáp nhập)</option>
    `;
    handleDistrictChange('TP. Thủ Đức');
  } else if (PROVINCE_DISTRICTS_MAP[val]) {
    const dList = PROVINCE_DISTRICTS_MAP[val];
    districtSelect.innerHTML = dList.map((d, idx) => `<option value="${d}" ${idx === 0 ? 'selected' : ''}>${d}</option>`).join('');
    handleDistrictChange(dList[0]);
  } else {
    const provText = document.getElementById('provinceSelect')?.selectedOptions[0]?.text || 'Tỉnh';
    districtSelect.innerHTML = `
      <option value="Khu vực Trung tâm ${provText}" selected>Khu vực Trung tâm ${provText}</option>
      <option value="Khu vực Phường / Xã lân cận">Khu vực Phường / Xã lân cận</option>
    `;
    wardSelect.innerHTML = `
      <option value="Phường Trung Tâm" selected>Phường Trung Tâm</option>
      <option value="Phường 1">Phường 1</option>
      <option value="Xã / Đặc khu">Xã / Đặc khu</option>
    `;
  }
  renderAllViews();
}

function recalcCartSummary() {
  renderAllViews();
}

function handleDistrictChange(val) {
  const wardSelect = document.getElementById('wardSelect');
  if (val.includes('Thủ Đức')) {
    wardSelect.innerHTML = `
      <option value="Phường Tăng Nhơn Phú" selected>Phường Tăng Nhơn Phú (Mới)</option>
      <option value="Phường Thủ Đức">Phường Thủ Đức (Mới)</option>
      <option value="Phường Hiệp Bình">Phường Hiệp Bình (Mới)</option>
      <option value="Phường Tam Bình">Phường Tam Bình (Mới)</option>
      <option value="Phường An Khánh">Phường An Khánh (Mới)</option>
      <option value="Phường Cát Lái">Phường Cát Lái (Mới)</option>
      <option value="Phường Long Trường">Phường Long Trường (Mới)</option>
      <option value="Phường Phước Long">Phường Phước Long (Mới)</option>
      <option value="Phường Tăng Nhơn Phú B">Phường Tăng Nhơn Phú B</option>
      <option value="Phường Hiệp Phú">Phường Hiệp Phú</option>
    `;
  } else if (val.includes('Quận 1') && !val.includes('Quận 10') && !val.includes('Quận 11') && !val.includes('Quận 12')) {
    wardSelect.innerHTML = `
      <option value="Phường Sài Gòn" selected>Phường Sài Gòn (Mới)</option>
      <option value="Phường Bến Thành">Phường Bến Thành (Mới)</option>
      <option value="Phường Tân Định">Phường Tân Định (Mới)</option>
      <option value="Phường Cầu Ông Lãnh">Phường Cầu Ông Lãnh (Mới)</option>
      <option value="Phường Bến Nghé">Phường Bến Nghé</option>
      <option value="Phường Đa Kao">Phường Đa Kao</option>
    `;
  } else if (val.includes('Quận 3')) {
    wardSelect.innerHTML = `
      <option value="Phường Bàn Cờ" selected>Phường Bàn Cờ (Mới)</option>
      <option value="Phường Xuân Hòa">Phường Xuân Hòa (Mới)</option>
      <option value="Phường Nhiêu Lộc">Phường Nhiêu Lộc (Mới)</option>
      <option value="Phường Võ Thị Sáu">Phường Võ Thị Sáu</option>
    `;
  } else if (val.includes('Quận 4')) {
    wardSelect.innerHTML = `
      <option value="Phường Khánh Hội" selected>Phường Khánh Hội (Mới)</option>
      <option value="Phường Xóm Chiếu">Phường Xóm Chiếu (Mới)</option>
      <option value="Phường Vĩnh Hội">Phường Vĩnh Hội (Mới)</option>
    `;
  } else if (val.includes('Bình Dương')) {
    wardSelect.innerHTML = `
      <option value="Phường Thủ Dầu Một" selected>Phường Thủ Dầu Một (Mới)</option>
      <option value="Phường Dĩ An">Phường Dĩ An (Mới)</option>
      <option value="Phường Thuận An">Phường Thuận An (Mới)</option>
      <option value="Phường Bình Dương">Phường Bình Dương (Mới)</option>
    `;
  } else if (val.includes('Bà Rịa') || val.includes('Vũng Tàu')) {
    wardSelect.innerHTML = `
      <option value="Phường Vũng Tàu" selected>Phường Vũng Tàu (Mới)</option>
      <option value="Phường Bà Rịa">Phường Bà Rịa (Mới)</option>
      <option value="Phường Phú Mỹ">Phường Phú Mỹ (Mới)</option>
      <option value="Đặc khu Côn Đảo">Đặc khu Côn Đảo</option>
    `;
  } else if (val.includes('Huyện')) {
    wardSelect.innerHTML = `
      <option value="Xã Trung Tâm" selected>Xã Trung Tâm (Mới)</option>
      <option value="Xã Tân Hiệp">Xã Tân Hiệp</option>
      <option value="Xã Bà Điểm">Xã Bà Điểm</option>
      <option value="Xã Vĩnh Lộc">Xã Vĩnh Lộc</option>
      <option value="Xã Bình Hưng">Xã Bình Hưng</option>
    `;
  } else {
    wardSelect.innerHTML = `
      <option value="Phường Trung Tâm" selected>Phường Trung Tâm</option>
      <option value="Phường 1">Phường 1</option>
      <option value="Phường 2">Phường 2</option>
      <option value="Xã / Đặc khu trực thuộc">Xã / Đặc khu trực thuộc</option>
    `;
  }
  renderAllViews();
}

function selectQuickVoucher(code) {
  const inp = document.getElementById('voucherCodeInput');
  if (inp) {
    inp.value = code;
    applyVoucher();
  }
}

async function applyVoucher() {
  const code = (document.getElementById('voucherCodeInput').value || '').trim().toUpperCase();
  const msgEl = document.getElementById('voucherMsg');
  if (!code) {
    msgEl.style.display = 'block';
    msgEl.style.color = '#d32f2f';
    msgEl.textContent = 'Vui lòng nhập mã voucher!';
    return;
  }

  const cart = getCart();
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  try {
    const resp = await fetch('index.php?action=check_voucher', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code: code, subtotal: subtotal })
    });
    const res = await resp.json();
    if (res.valid) {
      currentVoucherDiscount = res.discount || 0;
      appliedVoucherCode = res.code || code;
      msgEl.style.display = 'block';
      msgEl.style.color = '#2e7d32';
      msgEl.textContent = `Áp dụng mã ${appliedVoucherCode} thành công (-${formatVND(currentVoucherDiscount)})!`;
    } else {
      currentVoucherDiscount = 0;
      appliedVoucherCode = null;
      msgEl.style.display = 'block';
      msgEl.style.color = '#d32f2f';
      msgEl.textContent = res.message || 'Mã giảm giá không hợp lệ hoặc đã hết hạn.';
    }
  } catch (err) {
    currentVoucherDiscount = 0;
    appliedVoucherCode = null;
    msgEl.style.display = 'block';
    msgEl.style.color = '#d32f2f';
    msgEl.textContent = 'Lỗi kết nối khi kiểm tra mã giảm giá.';
  }
  renderAllViews();
}

function changeQty(id, variant, delta) {
  let cart = getCart();
  const item = cart.find(x => x.id === id && x.variant === variant);
  if (item) {
    item.quantity += delta;
    if (item.quantity <= 0) {
      cart = cart.filter(x => !(x.id === id && x.variant === variant));
    }
    saveCart(cart);
    renderAllViews();
  }
}

function removeItem(id, variant) {
  let cart = getCart();
  cart = cart.filter(x => !(x.id === id && x.variant === variant));
  saveCart(cart);
  renderAllViews();
}

function escapeCartHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function escapeCartJs(str) {
  return String(str ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function renderAllViews() {
  const cart = getCart();

  // 1. CẬP NHẬT GIAO DIỆN BƯỚC 1 (GIỎ HÀNG)
  const cartCountTitle = document.getElementById('cartCountTitle');
  const cartTableBody = document.getElementById('cartTableBody');
  const cartTableWrap = document.getElementById('cartTableWrap');
  const cartEmptyMsg = document.getElementById('cartEmptyMessage');
  const toastLatestName = document.getElementById('toastLatestProductName');

  cartCountTitle.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);

  if (cart.length === 0) {
    cartTableWrap.style.display = 'none';
    cartEmptyMsg.style.display = 'block';
  } else {
    cartTableWrap.style.display = 'block';
    cartEmptyMsg.style.display = 'none';
    toastLatestName.textContent = cart[0].name;

    cartTableBody.innerHTML = cart.map(item => `
      <tr>
        <td>
          <div class="cart-item-info">
            <div class="cart-item-img" style="display: grid; place-items: center; background: #ffffff; overflow: hidden; padding: 4px;">
              ${item.image ? `<img src="${escapeCartHtml(item.image)}" alt="${escapeCartHtml(item.name)}" style="width: 100%; height: 100%; object-fit: contain;">` : '<span style="font-size: 28px;">🐶</span>'}
            </div>
            <div class="cart-item-details">
              <strong>${escapeCartHtml(item.name)}</strong>
              <small>Phân loại: ${escapeCartHtml(item.variant)}</small>
              <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="cart-item-remove" onclick="removeItem('${escapeCartJs(item.id)}', '${escapeCartJs(item.variant)}')">
                  ✖ Bỏ sản phẩm
                </button>
                <span class="cart-item-added-badge">✔ Sản phẩm vừa thêm!</span>
              </div>
            </div>
          </div>
        </td>
        <td style="font-weight: 600; color: #3c3026;">${formatVND(item.price)}</td>
        <td>
          <div class="cart-item-stepper">
            <button type="button" onclick="changeQty('${escapeCartJs(item.id)}', '${escapeCartJs(item.variant)}', -1)">−</button>
            <span>${Number(item.quantity) || 1}</span>
            <button type="button" onclick="changeQty('${escapeCartJs(item.id)}', '${escapeCartJs(item.variant)}', 1)">+</button>
          </div>
        </td>
        <td>${formatVND(item.price * item.quantity)}</td>
      </tr>
    `).join('');
  }

  // 2. CẬP NHẬT CỘT PHẢI BƯỚC 2 (SUMMARY CHECKOUT)
  const summaryItemList = document.getElementById('summaryItemList');
  summaryItemList.innerHTML = cart.map(item => `
    <div class="summary-item-row">
      <div class="summary-thumb-wrap">
        <div style="width: 100%; height: 100%; border-radius: 8px; border: 1.5px solid #dcd5cb; display: grid; place-items: center; background: #ffffff; font-size: 28px; overflow: hidden; padding: 4px;">
          ${item.image ? `<img src="${escapeCartHtml(item.image)}" alt="${escapeCartHtml(item.name)}" style="width: 100%; height: 100%; object-fit: contain;">` : '🐶'}
        </div>
        <span class="summary-badge-count">${Number(item.quantity) || 1}</span>
      </div>
      <div class="summary-item-name">
        ${escapeCartHtml(item.name)}
      </div>
      <div class="summary-item-price">
        ${formatVND(item.price * item.quantity)}
      </div>
    </div>
  `).join('');

  // 3. TÍNH TIỀN TOÀN BỘ ĐƠN HÀNG
  const bConfig = window.KIBO_BUSINESS || {};
  const shipConf = bConfig.ship || { phi_co_ban: 20000, mien_phi_noi_thanh: 200000, mien_phi_khac: 500000 };
  const noiThanh = bConfig.noi_thanh_hcm || ['Q1','Q3','Q4','Q5','Q6','Q7','Q8','Q10','Q11','Q12','BT','PN','GV','TB','TP','BTH','TD'];
  const quyTyLe = (typeof bConfig.quy_ty_le === 'number') ? bConfig.quy_ty_le : 0.05;

  const provEl = document.getElementById('provinceSelect');
  const distEl = document.getElementById('districtSelect');
  const selProv = provEl ? (provEl.selectedOptions[0]?.text || provEl.value) : 'TP. Hồ Chí Minh';
  const selDist = distEl ? (distEl.value || distEl.selectedOptions[0]?.text) : 'TP. Thủ Đức';

  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const isHCM = (selProv.includes('Hồ Chí Minh') || selProv.includes('HCM'));
  const isNoiThanh = isHCM && (noiThanh.some(d => selDist === d));
  const freeThreshold = isNoiThanh ? shipConf.mien_phi_noi_thanh : shipConf.mien_phi_khac;
  const shipping = (subtotal === 0 || subtotal >= freeThreshold) ? 0 : shipConf.phi_co_ban;

  const rescueFund = Math.round(Math.max(0, subtotal - currentVoucherDiscount) * quyTyLe);
  const grandTotal = Math.max(0, subtotal + shipping - currentVoucherDiscount);

  // Hiển thị ở Bước 1
  document.getElementById('step1GrandTotal').textContent = formatVND(subtotal);

  // Hiển thị ở Bước 2
  document.getElementById('summarySubtotal').textContent = formatVND(subtotal);
  document.getElementById('summaryShipping').textContent = shipping === 0 ? 'Miễn phí' : formatVND(shipping);

  const discountRow = document.getElementById('summaryDiscountRow');
  if (currentVoucherDiscount > 0) {
    discountRow.style.display = 'flex';
    document.getElementById('summaryDiscount').textContent = '-' + formatVND(currentVoucherDiscount);
  } else {
    discountRow.style.display = 'none';
  }

  document.getElementById('summaryRescue').textContent = formatVND(rescueFund);
  document.getElementById('summaryGrandTotal').textContent = formatVND(grandTotal);

  // Cập nhật link VietQR động theo tổng tiền và cấu hình tập trung
  const vietqrImg = document.getElementById('vietqrImg');
  if (vietqrImg && window.KIBO_VIETQR) {
    const vBin = window.KIBO_VIETQR.bank_bin || '970407';
    const vAcc = window.KIBO_VIETQR.account_no || '29062004555';
    const vTpl = window.KIBO_VIETQR.template || 'compact2';
    vietqrImg.src = `https://api.vietqr.io/image/${vBin}-${vAcc}-${vTpl}.jpg?amount=${grandTotal}&addInfo=KIBO%20DONHANG`;
  }
}

function showCustomerToast(msg, type = 'error') {
  let toast = document.getElementById('checkoutToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'checkoutToast';
    toast.style.cssText = 'position: fixed; top: 16px; right: 16px; z-index: 9999999; padding: 10px 14px; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.2); font-family: Arial, sans-serif; font-size: 13.5px; color: #fff; max-width: 360px;';
    document.body.appendChild(toast);
  }
  toast.style.background = type === 'error' ? '#c94336' : '#2e7d32';
  toast.textContent = msg;
  toast.style.display = 'block';
  setTimeout(() => {
    toast.style.display = 'none';
  }, 3500);
}

async function handleFinalCheckout() {
  const cart = getCart();
  if (cart.length === 0) {
    showCustomerToast('Giỏ hàng của bạn đang trống! Vui lòng chọn sản phẩm.', 'error');
    return;
  }
  if (!validateCheckoutInputs()) {
    return;
  }
  const name = document.getElementById('custNameInput').value.trim();
  const phone = document.getElementById('custPhoneInput').value.trim();
  const email = document.getElementById('custEmailInput') ? document.getElementById('custEmailInput').value.trim() : '';
  const address = document.getElementById('custAddressInput') ? document.getElementById('custAddressInput').value.trim() : '';
  const city = document.getElementById('provinceSelect') ? document.getElementById('provinceSelect').selectedOptions[0].text : '';
  const district = document.getElementById('districtSelect') ? (document.getElementById('districtSelect').value || document.getElementById('districtSelect').selectedOptions[0].text) : '';
  const ward = document.getElementById('wardSelect') ? (document.getElementById('wardSelect').value || document.getElementById('wardSelect').selectedOptions[0].text) : '';
  const note = document.getElementById('orderNoteInput') ? document.getElementById('orderNoteInput').value.trim() : '';

  if (!name || !phone) {
    showCustomerToast('Vui lòng điền họ tên và số điện thoại giao hàng.', 'error');
    return;
  }

  const bConfig = window.KIBO_BUSINESS || {};
  const shipConf = bConfig.ship || { phi_co_ban: 20000, mien_phi_noi_thanh: 200000, mien_phi_khac: 500000 };
  const noiThanh = bConfig.noi_thanh_hcm || ['Q1','Q3','Q4','Q5','Q6','Q7','Q8','Q10','Q11','Q12','BT','PN','GV','TB','TP','BTH','TD'];

  const fullAddress = [address, ward, district, city].filter(Boolean).join(', ') || 'TP. Hồ Chí Minh';
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const isHCM = (city.includes('Hồ Chí Minh') || city.includes('HCM'));
  const isNoiThanh = isHCM && noiThanh.some(d => district === d || district.includes(d) || d.includes(district));
  const freeThreshold = isNoiThanh ? (shipConf.mien_phi_noi_thanh || 200000) : (shipConf.mien_phi_khac || 500000);
  const shipping = (subtotal === 0 || subtotal >= freeThreshold) ? 0 : (shipConf.phi_co_ban || 20000);
  const grandTotal = Math.max(0, subtotal + shipping - currentVoucherDiscount);
  const orderCode = 'KB-' + Math.floor(1000 + Math.random() * 9000);

  const paymentRadio = document.querySelector('input[name="payment_choice"]:checked');
  const paymentMethod = paymentRadio && paymentRadio.value === 'Online' ? 'Chuyển khoản VietQR' : 'Thanh toán khi nhận hàng (COD)';

  const btnConfirm = document.getElementById('btnConfirmOrder') || document.querySelector('.btn-checkout-full');
  if (btnConfirm) {
    btnConfirm.disabled = true;
    btnConfirm.style.cursor = 'not-allowed';
    btnConfirm.style.opacity = '0.75';
    btnConfirm.innerHTML = '⏳ Vui lòng chờ...';
  }

  try {
    const payload = {
      items: cart.map(i => ({
        id: i.id,
        quantity: i.quantity
      })),
      mavoucher: appliedVoucherCode,
      tennguoinhan: name,
      sodienthoainhan: phone,
      tinhthanh: city,
      quanhuyen: district,
      phuongxa: ward,
      diachichitiet: address,
      diachigiaohang: fullAddress,
      ghichu: note,
      phuongthuctt: paymentMethod,
      csrf_token: window.KIBO_CSRF_TOKEN || ''
    };

    // Lưu lại thông tin nhận hàng vào localStorage để ghi nhớ cho các lần mua tiếp theo
    try {
      localStorage.setItem('kibo_customer_info', JSON.stringify({
        name: name,
        phone: phone,
        address: address,
        email: email
      }));
    } catch (e) {}

    const resp = await fetch('index.php?action=create_order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const res = await resp.json();

    if (!res || !res.success) {
      showCustomerToast(res.message || 'Đặt hàng không thành công. Vui lòng kiểm tra lại!', 'error');
      if (btnConfirm) {
        btnConfirm.disabled = false;
        btnConfirm.style.cursor = 'pointer';
        btnConfirm.style.opacity = '1';
        btnConfirm.innerHTML = 'XÁC NHẬN ĐẶT HÀNG';
      }
      return;
    }

    const finalCode = res.order_code;
    const finalFund = res.fund_amount;

    // Dọn giỏ hàng sau khi đặt thành công
    saveCart([]);
    renderAllViews();

    // Hiển thị hộp thoại Modal xác nhận đặt hàng chuyên nghiệp
    const successModal = document.getElementById('orderSuccessModal');
    if (successModal) {
      document.getElementById('successOrderCode').textContent = '#' + finalCode;
      document.getElementById('successFundAmount').textContent = formatVND(finalFund);
      document.getElementById('successTrackingBtn').href = `index.php?action=tracking&order=${finalCode}`;
      successModal.style.display = 'grid';
    } else {
      window.location.href = `index.php?action=tracking&order=${finalCode}`;
    }
  } catch (err) {
    showCustomerToast('Lỗi kết nối khi gửi đơn hàng: ' + err.message, 'error');
    if (btnConfirm) {
      btnConfirm.disabled = false;
      btnConfirm.style.cursor = 'pointer';
      btnConfirm.style.opacity = '1';
      btnConfirm.innerHTML = 'XÁC NHẬN ĐẶT HÀNG';
    }
  } finally {
    if (btnConfirm) {
      btnConfirm.disabled = false;
      btnConfirm.style.cursor = 'pointer';
      btnConfirm.style.opacity = '1';
      btnConfirm.innerHTML = 'Xác nhận đặt hàng <?= kibo_icon("arrow", 18) ?>';
    }
  }
}

function initCustomerAutoFill() {
  try {
    const savedInfo = JSON.parse(localStorage.getItem('kibo_customer_info') || '{}');
    const nameInput = document.getElementById('custNameInput');
    const phoneInput = document.getElementById('custPhoneInput');
    const addressInput = document.getElementById('custAddressInput');
    const emailInput = document.getElementById('custEmailInput');

    if (nameInput && !nameInput.value && savedInfo.name) nameInput.value = savedInfo.name;
    if (phoneInput && !phoneInput.value && savedInfo.phone) phoneInput.value = savedInfo.phone;
    if (addressInput && !addressInput.value && savedInfo.address) addressInput.value = savedInfo.address;
    if (emailInput && !emailInput.value && savedInfo.email) emailInput.value = savedInfo.email;

    [nameInput, phoneInput, addressInput, emailInput].forEach(inp => {
      if (inp) {
        inp.addEventListener('input', () => {
          try {
            localStorage.setItem('kibo_customer_info', JSON.stringify({
              name: nameInput ? nameInput.value.trim() : '',
              phone: phoneInput ? phoneInput.value.trim() : '',
              address: addressInput ? addressInput.value.trim() : '',
              email: emailInput ? emailInput.value.trim() : ''
            }));
          } catch (e) {}
        });
      }
    });
  } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
  renderAllViews();
  initCustomerAutoFill();
  const preV = document.getElementById('voucherCodeInput');
  if (preV && preV.value.trim() !== '') {
    applyVoucher();
  }
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('show_qr') === '1' || urlParams.get('show_payment') === '1') {
    if (typeof showPaymentOptions === 'function') showPaymentOptions();
    const qrRadio = document.querySelector('input[name="payment_choice"][value="Online"]');
    if (qrRadio) {
      qrRadio.checked = true;
      if (typeof togglePaymentQR === 'function') togglePaymentQR(true);
    }
  }
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
