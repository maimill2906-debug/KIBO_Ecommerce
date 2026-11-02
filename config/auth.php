<?php
// ====================================================================
// KIBO PLATFORM - Authentication & Authorization Helpers
// ====================================================================

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        @session_start();
    }
}

function requireAdmin() {
    startSecureSession();
    if (empty($_SESSION['user']) || ($_SESSION['user']['VaiTro'] ?? '') !== 'Admin') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Truy cập bị từ chối: Yêu cầu quyền quản trị viên (Admin).'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
