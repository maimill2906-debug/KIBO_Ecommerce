

CREATE TABLE public.tai_khoan (
  matk character varying NOT NULL,
  tendangnhap character varying NOT NULL UNIQUE,
  matkhau character varying NOT NULL,
  vaitro character varying NOT NULL CHECK (vaitro::text = ANY (ARRAY['Admin'::character varying, 'User'::character varying]::text[])),
  trangthai character varying DEFAULT 'Hoạt động'::character varying,
  ngaytao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT tai_khoan_pkey PRIMARY KEY (matk)
);
CREATE TABLE public.khach_hang (
  makh character varying NOT NULL,
  matk character varying UNIQUE,
  hoten character varying NOT NULL,
  email character varying NOT NULL UNIQUE,
  ngaysinh date,
  diemtichluy integer DEFAULT 0 CHECK (diemtichluy >= 0),
  CONSTRAINT khach_hang_pkey PRIMARY KEY (makh),
  CONSTRAINT khach_hang_matk_fkey FOREIGN KEY (matk) REFERENCES public.tai_khoan(matk)
);
CREATE TABLE public.sdt_kh (
  makh character varying NOT NULL,
  sodienthoai character varying NOT NULL,
  CONSTRAINT sdt_kh_pkey PRIMARY KEY (makh, sodienthoai),
  CONSTRAINT sdt_kh_makh_fkey FOREIGN KEY (makh) REFERENCES public.khach_hang(makh)
);
CREATE TABLE public.dia_chi_kh (
  madiachi character varying NOT NULL,
  makh character varying NOT NULL,
  diachichitiet character varying NOT NULL,
  phuongxa character varying NOT NULL,
  quanhuyen character varying NOT NULL,
  tinhthanh character varying NOT NULL,
  macdinh boolean DEFAULT false,
  CONSTRAINT dia_chi_kh_pkey PRIMARY KEY (madiachi),
  CONSTRAINT dia_chi_kh_makh_fkey FOREIGN KEY (makh) REFERENCES public.khach_hang(makh)
);
CREATE TABLE public.gio_hang (
  magiohang character varying NOT NULL,
  makh character varying NOT NULL UNIQUE,
  ngaycapnhat timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT gio_hang_pkey PRIMARY KEY (magiohang),
  CONSTRAINT gio_hang_makh_fkey FOREIGN KEY (makh) REFERENCES public.khach_hang(makh)
);
CREATE TABLE public.danh_muc (
  madanhmuc character varying NOT NULL,
  tendanhmuc character varying NOT NULL,
  mota text,
  trangthai character varying DEFAULT 'Hiển thị'::character varying,
  loaithucung character varying DEFAULT 'Chung'::character varying,
  CONSTRAINT danh_muc_pkey PRIMARY KEY (madanhmuc)
);
CREATE TABLE public.san_pham (
  masp character varying NOT NULL,
  madanhmuc character varying,
  tensp character varying NOT NULL,
  thuonghieu character varying,
  motachitiet text,
  hinhanhchinh character varying,
  trangthai character varying DEFAULT 'Đang bán'::character varying CHECK (trangthai::text = ANY (ARRAY['Đang bán'::character varying, 'Tạm hết hàng'::character varying, 'Ngừng kinh doanh'::character varying, 'Ẩn'::character varying]::text[])),
  hansudung date,
  thanhphandinhduong text,
  CONSTRAINT san_pham_pkey PRIMARY KEY (masp),
  CONSTRAINT san_pham_madanhmuc_fkey FOREIGN KEY (madanhmuc) REFERENCES public.danh_muc(madanhmuc)
);
CREATE TABLE public.bien_the (
  mabienthe character varying NOT NULL,
  masp character varying NOT NULL,
  tenbienthe character varying NOT NULL,
  giaban numeric NOT NULL CHECK (giaban > 0::numeric),
  soluongton integer NOT NULL CHECK (soluongton >= 0),
  hinhanhbienthe character varying,
  giagoc numeric,
  giasi numeric,
  CONSTRAINT bien_the_pkey PRIMARY KEY (mabienthe),
  CONSTRAINT bien_the_masp_fkey FOREIGN KEY (masp) REFERENCES public.san_pham(masp)
);
CREATE TABLE public.chi_tiet_gio_hang (
  magiohang character varying NOT NULL,
  mabienthe character varying NOT NULL,
  soluong integer NOT NULL CHECK (soluong > 0),
  CONSTRAINT chi_tiet_gio_hang_pkey PRIMARY KEY (magiohang, mabienthe),
  CONSTRAINT chi_tiet_gio_hang_magiohang_fkey FOREIGN KEY (magiohang) REFERENCES public.gio_hang(magiohang),
  CONSTRAINT chi_tiet_gio_hang_mabienthe_fkey FOREIGN KEY (mabienthe) REFERENCES public.bien_the(mabienthe)
);
CREATE TABLE public.voucher (
  mavoucher character varying NOT NULL,
  tenvoucher character varying NOT NULL,
  loaigiamgia character varying NOT NULL CHECK (loaigiamgia::text = ANY (ARRAY['PhanTram'::character varying, 'SoTien'::character varying]::text[])),
  giatrigiam numeric NOT NULL CHECK (giatrigiam > 0::numeric),
  donhangtoithieu numeric DEFAULT 0,
  ngaybatdau timestamp with time zone NOT NULL,
  ngayketthuc timestamp with time zone NOT NULL,
  soluongconlai integer NOT NULL CHECK (soluongconlai >= 0),
  kichhoat boolean NOT NULL DEFAULT true,
  giamtoida numeric DEFAULT NULL::numeric,
  moikhachtoida integer DEFAULT 1,
  soluongphathanh integer,
  CONSTRAINT voucher_pkey PRIMARY KEY (mavoucher)
);
CREATE TABLE public.don_hang (
  madonhang character varying NOT NULL,
  makh character varying,
  mavoucher character varying,
  ngaydathang timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  tongtienhang numeric NOT NULL CHECK (tongtienhang > 0::numeric),
  tiengiamgia numeric DEFAULT 0 CHECK (tiengiamgia >= 0::numeric),
  phivanchuyen numeric DEFAULT 0 CHECK (phivanchuyen >= 0::numeric),
  tongthanhtoan numeric NOT NULL CHECK (tongthanhtoan > 0::numeric),
  trangthaidonhang character varying DEFAULT 'Chờ xác nhận'::character varying CHECK (trangthaidonhang::text = ANY (ARRAY['Chờ xác nhận'::character varying, 'Chờ đóng gói'::character varying, 'Đã xuất kho'::character varying, 'Đang giao'::character varying, 'Giao thành công'::character varying, 'Đã hủy'::character varying]::text[])),
  tennguoinhan character varying NOT NULL,
  sodienthoainhan character varying NOT NULL,
  diachigiaohang character varying NOT NULL,
  ghichu text,
  ngaygiaothanhcong timestamp with time zone,
  mavandon character varying DEFAULT NULL::character varying,
  matracuu character varying,
  CONSTRAINT don_hang_pkey PRIMARY KEY (madonhang),
  CONSTRAINT don_hang_makh_fkey FOREIGN KEY (makh) REFERENCES public.khach_hang(makh),
  CONSTRAINT don_hang_mavoucher_fkey FOREIGN KEY (mavoucher) REFERENCES public.voucher(mavoucher)
);
CREATE TABLE public.chi_tiet_don_hang (
  madonhang character varying NOT NULL,
  mabienthe character varying NOT NULL,
  soluong integer NOT NULL CHECK (soluong > 0),
  dongia numeric NOT NULL CHECK (dongia > 0::numeric),
  thanhtien numeric NOT NULL CHECK (thanhtien > 0::numeric),
  CONSTRAINT chi_tiet_don_hang_pkey PRIMARY KEY (madonhang, mabienthe),
  CONSTRAINT chi_tiet_don_hang_mabienthe_fkey FOREIGN KEY (mabienthe) REFERENCES public.bien_the(mabienthe),
  CONSTRAINT chi_tiet_don_hang_madonhang_fkey FOREIGN KEY (madonhang) REFERENCES public.don_hang(madonhang)
);
CREATE TABLE public.thanh_toan (
  magiaodich character varying NOT NULL,
  madonhang character varying NOT NULL UNIQUE,
  phuongthuctt character varying NOT NULL,
  sotien numeric NOT NULL CHECK (sotien > 0::numeric),
  thoigiantt timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  trangthaitt character varying DEFAULT 'Chờ thanh toán'::character varying,
  CONSTRAINT thanh_toan_pkey PRIMARY KEY (magiaodich),
  CONSTRAINT thanh_toan_madonhang_fkey FOREIGN KEY (madonhang) REFERENCES public.don_hang(madonhang)
);
CREATE TABLE public.yeu_cau_doi_tra (
  mayeucau character varying NOT NULL,
  madonhang character varying NOT NULL,
  lydodoitra text NOT NULL,
  hinhanhminhchung character varying,
  ngayyeucau timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  trangthaiyeucau character varying DEFAULT 'Đang xử lý'::character varying CHECK (trangthaiyeucau::text = ANY (ARRAY['Chờ xử lý'::character varying, 'Đã đồng ý đổi'::character varying, 'Đã hoàn tiền'::character varying, 'Đã từ chối'::character varying, 'Đã hoàn tất'::character varying]::text[])),
  huongxuly character varying,
  ngayxuly timestamp with time zone,
  sotienhoan numeric DEFAULT NULL::numeric,
  CONSTRAINT yeu_cau_doi_tra_pkey PRIMARY KEY (mayeucau),
  CONSTRAINT yeu_cau_doi_tra_madonhang_fkey FOREIGN KEY (madonhang) REFERENCES public.don_hang(madonhang)
);
CREATE TABLE public.tram_cuu_ho (
  matram character varying NOT NULL,
  tentram character varying NOT NULL,
  nguoidaidien character varying NOT NULL,
  sodienthoai character varying NOT NULL,
  diachi character varying NOT NULL,
  khuvuc character varying NOT NULL,
  trangthaihoptac character varying DEFAULT 'Đang hợp tác'::character varying,
  sotaikhoan character varying,
  nganhang character varying,
  CONSTRAINT tram_cuu_ho_pkey PRIMARY KEY (matram)
);
CREATE TABLE public.chung_tu_giai_ngan (
  machungtu character varying NOT NULL,
  matram character varying NOT NULL,
  sotiengiaingan numeric NOT NULL CHECK (sotiengiaingan > 0::numeric),
  ngaychuyenkhoan date NOT NULL,
  mucdichsudung text NOT NULL,
  filehoadonchungtu character varying NOT NULL,
  CONSTRAINT chung_tu_giai_ngan_pkey PRIMARY KEY (machungtu),
  CONSTRAINT chung_tu_giai_ngan_matram_fkey FOREIGN KEY (matram) REFERENCES public.tram_cuu_ho(matram)
);
CREATE TABLE public.quy_cuu_tro (
  madonggop character varying NOT NULL,
  madonhang character varying NOT NULL UNIQUE,
  sotiendonggop numeric NOT NULL CHECK (sotiendonggop >= 0::numeric),
  ngayghinhan timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  matracuu character varying NOT NULL UNIQUE,
  trangthaiquy character varying DEFAULT 'Đã tích lũy'::character varying CHECK (trangthaiquy::text = ANY (ARRAY['Chờ trích quỹ'::character varying, 'Đã trích quỹ'::character varying, 'Đã giải ngân'::character varying, 'Đã hủy'::character varying]::text[])),
  machungtu character varying,
  matram character varying,
  CONSTRAINT quy_cuu_tro_pkey PRIMARY KEY (madonggop),
  CONSTRAINT quy_cuu_tro_machungtu_fkey FOREIGN KEY (machungtu) REFERENCES public.chung_tu_giai_ngan(machungtu),
  CONSTRAINT quy_cuu_tro_madonhang_fkey FOREIGN KEY (madonhang) REFERENCES public.don_hang(madonhang)
);
CREATE TABLE public.nhat_ky (
  manhatky character varying NOT NULL,
  matk character varying,
  hanhdong character varying NOT NULL,
  doituong character varying NOT NULL,
  madoituong character varying,
  chitiet text,
  thoigian timestamp with time zone DEFAULT now(),
  CONSTRAINT nhat_ky_pkey PRIMARY KEY (manhatky)
);
CREATE TABLE public.so_dia_chi (
  madiachi character varying NOT NULL,
  makh character varying NOT NULL,
  tennguoinhan character varying NOT NULL,
  sodienthoai character varying NOT NULL,
  diachichitiet text NOT NULL,
  quanhuyen character varying NOT NULL,
  tinhthanh character varying NOT NULL,
  macdinh boolean DEFAULT false,
  ngaytao timestamp with time zone DEFAULT now(),
  CONSTRAINT so_dia_chi_pkey PRIMARY KEY (madiachi),
  CONSTRAINT so_dia_chi_makh_fkey FOREIGN KEY (makh) REFERENCES public.khach_hang(makh)
);
CREATE TABLE public.dang_nhap_that_bai (
  ip character varying NOT NULL,
  email character varying NOT NULL,
  solanthatbai integer DEFAULT 1,
  khoaden timestamp with time zone,
  lancoicuoi timestamp with time zone DEFAULT now(),
  CONSTRAINT dang_nhap_that_bai_pkey PRIMARY KEY (ip, email)
);

