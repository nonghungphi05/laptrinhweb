<?php
/**
 * Quản lý xe (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('admin');

// Xử lý xóa xe
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = intval($_GET['delete']);
    
    // Lấy thông tin xe để xóa ảnh
    $stmt = $conn->prepare("SELECT image FROM cars WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $car = $result->fetch_assoc();
        if ($car['image'] && file_exists('../uploads/' . $car['image'])) {
            unlink('../uploads/' . $car['image']);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    
    header('Location: cars.php');
    exit();
}

// Lấy danh sách xe
$stmt = $conn->query("SELECT c.*, u.full_name as owner_name,
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id) as total_bookings,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating
    FROM cars c
    JOIN users u ON c.owner_id = u.id
    ORDER BY c.created_at DESC");
$cars = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý xe - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <div class="dashboard-header">
                    <h1>Quản lý xe</h1>
                    <a href="dashboard.php" class="btn btn-secondary">← Quay lại Dashboard</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên xe</th>
                            <th>Chủ xe</th>
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
                                <td><?php echo $car['id']; ?></td>
                                <td>
                                    <img src="../uploads/<?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($car['name']); ?>"
                                         style="width: 60px; height: 45px; object-fit: cover; border-radius: 5px;">
                                </td>
                                <td><?php echo htmlspecialchars($car['name']); ?></td>
                                <td><?php echo htmlspecialchars($car['owner_name']); ?></td>
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
                                <td><?php echo $car['total_bookings']; ?></td>
                                <td>
                                    <a href="?delete=<?php echo $car['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Bạn có chắc muốn xóa xe này? Tất cả dữ liệu liên quan sẽ bị xóa.')">Xóa</a>
                                </td>
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

