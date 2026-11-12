# HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ KIBO
> **ĐỒ ÁN MÔN HỌC THƯƠNG MẠI ĐIỆN TỬ**  
> **Đề tài:** NỀN TẢNG THƯƠNG MẠI ĐIỆN TỬ SẢN PHẨM THÚ CƯNG  
> 🌐 **Website trực tuyến (Live Demo 24/7):** https://kibo-ecommerce.onrender.com


---

## 🛠️ 1. CÔNG NGHỆ SỬ DỤNG
* **Kiến trúc:** Mô hình MVC (Model - View - Controller) hướng dịch vụ.
* **Ngôn ngữ Backend:** PHP 8.2+ thuần (tối ưu hóa hiệu năng, bảo mật OWASP Top 10).
* **Giao diện Frontend:** HTML5, CSS3, Modern JavaScript ES6, Tailwind CSS (Mobile-first Responsive).
* **Cơ sở dữ liệu:** PostgreSQL 15 chuẩn hóa dạng chuẩn 3 (3NF) gồm 20 bảng, triển khai trên nền tảng **Supabase Cloud Pooler**.
* **Thanh toán:** Tích hợp thanh toán tiền mặt (COD) và chuyển khoản động **VietQR** chuẩn NAPAS 24/7.

---

## 📂 2. CẤU TRÚC KHO LƯU TRỮ
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

## 🔑 3. TÀI KHOẢN TRẢI NGHIỆM & KIỂM THỬ

| Vai trò | Tên đăng nhập / Email | Mật khẩu | Ghi chú |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@kibo.vn` | `12345678` | Toàn quyền quản trị đơn hàng, kho bãi, quỹ cứu trợ |
| **Khách hàng mẫu (User 1)** | `user@kibo.vn` | `UserKibo@2026!` | Tài khoản thành viên có lịch sử đơn hàng & điểm thưởng |
| **Khách hàng mẫu (User 2)** | `user@gmail.com` | `12345678` | Tài khoản thành viên phụ |

---

## 🚀 4. HƯỚNG DẪN CHẠY DỰ ÁN TRÊN LOCALHOST (XAMPP)
1. Đặt thư mục dự án vào thư mục web server: `C:\xampp\htdocs\kibo`.
2. Khởi động dịch vụ **Apache** trong bảng điều khiển XAMPP Control Panel.
3. Cấu hình tệp `SourceCode/.env` kết nối tới cơ sở dữ liệu PostgreSQL.
4. Mở trình duyệt và truy cập:
   * **Giao diện Khách hàng:** `http://localhost/kibo/SourceCode/`
   * **Giao diện Quản trị viên:** `http://localhost/kibo/SourceCode/?action=admin`
