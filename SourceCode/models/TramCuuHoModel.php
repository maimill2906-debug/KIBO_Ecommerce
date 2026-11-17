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

    public static function themTram($data) {
        $db = Database::getConnection();
        if (!$db) {
            return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.'];
        }

        $tenTram = trim((string)($data['tentram'] ?? ''));
        $nguoiDaiDien = trim((string)($data['nguoidaidien'] ?? ''));
        $sdt = trim((string)($data['sodienthoai'] ?? ''));
        $khuVuc = trim((string)($data['khuvuc'] ?? 'TP.HCM'));
        $diaChi = trim((string)($data['diachi'] ?? ''));
        $soTaiKhoan = trim((string)($data['sotaikhoan'] ?? ''));
        $nganHang = trim((string)($data['nganhang'] ?? ''));

        if ($tenTram === '' || $soTaiKhoan === '' || $nganHang === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ Tên trạm, Số tài khoản và Ngân hàng.'];
        }

        try {
            $db->beginTransaction();
            require_once __DIR__ . '/IdGen.php';
            $maTram = IdGen::next($db, 'tram_cuu_ho', 'matram', 'TRM', 3);

            $stmt = $db->prepare("INSERT INTO tram_cuu_ho (matram, tentram, nguoidaidien, sodienthoai, diachi, khuvuc, trangthaihoptac, sotaikhoan, nganhang)
                                  VALUES (?, ?, ?, ?, ?, ?, 'Đang hợp tác', ?, ?)");
            $stmt->execute([$maTram, $tenTram, $nguoiDaiDien, $sdt, $diaChi, $khuVuc, $soTaiKhoan, $nganHang]);
            $db->commit();

            return [
                'success' => true,
                'message' => "Đã thêm trạm cứu hộ \"{$tenTram}\" ({$maTram}) thành công!",
                'shelter' => [
                    'MaTram' => $maTram,
                    'TenTram' => $tenTram,
                    'SoTaiKhoan' => $soTaiKhoan,
                    'NganHang' => $nganHang
                ]
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("Lỗi themTram TramCuuHoModel: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể thêm trạm cứu hộ mới. Vui lòng thử lại.'];
        }
    }
}
