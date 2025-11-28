<?php
/**
 * Các hằng số và cấu hình chung
 */

// Base URL (cập nhật theo domain thực tế)
define('BASE_URL', 'http://localhost/webthuexe');

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination
define('ITEMS_PER_PAGE', 12);

// Car types
define('CAR_TYPES', [
    'sedan' => 'Sedan',
    'suv' => 'SUV',
    'mpv' => 'MPV',
    'pickup' => 'Bán tải'
]);

// Booking status
define('BOOKING_STATUS', [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'rejected' => 'Đã từ chối',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
]);

// Payment status
define('PAYMENT_STATUS', [
    'pending' => 'Chờ thanh toán',
    'completed' => 'Đã thanh toán',
    'failed' => 'Thất bại',
    'refunded' => 'Đã hoàn tiền',
    'cancelled' => 'Đã hủy'
]);

// User roles
define('USER_ROLES', [
    'customer' => 'Khách hàng',
    'host' => 'Chủ xe',
    'admin' => 'Quản trị viên'
]);

// VNPAY Config (Sandbox)
define('VNPAY_TMN_CODE', 'E1W34YKD');
define('VNPAY_HASH_SECRET', 'PNJVXMCKPHW7F3SZQ3GTA338WL8CZ0IG');
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', BASE_URL . '/api/vnpay_return.php');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

?>
