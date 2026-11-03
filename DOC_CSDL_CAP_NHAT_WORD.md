# TÀI LIỆU CẬP NHẬT THIẾT KẾ CƠ SỞ DỮ LIỆU (DÙNG ĐỂ CHỈNH BÁO CÁO WORD)

**Dự án:** Sàn Thương Mại Điện Tử Thú Cưng KIBO (Pet's Home)  
**Hệ quản trị CSDL:** PostgreSQL 15+ (Supabase)  
**Phiên bản cập nhật:** v2.1 (Đã đồng bộ 100% giữa Database thật, Code thực thi và Báo cáo)  
**Thời điểm trích xuất:** 02/11/2026 11:29:48  

---

## 1. TỔNG QUAN HỆ THỐNG CSDL (17 BẢNG)

Dữ liệu dưới đây được **trích xuất tự động 100% từ CSDL PostgreSQL thực tế** đang chạy thông qua hai truy vấn hệ thống `information_schema.columns` và `pg_constraint`. Tuyệt đối không thêm bớt cột hoặc kiểu dữ liệu ngoài schema thực tế.

### 1.1. Bảng `bien_the` (Biến thể sản phẩm (SKU, quy cách, giá bán, tồn kho))

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`mabienthe`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) | Mã biến thể SKU |
| 2 | **`masp`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) | Mã sản phẩm (khóa chính hoặc ngoại tham chiếu san_pham) |
| 3 | **`tenbienthe`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 4 | **`giaban`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |
| 5 | **`soluongton`** | `INTEGER` | - | KHÔNG (NOT NULL) | Số lượng tồn kho (ràng buộc chk_bt_ton: >= 0) |
| 6 | **`hinhanhbienthe`** | `VARCHAR` | 255 | CÓ (NULL) |  |
| 7 | **`giagoc`** | `NUMERIC` | - | CÓ (NULL) | Giá gốc trước khi giảm giá (bổ sung migration_v2) |

**Các ràng buộc toàn vẹn của bảng `bien_the`:**
- **`bien_the_giaban_check`**: `CHECK ((giaban > (0)::numeric))`
- **`bien_the_masp_fkey`**: `FOREIGN KEY (masp) REFERENCES san_pham(masp) ON DELETE CASCADE`
- **`bien_the_pkey`**: `PRIMARY KEY (mabienthe)`
- **`bien_the_soluongton_check`**: `CHECK ((soluongton >= 0))`
- **`chk_bt_ton`**: `CHECK ((soluongton >= 0))`

---

### 1.2. Bảng `chi_tiet_don_hang` (Chi tiết sản phẩm trong đơn hàng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`madonhang`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`mabienthe`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) | Mã biến thể SKU |
| 3 | **`soluong`** | `INTEGER` | - | KHÔNG (NOT NULL) |  |
| 4 | **`dongia`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |
| 5 | **`thanhtien`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |

**Các ràng buộc toàn vẹn của bảng `chi_tiet_don_hang`:**
- **`chi_tiet_don_hang_dongia_check`**: `CHECK ((dongia > (0)::numeric))`
- **`chi_tiet_don_hang_mabienthe_fkey`**: `FOREIGN KEY (mabienthe) REFERENCES bien_the(mabienthe) ON DELETE RESTRICT`
- **`chi_tiet_don_hang_madonhang_fkey`**: `FOREIGN KEY (madonhang) REFERENCES don_hang(madonhang) ON DELETE RESTRICT`
- **`chi_tiet_don_hang_pkey`**: `PRIMARY KEY (madonhang, mabienthe)`
- **`chi_tiet_don_hang_soluong_check`**: `CHECK ((soluong > 0))`
- **`chi_tiet_don_hang_thanhtien_check`**: `CHECK ((thanhtien > (0)::numeric))`

---

### 1.3. Bảng `chi_tiet_gio_hang` (Chi tiết sản phẩm trong giỏ hàng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`magiohang`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`mabienthe`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) | Mã biến thể SKU |
| 3 | **`soluong`** | `INTEGER` | - | KHÔNG (NOT NULL) |  |

