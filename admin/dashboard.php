<?php
/**
 * Dashboard Admin - Quản trị tổng thể
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

// Lấy thống kê tổng quan
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM cars");
$total_cars = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE status IN ('confirmed', 'completed')");
$total_revenue = $stmt->fetch_assoc()['total'];

// Đơn đặt mới nhất
$stmt = $conn->query("SELECT b.*, c.name as car_name, u.full_name as customer_name
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.customer_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 10");
$recent_bookings = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Admin Dashboard</h1>
                
                <!-- Thống kê -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Tổng người dùng</h3>
                        <div class="number"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Tổng xe</h3>
                        <div class="number"><?php echo $total_cars; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Tổng đơn đặt</h3>
                        <div class="number"><?php echo $total_bookings; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Doanh thu</h3>
                        <div class="number"><?php echo number_format($total_revenue); ?> VNĐ</div>
                    </div>
                </div>
                
                <!-- Menu quản lý -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0;">
                    <a href="users.php" class="btn btn-primary">Quản lý người dùng</a>
                    <a href="cars.php" class="btn btn-primary">Quản lý xe</a>
                    <a href="bookings.php" class="btn btn-primary">Quản lý đơn đặt</a>
                </div>
                
                <!-- Đơn đặt gần đây -->
                <h2>Đơn đặt gần đây</h2>
                <?php if (empty($recent_bookings)): ?>
                    <p>Chưa có đơn đặt nào.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Xe</th>
                                <th>Ngày thuê</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['car_name']); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> -
                                        <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                    </td>
                                    <td><?php echo number_format($booking['total_price']); ?> VNĐ</td>
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

