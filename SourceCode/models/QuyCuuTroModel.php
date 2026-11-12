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

    public static function getPendingDonations() {
        $db = Database::getConnection();
        if (!$db) return [];
        try {
            $sql = "SELECT qct.madonggop, qct.madonhang, qct.matracuu, qct.sotiendonggop, qct.ngayghinhan,
                           dh.tennguoinhan, dh.tongthanhtoan
                    FROM quy_cuu_tro qct
                    JOIN don_hang dh ON qct.madonhang = dh.madonhang
                    WHERE qct.trangthaiquy = ? AND qct.machungtu IS NULL
                    ORDER BY qct.ngayghinhan ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([TrangThai::QUY_DA_TRICH]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Lỗi getPendingDonations: " . $e->getMessage());
            return [];
        }
    }

    public static function giaiNgan($maTram, $soTien, $ngayChuyenKhoan = '', $mucDich = '', $fileChungTu = '', $danhSachMaDongGop = []) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];

        $maTram = trim((string)$maTram);
        $soTien = (float)$soTien;
        $mucDich = trim((string)$mucDich);
        $fileChungTu = trim((string)$fileChungTu);

        if (empty($maTram)) {
            return ['success' => false, 'message' => 'Vui lòng chọn trạm cứu hộ nhận giải ngân.'];
        }
        if ($soTien <= 0) {
            return ['success' => false, 'message' => 'Số tiền giải ngân phải lớn hơn 0đ.'];
        }

        // 1. Kiểm tra số dư khả dụng của quỹ trước tiên
        $stmtTotalEligible = $db->prepare("SELECT COALESCE(SUM(sotiendonggop), 0) FROM quy_cuu_tro WHERE trangthaiquy = ? AND machungtu IS NULL");
        $stmtTotalEligible->execute([TrangThai::QUY_DA_TRICH]);
        $currAvailable = (float)$stmtTotalEligible->fetchColumn();

        if ($currAvailable <= 0) {
            return ['success' => false, 'message' => 'Hiện tại quỹ cứu trợ chưa có số dư khả dụng (0đ) để thực hiện giải ngân.'];
        }
        if ($soTien > $currAvailable) {
            return ['success' => false, 'message' => "Số tiền giải ngân (" . number_format($soTien, 0, ',', '.') . "đ) vượt quá số dư khả dụng của quỹ hiện có (" . number_format($currAvailable, 0, ',', '.') . "đ)."];
        }

        if (empty($mucDich)) {
            $mucDich = 'Chi phí thức ăn và cứu trợ y tế cho động vật cơ nhỡ';
        }

        // 2. Kiểm tra ngày chuyển khoản: Phải hợp lệ và KHÔNG được ở tương lai
        if (empty($ngayChuyenKhoan)) {
            $ngayChuyenKhoan = date('Y-m-d');
        }
        $timeCK = strtotime($ngayChuyenKhoan);
        if (!$timeCK) {
            return ['success' => false, 'message' => 'Ngày chuyển khoản không hợp lệ.'];
        }
        $dateCK = date('Y-m-d', $timeCK);
        $today = date('Y-m-d');
        if ($dateCK > $today) {
            return ['success' => false, 'message' => 'Ngày chuyển khoản không hợp lệ (không được lớn hơn ngày hiện tại).'];
        }
        if ($dateCK < '2024-01-01') {
            return ['success' => false, 'message' => 'Ngày chuyển khoản không hợp lệ (trước năm 2024).'];
        }

        // 3. Kiểm tra file chứng từ: Không được rỗng, không chấp nhận chuỗi độc hại, bắt buộc file tồn tại trên ổ đĩa
        if (empty($fileChungTu)) {
            return ['success' => false, 'message' => 'Vui lòng tải lên file chứng từ sao kê / hóa đơn thực tế.'];
        }
        if (preg_match('#^(javascript:|data:|vbscript:|<script)#i', $fileChungTu) || strpos($fileChungTu, '..') !== false) {
            return ['success' => false, 'message' => 'Đường dẫn file chứng từ không hợp lệ hoặc chứa mã độc hại.'];
        }
        $fullPath = realpath(__DIR__ . '/../' . ltrim($fileChungTu, '/\\'));
        if (!$fullPath || !file_exists($fullPath)) {
            return ['success' => false, 'message' => "File chứng từ '{$fileChungTu}' không tồn tại thực tế trên máy chủ."];
        }

        try {
            $db->beginTransaction();

            // 3. Kiểm tra trạm cứu hộ hợp lệ
            $chkStation = $db->prepare("SELECT tentram FROM tram_cuu_ho WHERE matram = ?");
            $chkStation->execute([$maTram]);
            $stationName = $chkStation->fetchColumn();
            if (!$stationName) {
                throw new Exception("Trạm cứu hộ '{$maTram}' không tồn tại trong hệ thống.");
            }

            // 4. Lấy danh sách các khoản trích quỹ để giải ngân
            if (!empty($danhSachMaDongGop) && is_array($danhSachMaDongGop)) {
                $cleanIds = array_values(array_filter(array_map('trim', $danhSachMaDongGop)));
                if (empty($cleanIds)) {
                    throw new Exception("Vui lòng chọn ít nhất một khoản trích quỹ khả dụng.");
                }
                $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
                $sqlSel = "SELECT madonggop, sotiendonggop FROM quy_cuu_tro 
                           WHERE madonggop IN ($placeholders) AND trangthaiquy = ? AND machungtu IS NULL 
                           ORDER BY ngayghinhan ASC FOR UPDATE";
                $stmtEligible = $db->prepare($sqlSel);
                $params = array_merge($cleanIds, [TrangThai::QUY_DA_TRICH]);
                $stmtEligible->execute($params);
                $allocatedRows = $stmtEligible->fetchAll();

                if (count($allocatedRows) !== count($cleanIds)) {
                    throw new Exception("Một số khoản trích quỹ được chọn không hợp lệ, không ở trạng thái 'Đã trích quỹ' hoặc đã được giải ngân.");
                }

                $totalSelected = (float)array_sum(array_column($allocatedRows, 'sotiendonggop'));
                if (abs($totalSelected - $soTien) > 0.01) {
                    throw new Exception("Số tiền giải ngân (" . number_format($soTien, 0, ',', '.') . "đ) không khớp với tổng giá trị của " . count($allocatedRows) . " khoản trích quỹ được chọn (" . number_format($totalSelected, 0, ',', '.') . "đ). Hệ thống chỉ cho phép giải ngân đúng bằng tổng các dòng được chọn để đảm bảo đối soát minh bạch 100%.");
                }
            } else {
                // Tự động chọn theo thứ tự FIFO khớp chính xác số tiền
                $stmtEligible = $db->prepare("SELECT madonggop, sotiendonggop FROM quy_cuu_tro 
                                              WHERE trangthaiquy = ? AND machungtu IS NULL 
                                              ORDER BY ngayghinhan ASC FOR UPDATE");
                $stmtEligible->execute([TrangThai::QUY_DA_TRICH]);
                $eligibleRows = $stmtEligible->fetchAll();

                $allocatedRows = [];
                $runningSum = 0;
                foreach ($eligibleRows as $r) {
                    $allocatedRows[] = $r;
                    $runningSum += (float)$r['sotiendonggop'];
                    if (abs($runningSum - $soTien) < 0.01) {
                        break;
                    }
                    if ($runningSum > $soTien) {
                        throw new Exception("Số tiền giải ngân (" . number_format($soTien, 0, ',', '.') . "đ) không khớp chính xác với tổng của bất kỳ tổ hợp khoản trích quỹ nào. Vui lòng chọn cụ thể từng dòng hoặc nhập đúng tổng các khoản trích quỹ.");
                    }
                }

                if (empty($allocatedRows) || abs($runningSum - $soTien) > 0.01) {
                    throw new Exception("Số tiền giải ngân (" . number_format($soTien, 0, ',', '.') . "đ) không khớp chính xác với tổng số tiền của các khoản trích quỹ đang có.");
                }
            }

            // 5. Tạo mã chứng từ mới
            require_once __DIR__ . '/IdGen.php';
            $maChungTu = IdGen::next($db, 'chung_tu_giai_ngan', 'machungtu', 'CT', 3);

            // 6. Lưu bảng chung_tu_giai_ngan
            $sqlCT = "INSERT INTO chung_tu_giai_ngan (machungtu, matram, sotiengiaingan, ngaychuyenkhoan, mucdichsudung, filehoadonchungtu)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmtCT = $db->prepare($sqlCT);
            $stmtCT->execute([$maChungTu, $maTram, $soTien, $dateCK, $mucDich, $fileChungTu]);

            // 7. Cập nhật chính xác các dòng quy_cuu_tro sang 'Đã giải ngân'
            $stmtUpdateQuy = $db->prepare("UPDATE quy_cuu_tro SET trangthaiquy = ?, machungtu = ?, matram = ? WHERE madonggop = ?");
            $countAllocated = 0;
            foreach ($allocatedRows as $r) {
                $stmtUpdateQuy->execute([TrangThai::QUY_DA_GIAI_NGAN, $maChungTu, $maTram, $r['madonggop']]);
                $countAllocated++;
            }

            $db->commit();

            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', $maChungTu, "Giải ngân " . number_format($soTien, 0, ',', '.') . "đ cho {$stationName} ({$maTram}). Mục đích: {$mucDich}");

            return [
                'success' => true,
                'message' => "Lập chứng từ giải ngân '{$maChungTu}' thành công cho {$stationName} (" . number_format($soTien, 0, ',', '.') . "đ). Đã đối soát và liên kết chuẩn xác {$countAllocated} khoản trích quỹ.",
                'machungtu' => $maChungTu,
                'so_khoan_giai_ngan' => $countAllocated
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("Database error in giaiNgan QuyCuuTro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
