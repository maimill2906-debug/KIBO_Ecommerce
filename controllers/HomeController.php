<?php
// ====================================================================
// CONTROLLER: HomeController.php
// Lấy danh sách sản phẩm nổi bật + thống kê Quỹ 5% từ Model,
// truyền trực tiếp sang View (HomeView.php) để render HTML thuần túy (SSR)
// ====================================================================

require_once __DIR__ . '/../models/SanPhamModel.php';
require_once __DIR__ . '/../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../models/DonHangModel.php';

class HomeController {
    public function index() {
        // Lấy toàn bộ sản phẩm đang bán & 3 sản phẩm nổi bật cho Featured Products Grid
        $productsJs       = SanPhamModel::getAllForView(true);
        $featuredProducts = SanPhamModel::getFeaturedProducts(3);

        // Lấy số liệu minh bạch Quỹ Cứu Trợ 5%
        $fundInfo         = QuyCuuTroModel::getFundSummary();

        // Tra cứu đơn hàng thuần PHP nếu người dùng gửi mã qua GET
        $lookupCode       = isset($_GET['order_code']) ? trim($_GET['order_code']) : '';
        $lookupResult     = $lookupCode !== '' ? QuyCuuTroModel::lookup($lookupCode) : null;

        // 5 liên kết điều hướng chính trên Header & Footer
        $navLinks = [
            ['id' => 'home',     'label' => 'Trang Chủ',       'href' => 'index.php?action=home',     'active' => true],
            ['id' => 'about',    'label' => 'Về KIBO',         'href' => 'index.php?action=home#about',    'active' => false],
            ['id' => 'products', 'label' => 'Sản Phẩm',        'href' => 'index.php?action=products', 'active' => false],
            ['id' => 'rescue',   'label' => 'Quỹ Cứu Trợ 5%',  'href' => 'index.php?action=home#rescue',   'active' => false],
            ['id' => 'lookup',   'label' => 'Tra Cứu Đơn',     'href' => 'index.php?action=home#lookup',   'active' => false],
        ];

        // 3 hộp thống kê cam kết thương hiệu trong phần About
        $brandPromises = [
            [
                'value' => '5% Mỗi Đơn Hàng',
                'title' => 'Đóng Góp Quỹ Cứu Trợ',
                'desc'  => 'Mỗi đơn hàng hoàn tất tự động trích 5% giá trị vào quỹ lương thực và y tế cho các trạm cứu hộ chó mèo.'
            ],
            [
                'value' => '100% Chính Hãng',
                'title' => 'Cam Kết Xuất Xứ Minh Bạch',
                'desc'  => 'Phân phối trực tiếp từ các thương hiệu dinh dưỡng thú cưng uy tín toàn cầu với đầy đủ tem kiểm định.'
            ],
            [
                'value' => 'Đổi Trả 3 Ngày',
                'title' => 'Chính Sách Đổi Trả Linh Hoạt',
                'desc'  => 'Hỗ trợ đổi trả nhanh chóng trong vòng 3 ngày nếu bao bì hư hỏng hoặc sản phẩm không đúng cam kết.'
            ]
        ];

        // Thông tin liên hệ thương hiệu ở Footer & Header
        $contactInfo = [
            'address'  => '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh',
            'phone'    => '0772 906 756',
            'phoneRaw' => '0772906756',
            'email'    => 'cskh@kibo.vn',
            'socials'  => [
                ['name' => 'Facebook',  'icon' => 'fa-brands fa-facebook-f', 'url' => 'https://facebook.com'],
                ['name' => 'Instagram', 'icon' => 'fa-brands fa-instagram',  'url' => 'https://instagram.com'],
                ['name' => 'TikTok',    'icon' => 'fa-brands fa-tiktok',     'url' => 'https://tiktok.com'],
            ]
        ];

        require __DIR__ . '/../views/customer/HomeView.php';
    }
}
