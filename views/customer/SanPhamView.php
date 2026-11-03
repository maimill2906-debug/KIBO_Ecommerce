<?php
/**
 * VIEW: views/customer/SanPhamView.php
 * Giao diện Catalog Sản Phẩm KIBO — Dynamic Filter & Clean Presentation
 */
$pageTitle = 'Sản Phẩm — KIBO PET\'S HOME | Thức Ăn & Phụ Kiện Thú Cưng';
$activeMenu = 'products';
require __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/SanPhamModel.php';
require_once __DIR__ . '/../../models/DonHangModel.php';

$modelProds = SanPhamModel::getAllForView();
$catalogProducts = [];
$brandsMap = [];
$categoriesMap = [];

$palette = [
    ['art' => 'royal', 'color' => '#c94336', 'species' => 'dog'],
    ['art' => 'royal', 'color' => '#d9a14b', 'species' => 'cat'],
    ['art' => 'royal', 'color' => '#bd5c44', 'species' => 'dog'],
    ['art' => 'royal', 'color' => '#e8a85f', 'species' => 'cat'],
    ['art' => 'royal', 'color' => '#ad3f31', 'species' => 'dog'],
    ['art' => 'royal', 'color' => '#c17850', 'species' => 'cat'],
    ['art' => 'royal', 'color' => '#d7593c', 'species' => 'dog'],
    ['art' => 'royal', 'color' => '#8d765f', 'species' => 'cat'],
];

$idx = 0;
foreach ($modelProds as $mp) {
    $pal = $palette[$idx % count($palette)];
    $idx++;
    $origPrice = (float)($mp['originalPrice'] ?? $mp['price']);
    $price = (float)$mp['price'];
    
    $catalogProducts[] = [
        'id' => $mp['id'],
        'variant_id' => $mp['variant_id'] ?? 'BT001',
        'variants' => $mp['variants'] ?? [],
        'name' => $mp['name'],
        'brand' => $mp['brand'] ?: 'KIBO Original',
        'volume' => $mp['specs'] ?: 'Tiêu chuẩn',
        'price' => $price,
        'oldPrice' => $origPrice,
        'pet' => $mp['category'] ?: 'Thức Ăn Hạt',
        'badge' => ($origPrice > $price && !empty($mp['badge'])) ? $mp['badge'] : '',
        'status' => $mp['status'] ?? 'Còn hàng',
        'is_sale' => ($origPrice > $price),
        'art' => $pal['art'],
        'color' => $pal['color'],
        'species' => $pal['species']
    ];
    if (!empty($mp['brand'])) $brandsMap[$mp['brand']] = true;
    if (!empty($mp['category'])) $categoriesMap[$mp['category']] = true;
}

$brands = !empty($brandsMap) ? array_keys($brandsMap) : ['KIBO Original', 'Royal Canin', 'Cature', "Hill's Science", 'Whiskas', 'Purina'];
$categories = !empty($categoriesMap) ? array_keys($categoriesMap) : ['Thức Ăn Hạt', 'Pate & Ướt', 'Bánh Thưởng', 'Cát & Phụ Kiện'];

// URL Query Filters
$searchQuery = trim($_GET['q'] ?? '');
$initBrand = trim($_GET['brand'] ?? '');
$initCategory = trim($_GET['category'] ?? '');
$initSpecies = trim($_GET['species'] ?? '');

if (!empty($searchQuery)) {
    $catalogProducts = array_values(array_filter($catalogProducts, function($p) use ($searchQuery) {
        $haystack = mb_strtolower($p['name'] . ' ' . $p['brand'] . ' ' . $p['pet']);
        return mb_strpos($haystack, mb_strtolower($searchQuery)) !== false;
    }));
}

if (!empty($initSpecies)) {
    $catalogProducts = array_values(array_filter($catalogProducts, function($p) use ($initSpecies) {
        return ($p['species'] ?? '') === $initSpecies;
    }));
}

$totalCatalogCount = count($catalogProducts);
?>

