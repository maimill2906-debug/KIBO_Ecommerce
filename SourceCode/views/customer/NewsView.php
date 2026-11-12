<?php
/**
 * VIEW: views/customer/NewsView.php
 * Giao diện Tin Tức KIBO - 100% Khớp Mã Nguồn NewsPage.tsx & Mẫu Giao Diện Pet's Home
 */
$pageTitle = 'Tin Tức & Cẩm Nang - KIBO PET\'S HOME';
$activeMenu = 'news';
require __DIR__ . '/header.php';

$articles = [
    [
        'slug' => 'vi-sao-meo-thich-khong-gian-nho',
        'type' => 'experience',
        'title' => 'Vì Sao Mèo Thích Không Gian Nhỏ? Giải Mã Tâm Lý Thú Cưng',
        'date' => '20/05/2026',
        'category' => 'KINH NGHIỆM NUÔI PET',
        'image' => 'https://images.unsplash.com/photo-1762726244524-eec0323a74d5?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Bạn thắc mắc vì sao mèo luôn chui rúc vào hộp giấy hay góc tủ? Khám phá nguyên nhân từ bản năng, cảm giác an toàn và cách chuẩn bị không gian nghỉ phù hợp cho mèo trong nhà.'
    ],
    [
        'slug' => 'tieu-chi-chon-hat-dinh-duong',
        'type' => 'nutrition',
        'title' => 'Top 5 Tiêu Chí Chọn Hạt Dinh Dưỡng An Toàn Cho Chó Mèo',
        'date' => '18/05/2026',
        'category' => 'CẨM NANG DINH DƯỠNG',
        'image' => 'https://images.unsplash.com/photo-1623387641168-d9803ddd3f35?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Thành phần đạm, độ tuổi, tình trạng sức khỏe và nguồn gốc sản phẩm là những yếu tố quan trọng khi chọn hạt. KIBO tổng hợp 5 tiêu chí dễ kiểm tra trước khi mua.'
    ],
    [
        'slug' => 'ban-giao-quy-cuu-tro-nong-lam',
        'type' => 'fund',
        'title' => 'Hành Trình Bàn Giao Quỹ Cứu Trợ Đến Trạm Cứu Hộ Đối Tác',
        'date' => '12/05/2026',
        'category' => 'HOẠT ĐỘNG QUỸ CỨU TRỢ',
        'image' => 'https://images.unsplash.com/photo-1563460716037-460a3ad24ba9?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Khoản đóng góp 5% từ các đơn hàng thành công đã được KIBO đối soát minh bạch và giải ngân trực tiếp cho trạm cứu hộ bảo trợ. Xem chi tiết chứng từ ủy nhiệm chi và kế hoạch sử dụng quỹ.'
    ],
    [
        'slug' => 'cham-soc-long-muot-cho-meo',
        'type' => 'care',
        'title' => 'Hướng Dẫn Chăm Sóc Lông Mượt Và Ngừa Búi Lông Cho Mèo',
        'date' => '08/05/2026',
        'category' => 'CHĂM SÓC THÚ CƯNG',
        'image' => 'https://images.unsplash.com/photo-1558993457-4bc6ec2c3734?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Chải lông đúng lịch, bổ sung nước và chọn khẩu phần giàu chất xơ giúp giảm lông rụng và hạn chế búi lông. Tham khảo quy trình chăm sóc đơn giản có thể thực hiện tại nhà.'
    ]
];

$selectedCat = $_GET['cat'] ?? '';
$filteredArticles = $selectedCat ? array_filter($articles, fn($a) => $a['type'] === $selectedCat) : $articles;
?>

<main class="news-page">
  <div class="container news-breadcrumb">
    <a href="index.php?action=home">Trang chủ</a>
    <?= kibo_icon('arrow', 12) ?>
    <strong>Tin tức</strong>
  </div>

  <div class="container news-layout">
    <!-- SIDEBAR BÊN TRÁI -->
    <aside class="news-sidebar">
      <section class="sidebar-section">
        <h2>Danh mục</h2>
        <div class="news-categories">
          <a href="index.php?action=home">Trang chủ</a>
          <a href="index.php?action=products">Khuyến mãi <?= kibo_icon('arrow', 12) ?></a>
          <a href="index.php?action=about">Dịch vụ chăm sóc <?= kibo_icon('arrow', 12) ?></a>
          <a href="index.php?action=news" class="expanded">Tin tức <?= kibo_icon('arrow', 12) ?></a>
          <div class="news-subcategories">
            <a href="index.php?action=news&cat=experience" class="<?= $selectedCat === 'experience' ? 'selected' : '' ?>">
              Kinh nghiệm nuôi thú cưng
            </a>
            <a href="index.php?action=news&cat=nutrition" class="<?= $selectedCat === 'nutrition' ? 'selected' : '' ?>">
              Cẩm nang dinh dưỡng
            </a>
            <a href="index.php?action=news&cat=fund" class="<?= $selectedCat === 'fund' ? 'selected' : '' ?>">
              Hoạt động Quỹ cứu trợ
            </a>
            <a href="mailto:support@kibo.vn">Liên hệ</a>
          </div>
        </div>
      </section>

      <section class="sidebar-section related-section">
        <h2>Bài viết liên quan</h2>
        <div class="related-list">
          <?php foreach ($articles as $a): ?>
            <article>
              <img src="<?= $a['image'] ?>" alt="<?= htmlspecialchars($a['title']) ?>">
              <div>
                <a href="index.php?action=news_detail&slug=<?= $a['slug'] ?>"><?= htmlspecialchars($a['title']) ?></a>
                <small><?= $a['date'] ?></small>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </aside>

    <!-- NỘI DUNG BÀI VIẾT BÊN PHẢI -->
    <section class="news-feed">
      <?php foreach ($filteredArticles as $article): ?>
        <article class="news-card">
          <a href="index.php?action=news_detail&slug=<?= $article['slug'] ?>" class="news-image">
            <img src="<?= $article['image'] ?>" alt="<?= htmlspecialchars($article['title']) ?>">
            <span><?= $article['category'] ?></span>
          </a>
          <div class="news-card-copy">
            <h2>
              <a href="index.php?action=news_detail&slug=<?= $article['slug'] ?>">
                <?= htmlspecialchars($article['title']) ?>
              </a>
            </h2>
            <p class="news-meta">
              <?= kibo_icon('clock', 13) ?> <?= $article['date'] ?> - Ban Biên Tập KIBO
            </p>
            <p class="news-excerpt">
              <?= htmlspecialchars($article['excerpt']) ?>
            </p>
            <a href="index.php?action=news_detail&slug=<?= $article['slug'] ?>" class="read-more">
              Xem chi tiết <?= kibo_icon('arrow', 12) ?>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
