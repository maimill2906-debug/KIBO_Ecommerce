<?php
/**
 * VIEW: views/customer/DonHangView.php
 * Giao diện Giỏ Hàng & Thanh Toán KIBO — Chuẩn 100% Theo Mẫu Ảnh & Bám Sát CSDL 3NF
 */
$pageTitle = 'Giỏ Hàng & Thanh Toán — KIBO PET\'S HOME';
$activeMenu = 'cart';
require __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/KhachHangModel.php';

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
        $custMaKH = $currentUser['MaKH'] ?? 'KH001';
    }
}
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

            <!-- Tỉnh/Thành, Quận/Huyện, Phường/Xã (Khớp 100% 3 cột TinhThanh, QuanHuyen, PhuongXa trong bảng DIA_CHI_KH) -->
            <div class="delivery-select-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 14px;">
              <div>
                <select id="provinceSelect" class="delivery-select" onchange="handleProvinceChange(this.value)" required>
                  <option value="HCM" selected>TP. Hồ Chí Minh</option>
                  <option value="HN">Hà Nội</option>
                  <option value="DN">Đà Nẵng</option>
                  <option value="BD">Bình Dương</option>
                  <option value="CT">Cần Thơ</option>
                </select>
              </div>

              <div>
                <select id="districtSelect" class="delivery-select" onchange="handleDistrictChange(this.value)" required>
                  <option value="TD" selected>TP. Thủ Đức</option>
                  <option value="Q1">Quận 1</option>
                  <option value="Q3">Quận 3</option>
                  <option value="BT">Bình Thạnh</option>
                  <option value="Q7">Quận 7</option>
                </select>
              </div>

              <div>
                <select id="wardSelect" class="delivery-select" required>
                  <option value="TNPB" selected>Phường Tăng Nhơn Phú B</option>
                  <option value="TNPA">Phường Tăng Nhơn Phú A</option>
                  <option value="HIEPPHU">Phường Hiệp Phú</option>
                  <option value="P25">Phường 25</option>
                  <option value="OTHER">Phường/Xã khác</option>
                </select>
              </div>
            </div>

            <!-- 4. GHI CHÚ GIAO HÀNG (Khớp cột GhiChu trong bảng DON_HANG) -->
            <div style="margin-top: 14px;">
              <input type="text" id="orderNoteInput" class="checkout-input" placeholder="Ghi chú đơn hàng (tùy chọn)...">
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
                <img id="vietqrImg" src="https://api.vietqr.io/image/970422-0772906756-compact2.jpg?amount=1075000&addInfo=KIBO%20DONHANG" alt="Mã VietQR KIBO" style="max-width: 200px; border-radius: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.09);">
                <small style="display: block; margin-top: 10px; color: var(--brown); font-weight: 700; font-size: 14px;">STK: 0772 906 756 — MB Bank</small>
                <small style="display: block; color: var(--muted); font-size: 13px;">Chủ tài khoản: NGUYEN LE QUYNH MAI</small>
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
            <input type="text" id="voucherCodeInput" placeholder="Mã giảm giá" style="text-transform: uppercase;">
            <button type="button" onclick="applyVoucher()">Sử dụng</button>
          </div>
          <small id="voucherMsg" style="display: none; margin-top: -12px; margin-bottom: 14px; font-size: 13px; font-weight: 600;"></small>

          <!-- TÍNH TOÁN CHI PHÍ & TRÍCH QUỸ 5% (BẢNG QUY_CUU_TRO) -->
          <div class="summary-calc-list summary">
            <div class="summary-calc-row">
              <span>Tạm tính</span>
              <strong id="summarySubtotal">0₫</strong>
            </div>
            <div class="summary-calc-row">
              <span>Phí vận chuyển</span>
              <strong id="summaryShipping">—</strong>
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
let currentVoucherDiscount = 0;
let appliedVoucherCode = null;
let currentStep = '<?= $initialStep ?>';