<main class="catalog-page">
  <!-- BANNER CATALOG -->
  <section class="catalog-intro">
    <div class="catalog-container">
      <div class="catalog-breadcrumb">
        <a href="index.php?action=home">Trang chủ</a>
        <?= kibo_icon('arrow', 13) ?>
        <strong>Sản phẩm</strong>
        <?php if (!empty($initSpecies)): ?>
          <?= kibo_icon('arrow', 13) ?>
          <span><?= ($initSpecies === 'dog') ? 'Dành cho Chó' : 'Dành cho Mèo' ?></span>
        <?php endif; ?>
      </div>
      <div class="catalog-banner">
        <span><?= kibo_icon('paw', 28) ?></span>
        <div>
          <small>DINH DƯỠNG THÚ CƯNG CHÍNH HÃNG</small>
          <h1>Thức ăn & Phụ kiện tuyển chọn cho Boss</h1>
        </div>
      </div>
    </div>
  </section>

  <!-- LAYOUT CATALOG 2 CỘT -->
  <div class="catalog-container catalog-layout">
    <!-- BỘ LỌC BÊN TRÁI -->
    <aside class="filter-sidebar">
      <div class="filter-heading">
        <div class="filter-heading-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line>
            <line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line>
            <line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line>
            <line x1="17" y1="16" x2="23" y2="16"></line>
          </svg>
          <h2>Bộ lọc</h2>
        </div>
        <button type="button" class="filter-reset-link" onclick="resetCatalogFilter()" title="Xóa tất cả bộ lọc">Xóa tất cả</button>
      </div>

      <!-- Thương hiệu -->
      <div class="filter-group">
        <div class="filter-group-header">
          <h3>Thương hiệu</h3>
        </div>
        <div class="filter-group-body">
          <?php foreach ($brands as $b): ?>
            <label class="filter-option">
              <input type="checkbox" name="brand" value="<?= htmlspecialchars($b) ?>" <?= (strcasecmp($initBrand, $b) === 0) ? 'checked' : '' ?> onchange="applyCatalogFilter()">
              <span class="custom-check"></span>
              <span class="filter-label"><?= htmlspecialchars($b) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Loại sản phẩm -->
      <div class="filter-group">
        <div class="filter-group-header">
          <h3>Loại sản phẩm</h3>
        </div>
        <div class="filter-group-body">
          <?php foreach ($categories as $c): ?>
            <label class="filter-option">
              <input type="checkbox" name="category" value="<?= htmlspecialchars($c) ?>" <?= (strcasecmp($initCategory, $c) === 0) ? 'checked' : '' ?> onchange="applyCatalogFilter()">
              <span class="custom-check"></span>
              <span class="filter-label"><?= htmlspecialchars($c) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- GIÁ SẢN PHẨM -->
      <div class="filter-group">
        <div class="filter-group-header">
          <h3>Giá sản phẩm</h3>
        </div>
        <div class="filter-group-body">
          <label class="filter-option">
            <input type="checkbox" name="price_range" value="0-500000" onchange="applyCatalogFilter()">
            <span class="custom-check"></span>
            <span class="filter-label">Dưới 500,000₫</span>
          </label>
          <label class="filter-option">
            <input type="checkbox" name="price_range" value="500000-1000000" onchange="applyCatalogFilter()">
            <span class="custom-check"></span>
            <span class="filter-label">500,000₫ - 1,000,000₫</span>
          </label>
          <label class="filter-option">
            <input type="checkbox" name="price_range" value="1000000-1500000" onchange="applyCatalogFilter()">
            <span class="custom-check"></span>
            <span class="filter-label">1,000,000₫ - 1,500,000₫</span>
          </label>
          <label class="filter-option">
            <input type="checkbox" name="price_range" value="2000000-5000000" onchange="applyCatalogFilter()">
            <span class="custom-check"></span>
            <span class="filter-label">2,000,000₫ - 5,000,000₫</span>
          </label>
          <label class="filter-option">
            <input type="checkbox" name="price_range" value="5000000-999999999" onchange="applyCatalogFilter()">
            <span class="custom-check"></span>
            <span class="filter-label">Trên 5,000,000₫</span>
          </label>
        </div>
      </div>

      <!-- Trạng thái -->
      <div class="filter-group">
        <div class="filter-group-header">
          <h3>Tình trạng hàng</h3>
        </div>
        <div class="filter-group-body">
          <label class="filter-option">
            <input type="radio" name="status" value="all" checked onchange="applyCatalogFilter()">
            <span class="custom-radio"></span>
            <span class="filter-label">Tất cả sản phẩm</span>
          </label>
          <label class="filter-option">
            <input type="radio" name="status" value="in_stock" onchange="applyCatalogFilter()">
            <span class="custom-radio"></span>
            <span class="filter-label">Còn hàng</span>
          </label>
          <label class="filter-option">
            <input type="radio" name="status" value="sale" onchange="applyCatalogFilter()">
            <span class="custom-radio"></span>
            <span class="filter-label">Đang giảm giá</span>
          </label>
        </div>
      </div>

      <div class="filter-actions">
        <button type="button" class="apply-filter" onclick="applyCatalogFilter()">Áp dụng</button>
        <button type="button" class="clear-filter" onclick="resetCatalogFilter()">Thiết lập lại</button>
      </div>
    </aside>

    <!-- NỘI DUNG SẢN PHẨM BÊN PHẢI -->
    <section class="catalog-content">
      <div class="catalog-toolbar">
        <div>
          <?php if (!empty($searchQuery)): ?>
            <p style="margin: 0 0 4px; font-size: 14.5px; color: #555;">Kết quả tìm kiếm cho: <strong style="color: #2c1a0e;">"<?= htmlspecialchars($searchQuery) ?>"</strong> (<?= $totalCatalogCount ?> sản phẩm)</p>
          <?php endif; ?>
          <p>Hiển thị <strong id="catalogVisibleCount"><?= $totalCatalogCount ?> / <?= $totalCatalogCount ?></strong> sản phẩm</p>
          <div class="active-filters" id="activeFilterTags"></div>
        </div>

        <label class="sort-control">
          <span>Sắp xếp:</span>
          <select id="sortSelect" onchange="applyCatalogFilter()">
            <option value="featured">Nổi bật</option>
            <option value="asc">Giá tăng dần</option>
            <option value="desc">Giá giảm dần</option>
          </select>
        </label>
      </div>

      <div class="catalog-grid" id="catalogProductsGrid">
        <?php if (empty($catalogProducts)): ?>
          <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #666; background: #fff; border: 1px solid #e0e0e0; border-radius: 6px;">
            <p style="font-size: 16px; margin-bottom: 16px;">Không tìm thấy sản phẩm nào phù hợp.</p>
            <a href="index.php?action=products" class="btn primary" style="display: inline-block;">Xem tất cả sản phẩm</a>
          </div>
        <?php else: ?>
        <?php foreach ($catalogProducts as $product): ?>
          <article class="catalog-card" 
                   data-price="<?= $product['price'] ?>" 
                   data-brand="<?= htmlspecialchars($product['brand']) ?>" 
                   data-category="<?= htmlspecialchars($product['pet']) ?>" 
                   data-status="<?= ($product['status'] === 'Còn hàng') ? 'in_stock' : 'out_of_stock' ?>"
                   data-issale="<?= ($product['oldPrice'] > $product['price']) ? '1' : '0' ?>"
                   data-species="<?= htmlspecialchars($product['species'] ?? '') ?>"
                   data-id="<?= $product['id'] ?>" 
                   onclick="window.location.href='index.php?action=product_detail&id=<?= $product['id'] ?>'">
            <div class="catalog-product-image">
              <?php if (!empty($product['badge'])): ?>
                <span class="<?= ($product['badge'] === 'MỚI') ? 'new-badge' : 'sale-badge' ?>">
                  <?= htmlspecialchars($product['badge']) ?>
                </span>
              <?php endif; ?>
              <?= kibo_product_art($product['art'], $product['color']) ?>
            </div>
            <div class="catalog-card-body">
              <small class="catalog-category"><?= htmlspecialchars($product['pet']) ?></small>
              <h2><?= htmlspecialchars($product['name']) ?></h2>
              <p class="catalog-brand"><?= htmlspecialchars($product['brand']) ?></p>
              <span class="volume-tag"><?= htmlspecialchars($product['volume']) ?></span>
              <div class="catalog-price">
                <strong><?= number_format($product['price'], 0, ',', '.') ?>₫</strong>
                <?php if ($product['oldPrice'] > $product['price']): ?>
                  <del><?= number_format($product['oldPrice'], 0, ',', '.') ?>₫</del>
                <?php endif; ?>
              </div>
              <div class="fund-contrib-line">Góp vào quỹ: <strong><?= number_format(DonHangModel::tinhQuy($product['price']), 0, ',', '.') ?>₫</strong></div>
              <button type="button" class="btn add-cart-button" onclick="event.stopPropagation(); addToCart({ id: '<?= $product['variant_id'] ?>', name: '<?= addslashes($product['name']) ?>', price: <?= $product['price'] ?>, variant: '<?= addslashes($product['volume']) ?>', art: '<?= $product['art'] ?>', color: '<?= $product['color'] ?>', quantity: 1 })">
                <?= kibo_icon('cart', 16) ?> Thêm vào giỏ
              </button>
            </div>
          </article>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<script>
