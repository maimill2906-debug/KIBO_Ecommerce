<?php
// ====================================================================
// MODEL: DonHangModel.php
// Tương tác bảng don_hang, chi_tiet_don_hang, quy_cuu_tro, thanh_toan
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/BusinessException.php';
require_once __DIR__ . '/TrangThai.php';

class DonHangModel {
    public static function tinhQuy($tongTienHang, $tienGiam = 0) {
        static $tyLe = null;
        if ($tyLe === null) {
            $business = require __DIR__ . '/../config/business.php';
            if (!isset($business['quy_ty_le'])) {
                throw new RuntimeException("Thiếu cấu hình bắt buộc 'quy_ty_le' trong config/business.php");
            }
            $tyLe = (float)$business['quy_ty_le'];
        }
        $net = max(0, $tongTienHang - $tienGiam);
        return (float)round($net * $tyLe);
    }

    public static function tinhPhiShip($tongTienHang, $diaChiNhan = '', $quanHuyen = '', $tinhThanh = '') {
        $biz = require __DIR__ . '/../config/business.php';
        $shipConf = $biz['ship'] ?? ['phi_co_ban' => 20000, 'mien_phi_noi_thanh' => 200000, 'mien_phi_khac' => 500000];
        $noiThanhArr = $biz['noi_thanh_hcm'] ?? [];
        $tinhHcmArr = $biz['tinh_thanh_hcm'] ?? ['Hồ Chí Minh', 'TP. Hồ Chí Minh', 'TP Hồ Chí Minh', 'TP.HCM', 'TPHCM', 'Thành phố Hồ Chí Minh', 'HCM'];

        $cleanTinh = trim($tinhThanh);
        $cleanQuan = trim($quanHuyen);

        // 1. Xác định tỉnh/thành có phải TP.HCM không
        $isHCM = false;
        if (!empty($cleanTinh)) {
            foreach ($tinhHcmArr as $hcm) {
                if (strcasecmp($cleanTinh, $hcm) === 0) {
                    $isHCM = true;
                    break;
                }
            }
        } elseif (!empty($diaChiNhan)) {
            foreach ($tinhHcmArr as $hcm) {
                if (stripos($diaChiNhan, $hcm) !== false) {
                    $isHCM = true;
                    break;
                }
            }
        }

        // 2. Chỉ khi là TP.HCM mới xét tiếp nội thành (so khớp chính xác, không dùng stripos)
        $isNoiThanh = false;
        if ($isHCM) {
            if (!empty($cleanQuan)) {
                foreach ($noiThanhArr as $dist) {
                    $shortDist = preg_replace('/^(Quận|Thành phố)\s+/iu', '', $dist);
                    if (strcasecmp($cleanQuan, $dist) === 0 || strcasecmp($cleanQuan, $shortDist) === 0) {
                        $isNoiThanh = true;
                        break;
                    }
                }
            }
            // Fallback nếu không có quanhuyen riêng mà chỉ có diaChiNhan: dùng regex ranh giới từ nguyên vẹn \b
            if (!$isNoiThanh && !empty($diaChiNhan)) {
                foreach ($noiThanhArr as $dist) {
                    $pattern = '/\b' . preg_quote($dist, '/') . '\b/iu';
                    if (preg_match($pattern, $diaChiNhan)) {
                        $isNoiThanh = true;
                        break;
                    }
                }
            }
        }

        $shipThreshold = $isNoiThanh ? (float)$shipConf['mien_phi_noi_thanh'] : (float)$shipConf['mien_phi_khac'];
        return ($tongTienHang >= $shipThreshold) ? 0.0 : (float)$shipConf['phi_co_ban'];
    }

