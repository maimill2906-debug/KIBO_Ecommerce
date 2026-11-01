# CÂU HỎI VÀ ĐỀ XUẤT CẦN THỐNG NHẤT VỚI NHÓM PHÁT TRIỂN KIBO (TODO_QUESTIONS.md)

Tài liệu này ghi nhận các điểm nghiệp vụ và thiết kế hệ thống cần nhóm xác nhận hoặc hoàn thiện thêm trong các giai đoạn phát triển tiếp theo:

---

### 1. Chính sách xếp chồng Voucher Freeship và Chính sách Freeship tự động
- **Hiện trạng:** Hệ thống đang áp dụng chính sách Freeship tự động dựa trên giá trị đơn hàng (≥ 200.000đ ở nội thành TP.HCM hoặc ≥ 500.000đ ở các tỉnh thành khác thì phí vận chuyển tự động bằng 0đ).
- **Vấn đề đặt ra:** Nếu khách hàng nhập mã voucher `KIBOFREESHIP` hoặc mã giảm giá vận chuyển khi đơn hàng đã đủ điều kiện freeship tự động:
  - *Phương án A (Khuyên dùng):* Thông báo cho khách hàng rằng đơn hàng của bạn đã được hưởng Freeship tự động, và không tiêu tốn lượt voucher của khách.
  - *Phương án B:* Cho phép áp dụng giảm thêm vào tiền hàng.
- **Hành động:** Nhóm cần thống nhất quy tắc xử lý khi voucher trùng với chính sách tự động.

---

### 2. Bổ sung File Upload minh chứng hình ảnh thực tế khi gửi Đổi trả
- **Hiện trạng:** Khi khách hàng gửi yêu cầu đổi trả trên giao diện khách hàng, hệ thống ghi nhận lý do đổi trả và mặc định gán đường dẫn `assets/images/returns/default.png` nếu không có file đính kèm.
- **Đề xuất:** Cần tích hợp input file upload (`enctype="multipart/form-data"` hoặc upload lên Supabase Storage Bucket) để khách hàng bắt buộc phải chụp ảnh hàng hóa lỗi/hỏng thực tế trước khi gửi yêu cầu.

---

### 3. Cơ chế phân bổ Quỹ cứu trợ cho từng trạm cứu hộ
- **Hiện trạng:** Khi khách hàng đặt đơn hàng, khoản 5% được ghi vào bảng `quy_cuu_tro` với `machungtu = NULL` (chờ trích và chờ giải ngân tập trung). Khi Admin giải ngân định kỳ hàng tháng, hệ thống ghi nhận vào bảng `chung_tu_giai_ngan` gắn với `matram`.
- **Đề xuất:** Nhóm có muốn cho phép khách hàng tự chọn trạm cứu hộ cụ thể mà họ muốn 5% đơn hàng của mình quyên góp về (ví dụ: Trạm Nông Lâm hay Trạm Sài Gòn Time) ngay tại màn hình thanh toán hay không?

---

### 4. Bổ sung cổng thanh toán trực tuyến tự động (Webhook VietQR / PayOS / MoMo)
- **Hiện trạng:** Thanh toán VietQR hiện đang hiển thị mã QR theo chuẩn cú pháp Napas kèm mã đơn hàng. Việc xác nhận chuyển khoản thành công vẫn phụ thuộc vào việc Admin bấm duyệt hoặc xác nhận chuyển tiền thủ công.
- **Đề xuất:** Khi chuẩn bị bảo vệ đề tài hoặc triển khai thực tế, nhóm có thể tích hợp Webhook của ngân hàng hoặc PayOS để hệ thống tự động xác nhận `trangthaitt = 'Thành công'` ngay khi tiền vào tài khoản mà không cần can thiệp thủ công.
