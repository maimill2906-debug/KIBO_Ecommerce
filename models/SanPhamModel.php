<?php
// ====================================================================
// MODEL: SanPhamModel.php
// Quản lý dữ liệu sản phẩm - Tương tác trực tiếp Supabase PostgreSQL
// Bảng: san_pham, danh_muc, bien_the
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/IdGen.php';

class SanPhamModel {

    private static $mock = [
        [
            'id' => 'SP001',
            'name' => 'KIBO Cá Hồi & Gạo Lứt',
            'category' => 'Thức Ăn Hạt',
            'category_slug' => 'thuc-an-hat',
            'brand' => 'KIBO Original',
            'brand_slug' => 'kibo-original',
            'specs' => 'Túi 1.5kg • Cho mèo trưởng thành',
            'price' => 285000,
            'originalPrice' => 320000,
            'badge' => 'MỚI',
            'badge_type' => 'dark',
            'image' => 'assets/images/products/sp_kibo_cahoi.png',
            'desc' => 'Công thức cá hồi Na Uy tươi kết hợp gạo lứt giàu chất xơ, hỗ trợ tim mạch, sáng mắt và mượt lông cho mèo trưởng thành.',
            'status' => 'Còn hàng',
            'is_sale' => true
        ],
        [
            'id' => 'SP002',
            'name' => 'Royal Canin Kitten 36',
            'category' => 'Thức Ăn Hạt',
            'category_slug' => 'thuc-an-hat',
            'brand' => 'Royal Canin',
            'brand_slug' => 'royal-canin',
            'specs' => 'Túi 2kg • Mèo con dưới 12 tháng',
            'price' => 445000,
            'originalPrice' => 445000,
            'badge' => '',
            'badge_type' => '',
            'image' => 'assets/images/products/sp_royal_canin_kitten.png',
            'desc' => 'Dinh dưỡng cân bằng tăng cường hệ miễn dịch tự nhiên, hỗ trợ hệ tiêu hóa nhạy cảm của mèo con giai đoạn cai sữa.',
            'status' => 'Còn hàng',
            'is_sale' => false
        ],
        [
            'id' => 'SP003',
            'name' => 'KIBO Pate Cá Ngừ Tươi',
            'category' => 'Pate & Ướt',
            'category_slug' => 'pate-uot',
            'brand' => 'KIBO Original',
            'brand_slug' => 'kibo-original',
            'specs' => 'Hộp 85g • Pack 12 hộp',
            'price' => 170000,
            'originalPrice' => 200000,
            'badge' => '-15%',
            'badge_type' => 'sale',
            'image' => 'assets/images/products/sp_kibo_pate_cangu.png',
            'desc' => 'Pate cá ngừ đại dương tươi cắt lát hấp tự nhiên, bổ sung nước và Taurine thiết yếu cho boss kén ăn.',
            'status' => 'Còn hàng',
            'is_sale' => true
        ],
        [
            'id' => 'SP004',
            'name' => 'Cát Cature Smart Pellet 7L',
            'category' => 'Cát & Phụ Kiện',
            'category_slug' => 'cat-phu-kien',
            'brand' => 'Cature',
            'brand_slug' => 'cature',
            'specs' => 'Túi 7L (2.8kg) • Gỗ thông',
            'price' => 185000,
            'originalPrice' => 210000,
            'badge' => 'HOT',
            'badge_type' => 'sale',
            'image' => 'assets/images/products/sp_cature_pellet.png',
            'desc' => 'Cát gỗ thông tự nhiên kiểm soát mùi 7 ngày, vón siêu nhanh, phân hủy sinh học và xả được bồn cầu.',
            'status' => 'Còn hàng',
            'is_sale' => true
        ],
        [
            'id' => 'SP005',
            'name' => "Hill's Science Diet Adult",
            'category' => 'Thức Ăn Hạt',
            'category_slug' => 'thuc-an-hat',
            'brand' => "Hill's Science",
            'brand_slug' => 'hills-science',
            'specs' => 'Túi 3kg • Công thức Hairball',
            'price' => 620000,
            'originalPrice' => 680000,
            'badge' => '',
            'badge_type' => '',
            'image' => 'assets/images/products/sp_hills_science.png',
            'desc' => 'Chế độ ăn khoa học giúp hạn chế búi lông dạ dày, hỗ trợ tiêu hóa êm dịu và tăng cường sức khỏe da lông.',
            'status' => 'Còn hàng',
            'is_sale' => true
        ],
        [
            'id' => 'SP006',
            'name' => 'Whiskas Gà Nước Sốt',
            'category' => 'Pate & Ướt',
            'category_slug' => 'pate-uot',
            'brand' => 'Whiskas',
            'brand_slug' => 'whiskas',
            'specs' => 'Túi 85g • Pack 24 túi',
            'price' => 216000,
            'originalPrice' => 240000,
            'badge' => '-10%',
            'badge_type' => 'sale',
            'image' => 'assets/images/products/sp_whiskas_ga.png',
            'desc' => 'Miếng thịt gà mềm ngập trong nước sốt thịt đậm đà, kích thích vị giác và bổ sung canxi cho mèo cưng năng động.',
            'status' => 'Còn hàng',
            'is_sale' => true
        ],
        [
            'id' => 'SP007',
            'name' => 'KIBO Thanh Nhai Sạch Răng',
            'category' => 'Bánh Thưởng',
            'category_slug' => 'banh-thuong',
            'brand' => 'KIBO Original',
            'brand_slug' => 'kibo-original',
            'specs' => 'Hộp 50g • Vị bạc hà & gà',
            'price' => 89000,
            'originalPrice' => 89000,
            'badge' => 'MỚI',
            'badge_type' => 'dark',
            'image' => 'assets/images/products/sp_kibo_thanh_nhai.png',
            'desc' => 'Thanh gặm nha khoa thiết kế rãnh 360 độ giúp giảm mảng bám, chống cao răng và lưu hương bạc hà thơm tho.',
            'status' => 'Còn hàng',
            'is_sale' => false
        ],
        [
            'id' => 'SP008',
            'name' => 'Purina Pro Plan Senior 7+',
            'category' => 'Thức Ăn Hạt',
            'category_slug' => 'thuc-an-hat',
            'brand' => 'Purina',
            'brand_slug' => 'purina',
            'specs' => 'Túi 1.5kg • Mèo cao tuổi',
            'price' => 395000,
            'originalPrice' => 430000,
            'badge' => '',
            'badge_type' => '',
            'image' => 'assets/images/products/sp_purina_pro_plan.png',
            'desc' => 'Công thức Longevis độc quyền kéo dài tuổi thọ và duy trì độ linh hoạt xương khớp cho mèo từ 7 tuổi trở lên.',
            'status' => 'Còn hàng',
            'is_sale' => true
        ]
    ];

