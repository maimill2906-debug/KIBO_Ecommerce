<?php
/**
 * VIEW: views/customer/AboutView.php
 * Giao diện Về Chúng Tôi KIBO — 100% Khớp Mã Nguồn AboutPage.tsx
 */
$pageTitle = 'Về KIBO — PET\'S HOME | Hành Trình Vì Thú Cưng & Cộng Đồng';
$activeMenu = 'about';
require __DIR__ . '/header.php';

$aboutImage = 'https://images.unsplash.com/photo-1787722399319-2cad05551e19?auto=format&fit=crop&w=1200&q=85';
?>

<main class="page about-page">
  <!-- ABOUT HERO -->
  <section class="about-hero">
    <div class="container">
      <div class="about-breadcrumb">
        <a href="index.php?action=home">Trang chủ</a>
        <?= kibo_icon('arrow', 13) ?>
        <strong>Về chúng tôi</strong>
      </div>

      <div class="about-hero-grid">
        <div class="about-copy">
          <span class="eyebrow brown">KIBO | PET'S HOME</span>
          <h1>Chăm thú cưng tử tế, giúp thêm nhiều bé có một mái nhà.</h1>
          <p>KIBO bắt đầu từ một cửa hàng nhỏ dành cho những người xem thú cưng là thành viên trong gia đình. Chúng tôi lựa chọn sản phẩm rõ nguồn gốc, tư vấn đúng nhu cầu và dành 5% mỗi đơn thành công cho các trạm cứu hộ.</p>
          <div class="about-actions">
            <a href="index.php?action=products" class="btn primary">
              Xem sản phẩm <?= kibo_icon('arrow') ?>
            </a>
            <a href="index.php?action=rescue" class="btn outline">
              Xem quỹ cứu trợ
            </a>
          </div>
        </div>

        <div class="about-image">
          <img src="<?= $aboutImage ?>" alt="Chó và mèo đồng hành cùng KIBO">
          <span>Ảnh: Mạnh Ngô / Unsplash</span>
        </div>
      </div>
    </div>
  </section>

  <!-- VALUES -->
  <section class="container about-values">
    <div class="about-section-title">
      <span class="eyebrow brown">ĐIỀU KIBO TIN TƯỞNG</span>
      <h2>Mỗi lựa chọn nhỏ đều có thể tạo ra thay đổi tốt hơn.</h2>
    </div>

    <div class="value-grid">
      <article>
        <span><?= kibo_icon('shield') ?></span>
        <h3>Sản phẩm đáng tin</h3>
        <p>Ưu tiên hàng chính hãng, nhãn mác rõ ràng và dinh dưỡng phù hợp với từng độ tuổi.</p>
      </article>

      <article>
        <span><?= kibo_icon('heart') ?></span>
        <h3>Chia sẻ minh bạch</h3>
        <p>5% giá trị đơn thành công được ghi nhận, đối soát và công bố trong Quỹ cứu trợ KIBO.</p>
      </article>

      <article>
        <span><?= kibo_icon('user') ?></span>
        <h3>Tư vấn như người nhà</h3>
        <p>Không bán bằng mọi giá. Đội ngũ KIBO luôn hỏi kỹ thói quen và sức khỏe của từng bé.</p>
      </article>
    </div>
  </section>

  <!-- STORY -->
  <section class="about-story">
    <div class="container about-story-grid">
      <div>
        <span class="eyebrow">HÀNH TRÌNH CỦA CHÚNG TÔI</span>
        <h2>Từ một cửa hàng nhỏ đến cộng đồng cùng chăm và cùng sẻ chia.</h2>
      </div>

      <div class="story-list">
        <p>
          <strong>Khởi nguồn</strong>
          <span>Xây dựng mô hình thương mại điện tử thú cưng kết hợp trách nhiệm xã hội và quỹ hỗ trợ động vật cơ nhỡ.</span>
        </p>
        <p>
          <strong>Cơ chế 5%</strong>
          <span>Mỗi đơn hàng giao thành công đều tự động trích 5% doanh thu trực tiếp vào hệ thống quỹ cứu trợ.</span>
        </p>
        <p>
          <strong>Minh bạch</strong>
          <span>100% dòng tiền đóng góp và các đợt giải ngân tới trạm cứu hộ được công khai, có chứng từ rõ ràng.</span>
        </p>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="container about-cta">
    <div>
      <h2>Mua đúng cho bé, góp thêm cho trạm.</h2>
      <p>Mỗi đơn hàng tại KIBO đều mang theo một phần yêu thương.</p>
    </div>
    <a href="index.php?action=products" class="btn primary">
      Mua sắm cùng KIBO <?= kibo_icon('arrow') ?>
    </a>
  </section>
</main>

<?php require __DIR__ . '/footer.php'; ?>
