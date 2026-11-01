<?php
/**
 * VIEW: views/customer/ProductDetailView.php
 * Chi tiết sản phẩm KIBO — Thiết kế chuẩn e-commerce Pet's Home kết hợp dữ liệu KIBO
 */
require_once __DIR__ . '/../components/helpers.php';
require_once __DIR__ . '/../../models/SanPhamModel.php';

$allProducts = [
    'SP001' => [
        'id' => 'SP001',
        'name' => 'KIBO Cá Hồi & Gạo Lứt',
        'brand' => 'KIBO Original',
        'category' => 'Thức Ăn Hạt',
        'price' => 285000,
        'oldPrice' => 320000,
        'art' => 'royal',
        'color' => '#c94336',
        'status' => 'Còn hàng',
        'specs' => 'Túi 1.5kg',
        'short_desc' => 'Công thức cá hồi Na Uy tươi kết hợp gạo lứt giàu chất xơ, hỗ trợ tim mạch, sáng mắt và mượt lông cho mèo trưởng thành.',
        'full_desc' => "Công thức dinh dưỡng độc quyền từ cá hồi đại dương:\n\n• Giàu Omega 3 & 6: Nuôi dưỡng nang lông bóng mượt, giảm rụng lông rõ rệt.\n• Gạo lứt nguyên cám: Bổ sung chất xơ hòa tan giúp tiêu hóa êm dịu, ngừa búi lông.\n• Trích 5% doanh thu: Đồng hành trực tiếp cùng trạm cứu hộ động vật bảo trợ thú cưng."
    ],
    'rc-poodle' => [
        'id' => 'rc-poodle',
        'name' => 'Pate Royal Canin cho Poodle 85g',
        'brand' => 'Royal Canin',
        'category' => 'Thức ăn cho chó',
        'price' => 45000,
        'oldPrice' => 50000,
        'art' => 'royal',
        'color' => '#bd5c44',
        'status' => 'Còn hàng',
        'specs' => 'Gói 85g',
        'short_desc' => 'Pate tươi dinh dưỡng chuyên biệt cho các bé Poodle từ 10 tháng tuổi. Giúp giữ lọn lông xoăn bồng bềnh, mượt mà và thơm ngon kích thích các bé biếng ăn nhất.',
        'full_desc' => "Nuôi Poodle thì mê nhất là bộ lông xoăn xù, nhưng các bé cũng rất dễ bị kén ăn và nhạy cảm về tiêu hóa.\n\n• Giữ dáng lông xoăn mượt: Giàu Omega 3 & 6 chiết xuất dầu cá, giảm gãy rụng và nuôi dưỡng nang lông mềm mượt.\n• Vị thơm ngậy tự nhiên: Nước sốt đậm đà, miếng thịt mềm vừa vặn khuôn miệng, bé ngửi là muốn ăn ngay.\n• Êm bụng, dễ tiêu: Protein tinh chọn hấp thu nhanh, phân khuôn đẹp, giảm hẳn mùi hôi khó chịu."
    ],
    'rc-mini-adult' => [
        'id' => 'rc-mini-adult',
        'name' => 'Royal Canin Mini Adult cho chó trưởng thành',
        'brand' => 'Royal Canin',
        'category' => 'Thức ăn cho chó',
        'price' => 285000,
        'oldPrice' => 320000,
        'art' => 'royal',
        'color' => '#c94336',
        'status' => 'Còn hàng',
        'specs' => 'Túi 1.5kg',
        'short_desc' => 'Hạt dinh dưỡng cao cấp cho các dòng chó nhỏ (dưới 10kg) như Poodle, Phốc Sóc, Corgi, Pug. Hạt nhỏ giòn tan, bảo vệ răng miệng và giữ vóc dáng thon gọn.',
        'full_desc' => "Các dòng cún nhỏ thường rất năng động nhưng dạ dày lại khá nhạy cảm. Túi hạt Royal Canin Mini Adult giúp ba mẹ yên tâm chăm sóc bé mỗi ngày:\n\n• Hạt thiết kế vừa khuôn hàm: Dễ nhai, giòn rụm giúp làm sạch mảng bám trên răng.\n• Duy trì cân nặng chuẩn: Công thức cân đối năng lượng và L-Carnitine giúp cún cưng săn chắc, không lo béo phì.\n• Lông da bóng mượt: Bổ sung axit béo thiết yếu, giảm ngứa da và rụng lông khi giao mùa."
    ],
    'rc-indoor-cat' => [
        'id' => 'rc-indoor-cat',
        'name' => 'Royal Canin Indoor 27 cho mèo nuôi trong nhà',
        'brand' => 'Royal Canin',
        'category' => 'Thức ăn cho mèo',
        'price' => 465000,
        'oldPrice' => 510000,
        'art' => 'royal',
        'color' => '#d9a14b',
        'status' => 'Còn hàng',
        'specs' => 'Túi 2kg',
        'short_desc' => 'Hạt chuyên dụng cho mèo nuôi trong nhà hoặc chung cư ít chạy nhảy. Hỗ trợ tiêu hóa búi lông tự nhiên và giảm đến 60% mùi hôi khay vệ sinh.',
        'full_desc' => "Mèo nuôi trong phòng máy lạnh hay căn hộ thường ngủ nhiều, ít vận động và liếm lông liên tục, dễ bị búi lông trong dạ dày.\n\n• Giảm mùi khay cát rõ rệt: Protein L.I.P siêu dễ tiêu giúp phân khô ráo, giảm mùi hôi nồng nặc trong phòng kín.\n• Tống khứ búi lông êm ái: Lượng chất xơ hòa tan tự nhiên giúp tống xuất lông theo đường phân mà không làm mèo khó chịu.\n• Kiểm soát calo: Tránh tình trạng mèo cưng thừa cân, ù lì sau khi triệt sản."
    ],
    'rc-kitten' => [
        'id' => 'rc-kitten',
        'name' => 'Royal Canin Kitten hỗ trợ mèo con phát triển',
        'brand' => 'Royal Canin',
        'category' => 'Thức ăn cho mèo',
        'price' => 495000,
        'oldPrice' => 550000,
        'art' => 'royal',
        'color' => '#e8a85f',
        'status' => 'Còn hàng',
        'specs' => 'Túi 2kg',
        'short_desc' => 'Hạt dinh dưỡng khởi đầu trọn vẹn cho mèo con từ 4 đến 12 tháng tuổi. Hạt mềm dễ nhai, tăng sức đề kháng và giúp khung xương phát triển cứng cáp.',
        'full_desc' => "Giai đoạn dưới 1 năm tuổi là lúc bé mèo lớn nhanh nhất nhưng hệ miễn dịch non nớt cần được bảo bọc kỹ càng.\n\n• Đề kháng vững vàng: Phức hợp Vitamin E, C và Taurine tạo lá chắn tự nhiên giúp bé luôn lanh lợi, khỏe mạnh.\n• Bụng khỏe, hấp thu tốt: Men vi sinh prebiotic cân bằng hệ khuẩn đường ruột, không lo tiêu chảy khi đổi thức ăn.\n• Xương và cơ chắc khỏe: Hàm lượng canxi và đạm chuẩn mực giúp bé mèo phát triển vóc dáng cân đối."
    ],
    'ganador-adult' => [
        'id' => 'ganador-adult',
        'name' => 'Hạt Ganador Adult vị gà nướng cho chó trưởng thành',
        'brand' => 'Ganador',
        'category' => 'Thức ăn cho chó',
        'price' => 540000,
        'oldPrice' => 568000,
        'art' => 'ganador',
        'color' => '#e2b442',
        'status' => 'Còn hàng',
        'specs' => 'Bao 3kg',
        'short_desc' => 'Hạt vị gà nướng thơm lừng cực kỳ hút mũi, kích thích chó ăn khỏe và tràn đầy năng lượng chạy nhảy suốt cả ngày.',
        'full_desc' => "Bữa ăn giàu đạm chuẩn vị thịt nướng mà chú cún nào cũng mê tít ngay từ lần ngửi đầu tiên:\n\n• Hương vị gà nướng đậm đà: Kích thích khứu giác, cún cưng ăn sạch bát không cần dỗ dành.\n• Đầy đủ 2 lá bùa sức khỏe: Omega 3 & 6 từ dầu cá giúp da khỏe lông mượt, canxi cho xương khớp dẻo dai.\n• Năng lượng dồi dào: Đạt chuẩn quốc tế AAFCO, phù hợp cho mọi giống chó trưởng thành hoạt động nhiều."
    ],
    'nekko-combo' => [
        'id' => 'nekko-combo',
        'name' => 'Combo 12 lon Pate Nekko cá ngừ cho mèo 70g',
        'brand' => 'Nekko',
        'category' => 'Thức ăn cho mèo',
        'price' => 228000,
        'oldPrice' => 240000,
        'art' => 'nekko',
        'color' => '#dd7556',
        'status' => 'Còn hàng',
        'specs' => 'Combo 12 lon',
        'short_desc' => 'Thức ăn ướt thịt cá ngừ tươi nguyên thớ số 1 từ Thái Lan. Bổ sung thạch jelly thanh mát giúp mèo bù nước, thanh lọc cơ thể và bảo vệ thận.',
        'full_desc' => "Mèo rất lười uống nước nên ba mẹ cần bổ sung pate mỗi ngày để phòng tránh sỏi thận và bệnh tiết niệu:\n\n• Thịt cá ngừ thật 100%: Nguyên thớ thịt đỏ tươi ngon, thơm tự nhiên, không phẩm màu và không chất bảo quản.\n• Thạch mềm mướt bổ sung nước: Cung cấp lượng nước dồi dào cho cơ thể, mát ruột và hỗ trợ thận khỏe mạnh.\n• Tinh chất Vitamin E & Taurine: Cho đôi mắt mèo luôn sáng tinh anh và trái tim khỏe mạnh."
    ],
    'cature-pellet' => [
        'id' => 'cature-pellet',
        'name' => 'Cát vệ sinh cho mèo Cature Smart Pellet 7L',
        'brand' => 'Cature',
        'category' => 'Cát vệ sinh mèo',
        'price' => 185000,
        'oldPrice' => 210000,
        'art' => 'cature',
        'color' => '#75978a',
        'status' => 'Còn hàng',
        'specs' => 'Túi 7L',
        'short_desc' => 'Cát gỗ tự nhiên vón cục nhanh, khóa mùi hôi suốt 7 ngày. Hạt tròn không bết dính chân mèo và có thể tiêu hủy trực tiếp qua bồn cầu.',
        'full_desc' => "Giải pháp giữ không gian nhà luôn thơm mát mùi gỗ thông tự nhiên dù nuôi nhiều bé mèo:\n\n• 100% sợi gỗ thông tự nhiên: Thân thiện với môi trường, hạt tròn êm ái không làm đau đệm chân mèo.\n• Vón nhanh, không bụi: Không bụi 99.5%, bảo vệ hệ hô hấp của mèo và cả gia đình, nhà luôn sạch sẽ.\n• Xả thẳng bồn cầu tiện lợi: Dễ dàng hòa tan trong nước, tiết kiệm thời gian dọn dẹp mỗi ngày."
    ]
];