    public static function createSlug($str) {
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
        $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
        $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
        $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
        $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
        $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
        $str = preg_replace('/(đ)/', 'd', $str);
        $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
        $str = preg_replace('/([\s]+)/', '-', $str);
        return trim($str, '-');
    }

    public static function getAllForView() {
        $db = Database::getConnection();
        if (!$db) return [];

        try {
            // 1. Lấy toàn bộ biến thể kèm giá bán, giá gốc và tồn kho
            $sqlBT = "SELECT bt.mabienthe, bt.masp, bt.tenbienthe, bt.giaban, bt.giagoc, bt.soluongton, bt.hinhanhbienthe 
                      FROM bien_the bt ORDER BY bt.giaban ASC, bt.mabienthe ASC";
            $allBT = $db->query($sqlBT)->fetchAll();
            $variantsBySp = [];
            foreach ($allBT as $bt) {
                $variantsBySp[$bt['masp']][] = [
                    'mabienthe' => $bt['mabienthe'],
                    'tenbienthe' => $bt['tenbienthe'],
                    'giaban' => (float)$bt['giaban'],
                    'giagoc' => $bt['giagoc'] !== null ? (float)$bt['giagoc'] : null,
                    'soluongton' => (int)$bt['soluongton'],
                    'hinhanhbienthe' => $bt['hinhanhbienthe']
                ];
            }

            // 2. Lấy sản phẩm kèm danh mục
            $sql = "SELECT 
                        sp.masp,
                        sp.tensp,
                        sp.thuonghieu,
                        sp.motachitiet,
                        sp.hinhanhchinh,
                        sp.trangthai,
                        dm.madanhmuc,
                        dm.tendanhmuc
                    FROM san_pham sp
                    LEFT JOIN danh_muc dm ON sp.madanhmuc = dm.madanhmuc
                    ORDER BY sp.masp ASC";
            $rows = $db->query($sql)->fetchAll();

            $results = [];
            foreach ($rows as $r) {
                $spId = $r['masp'];
                $spVariants = $variantsBySp[$spId] ?? [];
                $minVariant = !empty($spVariants) ? $spVariants[0] : null;

                $price = $minVariant ? $minVariant['giaban'] : 0.0;
                $origPrice = ($minVariant && $minVariant['giagoc'] !== null && $minVariant['giagoc'] > $price) ? $minVariant['giagoc'] : null;
                $hasSale = ($origPrice !== null && $origPrice > $price);
                $badge = '';
                $badgeType = '';
                if ($hasSale) {
                    $pct = round((($origPrice - $price) / $origPrice) * 100);
                    $badge = "-{$pct}%";
                    $badgeType = 'sale';
                }

                $totalStock = array_reduce($spVariants, function($carry, $item) { return $carry + $item['soluongton']; }, 0);
                $statusText = ($r['trangthai'] === 'Đang bán' && $totalStock > 0) ? 'Còn hàng' : 'Hết hàng';

                $results[] = [
                    'id' => $spId,
                    'variant_id' => $minVariant ? $minVariant['mabienthe'] : 'BT001',
                    'variants' => $spVariants,
                    'name' => $r['tensp'],
                    'category' => $r['tendanhmuc'] ?? 'Thức Ăn Hạt',
                    'category_slug' => self::createSlug($r['tendanhmuc'] ?? 'thuc-an-hat'),
                    'brand' => $r['thuonghieu'] ?? 'KIBO Original',
                    'brand_slug' => self::createSlug($r['thuonghieu'] ?? 'kibo-original'),
                    'specs' => $minVariant ? $minVariant['tenbienthe'] : 'Tiêu chuẩn',
                    'price' => $price,
                    'originalPrice' => $origPrice,
                    'badge' => $badge,
                    'badge_type' => $badgeType,
                    'image' => $r['hinhanhchinh'] ?: 'assets/images/products/sp_kibo_cahoi.png',
                    'desc' => $r['motachitiet'] ?: '',
                    'status' => $statusText,
                    'is_sale' => $hasSale
                ];
            }
            return $results;
        } catch (Exception $e) {
            error_log("Lỗi getAllForView: " . $e->getMessage());
            return [];
        }
    }

