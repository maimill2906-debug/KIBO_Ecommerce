<?php
// ====================================================================
// MODEL: DoiTraModel.php
// Tương tác bảng yeu_cau_doi_tra (Chính sách đổi trả trong 3 ngày)
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TrangThai.php';
require_once __DIR__ . '/IdGen.php';

class DoiTraModel {

    public static function getAll() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT mayeucau as \"MaYeuCau\", madonhang as \"MaDonHang\", 
                               lydodoitra as \"LyDoDoiTra\", hinhanhminhchung as \"HinhAnhMinhChung\", 
                               ngayyeucau as \"NgayYeuCau\", trangthaiyeucau as \"TrangThaiYeuCau\", 
                               huongxuly as \"HuongXuLy\", ngayxuly as \"NgayXuLy\"
                        FROM yeu_cau_doi_tra 
                        ORDER BY ngayyeucau DESC";
                return $db->query($sql)->fetchAll();
            } catch (Exception $e) {
                error_log("Lỗi getAll DoiTra: " . $e->getMessage());
            }
        }
        return [];
    }

    public static function getByOrder($maDonHang) {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT mayeucau as \"MaYeuCau\", madonhang as \"MaDonHang\", 
                               lydodoitra as \"LyDoDoiTra\", hinhanhminhchung as \"HinhAnhMinhChung\", 
                               ngayyeucau as \"NgayYeuCau\", trangthaiyeucau as \"TrangThaiYeuCau\", 
                               huongxuly as \"HuongXuLy\", ngayxuly as \"NgayXuLy\"
                        FROM yeu_cau_doi_tra 
                        WHERE madonhang = ?
                        ORDER BY ngayyeucau DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$maDonHang]);
                return $stmt->fetchAll();
            } catch (Exception $e) {
                error_log("Lỗi getByOrder DoiTra: " . $e->getMessage());
            }
        }
        return [];
    }

    public static function taoYeuCau($maDonHang, $lyDo, $hinhAnh = '', $maKH = '', $sdt = '') {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];

        $maDonHang = trim($maDonHang);
        $lyDo = trim($lyDo);
        $maKH = trim($maKH);
        $sdt = preg_replace('/\D+/', '', (string)$sdt);

        if (empty($maDonHang)) {
            return ['success' => false, 'message' => 'Vui lòng cung cấp mã đơn hàng.'];
        }
        if (mb_strlen($lyDo) < 10 || mb_strlen($lyDo) > 255) {
            return ['success' => false, 'message' => 'Lý do đổi trả phải có độ dài từ 10 đến 255 ký tự.'];
        }

        try {
            $db->beginTransaction();

            // 1. Khóa và kiểm tra đơn hàng cùng thông tin khách hàng (để biết có matk hay không)
            $stmt = $db->prepare("
                SELECT d.*, kh.matk, kh.makh as kh_makh 
                FROM don_hang d 
                JOIN khach_hang kh ON d.makh = kh.makh 
                WHERE d.madonhang = ? 
                FOR UPDATE
            ");
            $stmt->execute([$maDonHang]);
            $order = $stmt->fetch();

            $genericErr = "Không tìm thấy đơn hàng hoặc thông tin xác minh không đúng.";

            if (!$order) {
                throw new Exception($genericErr);
            }

            // 2. Xác thực quyền sở hữu đơn hàng (Bảo mật nghiêm ngặt)
            $isRegisteredAccount = !empty($order['matk']);
            if ($isRegisteredAccount) {
                // Khách có tài khoản: BẮT BUỘC phải đăng nhập và MaKH trong session phải khớp chính xác
                if (empty($maKH) || $order['makh'] !== $maKH) {
                    throw new Exception($genericErr);
                }
            } else {
                // Khách vãng lai (makh dạng KV... hoặc matk IS NULL):
                // Yêu cầu thêm SĐT và so khớp với sodienthoainhan sau khi bỏ ký tự không phải số
                $orderPhoneClean = preg_replace('/\D+/', '', (string)$order['sodienthoainhan']);
                $isMatchingSession = (!empty($maKH) && $order['makh'] === $maKH);
                $isMatchingPhone = (!empty($sdt) && $sdt === $orderPhoneClean);

                if (!$isMatchingSession && !$isMatchingPhone) {
                    throw new Exception($genericErr);
                }
            }

            // 3. Trạng thái đơn phải là 'Giao thành công'
            if ($order['trangthaidonhang'] !== TrangThai::DH_GIAO_THANH_CONG) {
                throw new Exception("Chỉ có thể yêu cầu đổi trả cho đơn hàng đã 'Giao thành công'.");
            }

            // 4. Kiểm tra thời hạn 3 ngày kể từ ngaygiaothanhcong
            $biz = require __DIR__ . '/../config/business.php';
            $soNgay = (int)($biz['doi_tra_so_ngay'] ?? 3);

            $giaoTime = !empty($order['ngaygiaothanhcong']) ? strtotime($order['ngaygiaothanhcong']) : strtotime($order['ngaydathang']);
            $diffSeconds = time() - $giaoTime;
            $maxSeconds = $soNgay * 86400;

            if ($diffSeconds > $maxSeconds) {
                throw new Exception("Đã quá thời hạn đổi trả {$soNgay} ngày kể từ khi nhận hàng thành công.");
            }

            // 5. Kiểm tra chưa có yêu cầu đổi trả nào còn hiệu lực
            $chkYC = $db->prepare("SELECT mayeucau, trangthaiyeucau FROM yeu_cau_doi_tra WHERE madonhang = ? AND trangthaiyeucau <> ? LIMIT 1");
            $chkYC->execute([$maDonHang, TrangThai::DT_TU_CHOI]);
            $existYC = $chkYC->fetch();

            if ($existYC) {
                throw new Exception("Đơn hàng #{$maDonHang} đã có yêu cầu đổi trả ({$existYC['trangthaiyeucau']}), không thể tạo thêm.");
            }

            // 6. Sinh mã DT mới bằng IdGen
            $maYC = IdGen::next($db, 'yeu_cau_doi_tra', 'mayeucau', 'DT', 3);
            $img = !empty($hinhAnh) ? trim($hinhAnh) : 'assets/images/returns/default.png';

            $sql = "INSERT INTO yeu_cau_doi_tra (mayeucau, madonhang, lydodoitra, hinhanhminhchung, ngayyeucau, trangthaiyeucau, huongxuly)
                    VALUES (?, ?, ?, ?, NOW(), ?, 'Chờ quản trị viên tiếp nhận xử lý')";
            $stmtInsert = $db->prepare($sql);
            $stmtInsert->execute([$maYC, $maDonHang, $lyDo, $img, TrangThai::DT_CHO_XU_LY]);

            $db->commit();
            return [
                'success' => true,
                'message' => "Gửi yêu cầu đổi trả thành công cho đơn #{$maDonHang}!",
                'mayeucau' => $maYC
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Database error in taoYeuCau DoiTraModel: " . $e->getMessage());
            return ['success' => false, 'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function xuLy($maYeuCau, $hanhDong, $ghiChu = '', $nhapLaiKho = true) {
        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];

        $maYeuCau = trim($maYeuCau);
        $hanhDong = trim($hanhDong);
        $ghiChu = trim($ghiChu);

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT * FROM yeu_cau_doi_tra WHERE mayeucau = ? FOR UPDATE");
            $stmt->execute([$maYeuCau]);
            $yc = $stmt->fetch();

            if (!$yc) {
                throw new Exception("Không tìm thấy yêu cầu đổi trả '{$maYeuCau}'.");
            }
            if ($yc['trangthaiyeucau'] !== TrangThai::DT_CHO_XU_LY) {
                throw new Exception("Yêu cầu này đã được xử lý trước đó (Trạng thái: {$yc['trangthaiyeucau']}).");
            }

            $maDonHang = $yc['madonhang'];

            if ($hanhDong === 'doi') {
                $newStatus = TrangThai::DT_DONG_Y_DOI;
                $huongXuLy = $ghiChu ?: 'Đã đồng ý đổi hàng cho khách';
                $stmtUp = $db->prepare("UPDATE yeu_cau_doi_tra SET trangthaiyeucau = ?, huongxuly = ?, ngayxuly = NOW() WHERE mayeucau = ?");
                $stmtUp->execute([$newStatus, $huongXuLy, $maYeuCau]);
            } elseif ($hanhDong === 'hoantien') {
                $newStatus = TrangThai::DT_HOAN_TIEN;
                $huongXuLy = $ghiChu ?: 'Đã hoàn tiền cho khách hàng';
                $stmtUp = $db->prepare("UPDATE yeu_cau_doi_tra SET trangthaiyeucau = ?, huongxuly = ?, ngayxuly = NOW() WHERE mayeucau = ?");
                $stmtUp->execute([$newStatus, $huongXuLy, $maYeuCau]);

                // 1. Ghi nhận trạng thái thanh toán -> 'Hoàn tiền'
                $stmtTT = $db->prepare("UPDATE thanh_toan SET trangthaitt = ?, thoigiantt = NOW() WHERE madonhang = ?");
                $stmtTT->execute([TrangThai::TT_HOAN_TIEN, $maDonHang]);

                // 2. Hoàn lại tồn kho cho từng sản phẩm trong đơn (chỉ khi được phép nhập lại kho)
                if ($nhapLaiKho) {
                    $stmtItems = $db->prepare("SELECT mabienthe, soluong FROM chi_tiet_don_hang WHERE madonhang = ?");
                    $stmtItems->execute([$maDonHang]);
                    $lines = $stmtItems->fetchAll();
                    $stmtStock = $db->prepare("UPDATE bien_the SET soluongton = soluongton + ? WHERE mabienthe = ?");
                    foreach ($lines as $line) {
                        $stmtStock->execute([(int)$line['soluong'], $line['mabienthe']]);
                    }
                }

                // 3. Trừ lại số điểm tích lũy đã cộng cho khách (nếu có tài khoản)
                $stmtOrder = $db->prepare("SELECT makh, tongthanhtoan FROM don_hang WHERE madonhang = ?");
                $stmtOrder->execute([$maDonHang]);
                $orderInfo = $stmtOrder->fetch();
                if ($orderInfo && !empty($orderInfo['makh'])) {
                    $biz = require __DIR__ . '/../config/business.php';
                    $diemMoiDong = (int)($biz['diem_moi_dong'] ?? 10000);
                    $diemDaCong = (int)floor((float)$orderInfo['tongthanhtoan'] / $diemMoiDong);
                    if ($diemDaCong > 0) {
                        $stmtDeductPoints = $db->prepare("UPDATE khach_hang SET diemtichluy = GREATEST(0, COALESCE(diemtichluy, 0) - ?) WHERE makh = ? AND matk IS NOT NULL");
                        $stmtDeductPoints->execute([$diemDaCong, $orderInfo['makh']]);
                    }
                }

                // 4. Hủy phần đóng góp quỹ của đơn nếu chưa giải ngân
                $stmtQuy = $db->prepare("UPDATE quy_cuu_tro SET trangthaiquy = ? WHERE madonhang = ? AND trangthaiquy <> ?");
                $stmtQuy->execute([TrangThai::QUY_DA_HUY, $maDonHang, TrangThai::QUY_DA_GIAI_NGAN]);
            } elseif ($hanhDong === 'tuchoi') {
                if (empty($ghiChu)) {
                    throw new Exception("Bắt buộc phải nhập lý do khi từ chối yêu cầu đổi trả.");
                }
                $newStatus = TrangThai::DT_TU_CHOI;
                $stmtUp = $db->prepare("UPDATE yeu_cau_doi_tra SET trangthaiyeucau = ?, huongxuly = ?, ngayxuly = NOW() WHERE mayeucau = ?");
                $stmtUp->execute([$newStatus, $ghiChu, $maYeuCau]);
            } else {
                throw new Exception("Hành động xử lý '{$hanhDong}' không hợp lệ.");
            }

            $db->commit();
            return [
                'success' => true,
                'message' => "Xử lý yêu cầu đổi trả thành công ({$newStatus}).",
                'mayeucau' => $maYeuCau,
                'trangthai' => $newStatus
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Database error in xuLy DoiTraModel: " . $e->getMessage());
            return ['success' => false, 'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
