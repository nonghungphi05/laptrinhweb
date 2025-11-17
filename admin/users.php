<?php
/**
 * Quản lý người dùng (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

// Xử lý xóa user
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = intval($_GET['delete']);
    
    // Không cho phép xóa chính mình
    if ($delete_id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
    }
    
    header('Location: users.php');
    exit();
}

// Lấy danh sách người dùng + số liệu tài khoản
$user_query = "
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM cars WHERE owner_id = u.id) AS total_cars,
        (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id) AS total_bookings,
        (SELECT COUNT(*) FROM user_addresses WHERE user_id = u.id) AS total_addresses,
        (SELECT COUNT(*) FROM user_notifications WHERE user_id = u.id AND is_read = 0) AS unread_notifications,
        (SELECT COUNT(*) FROM payments p INNER JOIN bookings b ON p.booking_id = b.id WHERE b.customer_id = u.id) AS total_payments,
        (SELECT IFNULL(SUM(p.amount), 0) FROM payments p INNER JOIN bookings b ON p.booking_id = b.id WHERE b.customer_id = u.id AND p.status = 'completed') AS total_paid_amount
    FROM users u
    ORDER BY u.created_at DESC
";

$stmt = $conn->query($user_query);
$users = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <div class="dashboard-header">
                    <h1>Quản lý người dùng</h1>
                    <a href="dashboard.php" class="btn btn-secondary">← Quay lại Dashboard</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Email</th>
                            <th>Họ tên</th>
                            <th>Số điện thoại</th>
                            <th>Vai trò</th>
                            <th>Xe</th>
                            <th>Đơn đặt</th>
                            <th>Địa chỉ</th>
                            <th>Thông báo chưa đọc</th>
                            <th>Thanh toán</th>
                            <th>Đã thanh toán</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $role_colors = [
                                        'admin' => '#dc3545',
                                        'user' => '#0d6efd'
                                    ];
                                    $role_text = [
                                        'admin' => 'Admin',
                                        'user' => 'Người dùng'
                                    ];
                                    $role = $user['role'] ?? 'user';
                                    ?>
                                    <span style="color: <?php echo $role_colors[$role] ?? '#6c757d'; ?>; font-weight: bold;">
                                        <?php echo $role_text[$role] ?? '---'; ?>
                                    </span>
                                </td>
                                <td><?php echo $user['total_cars']; ?></td>
                                <td><?php echo $user['total_bookings']; ?></td>
                                <td><?php echo $user['total_addresses']; ?></td>
                                <td><?php echo $user['unread_notifications']; ?></td>
                                <td><?php echo $user['total_payments']; ?></td>
                                <td><?php echo formatCurrency($user['total_paid_amount']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Bạn có chắc muốn xóa user này? Tất cả dữ liệu liên quan sẽ bị xóa.')">Xóa</a>
                                    <?php else: ?>
                                        <em>Bạn</em>
                                    <?php endif; ?>
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

