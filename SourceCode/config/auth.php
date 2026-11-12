<?php
// ====================================================================
// KIBO PLATFORM - Authentication & Authorization Helpers
// ====================================================================

function startSecureSession() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
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
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $_SESSION['csrf_token'] = md5(uniqid('kibo_csrf_', true));
        }
    }
}

function getCsrfToken(): string {
    startSecureSession();
    return $_SESSION['csrf_token'] ?? '';
}

function csrfField(): string {
    $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCsrfToken($inputArray = []): bool {
    if (PHP_SAPI === 'cli') {
        return true;
    }
    startSecureSession();
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) {
        return false;
    }
    $clientToken = $_POST['csrf_token'] 
        ?? ($inputArray['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (empty($clientToken)) {
        return false;
    }
    return hash_equals($sessionToken, (string)$clientToken);
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
