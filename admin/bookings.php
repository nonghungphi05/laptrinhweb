<?php
/**
 * Quản lý đơn đặt (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

// Lấy danh sách đơn đặt
$stmt = $conn->query("SELECT b.*, 
    c.name as car_name,
    u.full_name as customer_name, u.email as customer_email,
    p.status as payment_status
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN payments p ON b.id = p.booking_id
    ORDER BY b.created_at DESC");
$bookings = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn đặt - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <div class="dashboard-header">
                    <h1>Quản lý đơn đặt</h1>
                    <a href="dashboard.php" class="btn btn-secondary">← Quay lại Dashboard</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Xe</th>
                            <th>Ngày thuê</th>
                            <th>Tổng tiền</th>
                            <th>Thanh toán</th>
                            <th>Trạng thái đơn</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                                    <small><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['car_name']); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> -
                                    <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                </td>
                                <td><?php echo number_format($booking['total_price']); ?> VNĐ</td>
                                <td>
                                    <?php if ($booking['payment_status'] === 'completed'): ?>
                                        <span style="color: #28a745;">✓ Đã thanh toán</span>
                                    <?php elseif ($booking['payment_status'] === 'pending'): ?>
                                        <span style="color: #ffc107;">⏳ Chờ thanh toán</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ Thất bại</span>
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
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

