<?php
/**
 * Return URL từ VNPay:
 * - Xác thực chữ ký
 * - Cập nhật bảng payments & bookings
 * - Redirect về trang thanh toán của đơn trong app
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once './config.php';

// Gom các tham số vnp_ từ query string
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData      = [];

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) === 'vnp_') {
        $inputData[$key] = $value;
    }
}

if (empty($inputData)) {
    header('Location: ../client/my-bookings.php');
    exit();
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);

$i        = 0;
$hashData = '';
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
    } else {
        $hashData .= urlencode($key) . '=' . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

$booking_id        = (int)($inputData['vnp_TxnRef'] ?? 0);
$vnp_Amount        = isset($inputData['vnp_Amount']) ? ((int)$inputData['vnp_Amount'] / 100) : 0;
$vnp_ResponseCode  = $inputData['vnp_ResponseCode'] ?? '';
$vnp_TransactionNo = $inputData['vnp_TransactionNo'] ?? '';

// Mặc định là thất bại
$payment_status = 'failed';

if ($secureHash === $vnp_SecureHash && $vnp_ResponseCode === '00' && $booking_id > 0) {
    // Lấy thông tin booking để đối chiếu số tiền
    $stmt = $conn->prepare("
        SELECT id, total_price
        FROM bookings
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking && (float)$booking['total_price'] == (float)$vnp_Amount) {
        // Ghi nhận thanh toán VNPay
        $insert = $conn->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status)
            VALUES (?, ?, 'VNPAY', ?, 'completed')
        ");
        $insert->bind_param('ids', $booking_id, $vnp_Amount, $vnp_TransactionNo);
        $insert->execute();

        // Cập nhật trạng thái đơn đặt
        $upd = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
        $upd->bind_param('i', $booking_id);
        $upd->execute();

        $payment_status = 'completed';
    }
}

// Redirect về trang thanh toán của đơn, UI sẽ đọc trạng thái từ DB
$base_path = getBasePath();
$redirect  = $base_path . '/client/payment.php?booking_id=' . $booking_id;

header('Location: ' . $redirect);
exit();


