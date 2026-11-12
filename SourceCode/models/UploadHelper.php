<?php
// ====================================================================
// HELPER: UploadHelper.php
// Tải lên file an toàn (kiểm tra MIME type, kích thước, tên file ngẫu nhiên)
// ====================================================================

class UploadHelper {
    public static function uploadFile($fileKey, $subDir = 'returns', $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], $maxSize = 5242880) {
        if (empty($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            $err = 'Không có file được tải lên.';
            return ['success' => false, 'error' => $err, 'message' => $err];
        }

        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $err = 'Lỗi trong quá trình upload file (Code: ' . $file['error'] . ').';
            return ['success' => false, 'error' => $err, 'message' => $err];
        }

        if ($file['size'] > $maxSize) {
            $err = 'Kích thước file vượt quá giới hạn cho phép (tối đa 5MB).';
            return ['success' => false, 'error' => $err, 'message' => $err];
        }

        $tmpName = $file['tmp_name'];
        if (!is_uploaded_file($tmpName)) {
            $err = 'File upload không hợp lệ.';
            return ['success' => false, 'error' => $err, 'message' => $err];
        }

        // Kiểm tra MIME type thực tế bằng finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes)) {
            $err = 'Định dạng file không được hỗ trợ (chỉ chấp nhận JPG, PNG, WEBP, PDF).';
            return ['success' => false, 'error' => $err, 'message' => $err];
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

        $err = 'Không thể lưu file lên máy chủ.';
        return ['success' => false, 'error' => $err, 'message' => $err];
    }
}
