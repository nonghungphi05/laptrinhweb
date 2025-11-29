<?php
/**
 * Tạo URL thanh toán VNPay cho đơn đặt xe (booking)
 * Được gọi từ: client/payment.php (form POST booking_id)
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once './config.php';

requireLogin();

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$booking_id = (int)($_POST['booking_id'] ?? 0);

if (!$booking_id || !$user_id) {
    header('Location: ../client/my-bookings.php');
    exit();
}

// Lấy thông tin booking để lấy số tiền & kiểm tra quyền sở hữu
$stmt = $conn->prepare("
    SELECT b.id, b.customer_id, b.total_price, b.status
    FROM bookings b
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$result  = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: ../client/my-bookings.php');
    exit();
}

// Nếu đã có thanh toán completed thì không cho thanh toán lại
$pay_stmt = $conn->prepare("
    SELECT status FROM payments
    WHERE booking_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$pay_stmt->bind_param('i', $booking_id);
$pay_stmt->execute();
$payment = $pay_stmt->get_result()->fetch_assoc();

if (!empty($payment) && $payment['status'] === 'completed') {
    header('Location: ../client/payment.php?booking_id=' . $booking_id);
    exit();
}

$amount = (float)$booking['total_price'];
if ($amount <= 0) {
    header('Location: ../client/payment.php?booking_id=' . $booking_id);
    exit();
}

// Các tham số gửi sang VNPay
// VNPay yêu cầu mỗi giao dịch có mã tham chiếu duy nhất.
// Gắn thêm timestamp để tránh bị báo "giao dịch đã tồn tại"
$vnp_TxnRef    = $booking_id . '-' . time();
$vnp_Amount    = $amount * 100;
$vnp_IpAddr    = $_SERVER['REMOTE_ADDR'];
$vnp_Locale    = 'vn';
$vnp_OrderInfo = 'Thanh toan don dat xe #' . $booking_id;

$inputData = array(
    'vnp_Version'    => '2.1.0',
    'vnp_TmnCode'    => $vnp_TmnCode,
    'vnp_Amount'     => (int)$vnp_Amount,
    'vnp_Command'    => 'pay',
    'vnp_CreateDate' => date('YmdHis'),
    'vnp_CurrCode'   => 'VND',
    'vnp_IpAddr'     => $vnp_IpAddr,
    'vnp_Locale'     => $vnp_Locale,
    'vnp_OrderInfo'  => $vnp_OrderInfo,
    'vnp_OrderType'  => 'other',
    'vnp_ReturnUrl'  => $vnp_Returnurl,
    'vnp_TxnRef'     => $vnp_TxnRef,
    'vnp_ExpireDate' => $expire
);

// Sắp xếp tham số và tạo chuỗi hash/query
ksort($inputData);
$hashData = '';
$query    = '';
$i        = 0;

foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
    } else {
        $hashData .= urlencode($key) . '=' . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . '=' . urlencode($value) . '&';
}

$vnpUrl = $vnp_Url . '?' . $query;

if (!empty($vnp_HashSecret)) {
    $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
    $vnpUrl       .= 'vnp_SecureHash=' . $vnpSecureHash;
}

// Chuyển hướng sang VNPay
header('Location: ' . $vnpUrl);
exit();


