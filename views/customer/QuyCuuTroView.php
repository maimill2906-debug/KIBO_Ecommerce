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
          <a href="<?= htmlspecialchars($latestDisbursement['FileHoaDonChungTu']) ?>" target="_blank" class="btn outline">
            Xem sao kê chứng từ <?= kibo_icon('arrow') ?>
          </a>
        <?php else: ?>
          <span style="font-size: 12.5px; color: #666; font-style: italic;">Chứng từ đã được kế toán đối soát trên hệ thống</span>
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
</main>

<?php require __DIR__ . '/footer.php'; ?>
