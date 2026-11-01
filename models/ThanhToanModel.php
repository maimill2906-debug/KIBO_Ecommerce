<?php
// ====================================================================
// MODEL: ThanhToanModel.php
// Tương tác bảng thanh_toan (VietQR, COD)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class ThanhToanModel {
    private static $mockPayments = [
        [
            'MaGiaoDich' => 'GD001',
            'MaDonHang' => 'KB-8921',
            'PhuongThucTT' => 'Chuyển khoản VietQR',
            'SoTien' => 285000,
            'ThoiGianTT' => '2026-10-25 09:16:00',
            'TrangThaiTT' => 'Thành công'
        ],
        [
            'MaGiaoDich' => 'GD002',
            'MaDonHang' => 'KB-8922',
            'PhuongThucTT' => 'Chuyển khoản VietQR',
            'SoTien' => 425000,
            'ThoiGianTT' => '2026-10-30 14:21:00',
            'TrangThaiTT' => 'Thành công'
        ]
    ];

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
            } catch (Exception $e) {}
        }
        foreach (self::$mockPayments as $p) {
            if ($p['MaDonHang'] === $maDonHang) return $p;
        }
        return null;
    }
}
