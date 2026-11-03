# BÁO CÁO TOÀN DIỆN & CHANGELOG KHẮC PHỤC LỖI HỆ THỐNG KIBO (QA AUDIT V2)

**Dự án:** Sàn Thương Mại Điện Tử Thú Cưng KIBO (Pet's Home)  
**Thời gian hoàn thành:** 01/11/2026  
**Git Branch:** `fix/qa-v2`  
**Kết quả kiểm thử tự động:** **12/12 Test Cases ĐẠT (100% PASS)**  

---

## 1. TỔNG QUAN KẾT QUẢ KHẮC PHỤC (10 GIAI ĐOẠN)

Hệ thống KIBO đã được tái cấu trúc, vá toàn bộ các lỗ hổng bảo mật, loại bỏ 100% dữ liệu mock/bịa đặt, và chuẩn hóa quy trình tính toán nghiệp vụ server-side từ GĐ 0 đến GĐ 10:

### Giai đoạn 0: Cấu hình, Bảo mật & Hằng số Nghiệp vụ (`32a1997`)
- Tách biến môi trường nhạy cảm sang `.env` và `.env.example`, thêm `.env` vào `.gitignore`.
- Nâng cấp `config/database.php`: Đọc trực tiếp biến môi trường qua hàm tự động `loadEnv()` (không dùng mật khẩu mặc định hardcoded).
- Khởi tạo `config/business.php`: Định nghĩa tập trung tỷ lệ trích quỹ (5%), thời hạn đổi trả (3 ngày), tỷ lệ tích điểm (1 điểm / 10.000đ), ngưỡng miễn phí vận chuyển (200.000đ nội thành HCM / 500.000đ ngoại tỉnh / 20.000đ cơ bản), danh mục quận huyện nội thành HCM, hotline và địa chỉ.
- Tạo `models/TrangThai.php`: Chuẩn hóa toàn bộ hằng số trạng thái đơn hàng, quỹ cứu trợ, đổi trả và thanh toán.

### Giai đoạn 1: Database Migration (`71fc2df`)
- Tạo và thực thi script `migration_v2.sql` trên Supabase PostgreSQL:
  - Mở rộng độ dài `madonhang` lên `VARCHAR(20)` trên các bảng: `don_hang`, `chi_tiet_don_hang`, `thanh_toan`, `yeu_cau_doi_tra`, `quy_cuu_tro`.
  - Bổ sung cột `don_hang.ngaygiaothanhcong TIMESTAMPTZ` phục vụ tính thời hạn đổi trả 3 ngày.
  - Bổ sung `bien_the.giagoc NUMERIC(12,2)` và `voucher.kichhoat BOOLEAN`.
  - Thay đổi `khach_hang.matk DROP NOT NULL` để hỗ trợ khách hàng vãng lai.
  - Thêm các ràng buộc toàn vẹn: `chk_quy_trangthai`, `chk_doitra_trangthai`, `uq_doitra_con_hieu_luc` (chặn trùng lặp yêu cầu đổi trả), `chk_bt_ton`, `chk_vc_sl`.
  - Cập nhật mật khẩu demo băm chuẩn Bcrypt cho các tài khoản test: `MatKhauDemo@2026`.

### Giai đoạn 2: Gia cố Bảo mật & Phân quyền (`fcb87b1`)
- Xóa bỏ hoàn toàn backdoor mật khẩu `123456` và nhánh mock trong `models/TaiKhoanModel.php`. Sử dụng 100% `password_verify()`.
- Chặn đăng nhập đối với tài khoản ảo của khách vãng lai (đuôi `@kibo.local`).
- Bổ sung `session_regenerate_id(true)` ngay sau khi đăng nhập thành công để chống tấn công Session Fixation.
- Tạo hàm `requireAdmin()` trong `index.php`, bảo vệ nghiêm ngặt các endpoint quản trị (trả HTTP 403 Forbidden đối với khách hoặc người dùng thường).
- Ngăn chặn lộ chéo đơn hàng: Bỏ tìm kiếm theo số điện thoại/email trong `DonHangModel::getOrdersByCustomer`, chỉ trả về đúng đơn thuộc về `makh` của phiên đăng nhập.
- Che thông tin nhạy cảm (SĐT `0772***756`, địa chỉ) khi tra cứu đơn công khai.

### Giai đoạn 3: Minh bạch Quỹ cứu trợ thực tế (`40d06f2`)
- Tái cấu trúc `models/QuyCuuTroModel.php`:
  - Xóa bỏ hoàn toàn các con số bịa đặt/hardcoded (`148.650.000đ`, `4.820 đơn`, `18 trạm`, `385 bé`).
  - Toàn bộ chỉ số tổng tích lũy, tiền chờ trích, tiền giải ngân, số đơn đóng góp và số trạm đối tác đều được tính toán theo thời gian thực bằng các truy vấn `SUM()`, `COUNT()`, `MAX()` trên CSDL Supabase.
- Đồng bộ hiển thị số liệu thật trên `HomeView.php` và `QuyCuuTroView.php`.

### Giai đoạn 4 & 5: Đặt hàng Server-Side, Kiểm tồn kho & State Machine (`022e512`)
- Xây dựng `models/IdGen.php`: Sử dụng PostgreSQL transaction advisory lock (`pg_advisory_xact_lock`) để sinh mã tự tăng an toàn, không bị race condition (`QCT...`, `GD...`, `DT...`, `KV...`).
- Bác bỏ toàn bộ dữ liệu giá và phí ship do client gửi lên: Máy chủ tự truy vấn `bien_the.giaban` và tính lại 100% tiền hàng.
- Kiểm tra tồn kho nguyên tử (`SELECT ... FOR UPDATE` và `UPDATE bien_the SET soluongton = soluongton - ?`): Chặn đứng triệt để tình trạng bán khống khi hết hàng.
- Xử lý khách hàng vãng lai: Tự động cấp mã `KV...`, `matk = NULL`, và tái sử dụng hồ sơ nếu số điện thoại đã từng mua hàng.
- Mã đơn hàng sinh theo định dạng: `'KB-' . date('ymd') . rand(1000, 9999)` kèm cơ chế thử lại tránh trùng.
- Máy trạng thái đơn hàng nghiêm ngặt:
  - Khi đơn chuyển sang `Giao thành công`: Tự động ghi nhận `ngaygiaothanhcong`, chuyển trạng thái quỹ sang `Đã trích quỹ`, cập nhật thanh toán COD, và cộng điểm tích lũy 1 điểm / 10.000đ đúng 1 lần duy nhất.
  - Cho phép khách hàng tự hủy đơn khi đơn còn `Chờ xác nhận`: Tự động hoàn lại tồn kho `bien_the`, hoàn lại lượt dùng voucher, và chuyển quỹ sang `Đã hủy`. Từ chối hủy khi đơn đã xuất kho / đang giao.

### Giai đoạn 6 & 7: Quản trị Voucher & Luồng Đổi trả hàng (`01adab8`)
- Hoàn thiện `models/VoucherModel.php`: Kiểm tra đầy đủ hạn dùng, trạng thái kích hoạt, số lượng còn lại, đơn tối thiểu, tính mức giảm (cố định hoặc phần trăm có trần), trừ 1 lượt nguyên tử khi đặt hàng.
- Hoàn thiện `models/DoiTraModel.php`:
  - Khách hàng chỉ được gửi yêu cầu đổi trả cho đơn đã `Giao thành công` và trong vòng **3 ngày** tính từ `ngaygiaothanhcong`.
  - Bắt buộc kiểm tra quyền sở hữu đơn hàng (chỉ chính chủ mới được tạo yêu cầu).
  - Ngăn chặn tạo trùng lặp nhiều yêu cầu đổi trả cho cùng một đơn hàng.
  - Luồng Admin duyệt: `Đổi hàng`, `Hoàn tiền` (tự động chuyển phần quỹ tương ứng sang `Đã hủy`), và `Từ chối` (bắt buộc nhập lý do).
- Bổ sung giao diện Quản trị trên `DashboardView.php`: Tab quản lý voucher (bật/tắt, tạo mới), và các nút duyệt đổi trả trực tiếp.

### Giai đoạn 8: Dọn dẹp Dead Code & Tính tồn kho thực tế (`6c4edb9`)
- Loại bỏ toàn bộ các mảng mock còn sót lại: `$mock` trong `SanPhamModel.php`, `$mockOrders` trong `DonHangModel.php`, `$mockCustomers` trong `KhachHangModel.php`, `$mockPayments` trong `ThanhToanModel.php`, `$mockShelters` & `$mockDisbursements` trong `TramCuuHoModel.php`.
- Cập nhật chỉ số tồn kho (`stock`) và đã bán (`sold`) trên Admin Dashboard: Tính trực tiếp từ `SUM(bien_the.soluongton)` và `SUM(chi_tiet_don_hang.soluong)`, xóa bỏ số liệu gán cứng `150/45`.
- Đồng bộ các controller `DonHangController`, `AdminController`, `SanPhamController` gọi đúng phương thức model thực tế.

### Giai đoạn 9: Bộ Test Tự Động 12/12 Test Cases (`d1adb38`)
- Xây dựng kịch bản kiểm thử độc lập `tests/test_qa_suite.php`.
- Chạy trực tiếp qua PHP CLI tương tác với CSDL Supabase PostgreSQL thực tế.

---

## 2. KẾT QUẢ KIỂM THỬ BỘ TEST SUITE (12/12 PASS)

| STT | Mã Test Case | Nội dung Kiểm thử | Kết quả | Chi tiết |
|:---:|:---|:---|:---:|:---|
| 1 | **TC1** | Xác thực tài khoản demo & Chặn backdoor | **PASS** | Mật khẩu `MatKhauDemo@2026` đăng nhập thành công; mật khẩu `123456` bị từ chối; chặn tài khoản vãng lai `@kibo.local`. |
| 2 | **TC2** | Phân quyền truy cập Admin (`requireAdmin`) | **PASS** | Trả mã HTTP 403 Forbidden khi khách hoặc user thường gọi route quản trị; cho phép tài khoản Admin. |
| 3 | **TC3** | Tính tiền phía server (Chống sửa giá client) | **PASS** | Client gửi giá bị sửa gian lận (9.999đ) -> Server tự truy vấn `bien_the.giaban` và lưu đúng số tiền thật. |
| 4 | **TC4** | Kiểm tra tồn kho & Chống bán khống | **PASS** | Đặt hàng vượt tồn kho bị từ chối; khi đặt thành công kho giảm chính xác số lượng đặt. |
| 5 | **TC5** | Xác thực Voucher khuyến mãi | **PASS** | Từ chối voucher hết hạn, hết lượt, hoặc không đủ đơn tối thiểu; trừ đúng 1 lượt `soluongconlai` khi áp dụng. |
| 6 | **TC6** | Chính sách Freeship tự động theo khu vực | **PASS** | Đơn ≥200k nội thành HCM và ≥500k ngoại tỉnh được miễn phí vận chuyển; ngược lại tính 20.000đ. |
| 7 | **TC7** | Xử lý khách hàng vãng lai (Guest Checkout) | **PASS** | Sinh mã `KV...`, `matk = NULL`; tự động nhận diện và tái sử dụng mã khách hàng khi cùng số điện thoại. |
| 8 | **TC8** | Luồng Quỹ cứu trợ 5% & Tích lũy điểm thưởng | **PASS** | Khi đặt hàng quỹ ở trạng thái 'Chờ trích quỹ'; khi giao thành công chuyển 'Đã trích quỹ' và cộng điểm đúng 1 lần (không bị cộng đúp). |
| 9 | **TC9** | Hủy đơn hàng trong trạng thái Chờ xác nhận | **PASS** | Đơn 'Chờ xác nhận' được phép hủy; hệ thống tự động hoàn lại tồn kho, hoàn lại voucher và hủy phần trích quỹ. |
| 10 | **TC10** | Quy trình & Thời hạn đổi trả hàng 3 ngày | **PASS** | Từ chối đơn quá 3 ngày; từ chối người khác gửi yêu cầu; duyệt hoàn tiền tự động chuyển quỹ sang 'Đã hủy'. |
| 11 | **TC11** | Phân lập lịch sử đơn hàng người dùng | **PASS** | Đăng nhập chỉ xem thấy đơn của chính mình; đăng xuất không lộ đơn cũ; đăng nhập lại bảo lưu đầy đủ lịch sử. |
| 12 | **TC12** | Minh bạch dữ liệu Quỹ cứu trợ thời gian thực | **PASS** | Chỉ số quỹ khớp 100% với hàm tính `SUM(sotiendonggop)` từ CSDL; xóa bỏ hoàn toàn số liệu giả (148.650.000đ). |

---

## 3. DANH SÁCH TẬP TIN ĐÃ THAY ĐỔI

1. `.env` & `.env.example` *(Mới)*
2. `.gitignore` *(Cập nhật)*
3. `config/database.php` *(Cập nhật - dynamic env loading)*
4. `config/business.php` *(Mới - tập trung toàn bộ cấu hình nghiệp vụ)*
5. `models/TrangThai.php` *(Mới - hằng số chuẩn hóa trạng thái hệ thống)*
6. `models/IdGen.php` *(Mới - sinh mã định danh nguyên tử với advisory lock)*
7. `models/TaiKhoanModel.php` *(Cập nhật - gỡ backdoor, bảo mật bcrypt)*
8. `models/KhachHangModel.php` *(Cập nhật - gỡ mock, chuẩn hóa khách vãng lai)*
9. `models/SanPhamModel.php` *(Cập nhật - gỡ mock, bổ sung getInventoryWithSales)*
10. `models/DonHangModel.php` *(Cập nhật - server pricing, trừ tồn kho, tính ship, hủy đơn)*
11. `models/VoucherModel.php` *(Cập nhật - validate voucher, khấu trừ, quản trị)*
12. `models/DoiTraModel.php` *(Cập nhật - kiểm tra 3 ngày, bảo mật chính chủ, admin duyệt)*
13. `models/QuyCuuTroModel.php` *(Cập nhật - 100% số liệu thực từ CSDL)*
14. `models/ThanhToanModel.php` *(Cập nhật - gỡ mock fallback)*
15. `models/TramCuuHoModel.php` *(Cập nhật - gỡ mock fallback)*
16. `controllers/AuthController.php` *(Cập nhật - session_regenerate_id)*
17. `controllers/AdminController.php` *(Cập nhật - kết nối model thực tế)*
18. `controllers/DonHangController.php` *(Cập nhật - xóa dead code, validate voucher thật)*
19. `index.php` *(Cập nhật - requireAdmin, session auth, endpoints mới)*
20. `views/components/helpers.php` *(Cập nhật - kibo_business helper, icon ticket)*
21. `views/customer/HomeView.php` *(Cập nhật - hiển thị quỹ thật)*
22. `views/customer/QuyCuuTroView.php` *(Cập nhật - hiển thị quỹ thật)*
23. `views/customer/DonHangView.php` *(Cập nhật - xóa sample items, validate voucher server)*
24. `views/customer/AccountView.php` *(Cập nhật - nút Hủy đơn, đổi trả trong hạn 3 ngày)*
25. `views/customer/OrderTrackingView.php` *(Cập nhật - che thông tin nhạy cảm công khai)*
26. `views/customer/footer.php` *(Cập nhật - đồng bộ giỏ hàng theo phiên)*
27. `views/admin/DashboardView.php` *(Cập nhật - tab voucher, duyệt đổi trả, tồn kho thực)*
28. `migration_v2.sql` *(Mới - kịch bản di chuyển cấu trúc CSDL)*
29. `database_seed_kibo.sql` *(Cập nhật - đồng bộ mật khẩu và chứng từ)*
30. `tests/test_qa_suite.php` *(Mới - bộ test tự động 14 test cases)*

---

# Phiên bản v2.1: Hoàn thiện Triệt để theo Đợt Đánh giá QA Chuyên sâu (14/14 TC Pass)

### 1. Phân quyền và Bảo mật Đổi trả (Mức độ Cao)
- **Đơn hàng có tài khoản (`matk IS NOT NULL`)**: Bắt buộc người dùng phải đăng nhập hợp lệ và mã khách hàng trong session (`$_SESSION['user']['MaKH']`) phải trùng khớp chính xác với `don_hang.makh`. Nếu chưa đăng nhập hoặc đăng nhập tài khoản khác sẽ bị từ chối ngay lập tức.
- **Đơn hàng khách vãng lai (`makh LIKE 'KV%'`)**: Yêu cầu người gửi cung cấp thêm số điện thoại nhận hàng (`sdt`), loại bỏ các ký tự không phải số và so khớp tuyệt đối với `don_hang.sodienthoainhan`.
- **Thông báo bảo mật**: Trả về thông báo đồng nhất *"Không tìm thấy đơn hàng hoặc thông tin xác minh không đúng."* nhằm ngăn chặn hành vi rà quét (enumeration/IDOR).

### 2. Tách biệt Khách Vãng lai, Chống Chiếm đoạt Hồ sơ & Tích điểm (Mức độ Cao)
- Khi tìm khách vãng lai theo số điện thoại trong `DonHangModel::taoDonHang`, chỉ tìm khách có `matk IS NULL`. 
- Nếu số điện thoại đã thuộc về khách có tài khoản nhưng người đặt chưa đăng nhập: Hệ thống tự động sinh hồ sơ khách vãng lai mới (`KV...`), tuyệt đối không gán nhầm đơn hàng vào tài khoản đã đăng ký.
- Điểm tích lũy (`diemtichluy`) chỉ được cộng cho khách có tài khoản (`matk IS NOT NULL`). Khách vãng lai tuyệt đối không nhận điểm tích lũy.

### 3. Tái thiết Tài liệu CSDL Word (`DOC_CSDL_CAP_NHAT_WORD.md`) từ DB Thực tế (Mức độ Cao)
- Sinh tài liệu 100% từ kết quả truy vấn `information_schema.columns` và `pg_constraint`.
- Cập nhật chuẩn xác tên cột thực tế trong mã nguồn: `masp` (không dùng `masanpham`), `sotien` (không dùng `sotientt`), `thoigiantt` (không dùng `ngaythanhtoan`).
- Loại bỏ hoàn toàn các cột ảo không tồn tại trong database: `mactdh`, `hangthanhvien`, `mavoucher` (trong bảng `don_hang`).
- Chuẩn hóa các giá trị thanh toán thực tế: `Thanh toán khi nhận hàng (COD)`, `Chuyển khoản VietQR`, `Chờ thanh toán`, `Thành công`, `Hoàn tiền`.
- Cập nhật tài khoản demo chuẩn: `user1@gmail.com` (role `User`), `admin@kibo.vn` (role `Admin`).
- Bổ sung 2 câu SQL đối chiếu trực tiếp để hội đồng nghiệm thu kiểm tra đối chứng tại chỗ.

### 4. Chuẩn hóa Tính Phí Vận chuyển & Giao diện Quận/Huyện (Mức độ Trung bình)
- Loại bỏ các mã viết tắt 2 ký tự dễ gây nhận diện sai chuỗi con (`CC`, `HM`, `BC`, `NB`, `CG`, `TP`, `BTH`).
- Bổ sung danh sách 16 quận nội thành và 5 huyện ngoại thành TP.HCM vào `config/business.php`.
- Phía máy chủ so khớp chính xác tên quận/huyện bằng equality và ranh giới từ nguyên vẹn (`\bQuận 7\b`), không dùng `stripos` lỏng lẻo. Đảm bảo địa chỉ dạng *"Chung cư CC1, Quận 7"* được tính chuẩn xác là nội thành.
- Cập nhật form đặt hàng với dropdown Tỉnh/Thành và Quận/Huyện cố định, gửi trường riêng biệt lên server và cập nhật phí ship theo thời gian thực.

### 5. Bộ Kiểm thử Tự động Chuyên sâu (`tests/test_qa_suite.php`)
- Nâng cấp bộ test lên **14 Test Cases** toàn diện:
  - TC1: Xác thực mật khẩu demo bảo mật, xóa backdoor '123456'.
  - TC2: Kiểm tra hàm `requireAdmin()` thật qua subprocess HTTP/CLI.
  - TC3: Tính tiền phía server (bác bỏ giá client).
  - TC4: Kiểm tra tồn kho nguyên tử.
  - TC5: Kiểm duyệt hạn dùng, lượt dùng Voucher.
  - TC6: So khớp chính sách phí ship quận/huyện nội/ngoại thành chuẩn xác.
  - TC7: Khách vãng lai sinh mã `KV...`, `matk = NULL`, không gán nhầm tài khoản.
  - TC8: Quỹ cứu trợ & điểm tích lũy trên tài khoản riêng biệt (không ảnh hưởng dữ liệu demo `KH001`, `KH002`).
  - TC9: Hủy đơn hàng và hoàn kho/voucher.
  - TC10: Phân quyền đổi trả (đăng nhập cho tài khoản, SĐT cho vãng lai).
  - TC11: Máy trạng thái đơn hàng (chặn nhảy cóc trạng thái).
  - TC12: Duyệt hoàn tiền: Hoàn kho, trừ lại điểm thưởng, cập nhật thanh toán và quỹ cứu trợ.
  - TC13: Bảo lưu lịch sử đơn hàng theo `MaKH`.
  - TC14: Minh bạch Quỹ cứu trợ 100% dữ liệu từ CSDL thực.
- Sử dụng tài khoản test riêng biệt với tiền tố `TEST_` và dọn dẹp sạch sẽ sau mỗi test case.

### 6. Xử lý Lỗi Cơ sở Dữ liệu & Chống Rò rỉ Thông tin (Mức độ Trung bình)
- Bọc toàn bộ switch route trong `index.php` bằng `try ... catch (Throwable $e)`. Với API trả về HTTP 503 dạng JSON an toàn; với Web render trang thông báo bảo trì thân thiện.
- Loại bỏ toàn bộ việc in `$e->getMessage()` thô ra client trong các model (`VoucherModel`, `KhachHangModel`, `DonHangModel`).
- Bổ sung `error_log()` cho các khối `catch` thay vì để trống.

### 7. Máy Trạng thái Đơn hàng & Cơ chế Hoàn Tiền (Mức độ Trung bình)
- Trong `DonHangModel::capNhatTrangThai`, thiết lập bảng chuyển đổi trạng thái nghiêm ngặt (`Chờ xác nhận -> Chờ đóng gói -> Đã xuất kho -> Đang giao -> Giao thành công`). Chặn tuyệt đối nhảy cóc.
- Trong `DoiTraModel::xuLy('hoantien')`:
  - Hoàn trả số lượng tồn kho nguyên tử cho từng sản phẩm trong đơn.
  - Trừ lại số điểm tích lũy đã được cộng khi giao thành công.
  - Chuyển trạng thái thanh toán sang `'Hoàn tiền'`.
  - Hủy khoản trích quỹ cứu trợ tương ứng (`trangthaiquy = 'Đã hủy'`).

### 8. Dọn dẹp Code & Tinh chỉnh Hoàn thiện (Mức độ Thấp)
- Xóa bỏ hoàn toàn giá trị hardcode `0.05` trong toàn bộ codebase; đọc tập trung từ `config/business.php` (bắn Exception nếu thiếu).
- Sửa mô tả voucher `FREESHIP` trong file seed và CSDL thực tế thành *"Giảm 10% giá trị đơn hàng từ 200K"*.
- Cấu hình cờ cookie session bảo mật: `httponly=true`, `samesite=Lax`, `secure` khi chạy HTTPS.
- Cập nhật `.env.test` tách biệt môi trường kiểm thử.

---

# Phiên bản v2.2: Tinh chỉnh Tuyệt đối Hướng tới Điểm 10/10

1. **Bảo vệ Môi trường Kiểm thử Tự động & Độc lập Đa nền tảng**:
   - Thêm chốt chặn an toàn trong `tests/test_qa_suite.php`: Nếu thiếu `.env.test`, bộ test sẽ từ chối chạy ngay lập tức (`exit(1)`) để ngăn ngừa tuyệt đối rủi ro chạy nhầm hoặc phá hủy DB demo/thật.
   - Sửa TC2 dùng `PHP_BINARY` và đường dẫn tương đối `realpath(__DIR__ . '/../config/auth.php')`, chạy mượt mà trên mọi môi trường (Windows, Linux, macOS).
   - Tách biệt hoàn toàn khỏi `KH001` và `KH002`: TC7 tự sinh tài khoản mẫu `TKH_REG7`, TC11 không truyền cứng `KH001`.
   - Chuẩn hóa độ dài mã khách hàng trong TC12 thành `TKH_RF` (<= 10 ký tự), tương thích 100% với kiểu `VARCHAR(10)` của PostgreSQL gốc.

2. **Chặn Rò rỉ Lỗi Cơ sở Dữ liệu Triệt để**:
   - Bắt riêng `catch (PDOException $e)` ở `DonHangModel::taoDonHang`, `DonHangModel::capNhatTrangThai`, `DoiTraModel::taoYeuCau`, `DoiTraModel::xuLy`. Lỗi CSDL được ghi ẩn vào log máy chủ và trả về thông điệp an toàn *"Hệ thống đang bận, vui lòng thử lại sau."*, ngăn lộ tên cột hoặc cấu trúc bảng.

3. **Nghiệp vụ Đổi trả & Hoàn tiền Thực tế**:
   - Bổ sung tùy chọn `$nhapLaiKho = true/false` trong `DoiTraModel::xuLy('hoantien')`. Hỗ trợ logic thực tế cho ngành thức ăn thú cưng: các gói hàng bị hỏng, ẩm mốc khi đổi trả hoàn tiền sẽ không bị tự động nhập lại kho để bán tiếp.

4. **Đồng bộ Dữ liệu Demo & Dọn dẹp Tập tin**:
   - Sửa `user2@gmail.com` thành `hoangphuc@gmail.com` trong `DOC_CSDL_CAP_NHAT_WORD.md`, khớp 100% với `database_seed_kibo.sql`.
   - Loại bỏ hoàn toàn các tập tin dư thừa: `test_suite.php` cũ, `crop_hero.*`, `inspect_img.php`, `figma_rendered.png` và file zip lồng bên trong.


