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
 * Yêu cầu đăng nhập
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /webthuexe/auth/login.php');
        exit();
    }
}

/**
 * Yêu cầu role cụ thể
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /webthuexe/index.php');
        exit();
    }
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


