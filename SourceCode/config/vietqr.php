<?php
// ====================================================================
// KIBO PLATFORM - VIETQR CONFIGURATION
// Cấu hình tài khoản ngân hàng nhận thanh toán VietQR chuẩn NAPAS
// ====================================================================

return [
    'bank_bin'      => getenv('VIETQR_BANK_BIN') ?: '970426',          // MSB (Ngân hàng Hàng Hải)
    'bank_name'     => getenv('VIETQR_BANK_NAME') ?: 'MSB',
    'account_no'    => getenv('VIETQR_ACCOUNT_NO') ?: '96886693102640', // STK VietQR Pro KIBO
    'account_name'  => getenv('VIETQR_ACCOUNT_NAME') ?: 'NGUYEN LE QUYNH MAI',
    'template'      => 'compact2'
];
