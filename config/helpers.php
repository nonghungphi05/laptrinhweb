<?php
/**
 * Các hàm helper tiện ích
 */

/**
 * Format tiền VNĐ
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

/**
 * Format ngày giờ
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Tính số ngày giữa 2 ngày
 */
function calculateDays($start_date, $end_date) {
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    return ceil(($end - $start) / 86400);
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Kiểm tra email hợp lệ
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Upload file
 */
function uploadFile($file, $destination_dir = '../uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Kiểm tra kích thước file (tối đa 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    // Tạo thư mục nếu chưa có
    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }
    
    // Tạo tên file mới với timestamp để tránh trùng
    $new_filename = uniqid() . '_' . time() . '.' . $ext;
    $upload_path = $destination_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $new_filename;
    }
    
    return false;
}

/**
 * Redirect
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Get và xóa flash message
 */
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)))), 1, $length);
}

/**
 * Pagination helper
 */
function getPagination($total_items, $items_per_page, $current_page) {
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $items_per_page
    ];
}

?>
