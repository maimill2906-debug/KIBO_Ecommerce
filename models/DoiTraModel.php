<?php
// ====================================================================
// MODEL: DoiTraModel.php
// Tương tác bảng yeu_cau_doi_tra (Chính sách đổi trả trong 3 ngày)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class DoiTraModel {
    private static $mockReturns = [
        [
            'MaYeuCau' => 'DT001',
            'MaDonHang' => 'KB-8921',
            'LyDoDoiTra' => 'Bao bì nguyên vẹn, muốn đổi sang dòng vị thịt gà cho mèo con',
            'HinhAnhMinhChung' => 'assets/images/returns/dt001.png',
            'NgayYeuCau' => '2026-10-26 15:30:00',
            'TrangThaiYeuCau' => 'Đã hoàn tất',
            'HuongXuLy' => 'Đổi sang mã BT003 và phụ thu chênh lệch'
        ]
    ];

    public static function getAll() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT mayeucau as \"MaYeuCau\", madonhang as \"MaDonHang\", 
                               lydodoitra as \"LyDoDoiTra\", hinhanhminhchung as \"HinhAnhMinhChung\", 
                               ngayyeucau as \"NgayYeuCau\", trangthaiyeucau as \"TrangThaiYeuCau\", 
                               huongxuly as \"HuongXuLy\" 
                        FROM yeu_cau_doi_tra 
                        ORDER BY ngayyeucau DESC";
                $rows = $db->query($sql)->fetchAll();
                if (!empty($rows)) {
                    return $rows;
                }
            } catch (Exception $e) {}
        }
        return self::$mockReturns;
    }

    public static function getByOrder($maDonHang) {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT mayeucau as \"MaYeuCau\", madonhang as \"MaDonHang\", 
                               lydodoitra as \"LyDoDoiTra\", hinhanhminhchung as \"HinhAnhMinhChung\", 
                               ngayyeucau as \"NgayYeuCau\", trangthaiyeucau as \"TrangThaiYeuCau\", 
                               huongxuly as \"HuongXuLy\" 
                        FROM yeu_cau_doi_tra 
                        WHERE madonhang = ?
                        ORDER BY ngayyeucau DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$maDonHang]);
                return $stmt->fetchAll();
            } catch (Exception $e) {}
        }
        return [];
    }

    public static function taoYeuCau($maDonHang, $lyDo, $hinhAnh = '') {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối CSDL'];
        if (empty($maDonHang) || empty($lyDo)) {
            return ['success' => false, 'message' => 'Vui lòng cung cấp mã đơn hàng và lý do đổi trả.'];
        }

        try {
            // Kiểm tra đơn hàng có tồn tại không
            $chk = $db->prepare("SELECT madonhang FROM don_hang WHERE madonhang = ? LIMIT 1");
            $chk->execute([$maDonHang]);
            if (!$chk->fetchColumn()) {
                return ['success' => false, 'message' => "Không tìm thấy đơn hàng #$maDonHang"];
            }

            // Sinh mã yêu cầu mới: DT + số
            $maxDT = $db->query("SELECT MAX(CAST(SUBSTRING(mayeucau FROM 3) AS INTEGER)) FROM yeu_cau_doi_tra WHERE mayeucau ~ '^DT[0-9]+$'")->fetchColumn();
            $nextNum = ($maxDT ? (int)$maxDT : 0) + 1;
            $maYC = 'DT' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

            $sql = "INSERT INTO yeu_cau_doi_tra (mayeucau, madonhang, lydodoitra, hinhanhminhchung, ngayyeucau, trangthaiyeucau, huongxuly)
                    VALUES (?, ?, ?, ?, NOW(), 'Chờ xử lý', 'Tiếp nhận xử lý')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$maYC, $maDonHang, $lyDo, $hinhAnh ?: 'assets/images/returns/default.png']);

            return ['success' => true, 'message' => "Gửi yêu cầu đổi trả thành công cho đơn #$maDonHang!", 'mayeucau' => $maYC];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}
