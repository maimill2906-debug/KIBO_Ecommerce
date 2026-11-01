<?php
// ====================================================================
// MODEL: VoucherModel.php
// Tương tác bảng voucher (Kiểm tra điều kiện áp mã giảm giá KIBO)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class VoucherModel {
    private static $mockVouchers = [
        [
            'MaVoucher' => 'KIBO20',
            'TenVoucher' => 'Giảm 20K cho đơn hàng đầu tiên',
            'LoaiGiamGia' => 'SoTien',
            'GiaTriGiam' => 20000,
            'DonHangToiThieu' => 200000,
            'SoLuongConLai' => 95
        ],
        [
            'MaVoucher' => 'FREESHIP',
            'TenVoucher' => 'Giảm 10% phí ship đơn từ 200K',
            'LoaiGiamGia' => 'PhanTram',
            'GiaTriGiam' => 10,
            'DonHangToiThieu' => 200000,
            'SoLuongConLai' => 500
        ]
    ];

    public static function getAll() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT mavoucher as \"MaVoucher\", tenvoucher as \"TenVoucher\", 
                               loaigiamgia as \"LoaiGiamGia\", giatrigiam as \"GiaTriGiam\", 
                               donhangtoithieu as \"DonHangToiThieu\", soluongconlai as \"SoLuongConLai\" 
                        FROM voucher 
                        WHERE soluongconlai > 0";
                $rows = $db->query($sql)->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {}
        }
        return self::$mockVouchers;
    }

    public static function validateVoucher($code, $subtotal) {
        $code = strtoupper(trim($code));
        foreach (self::getAll() as $v) {
            if (strtoupper($v['MaVoucher']) === $code) {
                if ($subtotal < $v['DonHangToiThieu']) {
                    return ['valid' => false, 'message' => 'Đơn hàng chưa đạt mức tối thiểu ' . number_format($v['DonHangToiThieu']) . 'đ'];
                }
                $discount = ($v['LoaiGiamGia'] === 'PhanTram')
                    ? round($subtotal * ($v['GiaTriGiam'] / 100))
                    : $v['GiaTriGiam'];
                return ['valid' => true, 'discount' => $discount, 'voucher' => $v];
            }
        }
        return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết lượt sử dụng'];
    }

    public static function useVoucher($code) {
        $db = Database::getConnection();
        if (!$db || empty($code)) return;
        try {
            $stmt = $db->prepare("UPDATE voucher SET soluongconlai = GREATEST(0, soluongconlai - 1) WHERE UPPER(mavoucher) = UPPER(?)");
            $stmt->execute([$code]);
        } catch (Exception $e) {}
    }
}
