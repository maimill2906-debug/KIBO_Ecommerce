<?php
// ====================================================================
// MODEL: TramCuuHoModel.php
// Tương tác bảng tram_cuu_ho, chung_tu_giai_ngan (Minh bạch ngày 30 hàng tháng)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class TramCuuHoModel {
    private static $mockShelters = [
        [
            'MaTram' => 'TCH01',
            'TenTram' => 'Trạm Cứu Hộ Động Vật Nông Lâm',
            'NguoiDaiDien' => 'Bác sĩ thú y Nguyễn Văn An',
            'SoDienThoai' => '0912345678',
            'DiaChi' => 'Khuôn viên ĐH Nông Lâm, TP. Thủ Đức, TP.HCM',
            'KhuVuc' => 'TP. Hồ Chí Minh',
            'TrangThaiHopTac' => 'Đang hợp tác'
        ],
        [
            'MaTram' => 'TCH02',
            'TenTram' => 'Trạm Cứu Hộ Động Vật Sài Gòn Time',
            'NguoiDaiDien' => 'Chị Trần Thị Mai Lan',
            'SoDienThoai' => '0987654321',
            'DiaChi' => 'Đường Tô Ngọc Vân, P. Tam Phú, TP. Thủ Đức, TP.HCM',
            'KhuVuc' => 'TP. Hồ Chí Minh',
            'TrangThaiHopTac' => 'Đang hợp tác'
        ]
    ];

    private static $mockDisbursements = [
        [
            'MaChungTu' => 'CT001',
            'MaTram' => 'TCH01',
            'TenTram' => 'Trạm Cứu Hộ Động Vật Nông Lâm',
            'SoTienGiaiNgan' => 12500000,
            'NgayChuyenKhoan' => '2026-09-30',
            'MucDichSuDung' => 'Chi phí thức ăn hạt và điều trị phẫu thuật triệt sản 35 ca chó mèo cơ nhỡ',
            'FileHoaDonChungTu' => 'assets/docs/chung_tu_nong_lam_092026.pdf'
        ],
        [
            'MaChungTu' => 'CT002',
            'MaTram' => 'TCH02',
            'TenTram' => 'Trạm Cứu Hộ Động Vật Sài Gòn Time',
            'SoTienGiaiNgan' => 8900000,
            'NgayChuyenKhoan' => '2026-10-15',
            'MucDichSuDung' => 'Tài trợ cát vệ sinh, vắc-xin 4 bệnh và thuốc đặc trị nấm mốc',
            'FileHoaDonChungTu' => 'assets/docs/chung_tu_sg_time_102026.pdf'
        ]
    ];

    public static function getShelters() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT matram as \"MaTram\", tentram as \"TenTram\", nguoidaidien as \"NguoiDaiDien\", 
                               sodienthoai as \"SoDienThoai\", diachi as \"DiaChi\", khuvuc as \"KhuVuc\", 
                               trangthaihoptac as \"TrangThaiHopTac\" 
                        FROM tram_cuu_ho 
                        ORDER BY matram ASC";
                $rows = $db->query($sql)->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {}
        }
        return self::$mockShelters;
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
            } catch (Exception $e) {}
        }
        return self::$mockDisbursements;
    }
}
