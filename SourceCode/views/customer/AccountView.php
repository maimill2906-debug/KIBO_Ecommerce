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
require_once __DIR__ . '/../../models/VoucherModel.php';

$pageTitle = 'Hồ Sơ Khách Hàng - KIBO PET\'S HOME';
$activeMenu = 'account';
require __DIR__ . '/header.php';

$cust = KhachHangModel::getCustomerDetail($currentUser['TenDangNhap']);
$maKH = $cust['MaKH'] ?? ($currentUser['MaKH'] ?? '');
$hoten = $cust['HoTen'] ?? ($currentUser['HoTen'] ?? 'Khách hàng KIBO');
$email = $cust['Email'] ?? ($currentUser['TenDangNhap'] ?? '');
$phone = $cust['SoDienThoaiChinh'] ?? ($currentUser['SoDienThoai'] ?? 'Chưa cập nhật');
$diachi = $cust['diachichitiet'] ?? ($currentUser['DiaChi'] ?? '');
$phuongXa = $cust['phuongxa'] ?? ($currentUser['PhuongXa'] ?? '');
$tinhThanh = $cust['tinhthanh'] ?? ($currentUser['TinhThanh'] ?? '');
$diachiParts = array_filter([$diachi, $phuongXa, $tinhThanh], function($v) {
    $t = trim((string)$v);
    return $t !== '' && $t !== 'Chưa cập nhật';
});
$diachiDisplay = !empty($diachiParts) ? implode(', ', $diachiParts) : 'Chưa cập nhật';
$ngaysinhRaw = $cust['NgaySinh'] ?? ($currentUser['NgaySinh'] ?? '');
$ngaysinhDisplay = !empty($ngaysinhRaw) ? date('d/m/Y', strtotime($ngaysinhRaw)) : 'Chưa cập nhật';
$diem = $cust['DiemTichLuy'] ?? 0;

