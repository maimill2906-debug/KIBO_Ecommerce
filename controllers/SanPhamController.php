<?php
require_once __DIR__ . '/../models/SanPhamModel.php';

class SanPhamController {
    private $sanPhamModel;

    public function __construct() {
        $this->sanPhamModel = new SanPhamModel();
    }

    public function index() {
        $allProducts = SanPhamModel::getAllForView();
        $filterStats = SanPhamModel::getFilterStats();

        // Đọc các bộ lọc từ query string (nếu có)
        $selectedCategories = isset($_GET['cat']) ? (array)$_GET['cat'] : ['thuc-an-hat', 'banh-thuong'];
        $selectedBrands     = isset($_GET['brand']) ? (array)$_GET['brand'] : ['kibo-original'];
        $priceMin           = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 50000;
        $priceMax           = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 850000;
        $selectedStatus     = $_GET['status'] ?? 'all';
        $sortBy             = $_GET['sort'] ?? 'featured';

        // Lọc danh sách sản phẩm
        $products = array_filter($allProducts, function($p) use ($selectedCategories, $selectedBrands, $priceMin, $priceMax) {
            // Cho hiển thị mặc định theo Figma
            return true;
        });

        // 5 liên kết điều hướng đồng bộ
        $navLinks = [
            ['id' => 'home',     'label' => 'Trang chủ',       'href' => 'index.php?action=home',     'active' => false],
            ['id' => 'about',    'label' => 'Về chúng tôi',    'href' => 'index.php?action=home#about', 'active' => false],
            ['id' => 'products', 'label' => 'Sản phẩm',        'href' => 'index.php?action=products', 'active' => true],
            ['id' => 'rescue',   'label' => 'Quỹ cứu trợ',     'href' => 'index.php?action=home#rescue', 'active' => false],
            ['id' => 'lookup',   'label' => 'Tra cứu đơn hàng', 'href' => 'index.php?action=home#lookup', 'active' => false],
        ];

        $hotline = kibo_business('hotline') ?? '0772 906 756';

        require __DIR__ . '/../views/customer/SanPhamView.php';
    }

    public function listJson() {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'categories' => $this->sanPhamModel->getCategories(),
            'products' => $this->sanPhamModel->getAllProductsWithVariants()
        ], JSON_UNESCAPED_UNICODE);
    }

    public function detailJson($maSP) {
        header('Content-Type: application/json; charset=utf-8');
        $product = $this->sanPhamModel->getProductById($maSP);
        echo json_encode($product, JSON_UNESCAPED_UNICODE);
    }
}
