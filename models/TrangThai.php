<?php
// ====================================================================
// KIBO E-COMMERCE PLATFORM - Standard Status Constants
// ====================================================================

class TrangThai {
    // 1. Trạng thái đơn hàng (6 trạng thái hợp lệ của hệ thống)
    public const DH_CHO_XAC_NHAN    = 'Chờ xác nhận';
    public const DH_CHO_DONG_GOI    = 'Chờ đóng gói';
    public const DH_DA_XUAT_KHO     = 'Đã xuất kho';
    public const DH_DANG_GIAO       = 'Đang giao';
    public const DH_GIAO_THANH_CONG = 'Giao thành công';
    public const DH_DA_HUY          = 'Đã hủy';

    // 2. Trạng thái Quỹ cứu trợ
    public const QUY_CHO_TRICH      = 'Chờ trích quỹ';
    public const QUY_DA_TRICH       = 'Đã trích quỹ';
    public const QUY_DA_GIAI_NGAN   = 'Đã giải ngân';
    public const QUY_DA_HUY         = 'Đã hủy';

    // 3. Trạng thái Yêu cầu đổi trả
    public const DT_CHO_XU_LY       = 'Chờ xử lý';
    public const DT_DONG_Y_DOI      = 'Đã đồng ý đổi';
    public const DT_HOAN_TIEN       = 'Đã hoàn tiền';
    public const DT_TU_CHOI         = 'Đã từ chối';
    public const DT_HOAN_TAT        = 'Đã hoàn tất';

    // 4. Trạng thái Thanh toán
    public const TT_CHO_THANH_TOAN  = 'Chờ thanh toán';
    public const TT_THANH_CONG      = 'Thành công';
    public const TT_THAT_BAI        = 'Thất bại';
    public const TT_HOAN_TIEN       = 'Hoàn tiền';

    // Danh sách các trạng thái đơn hàng hợp lệ
    public static function getDanhSachDonHang(): array {
        return [
            self::DH_CHO_XAC_NHAN,
            self::DH_CHO_DONG_GOI,
            self::DH_DA_XUAT_KHO,
            self::DH_DANG_GIAO,
            self::DH_GIAO_THANH_CONG,
            self::DH_DA_HUY
        ];
    }
}
