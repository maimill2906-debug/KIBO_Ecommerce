<?php
// ====================================================================
// MODEL: TaiKhoanModel.php
// Tương tác bảng tai_khoan, khach_hang (Xác thực, Phân quyền Admin/User, Bcrypt)
// Tương thích trực tiếp Supabase PostgreSQL
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class TaiKhoanModel {
    public static function authenticate($username, $password) {
        $username = trim($username);
        if (empty($username) || empty($password)) {
            return null;
        }

        // Từ chối đăng nhập nếu tendangnhap kết thúc @kibo.local (tài khoản vãng lai)
        if (str_ends_with(strtolower($username), '@kibo.local')) {
            return null;
        }

        $db = Database::getConnection();
        if ($db) {
            try {
                $sql = "SELECT tk.matk, tk.tendangnhap, tk.matkhau, tk.vaitro, tk.trangthai, kh.hoten, kh.makh,
                               (SELECT sodienthoai FROM sdt_kh WHERE makh = kh.makh LIMIT 1) as sodienthoai,
                               (SELECT diachichitiet FROM dia_chi_kh WHERE makh = kh.makh LIMIT 1) as diachi
                        FROM tai_khoan tk
                        LEFT JOIN khach_hang kh ON tk.matk = kh.matk
                        WHERE LOWER(tk.tendangnhap) = LOWER(?) AND tk.trangthai = 'Hoạt động'";
                $stmt = $db->prepare($sql);
                $stmt->execute([$username]);
                $acc = $stmt->fetch();

                if ($acc) {
                    $hash = $acc['matkhau'];
                    // Chỉ dùng password_verify, tuyệt đối không dùng backdoor 123456 hoặc so sánh plain text
                    if (password_verify($password, $hash)) {
                        return [
                            'MaTK' => $acc['matk'],
                            'TenDangNhap' => $acc['tendangnhap'],
                            'VaiTro' => $acc['vaitro'],
                            'TrangThai' => $acc['trangthai'],
                            'HoTen' => !empty($acc['hoten']) ? $acc['hoten'] : $acc['tendangnhap'],
                            'MaKH' => $acc['makh'] ?? '',
                            'SoDienThoai' => $acc['sodienthoai'] ?? '',
                            'DiaChi' => $acc['diachi'] ?? ''
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("Lỗi authenticate: " . $e->getMessage());
            }
        }

        return null;
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
}
