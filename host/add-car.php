<?php
/**
 * Thêm xe mới (Chủ xe)
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('host');

$error = '';
$success = '';

// Xử lý thêm xe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_per_day = $_POST['price_per_day'] ?? 0;
    $car_type = $_POST['car_type'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $owner_id = $_SESSION['user_id'];
    
    // Validate
    if (empty($name) || empty($price_per_day) || empty($car_type)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } elseif ($price_per_day <= 0) {
        $error = 'Giá thuê phải lớn hơn 0';
    } else {
        // Xử lý upload ảnh
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = '../uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = $new_filename;
                }
            }
        }
        
        // Insert vào database
        $stmt = $conn->prepare("INSERT INTO cars (owner_id, name, description, image, price_per_day, car_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdss", $owner_id, $name, $description, $image, $price_per_day, $car_type, $status);
        
        if ($stmt->execute()) {
            $success = 'Thêm xe thành công!';
            // Reset form
            $_POST = [];
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
    <title>Thêm xe mới - Chủ xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Thêm xe mới</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                        <a href="dashboard.php">Quay lại dashboard</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Tên xe *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="car_type">Loại xe *</label>
                        <select id="car_type" name="car_type" required>
                            <option value="">-- Chọn loại xe --</option>
                            <option value="sedan" <?php echo ($_POST['car_type'] ?? '') === 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                            <option value="suv" <?php echo ($_POST['car_type'] ?? '') === 'suv' ? 'selected' : ''; ?>>SUV</option>
                            <option value="mpv" <?php echo ($_POST['car_type'] ?? '') === 'mpv' ? 'selected' : ''; ?>>MPV</option>
                            <option value="pickup" <?php echo ($_POST['car_type'] ?? '') === 'pickup' ? 'selected' : ''; ?>>Bán tải</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_day">Giá thuê/ngày (VNĐ) *</label>
                        <input type="number" id="price_per_day" name="price_per_day" required min="0"
                               value="<?php echo htmlspecialchars($_POST['price_per_day'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Hình ảnh xe</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            <option value="available" <?php echo ($_POST['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Còn xe</option>
                            <option value="maintenance" <?php echo ($_POST['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                        </select>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Thêm xe</button>
                        <a href="dashboard.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>


