<?php
/**
 * VIEW: views/customer/PolicyView.php
 * Trang Chính Sách & Hỗ Trợ Khách Hàng KIBO Pet's Home
 */
$tab = $_GET['tab'] ?? 'shipping';
$titles = [
    'shipping' => 'Chính sách giao hàng & vận chuyển',
    'return'   => 'Chính sách đổi trả trong 03 ngày',
    'terms'    => 'Điều khoản giao dịch chung',
    'privacy'  => 'Chính sách bảo mật dữ liệu cá nhân'
];
$subtitles = [
    'shipping' => 'Áp dụng cho mọi đơn hàng đặt trực tuyến tại KIBO trên phạm vi 34 tỉnh, thành phố mới theo Nghị quyết 202/2025/QH15.',
    'return'   => 'Hỗ trợ đổi mới 1-1 hoặc hoàn tiền 100% trong vòng 72 giờ theo Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15.',
    'terms'    => 'Quy định về hợp đồng điện tử, thanh toán và Quỹ cứu trợ 5% theo Luật Thương mại điện tử số 122/2025/QH15.',
    'privacy'  => 'Cam kết bảo vệ thông tin khách hàng theo Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 và Nghị định 356/2025/NĐ-CP.'
];
$pageTitle = ($titles[$tab] ?? 'Hỗ trợ khách hàng') . ' - KIBO PET\'S HOME';
$activeMenu = 'policy';
require __DIR__ . '/header.php';

// Đọc trực tiếp các hằng số cấu hình nghiệp vụ từ config/business.php để luôn đồng bộ 100% với code xử lý đơn hàng
$biz = require __DIR__ . '/../../config/business.php';
$quyPercent   = round(((float)($biz['quy_ty_le'] ?? 0.05)) * 100);
$doiTraNgay   = (int)($biz['doi_tra_so_ngay'] ?? 3);
$doiTraGio    = $doiTraNgay * 24;
$diemMoiDong  = number_format((int)($biz['diem_moi_dong'] ?? 10000), 0, ',', '.') . 'đ';
$phiShipCoBan = number_format((int)($biz['ship']['phi_co_ban'] ?? 20000), 0, ',', '.') . 'đ';
$freeNoiThanh = number_format((int)($biz['ship']['mien_phi_noi_thanh'] ?? 200000), 0, ',', '.') . 'đ';
$freeNgoaiTinh= number_format((int)($biz['ship']['mien_phi_khac'] ?? 500000), 0, ',', '.') . 'đ';
?>

