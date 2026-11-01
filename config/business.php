<?php
// ====================================================================
// KIBO E-COMMERCE PLATFORM - Business Constants & Configurations
// ====================================================================

return [
    'quy_ty_le' => 0.05,        // 5% quỹ cứu trợ từ (tiền hàng - giảm giá)
    'doi_tra_so_ngay' => 3,     // Thời hạn đổi trả trong vòng 3 ngày kể từ khi giao thành công
    'diem_moi_dong' => 10000,   // 1 điểm tích lũy cho mỗi 10.000đ thanh toán

    'ship' => [
        'phi_co_ban' => 20000,
        'mien_phi_noi_thanh' => 200000, // Đơn hàng >= 200.000đ nội thành TP.HCM
        'mien_phi_khac' => 500000,      // Đơn hàng >= 500.000đ ngoại thành & các tỉnh
    ],

    // Danh sách quận/huyện nội thành TP.HCM (hỗ trợ cả mã viết tắt và tên tiếng Việt)
    'noi_thanh_hcm' => [
        // Mã viết tắt
        'Q1', 'Q3', 'Q4', 'Q5', 'Q6', 'Q7', 'Q8', 'Q10', 'Q11', 'Q12',
        'BT', 'BTH', 'PN', 'GV', 'TB', 'TP', 'TD',
        // Tên chuẩn tiếng Việt
        'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8',
        'Quận 10', 'Quận 11', 'Quận 12',
        'Quận Bình Thạnh', 'Bình Thạnh',
        'Quận Phú Nhuận', 'Phú Nhuận',
        'Quận Gò Vấp', 'Gò Vấp',
        'Quận Tân Bình', 'Tân Bình',
        'Quận Tân Phú', 'Tân Phú',
        'Quận Bình Tân', 'Bình Tân',
        'Thành phố Thủ Đức', 'TP. Thủ Đức', 'TP Thủ Đức', 'Thủ Đức'
    ],

    'hotline' => '1900 6868',
    'email' => 'support@kibo.vn',
    'dia_chi' => 'Lô E2a-7, Đường D1, Đ. D1, Long Thạnh Mỹ, Thành Phố Thủ Đức, TP. Hồ Chí Minh'
];
