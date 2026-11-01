<?php
// ====================================================================
// MODEL: GioHangModel.php
// Tương tác bảng gio_hang, chi_tiet_gio_hang (Chuẩn CSDL 3NF)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class GioHangModel {
    /**
     * Đồng bộ giỏ hàng của khách hàng vào CSDL Supabase
     */
    public static function syncCart($maKH, $items = []) {
        if (empty($maKH)) return false;
        $db = Database::getConnection();
        if (!$db) return false;

        try {
            $db->beginTransaction();

            // 1. Kiểm tra hoặc tạo giỏ hàng cho khách hàng
            $stmt = $db->prepare("SELECT magiohang FROM gio_hang WHERE makh = ? LIMIT 1");
            $stmt->execute([$maKH]);
            $maGioHang = $stmt->fetchColumn();

            if (!$maGioHang) {
                // Tạo mã giỏ hàng mới GH + số
                $maxGH = $db->query("SELECT MAX(CAST(SUBSTRING(magiohang FROM 3) AS INTEGER)) FROM gio_hang WHERE magiohang ~ '^GH[0-9]+$'")->fetchColumn();
                $nextGHNum = ($maxGH ? (int)$maxGH : 0) + 1;
                $maGioHang = 'GH' . str_pad($nextGHNum, 3, '0', STR_PAD_LEFT);

                $stmtInsertGH = $db->prepare("INSERT INTO gio_hang (magiohang, makh, ngaycapnhat) VALUES (?, ?, NOW())");
                $stmtInsertGH->execute([$maGioHang, $maKH]);
            } else {
                $stmtUpdateGH = $db->prepare("UPDATE gio_hang SET ngaycapnhat = NOW() WHERE magiohang = ?");
                $stmtUpdateGH->execute([$maGioHang]);
            }

            // 2. Xóa các chi tiết giỏ hàng cũ để lưu chi tiết giỏ hàng mới nhất
            $stmtDel = $db->prepare("DELETE FROM chi_tiet_gio_hang WHERE magiohang = ?");
            $stmtDel->execute([$maGioHang]);

            // 3. Thêm các sản phẩm hiện có trong giỏ hàng vào bảng chi_tiet_gio_hang
            if (!empty($items) && is_array($items)) {
                $stmtAdd = $db->prepare("INSERT INTO chi_tiet_gio_hang (magiohang, mabienthe, soluong) VALUES (?, ?, ?)");
                foreach ($items as $item) {
                    $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);
                    if ($qty <= 0) continue;

                    $maBT = 'BT001';
                    if (!empty($item['id']) && str_starts_with($item['id'], 'BT')) {
                        $maBT = $item['id'];
                    } else {
                        // Tra cứu biến thể theo tên sản phẩm
                        $findBT = $db->prepare("SELECT bt.mabienthe FROM bien_the bt JOIN san_pham sp ON bt.masp = sp.masp WHERE sp.tensp ILIKE ? LIMIT 1");
                        $findBT->execute(['%' . ($item['name'] ?? '') . '%']);
                        $found = $findBT->fetchColumn();
                        if ($found) $maBT = $found;
                    }

                    $stmtAdd->execute([$maGioHang, $maBT, $qty]);
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Lấy giỏ hàng đã lưu trong CSDL của khách hàng
     */
    public static function getCartFromDb($maKH) {
        if (empty($maKH)) return [];
        $db = Database::getConnection();
        if (!$db) return [];

        try {
            $sql = "SELECT bt.mabienthe as id, sp.tensp as name, bt.tenbienthe as variant, 
                           bt.giaban as price, ct.soluong as quantity, sp.hinhanhchinh as art
                    FROM gio_hang gh
                    JOIN chi_tiet_gio_hang ct ON gh.magiohang = ct.magiohang
                    JOIN bien_the bt ON ct.mabienthe = bt.mabienthe
                    JOIN san_pham sp ON bt.masp = sp.masp
                    WHERE gh.makh = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$maKH]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Dọn giỏ hàng sau khi đặt hàng thành công
     */
    public static function clearCartInDb($maKH) {
        if (empty($maKH)) return;
        $db = Database::getConnection();
        if (!$db) return;

        try {
            $stmt = $db->prepare("SELECT magiohang FROM gio_hang WHERE makh = ?");
            $stmt->execute([$maKH]);
            $maGH = $stmt->fetchColumn();
            if ($maGH) {
                $db->prepare("DELETE FROM chi_tiet_gio_hang WHERE magiohang = ?")->execute([$maGH]);
            }
        } catch (Exception $e) {}
    }

    public static function getCartItems() {
        if (!isset($_SESSION['kibo_cart'])) {
            $_SESSION['kibo_cart'] = [];
        }
        return $_SESSION['kibo_cart'];
    }

    public static function clearCart() {
        $_SESSION['kibo_cart'] = [];
    }
}
