<?php
declare(strict_types=1);

/**
 * Lớp ngoại lệ nghiệp vụ dành riêng cho KIBO Platform.
 * Mọi ngoại lệ nghiệp vụ tự ném (validation, logic tồn kho, voucher, đổi trả...)
 * kế thừa hoặc dùng lớp này để được phép hiển thị thông báo chi tiết cho client.
 * Các ngoại lệ khác (PDOException, TypeError, Throwable) sẽ bị che chắn để bảo mật.
 */
class BusinessException extends Exception {
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