function applyCatalogFilter() {
  const sort = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'featured';

  const checkedBrands = Array.from(document.querySelectorAll('input[name="brand"]:checked')).map(el => el.value);
  const checkedCategories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(el => el.value);
  const checkedPrices = Array.from(document.querySelectorAll('input[name="price_range"]:checked')).map(el => {
    const parts = el.value.split('-');
    const label = el.closest('.filter-option') ? el.closest('.filter-option').querySelector('.filter-label').textContent.trim() : el.value;
    return { min: parseInt(parts[0]), max: parseInt(parts[1]), text: label, value: el.value };
  });
  const selectedStatus = document.querySelector('input[name="status"]:checked') ? document.querySelector('input[name="status"]:checked').value : 'all';

  const grid = document.getElementById('catalogProductsGrid');
  if (!grid) return;
  const cards = Array.from(grid.querySelectorAll('.catalog-card'));

  let visible = 0;
  cards.forEach(card => {
    const price = parseInt(card.getAttribute('data-price')) || 0;
    const brand = card.getAttribute('data-brand') || '';
    const category = card.getAttribute('data-category') || '';
    const status = card.getAttribute('data-status') || '';
    const isSale = card.getAttribute('data-issale') || '0';

    let matchBrand = checkedBrands.length === 0 || checkedBrands.includes(brand);
    let matchCategory = checkedCategories.length === 0 || checkedCategories.includes(category);
    let matchPrice = checkedPrices.length === 0 || checkedPrices.some(r => price >= r.min && price <= r.max);

    let matchStatus = true;
    if (selectedStatus === 'in_stock') {
      matchStatus = (status === 'in_stock');
    } else if (selectedStatus === 'sale') {
      matchStatus = (isSale === '1');
    }

    if (matchBrand && matchCategory && matchPrice && matchStatus) {
      card.style.display = 'flex';
      visible++;
    } else {
      card.style.display = 'none';
    }
  });

  // Sắp xếp
  cards.sort((a, b) => {
    const pA = parseInt(a.getAttribute('data-price')) || 0;
    const pB = parseInt(b.getAttribute('data-price')) || 0;
    if (sort === 'asc') return pA - pB;
    if (sort === 'desc') return pB - pA;
    return 0;
  });
  cards.forEach(card => grid.appendChild(card));

  const countEl = document.getElementById('catalogVisibleCount');
  if (countEl) countEl.textContent = `${visible} / <?= $totalCatalogCount ?>`;

  // Cập nhật tag bộ lọc động
  renderActiveFilterTags(checkedBrands, checkedCategories, checkedPrices, selectedStatus);
}

