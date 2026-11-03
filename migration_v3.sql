BEGIN;

-- ====================================================================
-- KIBO PLATFORM - MIGRATION V3 (Hoàn thiện Quản trị, Khách hàng & Bảo mật)
-- Chạy được nhiều lần (Idempotent), trong 1 Transaction duy nhất
-- ====================================================================

-- 1. Tài khoản: Bổ sung trạng thái khóa/hoạt động
ALTER TABLE tai_khoan ADD COLUMN IF NOT EXISTS trangthai VARCHAR(20) DEFAULT 'Hoạt động';
UPDATE tai_khoan SET trangthai = 'Hoạt động' WHERE trangthai IS NULL;

-- 2. Đơn hàng: Mã vận đơn (admin nhập khi chuyển Đã xuất kho)
ALTER TABLE don_hang ADD COLUMN IF NOT EXISTS mavandon VARCHAR(30) DEFAULT NULL;

-- 3. Voucher: Giảm tối đa, Giới hạn lượt mỗi khách, Số lượng phát hành ban đầu
ALTER TABLE voucher ADD COLUMN IF NOT EXISTS giamtoida NUMERIC(12,2) DEFAULT NULL;
ALTER TABLE voucher ADD COLUMN IF NOT EXISTS moikhachtoida INT DEFAULT 1;
ALTER TABLE voucher ADD COLUMN IF NOT EXISTS soluongphathanh INT DEFAULT NULL;
UPDATE voucher SET soluongphathanh = soluongconlai WHERE soluongphathanh IS NULL;

-- 4. Bảng Nhật ký thao tác Admin (Audit Log)
CREATE TABLE IF NOT EXISTS nhat_ky (
    manhatky VARCHAR(30) PRIMARY KEY,
    matk VARCHAR(20) NULL,
    hanhdong VARCHAR(100) NOT NULL,
    doituong VARCHAR(50) NOT NULL,
    madoituong VARCHAR(50) NULL,
    chitiet TEXT NULL,
    thoigian TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_nhat_ky_thoigian ON nhat_ky(thoigian DESC);

-- 5. Sản phẩm & Danh mục: HSD, Thành phần dinh dưỡng, Phân loại thú cưng
ALTER TABLE san_pham ADD COLUMN IF NOT EXISTS hansudung DATE DEFAULT NULL;
ALTER TABLE san_pham ADD COLUMN IF NOT EXISTS thanhphandinhduong TEXT DEFAULT NULL;
ALTER TABLE danh_muc ADD COLUMN IF NOT EXISTS loaithucung VARCHAR(20) DEFAULT 'Chung';
ALTER TABLE yeu_cau_doi_tra ADD COLUMN IF NOT EXISTS sotienhoan NUMERIC(12,2) DEFAULT NULL;

-- 6. Bảng Sổ địa chỉ khách hàng (Address Book)
CREATE TABLE IF NOT EXISTS so_dia_chi (
    madiachi VARCHAR(30) PRIMARY KEY,
    makh VARCHAR(20) NOT NULL REFERENCES khach_hang(makh) ON DELETE CASCADE,
    tennguoinhan VARCHAR(100) NOT NULL,
    sodienthoai VARCHAR(20) NOT NULL,
    diachichitiet TEXT NOT NULL,
    quanhuyen VARCHAR(100) NOT NULL,
    tinhthanh VARCHAR(100) NOT NULL,
    macdinh BOOLEAN DEFAULT FALSE,
    ngaytao TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_so_dia_chi_makh ON so_dia_chi(makh);

-- 7. Bảng Chống dò mật khẩu (Brute-force protection: IP + Email)
CREATE TABLE IF NOT EXISTS dang_nhap_that_bai (
    ip VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    solanthatbai INT DEFAULT 1,
    khoaden TIMESTAMPTZ NULL,
    lancoicuoi TIMESTAMPTZ DEFAULT NOW(),
    PRIMARY KEY (ip, email)
);

COMMIT;
