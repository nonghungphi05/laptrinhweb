<?php
/**
 * Sửa thông tin xe (Chủ xe)
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

$error = '';
$success = '';

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_per_day = $_POST['price_per_day'] ?? 0;
    $car_type = $_POST['car_type'] ?? '';
    $status = $_POST['status'] ?? 'available';
    
    if (empty($name) || empty($price_per_day) || empty($car_type)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } elseif ($price_per_day <= 0) {
        $error = 'Giá thuê phải lớn hơn 0';
    } else {
        // Xử lý upload ảnh mới
        $image = $car['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = '../uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Xóa ảnh cũ
                    if ($image && file_exists('../uploads/' . $image)) {
                        unlink('../uploads/' . $image);
                    }
                    $image = $new_filename;
                }
            }
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE cars SET name = ?, description = ?, image = ?, price_per_day = ?, car_type = ?, status = ? WHERE id = ? AND owner_id = ?");
        $stmt->bind_param("sssdssii", $name, $description, $image, $price_per_day, $car_type, $status, $car_id, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Cập nhật xe thành công!';
            // Reload thông tin xe
            $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND owner_id = ?");
            $stmt->bind_param("ii", $car_id, $user_id);
            $stmt->execute();
            $car = $stmt->get_result()->fetch_assoc();
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
    <title>Sửa thông tin xe - Chủ xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="dashboard">
                <h1>Sửa thông tin xe</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Tên xe *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? $car['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="car_type">Loại xe *</label>
                        <select id="car_type" name="car_type" required>
                            <option value="sedan" <?php echo ($car['car_type'] ?? '') === 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                            <option value="suv" <?php echo ($car['car_type'] ?? '') === 'suv' ? 'selected' : ''; ?>>SUV</option>
                            <option value="mpv" <?php echo ($car['car_type'] ?? '') === 'mpv' ? 'selected' : ''; ?>>MPV</option>
                            <option value="pickup" <?php echo ($car['car_type'] ?? '') === 'pickup' ? 'selected' : ''; ?>>Bán tải</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_day">Giá thuê/ngày (VNĐ) *</label>
                        <input type="number" id="price_per_day" name="price_per_day" required min="0"
                               value="<?php echo htmlspecialchars($_POST['price_per_day'] ?? $car['price_per_day']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? $car['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Hình ảnh xe (để trống nếu không đổi)</label>
                        <?php if ($car['image']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($car['image']); ?>" 
                                 alt="Current image" 
                                 style="max-width: 300px; margin-bottom: 1rem; border-radius: 5px;">
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            <option value="available" <?php echo ($car['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Còn xe</option>
                            <option value="rented" <?php echo ($car['status'] ?? '') === 'rented' ? 'selected' : ''; ?>>Đang cho thuê</option>
                            <option value="maintenance" <?php echo ($car['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                        </select>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                        <a href="dashboard.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>


