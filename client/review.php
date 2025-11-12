<?php
/**
 * Đánh giá xe sau khi thuê
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$booking_id = $_GET['booking_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy thông tin booking
$stmt = $conn->prepare("SELECT b.*, c.id as car_id, c.name as car_name, c.image as car_image 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my-bookings.php');
    exit();
}

$booking = $result->fetch_assoc();

// Kiểm tra đã đánh giá chưa
$stmt = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$existing_review = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

// Xử lý đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Vui lòng chọn số sao từ 1-5';
    } else {
        if ($existing_review) {
            // Cập nhật đánh giá
            $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE booking_id = ?");
            $stmt->bind_param("isi", $rating, $comment, $booking_id);
        } else {
            // Tạo đánh giá mới
            $car_id = $booking['car_id'];
            $stmt = $conn->prepare("INSERT INTO reviews (car_id, customer_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $car_id, $user_id, $booking_id, $rating, $comment);
        }
        
        if ($stmt->execute()) {
            $success = 'Cảm ơn bạn đã đánh giá!';
            // Reload review
            $stmt = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $existing_review = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Đánh giá xe</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                        <a href="my-bookings.php">Quay lại đơn đặt</a>
                    </div>
                <?php endif; ?>
                
                <div class="booking-info">
                    <img src="../uploads/<?php echo htmlspecialchars($booking['car_image'] ?: 'default-car.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($booking['car_name']); ?>"
                         style="max-width: 300px; border-radius: 10px; margin-bottom: 1rem;">
                    <h3><?php echo htmlspecialchars($booking['car_name']); ?></h3>
                    <p>Đơn #<?php echo $booking_id; ?> - 
                        <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> đến 
                        <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                    </p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Đánh giá của bạn *</label>
                        <div class="rating" id="ratingStars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo ($existing_review && $i <= $existing_review['rating']) ? 'active' : ''; ?>" 
                                      data-value="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" 
                               value="<?php echo $existing_review['rating'] ?? 0; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Nhận xét (tùy chọn)</label>
                        <textarea id="comment" name="comment" rows="5" 
                                  placeholder="Chia sẻ trải nghiệm của bạn về chiếc xe này..."><?php echo htmlspecialchars($existing_review['comment'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $existing_review ? 'Cập nhật đánh giá' : 'Gửi đánh giá'; ?>
                        </button>
                        <a href="my-bookings.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Xử lý click vào sao
        const stars = document.querySelectorAll('#ratingStars .star');
        const ratingInput = document.getElementById('ratingInput');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.getAttribute('data-value'));
                ratingInput.value = value;
                
                // Cập nhật hiển thị
                stars.forEach(s => {
                    const sValue = parseInt(s.getAttribute('data-value'));
                    if (sValue <= value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            // Hover effect
            star.addEventListener('mouseenter', function() {
                const value = parseInt(this.getAttribute('data-value'));
                stars.forEach(s => {
                    const sValue = parseInt(s.getAttribute('data-value'));
                    if (sValue <= value) {
                        s.style.opacity = '1';
                    } else {
                        s.style.opacity = '0.3';
                    }
                });
            });
        });
        
        document.getElementById('ratingStars').addEventListener('mouseleave', function() {
            stars.forEach(s => {
                s.style.opacity = '1';
            });
        });
    </script>
</body>
</html>
