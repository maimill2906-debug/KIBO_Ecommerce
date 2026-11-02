-- ==============================================================================
-- KIBO E-COMMERCE PLATFORM (kibo.vn)
-- SCRIPT SEED DATA - ĐÃ KHỚP CHÍNH XÁC 100% CÁC RÀNG BUỘC (CHECK CONSTRAINTS)
-- ==============================================================================

-- 1. BẢNG DANH MỤC SẢN PHẨM (danh_muc)
INSERT INTO danh_muc (madanhmuc, tendanhmuc, mota, trangthai) VALUES
('DM01', 'Thức Ăn Hạt', 'Các dòng thức ăn hạt dinh dưỡng khô cao cấp cho chó mèo', 'Hoạt động'),
('DM02', 'Pate & Ướt', 'Pate lon, gói mềm thơm ngậy bổ sung nước và đạm cho thú cưng', 'Hoạt động'),
('DM03', 'Bánh Thưởng', 'Snack, súp thưởng và bánh que làm sạch răng cho boss', 'Hoạt động'),
('DM04', 'Cát & Phụ Kiện', 'Cát vệ sinh tự nhiên, vòng cổ, dây dắt và đồ chơi tương tác', 'Hoạt động')
ON CONFLICT (madanhmuc) DO UPDATE 
SET tendanhmuc = EXCLUDED.tendanhmuc, mota = EXCLUDED.mota;


-- 2. BẢNG SẢN PHẨM (san_pham) - trangthai: 'Đang bán'
INSERT INTO san_pham (masp, madanhmuc, tensp, thuonghieu, motachitiet, hinhanhchinh, trangthai) VALUES
('SP001', 'DM01', 'KIBO Cá Hồi & Gạo Lứt', 'KIBO Original', 'Công thức cá hồi Na Uy tươi kết hợp gạo lứt giàu chất xơ, hỗ trợ tim mạch, sáng mắt và mượt lông cho mèo trưởng thành.', 'assets/images/products/sp_kibo_cahoi.png', 'Đang bán'),
('SP002', 'DM01', 'Royal Canin Kitten 36', 'Royal Canin', 'Dinh dưỡng cân bằng tăng cường hệ miễn dịch tự nhiên, hỗ trợ hệ tiêu hóa nhạy cảm của mèo con giai đoạn cai sữa.', 'assets/images/products/sp_royal_canin_kitten.png', 'Đang bán'),
('SP003', 'DM02', 'KIBO Pate Cá Ngừ Tươi', 'KIBO Original', 'Pate cá ngừ đại dương tươi cắt lát hấp tự nhiên, bổ sung nước và Taurine thiết yếu cho boss kén ăn.', 'assets/images/products/sp_kibo_pate_cangu.png', 'Đang bán'),
('SP004', 'DM04', 'Cát Cature Smart Pellet 7L', 'Cature', 'Cát gỗ thông hữu cơ tự nhiên kiểm soát mùi 7 ngày, vón siêu nhanh và có thể xả trực tiếp bồn cầu.', 'assets/images/products/sp_cature_pellet.png', 'Đang bán'),
('SP005', 'DM01', 'Hill''s Science Diet Adult', 'Hill''s Science', 'Chế độ ăn khoa học giúp hạn chế búi lông dạ dày, hỗ trợ tiêu hóa êm dịu và tăng cường sức khỏe da lông.', 'assets/images/products/sp_hills_science.png', 'Đang bán'),
('SP006', 'DM02', 'Whiskas Gà Nước Sốt', 'Whiskas', 'Miếng thịt gà mềm ngập trong nước sốt thịt đậm đà, kích thích vị giác và bổ sung canxi cho mèo cưng năng động.', 'assets/images/products/sp_whiskas_ga.png', 'Đang bán'),
('SP007', 'DM03', 'KIBO Thanh Nhai Sạch Răng', 'KIBO Original', 'Thanh gặm nha khoa thiết kế rãnh 360 độ giúp giảm mảng bám, chống cao răng và lưu hương bạc hà thơm tho.', 'assets/images/products/sp_kibo_thanh_nhai.png', 'Đang bán'),
('SP008', 'DM01', 'Purina Pro Plan Senior 7+', 'Purina', 'Công thức Longevis độc quyền kéo dài tuổi thọ và duy trì độ linh hoạt xương khớp cho mèo từ 7 tuổi trở lên.', 'assets/images/products/sp_purina_pro_plan.png', 'Đang bán')
ON CONFLICT (masp) DO UPDATE 
SET tensp = EXCLUDED.tensp, thuonghieu = EXCLUDED.thuonghieu, motachitiet = EXCLUDED.motachitiet;


