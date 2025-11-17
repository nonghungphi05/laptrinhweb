<?php
/**
 * Đặt xe - Tất cả user đều có thể đặt xe
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$car_id = $_GET['car_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy thông tin xe
$stmt = $conn->prepare("SELECT c.*, u.full_name as owner_name 
    FROM cars c 
    JOIN users u ON c.owner_id = u.id 
    WHERE c.id = ? AND c.status = 'available'");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php');
    exit();
}

$car = $result->fetch_assoc();

$error = '';
$success = '';

// Xử lý đặt xe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (empty($start_date) || empty($end_date)) {
        $error = 'Vui lòng chọn ngày bắt đầu và kết thúc';
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = 'Ngày bắt đầu phải từ hôm nay trở đi';
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $error = 'Ngày kết thúc phải sau ngày bắt đầu';
    } else {
        // Kiểm tra trùng lịch (overlap detection)
        // 2 khoảng thời gian overlap nếu: start_date_moi <= end_date_cu AND end_date_moi >= start_date_cu
        $stmt = $conn->prepare("SELECT id FROM bookings 
            WHERE car_id = ? 
            AND status IN ('pending', 'confirmed')
            AND start_date <= ? 
            AND end_date >= ?");
        $stmt->bind_param("iss", $car_id, $end_date, $start_date);
        $stmt->execute();
        $conflict = $stmt->get_result();
        
        if ($conflict->num_rows > 0) {
            $error = 'Xe đã có người đặt trong khoảng thời gian này. Vui lòng chọn ngày khác.';
        } else {
            // Tính tổng tiền
            $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
            $total_price = $days * $car['price_per_day'];
            
            // Tạo booking
            $stmt = $conn->prepare("INSERT INTO bookings (car_id, customer_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iissd", $car_id, $user_id, $start_date, $end_date, $total_price);
            
            if ($stmt->execute()) {
                $booking_id = $conn->insert_id;
                
                // Chuyển sang trang thanh toán
                header("Location: payment.php?booking_id=$booking_id");
                exit();
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt xe - <?php echo htmlspecialchars($car['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Đặt xe: <?php echo htmlspecialchars($car['name']); ?></h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="booking-info">
                    <h3>Thông tin xe</h3>
                    <p><strong>Loại xe:</strong> <?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></p>
                    <p><strong>Giá thuê:</strong> <?php echo number_format($car['price_per_day']); ?> VNĐ/ngày</p>
                    <p><strong>Chủ xe:</strong> <?php echo htmlspecialchars($car['owner_name']); ?></p>
                </div>
                
                <form method="POST" action="" id="bookingForm">
                    <div class="form-group">
                        <label for="start_date">Ngày bắt đầu *</label>
                        <input type="date" id="start_date" name="start_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">Ngày kết thúc *</label>
                        <input type="date" id="end_date" name="end_date" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="booking-info">
                        <h3>Tổng tiền dự kiến</h3>
                        <div id="calculation">
                            <p>Số ngày: <span id="days">0</span></p>
                            <div class="total">
                                Tổng: <span id="totalPrice">0</span> VNĐ
                            </div>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Tiếp tục thanh toán</button>
                        <a href="car-detail.php?id=<?php echo (int)$car_id; ?>" class="btn btn-secondary">Quay lại xe</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Tính tổng tiền tự động
        const pricePerDay = <?php echo $car['price_per_day']; ?>;
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        function calculateTotal() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (startDate && endDate && endDate > startDate) {
                const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                const total = days * pricePerDay;
                
                document.getElementById('days').textContent = days;
                document.getElementById('totalPrice').textContent = total.toLocaleString('vi-VN');
            } else {
                document.getElementById('days').textContent = '0';
                document.getElementById('totalPrice').textContent = '0';
            }
        }
        
        startDateInput.addEventListener('change', calculateTotal);
        endDateInput.addEventListener('change', calculateTotal);
    </script>
</body>
</html>
