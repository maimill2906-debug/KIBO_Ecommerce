<?php
require_once __DIR__ . '/../models/ThanhToanModel.php';

class ThanhToanController {
    private $thanhToanModel;

    public function __construct() {
        $this->thanhToanModel = new ThanhToanModel();
    }

    public function getPaymentInfoJson($maDH) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->thanhToanModel->getByOrder($maDH), JSON_UNESCAPED_UNICODE);
    }
}