-- ============================================================
-- 3. DỮ LIỆU MẪU CHUẨN HÓA THỰC TẾ TRÊN WEBSITE (SEED DATA)
-- ============================================================

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.tai_khoan (10 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK004', 'user2@gmail.com', '$2y$10$VEbveYOFMWz3LifcCVFz2.89zmmSYwRPqrX5yiFcsegoqHdNUOuiG', 'User', 'Hoạt động', '2026-10-31 15:53:36.150205+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK005', 'Suongmai006@gmail.com', '$2y$10$t.fXIDtUGRUnB6DzEuDTceTZ1iCDmhoBniXDmJsL52DHZ5vEUwYr2', 'User', 'Hoạt động', '2026-10-31 15:56:59.822288+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK006', 'user@gmail.com', '$2y$10$a4bjDeTfYqLWCp2z8m47IuYVA0hcJnxOmILB5nehv6Wb/ydkl/ypG', 'User', 'Hoạt động', '2026-10-31 16:01:36.920164+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK007', 'user3@gmail.com', '$2y$10$2YZv4IU6K1rxWNrwNLaNcu.Dg6aIQH/XibfIwccXCSa638ah5oX/C', 'User', 'Hoạt động', '2026-11-01 09:30:22.707671+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK008', 'user4@gmail.com', '$2y$10$4B2PwG7zknS2J5mfBWPKKeONR10kVTFGC/fsb.F/EYT8oduUGE/Y6', 'User', 'Hoạt động', '2026-11-01 09:39:07.815039+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK009', 'test_qa_9653@kibo.vn', '$2y$10$P9mYifxE.M0ADJjPi.Xa7el.mWmxpQAoyJ/8X87LvmPGWXDXgQvby', 'User', 'Hoạt động', '2026-11-01 10:22:18.799096+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK003', 'hoangphuc@gmail.com', '$2y$10$O0YEgnPpbqHtUjbZPKjSgOi/xrJz9SRVGkPMVFB0EehQKITEO/Ziq', 'User', 'Hoạt động', '2026-07-15 07:00:00+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK010', 'user7@gmail.com', '$2y$10$6SU9Xrt/WJ8TbBHl5bfTS.Q3jI9yWnzk9DDd/fqln7TT1pJVdS19K', 'User', 'Hoạt động', '2026-11-07 06:37:53.818142+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK002', 'user@kibo.vn', '$2y$10$wCXWuVAAMxZmho6O0Bio5eYtNve71bYFvcAddRZg5pgsks3z4Ryz6', 'User', 'Hoạt động', '2026-06-01 03:30:00+00');
INSERT INTO public."tai_khoan" ("matk", "tendangnhap", "matkhau", "vaitro", "trangthai", "ngaytao") VALUES ('TK001', 'admin@kibo.vn', '$2y$10$QPWtOyvrAmtC/cX6zhf4fOVKptE7K850kBG5XTConSxT1uitiHbqK', 'Admin', 'Hoạt động', '2026-01-01 01:00:00+00');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.khach_hang (14 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH008', 'TK009', 'QA Tester', 'test_qa_9653@kibo.vn', NULL, '0');
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KV003', NULL, 'State Test', 'guest_0772906756_kv003@kibo.local', NULL, '0');
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH007', 'TK008', 'Quỳnh Giao', 'user4@gmail.com', NULL, 44);
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KV001', NULL, 'QA Tamper Test', 'guest_0901112233@kibo.local', NULL, '0');
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KV002', NULL, 'QA Stock Test', 'guest_0901112234@kibo.local', NULL, '0');
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH009', 'TK010', 'Quỳnh Giao', 'user7@gmail.com', NULL, '0');
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KV004', NULL, 'Quỳnh Giao', 'guest__kv004@kibo.local', NULL, '0');
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH_TK001', 'TK001', 'Quản trị viên 1 - Trưởng nhóm & Tổng chỉ huy', 'admin@kibo.vn', NULL, 100);
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH002', 'TK003', 'Lã Nguyễn Hoàng Phúc', 'hoangphuc@gmail.com', '2000-11-12', 67);
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH006', 'TK007', 'Quỳnh Giao', 'user3@gmail.com', NULL, 22);
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH001', 'TK002', 'Nguyễn Lê Quỳnh Mai', 'user@kibo.vn', '1998-05-20', 120);
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH004', 'TK005', 'Sương Mai', 'Suongmai006@gmail.com', NULL, 44);
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH003', 'TK004', 'Quỳnh Giao', 'user2@gmail.com', NULL, 22);
INSERT INTO public."khach_hang" ("makh", "matk", "hoten", "email", "ngaysinh", "diemtichluy") VALUES ('KH005', 'TK006', 'Quỳnh Giao', 'user@gmail.com', NULL, 22);

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.sdt_kh (12 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH001', '0772906756');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH002', '0987654321');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH003', '0772906756');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH005', '0772906756');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH006', '0772906756');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH008', '0772906756');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH007', '0772906756');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KV001', '0901112233');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KV002', '0901112234');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KV004', '');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KH_TK001', '0772906756');
INSERT INTO public."sdt_kh" ("makh", "sodienthoai") VALUES ('KV003', '0772906756');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.dia_chi_kh (9 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC001', 'KH001', '234 Lê Văn Việt', 'Tăng Nhơn Phú B', 'TP. Thủ Đức', 'TP. Hồ Chí Minh', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC002', 'KH002', 'Số 70 Tô Ký', 'Tân Chánh Hiệp', 'Quận 12', 'TP. Hồ Chí Minh', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC003', 'KH005', '123 Nguyễn Văn A, TP. Hồ Chí Minh', '', '', '', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC005', 'KH008', '123 Test Street', '', '', '', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC004', 'KH007', '171 quốc lộ 13, Phường Tăng Nhơn Phú B, TP. Thủ Đức', '', '', '', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC006', 'KV001', '123 Quận 1, TP. Hồ Chí Minh', '', '', '', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC007', 'KV002', 'Quận 1, TP.HCM', '', '', '', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC008', 'KV003', 'Quận 1, TP.HCM', '', '', '', TRUE);
INSERT INTO public."dia_chi_kh" ("madiachi", "makh", "diachichitiet", "phuongxa", "quanhuyen", "tinhthanh", "macdinh") VALUES ('DC009', 'KV004', '171 quốc lộ 13', 'Phường Tăng Nhơn Phú', 'TP. Thủ Đức', 'TP. Hồ Chí Minh', TRUE);

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.gio_hang (6 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."gio_hang" ("magiohang", "makh", "ngaycapnhat") VALUES ('GH001', 'KH001', '2026-10-31 03:00:00+00');
INSERT INTO public."gio_hang" ("magiohang", "makh", "ngaycapnhat") VALUES ('GH002', 'KH002', '2026-10-31 04:30:00+00');
INSERT INTO public."gio_hang" ("magiohang", "makh", "ngaycapnhat") VALUES ('GH003', 'KH007', '2026-11-01 10:31:04.360198+00');
INSERT INTO public."gio_hang" ("magiohang", "makh", "ngaycapnhat") VALUES ('GH004', 'KH003', '2026-11-01 12:41:52.860987+00');
INSERT INTO public."gio_hang" ("magiohang", "makh", "ngaycapnhat") VALUES ('GH005', 'KH009', '2026-11-07 06:38:17.555913+00');
INSERT INTO public."gio_hang" ("magiohang", "makh", "ngaycapnhat") VALUES ('GH006', 'KH_TK001', '2026-11-12 08:34:06.185422+00');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.danh_muc (4 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."danh_muc" ("madanhmuc", "tendanhmuc", "mota", "trangthai", "loaithucung") VALUES ('DM01', 'Thức Ăn Hạt', 'Các dòng thức ăn hạt dinh dưỡng khô cao cấp cho chó mèo', 'Hoạt động', 'Chung');
INSERT INTO public."danh_muc" ("madanhmuc", "tendanhmuc", "mota", "trangthai", "loaithucung") VALUES ('DM02', 'Pate & Ướt', 'Pate lon, gói mềm thơm ngậy bổ sung nước và đạm cho thú cưng', 'Hoạt động', 'Chung');
INSERT INTO public."danh_muc" ("madanhmuc", "tendanhmuc", "mota", "trangthai", "loaithucung") VALUES ('DM03', 'Bánh Thưởng', 'Snack, súp thưởng và bánh que làm sạch răng cho boss', 'Hoạt động', 'Chung');
INSERT INTO public."danh_muc" ("madanhmuc", "tendanhmuc", "mota", "trangthai", "loaithucung") VALUES ('DM04', 'Cát & Phụ Kiện', 'Cát vệ sinh tự nhiên, vòng cổ, dây dắt và đồ chơi tương tác', 'Hoạt động', 'Chung');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.san_pham (18 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP001', 'DM01', 'Natural Core C3 Bene 3012 - Hạt Cho Mèo Thịt Gà & Cá Hồi', 'Natural Core', 'Thức ăn hạt hữu cơ đa đạm thịt gà và cá hồi thủy phân Natural Core Bene 3012 giúp tiêu hóa khỏe mạnh, giảm dị ứng và bóng mượt lông cho mèo mọi lứa tuổi.', 'assets/images/products/sp001_nc_bene_c3.png', 'Đang bán', '2027-10-15', 'Thịt gà rút xương, cá hồi thủy phân, gạo hữu cơ, đạm thô >= 30%, chất béo thô >= 12%, chất xơ <= 4.5%, Taurine, Omega-3 & 6.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP010', 'DM01', 'SmartHeart Puppy Beef & Milk - Hạt Cho Chó Con Vị Bò & Sữa', 'SmartHeart', 'Thức ăn hạt SmartHeart Puppy hương vị thịt bò và sữa giàu DHA từ dầu cá và Choline giúp phát triển trí não, xương răng chắc khỏe cho chó con dưới 1 tuổi.', 'assets/images/products/sp011_smartheart_puppy.png', 'Đang bán', '2027-09-15', 'Bột thịt bò, sữa bột nguyên kem, gạo, dầu cá biển giàu DHA, canxi, phốt pho, Vitamin E & Selenium, đạm thô >= 26%, chất béo >= 10%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP011', 'DM01', 'JirehO Duck Formula - Hạt Hữu Cơ Thịt Vịt & Rau Củ Cho Chó', 'JirehO', 'Công thức thịt vịt tươi kết hợp rau củ hữu cơ, chiết xuất thảo mộc và lợi khuẩn Probiotics giúp ngăn ngừa táo bón và tăng cường miễn dịch cho chó.', 'assets/images/products/sp012_jireho_duck.png', 'Đang bán', '2027-12-01', 'Thịt vịt rút xương, rau củ hữu cơ, men vi sinh Probiotics, phức hợp Vitamin nhóm B, đạm thô >= 25%, chất béo >= 12%, chất xơ <= 5%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP002', 'DM01', 'Reflex Kitten Chicken & Rice - Hạt Cho Mèo Con Vị Gà & Gạo', 'Reflex', 'Thức ăn hạt cao cấp Reflex Kitten hương vị thịt gà và gạo cân bằng dinh dưỡng, giàu Omega 3 & 6 giúp mèo con phát triển toàn diện và tăng cường đề kháng.', 'assets/images/products/sp002_reflex_kitten.png', 'Đang bán', '2027-09-30', 'Protein thịt gà chế biến (42%), gạo, ngô, mỡ gà tinh luyện, đạm thô >= 37%, chất béo >= 20%, men bia, Taurine và Vitamin tổng hợp.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP005', 'DM01', 'Zenith Adult Chicken & Salmon - Hạt Mềm Cho Chó Thịt Gà & Cá Hồi', 'Zenith', 'Hạt mềm bán ẩm (Semi-Moist) Zenith Hàn Quốc chế biến từ thịt gà tươi và cá hồi, dễ nhai, không gây khát nước và bảo vệ hệ tiêu hóa cho chó trưởng thành.', 'assets/images/products/sp005_zenith_adult.png', 'Đang bán', '2027-11-20', 'Thịt gà tươi, thịt cá hồi, gạo lứt, collagen cá, Beta-glucan, đạm thô >= 28%, chất béo >= 14%, canxi cacbonat và lợi khuẩn đường ruột.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP008', 'DM01', 'Reflex Adult Cat Salmon & Anchovy - Hạt Mèo Vị Cá Hồi & Cá Cơm', 'Reflex', 'Thức ăn hạt Reflex High Quality cho mèo trưởng thành kết hợp cá hồi và cá cơm biển giàu axit béo Omega-3, giúp da khỏe mạnh và hạn chế rụng lông.', 'assets/images/products/sp008_reflex_cat_salmon.png', 'Đang bán', '2027-08-30', 'Protein cá hồi và cá cơm khử nước, gạo, mỡ gà, hạt lanh, men bia, Yucca Schidigera giảm mùi phân, đạm thô >= 32%, chất béo >= 15%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP009', 'DM01', 'Reflex Plus Mini Adult Dog Salmon - Hạt Chó Giống Nhỏ Vị Cá Hồi', 'Reflex', 'Hạt dinh dưỡng siêu cao cấp Reflex Plus bổ sung XOS Prebiotics và L-Carnitine dành riêng cho chó trưởng thành giống nhỏ và siêu nhỏ.', 'assets/images/products/sp009_reflex_dog_salmon.png', 'Đang bán', '2027-10-10', 'Đạm động vật và cá hồi thủy phân (82%), Xylo-oligosaccharides (XOS), L-Carnitine, Omega 3 & 6, đạm thô >= 26%, chất béo >= 15%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP012', 'DM01', 'Natural Core Organic Sensitive Salmon - Hạt Hữu Cơ Cá Hồi Chó Nhạy Cảm', 'Natural Core', 'Đạt chứng nhận hữu cơ quốc tế USDA & ECOCERT, sử dụng đạm cá hồi thủy phân chuyên biệt cho chó có làn da nhạy cảm hoặc dị ứng thức ăn.', 'assets/images/products/sp001_nc_salmon.png', 'Đang bán', '2027-11-15', 'Cá hồi thủy phân hữu cơ, hạt diêm mạch, khoai lang hữu cơ, hạt lanh, đạm thô >= 24%, chất béo >= 12%, không ngũ cốc gây dị ứng.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP003', 'DM02', 'Whiskas Pate Lon Vị Cá Mòi & Cá Biển Cho Mèo Trưởng Thành 400g', 'Whiskas', 'Pate lon Whiskas 400g làm từ thịt cá biển tươi nguyên chất nấu chín mềm trong thạch, cung cấp 100% dinh dưỡng cân bằng và bổ sung nước cho mèo từ 1 tuổi.', 'assets/images/products/sp013_whiskas_can_400g.png', 'Đang bán', '2027-08-20', 'Cá mòi & cá biển tươi (55%), thạch tự nhiên, dầu thực vật, Taurine, Vitamin E, B1, khoáng chất, đạm thô >= 10%, độ ẩm <= 84%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP006', 'DM02', 'Whiskas Pate Túi Vị Thịt Gà Nước Sốt Cho Mèo 80g', 'Whiskas', 'Miếng thịt gà tươi mềm ngọt ngập trong nước sốt đậm đà, kích thích vị giác cho mèo kén ăn và hỗ trợ hệ miễn dịch khỏe mạnh.', 'assets/images/products/sp006_whiskas_pouch.png', 'Đang bán', '2027-07-25', 'Thịt gà chọn lọc (45%), nước sốt giàu khoáng chất, đạm thô >= 8.5%, chất béo >= 4.5%, Vitamin B1, kẽm và axit béo thiết yếu.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP013', 'DM02', 'Meowow Tuna White Meat & Salmon - Pate Lon Cá Ngừ Trắng & Cá Hồi 80g', 'Meowow', 'Pate đóng hộp cao cấp Hàn Quốc Meowow từ thịt cá ngừ trắng nguyên thớ kết hợp cá hồi tươi, giàu DHA, Omega-3 và Taurine cho mèo.', 'assets/images/products/sp015_meowow_can.png', 'Đang bán', '2027-10-25', 'Thịt cá ngừ trắng, cá hồi tươi, nước tinh khiết, DHA, Omega-3, Taurine, Vitamin E, đạm thô >= 13%, độ ẩm <= 82%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP014', 'DM02', 'Me-O Pate Lon Cá Ngừ & Hải Sản Trong Thạch Cho Mèo 400g', 'Me-O', 'Pate lon lớn 400g thương hiệu Me-O Thái Lan chế biến từ cá ngừ, cá mòi và hải sản tươi đóng thạch thơm ngon, tiện lợi và tiết kiệm.', 'assets/images/products/sp003_meo_pate_lon.png', 'Đang bán', '2027-09-10', 'Cá ngừ đại dương, cá mòi, tôm mực tươi, chất tạo đông tự nhiên, Taurine, Vitamin E, đạm thô >= 11%, chất béo >= 2%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP007', 'DM03', 'JirehO Chicken & Milk Twists - Que Xoắn Sạch Răng Thịt Gà & Sữa 110g', 'JirehO', 'Thanh nhai xoắn mềm không chứa da sống (No-Hide) kết hợp ức gà thật và hương sữa thơm dịu, giúp làm sạch mảng bám răng và dễ tiêu hóa cho chó.', 'assets/images/products/sp017_jireho_twists.png', 'Đang bán', '2027-11-10', 'Ức gà tươi, tinh bột thực vật dễ tiêu, sữa tách béo, canxi hữu cơ, không chất bảo quản nhân tạo, đạm thô >= 18%, chất béo <= 3%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP015', 'DM03', 'Natural Core Chicken Wrapped Cartilage - Thịt Gà Cuộn Sụn Bổ Khớp', 'Natural Core', 'Bánh thưởng cao cấp Hàn Quốc làm từ 100% ức gà tươi cuộn sụn tự nhiên sấy dẻo, giàu Glucosamine và Chondroitin hỗ trợ xương khớp và sạch răng.', 'assets/images/products/sp016_nc_chicken_cartilage.png', 'Đang bán', '2027-10-05', 'Thịt ức gà tươi nguyên chất, sụn tự nhiên giàu collagen & glucosamine, không phẩm màu, không chất tạo mùi, đạm thô >= 45%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP016', 'DM03', 'JirehO Natural Chicken-Bound Small Bones - Xương Quấn Gà Không Gluten', 'JirehO', 'Xương gặm sạch răng không chứa Gluten quấn thịt ức gà thật bên ngoài, giúp chó giải tỏa căng thẳng, tập nhai và giảm cao răng hiệu quả.', 'assets/images/products/sp007_jireho_bones.png', 'Đang bán', '2027-12-10', 'Thịt ức gà tự nhiên, bột rau củ không chứa Gluten (Gluten-Free), khoáng chất bảo vệ men răng, đạm thô >= 22%, độ ẩm <= 14%.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP004', 'DM04', 'Cat''s Best Original - Cát Gỗ Hữu Cơ Vón Cục Khử Mùi Đức', 'Cat''s Best', 'Cát vệ sinh hữu cơ số 1 châu Âu nhập khẩu CHLB Đức làm từ 100% sợi gỗ tự nhiên công nghệ ICS, thấm hút gấp 7 lần, khóa mùi amoniac và xả được qua bồn cầu.', 'assets/images/products/sp004_catsbest_original.png', 'Đang bán', '2029-12-31', '100% sợi thực vật gỗ thông & linh sam tự nhiên (CHLB Đức), phân hủy sinh học hoàn toàn, không bụi mịn, không hóa chất tẩy trắng.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP017', 'DM04', 'Joe''s Cat Recycled Paper - Cát Giấy Tái Chế Vón Cục Không Bụi 3kg', 'Joe''s Cat', 'Cát giấy vệ sinh sinh thái Joe''s Cat nhập khẩu Đức sản xuất từ sợi cellulose tái chế, siêu nhẹ, không bụi 99%, an toàn cho mèo con và mèo sau phẫu thuật.', 'assets/images/products/sp018_joescat_litter.png', 'Đang bán', '2029-12-31', '100% sợi giấy cellulose tái chế thân thiện môi trường, khả năng giữ mùi tự nhiên và vón cục nhanh, trọng lượng nhẹ dễ dọn dẹp.');
INSERT INTO public."san_pham" ("masp", "madanhmuc", "tensp", "thuonghieu", "motachitiet", "hinhanhchinh", "trangthai", "hansudung", "thanhphandinhduong") VALUES ('SP018', 'DM04', 'Budle''Budle General Shampoo & Rinse - Sữa Tắm Gội Xả Thảo Dược 500ml', 'Budle''Budle', 'Sữa tắm tích hợp dầu xả thảo dược hữu cơ Hàn Quốc Budle''Budle với chiết xuất trà xanh, hương thảo và lô hội giúp làm sạch sâu, khử mùi và dưỡng lông mềm mượt.', 'assets/images/products/sp018_budle_shampoo.png', 'Đang bán', '2028-06-30', 'Tinh chất trà xanh hữu cơ, chiết xuất hoa cúc, lô hội, dầu jojoba, provitamin B5, độ pH cân bằng dịu nhẹ cho da thú cưng.');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.bien_the (32 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT018', 'SP012', 'Túi 1kg', 330000.00, 35, 'assets/images/products/sp001_nc_salmon.png', 360000.00, 270600);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT002', 'SP001', 'Bao 10kg', 1600000.00, 15, 'assets/images/products/sp001_nc_bene_c3.png', NULL, 1312000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT019', 'SP012', 'Túi 2.4kg', 735000.00, 18, 'assets/images/products/sp001_nc_salmon.png', NULL, 602700);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT021', 'SP013', 'Lon 80g', 27000.00, 90, 'assets/images/products/sp015_meowow_can.png', 30000.00, 22140);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT004', 'SP002', 'Bao 15kg', 1521000.00, 10, 'assets/images/products/sp002_reflex_kitten.png', NULL, 1241000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT006', 'SP003', 'Lốc 3 lon (400g)', 120000.00, 40, 'assets/images/products/sp013_whiskas_can_400g.png', NULL, 97500);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT007', 'SP004', 'Bao 5L (2.1kg)', 150000.00, 60, 'assets/images/products/sp004_catsbest_original.png', 165000.00, 123000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT028', 'SP004', 'Bao 10L (4.3kg)', 270000.00, 40, 'assets/images/products/sp004_catsbest_original.png', NULL, 221400);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT012', 'SP005', 'Hộp 3kg', 600000.00, 20, 'assets/images/products/sp005_zenith_adult.png', NULL, 492000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT022', 'SP013', 'Lốc 6 lon (80g)', 155000.00, 40, 'assets/images/products/sp015_meowow_can.png', NULL, 132840);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT009', 'SP006', 'Gói 80g', 14000.00, 150, 'assets/images/products/sp006_whiskas_pouch.png', 16000.00, 10350);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT023', 'SP014', 'Lon 400g', 45000.00, 85, 'assets/images/products/sp003_meo_pate_lon.png', 50000.00, 34166);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT020', 'SP006', 'Combo 6 gói (80g)', 80000.00, 45, 'assets/images/products/sp006_whiskas_pouch.png', NULL, 62100);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT010', 'SP007', 'Gói 110g', 40000.00, 90, 'assets/images/products/sp017_jireho_twists.png', 48000.00, 32800);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT025', 'SP007', 'Combo 3 gói (110g)', 115000.00, 50, 'assets/images/products/sp017_jireho_twists.png', NULL, 98400);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT011', 'SP008', 'Túi 2kg', 256000.00, 30, 'assets/images/products/sp008_reflex_cat_salmon.png', 280000.00, 204000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT013', 'SP009', 'Túi 3kg', 338000.00, 28, 'assets/images/products/sp009_reflex_dog_salmon.png', 365000.00, 270000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT014', 'SP010', 'Túi 1.3kg', 110000.00, 50, 'assets/images/products/sp011_smartheart_puppy.png', 125000.00, 85800);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT015', 'SP010', 'Túi 2.7kg', 210000.00, 30, 'assets/images/products/sp011_smartheart_puppy.png', NULL, 167500);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT024', 'SP014', 'Lốc 3 lon (400g)', 130000.00, 35, 'assets/images/products/sp003_meo_pate_lon.png', NULL, 102500);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT026', 'SP015', 'Gói 65g', 87000.00, 70, 'assets/images/products/sp016_nc_chicken_cartilage.png', 95000.00, 71340);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT027', 'SP015', 'Gói lớn 140g', 137000.00, 45, 'assets/images/products/sp016_nc_chicken_cartilage.png', NULL, 112340);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT029', 'SP016', 'Gói 120g', 45000.00, 80, 'assets/images/products/sp007_jireho_bones.png', 50000.00, 36900);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT030', 'SP016', 'Gói 240g', 85000.00, 50, 'assets/images/products/sp007_jireho_bones.png', NULL, 69700);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT031', 'SP017', 'Bao 3kg (8L)', 170000.00, 45, 'assets/images/products/sp018_joescat_litter.png', 185000.00, 139400);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT032', 'SP018', 'Chai 500ml', 200000.00, 50, 'assets/images/products/sp018_budle_shampoo.png', 225000.00, 164000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT008', 'SP005', 'Hộp 1.2kg', 255000.00, 39, 'assets/images/products/sp005_zenith_adult.png', 280000.00, 209100);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT016', 'SP011', 'Túi 1.2kg', 156000.00, 43, 'assets/images/products/sp012_jireho_duck.png', 175000.00, 127920);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT017', 'SP011', 'Bao 10kg', 1125000.00, 11, 'assets/images/products/sp012_jireho_duck.png', NULL, 922500);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT003', 'SP002', 'Túi 2kg', 241000.00, 33, 'assets/images/products/sp002_reflex_kitten.png', 265000.00, 191000);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT005', 'SP003', 'Lon 400g', 42000.00, 117, 'assets/images/products/sp013_whiskas_can_400g.png', 48000.00, 32500);
INSERT INTO public."bien_the" ("mabienthe", "masp", "tenbienthe", "giaban", "soluongton", "hinhanhbienthe", "giagoc", "giasi") VALUES ('BT001', 'SP001', 'Túi 1.5kg', 280000.00, 43, 'assets/images/products/sp001_nc_bene_c3.png', 310000.00, 229600);

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.chi_tiet_gio_hang (6 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."chi_tiet_gio_hang" ("magiohang", "mabienthe", "soluong") VALUES ('GH001', 'BT007', 1);
INSERT INTO public."chi_tiet_gio_hang" ("magiohang", "mabienthe", "soluong") VALUES ('GH003', 'BT001', 2);
INSERT INTO public."chi_tiet_gio_hang" ("magiohang", "mabienthe", "soluong") VALUES ('GH004', 'BT009', 1);
INSERT INTO public."chi_tiet_gio_hang" ("magiohang", "mabienthe", "soluong") VALUES ('GH004', 'BT005', 1);
INSERT INTO public."chi_tiet_gio_hang" ("magiohang", "mabienthe", "soluong") VALUES ('GH004', 'BT007', 1);
INSERT INTO public."chi_tiet_gio_hang" ("magiohang", "mabienthe", "soluong") VALUES ('GH005', 'BT003', 1);

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.voucher (2 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."voucher" ("mavoucher", "tenvoucher", "loaigiamgia", "giatrigiam", "donhangtoithieu", "ngaybatdau", "ngayketthuc", "soluongconlai", "kichhoat", "giamtoida", "moikhachtoida", "soluongphathanh") VALUES ('FREESHIP', 'Giảm 10% giá trị đơn hàng từ 200K', 'PhanTram', 10.00, 200000.00, '2025-12-31 17:00:00+00', '2026-12-31 16:59:59+00', 498, TRUE, NULL, 1, 500);
INSERT INTO public."voucher" ("mavoucher", "tenvoucher", "loaigiamgia", "giatrigiam", "donhangtoithieu", "ngaybatdau", "ngayketthuc", "soluongconlai", "kichhoat", "giamtoida", "moikhachtoida", "soluongphathanh") VALUES ('KIBO20', 'Giảm 20K cho đơn hàng đầu tiên', 'SoTien', 20000.00, 200000.00, '2025-12-31 17:00:00+00', '2026-12-31 16:59:59+00', 94, TRUE, NULL, 1, 95);

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.tram_cuu_ho (2 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."tram_cuu_ho" ("matram", "tentram", "nguoidaidien", "sodienthoai", "diachi", "khuvuc", "trangthaihoptac", "sotaikhoan", "nganhang") VALUES ('TCH01', 'Trạm Cứu Hộ Động Vật Nông Lâm', 'Bác sĩ thú y Nguyễn Văn An', '0912345678', 'Khuôn viên ĐH Nông Lâm, TP. Thủ Đức, TP.HCM', 'TP. Hồ Chí Minh', 'Đang hợp tác', '0772906756', 'MB Bank (Ngân hàng TMCP Quân Đội)');
INSERT INTO public."tram_cuu_ho" ("matram", "tentram", "nguoidaidien", "sodienthoai", "diachi", "khuvuc", "trangthaihoptac", "sotaikhoan", "nganhang") VALUES ('TCH02', 'Trạm Cứu Hộ Động Vật Sài Gòn Time', 'Chị Trần Thị Mai Lan', '0987654321', 'Đường Tô Ngọc Vân, P. Tam Phú, TP. Thủ Đức, TP.HCM', 'TP. Hồ Chí Minh', 'Đang hợp tác', 190388992211, 'Techcombank (Ngân hàng TMCP Kỹ Thương)');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.chung_tu_giai_ngan (1 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."chung_tu_giai_ngan" ("machungtu", "matram", "sotiengiaingan", "ngaychuyenkhoan", "mucdichsudung", "filehoadonchungtu") VALUES ('CT001', 'TCH01', 14250.00, '2026-10-26', 'Chi phí thức ăn hạt và điều trị phẫu thuật triệt sản 35 ca chó mèo cơ nhỡ', 'assets/docs/chung_tu_nong_lam_092026.pdf');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.don_hang (24 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611122793', 'KV004', 'KIBO20', '2026-11-12 05:39:17.981801+00', 280000.00, 20000.00, '0.00', 260000.00, 'Chờ xác nhận', 'Quỳnh Giao', '', '171 quốc lộ 13, Phường Tăng Nhơn Phú, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-2611122793');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611129735', 'KV003', NULL, '2026-11-12 05:43:10.63947+00', 241000.00, '0.00', '0.00', 241000.00, 'Chờ xác nhận', 'Quỳnh Giao', '0772906756', '171 quốc lộ 13, Phường Tăng Nhơn Phú, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-2611129735');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611121086', 'KV003', NULL, '2026-11-12 05:43:43.874616+00', 280000.00, '0.00', '0.00', 280000.00, 'Chờ xác nhận', 'Quỳnh Giao', '0772906756', '171 quốc lộ 13, Phường Tăng Nhơn Phú, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-2611121086');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611122062', 'KV003', NULL, '2026-11-12 05:45:11.383395+00', 42000.00, '0.00', 20000.00, 62000.00, 'Chờ xác nhận', 'Quỳnh Giao', '0772906756', '171 quốc lộ 13, Phường Tăng Nhơn Phú, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-2611122062');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611123166', 'KV003', NULL, '2026-11-12 05:46:39.348857+00', 42000.00, '0.00', 20000.00, 62000.00, 'Chờ xác nhận', 'Quỳnh Giao', '0772906756', '171 quốc lộ 13, Phường Tăng Nhơn Phú, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-2611123166');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-8922', 'KH001', 'KIBO20', '2026-10-30 07:20:00+00', 445000.00, 20000.00, '0.00', 425000.00, 'Đang giao', 'Nguyễn Lê Quỳnh Mai', '0772906756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', 'Để trước cửa nhà nếu không có ai.', NULL, NULL, 'KB-8922');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611018545', 'KH001', NULL, '2026-11-01 12:44:02.106773+00', 285000.00, '0.00', '0.00', 285000.00, 'Chờ xác nhận', 'Nguyễn Lê Quỳnh Mai', '0772906756', '234 Lê Văn Việt, TP. Thủ Đức, TP.HCM', '', NULL, NULL, 'KB-2611018545');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-3645', 'KH001', NULL, '2026-10-31 12:58:26+00', 1885000.00, '0.00', '0.00', 1885000.00, 'Đang giao', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, TNPB, TD', '', NULL, NULL, 'KB-3645');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-3760', 'KH001', NULL, '2026-10-31 12:58:24+00', 1885000.00, '0.00', '0.00', 1885000.00, 'Đang giao', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, TNPB, TD', '', NULL, NULL, 'KB-3760');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-8149', 'KH001', NULL, '2026-10-31 12:58:29+00', 1885000.00, '0.00', '0.00', 1885000.00, 'Đang giao', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, TNPB, TD', '', NULL, NULL, 'KB-8149');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-9507', 'KH001', NULL, '2026-10-31 12:58:21+00', 1885000.00, '0.00', '0.00', 1885000.00, 'Đang giao', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, TNPB, TD', '', NULL, NULL, 'KB-9507');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-8923', 'KH002', NULL, '2026-10-31 01:45:00+00', 185000.00, '0.00', 20000.00, 205000.00, 'Đang giao', 'Lã Nguyễn Hoàng Phúc', '0987654321', 'Số 70 Tô Ký, Phường Tân Chánh Hiệp, Quận 12, TP. Hồ Chí Minh', 'Đơn hàng mới tạo', NULL, NULL, 'KB-8923');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-5156', 'KH001', NULL, '2026-10-31 16:15:56+00', 1995000.00, '0.00', '0.00', 1995000.00, 'Chờ xác nhận', 'Nguyễn Lê Quỳnh Như', '0772 906 756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-5156');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-1652', 'KH001', NULL, '2026-10-31 16:40:58+00', 90000.00, '0.00', 20000.00, 110000.00, 'Chờ xác nhận', 'Mi Mi', '0866723459', '71 ql13, Phường/Xã trung tâm, Bình Thạnh, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-1652');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-3825', 'KH001', NULL, '2026-10-31 16:23:16+00', 90000.00, '0.00', 20000.00, 110000.00, 'Chờ xác nhận', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-3825');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-8522', 'KH001', NULL, '2026-10-31 16:27:10+00', 90000.00, '0.00', 20000.00, 110000.00, 'Chờ xác nhận', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-8522');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-8381', 'KH001', NULL, '2026-10-31 16:30:29+00', 445000.00, '0.00', '0.00', 445000.00, 'Chờ xác nhận', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-8381');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2715', 'KH001', NULL, '2026-10-31 16:32:01+00', 90000.00, '0.00', 20000.00, 110000.00, 'Chờ xác nhận', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-2715');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-6677', 'KH001', NULL, '2026-10-31 16:34:34+00', 890000.00, '0.00', '0.00', 890000.00, 'Chờ xác nhận', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-6677');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-8921', 'KH001', NULL, '2026-10-25 02:15:00+00', 285000.00, '0.00', '0.00', 285000.00, 'Giao thành công', 'Nguyễn Lê Quỳnh Mai', '0772906756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', 'Giao giờ hành chính, gọi trước 15 phút.', '2026-10-25 02:15:00+00', NULL, 'KB-8921');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-1029', 'KH001', NULL, '2026-10-31 13:38:54+00', 180000.00, '0.00', 20000.00, 200000.00, 'Giao thành công', 'Nguyễn Lê Quỳnh Mai', '0772 906 756', '234 Lê Văn Việt, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', '2026-10-31 13:38:54+00', NULL, 'KB-1029');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-9843', 'KH007', NULL, '2026-11-01 11:00:45+00', 216000.00, '0.00', '0.00', 216000.00, 'Giao thành công', 'Quỳnh Giao', '0772906756', '171 quốc lộ 13, Phường Tăng Nhơn Phú B, TP. Thủ Đức, TP. Hồ Chí Minh', '', '2026-11-01 11:00:45+00', NULL, 'KB-9843');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611075983', 'KV004', 'FREESHIP', '2026-11-07 08:31:26.623165+00', 241000.00, 24100.00, '0.00', 216900.00, 'Chờ xác nhận', 'Quỳnh Giao', '', '171 quốc lộ 13, Phường Tăng Nhơn Phú, TP. Thủ Đức, TP. Hồ Chí Minh', '', NULL, NULL, 'KB-2611075983');
INSERT INTO public."don_hang" ("madonhang", "makh", "mavoucher", "ngaydathang", "tongtienhang", "tiengiamgia", "phivanchuyen", "tongthanhtoan", "trangthaidonhang", "tennguoinhan", "sodienthoainhan", "diachigiaohang", "ghichu", "ngaygiaothanhcong", "mavandon", "matracuu") VALUES ('KB-2611076196', 'KV004', 'FREESHIP', '2026-11-07 08:32:53.739307+00', 297000.00, 29700.00, '0.00', 267300.00, 'Chờ đóng gói', 'thanh phuong', '', 'city role, Phường An Khánh, TP. Thủ Đức, TP. Hồ Chí Minh', 'city role', NULL, NULL, 'KB-2611076196');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.chi_tiet_don_hang (20 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-8921', 'BT001', 1, 285000.00, 285000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-8922', 'BT003', 1, 445000.00, 445000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-8923', 'BT007', 1, 185000.00, 185000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-5156', 'BT001', 7, 285000.00, 1995000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-3825', 'BT005', 1, 90000.00, 90000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-8522', 'BT005', 1, 90000.00, 90000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-8381', 'BT003', 1, 445000.00, 445000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2715', 'BT005', 1, 90000.00, 90000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-6677', 'BT003', 2, 445000.00, 890000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-1652', 'BT005', 1, 90000.00, 90000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-9843', 'BT009', 1, 216000.00, 216000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611018545', 'BT001', 1, 285000.00, 285000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611075983', 'BT003', 1, 241000.00, 241000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611076196', 'BT008', 1, 255000.00, 255000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611076196', 'BT005', 1, 42000.00, 42000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611122793', 'BT001', 1, 280000.00, 280000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611129735', 'BT003', 1, 241000.00, 241000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611121086', 'BT001', 1, 280000.00, 280000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611122062', 'BT005', 1, 42000.00, 42000.00);
INSERT INTO public."chi_tiet_don_hang" ("madonhang", "mabienthe", "soluong", "dongia", "thanhtien") VALUES ('KB-2611123166', 'BT005', 1, 42000.00, 42000.00);

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.thanh_toan (24 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD001', 'KB-8921', 'Chuyển khoản VietQR', 285000.00, '2026-10-25 02:16:00+00', 'Thành công');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD002', 'KB-8922', 'Chuyển khoản VietQR', 425000.00, '2026-10-30 07:21:00+00', 'Thành công');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD003', 'KB-8923', 'Thanh toán khi nhận hàng (COD)', 205000.00, '2026-10-31 01:45:00+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD596', 'KB-9507', 'Thanh toán khi nhận hàng (COD)', 1885000.00, '2026-10-31 12:58:21+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD530', 'KB-3760', 'Thanh toán khi nhận hàng (COD)', 1885000.00, '2026-10-31 12:58:24+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD785', 'KB-3645', 'Thanh toán khi nhận hàng (COD)', 1885000.00, '2026-10-31 12:58:26+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD186', 'KB-8149', 'Thanh toán khi nhận hàng (COD)', 1885000.00, '2026-10-31 12:58:29+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD757', 'KB-1029', 'Thanh toán khi nhận hàng (COD)', 200000.00, '2026-10-31 13:38:54+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD899', 'KB-5156', 'Thanh toán khi nhận hàng (COD)', 1995000.00, '2026-10-31 16:15:56+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD228', 'KB-3825', 'Thanh toán khi nhận hàng (COD)', 110000.00, '2026-10-31 16:23:16+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD898', 'KB-8522', 'Thanh toán khi nhận hàng (COD)', 110000.00, '2026-10-31 16:27:10+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD312', 'KB-8381', 'Thanh toán khi nhận hàng (COD)', 445000.00, '2026-10-31 16:30:29+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD893', 'KB-2715', 'Thanh toán khi nhận hàng (COD)', 110000.00, '2026-10-31 16:32:01+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD472', 'KB-6677', 'Thanh toán khi nhận hàng (COD)', 890000.00, '2026-10-31 16:34:34+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD567', 'KB-1652', 'Thanh toán khi nhận hàng (COD)', 110000.00, '2026-10-31 16:40:58+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD520', 'KB-9843', 'Thanh toán khi nhận hàng (COD)', 216000.00, '2026-11-01 11:00:45+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD908', 'KB-2611122793', 'Thanh toán khi nhận hàng (COD)', 260000.00, '2026-11-12 05:39:17.981801+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD909', 'KB-2611129735', 'Thanh toán khi nhận hàng (COD)', 241000.00, '2026-11-12 05:43:10.63947+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD904', 'KB-2611018545', 'Thanh toán khi nhận hàng (COD)', 285000.00, '2026-11-01 12:44:02.106773+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD910', 'KB-2611121086', 'Thanh toán khi nhận hàng (COD)', 280000.00, '2026-11-12 05:43:43.874616+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD911', 'KB-2611122062', 'Thanh toán khi nhận hàng (COD)', 62000.00, '2026-11-12 05:45:11.383395+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD912', 'KB-2611123166', 'Thanh toán khi nhận hàng (COD)', 62000.00, '2026-11-12 05:46:39.348857+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD906', 'KB-2611075983', 'Chuyển khoản VietQR', 216900.00, '2026-11-07 08:31:26.623165+00', 'Chờ thanh toán');
INSERT INTO public."thanh_toan" ("magiaodich", "madonhang", "phuongthuctt", "sotien", "thoigiantt", "trangthaitt") VALUES ('GD907', 'KB-2611076196', 'Thanh toán khi nhận hàng (COD)', 267300.00, '2026-11-07 08:32:53.739307+00', 'Chờ thanh toán');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.yeu_cau_doi_tra (1 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."yeu_cau_doi_tra" ("mayeucau", "madonhang", "lydodoitra", "hinhanhminhchung", "ngayyeucau", "trangthaiyeucau", "huongxuly", "ngayxuly", "sotienhoan") VALUES ('DT001', 'KB-8921', 'Bao bì nguyên vẹn, muốn đổi sang dòng vị thịt gà cho mèo con', 'assets/images/returns/dt001.png', '2026-10-26 08:30:00+00', 'Đã hoàn tất', 'Đổi sang mã BT003 và phụ thu chênh lệch', NULL, NULL);

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.quy_cuu_tro (24 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT944', 'KB-2611075983', 10845.00, '2026-11-07 08:31:26.623165+00', 'KB-2611075983', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT945', 'KB-2611076196', 13365.00, '2026-11-07 08:32:53.739307+00', 'KB-2611076196', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT002', 'KB-8922', 21250.00, '2026-10-30 07:20:00+00', 'KB-8922', 'Đã trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT946', 'KB-2611122793', 13000.00, '2026-11-12 05:39:17.981801+00', 'KB-2611122793', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT947', 'KB-2611129735', 12050.00, '2026-11-12 05:43:10.63947+00', 'KB-2611129735', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT948', 'KB-2611121086', 14000.00, '2026-11-12 05:43:43.874616+00', 'KB-2611121086', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT949', 'KB-2611122062', 2100.00, '2026-11-12 05:45:11.383395+00', 'KB-2611122062', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT950', 'KB-2611123166', 2100.00, '2026-11-12 05:46:39.348857+00', 'KB-2611123166', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT001', 'KB-8921', 14250.00, '2026-10-25 02:15:00+00', 'KB-8921', 'Đã giải ngân', 'CT001', 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT911', 'KB-3645', 94250.00, '2026-10-31 12:58:26+00', 'KB-3645', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT937', 'KB-3760', 94250.00, '2026-10-31 12:58:24+00', 'KB-3760', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT472', 'KB-8149', 94250.00, '2026-10-31 12:58:29+00', 'KB-8149', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT331', 'KB-9507', 94250.00, '2026-10-31 12:58:21+00', 'KB-9507', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT003', 'KB-8923', 9250.00, '2026-10-31 01:45:00+00', 'KB-8923', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT733', 'KB-5156', 99750.00, '2026-10-31 16:15:56+00', 'KB-5156', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT768', 'KB-3825', 4500.00, '2026-10-31 16:23:16+00', 'KB-3825', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT633', 'KB-8522', 4500.00, '2026-10-31 16:27:10+00', 'KB-8522', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT625', 'KB-8381', 22250.00, '2026-10-31 16:30:29+00', 'KB-8381', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT477', 'KB-2715', 4500.00, '2026-10-31 16:32:01+00', 'KB-2715', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT313', 'KB-6677', 44500.00, '2026-10-31 16:34:34+00', 'KB-6677', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT796', 'KB-1652', 4500.00, '2026-10-31 16:40:58+00', 'KB-1652', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT942', 'KB-2611018545', 14250.00, '2026-11-01 12:44:02.106773+00', 'KB-2611018545', 'Chờ trích quỹ', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT515', 'KB-1029', 9000.00, '2026-10-31 13:38:54+00', 'KB-1029', 'Đã giải ngân', NULL, 'TCH01');
INSERT INTO public."quy_cuu_tro" ("madonggop", "madonhang", "sotiendonggop", "ngayghinhan", "matracuu", "trangthaiquy", "machungtu", "matram") VALUES ('QCT196', 'KB-9843', 10800.00, '2026-11-01 11:00:45+00', 'KB-9843', 'Đã giải ngân', NULL, 'TCH01');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.nhat_ky (83 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0001', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-04 01:51:51.472061+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0002', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-04 01:54:40.226534+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0003', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 01:57:05.878235+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0004', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 02:02:54.495203+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0005', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 02:07:49.305161+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0006', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-04 05:41:37.273424+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0007', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 05:41:38.708765+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0008', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-04 05:43:36.159365+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0009', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-04 07:30:37.835686+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0010', NULL, 'Thêm danh mục', 'danh_muc', 'DM08', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-04 07:30:56.316075+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0011', NULL, 'Sửa danh mục', 'danh_muc', 'DM08', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-04 07:30:57.652046+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0012', NULL, 'Xóa danh mục', 'danh_muc', 'DM08', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-04 07:30:59.04244+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0013', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-04 07:31:16.923838+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0014', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 07:31:18.052383+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0015', NULL, 'Thêm danh mục', 'danh_muc', 'DM08', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-04 07:31:46.459984+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0016', NULL, 'Sửa danh mục', 'danh_muc', 'DM08', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-04 07:31:47.874829+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0017', NULL, 'Xóa danh mục', 'danh_muc', 'DM08', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-04 07:31:49.451818+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0018', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-04 07:31:50.405312+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0019', NULL, 'Xác nhận thanh toán', 'thanh_toan', 'GD-KB-VQR-141603', 'Xác nhận đã nhận chuyển khoản 225.000đ cho đơn KB-VQR-141603', '2026-11-04 13:16:07.920139+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0020', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-04 14:37:43.266104+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0021', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-04 14:39:12.025831+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0022', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 14:39:13.318993+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0023', NULL, 'Thêm danh mục', 'danh_muc', 'DM08', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-04 14:39:18.357626+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0024', NULL, 'Sửa danh mục', 'danh_muc', 'DM08', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-04 14:39:19.853635+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0025', NULL, 'Xóa danh mục', 'danh_muc', 'DM08', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-04 14:39:21.448431+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0026', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-04 14:39:22.512881+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0027', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-04 15:25:49.784743+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0028', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 15:25:51.010131+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0029', NULL, 'Xác nhận thanh toán', 'thanh_toan', 'GD-KB-VQR-162739', 'Xác nhận đã nhận chuyển khoản 225.000đ cho đơn KB-VQR-162739', '2026-11-04 15:27:44.172815+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0030', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-04 15:27:52.161364+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0031', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-04 15:27:53.454928+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0032', NULL, 'Thêm danh mục', 'danh_muc', 'DM08', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-04 15:27:59.317908+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0033', NULL, 'Sửa danh mục', 'danh_muc', 'DM08', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-04 15:28:01.158049+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0034', NULL, 'Xóa danh mục', 'danh_muc', 'DM08', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-04 15:28:02.803252+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0035', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-04 15:28:04.02934+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0036', NULL, 'Xác nhận thanh toán', 'thanh_toan', 'GD-KB-VQR-162849', 'Xác nhận đã nhận chuyển khoản 225.000đ cho đơn KB-VQR-162849', '2026-11-04 15:28:54.618129+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0037', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-07 05:09:30.959448+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0038', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-07 05:14:13.160669+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0039', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-07 05:26:36.265192+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0040', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK010', 'Gửi mã OTP khôi phục mật khẩu cho test_auth_14418@example.com', '2026-11-07 06:11:15.13665+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0041', NULL, 'Khôi phục mật khẩu OTP', 'tai_khoan', 'TK010', 'Đặt lại mật khẩu bằng OTP thành công', '2026-11-07 06:11:16.240465+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0042', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK004', 'Gửi mã OTP khôi phục mật khẩu cho user2@gmail.com', '2026-11-07 06:13:29.385558+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0043', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK004', 'Gửi mã OTP khôi phục mật khẩu cho user2@gmail.com', '2026-11-07 06:13:49.016899+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0044', NULL, 'Nhập kho', 'bien_the', 'SP011', 'Cộng thêm +1 món vào kho cho JirehO Duck Formula - Hạt Hữu Cơ Thịt Vịt & Rau Củ Cho Chó. Tồn kho mới: 55', '2026-11-07 11:32:48.147998+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0045', NULL, 'Thêm danh mục', 'danh_muc', 'DM05', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-08 04:14:07.357755+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0046', NULL, 'Sửa danh mục', 'danh_muc', 'DM05', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-08 04:14:09.309399+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0047', NULL, 'Xóa danh mục', 'danh_muc', 'DM05', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-08 04:14:10.681386+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0048', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-08 04:14:11.576664+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0049', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-08 09:50:32.474411+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0050', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-08 09:51:18.244478+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0051', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-08 09:51:19.591331+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0052', NULL, 'Xác nhận thanh toán', 'thanh_toan', 'GD-KB-VQR-105125', 'Xác nhận đã nhận chuyển khoản 225.000đ cho đơn KB-VQR-105125', '2026-11-08 09:51:30.748896+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0053', NULL, 'Thêm danh mục', 'danh_muc', 'DM05', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-08 09:51:37.204192+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0054', NULL, 'Sửa danh mục', 'danh_muc', 'DM05', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-08 09:51:38.838283+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0055', NULL, 'Xóa danh mục', 'danh_muc', 'DM05', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-08 09:51:40.6829+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0056', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-08 09:51:41.852469+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0057', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-12 07:47:51.508816+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0058', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-12 07:48:48.067393+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0059', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-12 07:48:49.420031+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0060', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-12 07:50:19.065878+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0061', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-12 07:50:20.507167+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0062', NULL, 'Xác nhận thanh toán', 'thanh_toan', 'GD-KB-VQR-085035', 'Xác nhận đã nhận chuyển khoản 225.000đ cho đơn KB-VQR-085035', '2026-11-12 07:50:40.369302+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0063', NULL, 'Thêm danh mục', 'danh_muc', 'DM05', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-12 07:54:47.654367+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0064', NULL, 'Sửa danh mục', 'danh_muc', 'DM05', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-12 07:54:49.495947+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0065', NULL, 'Xóa danh mục', 'danh_muc', 'DM05', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-12 07:54:51.340701+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0066', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-12 07:54:52.574084+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0067', NULL, 'Thêm danh mục', 'danh_muc', 'DM05', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-12 07:55:33.531617+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0068', NULL, 'Sửa danh mục', 'danh_muc', 'DM05', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-12 07:55:35.280004+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0069', NULL, 'Xóa danh mục', 'danh_muc', 'DM05', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-12 07:55:36.71276+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0070', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-12 07:55:37.94138+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0071', NULL, 'Thêm danh mục', 'danh_muc', 'DM05', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-12 08:11:03.13008+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0072', NULL, 'Sửa danh mục', 'danh_muc', 'DM05', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-12 08:11:04.587371+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0073', NULL, 'Xóa danh mục', 'danh_muc', 'DM05', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-12 08:11:06.210747+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0074', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-12 08:11:07.294336+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0075', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-12 08:16:57.006279+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0076', NULL, 'Thêm danh mục', 'danh_muc', 'DM05', 'Tên danh mục: Danh mục Test Tạm QA', '2026-11-12 08:42:29.380314+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0077', NULL, 'Sửa danh mục', 'danh_muc', 'DM05', 'Cập nhật tên: Danh mục Test Đã Sửa', '2026-11-12 08:42:31.010523+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0078', NULL, 'Xóa danh mục', 'danh_muc', 'DM05', 'Đã xóa danh mục: Danh mục Test Đã Sửa', '2026-11-12 08:42:32.742111+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0079', NULL, 'Yêu cầu OTP quên mật khẩu', 'tai_khoan', 'TK006', 'Gửi mã OTP khôi phục mật khẩu cho user@gmail.com', '2026-11-12 08:42:33.886054+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0080', NULL, 'Giải ngân quỹ cứu trợ', 'chung_tu_giai_ngan', 'CT002', 'Giải ngân 50.000đ cho Trạm Cứu Hộ Động Vật Nông Lâm (TCH01). Mục đích: Cứu trợ đợt kiểm thử TC15', '2026-11-12 08:44:31.35738+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0081', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Ngừng kinh doanh', '2026-11-12 08:45:39.419342+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0082', NULL, 'Đổi trạng thái sản phẩm', 'san_pham', 'SP001', 'Trạng thái mới: Đang bán', '2026-11-12 08:45:40.78924+00');
INSERT INTO public."nhat_ky" ("manhatky", "matk", "hanhdong", "doituong", "madoituong", "chitiet", "thoigian") VALUES ('NK0083', NULL, 'Xác nhận thanh toán', 'thanh_toan', 'GD-KB-VQR-094557', 'Xác nhận đã nhận chuyển khoản 225.000đ cho đơn KB-VQR-094557', '2026-11-12 08:46:02.481865+00');

-- ------------------------------------------------------------
-- Dữ liệu bảng: public.dang_nhap_that_bai (1 bản ghi)
-- ------------------------------------------------------------
INSERT INTO public."dang_nhap_that_bai" ("ip", "email", "solanthatbai", "khoaden", "lancoicuoi") VALUES ('127.0.0.1', 'admin@kibo.vn', 1, NULL, '2026-11-12 13:01:57.449638+00');
