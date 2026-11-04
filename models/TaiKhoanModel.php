<?php
// ====================================================================
// MODEL: TaiKhoanModel.php
// Tương tác bảng tai_khoan, khach_hang (Xác thực, Phân quyền Admin/User, Bcrypt)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class TaiKhoanModel {

    public static function kiemTraKhoaBruteForce($ip, $identifier) {
        $db = Database::getConnection();
        if (!$db) return false;
        try {
            $stmt = $db->prepare("SELECT solanthatbai, khoaden FROM dang_nhap_that_bai WHERE ip = ? AND email = ?");
            $stmt->execute([$ip, strtolower($identifier)]);
            $row = $stmt->fetch();
            if ($row && !empty($row['khoaden'])) {
                $lockUntil = strtotime($row['khoaden']);
                if ($lockUntil > time()) {
                    $remainingMins = max(1, (int)ceil(($lockUntil - time()) / 60));
                    return $remainingMins;
                }
            }
        } catch (Exception $e) {
            error_log("Lỗi check brute force: " . $e->getMessage());
        }
        return false;
    }

    public static function ghiNhanDangNhapThatBai($ip, $identifier) {
        $db = Database::getConnection();
        if (!$db) return;
        try {
            $email = strtolower($identifier);
            $stmt = $db->prepare("SELECT solanthatbai, lancoicuoi FROM dang_nhap_that_bai WHERE ip = ? AND email = ?");
            $stmt->execute([$ip, $email]);
            $row = $stmt->fetch();

            if ($row) {
                $lastTime = strtotime($row['lancoicuoi']);
                $attempts = (time() - $lastTime > 900) ? 1 : ((int)$row['solanthatbai'] + 1);
                $lockUntil = ($attempts >= 5) ? date('Y-m-d H:i:s', time() + 900) : null;
                $upd = $db->prepare("UPDATE dang_nhap_that_bai SET solanthatbai = ?, khoaden = ?, lancoicuoi = NOW() WHERE ip = ? AND email = ?");
                $upd->execute([$attempts, $lockUntil, $ip, $email]);
            } else {
                $ins = $db->prepare("INSERT INTO dang_nhap_that_bai (ip, email, solanthatbai, khoaden, lancoicuoi) VALUES (?, ?, 1, NULL, NOW())");
                $ins->execute([$ip, $email]);
            }
        } catch (Exception $e) {
            error_log("Lỗi ghi nhận đăng nhập thất bại: " . $e->getMessage());
        }
    }

    public static function xoaDangNhapThatBai($ip, $identifier) {
        $db = Database::getConnection();
        if (!$db) return;
        try {
            $stmt = $db->prepare("DELETE FROM dang_nhap_that_bai WHERE ip = ? AND email = ?");
            $stmt->execute([$ip, strtolower($identifier)]);
        } catch (Exception $e) {
            error_log("Lỗi xóa brute force: " . $e->getMessage());
        }
    }

    public static function authenticateWithStatus($identifier, $password, $ip = '') {
        $identifier = trim((string)$identifier);
        $password = (string)$password;
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'user' => null, 'error' => 'Vui lòng nhập đầy đủ thông tin đăng nhập.'];
        }

        // Chặn tài khoản vãng lai @kibo.local
        if (str_ends_with(strtolower($identifier), '@kibo.local')) {
            return ['success' => false, 'user' => null, 'error' => 'Tài khoản không hợp lệ.'];
        }

        // Kiểm tra khóa tạm do Brute-force
        $lockMins = self::kiemTraKhoaBruteForce($ip, $identifier);
        if ($lockMins !== false) {
            return [
                'success' => false, 
                'user' => null, 
                'error' => "Tài khoản tạm thời bị khóa {$lockMins} phút do đăng nhập sai nhiều lần. Vui lòng thử lại sau.",
                'is_locked_temp' => true
            ];
        }

        $db = Database::getConnection();
        if (!$db) {
            return ['success' => false, 'user' => null, 'error' => 'Lỗi kết nối cơ sở dữ liệu.'];
        }

        try {
            // Hỗ trợ đăng nhập bằng Email HOẶC Số điện thoại
            $cleanPhone = preg_replace('/[^\d]/', '', $identifier);
            $isPhone = (strlen($cleanPhone) >= 9 && strlen($cleanPhone) <= 11 && !str_contains($identifier, '@'));

            if ($isPhone) {
                $sql = "SELECT tk.matk, tk.tendangnhap, tk.matkhau, tk.vaitro, tk.trangthai, kh.hoten, kh.makh,
                               s.sodienthoai,
                               (SELECT diachichitiet FROM dia_chi_kh WHERE makh = kh.makh LIMIT 1) as diachi
                        FROM sdt_kh s
                        JOIN khach_hang kh ON s.makh = kh.makh
                        JOIN tai_khoan tk ON kh.matk = tk.matk
                        WHERE s.sodienthoai = ? LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$cleanPhone]);
                $acc = $stmt->fetch();
            } else {
                $sql = "SELECT tk.matk, tk.tendangnhap, tk.matkhau, tk.vaitro, tk.trangthai, kh.hoten, kh.makh,
                               (SELECT sodienthoai FROM sdt_kh WHERE makh = kh.makh LIMIT 1) as sodienthoai,
                               (SELECT diachichitiet FROM dia_chi_kh WHERE makh = kh.makh LIMIT 1) as diachi
                        FROM tai_khoan tk
                        LEFT JOIN khach_hang kh ON tk.matk = kh.matk
                        WHERE LOWER(tk.tendangnhap) = LOWER(?) LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$identifier]);
                $acc = $stmt->fetch();
            }

            if (!$acc) {
                self::ghiNhanDangNhapThatBai($ip, $identifier);
                return ['success' => false, 'user' => null, 'error' => 'Email/Số điện thoại hoặc mật khẩu không chính xác.'];
            }

            // Kiểm tra mật khẩu
            if (!password_verify($password, $acc['matkhau'])) {
                self::ghiNhanDangNhapThatBai($ip, $identifier);
                return ['success' => false, 'user' => null, 'error' => 'Email/Số điện thoại hoặc mật khẩu không chính xác.'];
            }

            // Kiểm tra trạng thái tài khoản bị khóa
            if (($acc['trangthai'] ?? '') === 'Bị khóa') {
                return [
                    'success' => false, 
                    'user' => null, 
                    'error' => 'Tài khoản đang bị tạm khóa, vui lòng liên hệ hotline 0772 906 756 để được hỗ trợ.',
                    'is_banned' => true
                ];
            }

            // Đăng nhập thành công -> Xóa lịch sử sai
            self::xoaDangNhapThatBai($ip, $identifier);

            $user = [
                'MaTK' => $acc['matk'],
                'TenDangNhap' => $acc['tendangnhap'],
                'VaiTro' => $acc['vaitro'],
                'TrangThai' => $acc['trangthai'],
                'HoTen' => !empty($acc['hoten']) ? $acc['hoten'] : $acc['tendangnhap'],
                'MaKH' => $acc['makh'] ?? '',
                'SoDienThoai' => $acc['sodienthoai'] ?? '',
                'DiaChi' => $acc['diachi'] ?? ''
            ];

            return ['success' => true, 'user' => $user, 'error' => null];
        } catch (Exception $e) {
            error_log("Lỗi authenticateWithStatus: " . $e->getMessage());
            return ['success' => false, 'user' => null, 'error' => 'Hệ thống đang bận, vui lòng thử lại sau.'];
        }
    }

    public static function authenticate($username, $password) {
        $res = self::authenticateWithStatus($username, $password);
        return $res['user'] ?? null;
    }

    public static function getAll() {
        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT tk.matk as \"MaTK\", tk.tendangnhap as \"TenDangNhap\", tk.vaitro as \"VaiTro\", tk.trangthai as \"TrangThai\", kh.hoten as \"HoTen\"
                        FROM tai_khoan tk
                        LEFT JOIN khach_hang kh ON tk.matk = kh.matk
                        ORDER BY tk.matk ASC";
                return $db->query($sql)->fetchAll();
            } catch (Exception $e) {
                error_log("Lỗi getAll TaiKhoan: " . $e->getMessage());
            }
        }
        return [];
    }

    public static function registerUser($data) {
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $hoten = trim($data['name'] ?? ($data['hoten'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');

        // 1. Kiểm tra Họ và tên
        if (empty($hoten) || mb_strlen($hoten) < 2) {
            return ['success' => false, 'message' => 'Họ và tên phải có ít nhất 2 ký tự.'];
        }

        // 2. Kiểm tra định dạng Email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email không đúng định dạng (Ví dụ: name@example.com).'];
        }
        if (str_ends_with(strtolower($email), '@kibo.local')) {
            return ['success' => false, 'message' => 'Tên miền email này được bảo lưu cho hệ thống.'];
        }

        // 3. Kiểm tra Mật khẩu đủ 8 ký tự
        if (empty($password) || mb_strlen($password) < 8) {
            return ['success' => false, 'message' => 'Mật khẩu phải có độ dài tối thiểu từ 8 ký tự trở lên.'];
        }

        // 4. Kiểm tra Số điện thoại nếu có nhập (phải đủ 10 số, bắt đầu bằng 0)
        if (!empty($phone)) {
            $cleanPhone = preg_replace('/[^\d]/', '', $phone);
            if (!preg_match('/^0[0-9]{9}$/', $cleanPhone)) {
                return ['success' => false, 'message' => 'Số điện thoại phải gồm đúng 10 chữ số và bắt đầu bằng số 0 (Ví dụ: 0912345678).'];
            }
            $phone = $cleanPhone;
        }

        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];

        try {
            // Kiểm tra trùng email
            $check = $db->prepare("SELECT matk FROM tai_khoan WHERE LOWER(tendangnhap) = LOWER(?)");
            $check->execute([$email]);
            if ($check->fetch()) {
                return ['success' => false, 'message' => 'Email này đã được sử dụng. Vui lòng đăng nhập hoặc chọn email khác!'];
            }

            // Kiểm tra trùng số điện thoại
            if (!empty($phone)) {
                $checkPhone = $db->prepare("SELECT s.makh FROM sdt_kh s 
                                            JOIN khach_hang kh ON s.makh = kh.makh 
                                            WHERE s.sodienthoai = ? AND kh.matk IS NOT NULL");
                $checkPhone->execute([$phone]);
                if ($checkPhone->fetch()) {
                    return ['success' => false, 'message' => 'Số điện thoại này đã được sử dụng bởi một tài khoản khác.'];
                }
            }

            $db->beginTransaction();

            // 2. Sinh mã TK mới: TK + số
            $maxTK = $db->query("SELECT MAX(CAST(SUBSTRING(matk FROM 3) AS INTEGER)) FROM tai_khoan WHERE matk ~ '^TK[0-9]+$'")->fetchColumn();
            $nextTKNum = ($maxTK ? (int)$maxTK : 3) + 1;
            $maTK = 'TK' . str_pad($nextTKNum, 3, '0', STR_PAD_LEFT);

            // Hash mật khẩu chuẩn bcrypt
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sqlTK = "INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro, trangthai, ngaytao) VALUES (?, ?, ?, 'User', 'Hoạt động', NOW())";
            $db->prepare($sqlTK)->execute([$maTK, $email, $hash]);

            // 3. Sinh mã KH mới: KH + số
            $maxKH = $db->query("SELECT MAX(CAST(SUBSTRING(makh FROM 3) AS INTEGER)) FROM khach_hang WHERE makh ~ '^KH[0-9]+$'")->fetchColumn();
            $nextKHNum = ($maxKH ? (int)$maxKH : 2) + 1;
            $maKH = 'KH' . str_pad($nextKHNum, 3, '0', STR_PAD_LEFT);

            $sqlKH = "INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, ?, ?, 0)";
            $db->prepare($sqlKH)->execute([$maKH, $maTK, $hoten, $email]);

            // 4. Lưu SĐT nếu có
            if (!empty($phone)) {
                $sqlSDT = "INSERT INTO sdt_kh (makh, sodienthoai) VALUES (?, ?)";
                $db->prepare($sqlSDT)->execute([$maKH, $phone]);
            }

            // 5. Lưu địa chỉ nếu có
            if (!empty($address)) {
                $maxDC = $db->query("SELECT MAX(CAST(SUBSTRING(madiachi FROM 3) AS INTEGER)) FROM dia_chi_kh WHERE madiachi ~ '^DC[0-9]+$'")->fetchColumn();
                $nextDCNum = ($maxDC ? (int)$maxDC : 0) + 1;
                $maDC = 'DC' . str_pad($nextDCNum, 3, '0', STR_PAD_LEFT);
                $sqlDC = "INSERT INTO dia_chi_kh (madiachi, makh, diachichitiet, phuongxa, quanhuyen, tinhthanh, macdinh) VALUES (?, ?, ?, '', '', '', true)";
                $db->prepare($sqlDC)->execute([$maDC, $maKH, $address]);
            }

            $db->commit();

            return [
                'success' => true,
                'user' => [
                    'MaTK' => $maTK,
                    'TenDangNhap' => $email,
                    'VaiTro' => 'User',
                    'TrangThai' => 'Hoạt động',
                    'HoTen' => $hoten,
                    'MaKH' => $maKH
                ]
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Lỗi tạo tài khoản: " . $e->getMessage());
            return ['success' => false, 'message' => 'Đăng ký tài khoản không thành công. Vui lòng thử lại sau!'];
        }
    }

    public static function doiMatKhau($maTK, $matKhauCu, $matKhauMoi) {
        $maTK = trim((string)$maTK);
        $matKhauCu = (string)$matKhauCu;
        $matKhauMoi = (string)$matKhauMoi;

        if (empty($maTK) || empty($matKhauCu) || empty($matKhauMoi)) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ mật khẩu hiện tại và mật khẩu mới.'];
        }
        if (mb_strlen($matKhauMoi) < 8) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự.'];
        }

        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối CSDL'];

        try {
            $stmt = $db->prepare("SELECT matkhau FROM tai_khoan WHERE matk = ?");
            $stmt->execute([$maTK]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify($matKhauCu, $hash)) {
                return ['success' => false, 'message' => 'Mật khẩu hiện tại không chính xác.'];
            }

            $newHash = password_hash($matKhauMoi, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE tai_khoan SET matkhau = ? WHERE matk = ?");
            $upd->execute([$newHash, $maTK]);

            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Đổi mật khẩu', 'tai_khoan', $maTK, 'Đổi mật khẩu tài khoản thành công');

            return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
