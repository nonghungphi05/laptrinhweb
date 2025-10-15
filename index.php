<?php
/**
 * Trang chủ - Hiển thị danh sách xe và tìm kiếm
 */
require_once 'config/database.php';
require_once 'config/session.php';

// Xử lý tìm kiếm và filter
$search = $_GET['search'] ?? '';
$car_type = $_GET['car_type'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Build query
$sql = "SELECT c.*, u.full_name as owner_name, 
        (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE car_id = c.id) as review_count
        FROM cars c 
        JOIN users u ON c.owner_id = u.id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($car_type)) {
    $sql .= " AND c.car_type = ?";
    $params[] = $car_type;
    $types .= "s";
}

if (!empty($min_price)) {
    $sql .= " AND c.price_per_day >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if (!empty($max_price)) {
    $sql .= " AND c.price_per_day <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Thuê Xe Tự Lái Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>Thuê Xe Tự Lái Online</h2>
            <p>Hàng trăm mẫu xe chất lượng - Giá cả hợp lý - Thủ tục đơn giản</p>
        </div>
    </section>
    
    <!-- Search Box -->
    <div class="container">
        <div class="search-box">
            <form method="GET" action="" class="search-form">
                <div class="form-group">
                    <label for="search">Tìm kiếm xe</label>
                    <input type="text" id="search" name="search" 
                           placeholder="Tên xe hoặc mô tả..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label for="car_type">Loại xe</label>
                    <select id="car_type" name="car_type">
                        <option value="">Tất cả</option>
                        <option value="sedan" <?php echo $car_type === 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                        <option value="suv" <?php echo $car_type === 'suv' ? 'selected' : ''; ?>>SUV</option>
                        <option value="mpv" <?php echo $car_type === 'mpv' ? 'selected' : ''; ?>>MPV</option>
                        <option value="pickup" <?php echo $car_type === 'pickup' ? 'selected' : ''; ?>>Bán tải</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="min_price">Giá tối thiểu (VNĐ)</label>
                    <input type="number" id="min_price" name="min_price" 
                           placeholder="0"
                           value="<?php echo htmlspecialchars($min_price); ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_price">Giá tối đa (VNĐ)</label>
                    <input type="number" id="max_price" name="max_price" 
                           placeholder="10000000"
                           value="<?php echo htmlspecialchars($max_price); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            </form>
        </div>
    </div>
    
    <!-- Main Content -->
    <main>
        <div class="container">
            <h2>Danh sách xe cho thuê</h2>
            
            <?php if (empty($cars)): ?>
                <p class="text-center">Không tìm thấy xe nào phù hợp.</p>
            <?php else: ?>
                <div class="car-grid">
                    <?php foreach ($cars as $car): ?>
                        <div class="car-card">
                            <img src="uploads/<?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($car['name']); ?>">
                            <div class="car-card-body">
                                <h3><?php echo htmlspecialchars($car['name']); ?></h3>
                                <div>
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
                                <p class="description"><?php echo htmlspecialchars(substr($car['description'], 0, 100)) . '...'; ?></p>
                                <div class="price"><?php echo number_format($car['price_per_day']); ?> VNĐ/ngày</div>
                                <?php if ($car['avg_rating']): ?>
                                    <div class="rating">
                                        <?php 
                                        $rating = round($car['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <span class="star <?php echo $i <= $rating ? 'active' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                        <span>(<?php echo $car['review_count']; ?> đánh giá)</span>
                                    </div>
                                <?php endif; ?>
                                <div class="actions mt-2">
                                    <a href="/webthuexe/client/car-detail.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                    <?php if (isLoggedIn() && hasRole('customer') && $car['status'] === 'available'): ?>
                                        <a href="/webthuexe/client/booking.php?car_id=<?php echo $car['id']; ?>" class="btn btn-success btn-sm">Đặt xe</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>


