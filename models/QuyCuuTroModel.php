<?php
// ====================================================================
// MODEL: QuyCuuTroModel.php
// Quản lý nghiệp vụ trích 5% doanh thu & minh bạch giải ngân
// Bảng: quy_cuu_tro, tram_cuu_ho, chung_tu_giai_ngan, don_hang
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TrangThai.php';

class QuyCuuTroModel {

    // Thống kê tổng quan Quỹ Cứu Trợ 5% hiển thị trên trang chủ KIBO (100% số liệu thực tế từ DB)
    public static function getFundSummary() {
        $db = Database::getConnection();
        if ($db) {
            try {
                // 1. Tích lũy = SUM(sotiendonggop) với trangthaiquy IN ('Đã trích quỹ', 'Đã giải ngân')
                $sqlTL = "SELECT COALESCE(SUM(sotiendonggop), 0) as tong_tich_luy, COUNT(madonggop) as so_don 
                          FROM quy_cuu_tro 
                          WHERE trangthaiquy IN ('" . TrangThai::QUY_DA_TRICH . "', '" . TrangThai::QUY_DA_GIAI_NGAN . "')";
                $rowTL = $db->query($sqlTL)->fetch();

                // 2. Đang chờ trích = SUM với 'Chờ trích quỹ'
                $sqlCho = "SELECT COALESCE(SUM(sotiendonggop), 0) as dang_cho_trich, COUNT(madonggop) as so_don_cho 
                           FROM quy_cuu_tro 
                           WHERE trangthaiquy = '" . TrangThai::QUY_CHO_TRICH . "'";
                $rowCho = $db->query($sqlCho)->fetch();

                // 3. Giải ngân = SUM(sotiengiaingan) từ chung_tu_giai_ngan; kỳ giải ngân gần nhất = MAX(ngaychuyenkhoan)
                $sqlGN = "SELECT COALESCE(SUM(sotiengiaingan), 0) as tong_giai_ngan, MAX(ngaychuyenkhoan) as ky_giai_ngan 
                          FROM chung_tu_giai_ngan";
                $rowGN = $db->query($sqlGN)->fetch();

                // 4. Số trạm = COUNT với trangthaihoptac = 'Đang hợp tác'
                $sqlTram = "SELECT COUNT(matram) as so_tram FROM tram_cuu_ho WHERE trangthaihoptac = 'Đang hợp tác'";
                $rowTram = $db->query($sqlTram)->fetch();

                $kyGN = !empty($rowGN['ky_giai_ngan']) ? date('d/m/Y', strtotime($rowGN['ky_giai_ngan'])) : 'Đang cập nhật';

                return [
                    'TongTichLuy'    => (float)($rowTL['tong_tich_luy'] ?? 0),
                    'SoDonDongGop'   => (int)($rowTL['so_don'] ?? 0),
                    'DangChoTrich'   => (float)($rowCho['dang_cho_trich'] ?? 0),
                    'SoDonChoTrich'  => (int)($rowCho['so_don_cho'] ?? 0),
                    'TongGiaiNgan'   => (float)($rowGN['tong_giai_ngan'] ?? 0),
                    'KyGiaiNgan'     => $kyGN,
                    'SoTramDoiTac'   => (int)($rowTram['so_tram'] ?? 0),
                    'error'          => null
                ];
            } catch (Exception $e) {
                error_log("Lỗi getFundSummary: " . $e->getMessage());
                return [
                    'TongTichLuy'    => 0,
                    'SoDonDongGop'   => 0,
                    'DangChoTrich'   => 0,
                    'SoDonChoTrich'  => 0,
                    'TongGiaiNgan'   => 0,
                    'KyGiaiNgan'     => 'N/A',
                    'SoTramDoiTac'   => 0,
                    'error'          => $e->getMessage()
                ];
            }
        }

        return [
            'TongTichLuy'    => 0,
            'SoDonDongGop'   => 0,
            'DangChoTrich'   => 0,
            'SoDonChoTrich'  => 0,
            'TongGiaiNgan'   => 0,
            'KyGiaiNgan'     => 'N/A',
            'SoTramDoiTac'   => 0,
            'error'          => 'Không thể kết nối cơ sở dữ liệu'
        ];
    }

    public static function getRecentTransactions($limit = 10) {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT qct.*, tch.tentram
                        FROM quy_cuu_tro qct
                        LEFT JOIN chung_tu_giai_ngan ct ON qct.machungtu = ct.machungtu
                        LEFT JOIN tram_cuu_ho tch ON ct.matram = tch.matram
                        ORDER BY qct.ngayghinhan DESC
                        LIMIT ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([(int)$limit]);
                $rows = $stmt->fetchAll();

                $results = [];
                foreach ($rows as $r) {
                    $results[] = [
                        'MaDonHang' => $r['madonhang'],
                        'MaTraCuu' => $r['matracuu'] ?? $r['madonhang'],
                        'SoTienDongGop' => (float)$r['sotiendonggop'],
                        'NgayGhiNhan' => date('d/m/Y H:i', strtotime($r['ngayghinhan'])),
                        'TrangThaiQuy' => $r['trangthaiquy'],
                        'TramNhan' => $r['tentram'] ?? 'Trạm cứu hộ đối tác'
                    ];
                }
                return $results;
            } catch (Exception $e) {
                error_log("Lỗi getRecentTransactions: " . $e->getMessage());
            }
        }

        return [];
    }

    public static function searchByOrder($keyword) {
        return self::lookup($keyword);
    }

    public static function lookup($keyword) {
        $keyword = trim($keyword);
        if (empty($keyword)) return null;

        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT qct.*, ct.mucdichsudung, ct.filehoadonchungtu, tch.tentram
                        FROM quy_cuu_tro qct
                        LEFT JOIN chung_tu_giai_ngan ct ON qct.machungtu = ct.machungtu
                        LEFT JOIN tram_cuu_ho tch ON ct.matram = tch.matram
                        WHERE qct.madonhang = ? OR qct.matracuu = ?
                        LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$keyword, $keyword]);
                $r = $stmt->fetch();

                if ($r) {
                    return [
                        'MaDonHang' => $r['madonhang'],
                        'MaTraCuu' => $r['matracuu'] ?? $r['madonhang'],
                        'SoTienDongGop' => (float)$r['sotiendonggop'],
                        'NgayGhiNhan' => date('d/m/Y H:i', strtotime($r['ngayghinhan'])),
                        'TrangThaiQuy' => $r['trangthaiquy'],
                        'TramNhan' => $r['tentram'] ?? 'Trạm cứu hộ đối tác',
                        'MucDich' => $r['mucdichsudung'] ?? 'Chi phí thức ăn và y tế cứu trợ'
                    ];
                }
            } catch (Exception $e) {
                error_log("Lỗi lookup QuyCuuTro: " . $e->getMessage());
            }
        }

        return null;
    }
}