<style>
  .kibo-policy-wrap {
    background: var(--cream);
    padding-bottom: 80px;
    min-height: 760px;
  }
  .kibo-policy-head {
    background: #f4efe7;
    border-bottom: 1px solid var(--line);
    padding: 20px 0 36px;
  }
  .kibo-policy-bc {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 16px;
  }
  .kibo-policy-bc a { color: var(--muted); text-decoration: none; }
  .kibo-policy-bc a:hover { color: var(--brown); }
  .kibo-policy-bc strong { color: var(--brown); }
  .kibo-policy-head h1 {
    font-size: clamp(26px, 3vw, 36px);
    color: var(--brown);
    margin: 6px 0 8px;
    line-height: 1.2;
  }
  .kibo-policy-head p {
    margin: 0;
    color: #625850;
    font-size: 15px;
    max-width: 720px;
  }
  .kibo-policy-grid {
    display: grid;
    grid-template-columns: 270px minmax(0, 1fr);
    gap: 32px;
    margin-top: 32px;
    align-items: start;
  }
  .kibo-policy-nav {
    background: #ffffff;
    border: 1px solid var(--line);
    border-radius: 6px;
    overflow: hidden;
  }
  .kibo-policy-nav-title {
    padding: 13px 18px;
    background: #f5f4f2;
    border-left: 4px solid var(--brown);
    font-size: 12.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--brown);
    margin: 0;
  }
  .kibo-policy-nav a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    font-size: 14px;
    font-weight: 500;
    color: #4f463f;
    border-bottom: 1px solid #efede9;
    text-decoration: none;
    transition: all .18s ease;
  }
  .kibo-policy-nav a:last-child { border-bottom: 0; }
  .kibo-policy-nav a:hover {
    color: var(--green-dark);
    background: #faf8f5;
  }
  .kibo-policy-nav a.is-active {
    color: var(--brown);
    font-weight: 700;
    background: #fbf9f5;
    border-left: 3px solid var(--green);
    padding-left: 15px;
  }
  .kibo-policy-helpbox {
    margin-top: 18px;
    padding: 20px;
    background: var(--brown);
    color: #ffffff;
    border-radius: 6px;
  }
  .kibo-policy-helpbox small {
    display: block;
    font-size: 11px;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #aed69c;
    font-weight: 700;
    margin-bottom: 6px;
  }
  .kibo-policy-helpbox strong {
    display: block;
    font-size: 18px;
    margin-bottom: 6px;
  }
  .kibo-policy-helpbox p {
    margin: 0;
    font-size: 13px;
    color: #d8cfc8;
    line-height: 1.55;
  }
  .kibo-policy-doc {
    background: #ffffff;
    border: 1px solid var(--line);
    border-radius: 6px;
    padding: 40px 46px;
    color: #3d352e;
    font-size: 15px;
    line-height: 1.75;
    min-width: 0;
  }
  .kibo-doc-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-bottom: 20px;
    margin-bottom: 28px;
    border-bottom: 1px solid var(--line);
    font-size: 13px;
    color: var(--muted);
  }
  .kibo-doc-meta span strong { color: var(--brown); }
  .kibo-doc-section {
    margin-bottom: 32px;
  }
  .kibo-doc-section:last-child {
    margin-bottom: 0;
  }
  .kibo-doc-section h2 {
    font-size: 18.5px;
    font-weight: 700;
    color: var(--brown);
    margin: 0 0 12px;
    display: flex;
    align-items: baseline;
    gap: 10px;
  }
  .kibo-doc-section h2 b {
    font-size: 13px;
    color: var(--green-dark);
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
  }
  .kibo-doc-section p {
    margin: 0 0 12px;
    color: #4f463f;
  }
  .kibo-doc-list {
    margin: 0;
    padding-left: 18px;
    color: #4f463f;
  }
  .kibo-doc-list li {
    margin-bottom: 8px;
  }
  .kibo-doc-list li:last-child {
    margin-bottom: 0;
  }
  .kibo-fee-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin: 16px 0 10px;
  }
  .kibo-fee-card {
    padding: 18px 20px;
    border: 1px solid var(--line);
    border-radius: 5px;
    background: #faf8f5;
  }
  .kibo-fee-card small {
    display: block;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--muted);
    margin-bottom: 6px;
  }
  .kibo-fee-card strong {
    display: block;
    font-size: 20px;
    color: var(--brown);
    margin-bottom: 6px;
  }
  .kibo-fee-card strong.free { color: var(--green-dark); }
  .kibo-fee-card p {
    margin: 0;
    font-size: 13.5px;
    line-height: 1.5;
    color: #5c524a;
  }
  .kibo-CleanTable {
    width: 100%;
    border-collapse: collapse;
    margin: 14px 0 8px;
    font-size: 14px;
  }
  .kibo-CleanTable th {
    text-align: left;
    padding: 12px 16px;
    background: #f5f2eb;
    color: var(--brown);
    font-size: 12.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
    white-space: normal;
  }
  .kibo-CleanTable td {
    padding: 14px 16px;
    border-bottom: 1px solid #efede9;
    color: #4a413a;
    vertical-align: top;
    white-space: normal;
    line-height: 1.55;
  }
  .kibo-CleanTable td:first-child {
    font-weight: 600;
    color: var(--brown);
    width: 28%;
  }
  .kibo-step-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin: 16px 0;
  }
  .kibo-step-box {
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: 5px;
    background: #ffffff;
  }
  .kibo-step-box span {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    color: var(--green-dark);
    background: #edf5ea;
    padding: 3px 8px;
    border-radius: 3px;
    margin-bottom: 8px;
  }
  .kibo-step-box strong {
    display: block;
    font-size: 14.5px;
    color: var(--brown);
    margin-bottom: 4px;
  }
  .kibo-step-box p {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: var(--muted);
  }
  .kibo-policy-foot {
    margin-top: 32px;
    padding-top: 20px;
    border-top: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 13.5px;
    color: var(--muted);
  }
  .kibo-policy-foot a {
    color: var(--brown);
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 3px;
  }
  @media (max-width: 960px) {
    .kibo-policy-grid { grid-template-columns: 1fr; }
    .kibo-policy-doc { padding: 26px 22px; }
    .kibo-fee-grid, .kibo-step-row { grid-template-columns: 1fr; }
  }
