<?php
/**
 * MODEL / INTEGRATION: DonViVanChuyen.php
 * Lớp tích hợp đơn vị vận chuyển (3PL E-Logistics: Tính phí ship theo vùng, cấp mã vận đơn)
 * Khớp Bảng 3.7 & Biểu đồ lớp Hình 3.16 (Chương 3 Báo cáo)
 */
require_once __DIR__ . '/DonHangModel.php';

class DonViVanChuyen {
    public static function tinhPhiGiaoHang($tongTienHang, $diaChiNhan = '', $quanHuyen = '', $tinhThanh = '') {
        return DonHangModel::tinhPhiShip($tongTienHang, $diaChiNhan, $quanHuyen, $tinhThanh);
    }

    public static function taoVanDon($maDonHang, $donVi = 'GHN') {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $donVi)) ?: 'KIBOLOG';
        $code = $prefix . '-' . date('ymd') . '-' . strtoupper(substr(md5((string)$maDonHang . microtime(true)), 0, 6));
        return [
            'success' => true,
            'madonhang' => $maDonHang,
            'mavandon' => $code,
            'donvivanchuyen' => $donVi,
            'trangthai' => 'Đã tiếp nhận vận đơn'
        ];
    }

    public static function capNhatHanhTrinh($maDonHang, $trangThaiMoi, $maVanDon = null) {
        return DonHangModel::capNhatTrangThai($maDonHang, $trangThaiMoi, $maVanDon);
    }
}
