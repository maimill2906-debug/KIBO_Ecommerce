<?php
/**
 * VIEW: views/customer/AccountView.php
 * Giao diện Hồ sơ Khách hàng & Lịch sử Đơn hàng
 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    header('Location: index.php?action=login');
    exit;
}

if (($currentUser['VaiTro'] ?? '') === 'Admin') {
    header('Location: index.php?action=admin');
    exit;
}

require_once __DIR__ . '/../../models/KhachHangModel.php';
require_once __DIR__ . '/../../models/DonHangModel.php';
require_once __DIR__ . '/../../models/DoiTraModel.php';

$pageTitle = 'Hồ Sơ Khách Hàng — KIBO PET\'S HOME';
$activeMenu = 'account';
require __DIR__ . '/header.php';

$cust = KhachHangModel::getCustomerDetail($currentUser['TenDangNhap']);
$maKH = $cust['MaKH'] ?? ($currentUser['MaKH'] ?? 'KH001');
$hoten = $cust['HoTen'] ?? ($currentUser['HoTen'] ?? 'Khách hàng KIBO');
$email = $cust['Email'] ?? ($currentUser['TenDangNhap'] ?? '');
$phone = $cust['SoDienThoaiChinh'] ?? ($currentUser['SoDienThoai'] ?? 'Chưa cập nhật');
$diachi = $cust['diachichitiet'] ?? ($currentUser['DiaChi'] ?? 'Chưa cập nhật');
$diem = $cust['DiemTichLuy'] ?? 0;

$myOrders = DonHangModel::getOrdersByCustomer($maKH, $phone, $email);

$allReturns = DoiTraModel::getAll();
$returnMap = [];
foreach ($allReturns as $r) {
    if (!empty($r['MaDonHang'])) {
        $returnMap[$r['MaDonHang']] = $r;
    }
}
?>

<main class="page" style="padding: 40px 0 80px; background: #faf8f5; font-family: Arial, sans-serif;">
  <div class="container" style="max-width: 1080px; margin: 0 auto; padding: 0 16px;">
    
    <!-- Breadcrumb -->
    <div style="font-size: 13.5px; color: #777; margin-bottom: 24px;">
      <a href="index.php?action=home" style="color: #666; text-decoration: none;">Trang chủ</a>
      <span style="margin: 0 8px;">/</span>
      <strong style="color: #2c1a0e;">Thông tin tài khoản</strong>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e0dbd5;">
      <div>
        <h1 style="font-size: 24px; font-weight: bold; color: #2c1a0e; margin: 0 0 6px;">Tài khoản của tôi</h1>
        <p style="margin: 0; color: #666; font-size: 14px;">Quản lý thông tin cá nhân và theo dõi đơn hàng của bạn tại KIBO</p>
      </div>
      <a href="index.php?action=logout" style="padding: 8px 18px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; text-decoration: none; font-weight: 500;">
        Đăng xuất
      </a>
    </div>

    <!-- Grid: 2 Cột -->
    <div style="display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: flex-start;">
      
      <!-- CỘT TRÁI: THÔNG TIN KHÁCH HÀNG -->
      <section style="background: #ffffff; border: 1px solid #dcdcdc; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); overflow: hidden;">
        <div style="background: #f7f7f7; padding: 14px 20px; border-bottom: 1px solid #e5e5e5; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; font-size: 15px; font-weight: bold; color: #222;">Thông tin cá nhân</h3>
          <button type="button" onclick="openEditProfileModal()" style="padding: 4px 10px; border: 1px solid #767676; background: #fff; color: #111; border-radius: 3px; font-size: 12.5px; font-weight: 500; cursor: pointer;">
            ✏️ Chỉnh sửa
          </button>
        </div>
        <div style="padding: 20px;">
          <div style="text-align: center; padding-bottom: 18px; border-bottom: 1px dashed #e5e5e5; margin-bottom: 18px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #2c1a0e; color: #ffffff; display: grid; place-items: center; font-size: 22px; font-weight: bold; margin: 0 auto 10px;">
              <?= mb_strtoupper(mb_substr($hoten, 0, 1)) ?>
            </div>
            <strong id="displayHoTen" style="font-size: 17px; color: #222; display: block;"><?= htmlspecialchars($hoten) ?></strong>
            <small style="color: #666; font-size: 13px;"><?= htmlspecialchars($email) ?></small>
          </div>

          <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; line-height: 1.8;">
            <tbody>
              <tr>
                <td style="color: #777; width: 110px; padding: 4px 0;">Mã khách hàng:</td>
                <td style="font-weight: bold; color: #222; padding: 4px 0;"><?= htmlspecialchars($maKH) ?></td>
              </tr>
              <tr>
                <td style="color: #777; padding: 4px 0;">Số điện thoại:</td>
                <td id="displayPhone" style="font-weight: 600; color: #222; padding: 4px 0;"><?= htmlspecialchars($phone) ?></td>
              </tr>
              <tr>
                <td style="color: #777; padding: 4px 0; vertical-align: top;">Địa chỉ:</td>
                <td id="displayAddress" style="color: #333; padding: 4px 0;"><?= htmlspecialchars($diachi) ?></td>
              </tr>
              <tr style="border-top: 1px dashed #eee;">
                <td style="color: #777; padding: 10px 0 4px;">Điểm tích lũy:</td>
                <td style="padding: 10px 0 4px; font-weight: bold; color: #2e7d32; font-size: 15px;">
                  <?= number_format($diem, 0, ',', '.') ?> điểm
                </td>
              </tr>
            </tbody>
          </table>

          <button type="button" onclick="openEditProfileModal()" style="width: 100%; margin-top: 16px; padding: 9px 0; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
            Cập nhật thông tin giao hàng
          </button>
        </div>
      </section>

      <!-- CỘT PHẢI: LỊCH SỬ ĐƠN HÀNG -->
      <section style="background: #ffffff; border: 1px solid #dcdcdc; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); overflow: hidden;">
        <div style="background: #f7f7f7; padding: 14px 20px; border-bottom: 1px solid #e5e5e5; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; font-size: 15px; font-weight: bold; color: #222;">Lịch sử đơn hàng của bạn</h3>
          <span style="font-size: 13px; color: #666;">Tổng: <strong><?= count($myOrders) ?></strong> đơn hàng</span>
        </div>

        <div style="padding: 20px;">
          <?php if (empty($myOrders)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #777;">
              <p style="font-size: 15px; margin-bottom: 12px;">Bạn chưa có đơn hàng nào tại KIBO.</p>
              <a href="index.php?action=products" style="padding: 8px 18px; background: #2c1a0e; color: #ffffff; border-radius: 4px; text-decoration: none; font-size: 13.5px; font-weight: bold; display: inline-block;">
                Mua sắm ngay
              </a>
            </div>
          <?php else: ?>
            <div style="overflow-x: auto;">
              <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                <thead>
                  <tr style="background: #fafafa; border-bottom: 1px solid #e0e0e0; color: #666; text-align: left;">
                    <th style="padding: 10px 12px; font-weight: 600;">Mã đơn</th>
                    <th style="padding: 10px 12px; font-weight: 600;">Ngày đặt</th>
                    <th style="padding: 10px 12px; font-weight: 600; text-align: right;">Tổng thanh toán</th>
                    <th style="padding: 10px 12px; font-weight: 600; text-align: right;">Trích quỹ (5%)</th>
                    <th style="padding: 10px 12px; font-weight: 600; text-align: center;">Trạng thái</th>
                    <th style="padding: 10px 12px; font-weight: 600; text-align: center;">Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($myOrders as $ord): 
                    $st = $ord['trangthaidonhang'] ?? 'Chờ xác nhận';
                    $stColor = ($st === 'Giao thành công') ? '#2e7d32' : (($st === 'Đang giao') ? '#1565c0' : '#d97706');
                  ?>
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                      <td style="padding: 12px; font-weight: bold; color: #2c1a0e;">
                        <?= htmlspecialchars($ord['madonhang']) ?>
                      </td>
                      <td style="padding: 12px; color: #555;">
                        <?= date('d/m/Y', strtotime($ord['ngaydathang'])) ?>
                      </td>
                      <td style="padding: 12px; text-align: right; font-weight: bold; color: #222;">
                        <?= number_format($ord['tongthanhtoan'], 0, ',', '.') ?>₫
                      </td>
                      <td style="padding: 12px; text-align: right; color: #2e7d32; font-weight: 600;">
                        <?= number_format($ord['sotiendonggop'] ?? DonHangModel::tinhQuy($ord['tongtienhang'] ?? $ord['tongthanhtoan'], $ord['tiengiamgia'] ?? 0), 0, ',', '.') ?>₫
                      </td>
                      <td style="padding: 12px; text-align: center;">
                        <span style="background: <?= $stColor ?>; color: #fff; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; display: inline-block;">
                          <?= htmlspecialchars($st) ?>
                        </span>
                      </td>
                      <td style="padding: 12px; text-align: center;">
                        <a href="index.php?action=tracking&order=<?= urlencode($ord['madonhang']) ?>" style="padding: 5px 12px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 3px; text-decoration: none; font-size: 12.5px; font-weight: 500; display: inline-block;">
                          Chi tiết
                        </a>
                        <?php if (isset($returnMap[$ord['madonhang']])): ?>
                          <div style="margin-top: 6px; font-size: 11.5px; color: #0284c7; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 3px; padding: 2px 6px; font-weight: 600;">
                            Đổi trả: <?= htmlspecialchars($returnMap[$ord['madonhang']]['TrangThaiYeuCau']) ?>
                          </div>
                        <?php elseif ($st === 'Giao thành công' || $st === 'Giao hàng thành công'): ?>
                          <button type="button" onclick="openReturnModal('<?= htmlspecialchars($ord['madonhang']) ?>')" style="margin-top: 6px; padding: 4px 8px; border: 1px solid #c8c8c8; background: #fff; color: #333; border-radius: 3px; font-size: 11.5px; cursor: pointer; display: block; width: 100%;">
                            Đổi trả (3 ngày)
                          </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </section>

    </div>
  </div>
</main>

<!-- MODAL CẬP NHẬT THÔNG TIN KHÁCH HÀNG (BASIC) -->
<div id="editProfileModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px;">
  <div style="background: #ffffff; width: min(500px, 94vw); border-radius: 4px; border: 1px solid #c8c8c8; box-shadow: 0 10px 25px rgba(0,0,0,0.18); font-family: Arial, sans-serif; overflow: hidden;">
    <!-- Header -->
    <div style="background: #f5f5f5; padding: 14px 20px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #222;">Cập nhật thông tin cá nhân</h3>
      <button type="button" onclick="closeEditProfileModal()" style="background: none; border: none; font-size: 20px; color: #666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
    </div>

    <!-- Body -->
    <form id="editProfileForm" onsubmit="handleProfileUpdate(event)" style="padding: 20px 24px;">
      <div id="profileUpdateAlert" style="display: none; padding: 10px 14px; border-radius: 4px; margin-bottom: 14px; font-size: 13.5px; font-weight: 600;"></div>

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Họ và tên *</label>
        <input type="text" id="editHoTen" value="<?= htmlspecialchars($hoten) ?>" required
               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none;">
      </div>

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Số điện thoại (10 chữ số) *</label>
        <input type="tel" id="editPhone" value="<?= htmlspecialchars($phone === 'Chưa cập nhật' ? '' : $phone) ?>" placeholder="Ví dụ: 0912345678"
               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none;">
      </div>

      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Địa chỉ nhận hàng chi tiết</label>
        <textarea id="editAddress" rows="3" placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."
                  style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none; resize: vertical;"><?= htmlspecialchars($diachi === 'Chưa cập nhật' ? '' : $diachi) ?></textarea>
      </div>

      <!-- Footer -->
      <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #eee;">
        <button type="button" onclick="closeEditProfileModal()" style="padding: 8px 18px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
        <button type="submit" id="btnSaveProfile" style="padding: 8px 22px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #fff; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditProfileModal() {
  const modal = document.getElementById('editProfileModal');
  if (modal) modal.style.display = 'grid';
  const alert = document.getElementById('profileUpdateAlert');
  if (alert) alert.style.display = 'none';
}

function closeEditProfileModal() {
  const modal = document.getElementById('editProfileModal');
  if (modal) modal.style.display = 'none';
}

async function handleProfileUpdate(e) {
  e.preventDefault();
  const hoten = document.getElementById('editHoTen').value.trim();
  const phone = document.getElementById('editPhone').value.replace(/\s+/g, '');
  const address = document.getElementById('editAddress').value.trim();
  const alert = document.getElementById('profileUpdateAlert');

  if (!hoten || hoten.length < 2) {
    showAlert('Họ và tên phải có ít nhất 2 ký tự.', false);
    document.getElementById('editHoTen').focus();
    return;
  }

  if (phone && !/^0\d{9}$/.test(phone)) {
    showAlert('Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng số 0 (Ví dụ: 0912345678).', false);
    document.getElementById('editPhone').focus();
    return;
  }

  const btn = document.getElementById('btnSaveProfile');
  const oldText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '⏳ Đang lưu...';

  try {
    const resp = await fetch('index.php?action=update_profile', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ hoten, phone, address })
    });
    const res = await resp.json();

    if (res.success) {
      showAlert('✓ ' + (res.message || 'Cập nhật thành công!'), true);
      document.getElementById('displayHoTen').textContent = hoten;
      if (document.getElementById('displayPhone')) document.getElementById('displayPhone').textContent = phone || 'Chưa cập nhật';
      if (document.getElementById('displayAddress')) document.getElementById('displayAddress').textContent = address || 'Chưa cập nhật';

      try {
        localStorage.setItem('kibo_customer_info', JSON.stringify({
          name: hoten,
          phone: phone,
          address: address,
          email: '<?= htmlspecialchars($email) ?>'
        }));
      } catch (e) {}

      setTimeout(() => {
        closeEditProfileModal();
        window.location.reload();
      }, 1000);
    } else {
      showAlert(res.message || 'Có lỗi xảy ra, vui lòng thử lại.', false);
    }
  } catch (err) {
    showAlert('Lỗi kết nối máy chủ. Vui lòng thử lại!', false);
  } finally {
    btn.disabled = false;
    btn.innerHTML = oldText;
  }
}

function showAlert(msg, isSuccess) {
  const alert = document.getElementById('profileUpdateAlert');
  if (!alert) return;
  alert.textContent = msg;
  alert.style.display = 'block';
  alert.style.background = isSuccess ? '#eaf4e6' : '#fdf2f2';
  alert.style.color = isSuccess ? '#2e7d32' : '#c94336';
  alert.style.border = isSuccess ? '1px solid #b7deb2' : '1px solid #f8d7da';
}

function openReturnModal(orderCode) {
  document.getElementById('returnOrderCode').value = orderCode;
  document.getElementById('displayReturnOrderCode').value = orderCode;
  document.getElementById('returnReason').value = '';
  document.getElementById('returnAlert').style.display = 'none';
  document.getElementById('returnModal').style.display = 'grid';
}

function closeReturnModal() {
  document.getElementById('returnModal').style.display = 'none';
}

async function handleReturnSubmit(e) {
  e.preventDefault();
  const madonhang = document.getElementById('returnOrderCode').value.trim();
  const lydo = document.getElementById('returnReason').value.trim();
  const btn = document.getElementById('btnSubmitReturn');
  const alertEl = document.getElementById('returnAlert');

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
        closeReturnModal();
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

<!-- MODAL YÊU CẦU ĐỔI TRẢ (BASIC) -->
<div id="returnModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px;">
  <div style="background: #ffffff; width: min(480px, 94vw); border-radius: 4px; border: 1px solid #c8c8c8; box-shadow: 0 10px 25px rgba(0,0,0,0.18); font-family: Arial, sans-serif; overflow: hidden;">
    <div style="background: #f5f5f5; padding: 14px 20px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #222;">Yêu cầu đổi trả sản phẩm (Chính sách 3 ngày)</h3>
      <button type="button" onclick="closeReturnModal()" style="background: none; border: none; font-size: 20px; color: #666; cursor: pointer; line-height: 1;">✕</button>
    </div>
    <form id="returnForm" onsubmit="handleReturnSubmit(event)" style="padding: 20px 24px;">
      <div id="returnAlert" style="display: none; padding: 10px 14px; border-radius: 4px; margin-bottom: 14px; font-size: 13.5px; font-weight: 600;"></div>
      <input type="hidden" id="returnOrderCode">
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Mã đơn hàng</label>
        <input type="text" id="displayReturnOrderCode" readonly style="width: 100%; height: 38px; padding: 0 12px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; font-weight: bold; color: #2c1a0e;">
      </div>
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Lý do đổi trả *</label>
        <textarea id="returnReason" rows="3" required placeholder="Ví dụ: Bao bì còn nguyên vẹn, muốn đổi sang hạt cho mèo con hoặc đổi phân loại..." style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none; resize: vertical;"></textarea>
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #eee;">
        <button type="button" onclick="closeReturnModal()" style="padding: 8px 18px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
        <button type="submit" id="btnSubmitReturn" style="padding: 8px 22px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #fff; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">Gửi yêu cầu</button>
      </div>
    </form>
  </div>
</div>
</script>

<?php require __DIR__ . '/footer.php'; ?>
