<?php
/**
 * VIEW: views/customer/NewsDetailView.php
 * Giao diện Chi tiết Tin tức KIBO — 100% Khớp Mã Nguồn NewsDetailPage.tsx từ Figma Export
 */
require_once __DIR__ . '/../components/helpers.php';

$articles = [
    [
        'slug' => 'vi-sao-meo-thich-khong-gian-nho',
        'type' => 'experience',
        'title' => 'Vì Sao Mèo Thích Không Gian Nhỏ? Giải Mã Tâm Lý Thú Cưng',
        'date' => '20/05/2026',
        'category' => 'KINH NGHIỆM NUÔI PET',
        'image' => 'https://images.unsplash.com/photo-1762726244524-eec0323a74d5?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Bạn thắc mắc vì sao mèo luôn chui rúc vào hộp giấy hay góc tủ? Khám phá nguyên nhân từ bản năng, cảm giác an toàn và cách chuẩn bị không gian nghỉ phù hợp cho mèo trong nhà.',
    ],
    [
        'slug' => 'tieu-chi-chon-hat-dinh-duong',
        'type' => 'nutrition',
        'title' => 'Top 5 Tiêu Chí Chọn Hạt Dinh Dưỡng An Toàn Cho Chó Mèo',
        'date' => '18/05/2026',
        'category' => 'CẨM NANG DINH DƯỠNG',
        'image' => 'https://images.unsplash.com/photo-1623387641168-d9803ddd3f35?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Thành phần đạm, độ tuổi, tình trạng sức khỏe và nguồn gốc sản phẩm là những yếu tố quan trọng khi chọn hạt. KIBO tổng hợp 5 tiêu chí dễ kiểm tra trước khi mua.',
    ],
    [
        'slug' => 'ban-giao-quy-cuu-tro-nong-lam',
        'type' => 'fund',
        'title' => 'Hành Trình Bàn Giao Quỹ Cứu Trợ Đến Trạm Cứu Hộ Đối Tác',
        'date' => '12/05/2026',
        'category' => 'HOẠT ĐỘNG QUỸ CỨU TRỢ',
        'image' => 'https://images.unsplash.com/photo-1563460716037-460a3ad24ba9?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Khoản đóng góp 5% từ các đơn hàng thành công đã được KIBO đối soát minh bạch và giải ngân trực tiếp cho trạm cứu hộ bảo trợ. Xem chi tiết chứng từ ủy nhiệm chi và kế hoạch sử dụng quỹ.',
    ],
    [
        'slug' => 'cham-soc-long-muot-cho-meo',
        'type' => 'care',
        'title' => 'Hướng Dẫn Chăm Sóc Lông Mượt Và Ngừa Búi Lông Cho Mèo',
        'date' => '08/05/2026',
        'category' => 'CHĂM SÓC THÚ CƯNG',
        'image' => 'https://images.unsplash.com/photo-1558993457-4bc6ec2c3734?auto=format&fit=crop&w=900&q=85',
        'excerpt' => 'Chải lông đúng lịch, bổ sung nước và chọn khẩu phần giàu chất xơ giúp giảm lông rụng và hạn chế búi lông. Tham khảo quy trình chăm sóc đơn giản có thể thực hiện tại nhà.',
    ],
];

$slug = $_GET['slug'] ?? 'vi-sao-meo-thich-khong-gian-nho';
$article = null;
foreach ($articles as $item) {
    if ($item['slug'] === $slug) {
        $article = $item;
        break;
    }
}

if (!$article) {
    $pageTitle = 'Không tìm thấy bài viết — KIBO PET\'S HOME';
    $activeMenu = 'news';
    require __DIR__ . '/header.php';
    ?>
    <main class="page not-found">
      <h1>Không tìm thấy bài viết</h1>
      <a href="index.php?action=news" class="btn primary">Về trang tin tức</a>
    </main>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}

$pageTitle = $article['title'] . ' — KIBO PET\'S HOME';
$activeMenu = 'news';
require __DIR__ . '/header.php';
?>

<main class="news-detail-page">
  <div class="container news-breadcrumb">
    <a href="index.php?action=home">Trang chủ</a>
    <?= kibo_icon('arrow', 12) ?>
    <a href="index.php?action=news">Tin tức</a>
    <?= kibo_icon('arrow', 12) ?>
    <strong><?= htmlspecialchars($article['title']) ?></strong>
  </div>

  <article class="container news-detail">
    <span class="news-detail-category"><?= htmlspecialchars($article['category']) ?></span>
    <h1><?= htmlspecialchars($article['title']) ?></h1>
    <p class="news-detail-meta">
      <?= kibo_icon('clock', 14) ?> <?= htmlspecialchars($article['date']) ?> - Ban Biên Tập KIBO
    </p>

    <img class="news-detail-image" src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">

    <div class="news-detail-body">
      <p><strong><?= htmlspecialchars($article['excerpt']) ?></strong></p>
      <p>Việc quan sát thói quen hằng ngày giúp người nuôi hiểu rõ hơn nhu cầu của thú cưng. Mỗi bé có một thể trạng, độ tuổi và môi trường sống khác nhau, vì vậy cách chăm sóc cũng cần được điều chỉnh phù hợp.</p>
      <h2>Những điều người nuôi cần lưu ý</h2>
      <p>Hãy ưu tiên sản phẩm có nguồn gốc rõ ràng, duy trì lịch kiểm tra sức khỏe và theo dõi các thay đổi nhỏ trong ăn uống, vận động hoặc hành vi. Khi xuất hiện dấu hiệu bất thường kéo dài, nên trao đổi với bác sĩ thú y thay vì tự điều trị tại nhà.</p>
      <p>KIBO tổng hợp nội dung từ kinh nghiệm chăm sóc thực tế và tài liệu chuyên môn để giúp bạn có thêm thông tin tham khảo. Nội dung không thay thế cho chẩn đoán của bác sĩ thú y.</p>
    </div>

    <div class="news-detail-actions">
      <a href="index.php?action=news" class="btn outline">
        <?= kibo_icon('arrow', 13) ?> Quay lại tin tức
      </a>
      <a href="index.php?action=products" class="btn primary">
        Xem sản phẩm phù hợp
      </a>
    </div>
  </article>
</main>

<?php require __DIR__ . '/footer.php'; ?>
