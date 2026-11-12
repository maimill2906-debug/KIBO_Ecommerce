<?php
// ====================================================================
// MODEL: VoucherModel.php
// Quản lý voucher khuyến mãi KIBO - Kiểm tra điều kiện & khấu trừ server-side
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class VoucherModel {

    public static function tinhGiam($voucher, $tongTienHang) {
        $loai = $voucher['loaigiamgia'] ?? ($voucher['LoaiGiamGia'] ?? 'SoTien');
        $val = (float)($voucher['giatrigiam'] ?? ($voucher['GiaTriGiam'] ?? 0));
        $maxDiscount = !empty($voucher['giamtoida']) ? (float)$voucher['giamtoida'] : (!empty($voucher['GiamToiDa']) ? (float)$voucher['GiamToiDa'] : null);

        if ($loai === 'PhanTram') {
            $discount = round($tongTienHang * ($val / 100));
            if ($maxDiscount !== null && $maxDiscount > 0) {
                $discount = min($discount, $maxDiscount);
            }
        } else {
            $discount = $val;
        }
        return min((float)$tongTienHang, (float)$discount);
    }

    public static function getAllAdmin() {
        $db = Database::getConnection();
        if (!$db) return [];
        try {
            $sql = "SELECT mavoucher, tenvoucher, loaigiamgia, giatrigiam, donhangtoithieu, giamtoida,
                           ngaybatdau, ngayketthuc, soluongconlai, kichhoat
                    FROM voucher 
                    ORDER BY ngaybatdau DESC";
            return $db->query($sql)->fetchAll();
        } catch (Exception $e) {
            error_log("Lỗi getAllAdmin Voucher: " . $e->getMessage());
            return [];
        }
    }

    public static function getAll() {
        $db = Database::getConnection();
        if (!$db) return [];
        try {
            $sql = "SELECT mavoucher as \"MaVoucher\", tenvoucher as \"TenVoucher\", 
                           loaigiamgia as \"LoaiGiamGia\", giatrigiam as \"GiaTriGiam\", 
                           donhangtoithieu as \"DonHangToiThieu\", giamtoida as \"GiamToiDa\",
                           soluongconlai as \"SoLuongConLai\", kichhoat as \"KichHoat\"
                    FROM voucher 
                    WHERE kichhoat = true 
                      AND soluongconlai > 0 
                      AND (ngaybatdau IS NULL OR ngaybatdau <= NOW()) 
                      AND (ngayketthuc IS NULL OR ngayketthuc >= NOW())
                    ORDER BY mavoucher ASC";
            return $db->query($sql)->fetchAll();
        } catch (Exception $e) {
            error_log("Lỗi getAll Voucher: " . $e->getMessage());
            return [];
        }
    }

    public static function getAvailableForCustomer($maKH = '') {
        $all = self::getAll();
        if (empty($maKH) || empty($all)) {
            foreach ($all as &$v) {
                $v['DaSuDung'] = false;
            }
            return $all;
        }
        $db = Database::getConnection();
        if (!$db) return $all;
        try {
            $stmt = $db->prepare("SELECT UPPER(mavoucher) FROM don_hang WHERE makh = ? AND mavoucher IS NOT NULL AND trangthaidonhang <> 'Đã hủy'");
            $stmt->execute([$maKH]);
            $usedCodes = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
            foreach ($all as &$v) {
                $codeUpper = strtoupper($v['MaVoucher'] ?? '');
                $v['DaSuDung'] = isset($usedCodes[$codeUpper]);
            }
        } catch (Exception $e) {
            error_log("Lỗi getAvailableForCustomer: " . $e->getMessage());
        }
        return $all;
    }

    public static function validateVoucher($code, $subtotal) {
        $code = strtoupper(trim($code));
        if (empty($code)) {
            return ['valid' => false, 'message' => 'Vui lòng nhập mã giảm giá.'];
        }

        $db = Database::getConnection();
        if (!$db) {
            return ['valid' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu.'];
        }

        try {
            $stmt = $db->prepare("SELECT * FROM voucher WHERE UPPER(mavoucher) = UPPER(?) LIMIT 1");
            $stmt->execute([$code]);
            $v = $stmt->fetch();

            if (!$v) {
                return ['valid' => false, 'message' => "Mã giảm giá '{$code}' không tồn tại trong hệ thống."];
            }
            if (isset($v['kichhoat']) && !$v['kichhoat']) {
                return ['valid' => false, 'message' => "Mã giảm giá '{$code}' hiện đang bị tạm khóa."];
            }
            $nowTs = time();
            if (!empty($v['ngaybatdau']) && strtotime($v['ngaybatdau']) > $nowTs) {
                return ['valid' => false, 'message' => "Mã giảm giá '{$code}' chưa đến thời gian áp dụng."];
            }
            if (!empty($v['ngayketthuc']) && strtotime($v['ngayketthuc']) < $nowTs) {
                return ['valid' => false, 'message' => "Mã giảm giá '{$code}' đã hết hạn sử dụng."];
            }
            if ((int)$v['soluongconlai'] <= 0) {
                return ['valid' => false, 'message' => "Mã giảm giá '{$code}' đã hết lượt sử dụng."];
            }
            $minOrder = (float)($v['donhangtoithieu'] ?? 0);
            if ((float)$subtotal < $minOrder) {
                return ['valid' => false, 'message' => "Đơn hàng tối thiểu để áp dụng mã này là " . number_format($minOrder, 0, ',', '.') . "đ."];
            }

            if (PHP_SAPI !== 'cli') {
                if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                    @session_start();
                }
                $currMaKH = $_SESSION['user']['MaKH'] ?? '';
                if (!empty($currMaKH)) {
                    $maxPerCust = !empty($v['moikhachtoida']) ? (int)$v['moikhachtoida'] : 1;
                    $chkUsed = $db->prepare("SELECT COUNT(*) FROM don_hang WHERE makh = ? AND UPPER(mavoucher) = UPPER(?) AND trangthaidonhang <> 'Đã hủy'");
                    $chkUsed->execute([$currMaKH, $v['mavoucher']]);
                    if ((int)$chkUsed->fetchColumn() >= $maxPerCust) {
                        return ['valid' => false, 'message' => "Tài khoản của bạn đã sử dụng hết lượt cho mã '{$v['mavoucher']}'."];
                    }
                }
            }

            $discount = self::tinhGiam($v, $subtotal);
            return [
                'valid' => true,
                'discount' => $discount,
                'code' => $v['mavoucher'],
                'name' => $v['tenvoucher'],
                'voucher' => $v
            ];
        } catch (Exception $e) {
            error_log("Lỗi validateVoucher: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Lỗi kiểm tra mã giảm giá, vui lòng thử lại sau.'];
        }
    }

    public static function useVoucher($code) {
        $db = Database::getConnection();
        if (!$db || empty($code)) return false;
        try {
            $stmt = $db->prepare("UPDATE voucher SET soluongconlai = soluongconlai - 1 WHERE UPPER(mavoucher) = UPPER(?) AND soluongconlai > 0");
            $stmt->execute([$code]);
            return ($stmt->rowCount() === 1);
        } catch (Exception $e) {
            error_log("Lỗi useVoucher: " . $e->getMessage());
            return false;
        }
    }

    public static function saveVoucher($data) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];

        $code = strtoupper(trim($data['mavoucher'] ?? ''));
        $name = trim($data['tenvoucher'] ?? '');
        $typeRaw = trim($data['loaigiamgia'] ?? ($data['loai'] ?? 'SoTien'));
        $type = ($typeRaw === 'Phần trăm' || $typeRaw === 'PhanTram') ? 'PhanTram' : 'SoTien';
        $val = (float)($data['giatrigiam'] ?? ($data['giatri'] ?? 0));
        $min = max(0, (float)($data['donhangtoithieu'] ?? ($data['dontoithieu'] ?? 0)));
        $maxDisc = !empty($data['giamtoida']) ? max(0, (float)$data['giamtoida']) : null;
        $qty = max(0, (int)($data['soluongconlai'] ?? ($data['soluongphathanh'] ?? 0)));
        $start = !empty($data['ngaybatdau']) ? date('Y-m-d H:i:s', strtotime($data['ngaybatdau'])) : date('Y-m-d 00:00:00');
        $end = !empty($data['ngayketthuc']) ? date('Y-m-d H:i:s', strtotime($data['ngayketthuc'])) : date('Y-m-d 23:59:59', strtotime('+30 days'));

        if (!preg_match('/^[A-Z0-9]{3,10}$/', $code)) {
            return ['success' => false, 'message' => 'Mã voucher phải gồm 3-10 ký tự chữ in hoa hoặc số (không dấu, không khoảng trắng).'];
        }
        if (!in_array($type, ['SoTien', 'PhanTram'], true)) {
            return ['success' => false, 'message' => 'Loại giảm giá không hợp lệ (SoTien hoặc PhanTram).'];
        }
        if ($type === 'PhanTram' && ($val < 1 || $val > 100)) {
            return ['success' => false, 'message' => 'Phần trăm giảm giá phải nằm trong khoảng từ 1% đến 100%.'];
        }
        if ($type === 'SoTien' && $val <= 0) {
            return ['success' => false, 'message' => 'Số tiền giảm giá phải lớn hơn 0đ.'];
        }
        if (strtotime($end) <= strtotime($start)) {
            return ['success' => false, 'message' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu.'];
        }

        try {
            $chk = $db->prepare("SELECT mavoucher FROM voucher WHERE UPPER(mavoucher) = UPPER(?)");
            $chk->execute([$code]);
            $exists = $chk->fetch();

            if ($exists) {
                $sql = "UPDATE voucher 
                        SET tenvoucher = ?, loaigiamgia = ?, giatrigiam = ?, donhangtoithieu = ?, giamtoida = ?, ngaybatdau = ?, ngayketthuc = ?, soluongconlai = ?
                        WHERE UPPER(mavoucher) = UPPER(?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$name, $type, $val, $min, $maxDisc, $start, $end, $qty, $code]);
                return ['success' => true, 'message' => "Cập nhật mã '{$code}' thành công."];
            } else {
                $sql = "INSERT INTO voucher (mavoucher, tenvoucher, loaigiamgia, giatrigiam, donhangtoithieu, giamtoida, ngaybatdau, ngayketthuc, soluongconlai, kichhoat)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, true)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$code, $name, $type, $val, $min, $maxDisc, $start, $end, $qty]);
                return ['success' => true, 'message' => "Tạo mới mã '{$code}' thành công."];
            }
        } catch (Exception $e) {
            error_log("Lỗi saveVoucher: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lưu mã giảm giá, vui lòng thử lại sau.'];
        }
    }

    public static function toggleActive($code) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];

        $code = strtoupper(trim($code));
        if (empty($code)) {
            return ['success' => false, 'message' => 'Thiếu mã voucher cần cập nhật trạng thái.'];
        }

        try {
            $stmt = $db->prepare("UPDATE voucher SET kichhoat = NOT COALESCE(kichhoat, true) WHERE UPPER(mavoucher) = UPPER(?) RETURNING kichhoat");
            $stmt->execute([$code]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$res && $stmt->rowCount() === 0) {
                return ['success' => false, 'message' => "Không tìm thấy mã voucher '{$code}' trong hệ thống."];
            }
            $active = !empty($res['kichhoat']);
            $msg = $active ? "Đã bật kích hoạt mã voucher {$code}" : "Đã tắt (vô hiệu hóa) mã voucher {$code}";
            return ['success' => true, 'kichhoat' => $active, 'message' => $msg];
        } catch (Exception $e) {
            error_log("Lỗi toggleActive: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi chuyển trạng thái mã giảm giá, vui lòng thử lại sau.'];
        }
    }
}
