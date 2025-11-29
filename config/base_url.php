<?php
/**
 * Định nghĩa BASE URL cho website
 * Thay đổi đường dẫn này nếu website chạy ở thư mục khác
 */

// Tự động phát hiện base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);

// Nếu chạy ở localhost/laptrinhweb
define('BASE_URL', '/laptrinhweb');
define('FULL_BASE_URL', $protocol . $host . BASE_URL);

/**
 * Hàm helper để tạo URL
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}
?>