    public static function getFeaturedProducts($limit = 3) {
        $all = self::getAllForView();
        return array_slice($all, 0, $limit);
    }

    public static function getFilterStats() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $catsSql = "SELECT dm.tendanhmuc as name, COUNT(sp.masp) as count 
                            FROM danh_muc dm 
                            LEFT JOIN san_pham sp ON dm.madanhmuc = sp.madanhmuc 
                            GROUP BY dm.madanhmuc, dm.tendanhmuc 
                            ORDER BY count DESC";
                $catRows = $db->query($catsSql)->fetchAll();

                $categories = [];
                foreach ($catRows as $c) {
                    $categories[] = [
                        'name' => $c['name'],
                        'slug' => self::createSlug($c['name']),
                        'count' => (int)$c['count']
                    ];
                }

                $brandsSql = "SELECT thuonghieu as name, COUNT(masp) as count 
                              FROM san_pham 
                              WHERE thuonghieu IS NOT NULL AND thuonghieu != '' 
                              GROUP BY thuonghieu 
                              ORDER BY count DESC";
                $brandRows = $db->query($brandsSql)->fetchAll();

                $brands = [];
                foreach ($brandRows as $b) {
                    $brands[] = [
                        'name' => $b['name'],
                        'slug' => self::createSlug($b['name']),
                        'count' => (int)$b['count']
                    ];
                }

