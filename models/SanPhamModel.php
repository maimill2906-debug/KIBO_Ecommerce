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

    public static function getAllForView($onlyActive = true) {
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

            // 2. Lấy sản phẩm kèm danh mục (cho phép lọc chỉ Đang bán hoặc tất cả)
            $whereClause = $onlyActive ? "WHERE sp.trangthai = 'Đang bán'" : "";
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
                    {$whereClause}
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
                if ($r['trangthai'] === 'Ngừng bán') {
                    $statusText = 'Ngừng bán';
                } else {
                    $statusText = ($totalStock > 0) ? 'Còn hàng' : 'Hết hàng';
                }

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
                    'trangthai' => $r['trangthai'],
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
        $all = self::getAllForView(true);
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
            // Chặn tạo trùng tên danh mục
            $chkName = $db->prepare("SELECT madanhmuc FROM danh_muc WHERE LOWER(TRIM(tendanhmuc)) = LOWER(?)");
            $chkName->execute([$tenDanhMuc]);
            if ($chkName->fetchColumn()) {
                return ['success' => false, 'error' => "Danh mục '{$tenDanhMuc}' đã tồn tại trong hệ thống."];
            }

            $maDM = IdGen::next($db, 'danh_muc', 'madanhmuc', 'DM', 2);

            $sql = "INSERT INTO danh_muc (madanhmuc, tendanhmuc, mota, trangthai) VALUES (?, ?, ?, 'Hoạt động')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$maDM, $tenDanhMuc, $moTa ?: 'Danh mục ' . $tenDanhMuc]);

            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Thêm danh mục', 'danh_muc', $maDM, "Tên danh mục: {$tenDanhMuc}");

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

    public static function getAllProductsWithVariants($onlyActive = false) {
        return self::getAllForView($onlyActive);
    }

    public static function getProductById($maSP) {
        $all = self::getAllForView(false);
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

        $tenSP = trim($data['tensp'] ?? '');
        if (empty($tenSP)) {
            return ['success' => false, 'error' => 'Tên sản phẩm không được để trống.'];
        }

        if (!isset($data['giaban']) || !is_numeric($data['giaban']) || (float)$data['giaban'] <= 0) {
            return ['success' => false, 'error' => 'Giá bán phải là số hợp lệ và lớn hơn 0đ.'];
        }
        $giaBan = (float)$data['giaban'];

        $maDM = trim($data['madanhmuc'] ?? 'DM01');
        if (empty($maDM)) {
            return ['success' => false, 'error' => 'Vui lòng chọn danh mục cho sản phẩm.'];
        }

        $chkDM = $db->prepare("SELECT madanhmuc FROM danh_muc WHERE madanhmuc = ?");
        $chkDM->execute([$maDM]);
        if (!$chkDM->fetchColumn()) {
            return ['success' => false, 'error' => "Danh mục '{$maDM}' không tồn tại trong hệ thống."];
        }

        $tonKho = isset($data['soluongton']) ? (int)$data['soluongton'] : 50;
        if ($tonKho < 0) {
            return ['success' => false, 'error' => 'Số lượng tồn kho không được âm.'];
        }

        try {
            $db->beginTransaction();

            $maSP = IdGen::next($db, 'san_pham', 'masp', 'SP', 3);
            $thuongHieu = trim($data['thuonghieu'] ?? 'KIBO Original');
            $mota = trim($data['motachitiet'] ?? 'Sản phẩm dinh dưỡng và chăm sóc thú cưng cao cấp.');
            $hinhAnh = trim($data['hinhanhchinh'] ?? '') ?: 'assets/images/products/sp_kibo_cahoi.png';
            $trangThai = 'Đang bán';

            $sqlSP = "INSERT INTO san_pham (masp, madanhmuc, tensp, thuonghieu, motachitiet, hinhanhchinh, trangthai)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtSP = $db->prepare($sqlSP);
            $stmtSP->execute([$maSP, $maDM, $tenSP, $thuongHieu, $mota, $hinhAnh, $trangThai]);

            // Sinh mã biến thể
            $maBT = IdGen::next($db, 'bien_the', 'mabienthe', 'BT', 3);
            $tenBT = trim($data['tenbienthe'] ?? '') ?: 'Túi tiêu chuẩn';

            $sqlBT = "INSERT INTO bien_the (mabienthe, masp, tenbienthe, giaban, soluongton, hinhanhbienthe)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmtBT = $db->prepare($sqlBT);
            $stmtBT->execute([$maBT, $maSP, $tenBT, $giaBan, $tonKho, $hinhAnh]);

            $db->commit();

            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Thêm sản phẩm mới', 'san_pham', $maSP, "Tên: {$tenSP}, Giá: " . number_format($giaBan, 0, ',', '.') . "đ, Tồn: {$tonKho}");

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

    public static function suaSanPham($maSP, $data) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'error' => 'Chưa kết nối CSDL'];

        $maSP = trim((string)$maSP);
        $tenSP = trim($data['tensp'] ?? '');
        if (empty($tenSP)) {
            return ['success' => false, 'error' => 'Tên sản phẩm không được để trống.'];
        }
        if (isset($data['giaban']) && ((float)$data['giaban'] <= 0 || !is_numeric($data['giaban']))) {
            return ['success' => false, 'error' => 'Giá bán phải là số hợp lệ và lớn hơn 0đ.'];
        }

        try {
            $db->beginTransaction();

            $chkSP = $db->prepare("SELECT masp FROM san_pham WHERE masp = ?");
            $chkSP->execute([$maSP]);
            if (!$chkSP->fetchColumn()) {
                throw new Exception("Sản phẩm '{$maSP}' không tồn tại.");
            }

            $maDM = trim($data['madanhmuc'] ?? '');
            if (!empty($maDM)) {
                $chkDM = $db->prepare("SELECT madanhmuc FROM danh_muc WHERE madanhmuc = ?");
                $chkDM->execute([$maDM]);
                if (!$chkDM->fetchColumn()) {
                    throw new Exception("Danh mục '{$maDM}' không tồn tại.");
                }
            }

            $thuongHieu = trim($data['thuonghieu'] ?? 'KIBO Original');
            $mota = trim($data['motachitiet'] ?? '');
            $hinhAnh = trim($data['hinhanhchinh'] ?? '');

            $sqlSP = "UPDATE san_pham SET tensp = ?, thuonghieu = ?, motachitiet = ?" . 
                     (!empty($maDM) ? ", madanhmuc = ?" : "") . 
                     (!empty($hinhAnh) ? ", hinhanhchinh = ?" : "") . 
                     " WHERE masp = ?";
            $paramsSP = [$tenSP, $thuongHieu, $mota];
            if (!empty($maDM)) $paramsSP[] = $maDM;
            if (!empty($hinhAnh)) $paramsSP[] = $hinhAnh;
            $paramsSP[] = $maSP;

            $stmtSP = $db->prepare($sqlSP);
            $stmtSP->execute($paramsSP);

            // Cập nhật giá bán hoặc tồn kho của biến thể chính
            if (isset($data['giaban']) || isset($data['soluongton'])) {
                $updates = [];
                $paramsBT = [];
                if (isset($data['giaban'])) {
                    $updates[] = "giaban = ?";
                    $paramsBT[] = (float)$data['giaban'];
                }
                if (isset($data['soluongton'])) {
                    $updates[] = "soluongton = ?";
                    $paramsBT[] = max(0, (int)$data['soluongton']);
                }
                $sqlBT = "UPDATE bien_the SET " . implode(', ', $updates) . 
                         " WHERE masp = ? AND mabienthe = (SELECT mabienthe FROM bien_the WHERE masp = ? ORDER BY mabienthe ASC LIMIT 1)";
                $paramsBT[] = $maSP;
                $paramsBT[] = $maSP;
                $stmtBT = $db->prepare($sqlBT);
                $stmtBT->execute($paramsBT);
            }

            $db->commit();
            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Cập nhật sản phẩm', 'san_pham', $maSP, "Tên: {$tenSP}");
            return ['success' => true, 'message' => "Cập nhật sản phẩm {$maSP} thành công."];
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function doiTrangThaiSanPham($maSP, $trangThai = null) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'error' => 'Chưa kết nối CSDL'];

        try {
            $chk = $db->prepare("SELECT trangthai FROM san_pham WHERE masp = ?");
            $chk->execute([$maSP]);
            $current = $chk->fetchColumn();
            if ($current === false) {
                return ['success' => false, 'error' => "Sản phẩm '{$maSP}' không tồn tại."];
            }

            if ($trangThai !== null) {
                $newStatus = ($trangThai === 'Ngừng bán') ? 'Ngừng kinh doanh' : $trangThai;
            } else {
                $newStatus = ($current === 'Đang bán') ? 'Ngừng kinh doanh' : 'Đang bán';
            }

            if (!in_array($newStatus, ['Đang bán', 'Tạm hết hàng', 'Ngừng kinh doanh', 'Ẩn'])) {
                $newStatus = 'Đang bán';
            }

            $stmt = $db->prepare("UPDATE san_pham SET trangthai = ? WHERE masp = ?");
            $stmt->execute([$newStatus, $maSP]);

            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Đổi trạng thái sản phẩm', 'san_pham', $maSP, "Trạng thái mới: {$newStatus}");

            return ['success' => true, 'trangthai' => $newStatus, 'message' => "Đã chuyển trạng thái sản phẩm {$maSP} sang '{$newStatus}'."];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
