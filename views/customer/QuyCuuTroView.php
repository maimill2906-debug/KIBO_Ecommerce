<?php
/**
 * VIEW: views/customer/QuyCuuTroView.php
 * Giao diện Quỹ Cứu Trợ 5% KIBO — 100% Khớp Mã Nguồn RescueFundPage.tsx & FundPage in KiboUI.tsx
 */
$pageTitle = 'Quỹ Cứu Trợ 5% — KIBO PET\'S HOME | Minh Bạch & Lan Tỏa Yêu Thương';
$activeMenu = 'rescue';
require __DIR__ . '/header.php';

$shelterImage = 'https://images.unsplash.com/photo-1718885433034-908d5bca08cf?auto=format&fit=crop&w=900&q=85';
?>

<main class="page fund-page">
  <!-- FUND HERO -->
  <section class="fund-hero">
    <div class="container">
      <span class="eyebrow">MINH BẠCH TỪ NHỮNG ĐIỀU NHỎ NHẤT</span>
      <h1>Quỹ cứu trợ KIBO</h1>
      <p>5% giá trị mỗi đơn hàng thành công được gửi trực tiếp đến các trạm cứu hộ động vật.</p>
      
      <div class="fund-counter" aria-live="polite">
        <small>TỔNG QUỸ ĐÃ TÍCH LŨY</small>
        <strong id="liveFundCounter">2.340.000 <sup>đ</sup></strong>
        <span><i></i> Đang cập nhật theo thời gian thực</span>
      </div>
    </div>
  </section>

  <!-- TRANSPARENCY SECTION -->
  <section class="container transparency">
    <article class="shelter-card">
      <img src="<?= $shelterImage ?>" alt="Chó và mèo tại trạm cứu hộ">
      <div>
        <span class="eyebrow brown">ĐỐI TÁC THÁNG NÀY</span>
        <h2>Trạm Cứu Hộ Nông Lâm TP.HCM</h2>
        <p>Nơi đang chăm sóc hơn 60 chó mèo bị bỏ rơi, cần hỗ trợ thức ăn và chi phí điều trị mỗi tháng.</p>
        <span class="verified">
          <?= kibo_icon('check', 14) ?> Đã xác minh bởi KIBO
        </span>
      </div>
    </article>

    <article class="statement-card">
      <span class="paper-icon">
        <?= kibo_icon('chart', 28) ?>
      </span>
      <small>SAO KÊ THÁNG 06/2025</small>
      <h2>Ủy nhiệm chi quỹ cứu trợ</h2>
      <dl>
        <div>
          <dt>Mã giao dịch</dt>
          <dd>UNC-KIBO-0625</dd>
        </div>
        <div>
          <dt>Số tiền giải ngân</dt>
          <dd>2.180.000đ</dd>
        </div>
        <div>
          <dt>Ngày thực hiện</dt>
          <dd>30/06/2025</dd>
        </div>
      </dl>
      <a href="index.php?action=tracking" class="btn outline">
        Xem sao kê ủy nhiệm chi <?= kibo_icon('arrow') ?>
      </a>
    </article>
  </section>
</main>

<script>
// Tăng nhẹ số tiền quỹ sau mỗi 10 giây để mô phỏng thời gian thực như Figma
let currentFund = 2340000;
setInterval(() => {
  currentFund += 250;
  const counter = document.getElementById('liveFundCounter');
  if (counter) {
    counter.innerHTML = new Intl.NumberFormat('vi-VN').format(currentFund) + ' <sup>đ</sup>';
  }
}, 10000);
</script>

<?php require __DIR__ . '/footer.php'; ?>