</style>

<main class="kibo-policy-wrap">
  <section class="kibo-policy-head">
    <div class="container">
      <div class="kibo-policy-bc">
        <a href="index.php?action=home">Trang chủ</a>
        <?= kibo_icon('arrow', 12) ?>
        <span>Hỗ trợ khách hàng</span>
        <?= kibo_icon('arrow', 12) ?>
        <strong><?= htmlspecialchars($titles[$tab] ?? 'Chính sách') ?></strong>
      </div>
      <span class="eyebrow brown" style="margin-bottom: 6px;">KIBO | HỖ TRỢ &amp; PHÁP LÝ</span>
      <h1><?= htmlspecialchars($titles[$tab] ?? 'Chính sách KIBO') ?></h1>
      <p><?= htmlspecialchars($subtitles[$tab] ?? '') ?></p>
    </div>
  </section>

  <div class="container kibo-policy-grid">
    <!-- Cột trái: Danh mục chính sách -->
    <aside>
      <nav class="kibo-policy-nav">
        <h2 class="kibo-policy-nav-title">Danh mục quy định</h2>
        <a href="index.php?action=policy&tab=shipping" class="<?= ($tab === 'shipping') ? 'is-active' : '' ?>">
          <span>Chính sách giao hàng</span>
          <?= kibo_icon('arrow', 14) ?>
        </a>
        <a href="index.php?action=policy&tab=return" class="<?= ($tab === 'return') ? 'is-active' : '' ?>">
          <span>Đổi trả trong 03 ngày</span>
          <?= kibo_icon('arrow', 14) ?>
        </a>
        <a href="index.php?action=policy&tab=terms" class="<?= ($tab === 'terms') ? 'is-active' : '' ?>">
          <span>Điều khoản giao dịch</span>
          <?= kibo_icon('arrow', 14) ?>
        </a>
        <a href="index.php?action=policy&tab=privacy" class="<?= ($tab === 'privacy') ? 'is-active' : '' ?>">
          <span>Bảo mật dữ liệu cá nhân</span>
          <?= kibo_icon('arrow', 14) ?>
        </a>
      </nav>

      <div class="kibo-policy-helpbox">
        <small>Cần hỗ trợ nhanh?</small>
        <strong><?= htmlspecialchars(kibo_business('hotline') ?? '0772 906 756') ?></strong>
        <p>Đội ngũ KIBO trực từ 8:00 - 21:00 mỗi ngày để giải đáp đơn hàng, đổi trả và chứng từ Quỹ 5%.</p>
      </div>
    </aside>

    <!-- Cột phải: Nội dung chi tiết -->
    <article class="kibo-policy-doc">
      <?php if ($tab === 'shipping'): ?>
        <div class="kibo-doc-meta">
          <span>Địa giới hành chính: <strong>34 Tỉnh, Thành phố (Nghị quyết số 202/2025/QH15)</strong></span>
          <span>Đối tác vận chuyển: <strong>Giao Hàng Nhanh (GHN), Giao Hàng Tiết Kiệm (GHTK), Viettel Post</strong></span>
        </div>

        <section class="kibo-doc-section">
          <h2><b>Mục 01.</b> Biểu phí vận chuyển &amp; Ngưỡng miễn phí</h2>
          <p>Phí giao hàng được tính tự động tại bước Thanh toán dựa trên khu vực nhận hàng và giá trị đơn hàng sau khi trừ khuyến mãi:</p>
          <div class="kibo-fee-grid">
            <div class="kibo-fee-card">
              <small>Nội thành TP.HCM</small>
              <strong class="free">Miễn phí 0đ</strong>
              <p>Cho đơn từ <strong><?= $freeNoiThanh ?></strong> trở lên tại các phường trung tâm và khu vực Thủ Đức.</p>
            </div>
            <div class="kibo-fee-card">
              <small>Ngoại thành &amp; 33 Tỉnh/Thành</small>
              <strong class="free">Miễn phí 0đ</strong>
              <p>Cho đơn từ <strong><?= $freeNgoaiTinh ?></strong> trở lên giao đi toàn bộ các tỉnh, thành trên cả nước.</p>
            </div>
            <div class="kibo-fee-card">
              <small>Đơn dưới ngưỡng Freeship</small>
              <strong><?= $phiShipCoBan ?> / đơn</strong>
              <p>Mức phí đồng giá hỗ trợ vận chuyển cho đơn dưới <?= $freeNoiThanh ?> (nội thành) hoặc <?= $freeNgoaiTinh ?> (tỉnh).</p>
            </div>
          </div>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Mục 02.</b> Thời gian xử lý &amp; Giao nhận dự kiến</h2>
          <table class="kibo-CleanTable">
            <thead>
              <tr>
                <th>Khu vực giao hàng</th>
                <th>Thời gian toàn trình</th>
                <th>Quy cách đóng gói &amp; Giao nhận</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Nội thành TP. Hồ Chí Minh</td>
                <td>Trong vòng 24 giờ</td>
                <td>Đơn đặt trước 16:00 được đóng gói xuất kho trong ngày; bọc chống sốc 2 lớp với thức ăn đóng hộp và pate.</td>
              </tr>
              <tr>
                <td>Khu vực mở rộng TP.HCM (Bình Dương, Bà Rịa - Vũng Tàu hợp nhất, Củ Chi, Cần Giờ)</td>
                <td>Từ 01 - 02 ngày làm việc</td>
                <td>Cập nhật mã vận đơn trực tuyến ngay khi bàn giao bưu cục; bưu tá gọi điện trước khi giao 15-30 phút.</td>
              </tr>
              <tr>
                <td>33 Tỉnh / Thành phố khác (theo Nghị quyết 202/2025/QH15 ngày 12/06/2025)</td>
                <td>Từ 02 - 04 ngày làm việc</td>
                <td>Đóng thùng carton niêm phong tem KIBO, hỗ trợ tra cứu hành trình 4 bước tại mục Tra cứu đơn hàng.</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Mục 03.</b> Quy định đồng kiểm khi nhận hàng</h2>
          <ul class="kibo-doc-list">
            <li>Khách hàng được quyền mở kiện hàng kiểm tra ngoại quan (tem niêm phong, đúng phân loại trọng lượng, hạn sử dụng in trên bao bì và tình trạng túi hạt/hộp pate không bị bục rách) trước khi thanh toán COD.</li>
            <li>Nếu phát hiện sản phẩm bị móp méo nặng hoặc sai mẫu đã đặt, quý khách có quyền từ chối nhận hàng ngay tại thời điểm giao mà không mất bất kỳ chi phí vận chuyển nào.</li>
          </ul>
        </section>

      <?php elseif ($tab === 'return'): ?>
        <div class="kibo-doc-meta">
          <span>Căn cứ: <strong>Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15 &amp; Nghị định 55/2024/NĐ-CP</strong></span>
          <span>Thời hạn hỗ trợ: <strong>0<?= $doiTraNgay ?> ngày (<?= $doiTraGio ?> giờ) sau khi giao hàng</strong></span>
        </div>

        <section class="kibo-doc-section">
          <h2><b>Mục 01.</b> Các trường hợp áp dụng đổi trả</h2>
          <p>Tuân thủ trách nhiệm đối với hàng hóa có khuyết tật tại Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15 và Nghị định 55/2024/NĐ-CP, KIBO nhận đổi mới 1-1 hoặc hoàn tiền 100% trong vòng <strong><?= $doiTraGio ?> giờ (0<?= $doiTraNgay ?> ngày)</strong> kể từ lúc đơn hàng báo <em>Giao thành công</em> đối với:</p>
          <ul class="kibo-doc-list">
            <li>Sản phẩm giao sai chủng loại, sai trọng lượng hoặc thiếu số lượng so với đơn đặt hàng.</li>
            <li>Bao bì hạt, súp thưởng hoặc hộp pate bị xì hơi, phồng rộp, rách niêm phong do quá trình vận chuyển.</li>
            <li>Sản phẩm có hạn sử dụng thực tế còn dưới 06 tháng (trừ các mặt hàng thanh lý cận date đã ghi rõ trên tên sản phẩm).</li>
            <li>Nhu cầu đổi sang dòng hạt khác khi bao bì sản phẩm cũ còn nguyên tem niêm phong, chưa khui mở.</li>
          </ul>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Mục 02.</b> Quy trình 04 bước gửi yêu cầu đổi trả trực tuyến</h2>
          <div class="kibo-step-row">
            <div class="kibo-step-box">
              <span>Bước 01</span>
              <strong>Chọn đơn hàng</strong>
              <p>Vào mục Tài khoản hoặc trang Tra cứu đơn hàng, nhập mã đơn KB-XXXXXX đã giao.</p>
            </div>
            <div class="kibo-step-box">
              <span>Bước 02</span>
              <strong>Gửi ảnh thực tế</strong>
              <p>Mô tả tình trạng sản phẩm và tải lên ảnh chụp thực tế (JPG, PNG, WEBP tối đa 5MB).</p>
            </div>
            <div class="kibo-step-box">
              <span>Bước 03</span>
              <strong>KIBO phản hồi</strong>
              <p>Bộ phận vận hành đối chiếu và xác nhận phương án xử lý trong vòng 24 giờ làm việc.</p>
            </div>
            <div class="kibo-step-box">
              <span>Bước 04</span>
              <strong>Hoàn tiền / Đổi mới</strong>
              <p>Thu hồi kiện hàng lỗi miễn phí và chuyển khoản hoàn tiền 100% hoặc gửi bù sản phẩm mới.</p>
            </div>
          </div>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Mục 03.</b> Nguyên tắc kiểm soát kho hàng hoàn &amp; Quỹ cứu trợ</h2>
          <ul class="kibo-doc-list">
            <li><strong>Không tái bán hàng thực phẩm đã khui hoặc hư hỏng:</strong> Mọi kiện thức ăn hoàn về do lỗi rách bao bì hoặc biến chất đều được phân loại vào kho hủy, tuyệt đối không cộng ngược vào tồn kho bán lẻ.</li>
            <li><strong>Đồng bộ điểm thưởng và Quỹ 5%:</strong> Khi đơn hàng được hoàn tiền toàn phần, hệ thống tự động khấu trừ số điểm tích lũy đã cộng của đơn đó và cập nhật trạng thái bản ghi đóng góp 5% Quỹ cứu trợ về <em>Đã hủy</em> để số liệu sao kê luôn khớp thực tế.</li>
          </ul>
        </section>

      <?php elseif ($tab === 'terms'): ?>
        <div class="kibo-doc-meta">
          <span>Căn cứ: <strong>Luật Thương mại điện tử số 122/2025/QH15 &amp; Luật Giao dịch điện tử số 20/2023/QH15</strong></span>
          <span>Bổ trợ: <strong>Nghị định 55/2024/NĐ-CP &amp; Nghị định 85/2021/NĐ-CP</strong></span>
        </div>

        <section class="kibo-doc-section">
          <h2><b>Điều 01.</b> Phân loại nền tảng &amp; Nguyên tắc giao dịch</h2>
          <p>Theo phân loại tại Luật Thương mại điện tử số 122/2025/QH15 (thông qua ngày 10/12/2025), <strong>KIBO Pet's Home</strong> thuộc mô hình <em>Nền tảng thương mại điện tử kinh doanh trực tiếp</em> do đội ngũ KIBO trực tiếp vận hành, cung cấp thức ăn dinh dưỡng, phụ kiện thú cưng chính hãng và trích 5% doanh thu mỗi đơn thành công cho các trạm cứu hộ động vật.</p>
          <ul class="kibo-doc-list">
            <li>Khách hàng có thể mua sắm thông qua tài khoản thành viên hoặc đặt hàng nhanh không cần đăng nhập (Guest Checkout).</li>
            <li>Giá bán niêm yết trên website là giá cuối cùng đã bao gồm thuế GTGT (chưa bao gồm phí vận chuyển phát sinh nếu đơn hàng chưa đạt ngưỡng miễn phí).</li>
            <li>Hóa đơn điện tử và thông điệp dữ liệu đơn hàng được khởi tạo tự động có giá trị pháp lý theo Luật Giao dịch điện tử số 20/2023/QH15.</li>
          </ul>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Điều 02.</b> Quy trình giao kết hợp đồng đặt hàng trực tuyến</h2>
          <p>Tuân thủ quy định về hợp đồng điện tử trong giao dịch từ xa tại Nghị định 55/2024/NĐ-CP và Nghị định 52/2013/NĐ-CP (sửa đổi bởi Nghị định 85/2021/NĐ-CP), một đơn hàng trên KIBO được xác lập qua 03 giai đoạn:</p>
          <table class="kibo-CleanTable">
            <thead>
              <tr>
                <th>Giai đoạn</th>
                <th>Thao tác của Khách hàng &amp; Hệ thống</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>1. Khởi tạo đề nghị giao kết</td>
                <td>Khách hàng chọn biến thể trọng lượng, số lượng sản phẩm và điền địa chỉ nhận hàng (theo 34 tỉnh, thành phố mới tại Nghị quyết 202/2025/QH15). Hệ thống tự động khóa đặt hàng với các mặt hàng hết tồn kho.</td>
              </tr>
              <tr>
                <td>2. Rà soát &amp; Chấp thuận</td>
                <td>Hệ thống hiển thị tóm tắt đơn hàng (tạm tính, mã giảm giá, phí vận chuyển, số tiền trích 5% vào Quỹ cứu trợ). Khách hàng chủ động tích chọn đồng ý với Điều khoản giao dịch trước khi gửi đơn.</td>
              </tr>
              <tr>
                <td>3. Xác nhận hợp đồng điện tử</td>
                <td>Ngay khi nhấn hoàn tất đặt hàng, hệ thống ghi nhận đơn hàng, cập nhật số lượng tồn kho và cấp Mã đơn hàng (KB-XXXXXX) cùng Mã tra cứu đóng góp Quỹ (QCT-XXXXXX).</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Điều 03.</b> Quy định về Mã giảm giá, Điểm tích lũy &amp; Hủy đơn</h2>
          <ul class="kibo-doc-list">
            <li><strong>Mã giảm giá (Voucher):</strong> Mỗi đơn hàng áp dụng tối đa 01 mã khuyến mãi còn hiệu lực. Các mã ưu đãi chào thành viên mới (như KIBO20, FREESHIP) được hiển thị sẵn trong trang Tài khoản và khung Thanh toán, giới hạn 01 lượt sử dụng trên mỗi tài khoản hoặc số điện thoại.</li>
            <li><strong>Điểm tích lũy thành viên:</strong> Với mỗi <strong><?= $diemMoiDong ?></strong> giá trị thanh toán của đơn hàng <em>Giao thành công</em>, tài khoản thành viên được cộng tự động 01 điểm thưởng.</li>
            <li><strong>Quyền hủy đơn hàng:</strong> Khách hàng được chủ động hủy đơn trực tiếp trên website khi đơn đang ở trạng thái <em>Chờ xác nhận</em> hoặc <em>Chờ đóng gói</em>; tồn kho và lượt sử dụng Voucher sẽ được hoàn lại ngay lập tức.</li>
          </ul>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Điều 04.</b> Cơ chế trích lập và công khai Quỹ cứu trợ <?= $quyPercent ?>%</h2>
          <p>Tuân thủ nguyên tắc minh bạch tài trợ tại Nghị định 93/2019/NĐ-CP (sửa đổi, bổ sung bởi Nghị định 136/2024/NĐ-CP), với mỗi đơn hàng giao thành công, KIBO trích trực tiếp <strong><?= $quyPercent ?>% giá trị tiền hàng (sau khi trừ khuyến mãi)</strong> từ doanh thu của cửa hàng vào Quỹ cứu trợ động vật, tuyệt đối không thu thêm bất kỳ khoản phụ phí nào từ khách hàng. Toàn bộ khoản trích được tổng hợp và giải ngân định kỳ hàng tháng kèm chứng từ Ủy nhiệm chi ngân hàng công khai tại trang <a href="index.php?action=rescue" style="color: var(--green-dark); font-weight: 700; text-decoration: underline;">Quỹ cứu trợ</a>.</p>
        </section>

      <?php else: ?>
        <div class="kibo-doc-meta">
          <span>Căn cứ: <strong>Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 (hiệu lực 01/01/2026)</strong></span>
          <span>Văn bản hướng dẫn: <strong>Nghị định số 356/2025/NĐ-CP (ban hành 31/12/2025)</strong></span>
        </div>

        <section class="kibo-doc-section">
          <h2><b>Mục 01.</b> Phân loại &amp; Phạm vi thu thập dữ liệu cá nhân</h2>
          <p>Thực hiện đúng quy định phân loại dữ liệu tại Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 và Nghị định 356/2025/NĐ-CP hướng dẫn thi hành, KIBO chỉ thu thập nhóm <strong>Dữ liệu cá nhân cơ bản</strong> phục vụ trực tiếp cho việc thực hiện đơn hàng:</p>
          <table class="kibo-CleanTable">
            <thead>
              <tr>
                <th>Nhóm thông tin</th>
                <th>Dữ liệu cá nhân cơ bản thu thập</th>
                <th>Mục đích xử lý tại KIBO</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Thông tin tài khoản</td>
                <td>Họ tên, Số điện thoại (10 số), Địa chỉ Email, Ngày sinh (không bắt buộc).</td>
                <td>Đăng nhập hệ thống, gửi mã xác thực OTP khi quên mật khẩu và cộng điểm thành viên.</td>
              </tr>
              <tr>
                <td>Thông tin giao nhận</td>
                <td>Số nhà, đường, Phường/Xã/Đặc khu, Tỉnh/Thành phố nhận hàng.</td>
                <td>Tính phí vận chuyển và cung cấp cho bưu tá liên hệ giao kiện hàng đến đúng địa chỉ.</td>
              </tr>
              <tr>
                <td>Lịch sử giao dịch</td>
                <td>Mã đơn hàng, phương thức thanh toán, ảnh chụp sản phẩm khi yêu cầu đổi trả.</td>
                <td>Tra cứu bảo hành/đổi trả trong 03 ngày và công khai mã đóng góp Quỹ cứu trợ 5%.</td>
              </tr>
            </tbody>
          </table>
          <p style="margin-top: 12px; font-size: 14px;"><strong>Cam kết không thu thập Dữ liệu cá nhân nhạy cảm &amp; giấy tờ tùy thân:</strong> Tuân thủ Luật số 91/2025/QH15, KIBO tuyệt đối không yêu cầu khách hàng cung cấp hình ảnh CCCD/giấy tờ tùy thân để xác thực tài khoản, và không lưu trữ số thẻ ngân hàng, mã CVV/CVC hay dữ liệu sinh trắc học. Mọi giao dịch chuyển khoản đều thực hiện qua mã VietQR trên ứng dụng ngân hàng của chính quý khách.</p>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Mục 02.</b> Cơ chế đồng ý chủ động &amp; Biện pháp bảo vệ thông tin</h2>
          <ul class="kibo-doc-list">
            <li><strong>Đồng ý tường minh:</strong> Hộp kiểm xác nhận đồng ý xử lý dữ liệu cá nhân tại trang Đăng ký và trang Thanh toán luôn để trống mặc định; hệ thống chỉ tiếp nhận thông tin khi khách hàng chủ động tích chọn.</li>
            <li><strong>Mã hóa mật khẩu &amp; Chống dò mật khẩu:</strong> Mật khẩu người dùng được mã hóa một chiều an toàn và tự động khóa tạm thời khi phát hiện đăng nhập sai liên tiếp nhiều lần.</li>
            <li><strong>Che giấu thông tin khi tra cứu công khai:</strong> Khi tra cứu mã vận đơn tại trang <em>Tra cứu đơn hàng</em> mà không đăng nhập tài khoản chủ đơn, hệ thống tự động ẩn một phần số điện thoại (ví dụ 0912***789) và ẩn địa chỉ chi tiết nhằm ngăn việc dò mã đơn để xem thông tin cá nhân của người khác.</li>
            <li><strong>Không mua bán hoặc chuyển giao dữ liệu trái phép:</strong> Dữ liệu người nhận chỉ được truyền cho đối tác vận chuyển để giao kiện hàng đó, không chia sẻ cho bất kỳ bên thứ ba nào phục vụ quảng cáo.</li>
          </ul>
        </section>

        <section class="kibo-doc-section">
          <h2><b>Mục 03.</b> Quyền của Chủ thể dữ liệu (Theo Luật số 91/2025/QH15 &amp; Nghị định 356/2025/NĐ-CP)</h2>
          <ul class="kibo-doc-list">
            <li>Khách hàng có toàn quyền truy cập, xem và chỉnh sửa Họ tên, Số điện thoại, Địa chỉ giao hàng, hoặc thay đổi mật khẩu bất cứ lúc nào tại trang <a href="index.php?action=account" style="color: var(--green-dark); font-weight: 700; text-decoration: underline;">Tài khoản của tôi</a>.</li>
            <li>Trường hợp muốn rút lại sự đồng ý, hạn chế xử lý hoặc yêu cầu xóa dữ liệu cá nhân khỏi hệ thống, quý khách vui lòng liên hệ Bộ phận Bảo vệ Dữ liệu Cá nhân (DPO) của KIBO qua Hotline <strong><?= htmlspecialchars(kibo_business('hotline') ?? '0772 906 756') ?></strong> hoặc email <strong><?= htmlspecialchars(kibo_business('email') ?? 'support@kibo.vn') ?></strong> để được thực hiện trong vòng 72 giờ theo quy định tại Nghị định 356/2025/NĐ-CP.</li>
          </ul>
        </section>
      <?php endif; ?>

      <div class="kibo-policy-foot">
        <span>Cập nhật theo hệ thống văn bản pháp luật hiệu lực năm 2026 - Ban vận hành KIBO Pet's Home</span>
        <a href="index.php?action=products">Tiếp tục mua sắm →</a>
      </div>
    </article>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
