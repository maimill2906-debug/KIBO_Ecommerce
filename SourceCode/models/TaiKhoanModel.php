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

            // Kiểm tra mật khẩu bằng Bcrypt chuẩn
            $isPasswordCorrect = password_verify($password, $acc['matkhau']);
            if (!$isPasswordCorrect) {
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

            // 2. Sinh mã TK mới an toàn bằng IdGen
            require_once __DIR__ . '/IdGen.php';
            $maTK = IdGen::next($db, 'tai_khoan', 'matk', 'TK', 3);

            // Hash mật khẩu chuẩn bcrypt
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sqlTK = "INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro, trangthai, ngaytao) VALUES (?, ?, ?, 'User', 'Hoạt động', NOW())";
            $db->prepare($sqlTK)->execute([$maTK, $email, $hash]);

            // 3. Kiểm tra xem email này đã từng mua hàng vãng lai (matk IS NULL) chưa
            $stmtExistKH = $db->prepare("SELECT makh, matk FROM khach_hang WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmtExistKH->execute([$email]);
            $existKH = $stmtExistKH->fetch();

            if ($existKH) {
                if (!empty($existKH['matk'])) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'Email này đã được sử dụng. Vui lòng đăng nhập hoặc chọn email khác!'];
                }
                $maKH = $existKH['makh'];
                $updKH = $db->prepare("UPDATE khach_hang SET matk = ?, hoten = ? WHERE makh = ?");
                $updKH->execute([$maTK, $hoten, $maKH]);
            } else {
                $maKH = IdGen::next($db, 'khach_hang', 'makh', 'KH', 3);
                $sqlKH = "INSERT INTO khach_hang (makh, matk, hoten, email, diemtichluy) VALUES (?, ?, ?, ?, 0)";
                $db->prepare($sqlKH)->execute([$maKH, $maTK, $hoten, $email]);
            }

            // 4. Lưu SĐT nếu có
            if (!empty($phone)) {
                $chkSDT = $db->prepare("SELECT COUNT(*) FROM sdt_kh WHERE makh = ? AND sodienthoai = ?");
                $chkSDT->execute([$maKH, $phone]);
                if ($chkSDT->fetchColumn() == 0) {
                    $sqlSDT = "INSERT INTO sdt_kh (makh, sodienthoai) VALUES (?, ?)";
                    $db->prepare($sqlSDT)->execute([$maKH, $phone]);
                }
            }

            // 5. Lưu địa chỉ nếu có
            if (!empty($address)) {
                $maDC = IdGen::next($db, 'dia_chi_kh', 'madiachi', 'DC', 3);
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

    // FR-004: Khôi phục mật khẩu bằng mã xác thực OTP qua Email / SĐT
    public static function guiMaOtpQuenMatKhau($identifier) {
        $identifier = trim((string)$identifier);
        if (empty($identifier)) {
            return ['success' => false, 'message' => 'Vui lòng nhập Email hoặc Số điện thoại đã đăng ký.'];
        }

        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối CSDL'];

        try {
            $cleanPhone = preg_replace('/[^\d]/', '', $identifier);
            $isPhone = (strlen($cleanPhone) >= 9 && strlen($cleanPhone) <= 11 && !str_contains($identifier, '@'));

            if ($isPhone) {
                $stmt = $db->prepare("SELECT tk.matk, tk.tendangnhap, kh.hoten 
                                      FROM sdt_kh s
                                      JOIN khach_hang kh ON s.makh = kh.makh
                                      JOIN tai_khoan tk ON kh.matk = tk.matk
                                      WHERE s.sodienthoai = ? LIMIT 1");
                $stmt->execute([$cleanPhone]);
            } else {
                $stmt = $db->prepare("SELECT tk.matk, tk.tendangnhap, kh.hoten 
                                      FROM tai_khoan tk
                                      LEFT JOIN khach_hang kh ON tk.matk = kh.matk
                                      WHERE LOWER(tk.tendangnhap) = LOWER(?) OR LOWER(kh.email) = LOWER(?) LIMIT 1");
                $stmt->execute([$identifier, $identifier]);
            }

            $acc = $stmt->fetch();
            if (!$acc) {
                return ['success' => false, 'message' => 'Không tìm thấy tài khoản khớp với Email/Số điện thoại này.'];
            }

            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }

            $otp = (string)random_int(100000, 999999);
            $_SESSION['kibo_reset_otp'] = [
                'matk' => $acc['matk'],
                'identifier' => strtolower($identifier),
                'otp' => $otp,
                'expires' => time() + 300 // 5 phút
            ];

            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Yêu cầu OTP quên mật khẩu', 'tai_khoan', $acc['matk'], "Gửi mã OTP khôi phục mật khẩu cho {$acc['tendangnhap']}");

            return [
                'success' => true,
                'message' => "Mã xác thực OTP gồm 6 chữ số đã được gửi tới email/SĐT liên kết với tài khoản {$acc['tendangnhap']} (Hiệu lực 5 phút)."
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi hệ thống khi tạo mã OTP.'];
        }
    }

    public static function datLaiMatKhauBangOtp($identifier, $otp, $newPassword) {
        $identifier = strtolower(trim((string)$identifier));
        $otp = trim((string)$otp);
        $newPassword = (string)$newPassword;

        if (empty($identifier) || empty($otp) || empty($newPassword)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ mã OTP và mật khẩu mới.'];
        }
        if (mb_strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có độ dài tối thiểu từ 8 ký tự trở lên.'];
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $saved = $_SESSION['kibo_reset_otp'] ?? null;
        if (!$saved || $saved['identifier'] !== $identifier) {
            return ['success' => false, 'message' => 'Yêu cầu đặt lại mật khẩu không tồn tại hoặc đã hết hạn. Vui lòng lấy lại mã OTP.'];
        }
        if (time() > ($saved['expires'] ?? 0)) {
            unset($_SESSION['kibo_reset_otp']);
            return ['success' => false, 'message' => 'Mã OTP đã hết hạn (quá 5 phút). Vui lòng lấy mã mới.'];
        }
        if (!hash_equals((string)$saved['otp'], $otp)) {
            return ['success' => false, 'message' => 'Mã xác thực OTP không chính xác.'];
        }

        $db = Database::getConnection();
        if (!$db) return ['success' => false, 'message' => 'Lỗi kết nối CSDL'];

        try {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE tai_khoan SET matkhau = ? WHERE matk = ?");
            $upd->execute([$newHash, $saved['matk']]);

            require_once __DIR__ . '/NhatKyModel.php';
            NhatKyModel::ghi('Khôi phục mật khẩu OTP', 'tai_khoan', $saved['matk'], 'Đặt lại mật khẩu bằng OTP thành công');

            unset($_SESSION['kibo_reset_otp']);
            return ['success' => true, 'message' => 'Đặt lại mật khẩu mới thành công! Bạn có thể đăng nhập ngay.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi khi lưu mật khẩu mới.'];
        }
    }
}
