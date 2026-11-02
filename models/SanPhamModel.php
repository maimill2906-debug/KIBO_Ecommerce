<?php
// ====================================================================
// MODEL: SanPhamModel.php
// Quản lý dữ liệu sản phẩm - Tương tác trực tiếp Supabase PostgreSQL
// Bảng: san_pham, danh_muc, bien_the
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/IdGen.php';

class SanPhamModel {

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

    public static function getInventoryWithSales() {
        $db = Database::getConnection();
        if (!$db) return [];
        try {
            $sql = "SELECT 
                        sp.masp AS id,
                        sp.tensp AS name,
                        COALESCE(dm.tendanhmuc, 'Thú cưng') AS category,
                        COALESCE(SUM(bt.soluongton), 0) AS stock,
                        COALESCE(sales.sold_count, 0) AS sold
                    FROM san_pham sp
                    LEFT JOIN danh_muc dm ON sp.madanhmuc = dm.madanhmuc
                    LEFT JOIN bien_the bt ON sp.masp = bt.masp
                    LEFT JOIN (
                        SELECT bt2.masp, SUM(ct.soluong) AS sold_count
                        FROM chi_tiet_don_hang ct
                        JOIN bien_the bt2 ON ct.mabienthe = bt2.mabienthe
                        GROUP BY bt2.masp
                    ) sales ON sp.masp = sales.masp
                    GROUP BY sp.masp, sp.tensp, dm.tendanhmuc, sales.sold_count
                    ORDER BY sp.masp ASC";
            $rows = $db->query($sql)->fetchAll();
            $inventory = [];
            foreach ($rows as $r) {
                $inventory[] = [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'sku' => 'KBO-' . $r['id'],
                    'category' => $r['category'],
                    'sold' => (int)$r['sold'],
                    'stock' => (int)$r['stock'],
                    'color' => '#c94336',
                    'art' => 'royal'
                ];
            }
            return $inventory;
        } catch (Exception $e) {
            error_log("Lỗi getInventoryWithSales: " . $e->getMessage());
            return [];
        }
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
            } catch (Exception $e) {
                error_log("Lỗi getFilterStats: " . $e->getMessage());
            }
        }

        return [
            'categories' => [],
            'brands' => [],
            'price' => ['min' => 0, 'max' => 0],
            'statuses' => [
                ['name' => 'Tất cả',        'value' => 'all'],
                ['name' => 'Còn hàng',      'value' => 'in_stock'],
                ['name' => 'Sắp về hàng',   'value' => 'upcoming'],
                ['name' => 'Đang giảm giá', 'value' => 'on_sale']
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
            } catch (Exception $e) {
                error_log("Lỗi getDanhSachDanhMuc: " . $e->getMessage());
            }
        }
        return [];
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
            } catch (Exception $e) {
                error_log("Lỗi getCategories SanPhamModel: " . $e->getMessage());
            }
        }
        return [];
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
