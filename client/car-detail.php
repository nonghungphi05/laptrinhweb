<?php
/**
 * Chi tiết xe và đánh giá
 */
require_once '../config/database.php';
require_once '../config/session.php';

$car_id = $_GET['id'] ?? 0;

// Lấy thông tin xe
$stmt = $conn->prepare("SELECT c.*, u.full_name as owner_name, u.phone as owner_phone,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE car_id = c.id) as review_count
    FROM cars c 
    JOIN users u ON c.owner_id = u.id 
    WHERE c.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php');
    exit();
}

$car = $result->fetch_assoc();

// Lấy đánh giá
$stmt = $conn->prepare("SELECT r.*, u.full_name 
    FROM reviews r 
    JOIN users u ON r.customer_id = u.id 
    WHERE r.car_id = ? 
    ORDER BY r.created_at DESC");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['name']); ?> - Chi tiết xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <a href="../index.php" class="btn btn-secondary mb-2">← Quay lại</a>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <img src="../uploads/<?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($car['name']); ?>"
                             style="width: 100%; border-radius: 10px;">
                    </div>
                    <div>
                        <h1><?php echo htmlspecialchars($car['name']); ?></h1>
                        <div class="mb-2">
                            <span class="type"><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></span>
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
                        </div>
                        
                        <?php if ($car['avg_rating']): ?>
                            <div class="rating mb-2">
                                <?php 
                                $rating = round($car['avg_rating']);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <span class="star <?php echo $i <= $rating ? 'active' : ''; ?>">★</span>
                                <?php endfor; ?>
                                <span><?php echo number_format($car['avg_rating'], 1); ?> (<?php echo $car['review_count']; ?> đánh giá)</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="price mb-2"><?php echo number_format($car['price_per_day']); ?> VNĐ/ngày</div>
                        
                        <p><strong>Chủ xe:</strong> <?php echo htmlspecialchars($car['owner_name']); ?></p>
                        <?php if ($car['owner_phone']): ?>
                            <p><strong>Liên hệ:</strong> <?php echo htmlspecialchars($car['owner_phone']); ?></p>
                        <?php endif; ?>
                        
                        <h3>Mô tả</h3>
                        <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                        
                        <?php if (isLoggedIn() && hasRole('customer') && $car['status'] === 'available'): ?>
                            <a href="booking.php?car_id=<?php echo $car['id']; ?>" class="btn btn-primary mt-2">Đặt xe ngay</a>
                        <?php elseif (!isLoggedIn()): ?>
                            <p class="mt-2"><a href="../auth/login.php">Đăng nhập</a> để đặt xe</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Đánh giá -->
                <h2>Đánh giá từ khách hàng</h2>
                <?php if (empty($reviews)): ?>
                    <p>Chưa có đánh giá nào.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong><?php echo htmlspecialchars($review['full_name']); ?></strong>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <small style="color: #666;"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>


