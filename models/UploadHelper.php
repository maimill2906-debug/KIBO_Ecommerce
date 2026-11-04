<?php
// ====================================================================
// HELPER: UploadHelper.php
// Tải lên file an toàn (kiểm tra MIME type, kích thước, tên file ngẫu nhiên)
// ====================================================================

class UploadHelper {
    public static function uploadFile($fileKey, $subDir = 'returns', $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], $maxSize = 5242880) {
        if (empty($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'Không có file được tải lên.'];
        }

        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Lỗi trong quá trình upload file (Code: ' . $file['error'] . ').'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Kích thước file vượt quá giới hạn cho phép (tối đa 5MB).'];
        }

        $tmpName = $file['tmp_name'];
        if (!is_uploaded_file($tmpName)) {
            return ['success' => false, 'error' => 'File upload không hợp lệ.'];
        }

        // Kiểm tra MIME type thực tế bằng finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes)) {
            return ['success' => false, 'error' => 'Định dạng file không được hỗ trợ (chỉ chấp nhận JPG, PNG, WEBP, PDF).'];
        }

        // Xác định phần mở rộng an toàn
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf'
        ];
        $ext = $extensions[$mime] ?? 'bin';

        $targetDir = __DIR__ . '/../assets/uploads/' . trim($subDir, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destPath = $targetDir . '/' . $fileName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $relativePath = 'assets/uploads/' . trim($subDir, '/') . '/' . $fileName;
            return ['success' => true, 'path' => $relativePath, 'filename' => $fileName];
        }

        return ['success' => false, 'error' => 'Không thể lưu file lên máy chủ.'];
    }
}
