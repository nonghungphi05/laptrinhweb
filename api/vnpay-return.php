<?php
/**
 * Xử lý kết quả trả về từ VNPAY
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$vnp_HashSecret = "DEMOSECRETKEY"; // Chuỗi bí mật (phải giống với lúc gửi)

// Lấy dữ liệu trả về
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);

$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// Kiểm tra checksum
$success = false;
$message = '';

if ($secureHash == $vnp_SecureHash) {
    // Dữ liệu hợp lệ
    if ($_GET['vnp_ResponseCode'] == '00') {
        // Thanh toán thành công
        $vnp_TxnRef = $_GET['vnp_TxnRef'];
        $vnp_Amount = $_GET['vnp_Amount'] / 100; // Chia 100 vì VNPAY nhân 100
        
        // Lấy booking_id từ transaction reference
        $booking_id = intval(explode('_', $vnp_TxnRef)[0]);
        
        // Cập nhật trạng thái payment
        $stmt = $conn->prepare("UPDATE payments SET status = 'completed' WHERE transaction_id = ?");
        $stmt->bind_param("s", $vnp_TxnRef);
        $stmt->execute();
        
        // Cập nhật trạng thái booking nếu đang pending
        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        $success = true;
        $message = 'Thanh toán thành công! Đơn đặt xe của bạn đã được xác nhận.';
    } else {
        // Thanh toán thất bại
        $message = 'Thanh toán thất bại. Mã lỗi: ' . $_GET['vnp_ResponseCode'];
    }
} else {
    $message = 'Chữ ký không hợp lệ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Kết quả thanh toán</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h2 style="margin-bottom: 1rem;">✓ <?php echo $message; ?></h2>
                        <p>Mã giao dịch: <?php echo htmlspecialchars($_GET['vnp_TxnRef'] ?? 'N/A'); ?></p>
                        <p>Số tiền: <?php echo number_format($vnp_Amount ?? 0); ?> VNĐ</p>
                        <p>Thời gian: <?php echo isset($_GET['vnp_PayDate']) ? date('d/m/Y H:i:s', strtotime($_GET['vnp_PayDate'])) : 'N/A'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <h2 style="margin-bottom: 1rem;">✗ <?php echo $message; ?></h2>
                    </div>
                <?php endif; ?>
                
                <div class="actions mt-3">
                    <a href="../client/my-bookings.php" class="btn btn-primary">Xem đơn đặt của tôi</a>
                    <a href="../index.php" class="btn btn-secondary">Về trang chủ</a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
