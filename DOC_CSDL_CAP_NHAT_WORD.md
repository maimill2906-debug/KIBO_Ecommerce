# TÀI LIỆU CẬP NHẬT THIẾT KẾ CƠ SỞ DỮ LIỆU (DÙNG ĐỂ CHỈNH BÁO CÁO WORD)
**Dự án:** Sàn Thương Mại Điện Tử Thú Cưng KIBO (Pet's Home)  
**Tài liệu tham chiếu:** Khớp 100% giữa Code thực thi, CSDL PostgreSQL (Supabase) và Báo cáo Đồ án

---

## 1. TỔNG HỢP CÁC BẢNG CÓ THAY ĐỔI / BỔ SUNG

Khi cập nhật file Word (Mục Thiết kế CSDL vật lý & Từ điển dữ liệu), bạn chỉ cần sao chép các dòng bôi đậm hoặc cập nhật vào các bảng tương ứng dưới đây:

---

### 1.1. Bảng `DON_HANG` (Đơn hàng)

| Tên cột | Kiểu dữ liệu (Word) | Ràng buộc | Ý nghĩa / Ghi chú |
| :--- | :--- | :--- | :--- |
| `madonhang` | **VARCHAR(20)** *(cũ là VARCHAR(10))* | PRIMARY KEY | Định dạng mã mới: `KB-YYMMDDXXXX` (13 ký tự) kèm cơ chế retry chống trùng |
| `makh` | VARCHAR(10) | FOREIGN KEY $\rightarrow$ `khach_hang(makh)` | Mã khách hàng (cả khách có TK và khách vãng lai) |
| `mavoucher` | VARCHAR(20) | NULLABLE, FOREIGN KEY $\rightarrow$ `voucher(mavoucher)` | Mã ưu đãi áp dụng |
| `ngaydathang` | TIMESTAMPTZ | NOT NULL, DEFAULT NOW() | Thời điểm đặt hàng |
| **`ngaygiaothanhcong`** *(CỘT MỚI)* | **TIMESTAMPTZ** | **NULLABLE** | **Thời điểm đơn chuyển trạng thái "Giao thành công", dùng làm mốc đếm ngược 3 ngày hạn đổi trả (BR6)** |
| `tongtienhang` | NUMERIC(12,2) | NOT NULL, >= 0 | Tiền hàng tính lại 100% từ giá bán các biến thể trên server |
| `tiengiamgia` | NUMERIC(12,2) | NOT NULL, DEFAULT 0 | Tiền voucher giảm trừ thực tế |
| `phivanchuyen` | NUMERIC(12,2) | NOT NULL, DEFAULT 0 | 0đ nếu $\ge$200k (nội thành HCM) hoặc $\ge$500k (ngoại tỉnh); 20.000đ trường hợp khác |
| `tongthanhtoan` | NUMERIC(12,2) | NOT NULL, >= 0 | `tongtienhang - tiengiamgia + phivanchuyen` |
| `trangthaidonhang` | VARCHAR(30) | CHECK (...) | `Chờ xác nhận`, `Chờ đóng gói`, `Đã xuất kho`, `Đang giao`, `Giao thành công`, `Đã hủy` |
| `tennguoinhan` | VARCHAR(100) | NOT NULL | Tên người nhận hàng |
| `sodienthoainhan`| VARCHAR(15) | NOT NULL | SĐT liên hệ nhận hàng |
| `diachigiaohang` | TEXT | NOT NULL | Địa chỉ chi tiết |
| `ghichu` | TEXT | NULLABLE | Ghi chú từ khách hàng |

---

### 1.2. Bảng `CHI_TIET_DON_HANG` (Chi tiết đơn hàng)

| Tên cột | Kiểu dữ liệu | Ràng buộc | Ý nghĩa / Ghi chú |
| :--- | :--- | :--- | :--- |
| `mactdh` | VARCHAR(10) | PRIMARY KEY | Mã chi tiết đơn |
| `madonhang` | **VARCHAR(20)** *(cũ là VARCHAR(10))* | NOT NULL, FOREIGN KEY $\rightarrow$ `don_hang(madonhang)` | Khóa ngoại tham chiếu đơn hàng mới |
| `mabienthe` | VARCHAR(10) | NOT NULL, FOREIGN KEY $\rightarrow$ `bien_the(mabienthe)` | Khóa ngoại biến thể sản phẩm |
| `soluong` | INT | NOT NULL, > 0 | Số lượng mua (được trừ kho nguyên tử) |
| `dongia` | NUMERIC(12,2) | NOT NULL, >= 0 | Đơn giá tại thời điểm đặt |
| `thanhtien` | NUMERIC(12,2) | NOT NULL, >= 0 | `soluong * dongia` |

---

### 1.3. Bảng `THANH_TOAN` (Giao dịch thanh toán)

| Tên cột | Kiểu dữ liệu | Ràng buộc | Ý nghĩa / Ghi chú |
| :--- | :--- | :--- | :--- |
| `magiaodich` | VARCHAR(15) | PRIMARY KEY | Sinh mã tự tăng `GDXXXXX` (sử dụng advisory lock) |
| `madonhang` | **VARCHAR(20)** *(cũ là VARCHAR(10))* | NOT NULL, FOREIGN KEY $\rightarrow$ `don_hang(madonhang)` | Khóa ngoại tham chiếu đơn hàng mới |
| `phuongthuctt` | VARCHAR(50) | NOT NULL | `COD` (Tiền mặt khi nhận) hoặc `ChuyenKhoan` |
| `sotientt` | NUMERIC(12,2) | NOT NULL, >= 0 | Số tiền thanh toán |
| `trangthaitt` | VARCHAR(30) | NOT NULL | `Chưa thanh toán`, `Đã thanh toán`, `Đã hoàn tiền` |
| `ngaythanhtoan` | TIMESTAMPTZ | NULLABLE | Thời điểm thanh toán |

---

### 1.4. Bảng `QUY_CUU_TRO` (Quỹ cứu trợ 5% thú cưng)

| Tên cột | Kiểu dữ liệu | Ràng buộc | Ý nghĩa / Ghi chú |
| :--- | :--- | :--- | :--- |
| `madonggop` | VARCHAR(10) | PRIMARY KEY | Sinh mã tự tăng `QCTXXXX` (sử dụng advisory lock) |
| `madonhang` | **VARCHAR(20)** *(cũ là VARCHAR(10))* | NOT NULL, **UNIQUE (`uq_quy_madonhang`)** | Mỗi đơn hàng gắn duy nhất với 1 bản ghi trích quỹ |
| `sotiendonggop` | NUMERIC(12,2) | NOT NULL, >= 0 | Công thức: `round((tongtienhang - tiengiamgia) * 0.05)` |
| `ngayghinhan` | TIMESTAMPTZ | NOT NULL, DEFAULT NOW() | Thời điểm ghi nhận trích quỹ |
| `matracuu` | **VARCHAR(20)** | NOT NULL | Mã tra cứu quỹ (đồng bộ với madonhang để khách tra cứu) |
| `trangthaiquy` | VARCHAR(30) | **CHECK (`chk_quy_trangthai`)** | **Giá trị mới: `Chờ trích quỹ`, `Đã trích quỹ`, `Đã giải ngân`, `Đã hủy`** |
| `machungtu` | VARCHAR(10) | NULLABLE, FOREIGN KEY $\rightarrow$ `chung_tu_giai_ngan(machungtu)` | Mã chứng từ giải ngân khi chuyển tiền sang trạm cứu hộ |

> **Quy tắc chuyển trạng thái quỹ:**
> 1. Lúc khách tạo đơn hàng $\rightarrow$ `Chờ trích quỹ`.
> 2. Khi đơn hàng chuyển `Giao thành công` $\rightarrow$ `Đã trích quỹ`.
> 3. Khi trạm nhận được tiền đợt cứu trợ $\rightarrow$ `Đã giải ngân`.
> 4. Khi đơn bị hủy hoặc đổi trả hoàn tiền $\rightarrow$ `Đã hủy` (không tính vào tổng quỹ).

---

### 1.5. Bảng `YEU_CAU_DOI_TRA` (Yêu cầu đổi trả hàng)

| Tên cột | Kiểu dữ liệu | Ràng buộc | Ý nghĩa / Ghi chú |
| :--- | :--- | :--- | :--- |
| `mayeucau` | VARCHAR(15) | PRIMARY KEY | Mã yêu cầu sinh tự động `DTXXXXX` |
| `madonhang` | **VARCHAR(20)** *(cũ là VARCHAR(10))* | NOT NULL, FOREIGN KEY $\rightarrow$ `don_hang(madonhang)` | Tham chiếu đến đơn hàng |
| `lydodoitra` | TEXT | NOT NULL | Khách hàng giải trình lý do |
| `hinhanhminhchung`| TEXT | NULLABLE | Link ảnh chụp/video sản phẩm lỗi |
| `ngayyeucau` | TIMESTAMPTZ | NOT NULL, DEFAULT NOW() | Thời điểm gửi yêu cầu |
| `trangthaiyeucau` | VARCHAR(30) | **CHECK (`chk_doitra_trangthai`)** | **`Chờ xử lý`, `Đã đồng ý đổi`, `Đã hoàn tiền`, `Đã từ chối`, `Đã hoàn tất`** |
| `huongxuly` | VARCHAR(50) | NULLABLE | Hướng giải quyết của Admin |
| **`ngayxuly`** *(CỘT MỚI)* | **TIMESTAMPTZ** | **NULLABLE** | **Thời điểm Admin thực hiện xét duyệt hoặc từ chối** |

> **Ràng buộc duy nhất chống gửi trùng (`uq_doitra_con_hieu_luc`):**
> Một đơn hàng chỉ được tồn tại **tối đa 1 yêu cầu đổi trả đang có hiệu lực** (trạng thái khác `Đã từ chối`). Nếu Admin từ chối, khách mới có thể gửi lại yêu cầu khác nếu vẫn còn trong hạn 3 ngày.

---

### 1.6. Bảng `KHACH_HANG` (Thông tin khách hàng)

| Tên cột | Kiểu dữ liệu | Ràng buộc | Thay đổi & Ý nghĩa |
| :--- | :--- | :--- | :--- |
| `makh` | VARCHAR(10) | PRIMARY KEY | Mã khách hàng (Khách đăng ký: `KH...`, Khách vãng lai: `KV...`) |
| `matk` | VARCHAR(10) | **NULLABLE (DROP NOT NULL)** | **Khách vãng lai không tạo tài khoản thì cột này mang giá trị `NULL`** |
| `hoten` | VARCHAR(100) | NOT NULL | Họ tên người mua |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | Với khách vãng lai: `guest_<sdt>@kibo.local` |
| `diemtichluy` | INT | NOT NULL, DEFAULT 0 | Cộng 1 điểm / 10.000đ của tổng thanh toán khi Giao thành công (cộng đúng 1 lần) |
| `hangthanhvien` | VARCHAR(20) | NOT NULL, DEFAULT 'Đồng' | Hạng: Đồng, Bạc, Vàng, Kim Cương |

---

### 1.7. Bảng `BIEN_THE` (Biến thể sản phẩm)

| Tên cột | Kiểu dữ liệu | Ràng buộc | Thay đổi & Ý nghĩa |
| :--- | :--- | :--- | :--- |
| `mabienthe` | VARCHAR(10) | PRIMARY KEY | Mã biến thể SKU |
| `masanpham` | VARCHAR(10) | FOREIGN KEY $\rightarrow$ `san_pham(masanpham)` | Khóa ngoại sản phẩm |
| `tenbienthe` | VARCHAR(100) | NOT NULL | Tên quy cách (vd: Bao 1.5kg, Vị Cá Hồi) |
| `giaban` | NUMERIC(12,2) | NOT NULL, >= 0 | Giá bán thực tế (Server dùng giá này tính tiền, không tin Client) |
| **`giagoc`** *(CỘT MỚI)* | **NUMERIC(12,2)** | **NULLABLE** | **Giá gốc trước khuyến mãi (nếu có để hiển thị % giảm giá)** |
| `soluongton` | INT | NOT NULL, DEFAULT 0, **CHECK (`chk_bt_ton`: soluongton >= 0)** | **Bổ sung constraint chặn âm kho ở mức CSDL, khóa dòng nguyên tử khi mua** |

---

### 1.8. Bảng `VOUCHER` (Mã giảm giá ưu đãi)

| Tên cột | Kiểu dữ liệu | Ràng buộc | Thay đổi & Ý nghĩa |
| :--- | :--- | :--- | :--- |
| `mavoucher` | VARCHAR(20) | PRIMARY KEY | Mã nhập ưu đãi (chữ hoa, số: vd `KIBO20`, `FREESHIP`) |
| `tenvoucher` | VARCHAR(100) | NOT NULL | Tên chương trình ưu đãi |
| `loaigiamgia`| VARCHAR(20) | NOT NULL | `SoTien` (cố định) hoặc `PhanTram` (%) |
| `giatrigiam` | NUMERIC(12,2) | NOT NULL, > 0 | Số tiền giảm hoặc phần trăm giảm |
| `donhangtoithieu`| NUMERIC(12,2)| NOT NULL, DEFAULT 0 | Giá trị đơn hàng tối thiểu để được áp dụng |
| `ngaybatdau` | TIMESTAMPTZ | NOT NULL | Ngày bắt đầu có hiệu lực |
| `ngayketthuc` | TIMESTAMPTZ | NOT NULL | Ngày hết hạn |
| `soluongconlai`| INT | NOT NULL, DEFAULT 0, **CHECK (`chk_vc_sl`: soluongconlai >= 0)** | **Số lượt sử dụng còn lại (trừ nguyên tử 1 khi đặt hàng, cộng lại khi hủy đơn)** |
| **`kichhoat`** *(CỘT MỚI)* | **BOOLEAN** | **NOT NULL, DEFAULT TRUE** | **Trạng thái bật / tắt do Admin kiểm soát trên giao diện** |

---

### 1.9. Bảng `TAI_KHOAN` (Tài khoản người dùng)

* **Bảo mật mật khẩu:**
  - Toàn bộ mật khẩu lưu dưới dạng chuỗi băm Bcrypt chuẩn PHP (`$2y$10$...`).
  - Xóa bỏ hoàn toàn backdoor hardcode mật khẩu `123456`.
  - Mật khẩu 3 tài khoản demo phục vụ chấm bài / nghiệm thu: **`MatKhauDemo@2026`**
    - `admin@kibo.vn` (VaiTro: `Admin`)
    - `quynhmai@gmail.com` (VaiTro: `KhachHang`)
    - `hoangphuc@gmail.com` (VaiTro: `KhachHang`)
  - Chặn đăng nhập đối với các tài khoản ảo đuôi `@kibo.local` của khách vãng lai.

---

## 2. BẢNG TỔNG KẾT RÀNG BUỘC TOÀN VẸN (CONSTRAINTS & INDEXES MỚI)

Để điền vào mục **"Các ràng buộc toàn vẹn"** trong Báo cáo Word:

1. **`chk_quy_trangthai` (Bảng `quy_cuu_tro`):**
   ```sql
   CHECK (trangthaiquy IN ('Chờ trích quỹ', 'Đã trích quỹ', 'Đã giải ngân', 'Đã hủy'))
   ```
2. **`uq_quy_madonhang` (Bảng `quy_cuu_tro`):**
   ```sql
   CREATE UNIQUE INDEX uq_quy_madonhang ON quy_cuu_tro(madonhang);
   ```
3. **`chk_doitra_trangthai` (Bảng `yeu_cau_doi_tra`):**
   ```sql
   CHECK (trangthaiyeucau IN ('Chờ xử lý', 'Đã đồng ý đổi', 'Đã hoàn tiền', 'Đã từ chối', 'Đã hoàn tất'))
   ```
4. **`uq_doitra_con_hieu_luc` (Bảng `yeu_cau_doi_tra`):**
   ```sql
   CREATE UNIQUE INDEX uq_doitra_con_hieu_luc ON yeu_cau_doi_tra(madonhang) 
   WHERE trangthaiyeucau <> 'Đã từ chối';
   ```
5. **`chk_bt_ton` (Bảng `bien_the`):**
   ```sql
   CHECK (soluongton >= 0)
   ```
6. **`chk_vc_sl` (Bảng `voucher`):**
   ```sql
   CHECK (soluongconlai >= 0)
   ```
7. **Khách vãng lai (Bảng `khach_hang`):**
   ```sql
   ALTER TABLE khach_hang ALTER COLUMN matk DROP NOT NULL;
   ```
