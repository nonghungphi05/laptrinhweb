<?php
/**
 * Quản lý đơn đặt xe (Chủ xe)
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('host');

$car_id = $_GET['car_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Kiểm tra xe thuộc sở hữu của user
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $car_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$car = $result->fetch_assoc();

// Xử lý xác nhận/từ chối đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    if ($action === 'confirm') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND car_id = ?");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ? AND car_id = ?");
    }
    
    if (isset($stmt)) {
        $stmt->bind_param("ii", $booking_id, $car_id);
        $stmt->execute();
    }
}

// Lấy danh sách đơn đặt
$stmt = $conn->prepare("SELECT b.*, u.full_name, u.email, u.phone,
    p.status as payment_status
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.car_id = ?
    ORDER BY b.created_at DESC");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đặt xe - Chủ xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Đơn đặt xe: <?php echo htmlspecialchars($car['name']); ?></h1>
                <a href="dashboard.php" class="btn btn-secondary mb-2">Quay lại dashboard</a>
                
                <?php if (empty($bookings)): ?>
                    <p class="text-center">Chưa có đơn đặt nào.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Liên hệ</th>
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
                                    <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['email']); ?><br>
                                        <?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> -
                                        <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                    </td>
                                    <td><?php echo number_format($booking['total_price']); ?> VNĐ</td>
                                    <td>
                                        <?php if ($booking['payment_status'] === 'completed'): ?>
                                            <span style="color: #28a745;">✓ Đã thanh toán</span>
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
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <div class="actions">
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="action" value="confirm">
                                                    <button type="submit" class="btn btn-success btn-sm">Xác nhận</button>
                                                </form>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger btn-sm">Từ chối</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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