-- 3. BẢNG BIẾN THỂ SẢN PHẨM (bien_the)
INSERT INTO bien_the (mabienthe, masp, tenbienthe, giaban, soluongton, hinhanhbienthe) VALUES
('BT001', 'SP001', 'Túi 1.5kg', 285000, 45, 'assets/images/products/sp_kibo_cahoi.png'),
('BT002', 'SP001', 'Bao 5kg', 750000, 20, 'assets/images/products/sp_kibo_cahoi.png'),
('BT003', 'SP002', 'Túi 2kg', 445000, 30, 'assets/images/products/sp_royal_canin_kitten.png'),
('BT004', 'SP002', 'Bao 4kg', 820000, 15, 'assets/images/products/sp_royal_canin_kitten.png'),
('BT005', 'SP003', 'Pack 6 hộp (85g)', 90000, 80, 'assets/images/products/sp_kibo_pate_cangu.png'),
('BT006', 'SP003', 'Pack 12 hộp (85g)', 170000, 60, 'assets/images/products/sp_kibo_pate_cangu.png'),
('BT007', 'SP004', 'Túi 7L (2.8kg)', 185000, 50, 'assets/images/products/sp_cature_pellet.png'),
('BT008', 'SP005', 'Túi 3kg', 620000, 25, 'assets/images/products/sp_hills_science.png'),
('BT009', 'SP006', 'Pack 24 túi (85g)', 216000, 40, 'assets/images/products/sp_whiskas_ga.png'),
('BT010', 'SP007', 'Hộp 50g', 89000, 100, 'assets/images/products/sp_kibo_thanh_nhai.png'),
('BT011', 'SP008', 'Túi 1.5kg', 395000, 30, 'assets/images/products/sp_purina_pro_plan.png')
ON CONFLICT (mabienthe) DO UPDATE 
SET giaban = EXCLUDED.giaban, soluongton = EXCLUDED.soluongton;


-- 4. BẢNG TRẠM CỨU HỘ ĐỐI TÁC (tram_cuu_ho)
INSERT INTO tram_cuu_ho (matram, tentram, nguoidaidien, sodienthoai, diachi, khuvuc, trangthaihoptac) VALUES
('TCH01', 'Trạm Cứu Hộ Động Vật Nông Lâm', 'Bác sĩ thú y Nguyễn Văn An', '0912345678', 'Khuôn viên ĐH Nông Lâm, TP. Thủ Đức, TP.HCM', 'TP. Hồ Chí Minh', 'Đang hợp tác'),
('TCH02', 'Trạm Cứu Hộ Động Vật Sài Gòn Time', 'Chị Trần Thị Mai Lan', '0987654321', 'Đường Tô Ngọc Vân, P. Tam Phú, TP. Thủ Đức, TP.HCM', 'TP. Hồ Chí Minh', 'Đang hợp tác')
ON CONFLICT (matram) DO UPDATE 
SET tentram = EXCLUDED.tentram, trangthaihoptac = EXCLUDED.trangthaihoptac;


-- 5. BẢNG CHỨNG TỪ GIẢI NGÂN QUỸ (chung_tu_giai_ngan)
INSERT INTO chung_tu_giai_ngan (machungtu, matram, sotiengiaingan, ngaychuyenkhoan, mucdichsudung, filehoadonchungtu) VALUES
('CT001', 'TCH01', 14250, '2026-10-26', 'Chi phí thức ăn hạt và cứu trợ y tế chó mèo cơ nhỡ', 'assets/docs/chung_tu_nong_lam_102026.pdf')
ON CONFLICT (machungtu) DO UPDATE
SET sotiengiaingan = EXCLUDED.sotiengiaingan, ngaychuyenkhoan = EXCLUDED.ngaychuyenkhoan;


