<?php
/**
 * VIEW: views/customer/ProductDetailView.php
 * Chi tiết sản phẩm KIBO — Thiết kế chuẩn e-commerce Pet's Home kết hợp dữ liệu KIBO
 */
require_once __DIR__ . '/../components/helpers.php';
require_once __DIR__ . '/../../models/SanPhamModel.php';
require_once __DIR__ . '/../../models/DonHangModel.php';

$productId = trim($_GET['id'] ?? '');
$modelProd = !empty($productId) ? SanPhamModel::getProductById($productId) : null;

if (!$modelProd) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy sản phẩm — KIBO PET\'S HOME';
    $activeMenu = 'products';
    require __DIR__ . '/header.php';
    ?>
    <main class="page not-found-page" style="padding: 80px 20px; text-align: center; min-height: 50vh;">
      <div class="container" style="max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; border: 1px solid #e8e2dc; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div style="font-size: 54px; margin-bottom: 16px;">🐾</div>
        <h1 style="font-size: 24px; color: #2c1a0e; margin-bottom: 12px; font-weight: 700;">Không tìm thấy sản phẩm</h1>
        <p style="color: #665c54; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
          Sản phẩm bạn đang tìm kiếm không tồn tại, đã ngừng kinh doanh hoặc đường dẫn không chính xác.
        </p>
        <a href="index.php?action=products" class="btn primary" style="display: inline-block; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold;">
          Khám phá danh mục sản phẩm
        </a>
      </div>
    </main>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}

$product = [
    'id' => $modelProd['id'],
    'variant_id' => $modelProd['variant_id'] ?? 'BT001',
    'variants' => $modelProd['variants'] ?? [],
    'name' => $modelProd['name'],
    'brand' => $modelProd['brand'] ?: 'KIBO Original',
    'category' => $modelProd['category'] ?: 'Thức Ăn Hạt',
    'price' => (float)$modelProd['price'],
    'oldPrice' => (float)($modelProd['originalPrice'] ?? $modelProd['price']),
    'art' => 'royal',
    'color' => '#c94336',
    'status' => $modelProd['status'] ?: 'Còn hàng',
    'specs' => $modelProd['specs'] ?: 'Tiêu chuẩn',
    'short_desc' => $modelProd['desc'] ?: 'Dinh dưỡng thú cưng cao cấp KIBO chính hãng.',
    'full_desc' => $modelProd['desc'] ?: 'Sản phẩm chính hãng với tiêu chuẩn chất lượng cao, trích 5% doanh thu đồng hành cùng quỹ cứu trợ thú cưng.'
];

$pageTitle = $product['name'] . ' — KIBO PET\'S HOME';
$activeMenu = 'products';
require __DIR__ . '/header.php';

$rescueContribution = number_format(DonHangModel::tinhQuy($product['price']), 0, ',', '.');
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

      <?php if (!empty($product['variants']) && count($product['variants']) > 1): ?>
        <div class="pdp-quantity-row" style="margin-bottom: 14px;">
          <label style="display: block; font-size: 13px; font-weight: 700; color: #555; margin-bottom: 6px;">Quy cách / Phân loại</label>
          <select id="pdpVariantSelect" onchange="handleVariantChange(this)" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background: #fff;">
            <?php foreach ($product['variants'] as $v): ?>
              <option value="<?= $v['mabienthe'] ?>" data-name="<?= htmlspecialchars($v['tenbienthe']) ?>" data-price="<?= $v['giaban'] ?>">
                <?= htmlspecialchars($v['tenbienthe']) ?> — <?= number_format($v['giaban'], 0, ',', '.') ?>₫ (Còn <?= $v['soluongton'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php elseif (!empty($product['variants'])): ?>
        <input type="hidden" id="pdpVariantSelect" value="<?= $product['variants'][0]['mabienthe'] ?>" data-name="<?= htmlspecialchars($product['variants'][0]['tenbienthe']) ?>" data-price="<?= $product['variants'][0]['giaban'] ?>">
      <?php else: ?>
        <input type="hidden" id="pdpVariantSelect" value="BT001" data-name="<?= htmlspecialchars($product['specs']) ?>" data-price="<?= $product['price'] ?>">
      <?php endif; ?>

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
        <ul style="list-style: none; padding: 0; margin: 10px 0 0; display: grid; gap: 8px;">
          <?php 
          $allCats = SanPhamModel::getDanhSachDanhMuc();
          foreach ($allCats as $cat): 
          ?>
            <li>
              <a href="index.php?action=products&category=<?= urlencode($cat['tendanhmuc']) ?>" style="display: flex; align-items: center; justify-content: space-between; color: #555; text-decoration: none; font-size: 13.5px; padding: 4px 0; border-bottom: 1px dashed #eee;">
                <span><?= htmlspecialchars($cat['tendanhmuc']) ?></span>
                <span style="font-size: 11px; color: #888;">➔</span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
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

function handleVariantChange(sel) {
  const opt = sel.selectedOptions[0];
  if (!opt) return;
  const price = parseFloat(opt.dataset.price) || 0;
  const priceDisplay = new Intl.NumberFormat('vi-VN').format(price) + '₫';
  const priceEl = document.querySelector('.pdp-price-main');
  if (priceEl) priceEl.textContent = priceDisplay;
  const fundEl = document.querySelector('.pdp-fund-line strong');
  if (fundEl) fundEl.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(price * 0.05)) + '₫';
}

function handlePdpBuy() {
  const qtyInput = document.getElementById('pdpQty');
  const qty = parseInt(qtyInput ? qtyInput.value : pdpQuantity) || 1;
  const varSel = document.getElementById('pdpVariantSelect');
  let varId = '<?= $product['variant_id'] ?? 'BT001' ?>';
  let varName = '<?= addslashes($product['specs']) ?>';
  let varPrice = <?= (float)$product['price'] ?>;

  if (varSel) {
    if (varSel.tagName === 'SELECT') {
      const opt = varSel.selectedOptions[0];
      if (opt) {
        varId = opt.value;
        varName = opt.dataset.name || varName;
        varPrice = parseFloat(opt.dataset.price) || varPrice;
      }
    } else {
      varId = varSel.value;
      varName = varSel.dataset.name || varName;
      varPrice = parseFloat(varSel.dataset.price) || varPrice;
    }
  }

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
    id: varId,
    name: '<?= addslashes($product['name']) ?>',
    price: varPrice,
    variant: varName,
    quantity: qty,
    art: '<?= $product['art'] ?? 'royal' ?>',
    color: '<?= $product['color'] ?? '#c94336' ?>'
  });
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
