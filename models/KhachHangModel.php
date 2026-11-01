<?php
// ====================================================================
// MODEL: KhachHangModel.php
// Tương tác bảng khach_hang, sdt_kh, dia_chi_kh (Chuẩn 3NF Chương 4)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class KhachHangModel {

    public static function getAll() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT kh.makh as \"MaKH\", kh.matk as \"MaTK\", kh.hoten as \"HoTen\", kh.email as \"Email\", 
                               kh.ngaysinh as \"NgaySinh\", kh.diemtichluy as \"DiemTichLuy\",
                               sdt.sodienthoai as \"SoDienThoaiChinh\",
                               dc.diachichitiet, dc.phuongxa, dc.quanhuyen, dc.tinhthanh
                        FROM khach_hang kh
                        LEFT JOIN sdt_kh sdt ON kh.makh = sdt.makh
                        LEFT JOIN dia_chi_kh dc ON kh.makh = dc.makh AND dc.macdinh = true
                        ORDER BY kh.makh ASC";
                $rows = $db->query($sql)->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {}
        }
        return [];
    }

    public static function getById($maKH) {
        $all = self::getAll();
        foreach ($all as $c) {
            if ($c['MaKH'] === $maKH) return $c;
        }
        return null;
    }

    public static function getCustomerDetail($identifier) {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT kh.makh as \"MaKH\", kh.matk as \"MaTK\", kh.hoten as \"HoTen\", kh.email as \"Email\", 
                               kh.ngaysinh as \"NgaySinh\", kh.diemtichluy as \"DiemTichLuy\",
                               sdt.sodienthoai as \"SoDienThoaiChinh\",
                               dc.diachichitiet, dc.phuongxa, dc.quanhuyen, dc.tinhthanh
                        FROM khach_hang kh
                        LEFT JOIN sdt_kh sdt ON kh.makh = sdt.makh
                        LEFT JOIN dia_chi_kh dc ON kh.makh = dc.makh
                        WHERE LOWER(kh.email) = LOWER(?) OR kh.matk = ? OR kh.makh = ?
                        LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$identifier, $identifier, $identifier]);
                $row = $stmt->fetch();
                if ($row) return $row;
            } catch (Exception $e) {}
        }
        return null;
    }

    public static function updateProfile($maKH, $hoten, $phone, $address) {
        $hoten = trim($hoten);
        $phone = preg_replace('/[^\d]/', '', trim($phone));
        $address = trim($address);

        if (mb_strlen($hoten) < 2) {
            return ['success' => false, 'message' => 'Họ và tên phải có ít nhất 2 ký tự.'];
        }

        if (!empty($phone) && !preg_match('/^0[0-9]{9}$/', $phone)) {
            return ['success' => false, 'message' => 'Số điện thoại phải gồm đúng 10 chữ số (Ví dụ: 0912345678).'];
        }

        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];

        try {
            // Nếu maKH trống, tự động tra cứu từ session
            if (empty($maKH) && !empty($_SESSION['user'])) {
                $sess = $_SESSION['user'];
                $chk = $db->prepare("SELECT makh FROM khach_hang WHERE matk = ? OR LOWER(email) = LOWER(?) LIMIT 1");
                $chk->execute([$sess['MaTK'] ?? '', $sess['TenDangNhap'] ?? '']);
                $found = $chk->fetchColumn();
                if ($found) $maKH = $found;
            }

            if (empty($maKH)) {
                return ['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng để cập nhật'];
            }

            $db->beginTransaction();

            // 1. Cập nhật khach_hang
            $stmtKH = $db->prepare("UPDATE khach_hang SET hoten = ? WHERE makh = ?");
            $stmtKH->execute([$hoten, $maKH]);

            // 2. Cập nhật sdt_kh
            if (!empty($phone)) {
                $checkSDT = $db->prepare("SELECT COUNT(*) FROM sdt_kh WHERE makh = ?");
                $checkSDT->execute([$maKH]);
                if ($checkSDT->fetchColumn() > 0) {
                    $stmtSDT = $db->prepare("UPDATE sdt_kh SET sodienthoai = ? WHERE makh = ?");
                    $stmtSDT->execute([$phone, $maKH]);
                } else {
                    $stmtSDT = $db->prepare("INSERT INTO sdt_kh (makh, sodienthoai) VALUES (?, ?)");
                    $stmtSDT->execute([$maKH, $phone]);
                }
            }

            // 3. Cập nhật dia_chi_kh
            if (!empty($address)) {
                $checkDC = $db->prepare("SELECT COUNT(*) FROM dia_chi_kh WHERE makh = ?");
                $checkDC->execute([$maKH]);
                if ($checkDC->fetchColumn() > 0) {
                    $stmtDC = $db->prepare("UPDATE dia_chi_kh SET diachichitiet = ? WHERE makh = ?");
                    $stmtDC->execute([$address, $maKH]);
                } else {
                    $maxDC = $db->query("SELECT MAX(CAST(SUBSTRING(madiachi FROM 3) AS INTEGER)) FROM dia_chi_kh WHERE madiachi ~ '^DC[0-9]+$'")->fetchColumn();
                    $nextDCNum = ($maxDC ? (int)$maxDC : 0) + 1;
                    $maDC = 'DC' . str_pad($nextDCNum, 3, '0', STR_PAD_LEFT);
                    $stmtDC = $db->prepare("INSERT INTO dia_chi_kh (madiachi, makh, diachichitiet, phuongxa, quanhuyen, tinhthanh, macdinh) VALUES (?, ?, ?, '', '', '', true)");
                    $stmtDC->execute([$maDC, $maKH, $address]);
                }
            }

            $db->commit();
            return ['success' => true, 'message' => 'Cập nhật thông tin thành công!', 'makh' => $maKH];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => 'Lỗi cập nhật: ' . $e->getMessage()];
        }
    }
}
