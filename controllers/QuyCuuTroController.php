<?php
require_once __DIR__ . '/../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../models/TramCuuHoModel.php';

class QuyCuuTroController {
    private $quyModel;
    private $tramModel;

    public function __construct() {
        $this->quyModel = new QuyCuuTroModel();
        $this->tramModel = new TramCuuHoModel();
    }

    public function index() {
        require __DIR__ . '/../views/customer/QuyCuuTroView.php';
    }

    public function summaryJson() {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'summary' => $this->quyModel->getFundSummary(),
            'recent' => $this->quyModel->getRecentTransactions(10),
            'shelters' => $this->tramModel->getShelters(),
            'disbursements' => $this->tramModel->getDisbursements()
        ], JSON_UNESCAPED_UNICODE);
    }

    public function searchOrderJson($maDH) {
        header('Content-Type: application/json; charset=utf-8');
        $row = $this->quyModel->searchByOrder($maDH);
        echo json_encode(['found' => !empty($row), 'data' => $row], JSON_UNESCAPED_UNICODE);
    }
}
