<?php
/**
 * HEADER: views/customer/header.php
 * Thiết kế 100% chuẩn xác theo KIBO Pet E-Commerce Platform (1)
 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}
require_once __DIR__ . '/../components/helpers.php';

$activeMenu = $activeMenu ?? 'home';
$pageTitle = $pageTitle ?? 'KIBO — PET\'S HOME | Dinh Dưỡng Thú Cưng Cao Cấp & Quỹ Cứu Trợ 5%';
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <!-- Google Fonts: Montserrat (Font hình học hiện đại, tròn trịa chuẩn phong cách Geomanist + hỗ trợ 100% tiếng Việt) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

  <!-- CSS Chính -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <script>
    window.KIBO_BUSINESS = <?= json_encode(require __DIR__ . '/../../config/business.php') ?>;
    window.KIBO_USER = <?= json_encode($currentUser ? ['makh' => $currentUser['MaKH'] ?? null, 'vaitro' => $currentUser['VaiTro'] ?? null] : null) ?>;
  </script>
</head>
<body>

  <!-- ====================================================================
       1. TOPBAR NÂU ĐẬM
       ==================================================================== -->
  <div class="topbar">
    <div class="container topbar-inner">
      <span class="social">f&nbsp;&nbsp; ◎ &nbsp;&nbsp;♪</span>
      <span class="donation-note">
        <?= kibo_icon('heart', 14) ?> 5% mỗi đơn hàng được gửi đến các trạm cứu hộ
      </span>
      <span class="top-links">
        <a href="index.php?action=tracking"><?= kibo_icon('truck', 14) ?> Tra cứu đơn hàng</a>
        <i></i>
        <?php if ($currentUser): ?>
          <a href="<?= ($currentUser['VaiTro'] === 'Admin') ? 'index.php?action=admin' : 'index.php?action=account' ?>">
            <?= kibo_icon('user', 14) ?> Xin chào, <strong><?= htmlspecialchars($currentUser['HoTen'] ?? $currentUser['TenDangNhap'] ?? 'Thành viên') ?></strong>
          </a>
          <i></i>
          <a href="index.php?action=logout" style="color: #ffccbc;">[Đăng xuất]</a>
        <?php else: ?>
          <a href="index.php?action=login" onclick="openAuthModal(); return false;">
            <?= kibo_icon('user', 14) ?> Đăng nhập / Đăng ký
          </a>
        <?php endif; ?>
      </span>
    </div>
  </div>

  <!-- ====================================================================
       2. HEADER CHÍNH
       ==================================================================== -->
  <header class="header">
    <div class="container header-main">
      <!-- Logo KIBO chính thức -->
      <a href="index.php?action=home" class="logo-button" title="KIBO — PET'S HOME">
        <?= kibo_logo_svg('default', 44) ?>
      </a>

      <!-- Ô tìm kiếm -->
      <form action="index.php" method="GET" class="search">
        <input type="hidden" name="action" value="products">
        <input name="q" placeholder="Tìm thức ăn, phụ kiện cho bé..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
        <button type="submit" aria-label="Tìm kiếm" class="search-btn">
          <?= kibo_icon('search', 18) ?>
        </button>
      </form>

      <!-- Hotline -->
      <a href="tel:0772906756" class="hotline" title="Gọi hotline tư vấn miễn phí">
        <?= kibo_icon('phone', 24) ?>
        <span>
          <strong>0772 906 756</strong>
          <small>Tổng đài miễn phí</small>
        </span>
      </a>

      <!-- Giỏ hàng -->
      <button type="button" class="cart-btn" onclick="openCartModal()" title="Xem giỏ hàng">
        <?= kibo_icon('cart', 24) ?>
        <span class="cart-copy">
          <strong id="headerCartCountText">(1) Sản phẩm</strong>
          <small>Giỏ hàng</small>
        </span>
      </button>
    </div>
  </header>

  <!-- ====================================================================
       3. NAVIGATION BAR
       ==================================================================== -->
  <nav class="nav">
    <div class="container nav-inner">
      <a href="index.php?action=home" class="<?= ($activeMenu === 'home') ? 'active-menu' : '' ?>">TRANG CHỦ</a>
      <a href="index.php?action=about" class="<?= ($activeMenu === 'about') ? 'active-menu' : '' ?>">VỀ CHÚNG TÔI</a>
      <a href="index.php?action=products" class="<?= ($activeMenu === 'products') ? 'active-menu' : '' ?>">SẢN PHẨM</a>
      <a href="index.php?action=rescue" class="<?= ($activeMenu === 'rescue') ? 'active-menu' : '' ?>">QUỸ CỨU TRỢ</a>
      <a href="index.php?action=tracking" class="<?= ($activeMenu === 'tracking') ? 'active-menu' : '' ?>">TRA CỨU ĐƠN</a>
      <?php if ($currentUser): ?>
        <a href="<?= ($currentUser['VaiTro'] === 'Admin') ? 'index.php?action=admin' : 'index.php?action=account' ?>" class="<?= ($activeMenu === 'account') ? 'active-menu' : '' ?>">TÀI KHOẢN</a>
      <?php else: ?>
        <a href="index.php?action=login" onclick="openAuthModal(); return false;">ĐĂNG NHẬP</a>
      <?php endif; ?>
    </div>
  </nav>