$productId = $_GET['id'] ?? 'SP001';
$modelProd = SanPhamModel::getProductById($productId);

if ($modelProd) {
    $product = [
        'id' => $modelProd['id'],
        'name' => $modelProd['name'],
        'brand' => $modelProd['brand'] ?: 'KIBO Original',
        'category' => $modelProd['category'] ?: 'Thức Ăn Hạt',
        'price' => (float)$modelProd['price'],
        'oldPrice' => (float)$modelProd['originalPrice'],
        'art' => 'royal',
        'color' => '#c94336',
        'status' => $modelProd['status'] ?: 'Còn hàng',
        'specs' => $modelProd['specs'] ?: 'Tiêu chuẩn',
        'short_desc' => $modelProd['desc'] ?: 'Dinh dưỡng thú cưng cao cấp KIBO.',
        'full_desc' => $modelProd['desc'] ?: 'Sản phẩm chính hãng với tiêu chuẩn chất lượng cao, đồng hành cùng quỹ cứu trợ 5%.'
    ];
} else {
    $product = $allProducts[$productId] ?? $allProducts['SP001'];
}

$pageTitle = $product['name'] . ' — KIBO PET\'S HOME';
$activeMenu = 'products';
require __DIR__ . '/header.php';

$rescueContribution = number_format(round($product['price'] * 0.05), 0, ',', '.');
$discountPercent = ($product['oldPrice'] > $product['price']) ? round((1 - $product['price'] / $product['oldPrice']) * 100) : 0;
?>

