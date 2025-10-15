<?php
/**
 * Xóa xe (Chủ xe)
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('host');

$car_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy thông tin xe và kiểm tra quyền sở hữu
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $car_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$car = $result->fetch_assoc();

// Xóa ảnh nếu có
if ($car['image'] && file_exists('../uploads/' . $car['image'])) {
    unlink('../uploads/' . $car['image']);
}

// Xóa xe (cascade sẽ xóa bookings, payments, reviews liên quan)
$stmt = $conn->prepare("DELETE FROM cars WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $car_id, $user_id);
$stmt->execute();

header('Location: dashboard.php');
exit();
?>