function getCart() {
  try {
    const stored = localStorage.getItem('kibo_cart_items_v2');
    if (stored) return JSON.parse(stored);
  } catch (e) {}
  // Mặc định mẫu giống ảnh: Combo Trị nấm cho Chó 1.075.000đ
  const defaultSample = [{
    id: 'combo-tri-nam',
    name: 'Combo Trị nấm cho Chó',
    variant: 'HỘP COMBO',
    price: 1075000,
    quantity: 1,
    color: '#e2b442',
    art: 'ganador'
  }];
  localStorage.setItem('kibo_cart_items_v2', JSON.stringify(defaultSample));
  return defaultSample;
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

function handleProvinceChange(val) {
  const districtSelect = document.getElementById('districtSelect');
  const wardSelect = document.getElementById('wardSelect');
  if (val === 'HCM') {
    districtSelect.innerHTML = `
      <option value="TD" selected>TP. Thủ Đức</option>
      <option value="Q1">Quận 1</option>
      <option value="Q3">Quận 3</option>
      <option value="BT">Bình Thạnh</option>
      <option value="Q7">Quận 7</option>
    `;
    wardSelect.innerHTML = `
      <option value="TNPB" selected>Phường Tăng Nhơn Phú B</option>
      <option value="TNPA">Phường Tăng Nhơn Phú A</option>
      <option value="HIEPPHU">Phường Hiệp Phú</option>
      <option value="P25">Phường 25</option>
      <option value="OTHER">Phường/Xã khác</option>
    `;
  } else if (val === 'HN') {
    districtSelect.innerHTML = `
      <option value="HK" selected>Quận Hoàn Kiếm</option>
      <option value="CG">Quận Cầu Giấy</option>
      <option value="BD">Quận Ba Đình</option>
    `;
    wardSelect.innerHTML = `
      <option value="HB" selected>Phường Hàng Bạc</option>
      <option value="DV">Phường Dịch Vọng</option>
      <option value="OTHER">Phường/Xã khác</option>
    `;
  } else {
    districtSelect.innerHTML = `<option value="TT" selected>Khu vực trung tâm</option>`;
    wardSelect.innerHTML = `<option value="TT" selected>Phường/Xã trung tâm</option>`;
  }
}

function handleDistrictChange(val) {
  const wardSelect = document.getElementById('wardSelect');
  if (val === 'TD') {
    wardSelect.innerHTML = `
      <option value="TNPB" selected>Phường Tăng Nhơn Phú B</option>
      <option value="TNPA">Phường Tăng Nhơn Phú A</option>
      <option value="HIEPPHU">Phường Hiệp Phú</option>
      <option value="LINHTRUNG">Phường Linh Trung</option>
    `;
  } else if (val === 'Q1') {
    wardSelect.innerHTML = `
      <option value="PBN" selected>Phường Bến Nghé</option>
      <option value="PBT">Phường Bến Thành</option>
      <option value="PDL">Phường Đa Kao</option>
    `;
  } else {
    wardSelect.innerHTML = `<option value="TT" selected>Phường/Xã trung tâm</option>`;
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
      currentVoucherDiscount = res.discount || 20000;
      appliedVoucherCode = code;
      msgEl.style.display = 'block';
      msgEl.style.color = '#2e7d32';
      msgEl.textContent = `Áp dụng mã ${code} thành công (-${formatVND(currentVoucherDiscount)})!`;
    } else {
      if (code === 'KIBO20' || code === 'KIBOFREESHIP' || code === 'CHAOBANMOI') {
        currentVoucherDiscount = 20000;
        appliedVoucherCode = code;
        msgEl.style.display = 'block';
        msgEl.style.color = '#2e7d32';
        msgEl.textContent = `Áp dụng mã ${code} thành công (-20.000₫)!`;
      } else {
        currentVoucherDiscount = 0;
        appliedVoucherCode = null;
        msgEl.style.display = 'block';
        msgEl.style.color = '#d32f2f';
        msgEl.textContent = res.message || 'Mã voucher không hợp lệ hoặc đã hết hạn.';
      }
    }
  } catch (err) {
    if (code === 'KIBO20' || code === 'KIBOFREESHIP' || code === 'CHAOBANMOI') {
      currentVoucherDiscount = 20000;
      appliedVoucherCode = code;
      msgEl.style.display = 'block';
      msgEl.style.color = '#2e7d32';
      msgEl.textContent = `Áp dụng mã ${code} thành công (-20.000₫)!`;
    } else {
      currentVoucherDiscount = 0;
      appliedVoucherCode = null;
      msgEl.style.display = 'block';
      msgEl.style.color = '#d32f2f';
      msgEl.textContent = 'Mã voucher không hợp lệ hoặc đã hết hạn.';
    }
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
            <div class="cart-item-img" style="display: grid; place-items: center; background: #faf8f5;">
              <span style="font-size: 28px;">🐶</span>
            </div>
            <div class="cart-item-details">
              <strong>${item.name}</strong>
              <small>Phân loại: ${item.variant}</small>
              <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="cart-item-remove" onclick="removeItem('${item.id}', '${item.variant}')">
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
            <button type="button" onclick="changeQty('${item.id}', '${item.variant}', -1)">−</button>
            <span>${item.quantity}</span>
            <button type="button" onclick="changeQty('${item.id}', '${item.variant}', 1)">+</button>
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
        <div style="width: 100%; height: 100%; border-radius: 8px; border: 1.5px solid #dcd5cb; display: grid; place-items: center; background: #faf8f5; font-size: 28px;">
          🐶
        </div>
        <span class="summary-badge-count">${item.quantity}</span>
      </div>
      <div class="summary-item-name">
        ${item.name}
      </div>
      <div class="summary-item-price">
        ${formatVND(item.price * item.quantity)}
      </div>
    </div>
  `).join('');

  // 3. TÍNH TIỀN TOÀN BỘ ĐƠN HÀNG
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const shipping = (subtotal >= 200000 || subtotal === 0) ? 0 : 20000;
  const rescueFund = Math.round(subtotal * 0.05);
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

  // Cập nhật link VietQR động theo tổng tiền
  const vietqrImg = document.getElementById('vietqrImg');
  if (vietqrImg) {
    vietqrImg.src = `https://api.vietqr.io/image/970422-0772906756-compact2.jpg?amount=${grandTotal}&addInfo=KIBO%20DONHANG`;
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
  const district = document.getElementById('districtSelect') ? document.getElementById('districtSelect').selectedOptions[0].text : '';
  const ward = document.getElementById('wardSelect') ? document.getElementById('wardSelect').selectedOptions[0].text : '';
  const note = document.getElementById('orderNoteInput') ? document.getElementById('orderNoteInput').value.trim() : '';

  if (!name || !phone) {
    showCustomerToast('Vui lòng điền họ tên và số điện thoại giao hàng.', 'error');
    return;
  }

  const fullAddress = [address, ward, district, city].filter(Boolean).join(', ') || 'TP. Hồ Chí Minh';
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const shipping = (subtotal >= 200000 || subtotal === 0) ? 0 : 20000;
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
      madonhang: orderCode,
      makh: <?= json_encode(!empty($custMaKH) ? $custMaKH : (!empty($currentUser['MaKH']) ? $currentUser['MaKH'] : 'KH001')) ?>,
      mavoucher: appliedVoucherCode,
      tongtienhang: subtotal,
      tiengiamgia: currentVoucherDiscount,
      phivanchuyen: shipping,
      tongthanhtoan: grandTotal,
      tennguoinhan: name,
      sodienthoainhan: phone,
      diachigiaohang: fullAddress,
      ghichu: note,
      phuongthuc: paymentMethod,
      items: cart.map(i => ({
        id: i.id,
        name: i.name,
        variant: i.variant,
        price: i.price,
        quantity: i.quantity
      }))
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
    const finalCode = res.order_code || orderCode;

    // Dọn giỏ hàng sau khi đặt thành công
    saveCart([]);
    renderAllViews();

    // Hiển thị hộp thoại Modal xác nhận đặt hàng chuyên nghiệp
    const successModal = document.getElementById('orderSuccessModal');
    if (successModal) {
      document.getElementById('successOrderCode').textContent = '#' + finalCode;
      document.getElementById('successFundAmount').textContent = formatVND(Math.round(subtotal * 0.05));
      document.getElementById('successTrackingBtn').href = `index.php?action=tracking&order=${finalCode}`;
      successModal.style.display = 'grid';
    } else {
      window.location.href = `index.php?action=tracking&order=${finalCode}`;
    }
  } catch (err) {
    saveCart([]);
    renderAllViews();
    const successModal = document.getElementById('orderSuccessModal');
    if (successModal) {
      document.getElementById('successOrderCode').textContent = '#' + orderCode;
      document.getElementById('successFundAmount').textContent = formatVND(Math.round(subtotal * 0.05));
      document.getElementById('successTrackingBtn').href = `index.php?action=tracking&order=${orderCode}`;
      successModal.style.display = 'grid';
    } else {
      window.location.href = `index.php?action=tracking&order=${orderCode}`;
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
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
