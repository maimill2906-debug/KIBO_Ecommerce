<?php
// ====================================================================
// KIBO PLATFORM - VIETQR CONFIGURATION
// Cấu hình tài khoản ngân hàng nhận thanh toán VietQR chuẩn NAPAS
// ====================================================================

return [
    'bank_bin'      => getenv('VIETQR_BANK_BIN') ?: '970407',          // Techcombank
    'bank_name'     => getenv('VIETQR_BANK_NAME') ?: 'Techcombank',
    'account_no'    => getenv('VIETQR_ACCOUNT_NO') ?: '29062004555',   // STK thụ hưởng KIBO
    'account_name'  => getenv('VIETQR_ACCOUNT_NAME') ?: 'NGUYEN LE QUYNH MAI',
    'template'      => 'compact2'
];
