<?php
/**
 * Dashboard cho chủ xe - Quản lý xe của mình
 */
require_once '../config/database.php';
require_once '../config/session.php';

// Yêu cầu đăng nhập và là host
requireRole('host');

$user_id = $_SESSION['user_id'];

// Lấy danh sách xe của chủ xe
$stmt = $conn->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id) as total_bookings,
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id AND status = 'pending') as pending_bookings,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating
    FROM cars c WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);

// Lấy thống kê
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_cars = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.owner_id = ? AND b.status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_bookings = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(b.total_price), 0) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.owner_id = ? AND b.status IN ('confirmed', 'completed')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chủ xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <div class="dashboard-header">
                    <h1>Quản lý xe của bạn</h1>
                    <a href="add-car.php" class="btn btn-primary">+ Thêm xe mới</a>
                </div>
                
                <!-- Thống kê -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Tổng số xe</h3>
                        <div class="number"><?php echo $total_cars; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Tổng đơn đặt</h3>
                        <div class="number"><?php echo $total_bookings; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Đơn chờ xử lý</h3>
                        <div class="number"><?php echo $pending_bookings; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Doanh thu</h3>
                        <div class="number"><?php echo number_format($total_revenue); ?> VNĐ</div>
                    </div>
                </div>
                
                <!-- Danh sách xe -->
                <h2>Danh sách xe</h2>
                <?php if (empty($cars)): ?>
                    <p class="text-center">Bạn chưa có xe nào. <a href="add-car.php">Thêm xe ngay</a></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Tên xe</th>
                                <th>Loại</th>
                                <th>Giá/ngày</th>
                                <th>Trạng thái</th>
                                <th>Đánh giá</th>
                                <th>Đơn đặt</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cars as $car): ?>
                                <tr>
                                    <td>
                                        <img src="../uploads/<?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($car['name']); ?>"
                                             style="width: 80px; height: 60px; object-fit: cover; border-radius: 5px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($car['name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></td>
                                    <td><?php echo number_format($car['price_per_day']); ?> VNĐ</td>
                                    <td>
                                        <span class="status status-<?php echo $car['status']; ?>">
                                            <?php 
                                            $status_text = [
                                                'available' => 'Còn xe',
                                                'rented' => 'Đang cho thuê',
                                                'maintenance' => 'Bảo trì'
                                            ];
                                            echo $status_text[$car['status']] ?? $car['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($car['avg_rating']): ?>
                                            <?php echo number_format($car['avg_rating'], 1); ?> ⭐
                                        <?php else: ?>
                                            Chưa có
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $car['total_bookings']; ?>
                                        <?php if ($car['pending_bookings'] > 0): ?>
                                            <span style="color: #dc3545;">(<?php echo $car['pending_bookings']; ?> chờ)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit-car.php?id=<?php echo $car['id']; ?>" class="btn btn-warning btn-sm">Sửa</a>
                                            <a href="car-bookings.php?car_id=<?php echo $car['id']; ?>" class="btn btn-secondary btn-sm">Đơn đặt</a>
                                            <a href="delete-car.php?id=<?php echo $car['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Bạn có chắc muốn xóa xe này?')">Xóa</a>
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


