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
$custProvince = '';
$custWard = '';
$custMaKH = '';

if (!empty($currentUser)) {
    $identifier = $currentUser['TenDangNhap'] ?? ($currentUser['MaTK'] ?? ($currentUser['MaKH'] ?? ''));
    $freshCust = KhachHangModel::getCustomerDetail($identifier);
    if ($freshCust) {
        $custName = !empty($freshCust['HoTen']) ? $freshCust['HoTen'] : ($currentUser['HoTen'] ?? '');
        $custEmail = !empty($freshCust['Email']) ? $freshCust['Email'] : ($currentUser['TenDangNhap'] ?? '');
        $custPhone = !empty($freshCust['SoDienThoaiChinh']) ? $freshCust['SoDienThoaiChinh'] : ($currentUser['SoDienThoai'] ?? '');
        $custAddress = !empty($freshCust['diachichitiet']) ? $freshCust['diachichitiet'] : ($currentUser['DiaChi'] ?? '');
        $custProvince = !empty($freshCust['tinhthanh']) ? $freshCust['tinhthanh'] : ($currentUser['TinhThanh'] ?? '');
        $custWard = !empty($freshCust['phuongxa']) ? $freshCust['phuongxa'] : ($currentUser['PhuongXa'] ?? '');
        $custMaKH = $freshCust['MaKH'] ?? ($currentUser['MaKH'] ?? '');

        $_SESSION['user']['HoTen'] = $custName;
        $_SESSION['user']['SoDienThoai'] = $custPhone;
        $_SESSION['user']['DiaChi'] = $custAddress;
        $_SESSION['user']['TinhThanh'] = $custProvince;
        $_SESSION['user']['PhuongXa'] = $custWard;
        $_SESSION['user']['MaKH'] = $custMaKH;
        $currentUser = $_SESSION['user'];
    } else {
        $custName = $currentUser['HoTen'] ?? '';
        $custEmail = $currentUser['TenDangNhap'] ?? '';
        $custPhone = $currentUser['SoDienThoai'] ?? '';
        $custAddress = $currentUser['DiaChi'] ?? '';
        $custProvince = $currentUser['TinhThanh'] ?? '';
        $custWard = $currentUser['PhuongXa'] ?? '';
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

            <!-- Tỉnh/Thành phố & Phường/Xã/Đặc khu (Dữ liệu 34 Tỉnh/Thành & 3.321 Phường/Xã từ Supabase) -->
            <div class="delivery-select-grid" style="grid-template-columns: 1fr 1fr;">
              <div>
                <select id="provinceSelect" class="delivery-select" onchange="handleProvinceChange(this.value)" title="34 Tỉnh/Thành phố theo Nghị quyết 202/2025/QH15" required>
                  <option value="">-- Đang tải Tỉnh / Thành phố... --</option>
                </select>
              </div>

              <div>
                <select id="wardSelect" class="delivery-select" onchange="recalcCartSummary();" required>
                  <option value="">-- Chọn Phường / Xã / Đặc khu --</option>
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
                    <strong style="display: block; font-size: 15.5px; color: var(--brown);">Thanh toán khi nhận hàng (COD)</strong>
                    <small style="display: block; margin-top: 3px; color: var(--muted); font-size: 13.5px;">Kiểm tra hàng tận tay và thanh toán trực tiếp cho bưu tá shipper.</small>
                  </div>
                </label>

                <label style="display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1.5px solid #d4ccc3; border-radius: 8px; cursor: pointer; background: #ffffff;">
                  <input type="radio" name="payment_choice" value="Online" onchange="togglePaymentQR(true)" style="margin-top: 4px; accent-color: var(--green);">
                  <div>
                    <strong style="display: block; font-size: 15.5px; color: var(--brown);">Thanh toán qua VietQR</strong>
                    <small style="display: block; margin-top: 3px; color: var(--muted); font-size: 13.5px;">Quét mã QR thanh toán nhanh qua bất kỳ app ngân hàng nào (mã QR tự tạo ở bước kế tiếp).</small>
                  </div>
                </label>
              </div>

              <!-- GHI CHÚ KHI CHỌN VIETQR (KHÔNG HIỆN QR TRÙNG Ở BƯỚC NGOÀI) -->
              <div id="vietqrBox" style="display: none; margin-top: 14px; padding: 14px 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #334155;">
                💳 Hệ thống sẽ mở <strong>Cổng thanh toán VietQR</strong> kèm số tiền &amp; mã đơn hàng chính xác ngay sau khi bạn bấm <strong>Xác nhận đặt hàng</strong>.
              </div>

              <?php if (!empty($currentUser) && (($currentUser['VaiTro'] ?? '') === 'Admin')): ?>
              <div style="margin-top: 16px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; color: #b91c1c; font-size: 13.5px; font-weight: 500; line-height: 1.5;">
                Tài khoản Quản trị viên chỉ dùng để quản lý hệ thống, không hỗ trợ đặt hàng. Vui lòng chuyển sang tài khoản Khách hàng để tiếp tục.
              </div>
              <?php endif; ?>

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
            <div id="voucherPromoBox" style="margin: -6px 0 18px; padding: 12px 14px; background: #fbf9f6; border: 1px dashed #d6c7b8; border-radius: 8px;">
              <div style="font-size: 12.5px; font-weight: 700; color: #2c1a0e; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                <span>CHƯƠNG TRÌNH ƯU ĐÃI DÀNH CHO BẠN</span>
              </div>
              <div id="voucherPromoList" style="display: grid; gap: 8px;">
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
                        Đơn tối thiểu: <?= number_format($vMin, 0, ',', '.') ?>₫
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

<!-- CỔNG THANH TOÁN VIETQR TRỰC TUYẾN (CHỜ XÁC NHẬN THANH TOÁN) -->
<div id="vietqrGatewayModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px;">
  <div style="background: #ffffff; width: min(520px, 95vw); border-radius: 6px; border: 1px solid #c8c8c8; box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2); font-family: Arial, sans-serif; overflow: hidden;">
    <div style="background: #f5f5f5; color: #222; padding: 16px 22px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 11.5px; color: #666; text-transform: uppercase; letter-spacing: 0.4px;">Cổng thanh toán KIBO</div>
        <h3 style="margin: 2px 0 0; font-size: 17px; font-weight: 700; color: #222;">Thanh toán chuyển khoản VietQR</h3>
      </div>
      <div id="gwCountdown" style="background: #fff; border: 1px solid #d4d4d4; color: #222; padding: 5px 10px; border-radius: 4px; font-weight: 700; font-size: 13.5px;">15:00</div>
    </div>

    <div style="padding: 20px 24px; text-align: center;">
      <div id="gwStatusBanner" style="background: #fafafa; color: #333; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 14px; font-size: 13.5px; font-weight: 600; margin-bottom: 16px;">
        Đang chờ bạn quét mã QR và chuyển khoản đúng số tiền...
      </div>

      <img id="gwQrImg" src="" alt="Mã VietQR thanh toán" style="width: 220px; height: auto; border-radius: 6px; border: 1px solid #ddd; margin-bottom: 14px;">

      <div style="background: #fafafa; border: 1px solid #e5e5e5; border-radius: 4px; padding: 12px 16px; text-align: left; font-size: 13.5px; line-height: 1.7; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between;">
          <span style="color: #666;">Ngân hàng:</span>
          <strong id="gwBankName" style="color: #222;">MSB</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: #666;">Số tài khoản:</span>
          <strong id="gwAccountNo" style="color: #222;">--</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: #666;">Số tiền:</span>
          <strong id="gwAmount" style="color: #222; font-size: 15px;">0₫</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: #666;">Nội dung CK (bắt buộc):</span>
          <strong id="gwContent" style="color: #222;">KIBO ...</strong>
        </div>
      </div>

      <div id="gwBottomStatus" style="display: none; margin-bottom: 12px; padding: 10px 14px; background: #f5f5f5; border: 1px solid #333; border-radius: 4px; color: #222; font-size: 14px; font-weight: 700;">
        Thanh toán thành công! Đang chuyển hướng...
      </div>

      <div>
        <button type="button" id="gwCancelBtn" onclick="cancelOnlinePayment()" style="width: 100%; padding: 10px 16px; background: #f5f5f5; color: #333; border: 1px solid #ccc; border-radius: 4px; font-weight: 600; font-size: 14px; cursor: pointer;">
          Hủy giao dịch
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL XÁC NHẬN HỦY GIAO DỊCH (GIỮA MÀN HÌNH) -->
<div id="gwCancelConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000001; place-items: center; padding: 20px;">
  <div style="background: #ffffff; width: min(400px, 92vw); border-radius: 6px; border: 1px solid #d1d5db; box-shadow: 0 12px 32px rgba(0,0,0,0.22); padding: 22px 24px; font-family: Arial, sans-serif; text-align: center;">
    <h4 style="margin: 0 0 8px; font-size: 16px; font-weight: 700; color: #111827;">Xác nhận hủy giao dịch</h4>
    <p id="gwCancelConfirmText" style="margin: 0 0 18px; font-size: 13.5px; color: #4b5563; line-height: 1.5;">
      Bạn muốn hủy giao dịch thanh toán cho đơn này?
    </p>
    <div style="display: flex; gap: 10px; justify-content: center;">
      <button type="button" onclick="closeCancelConfirmModal()" style="flex: 1; padding: 9px 14px; background: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
        Quay lại
      </button>
      <button type="button" onclick="confirmCancelOnlinePayment()" style="flex: 1; padding: 9px 14px; background: #c94336; color: #ffffff; border: none; border-radius: 4px; font-size: 13.5px; font-weight: 700; cursor: pointer;">
        Xác nhận hủy
      </button>
    </div>
  </div>
</div>

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
              <td id="successOrderStatus" style="padding: 8px 0; text-align: right; font-weight: 600; color: #222;">Chờ xác nhận</td>
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
        💡 <strong>Lưu ý cho khách chưa đăng ký tài khoản:</strong> Thông tin và mã đơn hàng đã được gửi về Email của bạn. Bạn có thể tra cứu tiến trình giao hàng bất cứ lúc nào tại mục <strong>"Tra cứu đơn"</strong>.
      </div>
      <?php else: ?>
      <div style="margin: 0 0 20px; padding: 12px 16px; background: #f0fdf4; border: 1.5px dashed #86efac; border-radius: 6px; font-size: 14.5px; color: #166534; line-height: 1.6;">
        Giờ đây, bạn có thể theo dõi và tra cứu đơn hàng bất cứ lúc nào tại mục <strong>"Tài khoản &gt; Lịch sử đơn hàng"</strong> (hoặc kiểm tra qua Email xác nhận).
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
  const provinceEl = document.getElementById('provinceSelect');
  if (provinceEl && !provinceEl.value) {
    showCustomerToast('Vui lòng chọn Tỉnh / Thành phố giao hàng.', 'error');
    provinceEl.focus();
    return false;
  }
  const wardEl = document.getElementById('wardSelect');
  if (wardEl && !wardEl.value) {
    showCustomerToast('Vui lòng chọn Phường / Xã / Đặc khu giao hàng.', 'error');
    wardEl.focus();
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

// Danh mục 34 Tỉnh/Thành & 3.321 Phường/Xã/Đặc khu tải trực tiếp từ Supabase (vietnamese-provinces-database)
const WARDS_CACHE = {};

const SAVED_CHECKOUT_PROVINCE = <?= json_encode($custProvince, JSON_UNESCAPED_UNICODE) ?>;
const SAVED_CHECKOUT_WARD = <?= json_encode($custWard, JSON_UNESCAPED_UNICODE) ?>;

async function loadProvincesFromDB() {
  const provinceSelect = document.getElementById('provinceSelect');
  const wardSelect = document.getElementById('wardSelect');
  if (!provinceSelect) return;
  try {
    const res = await fetch('index.php?action=get_provinces');
    const data = await res.json();
    if (data.success && Array.isArray(data.provinces) && data.provinces.length > 0) {
      provinceSelect.innerHTML = '<option value="" selected>-- Chọn Tỉnh / Thành phố --</option>' +
        data.provinces.map(p =>
          `<option value="${escapeCartHtml(p.code)}">${escapeCartHtml(p.full_name)}</option>`
        ).join('');
      if (SAVED_CHECKOUT_PROVINCE) {
        const targetProv = SAVED_CHECKOUT_PROVINCE.trim().toLowerCase();
        const matchedProv = data.provinces.find(p =>
          (p.full_name || '').trim().toLowerCase() === targetProv ||
          (p.name || '').trim().toLowerCase() === targetProv
        );
        if (matchedProv) {
          provinceSelect.value = matchedProv.code;
          await handleProvinceChange(matchedProv.code, SAVED_CHECKOUT_WARD);
          return;
        }
      }
      if (wardSelect) {
        wardSelect.innerHTML = '<option value="" selected>-- Chọn Phường / Xã / Đặc khu --</option>';
      }
      renderAllViews();
    } else {
      provinceSelect.innerHTML = '<option value="" selected>-- Không thể tải Tỉnh / Thành phố --</option>';
    }
  } catch (err) {
    provinceSelect.innerHTML = '<option value="" selected>-- Lỗi tải Tỉnh / Thành phố --</option>';
  }
}

async function handleProvinceChange(provinceCode, preselectWard = '') {
  const wardSelect = document.getElementById('wardSelect');
  if (!wardSelect) return;
  if (!provinceCode) {
    wardSelect.innerHTML = '<option value="">-- Chọn Phường / Xã / Đặc khu --</option>';
    renderAllViews();
    return;
  }

  if (WARDS_CACHE[provinceCode]) {
    renderWardsOptions(WARDS_CACHE[provinceCode], preselectWard);
    renderAllViews();
    return;
  }

  wardSelect.innerHTML = '<option value="">-- Đang tải Phường / Xã... --</option>';
  try {
    const res = await fetch('index.php?action=get_wards&province_code=' + encodeURIComponent(provinceCode));
    const data = await res.json();
    if (data.success && Array.isArray(data.wards) && data.wards.length > 0) {
      WARDS_CACHE[provinceCode] = data.wards;
      renderWardsOptions(data.wards, preselectWard);
    } else {
      wardSelect.innerHTML = '<option value="">-- Không có dữ liệu Phường / Xã --</option>';
    }
  } catch (err) {
    wardSelect.innerHTML = '<option value="">-- Lỗi tải Phường / Xã --</option>';
  }
  renderAllViews();
}

function renderWardsOptions(wards, preselectWard = '') {
  const wardSelect = document.getElementById('wardSelect');
  if (!wardSelect) return;
  wardSelect.innerHTML = '<option value="" selected>-- Chọn Phường / Xã / Đặc khu --</option>' +
    wards.map(w =>
      `<option value="${escapeCartHtml(w.full_name)}">${escapeCartHtml(w.full_name)}</option>`
    ).join('');
  if (preselectWard) {
    const targetWard = preselectWard.trim().toLowerCase();
    const matchedWard = wards.find(w => (w.full_name || '').trim().toLowerCase() === targetWard);
    if (matchedWard) {
      wardSelect.value = matchedWard.full_name;
    }
  }
}

function recalcCartSummary() {
  renderAllViews();
}

document.addEventListener('DOMContentLoaded', () => {
  loadProvincesFromDB();
});

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
  const phone = (document.getElementById('custPhoneInput')?.value || '').trim();

  try {
    const resp = await fetch('index.php?action=check_voucher', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code: code, subtotal: subtotal, phone: phone })
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
  const wardEl = document.getElementById('wardSelect');
  const selProv = (provEl && provEl.value) ? (provEl.selectedOptions[0]?.text || '') : '';
  const selDist = distEl ? (distEl.value || distEl.selectedOptions[0]?.text || '') : '';
  const selWard = (wardEl && wardEl.value) ? (wardEl.selectedOptions[0]?.text || wardEl.value) : '';

  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const isHCM = (selProv.includes('Hồ Chí Minh') || selProv.includes('HCM'));
  const isNoiThanh = isHCM && (selDist ? noiThanh.some(d => selDist === d) : selWard.startsWith('Phường'));
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
  const isNoiThanh = isHCM && (district ? noiThanh.some(d => district === d || district.includes(d) || d.includes(district)) : ward.startsWith('Phường'));
  const freeThreshold = isNoiThanh ? (shipConf.mien_phi_noi_thanh || 200000) : (shipConf.mien_phi_khac || 500000);
  const shipping = (subtotal === 0 || subtotal >= freeThreshold) ? 0 : (shipConf.phi_co_ban || 20000);
  const grandTotal = Math.max(0, subtotal + shipping - currentVoucherDiscount);
  const orderCode = 'KB-' + Math.floor(1000 + Math.random() * 9000);

  if (<?= json_encode(!empty($currentUser) && (($currentUser['VaiTro'] ?? '') === 'Admin')) ?>) {
    showCustomerToast('Tài khoản Quản trị viên không hỗ trợ đặt hàng. Vui lòng dùng tài khoản Khách hàng.', 'error');
    return;
  }

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

    if (res.is_online) {
      // Nếu thanh toán Online (VietQR): Giữ nguyên giỏ hàng cho tới khi thanh toán thành công!
      openVietQRGatewayModal(finalCode, res.tongthanhtoan || grandTotal, finalFund, res.vietqr_url);
    } else {
      // Thanh toán COD: Xác nhận đơn ngay và dọn giỏ hàng
      saveCart([]);
      renderAllViews();
      showOrderSuccessModal(finalCode, finalFund, 'Chờ xác nhận (Thanh toán COD)');
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

async function refreshAvailableVouchers() {
  const box = document.getElementById('voucherPromoBox');
  const listEl = document.getElementById('voucherPromoList');
  if (!box || !listEl) return;
  const phone = (document.getElementById('custPhoneInput')?.value || '').trim();
  try {
    const r = await fetch('index.php?action=get_available_vouchers&phone=' + encodeURIComponent(phone));
    const d = await r.json();
    if (!d || !d.success) return;
    const list = Array.isArray(d.vouchers) ? d.vouchers : [];
    if (list.length === 0) {
      box.style.display = 'none';
      // Nếu đang áp dụng mã mà SĐT vừa nhập đã hết lượt, xóa mã đang áp dụng
      if (appliedVoucherCode) {
        currentVoucherDiscount = 0;
        appliedVoucherCode = null;
        const msgEl = document.getElementById('voucherMsg');
        if (msgEl) msgEl.style.display = 'none';
        renderAllViews();
      }
      return;
    }
    box.style.display = 'block';
    listEl.innerHTML = list.map(v => {
      const code = String(v.MaVoucher || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const name = String(v.TenVoucher || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const minV = Number(v.DonHangToiThieu || 0);
      return `
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; background: #ffffff; border: 1px solid #ebd9c8; border-radius: 6px;">
          <div style="min-width: 0;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span style="background: #c94336; color: #fff; font-size: 12px; font-weight: 800; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.5px;">${code}</span>
              <strong style="font-size: 13.5px; color: #2c1a0e;">${name}</strong>
            </div>
            <small style="display: block; color: #6b5f54; font-size: 12.5px; margin-top: 3px;">Đơn tối thiểu: ${formatVND(minV)}</small>
          </div>
          <button type="button" onclick="selectQuickVoucher('${code.replace(/'/g, "\\'")}')" style="padding: 6px 12px; background: #2c1a0e; color: #fff; border: none; border-radius: 4px; font-size: 12.5px; font-weight: 700; cursor: pointer; white-space: nowrap;">Áp dụng</button>
        </div>`;
    }).join('');
    if (appliedVoucherCode && !list.some(v => String(v.MaVoucher).toUpperCase() === String(appliedVoucherCode).toUpperCase())) {
      currentVoucherDiscount = 0;
      appliedVoucherCode = null;
      const msgEl = document.getElementById('voucherMsg');
      if (msgEl) msgEl.style.display = 'none';
      renderAllViews();
    }
  } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
  renderAllViews();
  initCustomerAutoFill();
  refreshAvailableVouchers();
  const phoneInp = document.getElementById('custPhoneInput');
  if (phoneInp) {
    let phoneTimer = null;
    phoneInp.addEventListener('input', () => {
      clearTimeout(phoneTimer);
      phoneTimer = setTimeout(refreshAvailableVouchers, 350);
    });
    phoneInp.addEventListener('change', refreshAvailableVouchers);
  }
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
let gwPollTimer = null;
let gwCountdownTimer = null;
let gwActiveOrderCode = null;
let gwActiveFundAmount = 0;

function showOrderSuccessModal(orderCode, fundAmount, statusLabel) {
  const successModal = document.getElementById('orderSuccessModal');
  if (successModal) {
    document.getElementById('successOrderCode').textContent = '#' + orderCode;
    document.getElementById('successFundAmount').textContent = formatVND(fundAmount || 0);
    const stEl = document.getElementById('successOrderStatus');
    if (stEl && statusLabel) stEl.textContent = statusLabel;
    document.getElementById('successTrackingBtn').href = `index.php?action=tracking&order=${orderCode}`;
    successModal.style.display = 'grid';
  } else {
    window.location.href = `index.php?action=tracking&order=${orderCode}`;
  }
}

function stopGatewayTimers() {
  if (gwPollTimer) { clearInterval(gwPollTimer); gwPollTimer = null; }
  if (gwCountdownTimer) { clearInterval(gwCountdownTimer); gwCountdownTimer = null; }
}

function openVietQRGatewayModal(orderCode, amount, fundAmount, qrUrl) {
  stopGatewayTimers();
  gwActiveOrderCode = orderCode;
  gwActiveFundAmount = fundAmount || 0;

  const modal = document.getElementById('vietqrGatewayModal');
  const qrImg = document.getElementById('gwQrImg');
  const banner = document.getElementById('gwStatusBanner');
  const bottomStatus = document.getElementById('gwBottomStatus');
  const cancelBtn = document.getElementById('gwCancelBtn');
  if (bottomStatus) bottomStatus.style.display = 'none';
  if (cancelBtn) cancelBtn.style.display = 'block';

  if (qrImg && qrUrl) qrImg.src = qrUrl;
  if (document.getElementById('gwBankName') && window.KIBO_VIETQR) {
    document.getElementById('gwBankName').textContent = window.KIBO_VIETQR.bank_name || 'MSB';
    document.getElementById('gwAccountNo').textContent = window.KIBO_VIETQR.account_no || '';
  }
  if (document.getElementById('gwAmount')) document.getElementById('gwAmount').textContent = formatVND(amount);
  if (document.getElementById('gwContent')) document.getElementById('gwContent').textContent = 'KIBO ' + orderCode;
  if (banner) {
    banner.style.background = '#fafafa';
    banner.style.color = '#333';
    banner.style.borderColor = '#e0e0e0';
    banner.textContent = 'Đang chờ bạn quét mã QR và chuyển khoản đúng số tiền...';
  }

  let secondsLeft = 15 * 60;
  const cdEl = document.getElementById('gwCountdown');
  if (cdEl) cdEl.textContent = '15:00';
  gwCountdownTimer = setInterval(() => {
    secondsLeft--;
    if (cdEl) {
      const m = String(Math.floor(Math.max(0, secondsLeft) / 60)).padStart(2, '0');
      const s = String(Math.max(0, secondsLeft) % 60).padStart(2, '0');
      cdEl.textContent = `${m}:${s}`;
    }
    if (secondsLeft <= 0) {
      stopGatewayTimers();
      if (banner) {
        banner.style.background = '#f5f5f5';
        banner.style.color = '#222';
        banner.textContent = 'Hết thời gian thanh toán. Vui lòng đặt lại đơn hàng.';
      }
    }
  }, 1000);

  // Tự động kiểm tra trạng thái Webhook mỗi 2.5 giây
  gwPollTimer = setInterval(async () => {
    try {
      const r = await fetch('index.php?action=check_payment_status&order=' + encodeURIComponent(orderCode));
      const d = await r.json();
      if (d && d.paid) {
        handlePaymentCompleted(d.mavandon);
      }
    } catch (e) {}
  }, 2500);

  if (modal) modal.style.display = 'grid';
}

function handlePaymentCompleted(maVanDon) {
  stopGatewayTimers();
  // Chỉ dọn giỏ hàng sau khi tiền đã về thành công
  saveCart([]);
  renderAllViews();
  const banner = document.getElementById('gwStatusBanner');
  const bottomStatus = document.getElementById('gwBottomStatus');
  const cancelBtn = document.getElementById('gwCancelBtn');
  if (banner) {
    banner.style.background = '#f5f5f5';
    banner.style.color = '#222';
    banner.style.borderColor = '#ccc';
    banner.textContent = 'Đã nhận được thanh toán.';
  }
  if (bottomStatus) {
    bottomStatus.style.display = 'block';
    bottomStatus.textContent = 'Thanh toán thành công! Đang chuyển hướng...';
  }
  if (cancelBtn) cancelBtn.style.display = 'none';

  setTimeout(() => {
    const modal = document.getElementById('vietqrGatewayModal');
    if (modal) modal.style.display = 'none';
    const label = maVanDon
      ? `Đã thanh toán & Chờ đóng gói (Mã vận đơn: ${maVanDon})`
      : 'Đã thanh toán & Chờ đóng gói';
    showOrderSuccessModal(gwActiveOrderCode, gwActiveFundAmount, label);
  }, 2200);
}

function cancelOnlinePayment() {
  if (!gwActiveOrderCode) return;
  const txt = document.getElementById('gwCancelConfirmText');
  if (txt) txt.textContent = 'Bạn muốn hủy giao dịch thanh toán cho đơn #' + gwActiveOrderCode + '?';
  const confirmModal = document.getElementById('gwCancelConfirmModal');
  if (confirmModal) confirmModal.style.display = 'grid';
}

function closeCancelConfirmModal() {
  const confirmModal = document.getElementById('gwCancelConfirmModal');
  if (confirmModal) confirmModal.style.display = 'none';
}

async function confirmCancelOnlinePayment() {
  closeCancelConfirmModal();
  if (!gwActiveOrderCode) return;
  stopGatewayTimers();
  try {
    await fetch('index.php?action=cancel_order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        madonhang: gwActiveOrderCode,
        csrf_token: window.KIBO_CSRF_TOKEN || ''
      })
    });
  } catch (e) {}
  const modal = document.getElementById('vietqrGatewayModal');
  if (modal) modal.style.display = 'none';
  showCustomerToast('Đã hủy giao dịch.', 'info');
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
