<?php
/**
 * Trang thanh toán
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$booking_id = $_GET['booking_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy thông tin booking
$stmt = $conn->prepare("SELECT b.*, c.name as car_name, c.image as car_image 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE b.id = ? AND b.customer_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my-bookings.php');
    exit();
}

$booking = $result->fetch_assoc();

// Kiểm tra đã thanh toán chưa
$stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? AND status = 'completed'");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if ($payment) {
    $already_paid = true;
} else {
    $already_paid = false;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Đơn #<?php echo $booking_id; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Thanh toán đơn đặt xe #<?php echo $booking_id; ?></h1>
                
                <?php if ($already_paid): ?>
                    <div class="alert alert-success">
                        Đơn hàng này đã được thanh toán thành công!
                    </div>
                <?php endif; ?>
                
                <div class="booking-info">
                    <h3>Thông tin đơn đặt</h3>
                    <img src="../uploads/<?php echo htmlspecialchars($booking['car_image'] ?: 'default-car.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($booking['car_name']); ?>"
                         style="max-width: 300px; border-radius: 10px; margin-bottom: 1rem;">
                    <p><strong>Xe:</strong> <?php echo htmlspecialchars($booking['car_name']); ?></p>
                    <p><strong>Ngày thuê:</strong> 
                        <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> - 
                        <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                    </p>
                    <p><strong>Số ngày:</strong> 
                        <?php 
                        $days = (strtotime($booking['end_date']) - strtotime($booking['start_date'])) / 86400;
                        echo $days;
                        ?>
                    </p>
                    <div class="total">
                        Tổng tiền: <?php echo number_format($booking['total_price']); ?> VNĐ
                    </div>
                </div>
                
                <?php if (!$already_paid): ?>
                    <h3>Chọn phương thức thanh toán</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        <form action="../api/vnpay-payment.php" method="POST">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 2rem;">
                                <h3 style="margin-bottom: 0.5rem;">VNPAY</h3>
                                <p style="margin: 0;">Thanh toán qua VNPAY (Test)</p>
                            </button>
                        </form>
                        
                        <div style="padding: 2rem; background: #f8f9fa; border-radius: 5px; text-align: center;">
                            <h3 style="margin-bottom: 0.5rem;">Stripe / PayPal</h3>
                            <p style="color: #666;">Đang phát triển...</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="actions mt-3">
                        <a href="my-bookings.php" class="btn btn-primary">Xem đơn đặt của tôi</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
