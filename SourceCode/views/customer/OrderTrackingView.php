<?php
/**
 * VIEW: views/customer/OrderTrackingView.php
 * Giao diện Tra Cứu Đơn Hàng & Đóng Góp 5% - Chuẩn mã nguồn KIBO Pet's Home
 */
$pageTitle = 'Tra Cứu Đơn Hàng & Đóng Góp 5% - KIBO PET\'S HOME';
$activeMenu = 'tracking';
require __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/DonHangModel.php';
require_once __DIR__ . '/../../models/DoiTraModel.php';

// Chỉ nhận mã đơn khi người dùng thực sự nhập (chuẩn hóa loại bỏ dấu #, khoảng trắng)
$orderCodeInput = isset($_GET['order']) ? ltrim(strtoupper(trim($_GET['order'])), '#') : '';
$searched = isset($_GET['order']) && $orderCodeInput !== '';
$isValidCode = !empty($orderCodeInput);

$orderData = null;
$returns = [];
if ($isValidCode) {
    $dbOrder = DonHangModel::traCuu($orderCodeInput);
    if ($dbOrder) {
        $returns = DoiTraModel::getByOrder($dbOrder['orderId']);
        $itemsList = $dbOrder['items'] ?? [];
        $statusText = $dbOrder['status'];
        $step = 1;
        if (mb_stripos($statusText, 'thành công') !== false) {
            $step = 4;
        } elseif (mb_stripos($statusText, 'đang giao') !== false || $statusText === 'Đang giao') {
            $step = 3;
        } elseif (mb_stripos($statusText, 'kho') !== false || mb_stripos($statusText, 'gói') !== false) {
            $step = 2;
        } else {
            $step = 1; // Chờ xác nhận
        }

        $subtotal = (float)($dbOrder['subtotal'] ?? 0);
        $shipping = (float)($dbOrder['shipping'] ?? 0);
        $discount = (float)($dbOrder['discount'] ?? 0);
        $total = (float)($dbOrder['total'] ?? ($subtotal + $shipping - $discount));
        $fundAmount = (float)($dbOrder['fundAmount'] ?? DonHangModel::tinhQuy($subtotal, $discount));

        // Phân quyền hiển thị thông tin bảo mật: Chỉ hiển thị đầy đủ Tên, SĐT & Địa chỉ nếu là chủ đơn hàng, khách vừa đặt trong phiên, đã xác minh SĐT, hoặc Admin
        $currUser = $_SESSION['user'] ?? null;
        $orderMaKH = $dbOrder['makh'] ?? '';
        $rawPhone = $dbOrder['phone'] ?? '';
        $rawAddr = $dbOrder['address'] ?? '';
        $rawCust = $dbOrder['customerName'] ?? 'Khách hàng';

        $verifyError = '';
        $isVerifiedOrder = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_order_phone'])) {
            $inputPhone = preg_replace('/\s+/', '', trim((string)$_POST['verify_order_phone']));
            $cleanOrderPhone = preg_replace('/\s+/', '', trim((string)$rawPhone));
            if ($inputPhone !== '' && $cleanOrderPhone !== '' && $inputPhone === $cleanOrderPhone) {
                $isVerifiedOrder = true;
            } else {
                $verifyError = 'Số điện thoại xác minh không khớp với đơn hàng.';
            }
        }

        $recentGuestOrders = $_SESSION['recent_guest_orders'] ?? [];
        $isRecentGuestOrder = is_array($recentGuestOrders) && in_array($dbOrder['orderId'], $recentGuestOrders, true);
        $isOwner = $isRecentGuestOrder
                   || $isVerifiedOrder
                   || ($currUser && !empty($currUser['MaKH']) && !empty($orderMaKH) && $currUser['MaKH'] === $orderMaKH)
                   || ($currUser && ($currUser['VaiTro'] ?? '') === 'Admin');

        if (!$isOwner) {
            $maskedCustomer = (mb_strlen($rawCust) > 1) ? (mb_substr($rawCust, 0, 1) . '***') : 'Khách hàng';
            $maskedPhone = (strlen($rawPhone) >= 7) ? (substr($rawPhone, 0, 3) . '****' . substr($rawPhone, -3)) : '***';
            $addrParts = array_values(array_filter(array_map('trim', explode(',', $rawAddr)), function($p) { return $p !== ''; }));
            if (count($addrParts) >= 2) {
                $maskedAddr = '***, ' . implode(', ', array_slice($addrParts, 1));
            } else {
                $maskedAddr = '*** (Địa chỉ đã được ẩn để bảo mật)';
            }
        } else {
            $maskedCustomer = $rawCust;
            $maskedPhone = $rawPhone;
            $maskedAddr = $rawAddr;
        }

        $orderData = [
            'code' => $dbOrder['orderId'],
            'customer' => $maskedCustomer,
            'phone' => $maskedPhone,
            'address' => $maskedAddr,
            'itemsList' => $itemsList,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'fund' => number_format($fundAmount, 0, ',', '.') . 'đ',
            'fundStatus' => $dbOrder['trangthaiquy'] ?? ($dbOrder['fundStatus'] ?? ($step === 4 ? 'Đã trích quỹ' : 'Chờ trích quỹ')),
            'carrierStatus' => $dbOrder['carrierStatus'] ?? '',
            'mavandon' => $dbOrder['mavandon'] ?? null,
            'paymentStatus' => $dbOrder['paymentStatus'] ?? 'Chờ thanh toán',
            'rawPhone' => $rawPhone,
            'status' => $statusText,
            'status_step' => $step,
            'shelter' => $dbOrder['allocatedStation'] ?? null,
            'date' => $dbOrder['date']
        ];
    }
}
?>