**Các ràng buộc toàn vẹn của bảng `chi_tiet_gio_hang`:**
- **`chi_tiet_gio_hang_mabienthe_fkey`**: `FOREIGN KEY (mabienthe) REFERENCES bien_the(mabienthe) ON DELETE CASCADE`
- **`chi_tiet_gio_hang_magiohang_fkey`**: `FOREIGN KEY (magiohang) REFERENCES gio_hang(magiohang) ON DELETE CASCADE`
- **`chi_tiet_gio_hang_pkey`**: `PRIMARY KEY (magiohang, mabienthe)`
- **`chi_tiet_gio_hang_soluong_check`**: `CHECK ((soluong > 0))`

---

### 1.4. Bảng `chung_tu_giai_ngan` (Chứng từ giải ngân tài trợ cho trạm cứu hộ)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`machungtu`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`matram`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 3 | **`sotiengiaingan`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |
| 4 | **`ngaychuyenkhoan`** | `DATE` | - | KHÔNG (NOT NULL) |  |
| 5 | **`mucdichsudung`** | `TEXT` | - | KHÔNG (NOT NULL) |  |
| 6 | **`filehoadonchungtu`** | `VARCHAR` | 255 | KHÔNG (NOT NULL) |  |

**Các ràng buộc toàn vẹn của bảng `chung_tu_giai_ngan`:**
- **`chung_tu_giai_ngan_matram_fkey`**: `FOREIGN KEY (matram) REFERENCES tram_cuu_ho(matram) ON DELETE RESTRICT`
- **`chung_tu_giai_ngan_pkey`**: `PRIMARY KEY (machungtu)`
- **`chung_tu_giai_ngan_sotiengiaingan_check`**: `CHECK ((sotiengiaingan > (0)::numeric))`

---

### 1.5. Bảng `danh_muc` (Danh mục sản phẩm thú cưng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`madanhmuc`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`tendanhmuc`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 3 | **`mota`** | `TEXT` | - | CÓ (NULL) |  |
| 4 | **`trangthai`** | `VARCHAR` | 50 | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `danh_muc`:**
- **`danh_muc_pkey`**: `PRIMARY KEY (madanhmuc)`

---

### 1.6. Bảng `dia_chi_kh` (Sổ địa chỉ giao hàng của khách hàng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`madiachi`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`makh`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 3 | **`diachichitiet`** | `VARCHAR` | 255 | KHÔNG (NOT NULL) |  |
| 4 | **`phuongxa`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 5 | **`quanhuyen`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 6 | **`tinhthanh`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 7 | **`macdinh`** | `BOOLEAN` | - | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `dia_chi_kh`:**
- **`dia_chi_kh_makh_fkey`**: `FOREIGN KEY (makh) REFERENCES khach_hang(makh) ON DELETE CASCADE`
- **`dia_chi_kh_pkey`**: `PRIMARY KEY (madiachi)`

---

### 1.7. Bảng `don_hang` (Đơn đặt hàng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`madonhang`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`makh`** | `VARCHAR` | 20 | CÓ (NULL) |  |
| 3 | **`mavoucher`** | `VARCHAR` | 30 | CÓ (NULL) |  |
| 4 | **`ngaydathang`** | `TIMESTAMPTZ` | - | CÓ (NULL) |  |
| 5 | **`tongtienhang`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |
| 6 | **`tiengiamgia`** | `NUMERIC` | - | CÓ (NULL) |  |
| 7 | **`phivanchuyen`** | `NUMERIC` | - | CÓ (NULL) |  |
| 8 | **`tongthanhtoan`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |
| 9 | **`trangthaidonhang`** | `VARCHAR` | 50 | CÓ (NULL) |  |
| 10 | **`tennguoinhan`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 11 | **`sodienthoainhan`** | `VARCHAR` | 15 | KHÔNG (NOT NULL) |  |
| 12 | **`diachigiaohang`** | `VARCHAR` | 255 | KHÔNG (NOT NULL) |  |
| 13 | **`ghichu`** | `TEXT` | - | CÓ (NULL) |  |
| 14 | **`ngaygiaothanhcong`** | `TIMESTAMPTZ` | - | CÓ (NULL) | Thời điểm giao thành công làm mốc tính 3 ngày đổi trả (bổ sung migration_v2) |

