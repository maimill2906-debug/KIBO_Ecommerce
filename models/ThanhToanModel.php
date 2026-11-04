<?php
// ====================================================================
// MODEL: ThanhToanModel.php
// Tương tác bảng thanh_toan (VietQR, COD)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NhatKyModel.php';

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
            } catch (Exception $e) {
                error_log("Lỗi getByOrderId ThanhToanModel: " . $e->getMessage());
            }
        }
        return null;
    }

    public static function getAllWithOrders() {
        $db = Database::getConnection();
        if (!$db) return [];

        try {
            $sql = "SELECT tt.magiaodich, tt.madonhang, tt.phuongthuctt, tt.sotien, tt.thoigiantt, tt.trangthaitt,
                           dh.tennguoinhan, dh.sodienthoainhan, dh.trangthaidonhang, dh.ngaydathang
                    FROM thanh_toan tt
                    LEFT JOIN don_hang dh ON tt.madonhang = dh.madonhang
                    ORDER BY COALESCE(tt.thoigiantt, dh.ngaydathang) DESC";
            return $db->query($sql)->fetchAll();
        } catch (Exception $e) {
            error_log("Lỗi getAllWithOrders: " . $e->getMessage());
            return [];
        }
    }

    public static function xacNhanNhanTien($id) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối CSDL'];

        try {
            $stmt = $db->prepare("SELECT magiaodich, madonhang, sotien, trangthaitt, phuongthuctt FROM thanh_toan WHERE magiaodich = ? OR madonhang = ? LIMIT 1");
            $stmt->execute([$id, $id]);
            $tt = $stmt->fetch();

            if (!$tt) {
                return ['success' => false, 'message' => 'Không tìm thấy thông tin giao dịch thanh toán.'];
            }

            if ($tt['trangthaitt'] === 'Thành công') {
                return ['success' => false, 'message' => 'Giao dịch này đã được xác nhận thành công trước đó.'];
            }

            $upd = $db->prepare("UPDATE thanh_toan SET trangthaitt = 'Thành công', thoigiantt = NOW() WHERE magiaodich = ?");
            $upd->execute([$tt['magiaodich']]);

            NhatKyModel::ghi('Xác nhận thanh toán', 'thanh_toan', $tt['magiaodich'], "Xác nhận đã nhận chuyển khoản " . number_format($tt['sotien'], 0, ',', '.') . "đ cho đơn {$tt['madonhang']}");

            return [
                'success' => true,
                'message' => "Đã xác nhận nhận tiền thành công cho đơn {$tt['madonhang']} (" . number_format($tt['sotien'], 0, ',', '.') . "đ)."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function layDanhSachDoiSoat() {
        $db = Database::getConnection();
        if (!$db) return [];

        try {
            $sql = "SELECT tt.magiaodich, tt.madonhang, tt.phuongthuctt, tt.sotien, tt.thoigiantt, tt.trangthaitt,
                           dh.tennguoinhan, dh.sodienthoainhan, dh.trangthaidonhang, dh.ngaydathang,
                           CASE 
                               WHEN dh.trangthaidonhang = 'Giao thành công' AND tt.trangthaitt <> 'Thành công' THEN 'Cần thu tiền ngay'
                               WHEN tt.phuongthuctt = 'VietQR' AND dh.ngaydathang < NOW() - INTERVAL '24 hours' THEN 'Quá hạn chuyển khoản'
                               ELSE 'Chờ đối soát'
                           END as lydodoisoat
                    FROM thanh_toan tt
                    LEFT JOIN don_hang dh ON tt.madonhang = dh.madonhang
                    WHERE tt.trangthaitt = 'Chờ thanh toán'
                    ORDER BY dh.ngaydathang ASC";
            return $db->query($sql)->fetchAll();
        } catch (Exception $e) {
            error_log("Lỗi layDanhSachDoiSoat: " . $e->getMessage());
            return [];
        }
    }
}
