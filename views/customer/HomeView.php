<?php
/**
 * VIEW: views/customer/HomeView.php
 * Giao diện Trang Chủ KIBO — 100% Khớp Mã Nguồn Figma (Home in KiboUI.tsx)
 */
$pageTitle = 'KIBO — PET\'S HOME | Dinh Dưỡng Thú Cưng Cao Cấp & Quỹ Cứu Trợ 5%';
$activeMenu = 'home';
require __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/SanPhamModel.php';
require_once __DIR__ . '/../../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../../models/DonHangModel.php';

$heroImage = 'assets/images/hero-banner.jpg';
$petImage = 'https://images.unsplash.com/photo-1563460716037-460a3ad24ba9?auto=format&fit=crop&w=900&q=85';
$dogImage = 'https://images.unsplash.com/photo-1606098216818-40939b7c98ad?auto=format&fit=crop&w=800&q=85';
$shelterImage = 'https://images.unsplash.com/photo-1718885433034-908d5bca08cf?auto=format&fit=crop&w=900&q=85';

$fundSummary = QuyCuuTroModel::getFundSummary();
$dbFeatured = SanPhamModel::getFeaturedProducts(4);
$palette = [
    ['art' => 'royal', 'color' => '#c94336', 'species' => 'dog'],
    ['art' => 'royal', 'color' => '#d9a14b', 'species' => 'cat'],
    ['art' => 'cature', 'color' => '#75978a', 'species' => 'cat'],
    ['art' => 'royal', 'color' => '#b6352a', 'species' => 'dog'],
];

$featuredDeals = [];
$idx = 0;
foreach ($dbFeatured as $p) {
    $pal = $palette[$idx % count($palette)];
    $idx++;
    $price = (float)$p['price'];
    $oldPrice = (float)$p['originalPrice'];
    $featuredDeals[] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'category' => $p['category'] ?: 'Thú cưng',
        'species' => $pal['species'],
        'price' => $price,
        'priceDisplay' => number_format($price, 0, ',', '.') . '₫',
        'old' => number_format($oldPrice, 0, ',', '.') . '₫',
        'art' => $pal['art'],
        'color' => $pal['color']
    ];
}
?>

