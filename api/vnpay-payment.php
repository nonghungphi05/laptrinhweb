<?php
/**
 * Tích hợp VNPAY Payment Gateway
 * Tạo link thanh toán và chuyển hướng
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$booking_id = $_POST['booking_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy thông tin booking
$stmt = $conn->prepare("SELECT b.*, c.name as car_name 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE b.id = ? AND b.customer_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Booking không hợp lệ');
}

$booking = $result->fetch_assoc();

// Cấu hình VNPAY (Sandbox Test)
$vnp_TmnCode = "DEMO"; // Mã website tại VNPAY (dùng DEMO cho test)
$vnp_HashSecret = "DEMOSECRETKEY"; // Chuỗi bí mật (dùng DEMO cho test)
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "http://localhost/webthuexe/api/vnpay-return.php";

// Dữ liệu gửi sang VNPAY
$vnp_TxnRef = $booking_id . '_' . time(); // Mã đơn hàng
$vnp_OrderInfo = "Thanh toan thue xe: " . $booking['car_name'];
$vnp_OrderType = 'billpayment';
$vnp_Amount = $booking['total_price'] * 100; // VNPAY yêu cầu nhân 100
$vnp_Locale = 'vn';
$vnp_BankCode = '';
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

// Tạo mảng dữ liệu
$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef,
);

if (isset($vnp_BankCode) && $vnp_BankCode != "") {
    $inputData['vnp_BankCode'] = $vnp_BankCode;
}

// Sắp xếp dữ liệu theo thứ tự alphabet
ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";

foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

$vnp_Url = $vnp_Url . "?" . $query;

// Tạo secure hash
if (isset($vnp_HashSecret)) {
    $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
}

// Lưu payment record vào database (pending)
$stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) VALUES (?, ?, 'VNPAY', ?, 'pending')");
$stmt->bind_param("ids", $booking_id, $booking['total_price'], $vnp_TxnRef);
$stmt->execute();

// Chuyển hướng sang VNPAY
header('Location: ' . $vnp_Url);
exit();
?>