**Các ràng buộc toàn vẹn của bảng `don_hang`:**
- **`don_hang_makh_fkey`**: `FOREIGN KEY (makh) REFERENCES khach_hang(makh) ON DELETE RESTRICT`
- **`don_hang_mavoucher_fkey`**: `FOREIGN KEY (mavoucher) REFERENCES voucher(mavoucher) ON DELETE SET NULL`
- **`don_hang_phivanchuyen_check`**: `CHECK ((phivanchuyen >= (0)::numeric))`
- **`don_hang_pkey`**: `PRIMARY KEY (madonhang)`
- **`don_hang_tiengiamgia_check`**: `CHECK ((tiengiamgia >= (0)::numeric))`
- **`don_hang_tongthanhtoan_check`**: `CHECK ((tongthanhtoan > (0)::numeric))`
- **`don_hang_tongtienhang_check`**: `CHECK ((tongtienhang > (0)::numeric))`
- **`don_hang_trangthaidonhang_check`**: `CHECK (((trangthaidonhang)::text = ANY ((ARRAY['Chờ xác nhận'::character varying, 'Chờ đóng gói'::character varying, 'Đã xuất kho'::character varying, 'Đang giao'::character varying, 'Giao thành công'::character varying, 'Đã hủy'::character varying])::text[])))`

---

### 1.8. Bảng `gio_hang` (Giỏ hàng của khách hàng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`magiohang`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`makh`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 3 | **`ngaycapnhat`** | `TIMESTAMPTZ` | - | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `gio_hang`:**
- **`gio_hang_makh_fkey`**: `FOREIGN KEY (makh) REFERENCES khach_hang(makh) ON DELETE CASCADE`
- **`gio_hang_makh_key`**: `UNIQUE (makh)`
- **`gio_hang_pkey`**: `PRIMARY KEY (magiohang)`

---

### 1.9. Bảng `khach_hang` (Hồ sơ khách hàng (Đăng ký & Vãng lai))

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`makh`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`matk`** | `VARCHAR` | 20 | CÓ (NULL) | Khóa ngoại tài khoản; NULL đối với khách vãng lai (DROP NOT NULL trong migration_v2) |
| 3 | **`hoten`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 4 | **`email`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 5 | **`ngaysinh`** | `DATE` | - | CÓ (NULL) |  |
| 6 | **`diemtichluy`** | `INTEGER` | - | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `khach_hang`:**
- **`khach_hang_diemtichluy_check`**: `CHECK ((diemtichluy >= 0))`
- **`khach_hang_email_key`**: `UNIQUE (email)`
- **`khach_hang_matk_fkey`**: `FOREIGN KEY (matk) REFERENCES tai_khoan(matk) ON DELETE RESTRICT`
- **`khach_hang_matk_key`**: `UNIQUE (matk)`
- **`khach_hang_pkey`**: `PRIMARY KEY (makh)`

---

### 1.10. Bảng `quy_cuu_tro` (Ghi nhận trích 5% doanh thu vào Quỹ cứu trợ)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`madonggop`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`madonhang`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 3 | **`sotiendonggop`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |
| 4 | **`ngayghinhan`** | `TIMESTAMPTZ` | - | CÓ (NULL) |  |
| 5 | **`matracuu`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 6 | **`trangthaiquy`** | `VARCHAR` | 50 | CÓ (NULL) | Trạng thái: Chờ trích quỹ, Đã trích quỹ, Đã giải ngân, Đã hủy |
| 7 | **`machungtu`** | `VARCHAR` | 20 | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `quy_cuu_tro`:**
- **`chk_quy_trangthai`**: `CHECK (((trangthaiquy)::text = ANY ((ARRAY['Chờ trích quỹ'::character varying, 'Đã trích quỹ'::character varying, 'Đã giải ngân'::character varying, 'Đã hủy'::character varying])::text[])))`
- **`quy_cuu_tro_machungtu_fkey`**: `FOREIGN KEY (machungtu) REFERENCES chung_tu_giai_ngan(machungtu) ON DELETE SET NULL`
- **`quy_cuu_tro_madonhang_fkey`**: `FOREIGN KEY (madonhang) REFERENCES don_hang(madonhang) ON DELETE RESTRICT`
- **`quy_cuu_tro_madonhang_key`**: `UNIQUE (madonhang)`
- **`quy_cuu_tro_matracuu_key`**: `UNIQUE (matracuu)`
- **`quy_cuu_tro_pkey`**: `PRIMARY KEY (madonggop)`
- **`quy_cuu_tro_sotiendonggop_check`**: `CHECK ((sotiendonggop >= (0)::numeric))`

