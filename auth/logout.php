<?php
/**
 * Đăng xuất
 */
require_once '../config/session.php';

// Xóa session và chuyển về trang chủ
logout();
header('Location: /webthuexe/index.php');
exit();
?>


