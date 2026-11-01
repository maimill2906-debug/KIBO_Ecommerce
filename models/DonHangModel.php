<?php
// ====================================================================
// MODEL: DonHangModel.php
// Tương tác bảng don_hang, chi_tiet_don_hang, quy_cuu_tro, thanh_toan
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class DonHangModel {
    private static $mockOrders = [
        [
            'orderId' => 'KB-8921',
            'customerName' => 'Nguyễn Lê Quỳnh Mai',
            'phone' => '0772906756',
            'address' => '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh',
            'items' => [
                ['name' => 'KIBO Cá Hồi & Gạo Lứt', 'variant' => 'Túi 1.5kg', 'qty' => 1, 'price' => 285000]
            ],
            'subtotal' => 285000,
            'shipping' => 0,
            'discount' => 0,
            'total' => 285000,
            'fundAmount' => 14250,
            'lookupCode' => 'KB-8921',
            'status' => 'Giao hàng thành công',
            'carrierStatus' => 'GHN đã giao hàng thành công',
            'date' => '25/10/2026',
            'allocatedStation' => 'Trạm Cứu Hộ Động Vật Nông Lâm'
        ],
        [
            'orderId' => 'KB-8922',
            'customerName' => 'Nguyễn Lê Quỳnh Mai',
            'phone' => '0772906756',
            'address' => '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh',
            'items' => [
                ['name' => 'Royal Canin Kitten 36', 'variant' => 'Túi 2kg', 'qty' => 1, 'price' => 445000]
            ],
            'subtotal' => 445000,
            'shipping' => 0,
            'discount' => 20000,
            'total' => 425000,
            'fundAmount' => 21250,
            'lookupCode' => 'KB-8922',
            'status' => 'Đang giao hàng',
            'carrierStatus' => 'GHTK đang vận chuyển tới bạn',
            'date' => '30/10/2026',
            'allocatedStation' => 'Trạm Cứu Hộ Động Vật Sài Gòn Time'
        ]
    ];

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
                            'fundAmount' => (float)($r['sotiendonggop'] ?? round(($r['tongtienhang'] - $r['tiengiamgia']) * 0.05)),
                            'lookupCode' => $r['matracuu'] ?? $maDH,
                            'status' => $statusText,
                            'carrierStatus' => ($statusRaw === 'Giao thành công' ? 'Đã giao thành công' : 'Đơn vị vận chuyển đang xử lý'),
                            'date' => date('d/m/Y', strtotime($r['ngaydathang'])),
                            'allocatedStation' => $r['tentram'] ?? null,
                            'fundStatus' => $r['trangthaiquy'] ?? 'Đã tích lũy'
                        ];
                    }
                    return $mapped;
                }
            } catch (Exception $e) {}
        }
        return self::$mockOrders;
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
                        'fundAmount' => (float)($r['sotiendonggop'] ?? round(($r['tongtienhang'] - $r['tiengiamgia']) * 0.05)),
                        'lookupCode' => $r['matracuu'] ?? $r['madonhang'],
                        'status' => $statusText,
                        'carrierStatus' => ($statusRaw === 'Giao thành công' ? 'Đã giao thành công' : 'Đơn vị vận chuyển đang xử lý'),
                        'date' => date('d/m/Y', strtotime($r['ngaydathang'])),
                        'allocatedStation' => $r['tentram'] ?? null,
                        'fundStatus' => $r['trangthaiquy'] ?? 'Đã tích lũy'
                    ];
                }
            } catch (Exception $e) {}
        }

        // Mock fallback
        foreach (self::$mockOrders as $o) {
            if (strcasecmp($o['orderId'], $maDonHang) === 0 || strcasecmp($o['lookupCode'], $maDonHang) === 0) {
                return $o;
            }
        }
        return null;
    }

    public static function taoDonHang($orderData) {
        $db = Database::getConnection();
        if (!$db) {
            return false;
        }

        try {
            $db->beginTransaction();

            $maDonHang = $orderData['madonhang'] ?? ('KB-' . rand(1000, 9999));
            $sdtNhan = preg_replace('/[^\d]/', '', trim($orderData['sodienthoainhan'] ?? ''));

            // Tự động nhận diện MaKH chuẩn xác từ session, email hoặc số điện thoại
            $maKH = !empty($orderData['makh']) && $orderData['makh'] !== 'KH001' ? $orderData['makh'] : null;
            if (!$maKH && !empty($_SESSION['user']['MaKH'])) {
                $maKH = $_SESSION['user']['MaKH'];
            }
            if (!$maKH && !empty($_SESSION['user']['TenDangNhap'])) {
                $findKH = $db->prepare("SELECT makh FROM khach_hang WHERE LOWER(email) = LOWER(?) LIMIT 1");
                $findKH->execute([$_SESSION['user']['TenDangNhap']]);
                $maKH = $findKH->fetchColumn() ?: null;
            }
            if (!$maKH && !empty($sdtNhan)) {
                $findKH = $db->prepare("SELECT makh FROM sdt_kh WHERE REGEXP_REPLACE(sodienthoai, '[^0-9]', '', 'g') = ? LIMIT 1");
                $findKH->execute([$sdtNhan]);
                $maKH = $findKH->fetchColumn() ?: null;
            }
            if (!$maKH) {
                $maKH = 'KH001';
            }

            $ngayDat = date('Y-m-d H:i:s');
            $tongTienHang = $orderData['tongtienhang'] ?? 0;
            $tienGiam = $orderData['tiengiamgia'] ?? 0;
            $phiShip = $orderData['phivanchuyen'] ?? 0;
            $tongThanhToan = $orderData['tongthanhtoan'] ?? ($tongTienHang - $tienGiam + $phiShip);
            $tenNhan = trim($orderData['tennguoinhan'] ?? '');
            $diaChiNhan = trim($orderData['diachigiaohang'] ?? '');
            $ghiChu = $orderData['ghichu'] ?? '';
            $phuongThuc = $orderData['phuongthuc'] ?? 'Thanh toán khi nhận hàng (COD)';

            if (mb_strlen($tenNhan) < 2 || !preg_match('/^0[0-9]{9}$/', $sdtNhan)) {
                return false;
            }

            $maVoucher = !empty($orderData['mavoucher']) ? trim($orderData['mavoucher']) : null;
            if ($maVoucher) {
                $checkV = $db->prepare("SELECT mavoucher FROM voucher WHERE UPPER(mavoucher) = UPPER(?) LIMIT 1");
                $checkV->execute([$maVoucher]);
                $realV = $checkV->fetchColumn();
                $maVoucher = $realV ?: null;
            }

            // 1. Insert don_hang
            $sqlDH = "INSERT INTO don_hang (madonhang, makh, mavoucher, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang, ghichu)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận', ?, ?, ?, ?)";
            $stmtDH = $db->prepare($sqlDH);
            $stmtDH->execute([$maDonHang, $maKH, $maVoucher, $ngayDat, $tongTienHang, $tienGiam, $phiShip, $tongThanhToan, $tenNhan, $sdtNhan, $diaChiNhan, $ghiChu]);

            // Trừ lượt dùng voucher nếu có áp dụng
            if ($maVoucher) {
                require_once __DIR__ . '/VoucherModel.php';
                VoucherModel::useVoucher($maVoucher);
            }

            // 2. Insert quy_cuu_tro (trích đúng 5%)
            $tienDongGop = round(($tongTienHang - $tienGiam) * 0.05);
            $maDongGop = 'QCT' . rand(100, 999);
            $sqlQuy = "INSERT INTO quy_cuu_tro (madonggop, madonhang, sotiendonggop, ngayghinhan, matracuu, trangthaiquy, machungtu)
                       VALUES (?, ?, ?, ?, ?, 'Đã tích lũy', NULL)";
            $stmtQuy = $db->prepare($sqlQuy);
            $stmtQuy->execute([$maDongGop, $maDonHang, $tienDongGop, $ngayDat, $maDonHang]);

            // 3. Insert thanh_toan
            $maGiaoDich = 'GD' . rand(100, 999);
            $trangThaiTT = ($phuongThuc === 'Chuyển khoản VietQR') ? 'Chờ thanh toán' : 'Chờ thanh toán';
            $sqlTT = "INSERT INTO thanh_toan (magiaodich, madonhang, phuongthuctt, sotien, thoigiantt, trangthaitt)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmtTT = $db->prepare($sqlTT);
            $stmtTT->execute([$maGiaoDich, $maDonHang, $phuongThuc, $tongThanhToan, $ngayDat, $trangThaiTT]);

            // 4. Insert chi_tiet_don_hang
            if (!empty($orderData['items']) && is_array($orderData['items'])) {
                $sqlCT = "INSERT INTO chi_tiet_don_hang (madonhang, mabienthe, soluong, dongia, thanhtien)
                          VALUES (?, ?, ?, ?, ?)";
                $stmtCT = $db->prepare($sqlCT);
                foreach ($orderData['items'] as $item) {
                    $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);
                    $price = (float)($item['price'] ?? 0);
                    $lineTotal = $qty * $price;
                    $maBT = 'BT001';
                    if (!empty($item['id']) && str_starts_with($item['id'], 'BT')) {
                        $maBT = $item['id'];
                    } else {
                        $findBT = $db->prepare("SELECT bt.mabienthe FROM bien_the bt JOIN san_pham sp ON bt.masp = sp.masp WHERE sp.tensp ILIKE ? LIMIT 1");
                        $findBT->execute(['%' . ($item['name'] ?? '') . '%']);
                        $found = $findBT->fetchColumn();
                        if ($found) $maBT = $found;
                    }
                    $stmtCT->execute([$maDonHang, $maBT, $qty, $price, $lineTotal]);
                }
            }

            // 5. Tích lũy điểm cho khách hàng thành viên (mỗi 10.000đ = 1 điểm)
            if ($maKH) {
                $diemCong = (int)floor($tongThanhToan / 10000);
                if ($diemCong > 0) {
                    $updateDiem = $db->prepare("UPDATE khach_hang SET diemtichluy = COALESCE(diemtichluy, 0) + ? WHERE makh = ?");
                    $updateDiem->execute([$diemCong, $maKH]);
                }
            }

            $db->commit();
            return $maDonHang;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    public static function capNhatTrangThai($maDonHang, $trangThaiMoi) {
        $db = Database::getConnection();
        if (!$db) return false;
        try {
            $statusMap = [
                'Đang giao hàng' => 'Đang giao',
                'Giao hàng thành công' => 'Giao thành công',
                'Đã huỷ' => 'Đã hủy'
            ];
            $normalizedStatus = $statusMap[$trangThaiMoi] ?? $trangThaiMoi;

            $stmt = $db->prepare("UPDATE don_hang SET trangthaidonhang = ? WHERE madonhang = ?");
            return $stmt->execute([$normalizedStatus, $maDonHang]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getOrdersByCustomer($maKH, $phone = '', $email = '') {
        $db = Database::getConnection();
        if ($db) {
            try {
                $cleanPhone = preg_replace('/[^\d]/', '', (string)$phone);
                $email = strtolower(trim((string)$email));

                $sql = "SELECT dh.*, qct.sotiendonggop, qct.trangthaiquy
                        FROM don_hang dh
                        LEFT JOIN quy_cuu_tro qct ON dh.madonhang = qct.madonhang
                        WHERE dh.makh = ?
                           OR (LENGTH(?) >= 9 AND REGEXP_REPLACE(dh.sodienthoainhan, '[^0-9]', '', 'g') = ?)
                           OR (LENGTH(?) >= 9 AND REGEXP_REPLACE(dh.sodienthoainhan, '[^0-9]', '', 'g') IN (
                               SELECT REGEXP_REPLACE(sodienthoai, '[^0-9]', '', 'g') FROM sdt_kh WHERE makh = ?
                           ))
                           OR (LENGTH(?) > 3 AND dh.makh IN (SELECT makh FROM khach_hang WHERE LOWER(email) = ?))
                        ORDER BY dh.ngaydathang DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute([$maKH, $cleanPhone, $cleanPhone, $cleanPhone, $maKH, $email, $email]);
                return $stmt->fetchAll();
            } catch (Exception $e) {}
        }
        return [];
    }
}
