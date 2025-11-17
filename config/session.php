<?php
/**
 * File quản lý session
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra user đã đăng nhập chưa
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Kiểm tra role của user
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Kiểm tra user có một trong các role được chỉ định
 */
function hasAnyRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

/**
 * Lấy base path tự động
 */
function getBasePath() {
    static $base_path = null;
    if ($base_path !== null) {
        return $base_path;
    }
    
    $script_name = $_SERVER['SCRIPT_NAME'];
    $script_dir = dirname($script_name);
    
    // Loại bỏ các thư mục con (auth, client, cars, admin, etc.)
    $parts = explode('/', trim($script_dir, '/'));
    
    // Tìm các thư mục con của project
    $subdirs = ['auth', 'cars', 'admin', 'client', 'host', 'api', 'config'];
    foreach ($subdirs as $subdir) {
        $pos = array_search($subdir, $parts);
        if ($pos !== false && $pos > 0) {
            $base_parts = array_slice($parts, 0, $pos);
            $base_path = '/' . implode('/', $base_parts);
            return $base_path;
        }
    }
    
    // Nếu không tìm thấy, lấy thư mục đầu tiên
    if (!empty($parts)) {
        $base_path = '/' . $parts[0];
    } else {
        $base_path = '';
    }
    
    return $base_path;
}

/**
 * Yêu cầu đăng nhập
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $base_path = getBasePath();
        $current_url = $_SERVER['REQUEST_URI'];
        header('Location: ' . $base_path . '/auth/login.php?redirect=' . urlencode($current_url));
        exit();
    }
}

/**
 * Yêu cầu role cụ thể
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        $base_path = getBasePath();
        header('Location: ' . $base_path . '/index.php');
        exit();
    }
}

/**
 * Yêu cầu là admin
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Lấy thông tin user hiện tại
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}

/**
 * Đăng xuất
 */
function logout() {
    session_unset();
    session_destroy();
}

?>


