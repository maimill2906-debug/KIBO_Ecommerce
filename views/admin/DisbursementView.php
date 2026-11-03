<?php
// ====================================================================
// VIEW: DisbursementView.php
// Quản lý Giải ngân Quỹ cứu trợ ngày 30 hàng tháng
// Tương tác trực tiếp bảng chung_tu_giai_ngan, tram_cuu_ho, quy_cuu_tro
// ====================================================================

require_once __DIR__ . '/../../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../../models/TramCuuHoModel.php';

$fundSummary = QuyCuuTroModel::getFundSummary();
$shelters = TramCuuHoModel::getShelters();
$disbursements = TramCuuHoModel::getDisbursements();
$fundTransactions = QuyCuuTroModel::getRecentTransactions(20);
$choGiaiNgan = max(0, $fundSummary['TongTichLuy'] - $fundSummary['TongGiaiNgan']);
?>
<div id="admin-disbursement-view-component" class="ops-disbursement-container" style="padding: 20px; font-family: Arial, sans-serif;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h2 style="margin: 0; color: #1565c0; font-size: 22px;">Quản lý Giải ngân Quỹ Cứu Trợ 5%</h2>
      <p style="margin: 4px 0 0; color: #666; font-size: 14px;">Minh bạch dòng tiền giải ngân định kỳ ngày 30 hàng tháng cho các trạm cứu hộ đối tác</p>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 6px; padding: 16px;">
      <small style="color: #2e7d32; font-weight: bold;">Tổng quỹ đã trích</small>
      <div style="font-size: 22px; font-weight: bold; color: #2e7d32; margin-top: 4px;"><?= number_format($fundSummary['TongTichLuy'], 0, ',', '.') ?>đ</div>
      <p style="margin: 4px 0 0; font-size: 12px; color: #555;">Từ <?= (int)$fundSummary['SoDonDongGop'] ?> đơn hàng giao thành công</p>
    </div>
    <div style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 6px; padding: 16px;">
      <small style="color: #1565c0; font-weight: bold;">Đã giải ngân thực tế</small>
      <div style="font-size: 22px; font-weight: bold; color: #1565c0; margin-top: 4px;"><?= number_format($fundSummary['TongGiaiNgan'], 0, ',', '.') ?>đ</div>
      <p style="margin: 4px 0 0; font-size: 12px; color: #555;">Kỳ giải ngân gần nhất: <?= htmlspecialchars($fundSummary['KyGiaiNgan']) ?></p>
    </div>
    <div style="background: #fff3e0; border: 1px solid #ffe0b2; border-radius: 6px; padding: 16px;">
      <small style="color: #e65100; font-weight: bold;">Quỹ sẵn sàng giải ngân</small>
      <div style="font-size: 22px; font-weight: bold; color: #e65100; margin-top: 4px;"><?= number_format($choGiaiNgan, 0, ',', '.') ?>đ</div>
      <p style="margin: 4px 0 0; font-size: 12px; color: #555;"><?= (int)$fundSummary['SoTramDoiTac'] ?> trạm cứu hộ đối tác hợp tác</p>
    </div>
  </div>

  <!-- BẢNG CHỨNG TỪ GIẢI NGÂN -->
  <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden; margin-bottom: 24px;">
    <div style="padding: 14px 18px; background: #fafafa; border-bottom: 1px solid #e0e0e0; font-weight: bold; font-size: 15px; color: #333;">
      Lịch sử Chứng từ Giải ngân (chung_tu_giai_ngan)
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
      <thead>
        <tr style="background: #f5f5f5; text-align: left; border-bottom: 1px solid #ddd;">
          <th style="padding: 10px 14px;">Mã CT</th>
          <th style="padding: 10px 14px;">Trạm cứu hộ nhận</th>
          <th style="padding: 10px 14px;">Số tiền giải ngân</th>
          <th style="padding: 10px 14px;">Ngày chuyển</th>
          <th style="padding: 10px 14px;">Mục đích sử dụng</th>
          <th style="padding: 10px 14px;">Chứng từ sao kê</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($disbursements)): ?>
          <?php foreach ($disbursements as $ct): ?>
            <tr style="border-bottom: 1px solid #eee;">
              <td style="padding: 10px 14px;"><code><?= htmlspecialchars($ct['MaChungTu']) ?></code></td>
              <td style="padding: 10px 14px; font-weight: bold;"><?= htmlspecialchars($ct['TenTram']) ?></td>
              <td style="padding: 10px 14px; color: #1565c0; font-weight: bold;"><?= number_format($ct['SoTienGiaiNgan'], 0, ',', '.') ?>đ</td>
              <td style="padding: 10px 14px;"><?= date('d/m/Y', strtotime($ct['NgayChuyenKhoan'])) ?></td>
              <td style="padding: 10px 14px; color: #555;"><?= htmlspecialchars($ct['MucDichSuDung']) ?></td>
              <td style="padding: 10px 14px;">
                <?php if (!empty($ct['FileHoaDonChungTu'])): ?>
                  <a href="<?= htmlspecialchars($ct['FileHoaDonChungTu']) ?>" target="_blank" style="color: #1565c0; text-decoration: underline;">Xem file ↗</a>
                <?php else: ?>
                  <span style="color: #999;">Chưa đính kèm</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="padding: 20px; text-align: center; color: #888;">Chưa có chứng từ giải ngân nào.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
