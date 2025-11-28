<?php
/* Payment Notify (IPN)
 * URL này được VNPay gọi nền để xác nhận kết quả thanh toán.
 * - Kiểm tra checksum
 * - Đối chiếu số tiền
 * - Cập nhật bookings & payments
 * - Trả JSON để VNPay biết đã ghi nhận hay chưa
 */

require_once '../config/database.php';
require_once './config.php';

$inputData  = [];
$returnData = [];

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) === 'vnp_') {
        $inputData[$key] = $value;
    }
}

if (empty($inputData)) {
    $returnData['RspCode'] = '99';
    $returnData['Message'] = 'No input data';
    echo json_encode($returnData);
    exit();
}

$vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
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

$secureHash            = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$vnpTranId             = $inputData['vnp_TransactionNo'] ?? '';
$vnp_Amount            = isset($inputData['vnp_Amount']) ? ((int)$inputData['vnp_Amount'] / 100) : 0;
$vnp_ResponseCode      = $inputData['vnp_ResponseCode'] ?? '';
$vnp_TransactionStatus = $inputData['vnp_TransactionStatus'] ?? '';
$vnp_TxnRef            = $inputData['vnp_TxnRef'] ?? '';
$orderId               = 0;
if ($vnp_TxnRef !== '') {
    $parts = explode('-', $vnp_TxnRef, 2);
    $orderId = (int)$parts[0];
}
if ($orderId === 0 && $vnp_TxnRef !== '') {
    $orderId = (int)$vnp_TxnRef;
}

try {
    if ($secureHash !== $vnp_SecureHash) {
        $returnData['RspCode'] = '97';
        $returnData['Message'] = 'Invalid signature';
    } else {
        // Lấy thông tin booking
        $stmt = $conn->prepare("
            SELECT id, total_price, status
            FROM bookings
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        if (!$booking) {
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
        } elseif ((float)$booking['total_price'] != (float)$vnp_Amount) {
            $returnData['RspCode'] = '04';
            $returnData['Message'] = 'Invalid amount';
        } else {
            if ($booking['status'] === 'pending') {
                if ($vnp_ResponseCode === '00' && $vnp_TransactionStatus === '00') {
                    // Ghi nhận thanh toán
                    $insert = $conn->prepare("
                        INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status)
                        VALUES (?, ?, 'VNPAY', ?, 'completed')
                    ");
                    $insert->bind_param('ids', $orderId, $vnp_Amount, $vnpTranId);
                    $insert->execute();

                    $upd = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
                    $upd->bind_param('i', $orderId);
                    $upd->execute();

                    $returnData['RspCode'] = '00';
                    $returnData['Message'] = 'Confirm Success';
                } else {
                    $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                    $upd->bind_param('i', $orderId);
                    $upd->execute();

                    $returnData['RspCode'] = '00';
                    $returnData['Message'] = 'Payment Failed';
                }
            } else {
                $returnData['RspCode'] = '02';
                $returnData['Message'] = 'Order already confirmed';
            }
        }
    }
} catch (Exception $e) {
    $returnData['RspCode'] = '99';
    $returnData['Message'] = 'Unknown error';
}

echo json_encode($returnData);


