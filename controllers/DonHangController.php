<?php
require_once __DIR__ . '/../models/DonHangModel.php';
require_once __DIR__ . '/../models/VoucherModel.php';

class DonHangController {
    public function index() {
        require __DIR__ . '/../views/customer/DonHangView.php';
    }

    public function listJson() {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(DonHangModel::getAll(), JSON_UNESCAPED_UNICODE);
    }

    public function detailJson($maDH) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(DonHangModel::getOrderByCode($maDH), JSON_UNESCAPED_UNICODE);
    }

    public function checkVoucherJson($code, $subtotal) {
        header('Content-Type: application/json; charset=utf-8');
        $res = VoucherModel::validateVoucher($code, (float)$subtotal);
        if ($res['valid']) {
            echo json_encode(['valid' => true, 'discount' => $res['discount'], 'voucher' => $res['voucher']], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['valid' => false, 'discount' => 0, 'message' => $res['message']], JSON_UNESCAPED_UNICODE);
        }
    }
}
