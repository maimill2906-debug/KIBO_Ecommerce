<?php
// ====================================================================
// HELPER: IdGen.php
// Sinh mã tự tăng an toàn chống race condition bằng PostgreSQL Advisory Xact Lock
// ====================================================================

require_once __DIR__ . '/../config/database.php';

class IdGen {
    /**
     * Sinh mã định danh tiếp theo cho bảng
     * @param PDO $db
     * @param string $table Tên bảng (quy_cuu_tro, thanh_toan, yeu_cau_doi_tra, khach_hang, ...)
     * @param string $col Tên cột khóa chính (madonggop, magiaodich, mayeucau, makh, ...)
     * @param string $prefix Tiền tố (QCT, GD, DT, KV, SP, DM, ...)
     * @param int $pad Số lượng chữ số (mặc định 3, ví dụ 001)
     * @return string
     */
    public static function next($db, $table, $col, $prefix, $pad = 3): string {
        // 1. Khóa giao dịch độc quyền theo tên bảng chống race condition
        $stmtLock = $db->prepare("SELECT pg_advisory_xact_lock(hashtext(?))");
        $stmtLock->execute([$table]);

        // 2. Tìm giá trị số lớn nhất hiện tại
        $prefixLen = strlen($prefix) + 1; // 1-based index cho SUBSTRING trong PostgreSQL
        $regex = '^' . preg_quote($prefix, '/') . '[0-9]+$';

        $sql = "SELECT MAX(CAST(SUBSTRING({$col} FROM {$prefixLen}) AS INTEGER))
                FROM {$table}
                WHERE {$col} ~ ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$regex]);
        $maxNum = $stmt->fetchColumn();

        $nextNum = ($maxNum !== false && $maxNum !== null) ? ((int)$maxNum + 1) : 1;
        return $prefix . str_pad((string)$nextNum, $pad, '0', STR_PAD_LEFT);
    }
}