-- 6. BẢNG TÀI KHOẢN (tai_khoan) - vaitro: 'Admin' hoặc 'User'
INSERT INTO tai_khoan (matk, tendangnhap, matkhau, vaitro, trangthai, ngaytao) VALUES
('TK001', 'admin@kibo.vn', '$2y$10$O0YEgnPpbqHtUjbZPKjSgOi/xrJz9SRVGkPMVFB0EehQKITEO/Ziq', 'Admin', 'Hoạt động', '2026-01-01 08:00:00+07'),
('TK002', 'user1@gmail.com', '$2y$10$O0YEgnPpbqHtUjbZPKjSgOi/xrJz9SRVGkPMVFB0EehQKITEO/Ziq', 'User', 'Hoạt động', '2026-06-01 10:30:00+07'),
('TK003', 'hoangphuc@gmail.com', '$2y$10$O0YEgnPpbqHtUjbZPKjSgOi/xrJz9SRVGkPMVFB0EehQKITEO/Ziq', 'User', 'Hoạt động', '2026-07-15 14:00:00+07')
ON CONFLICT (matk) DO UPDATE 
SET tendangnhap = EXCLUDED.tendangnhap, matkhau = EXCLUDED.matkhau, vaitro = EXCLUDED.vaitro;


-- 7. BẢNG KHÁCH HÀNG (khach_hang)
INSERT INTO khach_hang (makh, matk, hoten, email, ngaysinh, diemtichluy) VALUES
('KH001', 'TK002', 'Nguyễn Lê Quỳnh Mai', 'user1@gmail.com', '1998-05-20', 120),
('KH002', 'TK003', 'Lã Nguyễn Hoàng Phúc', 'hoangphuc@gmail.com', '2000-11-12', 45)
ON CONFLICT (makh) DO UPDATE 
SET hoten = EXCLUDED.hoten, email = EXCLUDED.email;


-- 8. BẢNG SỐ ĐIỆN THOẠI KHÁCH HÀNG (sdt_kh)
INSERT INTO sdt_kh (makh, sodienthoai) VALUES
('KH001', '0772906756'),
('KH002', '0987654321')
ON CONFLICT DO NOTHING;


-- 9. BẢNG ĐỊA CHỈ KHÁCH HÀNG (dia_chi_kh)
INSERT INTO dia_chi_kh (madiachi, makh, diachichitiet, phuongxa, quanhuyen, tinhthanh, macdinh) VALUES
('DC001', 'KH001', '234 Lê Văn Việt', 'Tăng Nhơn Phú B', 'TP. Thủ Đức', 'TP. Hồ Chí Minh', TRUE),
('DC002', 'KH002', 'Số 70 Tô Ký', 'Tân Chánh Hiệp', 'Quận 12', 'TP. Hồ Chí Minh', TRUE)
ON CONFLICT (madiachi) DO NOTHING;


-- 10. BẢNG VOUCHER KHUYẾN MÃI (voucher) - loaigiamgia: 'SoTien' hoặc 'PhanTram'
INSERT INTO voucher (mavoucher, tenvoucher, loaigiamgia, giatrigiam, donhangtoithieu, ngaybatdau, ngayketthuc, soluongconlai) VALUES
('KIBO20', 'Giảm 20K cho đơn hàng đầu tiên', 'SoTien', 20000, 200000, '2026-01-01 00:00:00+07', '2026-12-31 23:59:59+07', 95),
('FREESHIP', 'Giảm 10% giá trị đơn hàng từ 200K', 'PhanTram', 10, 200000, '2026-01-01 00:00:00+07', '2026-12-31 23:59:59+07', 500)
ON CONFLICT (mavoucher) DO UPDATE 
SET tenvoucher = EXCLUDED.tenvoucher, loaigiamgia = EXCLUDED.loaigiamgia, giatrigiam = EXCLUDED.giatrigiam;


-- 11. BẢNG GIỎ HÀNG (gio_hang)
INSERT INTO gio_hang (magiohang, makh, ngaycapnhat) VALUES
('GH001', 'KH001', '2026-10-31 10:00:00+07'),
('GH002', 'KH002', '2026-10-31 11:30:00+07')
ON CONFLICT (magiohang) DO NOTHING;


-- 12. BẢNG CHI TIẾT GIỎ HÀNG (chi_tiet_gio_hang)
INSERT INTO chi_tiet_gio_hang (magiohang, mabienthe, soluong) VALUES
('GH001', 'BT007', 1)
ON CONFLICT DO NOTHING;