$myOrders = DonHangModel::getOrdersByCustomer($maKH);
$myVouchers = VoucherModel::getAvailableForCustomer($maKH);

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
      <a href="index.php?action=logout" onclick="try{localStorage.removeItem('kibo_cart_items_v2');localStorage.removeItem('kibo_cart_owner');localStorage.removeItem('kibo_customer_info');}catch(e){}" style="padding: 8px 18px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; text-decoration: none; font-weight: 500;">
        Đăng xuất
      </a>
    </div>

    <!-- Grid: 2 Cột -->
    <div class="account-profile-grid">
      
      <!-- CỘT TRÁI: THÔNG TIN KHÁCH HÀNG -->
      <section style="background: #ffffff; border: 1px solid #dcdcdc; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); overflow: hidden;">
        <div style="background: #f7f7f7; padding: 14px 20px; border-bottom: 1px solid #e5e5e5; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
          <h3 style="margin: 0; font-size: 15px; font-weight: bold; color: #222;">Thông tin cá nhân</h3>
          <div style="display: flex; gap: 6px;">
            <button type="button" onclick="openChangePasswordModal()" style="padding: 4px 10px; border: 1px solid #767676; background: #fff; color: #111; border-radius: 3px; font-size: 12.5px; font-weight: 500; cursor: pointer;">
              🔑 Đổi mật khẩu
            </button>
          </div>
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
                <td style="color: #777; width: 110px; padding: 4px 0;">Số điện thoại:</td>
                <td id="displayPhone" style="font-weight: 600; color: #222; padding: 4px 0;"><?= htmlspecialchars($phone) ?></td>
              </tr>
              <tr>
                <td style="color: #777; padding: 4px 0;">Ngày sinh:</td>
                <td id="displayNgaySinh" style="color: #333; padding: 4px 0;"><?= htmlspecialchars($ngaysinhDisplay) ?></td>
              </tr>
              <tr>
                <td style="color: #777; padding: 4px 0; vertical-align: top;">Địa chỉ:</td>
                <td id="displayAddress" style="color: #333; padding: 4px 0;"><?= htmlspecialchars($diachiDisplay) ?></td>
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
            Cập nhật thông tin
          </button>

          <!-- KHO VOUCHER & ƯU ĐÃI DÀNH CHO THÀNH VIÊN -->
          <div style="margin-top: 22px; padding-top: 18px; border-top: 1px solid #e5e5e5;">
            <h4 style="margin: 0 0 10px; font-size: 14px; font-weight: 700; color: #2c1a0e; display: flex; align-items: center; gap: 6px;">
              🎁 Kho Voucher &amp; Ưu đãi của bạn
            </h4>
            <?php if (empty($myVouchers)): ?>
              <p style="font-size: 13px; color: #777; margin: 0;">Hiện chưa có mã giảm giá nào đang kích hoạt.</p>
            <?php else: ?>
              <div style="display: grid; gap: 10px;">
                <?php foreach ($myVouchers as $vc): 
                  $isUsed = !empty($vc['DaSuDung']);
                ?>
                  <div style="padding: 12px 14px; border: 1px dashed <?= $isUsed ? '#ccc' : '#c94336' ?>; background: <?= $isUsed ? '#f9f9f9' : '#fffaf7' ?>; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; gap: 10px; opacity: <?= $isUsed ? '0.65' : '1' ?>;">
                    <div>
                      <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="background: <?= $isUsed ? '#888' : '#c94336' ?>; color: #fff; font-weight: 700; font-size: 12px; padding: 3px 8px; border-radius: 4px;">
                          <?= htmlspecialchars($vc['MaVoucher']) ?>
                        </span>
                        <strong style="font-size: 14px; color: #222;"><?= htmlspecialchars($vc['TenVoucher']) ?></strong>
                      </div>
                      <small style="display: block; color: #666; font-size: 12.5px; margin-top: 4px;">
                        Đơn từ <?= number_format((float)$vc['DonHangToiThieu'], 0, ',', '.') ?>₫
                      </small>
                    </div>
                    <?php if ($isUsed): ?>
                      <span style="font-size: 12.5px; color: #777; font-weight: 600; white-space: nowrap;">Đã dùng</span>
                    <?php else: ?>
                      <a href="index.php?action=cart&step=checkout&voucher=<?= urlencode($vc['MaVoucher']) ?>" style="padding: 6px 12px; background: #2c1a0e; color: #fff; text-decoration: none; border-radius: 4px; font-size: 12.5px; font-weight: 600; white-space: nowrap;">
                        Dùng ngay
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
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
                        <span style="background: #f5f5f5; color: #333333; border: 1px solid #d4d4d4; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: 600; display: inline-block;">
                          <?= htmlspecialchars($st) ?>
                        </span>
                        <?php
                          $ttStatus = $ord['trangthaitt'] ?? '';
                          $isOrderPaid = ($ttStatus === 'Thành công');
                        ?>
                        <?php if ($ttStatus === 'Chờ hoàn tiền'): ?>
                          <div style="margin-top: 4px; font-size: 11.5px; color: #555555;">Chờ hoàn tiền</div>
                        <?php elseif ($ttStatus === 'Hoàn tiền'): ?>
                          <div style="margin-top: 4px; font-size: 11.5px; color: #2e7d32; font-weight: 600;">Đã hoàn tiền</div>
                        <?php endif; ?>
                      </td>
                      <td style="padding: 12px; text-align: center;">
                        <a href="index.php?action=tracking&order=<?= urlencode($ord['madonhang']) ?>" style="padding: 5px 12px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 3px; text-decoration: none; font-size: 12.5px; font-weight: 500; display: inline-block;">
                          Chi tiết
                        </a>
                        <?php 
                          $giaoTs = !empty($ord['ngaygiaothanhcong']) ? strtotime($ord['ngaygiaothanhcong']) : strtotime($ord['ngaydathang']);
                          $remainSecs = (3 * 86400) - (time() - $giaoTs);
                          $remainDays = (int)ceil($remainSecs / 86400);
                        ?>
                        <?php if (isset($returnMap[$ord['madonhang']])): ?>
                          <div style="margin-top: 6px; font-size: 12px; color: #333333; background: #f5f5f5; border: 1px solid #d4d4d4; border-radius: 3px; padding: 4px 8px; font-weight: 600;">
                            Đổi trả: <?= htmlspecialchars($returnMap[$ord['madonhang']]['TrangThaiYeuCau']) ?>
                          </div>
                        <?php elseif (($st === 'Giao thành công' || $st === 'Giao hàng thành công') && $remainDays > 0): ?>
                          <button type="button" onclick="openReturnModal('<?= htmlspecialchars($ord['madonhang']) ?>')" style="margin-top: 6px; padding: 6px 10px; border: 1px solid #c8c8c8; background: #fff; color: #333; border-radius: 3px; font-size: 12px; cursor: pointer; display: block; width: 100%; font-weight: 500;">
                            Đổi trả (Còn <?= $remainDays ?> ngày)
                          </button>
                        <?php elseif (in_array($st, ['Chờ xác nhận', 'Chờ đóng gói'], true)): ?>
                          <button type="button" onclick="cancelOrder('<?= htmlspecialchars($ord['madonhang']) ?>', <?= $isOrderPaid ? 'true' : 'false' ?>)" style="margin-top: 6px; padding: 6px 10px; border: 1px solid #c8c8c8; background: #ffffff; color: #333333; border-radius: 3px; font-size: 12.5px; font-weight: 500; cursor: pointer; display: block; width: 100%;">
                            Hủy đơn
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

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Ngày sinh</label>
        <input type="date" id="editNgaySinh" value="<?= htmlspecialchars($ngaysinhRaw ? date('Y-m-d', strtotime($ngaysinhRaw)) : '') ?>" max="<?= date('Y-m-d') ?>"
               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none;">
      </div>

      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Địa chỉ nhận hàng (Số nhà, tên đường...)</label>
        <input type="text" id="editAddress" placeholder="Số nhà, tên đường..."
               value="<?= htmlspecialchars($diachi === 'Chưa cập nhật' ? '' : $diachi) ?>"
               style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; outline: none;">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Tỉnh / Thành phố</label>
          <select id="editProvinceSelect" onchange="handleEditProvinceChange(this.value)"
                  style="width: 100%; padding: 10px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box; outline: none; background: #fff;">
            <option value="">-- Chọn Tỉnh / Thành phố --</option>
          </select>
        </div>
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Phường / Xã / Đặc khu</label>
          <select id="editWardSelect"
                  style="width: 100%; padding: 10px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box; outline: none; background: #fff;">
            <option value="">-- Chọn Phường / Xã --</option>
          </select>
        </div>
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
const SAVED_PROFILE_PROVINCE = <?= json_encode($tinhThanh, JSON_UNESCAPED_UNICODE) ?>;
const SAVED_PROFILE_WARD = <?= json_encode($phuongXa, JSON_UNESCAPED_UNICODE) ?>;
let editProvincesLoaded = false;
const EDIT_WARDS_CACHE = {};

