# HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ ĐA KÊNH KIBO (KIBO.VN)
> **ĐỒ ÁN MÔN HỌC THƯƠNG MẠI ĐIỆN TỬ**  
> **Đề tài:** Xây dựng Hệ thống Thương mại Điện tử Đa kênh KIBO tích hợp Quỹ Cứu trợ Động vật cơ nhỡ.

---

## 📌 1. GIỚI THIỆU DỰ ÁN
**KIBO (kibo.vn)** là nền tảng thương mại điện tử chuyên cung cấp thức ăn, phụ kiện và sản phẩm chăm sóc thú cưng chính hãng, kết hợp mô hình kinh doanh nhân văn:
* **Cam kết xã hội:** Tự động trích **5% giá trị thuần** mỗi đơn hàng giao thành công vào Quỹ cứu trợ động vật cơ nhỡ.
* **Minh bạch tài chính:** Báo cáo sao kê quỹ trực tuyến theo thời gian thực; quy trình giải ngân đối soát trực tiếp với các Trạm cứu hộ đối tác kèm chứng từ ủy nhiệm chi thực tế.
* **Kiểm soát giao dịch (ACID):** Máy chủ tính giá 100% (chống gian lận client-side), kiểm soát tồn kho nguyên tử và vận hành máy trạng thái đơn hàng 5 bước bất biến.

---

## 🛠️ 2. CÔNG NGHỆ SỬ DỤNG
* **Kiến trúc:** Mô hình MVC (Model - View - Controller) hướng dịch vụ.
* **Ngôn ngữ Backend:** PHP 8.2+ thuần (tối ưu hóa hiệu năng, bảo mật OWASP Top 10).
* **Giao diện Frontend:** HTML5, CSS3, Modern JavaScript ES6, Tailwind CSS (Mobile-first Responsive).
* **Cơ sở dữ liệu:** PostgreSQL 15 chuẩn hóa dạng chuẩn 3 (3NF) gồm 20 bảng, triển khai trên nền tảng **Supabase Cloud Pooler**.
* **Thanh toán:** Tích hợp thanh toán tiền mặt (COD) và chuyển khoản động **VietQR** chuẩn NAPAS 24/7.

---

## 📂 3. CẤU TRÚC KHO LƯU TRỮ (REPOSITORY)
```text
├── SourceCode/                 # Toàn bộ mã nguồn ứng dụng web KIBO
│   ├── assets/                 # CSS, Fonts, Images sản phẩm, JS
│   ├── config/                 # Cấu hình Database, VietQR, Session & CSRF
│   ├── controllers/            # Tầng điều hướng Controller
│   ├── models/                 # Tầng nghiệp vụ Model (CSDL, Quỹ 5%, Giỏ hàng)
│   ├── views/                  # Tầng giao diện người dùng (Admin & Customer)
│   ├── index.php               # Điểm khởi chạy trung tâm (Front Controller)
│   └── .env.example            # Cấu hình biến môi trường mẫu
├── CSDL_HeThong.sql            # Script khởi tạo CSDL PostgreSQL (20 bảng chuẩn 3NF)
└── README.md                   # Tài liệu hướng dẫn dự án
```

---

## 🔑 4. TÀI KHOẢN TRẢI NGHIỆM & KIỂM THỬ

| Vai trò | Tên đăng nhập / Email | Mật khẩu | Ghi chú |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@kibo.vn` | `12345678` | Toàn quyền quản trị đơn hàng, kho bãi, quỹ cứu trợ |
| **Khách hàng mẫu (User 1)** | `user@kibo.vn` | `UserKibo@2026!` | Tài khoản thành viên có lịch sử đơn hàng & điểm thưởng |
| **Khách hàng mẫu (User 2)** | `user@gmail.com` | `12345678` | Tài khoản thành viên phụ |

---

## 🚀 5. HƯỚNG DẪN CHẠY DỰ ÁN TRÊN LOCALHOST (XAMPP)
1. Đặt thư mục dự án vào thư mục web server: `C:\xampp\htdocs\kibo`.
2. Khởi động dịch vụ **Apache** trong bảng điều khiển XAMPP Control Panel.
3. Cấu hình tệp `SourceCode/.env` kết nối tới cơ sở dữ liệu PostgreSQL.
4. Mở trình duyệt và truy cập:
   * **Giao diện Khách hàng:** `http://localhost/kibo/SourceCode/`
   * **Giao diện Quản trị viên:** `http://localhost/kibo/SourceCode/?action=admin`