                if (!empty($categories) && !empty($brands)) {
                    return [
                        'categories' => $categories,
                        'brands' => $brands,
                        'price' => ['min' => 50000, 'max' => 850000],
                        'statuses' => [
                            ['name' => 'Tất cả',        'value' => 'all'],
                            ['name' => 'Còn hàng',      'value' => 'in_stock'],
                            ['name' => 'Sắp về hàng',   'value' => 'upcoming'],
                            ['name' => 'Đang giảm giá', 'value' => 'on_sale']
                        ]
                    ];
                }
            } catch (Exception $e) {}
        }

        return [
            'categories' => [
                ['name' => 'Thức Ăn Hạt', 'slug' => 'thuc-an-hat', 'count' => 42],
                ['name' => 'Pate & Ướt',   'slug' => 'pate-uot',     'count' => 28],
                ['name' => 'Bánh Thưởng', 'slug' => 'banh-thuong',  'count' => 19],
                ['name' => 'Cát & Phụ Kiện', 'slug' => 'cat-phu-kien', 'count' => 35],
            ],
            'brands' => [
                ['name' => 'KIBO Original',  'slug' => 'kibo-original',  'count' => 24],
                ['name' => 'Royal Canin',    'slug' => 'royal-canin',    'count' => 18],
                ['name' => 'Whiskas',        'slug' => 'whiskas',        'count' => 12],
                ['name' => "Hill's Science", 'slug' => 'hills-science',  'count' => 9],
                ['name' => 'Purina',         'slug' => 'purina',         'count' => 15],
            ],
            'price' => [
                'min' => 50000,
                'max' => 850000
            ],
            'statuses' => [
                ['name' => 'Tất cả',         'value' => 'all'],
                ['name' => 'Còn hàng',       'value' => 'in_stock'],
                ['name' => 'Sắp về hàng',    'value' => 'upcoming'],
                ['name' => 'Đang giảm giá',  'value' => 'on_sale'],
            ]
        ];
    }

    public static function getDanhSachDanhMuc() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $rows = $db->query("SELECT madanhmuc, tendanhmuc, mota, trangthai FROM danh_muc ORDER BY madanhmuc ASC")->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {}
        }
        return [
            ['madanhmuc' => 'DM01', 'tendanhmuc' => 'Thức Ăn Hạt', 'mota' => 'Thức ăn hạt dinh dưỡng', 'trangthai' => 'Hoạt động'],
            ['madanhmuc' => 'DM02', 'tendanhmuc' => 'Pate & Ướt', 'mota' => 'Pate lon & gói thơm ngậy', 'trangthai' => 'Hoạt động'],
            ['madanhmuc' => 'DM03', 'tendanhmuc' => 'Bánh Thưởng', 'mota' => 'Bánh snack & súp thưởng', 'trangthai' => 'Hoạt động'],
            ['madanhmuc' => 'DM04', 'tendanhmuc' => 'Cát & Phụ Kiện', 'mota' => 'Cát vệ sinh và đồ chơi', 'trangthai' => 'Hoạt động']
        ];
    }

    public static function themDanhMuc($tenDanhMuc, $moTa = '') {
        $tenDanhMuc = trim($tenDanhMuc);
        if (empty($tenDanhMuc)) {
            return ['success' => false, 'error' => 'Tên danh mục không được để trống'];
        }

        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'error' => 'Chưa kết nối CSDL'];

        try {
            $maDM = IdGen::next($db, 'danh_muc', 'madanhmuc', 'DM', 2);

            $sql = "INSERT INTO danh_muc (madanhmuc, tendanhmuc, mota, trangthai) VALUES (?, ?, ?, 'Hoạt động')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$maDM, $tenDanhMuc, $moTa ?: 'Danh mục ' . $tenDanhMuc]);

            return [
                'success' => true,
                'madanhmuc' => $maDM,
                'tendanhmuc' => $tenDanhMuc
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCategories() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $rows = $db->query("SELECT tendanhmuc FROM danh_muc WHERE trangthai = 'Hoạt động' ORDER BY madanhmuc ASC")->fetchAll();
                if (!empty($rows)) {
                    return array_column($rows, 'tendanhmuc');
                }
            } catch (Exception $e) {}
        }
        return ['Thức Ăn Hạt', 'Pate & Ướt', 'Bánh Thưởng', 'Cát & Phụ Kiện'];
    }

    public static function getAllProductsWithVariants() {
        return self::getAllForView();
    }

    public static function getProductById($maSP) {
        $all = self::getAllForView();
        foreach ($all as $p) {
            if ($p['id'] === $maSP) {
                return $p;
            }
        }
        return null;
    }

    public static function themSanPham($data) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'error' => 'Chưa kết nối CSDL'];

        try {
            $db->beginTransaction();

            $maSP = IdGen::next($db, 'san_pham', 'masp', 'SP', 3);
            $maDM = $data['madanhmuc'] ?? 'DM01';
            $tenSP = trim($data['tensp'] ?? 'Sản phẩm mới');
            $thuongHieu = trim($data['thuonghieu'] ?? 'KIBO Original');
            $mota = trim($data['motachitiet'] ?? 'Sản phẩm dinh dưỡng và chăm sóc thú cưng cao cấp.');
            $hinhAnh = $data['hinhanhchinh'] ?: 'assets/images/products/sp_kibo_cahoi.png';
            $trangThai = 'Đang bán';

            $sqlSP = "INSERT INTO san_pham (masp, madanhmuc, tensp, thuonghieu, motachitiet, hinhanhchinh, trangthai)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtSP = $db->prepare($sqlSP);
            $stmtSP->execute([$maSP, $maDM, $tenSP, $thuongHieu, $mota, $hinhAnh, $trangThai]);

            // Sinh mã biến thể
            $maBT = IdGen::next($db, 'bien_the', 'mabienthe', 'BT', 3);
            $tenBT = trim($data['tenbienthe'] ?: 'Túi tiêu chuẩn');
            $giaBan = max(1000, (float)($data['giaban'] ?? 150000));
            $tonKho = max(0, (int)($data['soluongton'] ?? 50));

            $sqlBT = "INSERT INTO bien_the (mabienthe, masp, tenbienthe, giaban, soluongton, hinhanhbienthe)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmtBT = $db->prepare($sqlBT);
            $stmtBT->execute([$maBT, $maSP, $tenBT, $giaBan, $tonKho, $hinhAnh]);

            $db->commit();
            return [
                'success' => true,
                'masp' => $maSP,
                'tensp' => $tenSP,
                'mabienthe' => $maBT,
                'giaban' => $giaBan,
                'soluongton' => $tonKho
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
