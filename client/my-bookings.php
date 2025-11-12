<?php
/**
 * Đơn đặt của tôi (tất cả user)
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn đặt
$stmt = $conn->prepare("SELECT b.*, c.name as car_name, c.image as car_image,
    p.status as payment_status, p.payment_method
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đặt của tôi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Đơn đặt của tôi</h1>
                
                <?php if (empty($bookings)): ?>
                    <p class="text-center">Bạn chưa có đơn đặt nào. <a href="../index.php">Tìm xe ngay</a></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Xe</th>
                                <th>Ngày thuê</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td>
                                        <img src="../uploads/<?php echo htmlspecialchars($booking['car_image'] ?: 'default-car.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($booking['car_name']); ?>"
                                             style="width: 60px; height: 45px; object-fit: cover; border-radius: 5px; margin-right: 0.5rem; vertical-align: middle;">
                                        <?php echo htmlspecialchars($booking['car_name']); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> -
                                        <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                    </td>
                                    <td><?php echo number_format($booking['total_price']); ?> VNĐ</td>
                                    <td>
                                        <?php if ($booking['payment_status'] === 'completed'): ?>
                                            <span style="color: #28a745;">✓ Đã thanh toán</span>
                                            <?php if ($booking['payment_method']): ?>
                                                <br><small>(<?php echo htmlspecialchars($booking['payment_method']); ?>)</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">✗ Chưa thanh toán</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'pending' => '#ffc107',
                                            'confirmed' => '#28a745',
                                            'rejected' => '#dc3545',
                                            'completed' => '#007bff',
                                            'cancelled' => '#6c757d'
                                        ];
                                        $status_text = [
                                            'pending' => 'Chờ xác nhận',
                                            'confirmed' => 'Đã xác nhận',
                                            'rejected' => 'Đã từ chối',
                                            'completed' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        ?>
                                        <span style="color: <?php echo $status_colors[$booking['status']]; ?>; font-weight: bold;">
                                            <?php echo $status_text[$booking['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <?php if ($booking['payment_status'] !== 'completed'): ?>
                                                <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success btn-sm">Thanh toán</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['status'] === 'completed' && $booking['payment_status'] === 'completed'): ?>
                                                <a href="review.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-warning btn-sm">Đánh giá</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