<main class="page pdp-page">
  <div class="container breadcrumb">
    <a href="index.php?action=home">Trang chủ</a>
    <?= kibo_icon('arrow', 13) ?>
    <a href="index.php?action=products"><?= htmlspecialchars($product['category']) ?></a>
    <?= kibo_icon('arrow', 13) ?>
    <strong><?= htmlspecialchars($product['name']) ?></strong>
  </div>

  <section class="container pdp">
    <!-- CỘT 1: GALLERY ẢNH SẢN PHẨM -->
    <div class="gallery">
      <div class="main-product" id="mainProductPreview">
        <?php if ($discountPercent > 0): ?>
          <span class="discount">-<?= $discountPercent ?>%</span>
        <?php endif; ?>
        <div id="mainArtContainer">
          <?= kibo_product_art($product['art'], $product['color']) ?>
        </div>
        <div class="brand-watermark">KIBO Pet's Home</div>
      </div>
      <div class="thumbs">
        <?php foreach ([$product['color'], '#b6352a', '#75978a', '#dd7556'] as $i => $c): ?>
          <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" onclick="switchThumbColor('<?= $c ?>', this)">
            <div class="product-art" style="--pack: <?= $c ?>;">
              <div class="pack <?= $product['art'] ?>"></div>
            </div>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- CỘT 2: THÔNG TIN CHI TIẾT SẢN PHẨM (HỌC HỎI TỪ PET'S HOME) -->
    <div class="pdp-info">
      <h1><?= htmlspecialchars($product['name']) ?></h1>
      <div class="rating pdp-rating">★★★★★ <span>5.0, 32 Reviews</span></div>

      <div class="pdp-price-row">
        <strong class="pdp-price-main"><?= number_format($product['price'], 0, ',', '.') ?>₫</strong>
        <?php if ($product['oldPrice'] > $product['price']): ?>
          <del class="pdp-price-old"><?= number_format($product['oldPrice'], 0, ',', '.') ?>₫</del>
        <?php endif; ?>
      </div>
      <div class="fund-contrib-line pdp-fund-line">Góp vào quỹ: <strong><?= $rescueContribution ?>₫</strong></div>

      <div class="pdp-attributes">
        <p><span>Thương hiệu:</span> <strong><?= htmlspecialchars($product['brand']) ?></strong></p>
        <p><span>Tình trạng:</span> <strong class="in-stock"><?= htmlspecialchars($product['status'] ?? 'Còn hàng') ?></strong></p>
      </div>

      <div class="pdp-short-desc">
        <p><?= htmlspecialchars($product['short_desc']) ?></p>
      </div>

      <div class="pdp-quantity-row">
        <label>Số lượng</label>
        <div class="stepper">
          <button type="button" onclick="changePdpQty(-1)">−</button>
          <input type="text" id="pdpQty" value="1" readonly>
          <button type="button" onclick="changePdpQty(1)">+</button>
        </div>
      </div>

      <button type="button" class="btn btn-buy-now" id="btnPdpAddToCart" onclick="handlePdpBuy()">
        <?= kibo_icon('cart', 20) ?> THÊM VÀO GIỎ HÀNG
      </button>

      <div class="contribution">
        <span><?= kibo_icon('heart', 20) ?></span>
        <p>
          <strong>Bạn góp thêm <?= $rescueContribution ?>đ vào Quỹ cứu trợ KIBO</strong>
          <small>Trích tự động 5% mỗi đơn hàng thành công cho các trạm cứu hộ động vật.</small>
        </p>
      </div>
    </div>

    <!-- CỘT 3: HỘP CAM KẾT & DỊCH VỤ (ĐỒNG NHẤT HỆ THỐNG KIBO) -->
    <aside class="pdp-trust-box">
      <div class="trust-item">
        <span class="trust-icon"><?= kibo_icon('truck', 20) ?></span>
        <div>
          <strong>Giao nhanh 24H</strong>
          <small>Nội thành TP.HCM</small>
        </div>
      </div>

      <div class="trust-item">
        <span class="trust-icon"><?= kibo_icon('shield', 20) ?></span>
        <div>
          <strong>100% chính hãng</strong>
          <small>Nguồn gốc minh bạch</small>
        </div>
      </div>

      <div class="trust-item">
        <span class="trust-icon"><?= kibo_icon('box', 20) ?></span>
        <div>
          <strong>Đổi trả 3 ngày</strong>
          <small>Nhanh chóng, dễ dàng</small>
        </div>
      </div>

      <div class="trust-item">
        <span class="trust-icon"><?= kibo_icon('heart', 20) ?></span>
        <div>
          <strong>Chung tay cứu trợ</strong>
          <small>5% mỗi đơn hàng</small>
        </div>
      </div>

      <!-- Thanh danh mục nhỏ bên dưới -->
      <div class="pdp-side-category">
        <h3>DANH MỤC</h3>
      </div>
    </aside>
  </section>

  <!-- TAB MÔ TẢ CHI TIẾT (CHUẨN PET'S HOME) -->
  <section class="container pdp-description-section">
    <div class="pdp-tabs">
      <button type="button" class="tab-btn active">MÔ TẢ</button>
    </div>
    <div class="pdp-tab-content">
      <h2><?= htmlspecialchars($product['name']) ?></h2>
      <div class="pdp-full-text">
        <p><?= nl2br(htmlspecialchars($product['full_desc'])) ?></p>
      </div>
    </div>
  </section>
</main>

<script>
let pdpQuantity = 1;

function changePdpQty(delta) {
  pdpQuantity = Math.max(1, pdpQuantity + delta);
  document.getElementById('pdpQty').value = pdpQuantity;
}

function switchThumbColor(color, btn) {
  document.querySelectorAll('.thumbs button').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const pack = document.querySelector('#mainArtContainer .pack');
  if (pack) {
    document.querySelector('#mainArtContainer .product-art').style.setProperty('--pack', color);
  }
}

function handlePdpBuy() {
  const qtyInput = document.getElementById('pdpQty');
  const qty = parseInt(qtyInput ? qtyInput.value : pdpQuantity) || 1;
  const btn = document.getElementById('btnPdpAddToCart');
  if (btn) {
    const origHtml = btn.innerHTML;
    btn.innerHTML = '✔ ĐÃ THÊM ' + qty + ' VÀO GIỎ!';
    btn.style.background = '#2e7d32';
    btn.style.color = '#ffffff';
    setTimeout(() => {
      btn.innerHTML = origHtml;
      btn.style.background = '';
      btn.style.color = '';
    }, 1800);
  }

  addToCart({
    id: '<?= $product['id'] ?>',
    name: '<?= addslashes($product['name']) ?>',
    price: <?= (float)$product['price'] ?>,
    variant: '<?= addslashes($product['specs']) ?>',
    quantity: qty,
    art: '<?= $product['art'] ?? 'royal' ?>',
    color: '<?= $product['color'] ?? '#c94336' ?>'
  });
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
