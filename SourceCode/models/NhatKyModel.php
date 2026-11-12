<?php
// ====================================================================
// MODEL: NhatKyModel.php
// Quản lý Nhật ký thao tác Admin (Audit Log) - Bảng nhat_ky
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/IdGen.php';

class NhatKyModel {
    public static function ghi($hanhDong, $doiTuong, $maDoiTuong = null, $chiTiet = null, $maTK = null) {
        $db = Database::getConnection();
        if (!$db) return false;

        try {
            if (!$maTK && !empty($_SESSION['admin_user']['matk'])) {
                $maTK = $_SESSION['admin_user']['matk'];
            }
            $maNhatKy = IdGen::next($db, 'nhat_ky', 'manhatky', 'NK', 4);

            $sql = "INSERT INTO nhat_ky (manhatky, matk, hanhdong, doituong, madoituong, chitiet, thoigian)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                $maNhatKy,
                $maTK,
                $hanhDong,
                $doiTuong,
                $maDoiTuong,
                $chiTiet
            ]);
        } catch (Exception $e) {
            error_log("Lỗi ghi nhật ký hệ thống: " . $e->getMessage());
            return false;
        }
    }

    public static function layDanhSach($limit = 100) {
        $db = Database::getConnection();
        if (!$db) return [];

        try {
            $sql = "SELECT nk.*, tk.tendangnhap 
                    FROM nhat_ky nk
                    LEFT JOIN tai_khoan tk ON nk.matk = tk.matk
                    ORDER BY nk.thoigian DESC LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Lỗi lấy danh sách nhật ký: " . $e->getMessage());
            return [];
        }
    }
}