function escapeAccountHtml(str) {
  return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

async function loadEditProvinces() {
  if (editProvincesLoaded) return;
  const provSel = document.getElementById('editProvinceSelect');
  if (!provSel) return;
  provSel.innerHTML = '<option value="">-- Đang tải... --</option>';
  try {
    const res = await fetch('index.php?action=get_provinces');
    const data = await res.json();
    if (data.success && Array.isArray(data.provinces) && data.provinces.length > 0) {
      provSel.innerHTML = '<option value="">-- Chọn Tỉnh / Thành phố --</option>' +
        data.provinces.map(p => `<option value="${escapeAccountHtml(p.code)}">${escapeAccountHtml(p.full_name)}</option>`).join('');
      editProvincesLoaded = true;
      if (SAVED_PROFILE_PROVINCE) {
        const target = SAVED_PROFILE_PROVINCE.trim().toLowerCase();
        const matched = data.provinces.find(p =>
          (p.full_name || '').trim().toLowerCase() === target ||
          (p.name || '').trim().toLowerCase() === target
        );
        if (matched) {
          provSel.value = matched.code;
          await handleEditProvinceChange(matched.code, SAVED_PROFILE_WARD);
        }
      }
    } else {
      provSel.innerHTML = '<option value="">-- Không thể tải Tỉnh / Thành --</option>';
    }
  } catch (e) {
    provSel.innerHTML = '<option value="">-- Lỗi tải Tỉnh / Thành --</option>';
  }
}

async function handleEditProvinceChange(provinceCode, preselectWard = '') {
  const wardSel = document.getElementById('editWardSelect');
  if (!wardSel) return;
  if (!provinceCode) {
    wardSel.innerHTML = '<option value="">-- Chọn Phường / Xã --</option>';
    return;
  }

  const renderWards = (wards) => {
    wardSel.innerHTML = '<option value="">-- Chọn Phường / Xã / Đặc khu --</option>' +
      wards.map(w => `<option value="${escapeAccountHtml(w.full_name)}">${escapeAccountHtml(w.full_name)}</option>`).join('');
    if (preselectWard) {
      const target = preselectWard.trim().toLowerCase();
      const matched = wards.find(w => (w.full_name || '').trim().toLowerCase() === target);
      if (matched) wardSel.value = matched.full_name;
    }
  };

  if (EDIT_WARDS_CACHE[provinceCode]) {
    renderWards(EDIT_WARDS_CACHE[provinceCode]);
    return;
  }

  wardSel.innerHTML = '<option value="">-- Đang tải... --</option>';
  try {
    const res = await fetch('index.php?action=get_wards&province_code=' + encodeURIComponent(provinceCode));
    const data = await res.json();
    if (data.success && Array.isArray(data.wards) && data.wards.length > 0) {
      EDIT_WARDS_CACHE[provinceCode] = data.wards;
      renderWards(data.wards);
    } else {
      wardSel.innerHTML = '<option value="">-- Không có dữ liệu --</option>';
    }
  } catch (e) {
    wardSel.innerHTML = '<option value="">-- Lỗi tải Phường / Xã --</option>';
  }
}

function openEditProfileModal() {
  const modal = document.getElementById('editProfileModal');
  if (modal) modal.style.display = 'grid';
  const alert = document.getElementById('profileUpdateAlert');
  if (alert) alert.style.display = 'none';
  loadEditProvinces();
}

function closeEditProfileModal() {
  const modal = document.getElementById('editProfileModal');
  if (modal) modal.style.display = 'none';
}

async function handleProfileUpdate(e) {
  e.preventDefault();
  const hoten = document.getElementById('editHoTen').value.trim();
  const phone = document.getElementById('editPhone').value.replace(/\s+/g, '');
  const ngaysinh = document.getElementById('editNgaySinh') ? document.getElementById('editNgaySinh').value.trim() : '';
  const address = document.getElementById('editAddress').value.trim();
  const provSel = document.getElementById('editProvinceSelect');
  const tinhthanh = (provSel && provSel.value) ? provSel.options[provSel.selectedIndex].text.trim() : '';
  const wardSel = document.getElementById('editWardSelect');
  const phuongxa = wardSel ? wardSel.value.trim() : '';
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
      body: JSON.stringify({ hoten, phone, ngaysinh, address, phuongxa, tinhthanh })
    });
    const res = await resp.json();

    if (res.success) {
      showAlert('✓ ' + (res.message || 'Cập nhật thành công!'), true);
      const fullAddressText = [address, phuongxa, tinhthanh].filter(Boolean).join(', ');
      document.getElementById('displayHoTen').textContent = hoten;
      if (document.getElementById('displayPhone')) document.getElementById('displayPhone').textContent = phone || 'Chưa cập nhật';
      if (document.getElementById('displayAddress')) document.getElementById('displayAddress').textContent = fullAddressText || 'Chưa cập nhật';

      try {
        localStorage.setItem('kibo_customer_info', JSON.stringify({
          name: hoten,
          phone: phone,
          address: address,
          ward: phuongxa,
          province: tinhthanh,
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
  alert.style.background = '#f7f7f7';
  alert.style.color = '#222222';
  alert.style.border = isSuccess ? '1px solid #dcdcdc' : '1px solid #999999';
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
    alertEl.style.background = '#f7f7f7';
    alertEl.style.color = '#222222';
    alertEl.style.border = '1px solid #999999';
    return;
  }

  const imageInput = document.getElementById('returnProofImage');
  btn.disabled = true;
  btn.innerHTML = '⏳ Đang gửi...';

  try {
    const formData = new FormData();
    formData.append('madonhang', madonhang);
    formData.append('lydo', lydo);
    if (imageInput && imageInput.files && imageInput.files[0]) {
      formData.append('hinhanh_file', imageInput.files[0]);
    }

    const resp = await fetch('index.php?action=submit_return', {
      method: 'POST',
      body: formData
    });
    const res = await resp.json();
    if (res.success) {
      alertEl.textContent = '✓ ' + (res.message || 'Gửi yêu cầu thành công!');
      alertEl.style.display = 'block';
      alertEl.style.background = '#f7f7f7';
      alertEl.style.color = '#222222';
      alertEl.style.border = '1px solid #dcdcdc';
      setTimeout(() => {
        closeReturnModal();
        window.location.reload();
      }, 1200);
    } else {
      alertEl.textContent = res.message || 'Có lỗi xảy ra, vui lòng thử lại.';
      alertEl.style.display = 'block';
      alertEl.style.background = '#f7f7f7';
      alertEl.style.color = '#222222';
      alertEl.style.border = '1px solid #999999';
    }
  } catch (err) {
    alertEl.textContent = 'Lỗi kết nối máy chủ.';
    alertEl.style.display = 'block';
    alertEl.style.background = '#f7f7f7';
    alertEl.style.color = '#222222';
    alertEl.style.border = '1px solid #999999';
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Gửi yêu cầu';
  }
}

function openChangePasswordModal() {
  document.getElementById('changePasswordModal').style.display = 'grid';
  document.getElementById('changePasswordAlert').style.display = 'none';
  document.getElementById('changePasswordForm').reset();
}

function closeChangePasswordModal() {
  document.getElementById('changePasswordModal').style.display = 'none';
}

async function handleChangePasswordSubmit(e) {
  e.preventDefault();
  const oldPass = document.getElementById('oldPassword').value;
  const newPass = document.getElementById('newPassword').value;
  const confirmPass = document.getElementById('confirmNewPassword').value;
  const alertEl = document.getElementById('changePasswordAlert');
  const btn = document.getElementById('btnSubmitPassword');

  if (newPass.length < 8) {
    alertEl.textContent = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
    alertEl.style.display = 'block';
    alertEl.style.background = '#f7f7f7';
    alertEl.style.color = '#222222';
    alertEl.style.border = '1px solid #999999';
    return;
  }

  if (newPass !== confirmPass) {
    alertEl.textContent = 'Mật khẩu xác nhận không khớp.';
    alertEl.style.display = 'block';
    alertEl.style.background = '#f7f7f7';
    alertEl.style.color = '#222222';
    alertEl.style.border = '1px solid #999999';
    return;
  }

  btn.disabled = true;
  btn.textContent = '⏳ Đang xử lý...';

  try {
    const resp = await fetch('index.php?action=change_password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ old_password: oldPass, new_password: newPass })
    });
    const res = await resp.json();
    if (res.success) {
      alertEl.textContent = '✓ ' + (res.message || 'Đổi mật khẩu thành công!');
      alertEl.style.display = 'block';
      alertEl.style.background = '#f7f7f7';
      alertEl.style.color = '#222222';
      alertEl.style.border = '1px solid #dcdcdc';
      setTimeout(() => {
        closeChangePasswordModal();
      }, 1500);
    } else {
      alertEl.textContent = res.message || 'Không thể đổi mật khẩu.';
      alertEl.style.display = 'block';
      alertEl.style.background = '#f7f7f7';
      alertEl.style.color = '#222222';
      alertEl.style.border = '1px solid #999999';
    }
  } catch (err) {
    alertEl.textContent = 'Lỗi kết nối máy chủ.';
    alertEl.style.display = 'block';
    alertEl.style.background = '#f7f7f7';
    alertEl.style.color = '#222222';
    alertEl.style.border = '1px solid #999999';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Lưu mật khẩu mới';
  }
}

async function cancelOrder(orderId, isPaid = false) {
  if (isPaid) {
    openRefundCancelModal(orderId);
    return;
  }
  const ok = await kiboConfirm(`Bạn có chắc chắn muốn hủy đơn hàng #${orderId} không?`, 'Xác nhận hủy đơn hàng', 'Xác nhận hủy', 'Đóng');
  if (!ok) return;
  await submitCancelOrderRequest(orderId, null);
}

async function submitCancelOrderRequest(orderId, refundBank) {
  try {
    const payload = {
      madonhang: orderId,
      csrf_token: window.KIBO_CSRF_TOKEN || ''
    };
    if (refundBank) {
      payload.refund_bank = refundBank;
    }
    const res = await fetch('index.php?action=cancel_order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      await kiboAlert(data.message || 'Hủy đơn hàng thành công!', 'Thông báo');
      window.location.reload();
    } else {
      await kiboAlert(data.message || 'Không thể hủy đơn hàng.', 'Thông báo');
    }
  } catch (e) {
    await kiboAlert('Lỗi kết nối khi gửi yêu cầu hủy đơn.', 'Thông báo');
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
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Hình ảnh minh chứng (JPG/PNG, tối đa 5MB)</label>
        <input type="file" id="returnProofImage" accept="image/jpeg,image/png,image/webp" style="width: 100%; font-size: 13px; padding: 4px 0;">
        <small style="color: #777; font-size: 11.5px; display: block; margin-top: 3px;">Tải ảnh thực tế sản phẩm/bao bì để cửa hàng hỗ trợ nhanh nhất.</small>
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #eee;">
        <button type="button" onclick="closeReturnModal()" style="padding: 8px 18px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
        <button type="submit" id="btnSubmitReturn" style="padding: 8px 22px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #fff; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">Gửi yêu cầu</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ĐỔI MẬT KHẨU -->
<div id="changePasswordModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px;">
  <div style="background: #ffffff; width: min(440px, 94vw); border-radius: 4px; border: 1px solid #c8c8c8; box-shadow: 0 10px 25px rgba(0,0,0,0.18); font-family: Arial, sans-serif; overflow: hidden;">
    <div style="background: #f5f5f5; padding: 14px 20px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #222;">Đổi mật khẩu tài khoản</h3>
      <button type="button" onclick="closeChangePasswordModal()" style="background: none; border: none; font-size: 20px; color: #666; cursor: pointer; line-height: 1;">✕</button>
    </div>
    <form id="changePasswordForm" onsubmit="handleChangePasswordSubmit(event)" style="padding: 20px 24px;">
      <div id="changePasswordAlert" style="display: none; padding: 10px 14px; border-radius: 4px; margin-bottom: 14px; font-size: 13.5px; font-weight: 600;"></div>
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Mật khẩu hiện tại *</label>
        <input type="password" id="oldPassword" required placeholder="Nhập mật khẩu đang dùng" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
      </div>
      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Mật khẩu mới (tối thiểu 8 ký tự) *</label>
        <input type="password" id="newPassword" required minlength="8" placeholder="Nhập mật khẩu mới" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
      </div>
      <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px;">Xác nhận mật khẩu mới *</label>
        <input type="password" id="confirmNewPassword" required minlength="8" placeholder="Nhập lại mật khẩu mới" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #eee;">
        <button type="button" onclick="closeChangePasswordModal()" style="padding: 8px 18px; border: 1px solid #767676; background: #efefef; color: #111; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Hủy bỏ</button>
        <button type="submit" id="btnSubmitPassword" style="padding: 8px 22px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #fff; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">Lưu mật khẩu mới</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL HỦY ĐƠN ĐÃ THANH TOÁN & NHẬP TÀI KHOẢN HOÀN TIỀN -->
<div id="refundCancelModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 999999; place-items: center; padding: 16px;">
  <div style="background: #ffffff; width: min(460px, 94vw); border-radius: 4px; border: 1px solid #c8c8c8; box-shadow: 0 10px 28px rgba(0,0,0,0.2); font-family: Arial, sans-serif; overflow: hidden;">
    <div style="background: #f5f5f5; padding: 13px 18px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #222222;">Hủy đơn &amp; Yêu cầu hoàn tiền</h3>
      <button type="button" onclick="closeRefundCancelModal()" style="background: none; border: none; font-size: 18px; color: #666666; cursor: pointer; line-height: 1; padding: 0 4px;">✕</button>
    </div>
    <form id="refundCancelForm" onsubmit="handleRefundCancelSubmit(event)" style="padding: 18px 20px;">
      <input type="hidden" id="refundCancelOrderId">
      <p style="margin: 0 0 14px; font-size: 13.5px; line-height: 1.5; color: #333333;">
        Đơn hàng <strong id="refundCancelOrderText">#</strong> đã được thanh toán. Vui lòng nhập thông tin tài khoản ngân hàng để cửa hàng chuyển khoản hoàn tiền cho bạn:
      </p>
      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px;">Tên ngân hàng *</label>
        <input type="text" id="refundBankName" required placeholder="VD: Vietcombank, MB Bank, Techcombank..." style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box;">
      </div>
      <div style="margin-bottom: 12px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px;">Số tài khoản *</label>
        <input type="text" id="refundAccountNumber" required placeholder="Nhập số tài khoản nhận tiền hoàn" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box;">
      </div>
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px;">Tên chủ tài khoản *</label>
        <input type="text" id="refundAccountHolder" required placeholder="VD: NGUYEN VAN A" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13.5px; box-sizing: border-box;">
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 12px; border-top: 1px solid #eee;">
        <button type="button" onclick="closeRefundCancelModal()" style="padding: 8px 18px; border: 1px solid #cccccc; background: #ffffff; color: #333333; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Đóng</button>
        <button type="submit" id="btnSubmitRefundCancel" style="padding: 8px 18px; border: 1px solid #2c1a0e; background: #2c1a0e; color: #ffffff; border-radius: 4px; font-size: 13.5px; font-weight: 600; cursor: pointer;">Xác nhận hủy &amp; Hoàn tiền</button>
      </div>
    </form>
  </div>
</div>

<script>
function openRefundCancelModal(orderId) {
  document.getElementById('refundCancelOrderId').value = orderId;
  document.getElementById('refundCancelOrderText').textContent = '#' + orderId;
  document.getElementById('refundBankName').value = '';
  document.getElementById('refundAccountNumber').value = '';
  document.getElementById('refundAccountHolder').value = '';
  document.getElementById('refundCancelModal').style.display = 'grid';
}
function closeRefundCancelModal() {
  document.getElementById('refundCancelModal').style.display = 'none';
}
async function handleRefundCancelSubmit(e) {
  e.preventDefault();
  const orderId = document.getElementById('refundCancelOrderId').value;
  const ngan_hang = document.getElementById('refundBankName').value.trim();
  const so_tai_khoan = document.getElementById('refundAccountNumber').value.trim();
  const chu_tai_khoan = document.getElementById('refundAccountHolder').value.trim();
  if (!ngan_hang || !so_tai_khoan || !chu_tai_khoan) return;

  const btn = document.getElementById('btnSubmitRefundCancel');
  btn.disabled = true;
  btn.textContent = 'Đang xử lý...';
  closeRefundCancelModal();
  await submitCancelOrderRequest(orderId, { ngan_hang, so_tai_khoan, chu_tai_khoan });
  btn.disabled = false;
  btn.textContent = 'Xác nhận hủy & Hoàn tiền';
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
