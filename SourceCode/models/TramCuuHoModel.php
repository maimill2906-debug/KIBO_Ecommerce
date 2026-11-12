<?php
// ====================================================================
// MODEL: TramCuuHoModel.php
// Tương tác bảng tram_cuu_ho, chung_tu_giai_ngan (Minh bạch ngày 30 hàng tháng)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class TramCuuHoModel {

    public static function getShelters() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT matram as \"MaTram\", tentram as \"TenTram\", nguoidaidien as \"NguoiDaiDien\", 
                               sodienthoai as \"SoDienThoai\", diachi as \"DiaChi\", khuvuc as \"KhuVuc\", 
                               trangthaihoptac as \"TrangThaiHopTac\",
                               COALESCE(sotaikhoan, '0772906756') as \"SoTaiKhoan\",
                               COALESCE(nganhang, 'MB Bank (Ngân hàng TMCP Quân Đội)') as \"NganHang\"
                        FROM tram_cuu_ho 
                        ORDER BY matram ASC";
                $rows = $db->query($sql)->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {
                error_log("Lỗi getAll TramCuuHoModel: " . $e->getMessage());
            }
        }
        return [];
    }

    public static function getDisbursements() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT ct.machungtu as \"MaChungTu\", ct.matram as \"MaTram\", t.tentram as \"TenTram\", 
                               ct.sotiengiaingan as \"SoTienGiaiNgan\", ct.ngaychuyenkhoan as \"NgayChuyenKhoan\", 
                               ct.mucdichsudung as \"MucDichSuDung\", ct.filehoadonchungtu as \"FileHoaDonChungTu\" 
                        FROM chung_tu_giai_ngan ct 
                        JOIN tram_cuu_ho t ON ct.matram = t.matram 
                        ORDER BY ct.ngaychuyenkhoan DESC";
                $rows = $db->query($sql)->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {
                error_log("Lỗi getDisbursements TramCuuHoModel: " . $e->getMessage());
            }
        }
        return [];
    }
}
