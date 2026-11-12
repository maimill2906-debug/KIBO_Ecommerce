<?php
/**
 * CONTROLLER: QuyCuuTroController.php
 * Điều phối hiển thị minh bạch Quỹ cứu trợ 5%, tra cứu đóng góp theo mã đơn và giải ngân
 * Khớp Bảng 3.7 & Biểu đồ lớp Hình 3.16 (Chương 3 Báo cáo)
 */
require_once __DIR__ . '/../models/QuyCuuTroModel.php';
require_once __DIR__ . '/../models/TramCuuHoModel.php';

class QuyCuuTroController {
    public function index() {
        $this->xemBaoCaoQuy();
    }

    public function xemBaoCaoQuy() {
        $fundSummary = QuyCuuTroModel::getFundSummary();
        $recentContributions = QuyCuuTroModel::getRecentTransactions(10);
        $shelters = TramCuuHoModel::getShelters();
        $disbursements = TramCuuHoModel::getDisbursements();
        require __DIR__ . '/../views/customer/QuyCuuTroView.php';
    }

    public function traCuuTheoDon($maDonHang) {
        header('Content-Type: application/json; charset=utf-8');
        $res = QuyCuuTroModel::lookup($maDonHang);
        echo json_encode(['success' => (bool)$res, 'data' => $res], JSON_UNESCAPED_UNICODE);
    }

    public function giaiNgan() {
        header('Content-Type: application/json; charset=utf-8');
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $res = QuyCuuTroModel::giaiNgan(
            $input['matram'] ?? '',
            (float)($input['sotien'] ?? 0),
            $input['ngaychuyenkhoan'] ?? '',
            $input['mucdich'] ?? '',
            $input['filechungtu'] ?? ''
        );
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }
}
