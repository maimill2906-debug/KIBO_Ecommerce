<?php
/**
 * VIEW: views/customer/OrderTrackingView.php
 * Giao diện Tra Cứu Đơn Hàng & Đóng Góp 5% — Chuẩn mã nguồn KIBO Pet's Home
 */
$pageTitle = 'Tra Cứu Đơn Hàng & Đóng Góp 5% — KIBO PET\'S HOME';
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

        // Phân quyền hiển thị thông tin bảo mật: Chỉ hiển thị đầy đủ SĐT & Địa chỉ nếu là chủ đơn hàng hoặc Admin
        $currUser = $_SESSION['user'] ?? null;
        $orderMaKH = $dbOrder['makh'] ?? '';
        $isOwner = ($currUser && !empty($currUser['MaKH']) && !empty($orderMaKH) && $currUser['MaKH'] === $orderMaKH)
                   || ($currUser && ($currUser['VaiTro'] ?? '') === 'Admin');

        $rawPhone = $dbOrder['phone'] ?? '';
        $rawAddr = $dbOrder['address'] ?? '';

        if (!$isOwner) {
            $maskedPhone = (strlen($rawPhone) >= 7) ? (substr($rawPhone, 0, 4) . '***' . substr($rawPhone, -3)) : '0912***789';
            $addrParts = explode(',', $rawAddr);
            if (count($addrParts) > 1) {
                array_shift($addrParts);
                $maskedAddr = '***, ' . trim(implode(', ', $addrParts));
            } else {
                $maskedAddr = '*** (Đã ẩn địa chỉ chi tiết)';
            }
        } else {
            $maskedPhone = $rawPhone;
            $maskedAddr = $rawAddr;
        }

        $orderData = [
            'code' => $dbOrder['orderId'],
            'customer' => $dbOrder['customerName'],
            'phone' => $maskedPhone,
            'address' => $maskedAddr,
            'itemsList' => $itemsList,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'fund' => number_format($fundAmount, 0, ',', '.') . 'đ',
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
        <p>Nhập mã đơn hàng (ví dụ: <strong>KB-8921</strong> hoặc mã <strong>KB-...</strong> trên hóa đơn) để theo dõi lộ trình vận chuyển và xác minh minh bạch khoản 5% trích vào Quỹ cứu trợ.</p>
      </div>

      <form action="index.php" method="GET" class="tracking-card">
        <input type="hidden" name="action" value="tracking">
        <label>
          <?= kibo_icon('search', 22) ?>
          <input type="text" name="order" value="<?= htmlspecialchars($orderCodeInput) ?>" placeholder="Nhập mã đơn hàng (ví dụ: KB-8921)..." required autofocus>
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
        <!-- KHUNG HƯỚNG DẪN KHI CHƯA NHẬP MÃ -->
        <div class="tracking-empty-hint">
          <div class="hint-icon">
            <?= kibo_icon('box', 36) ?>
          </div>
          <h3>Nhập mã đơn hàng của bạn để tra cứu</h3>
          <p>Vui lòng nhập mã đơn hàng (ví dụ: <strong>KB-8921</strong> hoặc <strong>KB-261104...</strong>) được cấp khi hoàn tất đặt hàng hoặc trong mục <strong>Tài khoản</strong> để xem hành trình vận chuyển và xác minh 5% đóng góp Quỹ cứu trợ.</p>
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
              <?php if (!empty($orderData['shelter']) && $step === 4): ?>
                <em>Đã giải ngân cho: <strong><?= htmlspecialchars($orderData['shelter']) ?></strong></em>
              <?php else: ?>
                <em>Đã trích vào <strong>Quỹ Cứu Trợ Động Vật KIBO</strong> (Đang tích lũy, sẽ phân bổ đối soát cho các trạm đối tác sau khi đơn hoàn tất)</em>
              <?php endif; ?>
            </div>
            <div>
              <small>THÔNG TIN NGƯỜI NHẬN</small>
              <strong><?= htmlspecialchars($orderData['customer']) ?></strong>
              <?php if (!empty($orderData['phone'])): ?>
                <p style="margin: 2px 0 4px; color: #444; font-size: 13.5px;">SĐT: <strong><?= htmlspecialchars($orderData['phone']) ?></strong></p>
              <?php endif; ?>
              <p style="margin: 0; color: #555; font-size: 13.5px;"><?= htmlspecialchars($orderData['address']) ?></p>
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
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Lý do đổi trả *</label>
        <textarea id="trackReturnReason" rows="3" required placeholder="Ví dụ: Đổi kích thước túi hạt, đổi phân loại hương vị..." style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none; resize: vertical;"></textarea>
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
  document.getElementById('trackingReturnAlert').style.display = 'none';
  document.getElementById('trackingReturnModal').style.display = 'grid';
}

function closeTrackingReturnModal() {
  document.getElementById('trackingReturnModal').style.display = 'none';
}

async function handleTrackingReturnSubmit(e) {
  e.preventDefault();
  const madonhang = document.getElementById('trackReturnCode').value.trim();
  const lydo = document.getElementById('trackReturnReason').value.trim();
  const btn = document.getElementById('btnSubmitTrackReturn');
  const alertEl = document.getElementById('trackingReturnAlert');

  if (!lydo) {
    alertEl.textContent = 'Vui lòng nhập lý do đổi trả.';
    alertEl.style.display = 'block';
    alertEl.style.background = '#fdf2f2';
    alertEl.style.color = '#c94336';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '⏳ Đang gửi...';

  try {
    const resp = await fetch('index.php?action=submit_return', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ madonhang, lydo })
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
</script>
    </div>
  </section>
</main>

<?php require __DIR__ . '/footer.php'; ?>