-- 13. BẢNG ĐƠN HÀNG (don_hang) - trangthaidonhang: 'Chờ xác nhận', 'Chờ đóng gói', 'Đã xuất kho', 'Đang giao', 'Giao thành công', 'Đã hủy'
INSERT INTO don_hang (madonhang, makh, mavoucher, ngaydathang, tongtienhang, tiengiamgia, phivanchuyen, tongthanhtoan, trangthaidonhang, tennguoinhan, sodienthoainhan, diachigiaohang, ghichu) VALUES
('KB-8921', 'KH001', NULL, '2026-10-25 09:15:00+07', 285000, 0, 0, 285000, 'Giao thành công', 'Nguyễn Lê Quỳnh Mai', '0772906756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', 'Giao giờ hành chính, gọi trước 15 phút.'),
('KB-8922', 'KH001', 'KIBO20', '2026-10-30 14:20:00+07', 445000, 20000, 0, 425000, 'Đang giao', 'Nguyễn Lê Quỳnh Mai', '0772906756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', 'Để trước cửa nhà nếu không có ai.'),
('KB-8923', 'KH002', NULL, '2026-10-31 08:45:00+07', 185000, 0, 20000, 205000, 'Chờ xác nhận', 'Lã Nguyễn Hoàng Phúc', '0987654321', 'Số 70 Tô Ký, Phường Tân Chánh Hiệp, Quận 12, TP. Hồ Chí Minh', 'Đơn hàng mới tạo')
ON CONFLICT (madonhang) DO UPDATE 
SET tongthanhtoan = EXCLUDED.tongthanhtoan, trangthaidonhang = EXCLUDED.trangthaidonhang;


-- 14. BẢNG CHI TIẾT ĐƠN HÀNG (chi_tiet_don_hang)
INSERT INTO chi_tiet_don_hang (madonhang, mabienthe, soluong, dongia, thanhtien) VALUES
('KB-8921', 'BT001', 1, 285000, 285000),
('KB-8922', 'BT003', 1, 445000, 445000),
('KB-8923', 'BT007', 1, 185000, 185000)
ON CONFLICT DO NOTHING;


-- 15. BẢNG QUỸ CỨU TRỢ ĐỘNG VẬT (quy_cuu_tro) - trangthaiquy: 'Chờ trích quỹ', 'Đã trích quỹ', 'Đã giải ngân', 'Đã hủy'
INSERT INTO quy_cuu_tro (madonggop, madonhang, sotiendonggop, ngayghinhan, matracuu, trangthaiquy, machungtu) VALUES
('QCT001', 'KB-8921', 14250, '2026-10-25 09:15:00+07', 'KB-8921', 'Đã giải ngân', 'CT001'),
('QCT002', 'KB-8922', 21250, '2026-10-30 14:20:00+07', 'KB-8922', 'Chờ trích quỹ', NULL),
('QCT003', 'KB-8923', 9250, '2026-10-31 08:45:00+07', 'KB-8923', 'Chờ trích quỹ', NULL)
ON CONFLICT (madonggop) DO UPDATE 
SET sotiendonggop = EXCLUDED.sotiendonggop, trangthaiquy = EXCLUDED.trangthaiquy;


-- 16. BẢNG THANH TOÁN (thanh_toan)
INSERT INTO thanh_toan (magiaodich, madonhang, phuongthuctt, sotien, thoigiantt, trangthaitt) VALUES
('GD001', 'KB-8921', 'Chuyển khoản VietQR', 285000, '2026-10-25 09:16:00+07', 'Thành công'),
('GD002', 'KB-8922', 'Chuyển khoản VietQR', 425000, '2026-10-30 14:21:00+07', 'Thành công'),
('GD003', 'KB-8923', 'Thanh toán khi nhận hàng (COD)', 205000, '2026-10-31 08:45:00+07', 'Chờ thanh toán')
ON CONFLICT (magiaodich) DO NOTHING;


-- 17. BẢNG YÊU CẦU ĐỔI TRẢ (yeu_cau_doi_tra)
INSERT INTO yeu_cau_doi_tra (mayeucau, madonhang, lydodoitra, hinhanhminhchung, ngayyeucau, trangthaiyeucau, huongxuly) VALUES
('DT001', 'KB-8921', 'Bao bì nguyên vẹn, muốn đổi sang dòng vị thịt gà cho mèo con', 'assets/images/returns/dt001.png', '2026-10-26 15:30:00+07', 'Đã hoàn tất', 'Đổi sang mã BT003 và phụ thu chênh lệch')
ON CONFLICT (mayeucau) DO NOTHING;
