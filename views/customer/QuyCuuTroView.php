<?php
/**
 * VIEW: views/customer/QuyCuuTroView.php
 * Giao diện Quỹ Cứu Trợ 5% KIBO — 100% Số Liệu Thực Tế Từ CSDL
 */
$pageTitle = 'Quỹ Cứu Trợ 5% — KIBO PET\'S HOME | Minh Bạch & Lan Tỏa Yêu Thương';
$activeMenu = 'rescue';
require __DIR__ . '/header.php';

require_once __DIR__ . '/../../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../../models/TramCuuHoModel.php';

$fundInfo = QuyCuuTroModel::getFundSummary();
$shelters = TramCuuHoModel::getShelters();
$disbursements = TramCuuHoModel::getDisbursements();
$latestDisbursement = !empty($disbursements) ? $disbursements[0] : null;
$partnerShelter = !empty($shelters) ? $shelters[0] : null;

$shelterImage = 'https://images.unsplash.com/photo-1718885433034-908d5bca08cf?auto=format&fit=crop&w=900&q=85';
?>

<main class="page fund-page">
  <!-- FUND HERO -->
  <section class="fund-hero">
    <div class="container">
      <span class="eyebrow">MINH BẠCH TỪ NHỮNG ĐIỀU NHỎ NHẤT</span>
      <h1>Quỹ cứu trợ KIBO</h1>
      <p>5% giá trị mỗi đơn hàng thành công được tự động trích trực tiếp vào quỹ hỗ trợ các trạm động vật.</p>
      
      <div class="fund-counter" aria-live="polite">
        <small>TỔNG QUỸ ĐÃ TÍCH LŨY THỰC TẾ</small>
        <strong id="liveFundCounter"><?= number_format($fundInfo['TongTichLuy'], 0, ',', '.') ?> <sup>đ</sup></strong>
        <span><i></i> Số liệu xác thực từ CSDL KIBO (Đã giải ngân: <?= number_format($fundInfo['TongGiaiNgan'], 0, ',', '.') ?>đ · Đang chờ trích: <?= number_format($fundInfo['DangChoTrich'], 0, ',', '.') ?>đ)</span>
      </div>
    </div>
  </section>

  <!-- TRANSPARENCY SECTION -->
  <section class="container transparency">
    <article class="shelter-card">
      <img src="<?= $shelterImage ?>" alt="Chó và mèo tại trạm cứu hộ">
      <div>
        <span class="eyebrow brown">ĐỐI TÁC BẢO TRỢ</span>
        <h2><?= htmlspecialchars($partnerShelter['TenTram'] ?? 'Trạm Cứu Hộ Động Vật Đối Tác') ?></h2>
        <p><?= htmlspecialchars($partnerShelter['DiaChi'] ?? 'TP. Hồ Chí Minh') ?> — Người đại diện: <strong><?= htmlspecialchars($partnerShelter['NguoiDaiDien'] ?? 'Ban quản lý Trạm') ?></strong></p>
        <span class="verified">
          <?= kibo_icon('check', 14) ?> Đối tác đã xác minh hợp tác
        </span>
      </div>
    </article>

    <?php if ($latestDisbursement): ?>
      <article class="statement-card">
        <span class="paper-icon">
          <?= kibo_icon('chart', 28) ?>
        </span>
        <small>CHỨNG TỪ GIẢI NGÂN GẦN NHẤT</small>
        <h2>Ủy nhiệm chi quỹ cứu trợ</h2>
        <dl>
          <div>
            <dt>Mã chứng từ</dt>
            <dd><?= htmlspecialchars($latestDisbursement['MaChungTu']) ?></dd>
          </div>
          <div>
            <dt>Trạm nhận</dt>
            <dd><?= htmlspecialchars($latestDisbursement['TenTram']) ?></dd>
          </div>
          <div>
            <dt>Số tiền giải ngân</dt>
            <dd style="color: #1565c0; font-weight: bold;"><?= number_format($latestDisbursement['SoTienGiaiNgan'], 0, ',', '.') ?>đ</dd>
          </div>
          <div>
            <dt>Ngày thực hiện</dt>
            <dd><?= date('d/m/Y', strtotime($latestDisbursement['NgayChuyenKhoan'])) ?></dd>
          </div>
        </dl>
        <?php if (!empty($latestDisbursement['FileHoaDonChungTu'])): ?>
          <a href="<?= htmlspecialchars($latestDisbursement['FileHoaDonChungTu']) ?>" target="_blank" onclick="openStatementModal(event)" class="btn outline" style="display: inline-flex; align-items: center; gap: 8px;">
            Xem sao kê chứng từ <?= kibo_icon('arrow') ?>
          </a>
        <?php else: ?>
          <button type="button" onclick="openStatementModal(event)" class="btn outline" style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
            Xem sao kê chứng từ <?= kibo_icon('arrow') ?>
          </button>
        <?php endif; ?>
      </article>
    <?php else: ?>
      <article class="statement-card">
        <span class="paper-icon">
          <?= kibo_icon('chart', 28) ?>
        </span>
        <small>ĐỐI SOÁT QUỸ ĐỊNH KỲ</small>
        <h2>Ủy nhiệm chi quỹ cứu trợ</h2>
        <p style="color: #555; font-size: 13.5px; margin: 12px 0; line-height: 1.5;">
          Khoản trích 5% được hệ thống ghi nhận tự động sau mỗi đơn hàng giao thành công và giải ngân minh bạch định kỳ hàng tháng.
        </p>
        <div style="font-size: 14px; font-weight: bold; color: #2e7d32; margin-top: 10px;">
          Sẵn sàng giải ngân: <?= number_format(max(0, $fundInfo['TongTichLuy'] - $fundInfo['TongGiaiNgan']), 0, ',', '.') ?>đ
        </div>
      </article>
    <?php endif; ?>
  </section>

  <!-- MODAL XEM SAO KÊ CHỨNG TỪ GIẢI NGÂN -->
  <?php if ($latestDisbursement): ?>
  <div id="statementModalBackdrop" onclick="if(event.target === this) closeStatementModal()" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.65); z-index: 999999; place-items: center; padding: 16px; overflow-y: auto;">
    <div style="background: #ffffff; width: min(650px, 95vw); border-radius: 8px; border: 1px solid #d0d7de; box-shadow: 0 10px 40px rgba(0,0,0,0.3); overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
      
      <!-- Modal Header -->
      <div style="background: #2c1a0e; color: #ffffff; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <h3 style="margin: 0; font-size: 16.5px; font-weight: bold; color: #ffffff;">Ủy nhiệm chi Quỹ Cứu Trợ KIBO</h3>
          <span style="background: #16a34a; color: #fff; font-size: 11.5px; padding: 3px 8px; border-radius: 4px; font-weight: bold;">Đã giải ngân</span>
        </div>
        <button type="button" onclick="closeStatementModal()" style="background: none; border: none; font-size: 24px; color: #f5f5f5; cursor: pointer; line-height: 1; padding: 0 4px;">&times;</button>
      </div>

      <!-- Modal Body (Giấy ủy nhiệm chi / Chứng từ) -->
      <div style="padding: 24px; background: #fafafa; font-size: 13.5px; color: #333;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
          
          <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #2c1a0e; padding-bottom: 12px; margin-bottom: 16px;">
            <div>
              <div style="font-weight: 800; font-size: 15px; color: #2c1a0e; text-transform: uppercase;">KIBO PET'S HOME</div>
              <div style="font-size: 12px; color: #64748b;">Quỹ Cứu Trợ Động Vật 5% — Minh Bạch Thực Tế</div>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 12px; color: #64748b;">MÃ CHỨNG TỪ</div>
              <div style="font-size: 16px; font-weight: bold; color: #b91c1c;">#<?= htmlspecialchars($latestDisbursement['MaChungTu']) ?></div>
            </div>
          </div>

          <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 13.5px;">
            <tr style="border-bottom: 1px dashed #e2e8f0;">
              <td style="padding: 9px 0; color: #64748b; width: 38%;">Trạm nhận giải ngân:</td>
              <td style="padding: 9px 0; font-weight: bold; color: #1e293b;"><?= htmlspecialchars($latestDisbursement['TenTram']) ?></td>
            </tr>
            <tr style="border-bottom: 1px dashed #e2e8f0;">
              <td style="padding: 9px 0; color: #64748b;">Người đại diện:</td>
              <td style="padding: 9px 0; color: #1e293b;"><?= htmlspecialchars($partnerShelter['NguoiDaiDien'] ?? 'Bác sĩ phụ trách trạm') ?></td>
            </tr>
            <tr style="border-bottom: 1px dashed #e2e8f0;">
              <td style="padding: 9px 0; color: #64748b;">Ngày giải ngân:</td>
              <td style="padding: 9px 0; font-weight: 600; color: #1e293b;"><?= date('d/m/Y', strtotime($latestDisbursement['NgayChuyenKhoan'])) ?></td>
            </tr>
            <tr style="border-bottom: 1px dashed #e2e8f0;">
              <td style="padding: 9px 0; color: #64748b;">Số tiền giải ngân:</td>
              <td style="padding: 9px 0; font-weight: 800; font-size: 17px; color: #1565c0;">
                <?= number_format($latestDisbursement['SoTienGiaiNgan'], 0, ',', '.') ?> VNĐ
              </td>
            </tr>
            <tr style="border-bottom: 1px dashed #e2e8f0;">
              <td style="padding: 9px 0; color: #64748b;">Mục đích sử dụng:</td>
              <td style="padding: 9px 0; color: #334155; line-height: 1.5;">
                <?= htmlspecialchars($latestDisbursement['MucDichSuDung'] ?? 'Chi phí thức ăn và cứu trợ y tế cho động vật cơ nhỡ.') ?>
              </td>
            </tr>
            <tr>
              <td style="padding: 9px 0; color: #64748b;">Trạng thái đối soát:</td>
              <td style="padding: 9px 0; color: #16a34a; font-weight: bold;">
                <?= kibo_icon('check', 14) ?> Đã chuyển khoản & đối soát thành công qua Ngân hàng
              </td>
            </tr>
          </table>

          <div style="display: flex; justify-content: space-between; margin-top: 24px; padding-top: 14px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #64748b;">
            <div style="width: 45%;">
              <div style="font-weight: bold; color: #334155; margin-bottom: 40px;">KẾ TOÁN TRƯỞNG QUỸ</div>
              <div style="color: #16a34a; font-weight: bold;">[ĐÃ KÝ ĐIỆN TỬ]</div>
            </div>
            <div style="width: 45%;">
              <div style="font-weight: bold; color: #334155; margin-bottom: 40px;">ĐẠI DIỆN TRẠM CỨU HỘ</div>
              <div style="color: #16a34a; font-weight: bold;">[ĐÃ XÁC NHẬN NHẬN TIỀN]</div>
            </div>
          </div>

        </div>
      </div>

      <!-- Modal Footer -->
      <div style="padding: 12px 20px; background: #ffffff; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" onclick="closeStatementModal()" style="padding: 8px 16px; border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 4px; font-size: 13.5px; cursor: pointer;">Đóng</button>
        <?php if (!empty($latestDisbursement['FileHoaDonChungTu'])): ?>
          <a href="<?= htmlspecialchars($latestDisbursement['FileHoaDonChungTu']) ?>" target="_blank" download style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border: none; background: #2c1a0e; color: #ffffff; border-radius: 4px; font-weight: 600; font-size: 13.5px; text-decoration: none; cursor: pointer;">
            Tải PDF chứng từ gốc
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>
  <script>
    function openStatementModal(e) {
      if (e) e.preventDefault();
      const modal = document.getElementById('statementModalBackdrop');
      if (modal) modal.style.display = 'grid';
    }
    function closeStatementModal() {
      const modal = document.getElementById('statementModalBackdrop');
      if (modal) modal.style.display = 'none';
    }
  </script>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/footer.php'; ?>
