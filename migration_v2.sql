BEGIN;
-- hàm tạm: xóa CHECK theo tên cột (không biết tên constraint)
CREATE OR REPLACE FUNCTION pg_temp.drop_checks(t regclass, col text) RETURNS void AS $$
DECLARE r record;
BEGIN
  FOR r IN SELECT conname FROM pg_constraint
           WHERE conrelid=t AND contype='c' AND pg_get_constraintdef(oid) ILIKE '%'||col||'%'
  LOOP EXECUTE format('ALTER TABLE %s DROP CONSTRAINT %I', t, r.conname); END LOOP;
END $$ LANGUAGE plpgsql;

-- 1. mã đơn dài hơn (mã mới 13 ký tự)
ALTER TABLE chi_tiet_don_hang ALTER COLUMN madonhang TYPE VARCHAR(20);
ALTER TABLE thanh_toan        ALTER COLUMN madonhang TYPE VARCHAR(20);
ALTER TABLE yeu_cau_doi_tra   ALTER COLUMN madonhang TYPE VARCHAR(20);
ALTER TABLE quy_cuu_tro       ALTER COLUMN madonhang TYPE VARCHAR(20),
                              ALTER COLUMN matracuu  TYPE VARCHAR(20);
ALTER TABLE don_hang          ALTER COLUMN madonhang TYPE VARCHAR(20);

-- 2. cột mới
ALTER TABLE don_hang       ADD COLUMN IF NOT EXISTS ngaygiaothanhcong TIMESTAMPTZ;
UPDATE don_hang SET ngaygiaothanhcong = ngaydathang
 WHERE trangthaidonhang='Giao thành công' AND ngaygiaothanhcong IS NULL;
ALTER TABLE bien_the       ADD COLUMN IF NOT EXISTS giagoc NUMERIC(12,2);   -- NULL = không giảm giá
ALTER TABLE voucher        ADD COLUMN IF NOT EXISTS kichhoat BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE yeu_cau_doi_tra ADD COLUMN IF NOT EXISTS ngayxuly TIMESTAMPTZ;
ALTER TABLE khach_hang     ALTER COLUMN matk DROP NOT NULL;                  -- khách vãng lai

-- 3. trạng thái quỹ
SELECT pg_temp.drop_checks('quy_cuu_tro','trangthaiquy');
UPDATE quy_cuu_tro q SET trangthaiquy = CASE
  WHEN q.trangthaiquy='Đã giải ngân'          THEN 'Đã giải ngân'
  WHEN d.trangthaidonhang='Giao thành công'   THEN 'Đã trích quỹ'
  WHEN d.trangthaidonhang='Đã hủy'            THEN 'Đã hủy'
  ELSE 'Chờ trích quỹ' END
FROM don_hang d WHERE d.madonhang=q.madonhang;
ALTER TABLE quy_cuu_tro ADD CONSTRAINT chk_quy_trangthai
  CHECK (trangthaiquy IN ('Chờ trích quỹ','Đã trích quỹ','Đã giải ngân','Đã hủy'));
CREATE UNIQUE INDEX IF NOT EXISTS uq_quy_madonhang ON quy_cuu_tro(madonhang);

-- 4. trạng thái đổi trả + chống gửi trùng ở mức DB
SELECT pg_temp.drop_checks('yeu_cau_doi_tra','trangthaiyeucau');
ALTER TABLE yeu_cau_doi_tra ADD CONSTRAINT chk_doitra_trangthai
  CHECK (trangthaiyeucau IN ('Chờ xử lý','Đã đồng ý đổi','Đã hoàn tiền','Đã từ chối','Đã hoàn tất'));
CREATE UNIQUE INDEX IF NOT EXISTS uq_doitra_con_hieu_luc
  ON yeu_cau_doi_tra(madonhang) WHERE trangthaiyeucau <> 'Đã từ chối';

-- 5. chặn tồn kho/voucher âm
ALTER TABLE bien_the DROP CONSTRAINT IF EXISTS chk_bt_ton;
ALTER TABLE bien_the ADD  CONSTRAINT chk_bt_ton CHECK (soluongton >= 0);
ALTER TABLE voucher  DROP CONSTRAINT IF EXISTS chk_vc_sl;
ALTER TABLE voucher  ADD  CONSTRAINT chk_vc_sl CHECK (soluongconlai >= 0);

-- 6. mật khẩu demo (MatKhauDemo@2026)
UPDATE tai_khoan SET matkhau='$2y$10$O0YEgnPpbqHtUjbZPKjSgOi/xrJz9SRVGkPMVFB0EehQKITEO/Ziq' WHERE matk IN ('TK001','TK002','TK003');
COMMIT;
