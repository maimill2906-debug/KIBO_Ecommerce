<?php
require_once __DIR__ . '/../models/DonHangModel.php';

class DonHangController {
    private $donHangModel;

    public function __construct() {
        $this->donHangModel = new DonHangModel();
    }

    public function index() {
        require __DIR__ . '/../views/customer/DonHangView.php';
    }

    public function listJson() {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->donHangModel->getAllOrders(), JSON_UNESCAPED_UNICODE);
    }

    public function detailJson($maDH) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->donHangModel->getOrderById($maDH), JSON_UNESCAPED_UNICODE);
    }

    public function checkVoucherJson($code, $subtotal) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['valid' => true, 'discount' => 10000], JSON_UNESCAPED_UNICODE);
    }
}
