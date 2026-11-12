<?php
/**
 * CONTROLLER: ThanhToanController.php
 * Điều phối quy trình thanh toán trực tuyến VietQR / COD và đối soát giao dịch
 * Khớp Bảng 3.7 & Biểu đồ lớp Hình 3.16 (Chương 3 Báo cáo)
 */
require_once __DIR__ . '/../models/ThanhToanModel.php';
require_once __DIR__ . '/../models/CongThanhToan.php';

class ThanhToanController {
    public function index() {
        $_GET['step'] = 'checkout';
        require __DIR__ . '/../views/customer/ThanhToanView.php';
    }

    public function taoGiaoDich($maDonHang, $soTien, $phuongThuc = 'VietQR') {
        $qrUrl = CongThanhToan::taoMaVietQR($maDonHang, $soTien);
        return [
            'success' => true,
            'madonhang' => $maDonHang,
            'sotien' => (float)$soTien,
            'phuongthuc' => $phuongThuc,
            'vietqr_url' => $qrUrl
        ];
    }

    public function xacNhanThanhToan() {
        header('Content-Type: application/json; charset=utf-8');
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: $_POST;
        $id = $input['id'] ?? ($input['magiaodich'] ?? ($input['madonhang'] ?? ''));
        $res = ThanhToanModel::xacNhanNhanTien($id);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public function xuLyWebhook() {
        header('Content-Type: application/json; charset=utf-8');
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;
        $res = CongThanhToan::kiemTraGiaoDich($payload);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }
}
