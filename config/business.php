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

    // Các định dạng nhận diện TP.HCM
    'tinh_thanh_hcm' => [
        'Hồ Chí Minh', 'TP. Hồ Chí Minh', 'TP Hồ Chí Minh', 'TP.HCM', 'TPHCM', 'Thành phố Hồ Chí Minh', 'HCM'
    ],

    // Danh sách 16 quận/thành phố nội thành TP.HCM (so khớp chính xác, bỏ mọi mã viết tắt 2 chữ dễ nhầm CC, HM, BC, NB, CG, TP, BTH)
    'noi_thanh_hcm' => [
        'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8',
        'Quận 10', 'Quận 11', 'Quận 12',
        'Quận Bình Thạnh',
        'Quận Phú Nhuận',
        'Quận Gò Vấp',
        'Quận Tân Bình',
        'Quận Tân Phú',
        'Quận Bình Tân',
        'Thành phố Thủ Đức', 'TP. Thủ Đức', 'TP Thủ Đức', 'Thủ Đức'
    ],

    // 5 huyện ngoại thành TP.HCM (ngưỡng miễn ship 500.000đ)
    'ngoai_thanh_hcm' => [
        'Huyện Củ Chi', 'Củ Chi',
        'Huyện Hóc Môn', 'Hóc Môn',
        'Huyện Bình Chánh', 'Bình Chánh',
        'Huyện Nhà Bè', 'Nhà Bè',
        'Huyện Cần Giờ', 'Cần Giờ'
    ],

    'hotline' => '0772 906 756',
    'email' => 'support@kibo.vn',
    'dia_chi' => 'Lô E2a-7, Đường D1, Đ. D1, Long Thạnh Mỹ, Thành Phố Thủ Đức, TP. Hồ Chí Minh'
];