    public static function getAll() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT 
                            dh.madonhang,
                            dh.makh,
                            dh.mavoucher,
                            dh.ngaydathang,
                            dh.tongtienhang,
                            dh.tiengiamgia,
                            dh.phivanchuyen,
                            dh.tongthanhtoan,
                            dh.trangthaidonhang,
                            dh.tennguoinhan,
                            dh.sodienthoainhan,
                            dh.diachigiaohang,
                            dh.ghichu,
                            qct.sotiendonggop,
                            qct.matracuu,
                            qct.trangthaiquy,
                            tch.tentram
                        FROM don_hang dh
                        LEFT JOIN quy_cuu_tro qct ON dh.madonhang = qct.madonhang
                        LEFT JOIN chung_tu_giai_ngan ct ON qct.machungtu = ct.machungtu
                        LEFT JOIN tram_cuu_ho tch ON ct.matram = tch.matram
                        ORDER BY dh.ngaydathang DESC";
                $rows = $db->query($sql)->fetchAll();
                if ($rows && count($rows) > 0) {
                    $mapped = [];
                    foreach ($rows as $r) {
                        $maDH = $r['madonhang'];
                        $items = self::getOrderItems($maDH, $db);
                        $statusRaw = $r['trangthaidonhang'];
                        $statusText = ($statusRaw === 'Giao thành công') ? 'Giao hàng thành công' : 
                                      (($statusRaw === 'Đang giao') ? 'Đang giao hàng' : $statusRaw);

                        $mapped[] = [
                            'orderId' => $maDH,
                            'customerName' => $r['tennguoinhan'] ?? 'Khách hàng KIBO',
                            'phone' => $r['sodienthoainhan'] ?? '',
                            'address' => $r['diachigiaohang'] ?? '',
                            'items' => $items,
                            'subtotal' => (float)$r['tongtienhang'],
                            'shipping' => (float)$r['phivanchuyen'],
                            'discount' => (float)$r['tiengiamgia'],
                            'total' => (float)$r['tongthanhtoan'],
                            'fundAmount' => (float)($r['sotiendonggop'] ?? self::tinhQuy($r['tongtienhang'], $r['tiengiamgia'])),
                            'lookupCode' => $r['matracuu'] ?? $maDH,
                            'status' => $statusText,
                            'carrierStatus' => ($statusRaw === 'Giao thành công' ? 'Đã giao thành công' : 'Đơn vị vận chuyển đang xử lý'),
                            'date' => date('d/m/Y', strtotime($r['ngaydathang'])),
                            'rawDate' => $r['ngaydathang'],
                            'allocatedStation' => $r['tentram'] ?? null,
                            'fundStatus' => $r['trangthaiquy'] ?? 'Đã tích lũy'
                        ];
                    }
                    return $mapped;
                }
            } catch (Exception $e) {
                error_log("Lỗi getOrdersByCustomer DonHangModel: " . $e->getMessage());
            }
        }
        return [];
    }

    public static function getOrderItems($maDonHang, $db = null) {
        if (!$db) {
            $db = Database::getConnection();
        }
        if (!$db) return [];

        try {
            $sql = "SELECT ctdh.*, bt.tenbienthe, sp.tensp, bt.hinhanhbienthe
                    FROM chi_tiet_don_hang ctdh
                    JOIN bien_the bt ON ctdh.mabienthe = bt.mabienthe
                    JOIN san_pham sp ON bt.masp = sp.masp
                    WHERE ctdh.madonhang = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$maDonHang]);
            $rows = $stmt->fetchAll();

            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'name' => $row['tensp'],
                    'variant' => $row['tenbienthe'],
                    'qty' => (int)$row['soluong'],
                    'price' => (float)$row['dongia']
                ];
            }
            return $items;
        } catch (Exception $e) {
            return [];
        }
    }

    public static function traCuu($maDonHang) {
        $maDonHang = trim($maDonHang);
        if (empty($maDonHang)) return null;

        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT 
                            dh.*,
                            qct.sotiendonggop,
                            qct.matracuu,
                            qct.trangthaiquy,
                            tch.tentram
                        FROM don_hang dh
                        LEFT JOIN quy_cuu_tro qct ON dh.madonhang = qct.madonhang
                        LEFT JOIN chung_tu_giai_ngan ct ON qct.machungtu = ct.machungtu
                        LEFT JOIN tram_cuu_ho tch ON ct.matram = tch.matram
                        WHERE dh.madonhang = ? OR qct.matracuu = ?
                        LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$maDonHang, $maDonHang]);
                $r = $stmt->fetch();

                if ($r) {
                    $items = self::getOrderItems($r['madonhang'], $db);
                    $statusRaw = $r['trangthaidonhang'];
                    $statusText = ($statusRaw === 'Giao thành công') ? 'Giao hàng thành công' : 
                                  (($statusRaw === 'Đang giao') ? 'Đang giao hàng' : $statusRaw);

                    return [
                        'orderId' => $r['madonhang'],
                        'customerName' => $r['tennguoinhan'] ?? 'Khách hàng',
                        'phone' => $r['sodienthoainhan'] ?? '',
                        'address' => $r['diachigiaohang'] ?? '',
                        'items' => $items,
                        'subtotal' => (float)$r['tongtienhang'],
                        'shipping' => (float)$r['phivanchuyen'],
                        'discount' => (float)$r['tiengiamgia'],
                        'total' => (float)$r['tongthanhtoan'],
                        'fundAmount' => (float)($r['sotiendonggop'] ?? self::tinhQuy($r['tongtienhang'], $r['tiengiamgia'])),
                        'lookupCode' => $r['matracuu'] ?? $r['madonhang'],
                        'status' => $statusText,
                        'carrierStatus' => ($statusRaw === 'Giao thành công' ? 'Đã giao thành công' : 'Đơn vị vận chuyển đang xử lý'),
                        'date' => date('d/m/Y', strtotime($r['ngaydathang'])),
                        'allocatedStation' => $r['tentram'] ?? null,
                        'fundStatus' => $r['trangthaiquy'] ?? 'Đã tích lũy'
                    ];
                }
            } catch (Exception $e) {
                error_log("Lỗi getOrdersByCustomer DonHangModel: " . $e->getMessage());
            }
        }

        return null;
    }

    public static function getOrderByCode($maDonHang) {
        return self::traCuu($maDonHang);
    }

    public static function taoDonHang($orderData, $maKHOption = null) {
        $db = Database::getConnection();
        if (!$db) {
            return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];
        }

        try {
            $db->beginTransaction();

            // 1. Validate người nhận
            $tenNhan = trim($orderData['tennguoinhan'] ?? ($orderData['customer']['name'] ?? ''));
            $sdtNhan = preg_replace('/[^\d]/', '', trim($orderData['sodienthoainhan'] ?? ($orderData['customer']['phone'] ?? '')));
            if (mb_strlen($tenNhan) < 2) {
                throw new BusinessException('Họ và tên người nhận phải có ít nhất 2 ký tự.');
            }
            if (!preg_match('/^0[0-9]{9}$/', $sdtNhan)) {
                throw new BusinessException('Số điện thoại người nhận không hợp lệ (phải bắt đầu bằng 0 và gồm 10 chữ số).');
            }

            // 2. Validate phương thức thanh toán
            $phuongThucRaw = trim($orderData['phuongthuctt'] ?? ($orderData['phuongthuc'] ?? ($orderData['paymentMethod'] ?? 'cod')));
            if (strcasecmp($phuongThucRaw, 'cod') === 0 || stripos($phuongThucRaw, 'khi nhận hàng') !== false) {
                $phuongThuc = 'Thanh toán khi nhận hàng (COD)';
            } elseif (strcasecmp($phuongThucRaw, 'vietqr') === 0 || stripos($phuongThucRaw, 'VietQR') !== false) {
                $phuongThuc = 'Chuyển khoản VietQR';
            } else {
                $phuongThuc = $phuongThucRaw;
            }
            $allowedPT = ['Thanh toán khi nhận hàng (COD)', 'Chuyển khoản VietQR'];
            if (!in_array($phuongThuc, $allowedPT, true)) {
                throw new BusinessException('Phương thức thanh toán không hợp lệ.');
            }

            // 3. Validate items giỏ hàng
            $rawItems = $orderData['items'] ?? [];
            if (empty($rawItems) || !is_array($rawItems)) {
                throw new BusinessException('Giỏ hàng trống! Vui lòng chọn sản phẩm.');
            }

            // Gộp item trùng mã biến thể
            $consolidatedItems = [];
            foreach ($rawItems as $it) {
                $id = trim($it['id'] ?? ($it['mabienthe'] ?? ''));
                $qty = (int)($it['quantity'] ?? ($it['qty'] ?? 1));
                if ($id === '' || $qty < 1 || $qty > 99) {
                    throw new BusinessException("Số lượng sản phẩm không hợp lệ (cho phép từ 1 đến 99).");
                }
                if (!preg_match('/^BT[0-9]+$/', $id)) {
                    throw new BusinessException("Mã sản phẩm '{$id}' không đúng định dạng biến thể hợp lệ.");
                }
                $consolidatedItems[$id] = ($consolidatedItems[$id] ?? 0) + $qty;
            }

            // 4. Khóa & kiểm tra tồn kho từng sản phẩm, tính tiền hàng từ server
            $tongTienHang = 0;
            $orderLines = [];
            $stmtBT = $db->prepare("SELECT bt.mabienthe, bt.giaban, bt.soluongton, sp.tensp, sp.trangthai 
                                    FROM bien_the bt 
                                    JOIN san_pham sp ON bt.masp = sp.masp 
                                    WHERE bt.mabienthe = ? 
                                    FOR UPDATE");
            $stmtDeductBT = $db->prepare("UPDATE bien_the SET soluongton = soluongton - ? WHERE mabienthe = ?");

            foreach ($consolidatedItems as $maBT => $qty) {
                $stmtBT->execute([$maBT]);
                $btRow = $stmtBT->fetch();
                if (!$btRow) {
                    throw new BusinessException("Biến thể sản phẩm '{$maBT}' không tồn tại trong hệ thống.");
                }
                if ($btRow['trangthai'] !== 'Đang bán') {
                    throw new BusinessException("Sản phẩm '{$btRow['tensp']}' hiện đã ngừng kinh doanh.");
                }
                if ((int)$btRow['soluongton'] < $qty) {
                    throw new BusinessException("Sản phẩm '{$btRow['tensp']}' không đủ số lượng tồn kho (còn {$btRow['soluongton']}, yêu cầu {$qty}).");
                }

                // Trừ tồn kho
                $stmtDeductBT->execute([$qty, $maBT]);

                $dongia = (float)$btRow['giaban'];
                $thanhtien = $dongia * $qty;
                $tongTienHang += $thanhtien;

                $orderLines[] = [
                    'mabienthe' => $maBT,
                    'soluong' => $qty,
                    'dongia' => $dongia,
                    'thanhtien' => $thanhtien
                ];
            }

            // 5. Địa chỉ cấu trúc & phân loại vùng freeship
            $tinhThanh = trim($orderData['tinhthanh'] ?? '');
            $quanHuyen = trim($orderData['quanhuyen'] ?? '');
            $phuongXa  = trim($orderData['phuongxa'] ?? '');
            $dcChiTiet = trim($orderData['diachichitiet'] ?? ($orderData['diachigiaohang'] ?? ($orderData['customer']['address'] ?? '')));
            $diaChiNhan = trim($orderData['diachigiaohang'] ?? ($orderData['customer']['address'] ?? ''));
            if (empty($diaChiNhan)) {
                $diaChiNhan = implode(', ', array_filter([$dcChiTiet, $phuongXa, $quanHuyen, $tinhThanh]));
            }
            if (empty($diaChiNhan)) {
                $diaChiNhan = 'TP. Hồ Chí Minh';
            }

            $phiShip = self::tinhPhiShip($tongTienHang, $diaChiNhan, $quanHuyen, $tinhThanh);

            // 6. Kiểm tra & Áp dụng Voucher (nếu có)
            $tienGiam = 0.0;
            $maVoucher = !empty($orderData['mavoucher']) ? trim($orderData['mavoucher']) : (!empty($orderData['voucherCode']) ? trim($orderData['voucherCode']) : null);
            if ($maVoucher) {
                $stmtV = $db->prepare("SELECT * FROM voucher WHERE UPPER(mavoucher) = UPPER(?) FOR UPDATE");
                $stmtV->execute([$maVoucher]);
                $vRow = $stmtV->fetch();

                if (!$vRow) {
                    throw new BusinessException("Mã giảm giá '{$maVoucher}' không tồn tại trong hệ thống.");
                }
                if (isset($vRow['kichhoat']) && !$vRow['kichhoat']) {
                    throw new BusinessException("Mã giảm giá '{$maVoucher}' hiện đang bị tạm khóa.");
                }
                $nowTs = time();
                if (!empty($vRow['ngaybatdau']) && strtotime($vRow['ngaybatdau']) > $nowTs) {
                    throw new BusinessException("Mã giảm giá '{$maVoucher}' chưa đến thời gian áp dụng.");
                }
                if (!empty($vRow['ngayketthuc']) && strtotime($vRow['ngayketthuc']) < $nowTs) {
                    throw new BusinessException("Mã giảm giá '{$maVoucher}' đã hết hạn sử dụng.");
                }
                if ((int)$vRow['soluongconlai'] <= 0) {
                    throw new BusinessException("Mã giảm giá '{$maVoucher}' đã hết lượt sử dụng.");
                }
                $minOrder = (float)($vRow['donhangtoithieu'] ?? 0);
                if ($tongTienHang < $minOrder) {
                    throw new BusinessException("Đơn hàng tối thiểu để áp dụng mã '{$maVoucher}' là " . number_format($minOrder, 0, ',', '.') . "đ.");
                }

                // Trừ lượt dùng voucher
                $stmtUseV = $db->prepare("UPDATE voucher SET soluongconlai = soluongconlai - 1 WHERE mavoucher = ? AND soluongconlai > 0");
                $stmtUseV->execute([$vRow['mavoucher']]);
                if ($stmtUseV->rowCount() !== 1) {
                    throw new BusinessException("Mã giảm giá đã hết lượt sử dụng ngay trước khi thanh toán.");
                }

                $loaiGiam = $vRow['loaigiamgia'] ?? 'SoTien';
                $valGiam = (float)($vRow['giatrigiam'] ?? 0);
                if ($loaiGiam === 'PhanTram') {
                    $tienGiam = round($tongTienHang * ($valGiam / 100));
                    $maxDiscount = !empty($vRow['giamtoida']) ? (float)$vRow['giamtoida'] : null;
                    if ($maxDiscount !== null && $maxDiscount > 0) {
                        $tienGiam = min($tienGiam, $maxDiscount);
                    }
                } else {
                    $tienGiam = $valGiam;
                }
                $tienGiam = min($tongTienHang, $tienGiam);
                $maVoucher = $vRow['mavoucher'];

                // Giới hạn 1 lần sử dụng cho mỗi khách hàng có tài khoản
                $tempCustomerCheck = $maKHOption ?: (!empty($_SESSION['user']['MaKH']) ? $_SESSION['user']['MaKH'] : null);
                if (!empty($tempCustomerCheck)) {
                    $chkUsed = $db->prepare("SELECT 1 FROM don_hang WHERE makh = ? AND UPPER(mavoucher) = UPPER(?) AND trangthaidonhang <> ? LIMIT 1");
                    $chkUsed->execute([$tempCustomerCheck, $maVoucher, TrangThai::DH_DA_HUY]);
                    if ($chkUsed->fetch()) {
                        throw new BusinessException("Mỗi tài khoản chỉ được áp dụng mã giảm giá '{$maVoucher}' 1 lần.");
                    }
                }
            }

            // 7. Tổng thanh toán & Tiền Quỹ 5%
            $tongThanhToan = max(0, $tongTienHang - $tienGiam + $phiShip);
            $tienDongGop = self::tinhQuy($tongTienHang, $tienGiam);

            // 8. Định danh khách hàng (truyền trực tiếp, session hoặc khách vãng lai)
            $maKH = $maKHOption ?: (!empty($_SESSION['user']['MaKH']) ? $_SESSION['user']['MaKH'] : null);
            if (!$maKH) {
                // Khách vãng lai: CHỈ tìm khách vãng lai cũ có matk IS NULL (tuyệt đối không gắn vào tài khoản có matk)
                $findKH = $db->prepare("SELECT kh.makh 
                                        FROM khach_hang kh 
                                        JOIN sdt_kh s ON kh.makh = s.makh 
                                        WHERE s.sodienthoai = ? 
                                          AND kh.matk IS NULL 
                                        LIMIT 1");
                $findKH->execute([$sdtNhan]);
                $existMaKH = $findKH->fetchColumn();
                if ($existMaKH) {
                    $maKH = $existMaKH;
                    // Đảm bảo sdt_kh đã có
                    $chkSDT = $db->prepare("SELECT 1 FROM sdt_kh WHERE makh = ? AND sodienthoai = ?");
                    $chkSDT->execute([$maKH, $sdtNhan]);
                    if (!$chkSDT->fetch()) {
                        $db->prepare("INSERT INTO sdt_kh (makh, sodienthoai) VALUES (?, ?)")->execute([$maKH, $sdtNhan]);
                    }
                } else {
                    require_once __DIR__ . '/IdGen.php';
                    $maKH = IdGen::next($db, 'khach_hang', 'makh', 'KV', 3);
                    $guestEmail = 'guest_' . $sdtNhan . '_' . strtolower($maKH) . '@kibo.local';
                    $stmtNewGuest = $db->prepare("INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, NULL, ?, ?, 0)");
                    $stmtNewGuest->execute([$maKH, $tenNhan, $guestEmail]);

                    $stmtNewSDT = $db->prepare("INSERT INTO sdt_kh (makh, sodienthoai) VALUES (?, ?)");
                    $stmtNewSDT->execute([$maKH, $sdtNhan]);

                    if (!empty($diaChiNhan)) {
                        $maDC = IdGen::next($db, 'dia_chi_kh', 'madiachi', 'DC', 3);
                        $stmtNewDC = $db->prepare("INSERT INTO dia_chi_kh (madiachi, makh, diachichitiet, phuongxa, quanhuyen, tinhthanh, macdinh) VALUES (?, ?, ?, ?, ?, ?, true)");
                        $stmtNewDC->execute([$maDC, $maKH, $dcChiTiet ?: $diaChiNhan, $phuongXa, $quanHuyen, $tinhThanh]);
                    }
                }
            }

            // 9. Sinh mã đơn hàng 'KB-' . date('ymd') . rand(1000,9999), thử lại tối đa 5 lần nếu trùng
            $maDonHang = null;
            $chkDH = $db->prepare("SELECT 1 FROM don_hang WHERE madonhang = ? LIMIT 1");
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = 'KB-' . date('ymd') . rand(1000, 9999);
                $chkDH->execute([$candidate]);
                if (!$chkDH->fetch()) {
                    $maDonHang = $candidate;
                    break;
                }
            }
            if (!$maDonHang) {
                $maDonHang = 'KB-' . date('ymd') . rand(1000, 9999);
            }

            // 10. Ghi don_hang
            require_once __DIR__ . '/TrangThai.php';
            $ghiChu = trim($orderData['ghichu'] ?? '');
            $sqlDH = "INSERT INTO don_hang (madonhang, makh, mavoucher, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang, ghichu)
                      VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtDH = $db->prepare($sqlDH);
            $stmtDH->execute([
                $maDonHang,
                $maKH,
                $maVoucher,
                $tongTienHang,
                $tienGiam,
                $phiShip,
                $tongThanhToan,
                TrangThai::DH_CHO_XAC_NHAN,
                $tenNhan,
                $sdtNhan,
                $diaChiNhan,
                $ghiChu
            ]);

            // 11. Ghi chi_tiet_don_hang
            $sqlCT = "INSERT INTO chi_tiet_don_hang (madonhang, mabienthe, soluong, dongia, thanhtien) VALUES (?, ?, ?, ?, ?)";
            $stmtCT = $db->prepare($sqlCT);
            foreach ($orderLines as $ol) {
                $stmtCT->execute([$maDonHang, $ol['mabienthe'], $ol['soluong'], $ol['dongia'], $ol['thanhtien']]);
            }

            // 12. Ghi quy_cuu_tro (trạng thái Chờ trích quỹ)
            require_once __DIR__ . '/IdGen.php';
            $maDongGop = IdGen::next($db, 'quy_cuu_tro', 'madonggop', 'QCT', 3);
            $sqlQuy = "INSERT INTO quy_cuu_tro (madonggop, madonhang, sotiendonggop, ngayghinhan, matracuu, trangthaiquy, machungtu)
                       VALUES (?, ?, ?, NOW(), ?, ?, NULL)";
            $stmtQuy = $db->prepare($sqlQuy);
            $stmtQuy->execute([$maDongGop, $maDonHang, $tienDongGop, $maDonHang, TrangThai::QUY_CHO_TRICH]);

            // 13. Ghi thanh_toan (trạng thái Chờ thanh toán)
            $maGD = IdGen::next($db, 'thanh_toan', 'magiaodich', 'GD', 3);
            $sqlTT = "INSERT INTO thanh_toan (magiaodich, madonhang, phuongthuctt, sotien, thoigiantt, trangthaitt)
                      VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmtTT = $db->prepare($sqlTT);
            $stmtTT->execute([$maGD, $maDonHang, $phuongThuc, $tongThanhToan, TrangThai::TT_CHO_THANH_TOAN]);

            // 14. Commit transaction
            $db->commit();

            return [
                'success' => true,
                'orderId' => $maDonHang,
                'order_code' => $maDonHang,
                'tongthanhtoan' => $tongThanhToan,
                'ship' => $phiShip,
                'fund_amount' => $tienDongGop
            ];
        } catch (BusinessException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Database error in taoDonHang: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Unexpected error in taoDonHang: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'
            ];
        }
    }

    public static function capNhatTrangThai($maDonHang, $trangThaiMoi) {
        $db = Database::getConnection();
        if (!$db) {
            return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];
        }

        try {
            $db->beginTransaction();

            require_once __DIR__ . '/TrangThai.php';
            $statusMap = [
                'Đang giao hàng' => TrangThai::DH_DANG_GIAO,
                'Giao hàng thành công' => TrangThai::DH_GIAO_THANH_CONG,
                'Đã huỷ' => TrangThai::DH_DA_HUY
            ];
            $normalizedStatus = $statusMap[$trangThaiMoi] ?? $trangThaiMoi;

            if (!in_array($normalizedStatus, TrangThai::getDanhSachDonHang(), true)) {
                throw new BusinessException("Trạng thái '{$trangThaiMoi}' không hợp lệ.");
            }

            // 1. Khóa dòng đơn hàng
            $stmtDH = $db->prepare("SELECT * FROM don_hang WHERE madonhang = ? FOR UPDATE");
            $stmtDH->execute([$maDonHang]);
            $order = $stmtDH->fetch();
            if (!$order) {
                throw new BusinessException("Không tìm thấy đơn hàng '{$maDonHang}'.");
            }

            $currentStatus = $order['trangthaidonhang'];

            if ($currentStatus === $normalizedStatus) {
                return ['success' => true, 'message' => "Đơn hàng đã ở trạng thái '{$currentStatus}'."];
            }

            // Quy định chuyển trạng thái nghiêm ngặt theo luồng chuẩn (chặn tuyệt đối việc nhảy cóc)
            $allowedTransitions = [
                TrangThai::DH_CHO_XAC_NHAN => [TrangThai::DH_CHO_DONG_GOI, TrangThai::DH_DA_HUY],
                TrangThai::DH_CHO_DONG_GOI => [TrangThai::DH_DA_XUAT_KHO, TrangThai::DH_DA_HUY],
                TrangThai::DH_DA_XUAT_KHO  => [TrangThai::DH_DANG_GIAO],
                TrangThai::DH_DANG_GIAO    => [TrangThai::DH_GIAO_THANH_CONG],
                TrangThai::DH_GIAO_THANH_CONG => [],
                TrangThai::DH_DA_HUY       => []
            ];

            if (!isset($allowedTransitions[$currentStatus]) || !in_array($normalizedStatus, $allowedTransitions[$currentStatus], true)) {
                throw new BusinessException("Không thể chuyển trạng thái đơn hàng từ '{$currentStatus}' sang '{$normalizedStatus}'.");
            }

            // 2. Xử lý chuyển trạng thái
            if ($normalizedStatus === TrangThai::DH_GIAO_THANH_CONG) {
                // a. Cập nhật ngaygiaothanhcong
                $stmtUp = $db->prepare("UPDATE don_hang SET trangthaidonhang = ?, ngaygiaothanhcong = NOW() WHERE madonhang = ?");
                $stmtUp->execute([$normalizedStatus, $maDonHang]);

                // b. Quỹ Chờ trích quỹ -> Đã trích quỹ
                $stmtQuy = $db->prepare("UPDATE quy_cuu_tro SET trangthaiquy = ? WHERE madonhang = ? AND trangthaiquy = ?");
                $stmtQuy->execute([TrangThai::QUY_DA_TRICH, $maDonHang, TrangThai::QUY_CHO_TRICH]);

                // c. Nếu COD thì thanh_toan -> Thành công
                $stmtTT = $db->prepare("UPDATE thanh_toan SET trangthaitt = ?, thoigiantt = NOW() WHERE madonhang = ? AND trangthaitt = ?");
                $stmtTT->execute([TrangThai::TT_THANH_CONG, $maDonHang, TrangThai::TT_CHO_THANH_TOAN]);

                // d. Cộng điểm tích lũy đúng 1 lần (1 điểm / 10.000đ của tongthanhtoan)
                $maKH = $order['makh'];
                if ($maKH) {
                    $biz = require __DIR__ . '/../config/business.php';
                    $diemMoiDong = (int)($biz['diem_moi_dong'] ?? 10000);
                    $diemCong = (int)floor((float)$order['tongthanhtoan'] / $diemMoiDong);
                    if ($diemCong > 0) {
                        $stmtDiem = $db->prepare("UPDATE khach_hang SET diemtichluy = COALESCE(diemtichluy, 0) + ? WHERE makh = ? AND matk IS NOT NULL");
                        $stmtDiem->execute([$diemCong, $maKH]);
                    }
                }
            } elseif ($normalizedStatus === TrangThai::DH_DA_HUY) {
                // a. Cập nhật đơn hàng -> Đã hủy
                $stmtUp = $db->prepare("UPDATE don_hang SET trangthaidonhang = ? WHERE madonhang = ?");
                $stmtUp->execute([$normalizedStatus, $maDonHang]);

                // b. Quỹ -> Đã hủy
                $stmtQuy = $db->prepare("UPDATE quy_cuu_tro SET trangthaiquy = ? WHERE madonhang = ?");
                $stmtQuy->execute([TrangThai::QUY_DA_HUY, $maDonHang]);

                // c. Hoàn lại tồn kho cho từng biến thể
                $stmtCT = $db->prepare("SELECT mabienthe, soluong FROM chi_tiet_don_hang WHERE madonhang = ?");
                $stmtCT->execute([$maDonHang]);
                $items = $stmtCT->fetchAll();

                $stmtRestoreStock = $db->prepare("UPDATE bien_the SET soluongton = soluongton + ? WHERE mabienthe = ?");
                foreach ($items as $it) {
                    $stmtRestoreStock->execute([(int)$it['soluong'], $it['mabienthe']]);
                }

                // d. Hoàn lại lượt dùng voucher (nếu có)
                if (!empty($order['mavoucher'])) {
                    $stmtRestoreV = $db->prepare("UPDATE voucher SET soluongconlai = soluongconlai + 1 WHERE mavoucher = ?");
                    $stmtRestoreV->execute([$order['mavoucher']]);
                }
            } else {
                // Các trạng thái trung gian: Chờ đóng gói, Đã xuất kho, Đang giao
                $stmtUp = $db->prepare("UPDATE don_hang SET trangthaidonhang = ? WHERE madonhang = ?");
                $stmtUp->execute([$normalizedStatus, $maDonHang]);
            }

            $db->commit();
            return [
                'success' => true,
                'message' => "Cập nhật đơn hàng sang '{$normalizedStatus}' thành công.",
                'madonhang' => $maDonHang,
                'trangthai' => $normalizedStatus
            ];
        } catch (BusinessException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Database error in capNhatTrangThai: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Unexpected error in capNhatTrangThai: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'
            ];
        }
    }

    public static function getOrdersByCustomer($maKH, $phone = '', $email = '') {
        $maKH = trim((string)$maKH);
        if (empty($maKH)) return [];
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT dh.*, qct.sotiendonggop, qct.trangthaiquy
                        FROM don_hang dh
                        LEFT JOIN quy_cuu_tro qct ON dh.madonhang = qct.madonhang
                        WHERE dh.makh = ?
                        ORDER BY dh.ngaydathang DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$maKH]);
                return $stmt->fetchAll();
            } catch (Exception $e) {
                error_log("Lỗi getOrdersByCustomer: " . $e->getMessage());
            }
        }
        return [];
    }
}