<main>
  <!-- ====================================================================
       1. HERO SECTION (Ảnh chuẩn Unsplash từ Figma)
       ==================================================================== -->
  <section class="hero">
    <img src="<?= $heroImage ?>" alt="Chó và mèo thân thiết tại KIBO">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <span class="eyebrow">KIBO | PET'S HOME</span>
      <h1>Một bữa ăn ngon.<br><em>Hai lần hạnh phúc.</em></h1>
      <p>Chăm sóc thú cưng của bạn và lan tỏa yêu thương đến những bé đang chờ một mái nhà.</p>
      <div class="hero-actions">
        <a href="index.php?action=products" class="primary">
          MUA SẮM NGAY <?= kibo_icon('arrow', 14) ?>
        </a>
        <a href="index.php?action=rescue" class="text-btn">
          Tìm hiểu quỹ cứu trợ
        </a>
      </div>
      <div class="hero-badge">
        <span><?= kibo_icon('heart') ?></span>
        <p>
          <strong>Tự động trích 5%</strong>
          <small>Từ mỗi đơn thành công cho trạm cứu hộ</small>
        </p>
      </div>
    </div>
  </section>

  <!-- ====================================================================
       2. 4 TIÊU CHÍ BENEFITS
       ==================================================================== -->
  <section class="container benefits">
    <div>
      <span><?= kibo_icon('truck') ?></span>
      <p><strong>Giao nhanh 24H</strong><small>Nội thành TP.HCM</small></p>
    </div>
    <div>
      <span><?= kibo_icon('shield') ?></span>
      <p><strong>100% chính hãng</strong><small>Nguồn gốc minh bạch</small></p>
    </div>
    <div>
      <span><?= kibo_icon('box') ?></span>
      <p><strong>Đổi trả 3 ngày</strong><small>Nhanh chóng, dễ dàng</small></p>
    </div>
    <div>
      <span><?= kibo_icon('heart') ?></span>
      <p><strong>Chung tay cứu trợ</strong><small>5% mỗi đơn hàng</small></p>
    </div>
  </section>

  <!-- ====================================================================
       3. 3 BANNER DANH MỤC CATEGORY GRID
       ==================================================================== -->
  <section class="container category-grid">
    <article class="category dog-card">
      <img src="<?= $dogImage ?>" alt="Thức ăn cho chó">
      <div>
        <div class="cat-content">
          <small>DÀNH CHO BOSS NĂNG ĐỘNG</small>
          <h2>Thức ăn cho chó</h2>
          <p>Dinh dưỡng đủ đầy cho mỗi ngày vui.</p>
        </div>
        <a href="index.php?action=products&species=dog" class="cat-link">
          Khám phá ngay <?= kibo_icon('arrow', 12) ?>
        </a>
      </div>
    </article>

    <article class="category cat-card">
      <img src="<?= $petImage ?>" alt="Pate & cát vệ sinh">
      <div>
        <div class="cat-content">
          <small>CHĂM BÉ MÈO KHỎE NGOAN</small>
          <h2>Pate & cát vệ sinh</h2>
        </div>
        <a href="index.php?action=products&species=cat" class="cat-link">
          Khám phá ngay <?= kibo_icon('arrow', 12) ?>
        </a>
      </div>
    </article>

    <article class="category mission-card">
      <img src="<?= $shelterImage ?>" alt="Quỹ cứu trợ 5%">
      <div>
        <div class="cat-content">
          <small>MỖI ĐƠN HÀNG | MỘT HY VỌNG</small>
          <h2>Quỹ cứu trợ 5%</h2>
          <p>Minh bạch từng đóng góp, sẻ chia từng bữa ăn.</p>
        </div>
        <a href="index.php?action=rescue" class="cat-link">
          Xem hành trình <?= kibo_icon('arrow', 12) ?>
        </a>
      </div>
    </article>
  </section>

  <!-- ====================================================================
       4. DEAL NỔI BẬT (#deals)
       ==================================================================== -->
  <section class="deals" id="deals">
    <div class="container">
      <div class="section-head">
        <div>
          <span class="eyebrow brown">KIBO ƯU ĐÃI</span>
          <h2>Deal nổi bật</h2>
          <p>Giá tốt cho boss, thêm yêu thương cho các bé ở trạm.</p>
        </div>
        <div class="filter-pills">
          <button type="button" class="active" onclick="filterDeals('all', this)">Tất cả</button>
          <button type="button" onclick="filterDeals('dog', this)">Chó</button>
          <button type="button" onclick="filterDeals('cat', this)">Mèo</button>
        </div>
      </div>

      <div class="product-grid" id="dealsGrid">
        <?php foreach ($featuredDeals as $p): ?>
          <article class="product-card" data-species="<?= $p['species'] ?>" onclick="window.location.href='index.php?action=product_detail&id=<?= $p['id'] ?>'">
            <div class="product-img">
              <span class="discount">-5%</span>
              <button type="button" class="btn quick" onclick="event.stopPropagation(); window.location.href='index.php?action=product_detail&id=<?= $p['id'] ?>'">
                Xem nhanh
              </button>
              <?= kibo_product_art($p['art'], $p['color']) ?>
            </div>
            <div class="product-info">
              <small class="brand-label">KIBO SELECT</small>
              <h3><?= htmlspecialchars($p['name']) ?></h3>
              <div class="rating">★★★★★ <span>5.0, 24 Reviews</span></div>
              <div class="price">
                <strong><?= $p['priceDisplay'] ?></strong>
                <del><?= $p['old'] ?></del>
              </div>
              <div class="fund-contrib-line">Góp vào quỹ: <strong><?= number_format(DonHangModel::tinhQuy($p['price']), 0, ',', '.') ?>₫</strong></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <a href="index.php?action=products" class="btn outline centered">
        Xem tất cả sản phẩm <?= kibo_icon('arrow') ?>
      </a>
    </div>
  </section>

  <!-- ====================================================================
       5. IMPACT STRIP
       ==================================================================== -->
  <section class="container impact-strip" id="about">
    <div>
      <span class="eyebrow">KIBO IMPACT</span>
      <h2>Bạn mua một bữa ăn.<br>Chúng mình trao thêm một bữa ăn.</h2>
    </div>
    <div class="impact-numbers">
      <p>
        <strong><?= number_format($fundSummary['TongTichLuy'] ?? 0, 0, ',', '.') ?>đ</strong>
        <small>Quỹ đã tích lũy</small>
      </p>
      <i></i>
      <p>
        <strong><?= number_format($fundSummary['SoDonDongGop'] ?? 0, 0, ',', '.') ?></strong>
        <small>Đơn hàng sẻ chia</small>
      </p>
      <i></i>
      <p>
        <strong><?= str_pad((string)($fundSummary['SoTramDoiTac'] ?? 0), 2, '0', STR_PAD_LEFT) ?></strong>
        <small>Trạm đồng hành</small>
      </p>
    </div>
  </section>
</main>

<script>
function filterDeals(species, btn) {
  document.querySelectorAll('.filter-pills button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const cards = document.querySelectorAll('#dealsGrid .product-card');
  cards.forEach(card => {
    if (species === 'all' || card.getAttribute('data-species') === species) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