---

### 1.11. Bảng `san_pham` (Sản phẩm thú cưng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`masp`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) | Mã sản phẩm (khóa chính hoặc ngoại tham chiếu san_pham) |
| 2 | **`madanhmuc`** | `VARCHAR` | 20 | CÓ (NULL) |  |
| 3 | **`tensp`** | `VARCHAR` | 200 | KHÔNG (NOT NULL) |  |
| 4 | **`thuonghieu`** | `VARCHAR` | 100 | CÓ (NULL) |  |
| 5 | **`motachitiet`** | `TEXT` | - | CÓ (NULL) |  |
| 6 | **`hinhanhchinh`** | `VARCHAR` | 255 | CÓ (NULL) |  |
| 7 | **`trangthai`** | `VARCHAR` | 50 | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `san_pham`:**
- **`san_pham_madanhmuc_fkey`**: `FOREIGN KEY (madanhmuc) REFERENCES danh_muc(madanhmuc) ON DELETE SET NULL`
- **`san_pham_pkey`**: `PRIMARY KEY (masp)`
- **`san_pham_trangthai_check`**: `CHECK (((trangthai)::text = ANY ((ARRAY['Đang bán'::character varying, 'Tạm hết hàng'::character varying, 'Ngừng kinh doanh'::character varying, 'Ẩn'::character varying])::text[])))`

---

### 1.12. Bảng `sdt_kh` (Số điện thoại liên hệ của khách hàng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`makh`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`sodienthoai`** | `VARCHAR` | 15 | KHÔNG (NOT NULL) |  |

**Các ràng buộc toàn vẹn của bảng `sdt_kh`:**
- **`sdt_kh_makh_fkey`**: `FOREIGN KEY (makh) REFERENCES khach_hang(makh) ON DELETE CASCADE`
- **`sdt_kh_pkey`**: `PRIMARY KEY (makh, sodienthoai)`

---

### 1.13. Bảng `tai_khoan` (Tài khoản người dùng & Quản trị viên)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`matk`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`tendangnhap`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 3 | **`matkhau`** | `VARCHAR` | 255 | KHÔNG (NOT NULL) |  |
| 4 | **`vaitro`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 5 | **`trangthai`** | `VARCHAR` | 50 | CÓ (NULL) |  |
| 6 | **`ngaytao`** | `TIMESTAMPTZ` | - | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `tai_khoan`:**
- **`tai_khoan_pkey`**: `PRIMARY KEY (matk)`
- **`tai_khoan_tendangnhap_key`**: `UNIQUE (tendangnhap)`
- **`tai_khoan_vaitro_check`**: `CHECK (((vaitro)::text = ANY ((ARRAY['Admin'::character varying, 'User'::character varying])::text[])))`

---

### 1.14. Bảng `thanh_toan` (Giao dịch thanh toán đơn hàng)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`magiaodich`** | `VARCHAR` | 50 | KHÔNG (NOT NULL) |  |
| 2 | **`madonhang`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 3 | **`phuongthuctt`** | `VARCHAR` | 50 | KHÔNG (NOT NULL) |  |
| 4 | **`sotien`** | `NUMERIC` | - | KHÔNG (NOT NULL) | Số tiền thanh toán thực tế của đơn hàng |
| 5 | **`thoigiantt`** | `TIMESTAMPTZ` | - | CÓ (NULL) | Thời điểm thực hiện giao dịch thanh toán |
| 6 | **`trangthaitt`** | `VARCHAR` | 50 | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `thanh_toan`:**
- **`thanh_toan_madonhang_fkey`**: `FOREIGN KEY (madonhang) REFERENCES don_hang(madonhang) ON DELETE RESTRICT`
- **`thanh_toan_madonhang_key`**: `UNIQUE (madonhang)`
- **`thanh_toan_pkey`**: `PRIMARY KEY (magiaodich)`
- **`thanh_toan_sotien_check`**: `CHECK ((sotien > (0)::numeric))`

