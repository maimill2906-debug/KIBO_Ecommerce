<?php
require_once __DIR__ . '/../models/DonHangModel.php';
require_once __DIR__ . '/../models/SanPhamModel.php';
require_once __DIR__ . '/../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../models/TramCuuHoModel.php';

class AdminController {
    private $donHangModel;
    private $sanPhamModel;
    private $quyModel;
    private $tramModel;

    public function __construct() {
        $this->donHangModel = new DonHangModel();
        $this->sanPhamModel = new SanPhamModel();
        $this->quyModel = new QuyCuuTroModel();
        $this->tramModel = new TramCuuHoModel();
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $user = $_SESSION['user'] ?? null;
        if (!$user || ($user['VaiTro'] ?? '') !== 'Admin') {
            header('Location: index.php?action=login');
            exit;
        }

        require __DIR__ . '/../views/admin/DashboardView.php';
    }

    public function dashboardJson() {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'orders' => $this->donHangModel->getAllOrders(),
            'fundSummary' => $this->quyModel->getFundSummary(),
            'disbursements' => $this->tramModel->getDisbursements()
        ], JSON_UNESCAPED_UNICODE);
    }
}
