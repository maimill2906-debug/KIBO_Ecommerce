<?php
/**
 * VIEW: views/customer/PolicyView.php
 * Trang Chính Sách & Điều Khoản KIBO Pet's Home
 */
$tab = $_GET['tab'] ?? 'shipping';
$titles = [
    'shipping' => 'Chính Sách Giao Hàng & Vận Chuyển',
    'return' => 'Chính Sách Đổi Trả Hàng Trong Vòng 3 Ngày',
    'terms' => 'Điều Khoản Dịch Vụ & Sử Dụng',
    'privacy' => 'Chính Sách Bảo Mật Thông Tin Khách Hàng'
];
$pageTitle = ($titles[$tab] ?? 'Chính Sách') . ' — KIBO PET\'S HOME';
$activeMenu = 'policy';
require __DIR__ . '/header.php';
?>

<main class="page policy-page" style="padding-bottom: 60px;">
  <section style="background: #fbf7f2; border-bottom: 1px solid #ebd9c8; padding: 32px 0;">
    <div class="container">
      <div style="font-size: 13px; color: #7c5e48; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <a href="index.php?action=home" style="color: #7c5e48; text-decoration: none;">Trang chủ</a>
        <?= kibo_icon('arrow', 12) ?>
        <strong>Hỗ trợ khách hàng</strong>
      </div>
      <h1 style="font-size: 26px; color: #2c1a0e; margin: 0;"><?= htmlspecialchars($titles[$tab] ?? 'Chính sách KIBO') ?></h1>
    </div>
  </section>

  <div class="container" style="margin-top: 32px; display: grid; grid-template-columns: 240px 1fr; gap: 32px;">
    <!-- Sidebar Menu Điều Hướng Chính Sách -->
    <aside>
      <nav style="background: #ffffff; border: 1px solid #ebd9c8; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        <a href="index.php?action=policy&tab=shipping" style="display: block; padding: 14px 18px; border-bottom: 1px solid #f3ebe1; text-decoration: none; font-size: 14px; font-weight: <?= ($tab === 'shipping') ? 'bold' : 'normal' ?>; color: <?= ($tab === 'shipping') ? '#1e7b49' : '#4a3b32' ?>; background: <?= ($tab === 'shipping') ? '#f2f9f5' : '#fff' ?>;">
          Chính sách giao hàng
        </a>
        <a href="index.php?action=policy&tab=return" style="display: block; padding: 14px 18px; border-bottom: 1px solid #f3ebe1; text-decoration: none; font-size: 14px; font-weight: <?= ($tab === 'return') ? 'bold' : 'normal' ?>; color: <?= ($tab === 'return') ? '#1e7b49' : '#4a3b32' ?>; background: <?= ($tab === 'return') ? '#f2f9f5' : '#fff' ?>;">
          Đổi trả trong 3 ngày
        </a>
        <a href="index.php?action=policy&tab=terms" style="display: block; padding: 14px 18px; border-bottom: 1px solid #f3ebe1; text-decoration: none; font-size: 14px; font-weight: <?= ($tab === 'terms') ? 'bold' : 'normal' ?>; color: <?= ($tab === 'terms') ? '#1e7b49' : '#4a3b32' ?>; background: <?= ($tab === 'terms') ? '#f2f9f5' : '#fff' ?>;">
          Điều khoản sử dụng
        </a>
        <a href="index.php?action=policy&tab=privacy" style="display: block; padding: 14px 18px; text-decoration: none; font-size: 14px; font-weight: <?= ($tab === 'privacy') ? 'bold' : 'normal' ?>; color: <?= ($tab === 'privacy') ? '#1e7b49' : '#4a3b32' ?>; background: <?= ($tab === 'privacy') ? '#f2f9f5' : '#fff' ?>;">
          Chính sách bảo mật
        </a>
      </nav>
    </aside>

    <!-- Nội dung chính sách -->
    <article style="background: #ffffff; border: 1px solid #ebd9c8; border-radius: 8px; padding: 32px; line-height: 1.7; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
      <?php if ($tab === 'shipping'): ?>
        <h2 style="color: #2c1a0e; margin-top: 0;">1. Quy định về phạm vi và thời gian giao hàng</h2>
        <p>KIBO Pet's Home hỗ trợ vận chuyển sản phẩm dinh dưỡng và phụ kiện thú cưng trên phạm vi toàn quốc.</p>
        <ul>
          <li><strong>Khu vực nội thành TP. Hồ Chí Minh:</strong> Giao hàng hỏa tốc trong 24 giờ kể từ khi xuất kho.</li>
          <li><strong>Khu vực ngoại thành & các tỉnh thành khác:</strong> Giao hàng qua đơn vị chuyển phát uy tín từ 2 - 4 ngày làm việc.</li>
        </ul>

        <h2 style="color: #2c1a0e; margin-top: 24px;">2. Chính sách phí vận chuyển ưu đãi</h2>
        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
          <thead>
            <tr style="background: #fbf7f2; text-align: left;">
              <th style="padding: 10px; border: 1px solid #ebd9c8;">Khu vực nhận hàng</th>
              <th style="padding: 10px; border: 1px solid #ebd9c8;">Giá trị đơn hàng</th>
              <th style="padding: 10px; border: 1px solid #ebd9c8;">Phí vận chuyển</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding: 10px; border: 1px solid #ebd9c8;">Nội thành TP.HCM (Quận 1 - 12, Bình Thạnh, Gò Vấp, Phú Nhuận, Tân Bình, Tân Phú)</td>
              <td style="padding: 10px; border: 1px solid #ebd9c8;">Từ 200.000đ trở lên</td>
              <td style="padding: 10px; border: 1px solid #ebd9c8; color: #16a34a; font-weight: bold;">MIỄN PHÍ VẬN CHUYỂN</td>
            </tr>
            <tr>
              <td style="padding: 10px; border: 1px solid #ebd9c8;">Ngoại thành TP.HCM & Tất cả các tỉnh/thành khác</td>
              <td style="padding: 10px; border: 1px solid #ebd9c8;">Từ 500.000đ trở lên</td>
              <td style="padding: 10px; border: 1px solid #ebd9c8; color: #16a34a; font-weight: bold;">MIỄN PHÍ VẬN CHUYỂN</td>
            </tr>
            <tr>
              <td style="padding: 10px; border: 1px solid #ebd9c8;">Đơn hàng chưa đạt mức miễn phí</td>
              <td style="padding: 10px; border: 1px solid #ebd9c8;">Dưới 200.000đ hoặc dưới 500.000đ (ngoại tỉnh)</td>
              <td style="padding: 10px; border: 1px solid #ebd9c8; font-weight: bold; color: #b91c1c;">30.000đ</td>
            </tr>
          </tbody>
        </table>

      <?php elseif ($tab === 'return'): ?>
        <h2 style="color: #2c1a0e; margin-top: 0;">1. Quy định thời hạn đổi trả 3 ngày</h2>
        <p>Khách hàng được quyền gửi yêu cầu đổi hàng hoặc hoàn tiền trong vòng <strong>3 ngày (72 giờ)</strong> kể từ thời điểm nhận hàng thành công.</p>
        
        <h2 style="color: #2c1a0e; margin-top: 24px;">2. Điều kiện áp dụng đổi trả</h2>
        <ul>
          <li>Sản phẩm giao sai mẫu, sai trọng lượng, hết hạn sử dụng (date) hoặc rách bao bì do vận chuyển.</li>
          <li>Sản phẩm có lỗi từ nhà sản xuất được xác nhận.</li>
          <li>Có cung cấp hình ảnh/video mở hộp minh chứng khi gửi yêu cầu trong tài khoản hoặc trang tra cứu.</li>
        </ul>

        <h2 style="color: #2c1a0e; margin-top: 24px;">3. Quyền lợi hoàn tiền và điểm thưởng</h2>
        <p>Khi yêu cầu hoàn tiền được duyệt, tiền thanh toán sẽ được hoàn trả đầy đủ và lượng tồn kho sẽ được kiểm kê theo quyết định tiêu hủy hoặc nhập lại kho của bộ phận kỹ thuật.</p>

      <?php elseif ($tab === 'terms'): ?>
        <h2 style="color: #2c1a0e; margin-top: 0;">1. Điều khoản chung</h2>
        <p>Khi truy cập và đặt hàng tại KIBO Pet's Home, quý khách đồng ý tuân thủ các quy chế hoạt động của nền tảng thương mại điện tử KIBO.</p>
        
        <h2 style="color: #2c1a0e; margin-top: 24px;">2. Cam kết 5% vì cộng đồng</h2>
        <p>KIBO cam kết trích chính xác 5% doanh thu của mọi đơn hàng thành công vào Quỹ cứu trợ động vật đối tác và công khai minh bạch sao kê tài chính.</p>

      <?php else: ?>
        <h2 style="color: #2c1a0e; margin-top: 0;">1. Thu thập thông tin</h2>
        <p>KIBO chỉ thu thập các thông tin tối thiểu cần thiết để xử lý đơn hàng và giao hàng: Họ tên, Số điện thoại, Email và Địa chỉ giao hàng.</p>
        
        <h2 style="color: #2c1a0e; margin-top: 24px;">2. Cam kết bảo mật</h2>
        <p>Mọi thông tin cá nhân đều được lưu trữ bảo mật trên cơ sở dữ liệu có mã hóa. Chúng tôi tuyệt đối không bán hoặc chia sẻ thông tin khách hàng cho bất kỳ bên thứ ba nào vì mục đích quảng cáo.</p>
      <?php endif; ?>
    </article>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