---

### 1.15. Bảng `tram_cuu_ho` (Trạm cứu hộ động vật đối tác)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`matram`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`tentram`** | `VARCHAR` | 150 | KHÔNG (NOT NULL) |  |
| 3 | **`nguoidaidien`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 4 | **`sodienthoai`** | `VARCHAR` | 15 | KHÔNG (NOT NULL) |  |
| 5 | **`diachi`** | `VARCHAR` | 255 | KHÔNG (NOT NULL) |  |
| 6 | **`khuvuc`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 7 | **`trangthaihoptac`** | `VARCHAR` | 50 | CÓ (NULL) |  |

**Các ràng buộc toàn vẹn của bảng `tram_cuu_ho`:**
- **`tram_cuu_ho_pkey`**: `PRIMARY KEY (matram)`

---

### 1.16. Bảng `voucher` (Mã ưu đãi / Khuyến mãi)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`mavoucher`** | `VARCHAR` | 30 | KHÔNG (NOT NULL) |  |
| 2 | **`tenvoucher`** | `VARCHAR` | 100 | KHÔNG (NOT NULL) |  |
| 3 | **`loaigiamgia`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 4 | **`giatrigiam`** | `NUMERIC` | - | KHÔNG (NOT NULL) |  |
| 5 | **`donhangtoithieu`** | `NUMERIC` | - | CÓ (NULL) |  |
| 6 | **`ngaybatdau`** | `TIMESTAMPTZ` | - | KHÔNG (NOT NULL) |  |
| 7 | **`ngayketthuc`** | `TIMESTAMPTZ` | - | KHÔNG (NOT NULL) |  |
| 8 | **`soluongconlai`** | `INTEGER` | - | KHÔNG (NOT NULL) | Số lượt dùng còn lại (ràng buộc chk_vc_sl: >= 0) |
| 9 | **`kichhoat`** | `BOOLEAN` | - | KHÔNG (NOT NULL) | Trạng thái bật/tắt (bổ sung migration_v2) |

**Các ràng buộc toàn vẹn của bảng `voucher`:**
- **`chk_vc_sl`**: `CHECK ((soluongconlai >= 0))`
- **`voucher_giatrigiam_check`**: `CHECK ((giatrigiam > (0)::numeric))`
- **`voucher_loaigiamgia_check`**: `CHECK (((loaigiamgia)::text = ANY ((ARRAY['PhanTram'::character varying, 'SoTien'::character varying])::text[])))`
- **`voucher_pkey`**: `PRIMARY KEY (mavoucher)`
- **`voucher_soluongconlai_check`**: `CHECK ((soluongconlai >= 0))`

---

### 1.17. Bảng `yeu_cau_doi_tra` (Yêu cầu đổi trả hàng trong 3 ngày)

| STT | Tên cột | Kiểu dữ liệu | Độ dài tối đa | Cho phép NULL? | Ghi chú / Diễn giải nghiệp vụ |
|:---:|:---|:---|:---:|:---:|:---|
| 1 | **`mayeucau`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 2 | **`madonhang`** | `VARCHAR` | 20 | KHÔNG (NOT NULL) |  |
| 3 | **`lydodoitra`** | `TEXT` | - | KHÔNG (NOT NULL) |  |
| 4 | **`hinhanhminhchung`** | `VARCHAR` | 255 | CÓ (NULL) |  |
| 5 | **`ngayyeucau`** | `TIMESTAMPTZ` | - | CÓ (NULL) |  |
| 6 | **`trangthaiyeucau`** | `VARCHAR` | 50 | CÓ (NULL) | Trạng thái: Chờ xử lý, Đã đồng ý đổi, Đã hoàn tiền, Đã từ chối, Đã hoàn tất |
| 7 | **`huongxuly`** | `VARCHAR` | 50 | CÓ (NULL) |  |
| 8 | **`ngayxuly`** | `TIMESTAMPTZ` | - | CÓ (NULL) | Thời điểm Admin duyệt đổi trả (bổ sung migration_v2) |