<main class="page tracking-page">
  <section class="tracking-section">
    <div class="container tracking">
      <div class="tracking-intro">
        <span class="eyebrow brown">THEO DÕI ĐƠN HÀNG VÀ ĐÓNG GÓP 5%</span>
        <h1>Tra cứu đơn hàng</h1>
        <p>Theo dõi lộ trình vận chuyển và xác minh minh bạch khoản 5% trích vào Quỹ cứu trợ.</p>
      </div>

      <form action="index.php" method="GET" class="tracking-card">
        <input type="hidden" name="action" value="tracking">
        <label>
          <?= kibo_icon('search', 22) ?>
          <input type="text" name="order" value="<?= htmlspecialchars($orderCodeInput) ?>" placeholder="Nhập mã đơn hàng..." required autofocus>
        </label>
        <button type="submit" class="btn primary">Kiểm tra ngay</button>
      </form>

      <?php if ($searched && !$isValidCode): ?>
        <div class="tracking-error">
          ⚠️ Vui lòng nhập mã đơn hàng của bạn để tra cứu.
        </div>
      <?php elseif ($searched && $isValidCode && !$orderData): ?>
        <div class="tracking-error" style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 14px 18px; border-radius: 6px; margin: 18px 0; font-size: 14px;">
          ❌ Không tìm thấy thông tin cho mã đơn "<strong><?= htmlspecialchars($orderCodeInput) ?></strong>". Vui lòng kiểm tra lại mã trên hóa đơn/email hoặc trong mục <strong>Lịch sử đơn hàng</strong> của bạn.
        </div>
      <?php endif; ?>

      <?php if (!$searched): ?>
        <!-- KHUNG HƯỚNG DẪN DUY NHẤT KHI CHƯA NHẬP MÃ -->
        <div class="tracking-empty-hint">
          <div class="hint-icon">
            <?= kibo_icon('box', 36) ?>
          </div>
          <h3>Nhập mã đơn hàng của bạn để tra cứu</h3>
          <p>Vui lòng nhập mã đơn hàng (định dạng <strong>KB-XXXXXX</strong>) được cấp khi hoàn tất đặt hàng hoặc trong mục <strong>Tài khoản</strong> để xem hành trình vận chuyển và xác minh 5% đóng góp Quỹ cứu trợ.</p>
        </div>
      <?php endif; ?>

      <?php if ($isValidCode && $orderData): ?>
        <!-- KẾT QUẢ TRA CỨU ĐƠN HÀNG (DẠNG BASIC) -->
        <article class="order-result">
          <div class="result-head">
            <div>
              <small>MÃ ĐƠN HÀNG:</small>
              <strong><?= htmlspecialchars($orderData['code']) ?></strong>
            </div>
            <?php
              $isSuccess = ($step === 4);
              $isShipping = ($step === 3);
              $isPacking = ($step === 2);
              $isPending = ($step === 1);
              $badgeBg = $isSuccess ? '#2e7d32' : ($isShipping ? '#1565c0' : ($isPacking ? '#b45309' : '#d97706'));
            ?>
            <span style="background: <?= $badgeBg ?>; color: #ffffff; font-weight: bold; padding: 6px 14px; border-radius: 3px; font-size: 13px;">
              <?= htmlspecialchars($orderData['status']) ?>
            </span>
          </div>

          <div class="result-grid">
            <div class="result-impact">
              <small>ĐÓNG GÓP TỪ ĐƠN HÀNG (5%)</small>
              <strong><?= htmlspecialchars($orderData['fund']) ?></strong>
              <?php if (($orderData['fundStatus'] ?? '') === 'Đã hủy' || ($orderData['status'] ?? '') === 'Đã hủy'): ?>
                <em style="color: #c94336;">Trạng thái quỹ: <strong>Đã hủy</strong> (Đơn hàng hủy hoặc hoàn tiền)</em>
              <?php elseif (($orderData['fundStatus'] ?? '') === 'Đã giải ngân' && !empty($orderData['shelter'])): ?>
                <em>Đã giải ngân cho: <strong><?= htmlspecialchars($orderData['shelter']) ?></strong></em>
              <?php elseif (($orderData['fundStatus'] ?? '') === 'Đã trích quỹ'): ?>
                <em>Trạng thái quỹ: <strong>Đã trích quỹ</strong> (Đã cộng vào Quỹ Cứu Trợ Động Vật KIBO, chờ giải ngân cuối tháng)</em>
              <?php else: ?>
                <em>Trạng thái quỹ: <strong>Chờ trích quỹ</strong> (Sẽ chính thức trích vào Quỹ khi đơn giao thành công & hoàn tất thanh toán)</em>
              <?php endif; ?>
            </div>
            <div>
              <small>THÔNG TIN NGƯỜI NHẬN & VẬN ĐƠN</small>
              <strong><?= htmlspecialchars($orderData['customer']) ?></strong>
              <?php if (!empty($orderData['phone'])): ?>
                <p style="margin: 2px 0 4px; color: #444; font-size: 13.5px;">SĐT: <strong><?= htmlspecialchars($orderData['phone']) ?></strong></p>
              <?php endif; ?>
              <p style="margin: 0 0 4px; color: #555; font-size: 13.5px;"><?= htmlspecialchars($orderData['address']) ?></p>
              <?php if (empty($isOwner)): ?>
                <form method="POST" action="index.php?action=tracking&order=<?= urlencode($orderData['code']) ?>" style="margin: 8px 0 6px; display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                  <input type="tel" name="verify_order_phone" placeholder="Nhập SĐT đặt hàng để xem đầy đủ địa chỉ" required style="flex: 1; min-width: 190px; height: 32px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 12.5px;">
                  <button type="submit" style="height: 32px; padding: 0 12px; background: #2c1a0e; color: #fff; border: none; border-radius: 4px; font-size: 12.5px; font-weight: 600; cursor: pointer;">Xem đầy đủ</button>
                </form>
                <?php if (!empty($verifyError)): ?>
                  <div style="font-size: 12px; color: #c94336; margin-bottom: 4px;"><?= htmlspecialchars($verifyError) ?></div>
                <?php endif; ?>
              <?php endif; ?>
              <?php if (!empty($orderData['carrierStatus'])): ?>
                <p style="margin: 4px 0 0; color: #2e7d32; font-size: 13px; font-weight: 600;">🚚 Vận chuyển: <?= htmlspecialchars($orderData['carrierStatus']) ?></p>
              <?php endif; ?>
              <?php if (!empty($orderData['mavandon'])): ?>
                <p style="margin: 4px 0 0; color: #2c1a0e; font-size: 13px; font-weight: 700;">📦 Mã vận đơn: <span style="background: #f5efe8; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($orderData['mavandon']) ?></span></p>
              <?php endif; ?>
              <?php if (!empty($isOwner) && in_array($orderData['status'], ['Chờ xác nhận', 'Chờ đóng gói'], true)): ?>
                <?php $isTrackingPaid = (($orderData['paymentStatus'] ?? '') === 'Thành công'); ?>
                <div style="margin-top: 10px;">
                  <button type="button" onclick='cancelTrackingOrder(<?= json_encode($orderData["code"]) ?>, <?= $isTrackingPaid ? "true" : "false" ?>, <?= json_encode($orderData["rawPhone"] ?? "") ?>)' style="padding: 6px 14px; border: 1px solid #c8c8c8; background: #ffffff; color: #333333; border-radius: 4px; font-size: 13px; font-weight: 500; cursor: pointer;">
                    Hủy đơn hàng
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- CHI TIẾT SẢN PHẨM & ĐƠN GIÁ -->
          <div style="padding: 20px 24px; border-bottom: 1px solid #e5e5e5; background: #ffffff;">
            <div style="font-size: 12px; font-weight: 700; color: #666; text-transform: uppercase; margin-bottom: 10px;">
              DANH SÁCH SẢN PHẨM & ĐƠN GIÁ
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
              <thead>
                <tr style="border-bottom: 1px solid #e0e0e0; color: #666; text-align: left;">
                  <th style="padding: 8px 0; font-weight: 600;">Sản phẩm</th>
                  <th style="padding: 8px 12px; font-weight: 600; text-align: center;">SL</th>
                  <th style="padding: 8px 12px; font-weight: 600; text-align: right;">Đơn giá</th>
                  <th style="padding: 8px 0; font-weight: 600; text-align: right;">Thành tiền</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($orderData['itemsList'])): ?>
                  <?php foreach ($orderData['itemsList'] as $it): 
                    $qty = (int)($it['qty'] ?? 1);
                    $price = (float)($it['price'] ?? 0);
                    $lineTotal = $qty * $price;
                  ?>
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                      <td style="padding: 10px 0; color: #222;">
                        <strong><?= htmlspecialchars($it['name']) ?></strong>
                        <?php if (!empty($it['variant'])): ?>
                          <small style="display: block; color: #777; margin-top: 2px;">Phân loại: <?= htmlspecialchars($it['variant']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td style="padding: 10px 12px; text-align: center; color: #333; font-weight: 600;"><?= $qty ?></td>
                      <td style="padding: 10px 12px; text-align: right; color: #444;"><?= number_format($price, 0, ',', '.') ?>₫</td>
                      <td style="padding: 10px 0; text-align: right; font-weight: 600; color: #222;"><?= number_format($lineTotal, 0, ',', '.') ?>₫</td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="padding: 10px 0; color: #222;">Sản phẩm theo đơn hàng</td>
                    <td style="padding: 10px 12px; text-align: center; color: #333;">1</td>
                    <td style="padding: 10px 12px; text-align: right; color: #444;"><?= number_format($orderData['subtotal'], 0, ',', '.') ?>₫</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600; color: #222;"><?= number_format($orderData['subtotal'], 0, ',', '.') ?>₫</td>
                  </tr>
                <?php endif; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" style="padding: 10px 12px 4px 0; text-align: right; color: #666;">Tạm tính tiền hàng:</td>
                  <td style="padding: 10px 0 4px; text-align: right; color: #333; font-weight: 600;"><?= number_format($orderData['subtotal'], 0, ',', '.') ?>₫</td>
                </tr>
                <tr>
                  <td colspan="3" style="padding: 4px 12px 4px 0; text-align: right; color: #666;">Phí vận chuyển:</td>
                  <td style="padding: 4px 0; text-align: right; color: #333;"><?= $orderData['shipping'] > 0 ? number_format($orderData['shipping'], 0, ',', '.') . '₫' : 'Miễn phí' ?></td>
                </tr>
                <?php if ($orderData['discount'] > 0): ?>
                  <tr>
                    <td colspan="3" style="padding: 4px 12px 4px 0; text-align: right; color: #2e7d32;">Mã giảm giá:</td>
                    <td style="padding: 4px 0; text-align: right; color: #2e7d32; font-weight: 600;">-<?= number_format($orderData['discount'], 0, ',', '.') ?>₫</td>
                  </tr>
                <?php endif; ?>
                <tr style="border-top: 1px solid #ddd;">
                  <td colspan="3" style="padding: 10px 12px 4px 0; text-align: right; font-weight: bold; font-size: 14.5px; color: #111;">Tổng thanh toán:</td>
                  <td style="padding: 10px 0 4px; text-align: right; font-weight: bold; font-size: 16px; color: #2c1a0e;"><?= number_format($orderData['total'], 0, ',', '.') ?>₫</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- LỘ TRÌNH VẬN CHUYỂN BASIC -->
          <div class="timeline" style="border-top: 1px solid #e5e5e5;">
            <!-- Bước 1: Chờ xác nhận -->
            <div style="opacity: <?= $step >= 1 ? '1' : '0.45' ?>;">
              <div class="timeline-step-indicator">
                <span class="timeline-step-badge" style="background: <?= $step >= 1 ? '#2c1a0e' : '#ccc' ?>;">
                  <?= $step > 1 ? '✓' : '1' ?>
                </span>
                <span class="timeline-step-bar" style="background: <?= $step > 1 ? '#2c1a0e' : '#e5e5e5' ?>;"></span>
              </div>
              <strong><?= $step === 1 ? 'Chờ xác nhận' : 'Đã xác nhận' ?></strong>
              <small><?= htmlspecialchars($orderData['date']) ?></small>
            </div>

            <!-- Bước 2: Chuẩn bị hàng -->
            <div style="opacity: <?= $step >= 2 ? '1' : '0.45' ?>;">
              <div class="timeline-step-indicator">
                <span class="timeline-step-badge" style="background: <?= $step >= 2 ? '#2c1a0e' : '#ccc' ?>;">
                  <?= $step > 2 ? '✓' : '2' ?>
                </span>
                <span class="timeline-step-bar" style="background: <?= $step > 2 ? '#2c1a0e' : '#e5e5e5' ?>;"></span>
              </div>
              <strong><?= $step >= 2 ? 'Đang đóng gói' : 'Chuẩn bị hàng' ?></strong>
              <small><?= $step >= 2 ? htmlspecialchars($orderData['date']) : 'Chờ xử lý' ?></small>
            </div>

            <!-- Bước 3: Đang giao hàng -->
            <div style="opacity: <?= $step >= 3 ? '1' : '0.45' ?>;">
              <div class="timeline-step-indicator">
                <span class="timeline-step-badge" style="background: <?= $step >= 3 ? '#2c1a0e' : '#ccc' ?>;">
                  <?= $step > 3 ? '✓' : '3' ?>
                </span>
                <span class="timeline-step-bar" style="background: <?= $step > 3 ? '#2c1a0e' : '#e5e5e5' ?>;"></span>
              </div>
              <strong>Đang giao hàng</strong>
              <small><?= $step >= 3 ? htmlspecialchars($orderData['date']) : 'Chờ giao bưu tá' ?></small>
            </div>

            <!-- Bước 4: Giao hàng thành công -->
            <div style="opacity: <?= $step >= 4 ? '1' : '0.45' ?>;">
              <div class="timeline-step-indicator">
                <span class="timeline-step-badge" style="background: <?= $step >= 4 ? '#2e7d32' : '#ccc' ?>;">
                  <?= $step >= 4 ? '✓' : '4' ?>
                </span>
                <span class="timeline-step-bar" style="background: transparent;"></span>
              </div>
              <strong>Giao thành công</strong>
              <small><?= $step >= 4 ? htmlspecialchars($orderData['date']) : 'Chờ hoàn tất' ?></small>
            </div>
          </div>

          <!-- KHU VỰC THÔNG TIN ĐỔI TRẢ (NẾU CÓ) HOẶC NÚT ĐỔI TRẢ -->
          <?php if (!empty($returns)): ?>
            <div style="margin: 20px 24px 24px; padding: 14px 18px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 4px; font-size: 13.5px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <strong style="color: #0369a1; font-size: 14px;">📦 Thông tin yêu cầu đổi trả (Mã: <?= htmlspecialchars($returns[0]['MaYeuCau']) ?>)</strong>
                <span style="background: #0284c7; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;">
                  <?= htmlspecialchars($returns[0]['TrangThaiYeuCau']) ?>
                </span>
              </div>
              <p style="margin: 0 0 6px; color: #444;"><strong>Lý do:</strong> <?= htmlspecialchars($returns[0]['LyDoDoiTra']) ?></p>
              <?php if (!empty($returns[0]['HuongXuLy'])): ?>
                <p style="margin: 0; color: #666; font-size: 13px;"><strong>Hướng xử lý:</strong> <?= htmlspecialchars($returns[0]['HuongXuLy']) ?></p>
              <?php endif; ?>
            </div>
          <?php elseif ($step === 4): ?>
            <div style="margin: 20px 24px 24px; padding-top: 16px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
              <small style="color: #666; font-size: 13px;">Đơn hàng thỏa điều kiện đổi trả miễn phí trong 3 ngày nếu bao bì còn nguyên vẹn.</small>
              <button type="button" onclick="openTrackingReturnModal()" style="padding: 7px 16px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 3px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Gửi yêu cầu đổi trả
              </button>
            </div>
          <?php endif; ?>
        </article>
      <?php endif; ?>
    </div>
  </section>
</main>

<!-- MODAL ĐỔI TRẢ BASIC TẠI TRANG TRACKING -->
<div id="trackingReturnModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px;">
  <div style="background: #ffffff; width: min(480px, 94vw); border-radius: 4px; border: 1px solid #c8c8c8; box-shadow: 0 10px 25px rgba(0,0,0,0.18); font-family: Arial, sans-serif; overflow: hidden;">
    <div style="background: #f5f5f5; padding: 14px 20px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #222;">Yêu cầu đổi trả đơn hàng</h3>
      <button type="button" onclick="closeTrackingReturnModal()" style="background: none; border: none; font-size: 20px; color: #666; cursor: pointer; line-height: 1;">✕</button>
    </div>
    <form onsubmit="handleTrackingReturnSubmit(event)" style="padding: 20px 24px;">
      <div id="trackingReturnAlert" style="display: none; padding: 10px 14px; border-radius: 4px; margin-bottom: 14px; font-size: 13.5px; font-weight: 600;"></div>
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Mã đơn hàng</label>
        <input type="text" id="trackReturnCode" value="<?= htmlspecialchars($orderData['code'] ?? '') ?>" readonly style="width: 100%; height: 38px; padding: 0 12px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; font-weight: bold; color: #2c1a0e;">
      </div>
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Số điện thoại người nhận (để xác minh) *</label>
        <input type="tel" id="trackReturnPhone" placeholder="Nhập SĐT đặt hàng (10 chữ số)..." required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
      </div>
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Lý do đổi trả (tối thiểu 10 ký tự) *</label>
        <textarea id="trackReturnReason" rows="3" required placeholder="Ví dụ: Đổi kích thước túi hạt, bao bì bị rách do vận chuyển..." style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none; resize: vertical;"></textarea>
      </div>
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Hình ảnh minh chứng (JPG, PNG, WEBP)</label>
        <input type="file" id="trackReturnProofImage" accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 6px 0; font-size: 13px; color: #333;">
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #eee;">
        <button type="button" onclick="closeTrackingReturnModal()" style="padding: 8px 18px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
        <button type="submit" id="btnSubmitTrackReturn" style="padding: 8px 22px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #fff; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">Gửi yêu cầu</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTrackingReturnModal() {
  document.getElementById('trackReturnReason').value = '';
  if (document.getElementById('trackReturnPhone')) document.getElementById('trackReturnPhone').value = '';
  if (document.getElementById('trackReturnProofImage')) document.getElementById('trackReturnProofImage').value = '';
  document.getElementById('trackingReturnAlert').style.display = 'none';
  document.getElementById('trackingReturnModal').style.display = 'grid';
}

function closeTrackingReturnModal() {
  document.getElementById('trackingReturnModal').style.display = 'none';
}

async function handleTrackingReturnSubmit(e) {
  e.preventDefault();
  const madonhang = document.getElementById('trackReturnCode').value.trim();
  const sdt = document.getElementById('trackReturnPhone') ? document.getElementById('trackReturnPhone').value.trim() : '';
  const lydo = document.getElementById('trackReturnReason').value.trim();
  const imgInput = document.getElementById('trackReturnProofImage');
  const btn = document.getElementById('btnSubmitTrackReturn');
  const alertEl = document.getElementById('trackingReturnAlert');

  if (!lydo || lydo.length < 10) {
    alertEl.textContent = 'Vui lòng nhập lý do đổi trả chi tiết (tối thiểu 10 ký tự).';
    alertEl.style.display = 'block';
    alertEl.style.background = '#fdf2f2';
    alertEl.style.color = '#c94336';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '⏳ Đang gửi...';

  try {
    const formData = new FormData();
    formData.append('madonhang', madonhang);
    formData.append('sdt', sdt);
    formData.append('lydo', lydo);
    if (imgInput && imgInput.files && imgInput.files[0]) {
      formData.append('hinhanh_file', imgInput.files[0]);
    }

    const resp = await fetch('index.php?action=submit_return', {
      method: 'POST',
      body: formData
    });
    const res = await resp.json();
    if (res.success) {
      alertEl.textContent = '✓ ' + (res.message || 'Gửi yêu cầu thành công!');
      alertEl.style.display = 'block';
      alertEl.style.background = '#eaf4e6';
      alertEl.style.color = '#2e7d32';
      setTimeout(() => {
        closeTrackingReturnModal();
        window.location.reload();
      }, 1200);
    } else {
      alertEl.textContent = res.message || 'Có lỗi xảy ra, vui lòng thử lại.';
      alertEl.style.display = 'block';
      alertEl.style.background = '#fdf2f2';
      alertEl.style.color = '#c94336';
    }
  } catch (err) {
    alertEl.textContent = 'Lỗi kết nối máy chủ.';
    alertEl.style.display = 'block';
    alertEl.style.background = '#fdf2f2';
    alertEl.style.color = '#c94336';
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Gửi yêu cầu';
  }
}

let trackingCancelPhone = '';
async function cancelTrackingOrder(orderId, isPaid, rawPhone) {
  trackingCancelPhone = rawPhone || '';
  if (isPaid) {
    document.getElementById('trackingRefundOrderId').value = orderId;
    document.getElementById('trackingRefundOrderText').textContent = '#' + orderId;
    document.getElementById('trackingRefundBank').value = '';
    document.getElementById('trackingRefundAccNo').value = '';
    document.getElementById('trackingRefundAccHolder').value = '';
    document.getElementById('trackingRefundCancelModal').style.display = 'grid';
    return;
  }
  const ok = await kiboConfirm(`Bạn có chắc chắn muốn hủy đơn hàng #${orderId} không?`, 'Xác nhận hủy đơn hàng', 'Xác nhận hủy', 'Đóng');
  if (!ok) return;
  await submitTrackingCancelOrder(orderId, null);
}

function closeTrackingRefundModal() {
  const m = document.getElementById('trackingRefundCancelModal');
  if (m) m.style.display = 'none';
}

async function handleTrackingRefundSubmit(e) {
  e.preventDefault();
  const orderId = document.getElementById('trackingRefundOrderId').value;
  const ngan_hang = document.getElementById('trackingRefundBank').value.trim();
  const so_tai_khoan = document.getElementById('trackingRefundAccNo').value.trim();
  const chu_tai_khoan = document.getElementById('trackingRefundAccHolder').value.trim();
  if (!ngan_hang || !so_tai_khoan || !chu_tai_khoan) return;
  closeTrackingRefundModal();
  await submitTrackingCancelOrder(orderId, { ngan_hang, so_tai_khoan, chu_tai_khoan });
}

async function submitTrackingCancelOrder(orderId, refundBank) {
  try {
    const payload = {
      madonhang: orderId,
      verify_phone: trackingCancelPhone,
      csrf_token: window.KIBO_CSRF_TOKEN || ''
    };
    if (refundBank) payload.refund_bank = refundBank;
    const res = await fetch('index.php?action=cancel_order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      await kiboAlert(data.message || 'Hủy đơn hàng thành công!', 'Thông báo');
      window.location.href = 'index.php?action=tracking&order=' + encodeURIComponent(orderId);
    } else {
      await kiboAlert(data.message || 'Không thể hủy đơn hàng.', 'Thông báo');
    }
  } catch (err) {
    await kiboAlert('Lỗi kết nối khi gửi yêu cầu hủy đơn.', 'Thông báo');
  }
}
</script>

<!-- MODAL HỦY ĐƠN ĐÃ THANH TOÁN & HOÀN TIỀN (TRANG TRA CỨU) -->
<div id="trackingRefundCancelModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px;">
  <div style="background: #ffffff; width: min(460px, 94vw); border-radius: 4px; border: 1px solid #c8c8c8; box-shadow: 0 10px 28px rgba(0,0,0,0.2); font-family: Arial, sans-serif; overflow: hidden;">
    <div style="background: #f5f5f5; padding: 13px 18px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #222222;">Hủy đơn &amp; Yêu cầu hoàn tiền</h3>
      <button type="button" onclick="closeTrackingRefundModal()" style="background: none; border: none; font-size: 18px; color: #666666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
    </div>
    <form onsubmit="handleTrackingRefundSubmit(event)" style="padding: 18px 20px;">
      <input type="hidden" id="trackingRefundOrderId">
      <p style="margin: 0 0 14px; font-size: 13.5px; line-height: 1.5; color: #333333;">
        Đơn hàng <strong id="trackingRefundOrderText">#</strong> đã được thanh toán. Vui lòng nhập thông tin tài khoản ngân hàng để nhận hoàn tiền:
      </p>
      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px;">Tên ngân hàng *</label>
        <input type="text" id="trackingRefundBank" required placeholder="VD: Vietcombank, MB Bank, Techcombank..." style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box;">
      </div>
      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px;">Số tài khoản *</label>
        <input type="text" id="trackingRefundAccNo" required placeholder="Nhập số tài khoản nhận tiền hoàn" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box;">
      </div>
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px;">Tên chủ tài khoản *</label>
        <input type="text" id="trackingRefundAccHolder" required placeholder="VD: NGUYEN VAN A" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box;">
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 12px; border-top: 1px solid #eee;">
        <button type="button" onclick="closeTrackingRefundModal()" style="padding: 8px 18px; border: 1px solid #cccccc; background: #ffffff; color: #333333; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Đóng</button>
        <button type="submit" style="padding: 8px 18px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #ffffff; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">Xác nhận hủy &amp; Hoàn tiền</button>
      </div>
    </form>
  </div>
</div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/footer.php'; ?>
