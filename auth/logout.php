<?php
/**
 * Đăng xuất
 */
require_once '../config/session.php';

// Lấy base path
$base_path = getBasePath();

// Xóa session và chuyển về trang chủ
logout();
header('Location: ' . $base_path . '/index.php');
exit();
?>