**Các ràng buộc toàn vẹn của bảng `yeu_cau_doi_tra`:**
- **`chk_doitra_trangthai`**: `CHECK (((trangthaiyeucau)::text = ANY ((ARRAY['Chờ xử lý'::character varying, 'Đã đồng ý đổi'::character varying, 'Đã hoàn tiền'::character varying, 'Đã từ chối'::character varying, 'Đã hoàn tất'::character varying])::text[])))`
- **`yeu_cau_doi_tra_madonhang_fkey`**: `FOREIGN KEY (madonhang) REFERENCES don_hang(madonhang) ON DELETE RESTRICT`
- **`yeu_cau_doi_tra_pkey`**: `PRIMARY KEY (mayeucau)`

---

## 2. GIÁ TRỊ ENUM / HẰNG SỐ CHUẨN XÁC TRONG CODE VÀ CSDL

Khi viết báo cáo hoặc trả lời hội đồng, dùng **chính xác 100% các giá trị chuỗi** sau đây:

### 2.1. Bảng `thanh_toan`
- **Phương thức thanh toán (`phuongthuctt`):**
  - `'Thanh toán khi nhận hàng (COD)'`
  - `'Chuyển khoản VietQR'`
- **Trạng thái thanh toán (`trangthaitt`):**
  - `'Chờ thanh toán'`
  - `'Thành công'`
  - `'Hoàn tiền'`

### 2.2. Bảng `don_hang`
- **Trạng thái đơn hàng (`trangthaidonhang`):**
  - `'Chờ xác nhận'` $\rightarrow$ `'Chờ đóng gói'` $\rightarrow$ `'Đã xuất kho'` $\rightarrow$ `'Đang giao'` $\rightarrow$ `'Giao thành công'` $\rightarrow$ `'Đã hủy'`

### 2.3. Bảng `quy_cuu_tro`
- **Trạng thái quỹ (`trangthaiquy`):**
  - `'Chờ trích quỹ'` (lúc khách đặt hàng)
  - `'Đã trích quỹ'` (khi đơn chuyển 'Giao thành công')
  - `'Đã giải ngân'` (khi chuyển tiền cho trạm cứu hộ kèm chứng từ)
  - `'Đã hủy'` (khi đơn hàng bị hủy hoặc đổi trả hoàn tiền)

### 2.4. Bảng `yeu_cau_doi_tra`
- **Trạng thái đổi trả (`trangthaiyeucau`):**
  - `'Chờ xử lý'`
  - `'Đã đồng ý đổi'`
  - `'Đã hoàn tiền'`
  - `'Đã từ chối'`
  - `'Đã hoàn tất'`

### 2.5. Tài khoản Demo kiểm thử
- **`admin@kibo.vn`**: Vai trò `'Admin'`, mật khẩu băm Bcrypt: `MatKhauDemo@2026`
- **`user1@gmail.com`**: Vai trò `'User'`, Mã khách hàng `KH001`, mật khẩu: `MatKhauDemo@2026`
- **`hoangphuc@gmail.com`**: Vai trò `'User'`, Mã khách hàng `KH002`, mật khẩu: `MatKhauDemo@2026`

---

## 3. CÁCH ĐỐI CHIẾU TRỰC TIẾP TRÊN CSDL (DÀNH CHO HỘI ĐỒNG BẢO VỆ)

Để chứng minh tính trung thực và chuẩn xác trước hội đồng, chỉ cần mở PostgreSQL / Supabase SQL Editor và chạy 2 câu lệnh sau:

```sql
-- 1. Xem danh sách toàn bộ cột, kiểu dữ liệu và độ dài:
SELECT table_name, column_name, data_type, character_maximum_length, is_nullable
FROM information_schema.columns
WHERE table_schema = 'public'
ORDER BY table_name, ordinal_position;
```

```sql
-- 2. Xem toàn bộ khóa chính, khóa ngoại, CHECK constraint và Unique Index:
SELECT conrelid::regclass AS table_name, conname AS constraint_name, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE connamespace = 'public'::regnamespace
ORDER BY table_name, constraint_name;
```