function renderActiveFilterTags(brands, categories, prices, status) {
  const container = document.getElementById('activeFilterTags');
  if (!container) return;
  container.innerHTML = '';

  brands.forEach(b => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = `${escapeHtml(b)} &times;`;
    btn.title = `Bỏ lọc thương hiệu ${b}`;
    btn.onclick = () => {
      const cb = document.querySelector(`input[name="brand"][value="${CSS.escape(b)}"]`);
      if (cb) cb.checked = false;
      applyCatalogFilter();
    };
    container.appendChild(btn);
  });

  categories.forEach(c => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = `${escapeHtml(c)} &times;`;
    btn.title = `Bỏ lọc loại sản phẩm ${c}`;
    btn.onclick = () => {
      const cb = document.querySelector(`input[name="category"][value="${CSS.escape(c)}"]`);
      if (cb) cb.checked = false;
      applyCatalogFilter();
    };
    container.appendChild(btn);
  });

  prices.forEach(p => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = `${escapeHtml(p.text)} &times;`;
    btn.title = `Bỏ lọc khoảng giá ${p.text}`;
    btn.onclick = () => {
      const cb = document.querySelector(`input[name="price_range"][value="${CSS.escape(p.value)}"]`);
      if (cb) cb.checked = false;
      applyCatalogFilter();
    };
    container.appendChild(btn);
  });

  if (status === 'in_stock') {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = `Còn hàng &times;`;
    btn.title = 'Bỏ lọc còn hàng';
    btn.onclick = () => {
      const r = document.querySelector('input[name="status"][value="all"]');
      if (r) r.checked = true;
      applyCatalogFilter();
    };
    container.appendChild(btn);
  } else if (status === 'sale') {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = `Đang giảm giá &times;`;
    btn.title = 'Bỏ lọc giảm giá';
    btn.onclick = () => {
      const r = document.querySelector('input[name="status"][value="all"]');
      if (r) r.checked = true;
      applyCatalogFilter();
    };
    container.appendChild(btn);
  }
}

function resetCatalogFilter() {
  const sortSelect = document.getElementById('sortSelect');
  if (sortSelect) sortSelect.value = 'featured';

  document.querySelectorAll('.filter-sidebar input[type="checkbox"]').forEach(cb => cb.checked = false);
  const allStatus = document.querySelector('.filter-sidebar input[value="all"]');
  if (allStatus) allStatus.checked = true;

  if (window.location.search.includes('q=') || window.location.search.includes('species=') || window.location.search.includes('category=') || window.location.search.includes('brand=')) {
    window.location.href = 'index.php?action=products';
    return;
  }
  applyCatalogFilter();
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', function() {
  applyCatalogFilter();
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
