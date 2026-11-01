<?php
// ====================================================================
// MODEL: ThanhToanModel.php
// Tương tác bảng thanh_toan (VietQR, COD)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class ThanhToanModel {

    public static function getByOrder($maDonHang) {
        $db = Database::getConnection();
        if ($db) {
            try {
                $stmt = $db->prepare("SELECT magiaodich as \"MaGiaoDich\", madonhang as \"MaDonHang\", 
                                             phuongthuctt as \"PhuongThucTT\", sotien as \"SoTien\", 
                                             thoigiantt as \"ThoiGianTT\", trangthaitt as \"TrangThaiTT\" 
                                      FROM thanh_toan 
                                      WHERE madonhang = :id");
                $stmt->execute([':id' => $maDonHang]);
                $row = $stmt->fetch();
                if ($row) return $row;
            } catch (Exception $e) {}
        }
        return null;
    }
}
