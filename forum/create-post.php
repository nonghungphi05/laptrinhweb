<?php
/**
 * Trang đăng bài viết mới
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$error = '';
$success = '';
$base_path = getBasePath();

// Lấy danh sách categories
$categories_stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetch_all(MYSQLI_ASSOC);

// Xử lý đăng bài
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $post_type = $_POST['post_type'] ?? 'discussion';
    $user_id = $_SESSION['user_id'];
    
    // Validate
    if (empty($title) || empty($content) || empty($category_id)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } else {
        // Nếu là bài cho thuê xe, cần thêm thông tin xe
        if ($post_type === 'rental') {
            $car_name = trim($_POST['car_name'] ?? '');
            $car_description = trim($_POST['car_description'] ?? '');
            $price_per_day = floatval($_POST['price_per_day'] ?? 0);
            $car_type = $_POST['car_type'] ?? '';
            $rental_type = $_POST['rental_type'] ?? 'self-drive';
            $location = $_POST['location'] ?? 'hcm';
            $status = $_POST['status'] ?? 'available';
            
            if (empty($car_name) || empty($price_per_day) || empty($car_type) || empty($rental_type)) {
                $error = 'Vui lòng điền đầy đủ thông tin xe';
            } elseif (!isset($_FILES['car_image']) || $_FILES['car_image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Vui lòng chọn hình ảnh xe';
            } else {
                // Bắt đầu transaction
                $conn->begin_transaction();
                
                try {
                    // Xử lý upload ảnh
                    $car_image = '';
                    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
                        $car_image = uploadFile($_FILES['car_image'], '../uploads/');
                        if (!$car_image) {
                            throw new Exception('Lỗi upload ảnh. Vui lòng kiểm tra file ảnh (chỉ chấp nhận: JPG, PNG, GIF, WEBP, tối đa 5MB).');
                        }
                    }
                    
                    // Tạo bài viết
                    $post_stmt = $conn->prepare("INSERT INTO posts (user_id, category_id, title, content, post_type, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $post_stmt->bind_param("iisss", $user_id, $category_id, $title, $content, $post_type);
                    $post_stmt->execute();
                    $post_id = $conn->insert_id;
                    
                    // Tạo xe (thêm rental_type và location)
                    $car_stmt = $conn->prepare("INSERT INTO cars (owner_id, post_id, name, description, image, price_per_day, car_type, rental_type, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $car_stmt->bind_param("iisssdssss", $user_id, $post_id, $car_name, $car_description, $car_image, $price_per_day, $car_type, $rental_type, $location, $status);
                    $car_stmt->execute();
                    $car_id = $conn->insert_id;
                    
                    // Cập nhật post với car_id
                    $update_stmt = $conn->prepare("UPDATE posts SET car_id = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $car_id, $post_id);
                    $update_stmt->execute();
                    
                    $conn->commit();
                    $success = 'Đăng bài thành công!';
                    header("Location: " . ($base_path ? $base_path . '/forum/post-detail.php?id=' . $post_id : 'post-detail.php?id=' . $post_id));
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }
        } else {
            // Bài viết thảo luận thông thường
            $post_stmt = $conn->prepare("INSERT INTO posts (user_id, category_id, title, content, post_type, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $post_stmt->bind_param("iisss", $user_id, $category_id, $title, $content, $post_type);
            
            if ($post_stmt->execute()) {
                $post_id = $conn->insert_id;
                $success = 'Đăng bài thành công!';
                header("Location: " . ($base_path ? $base_path . '/forum/post-detail.php?id=' . $post_id : 'post-detail.php?id=' . $post_id));
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
    <title>Đăng bài mới - Diễn đàn Thuê Xe</title>
    <link rel="stylesheet" href="<?php echo $base_path ? $base_path . '/assets/css/style.css' : '../assets/css/style.css'; ?>">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="form-container">
                <h1>✏️ Đăng bài mới</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" id="postForm">
                    <div class="form-group">
                        <label for="post_type">Loại bài viết *</label>
                        <select id="post_type" name="post_type" required onchange="toggleCarFields()">
                            <option value="discussion" <?php echo ($_POST['post_type'] ?? '') === 'discussion' ? 'selected' : ''; ?>>💬 Thảo luận</option>
                            <option value="rental" <?php echo ($_POST['post_type'] ?? '') === 'rental' ? 'selected' : ''; ?>>🚗 Cho thuê xe</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Danh mục *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Tiêu đề *</label>
                        <input type="text" id="title" name="title" required 
                               placeholder="Nhập tiêu đề bài viết..."
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Nội dung *</label>
                        <textarea id="content" name="content" rows="10" required 
                                  placeholder="Nhập nội dung bài viết..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Thông tin xe (chỉ hiển thị khi chọn "Cho thuê xe") -->
                    <div id="carFields" style="display: none;">
                        <h3>Thông tin xe cho thuê</h3>
                        
                        <div class="form-group">
                            <label for="rental_type">Loại dịch vụ *</label>
                            <select id="rental_type" name="rental_type" onchange="updatePriceLabel()">
                                <option value="">-- Chọn loại dịch vụ --</option>
                                <option value="self-drive" <?php echo ($_POST['rental_type'] ?? '') === 'self-drive' ? 'selected' : ''; ?>>🚗 Xe tự lái</option>
                                <option value="with-driver" <?php echo ($_POST['rental_type'] ?? '') === 'with-driver' ? 'selected' : ''; ?>>🚕 Xe có tài xế</option>
                                <option value="long-term" <?php echo ($_POST['rental_type'] ?? '') === 'long-term' ? 'selected' : ''; ?>>📅 Thuê xe dài hạn</option>
                            </select>
                            <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                                • Xe tự lái: Khách hàng tự lái xe<br>
                                • Xe có tài xế: Có tài xế chuyên nghiệp đi kèm<br>
                                • Thuê xe dài hạn: Thuê từ 3 tháng trở lên (giá theo tháng)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="car_name">Tên xe *</label>
                            <input type="text" id="car_name" name="car_name" 
                                   placeholder="Ví dụ: Toyota Vios 2023"
                                   value="<?php echo htmlspecialchars($_POST['car_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="car_type">Loại xe *</label>
                            <select id="car_type" name="car_type">
                                <option value="">-- Chọn loại xe --</option>
                                <option value="sedan" <?php echo ($_POST['car_type'] ?? '') === 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                                <option value="suv" <?php echo ($_POST['car_type'] ?? '') === 'suv' ? 'selected' : ''; ?>>SUV</option>
                                <option value="mpv" <?php echo ($_POST['car_type'] ?? '') === 'mpv' ? 'selected' : ''; ?>>MPV</option>
                                <option value="pickup" <?php echo ($_POST['car_type'] ?? '') === 'pickup' ? 'selected' : ''; ?>>Bán tải</option>
                                <option value="hatchback" <?php echo ($_POST['car_type'] ?? '') === 'hatchback' ? 'selected' : ''; ?>>Hatchback</option>
                                <option value="van" <?php echo ($_POST['car_type'] ?? '') === 'van' ? 'selected' : ''; ?>>Xe khách</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Địa điểm *</label>
                            <select id="location" name="location">
                                <option value="hcm" <?php echo ($_POST['location'] ?? 'hcm') === 'hcm' ? 'selected' : ''; ?>>TP. Hồ Chí Minh</option>
                                <option value="hanoi" <?php echo ($_POST['location'] ?? '') === 'hanoi' ? 'selected' : ''; ?>>Hà Nội</option>
                                <option value="danang" <?php echo ($_POST['location'] ?? '') === 'danang' ? 'selected' : ''; ?>>Đà Nẵng</option>
                                <option value="cantho" <?php echo ($_POST['location'] ?? '') === 'cantho' ? 'selected' : ''; ?>>Cần Thơ</option>
                                <option value="nhatrang" <?php echo ($_POST['location'] ?? '') === 'nhatrang' ? 'selected' : ''; ?>>Nha Trang</option>
                                <option value="dalat" <?php echo ($_POST['location'] ?? '') === 'dalat' ? 'selected' : ''; ?>>Đà Lạt</option>
                                <option value="phuquoc" <?php echo ($_POST['location'] ?? '') === 'phuquoc' ? 'selected' : ''; ?>>Phú Quốc</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_per_day" id="price_label">Giá thuê/ngày (VNĐ) *</label>
                            <input type="number" id="price_per_day" name="price_per_day" min="0" step="1000"
                                   placeholder="500000"
                                   value="<?php echo htmlspecialchars($_POST['price_per_day'] ?? ''); ?>">
                            <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                                Lưu ý: Nếu chọn "Thuê xe dài hạn", nhập giá theo tháng (ví dụ: 8000000 cho 8 triệu/tháng)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="car_description">Mô tả xe</label>
                            <textarea id="car_description" name="car_description" rows="5"
                                      placeholder="Mô tả chi tiết về xe (tính năng, tiện nghi, phù hợp cho...)..."><?php echo htmlspecialchars($_POST['car_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="car_image">Hình ảnh xe *</label>
                            <input type="file" id="car_image" name="car_image" accept="image/*" onchange="previewImage(this)">
                            <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                                Chấp nhận: JPG, PNG, GIF, WEBP (tối đa 5MB)
                            </small>
                            <div id="image-preview" style="margin-top: 1rem; display: none;">
                                <img id="preview-img" src="" alt="Preview" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd; object-fit: cover;">
                                <button type="button" onclick="removePreview()" style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">Xóa ảnh</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select id="status" name="status">
                                <option value="available" <?php echo ($_POST['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Còn xe</option>
                                <option value="maintenance" <?php echo ($_POST['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Đăng bài</button>
                        <a href="<?php echo $base_path ? $base_path . '/forum/index.php' : 'index.php'; ?>" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function toggleCarFields() {
            const postType = document.getElementById('post_type').value;
            const carFields = document.getElementById('carFields');
            const carInputs = carFields.querySelectorAll('input, select, textarea');
            
            if (postType === 'rental') {
                carFields.style.display = 'block';
                carInputs.forEach(input => {
                    if (input.id === 'car_image') {
                        input.setAttribute('required', 'required');
                    }
                });
                document.getElementById('car_name').setAttribute('required', 'required');
                document.getElementById('car_type').setAttribute('required', 'required');
                document.getElementById('rental_type').setAttribute('required', 'required');
                document.getElementById('location').setAttribute('required', 'required');
                document.getElementById('price_per_day').setAttribute('required', 'required');
            } else {
                carFields.style.display = 'none';
                carInputs.forEach(input => {
                    input.removeAttribute('required');
                });
            }
        }
        
        // Cập nhật label giá khi chọn loại dịch vụ
        function updatePriceLabel() {
            const rentalType = document.getElementById('rental_type').value;
            const priceLabel = document.getElementById('price_label');
            const priceInput = document.getElementById('price_per_day');
            
            if (rentalType === 'long-term') {
                priceLabel.textContent = 'Giá thuê/tháng (VNĐ) *';
                priceInput.placeholder = '8000000';
            } else {
                priceLabel.textContent = 'Giá thuê/ngày (VNĐ) *';
                priceInput.placeholder = '500000';
            }
        }
        
        // Preview hình ảnh khi chọn file
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            const fileInput = document.getElementById('car_image');
            
            if (input.files && input.files[0]) {
                // Kiểm tra kích thước file (5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (input.files[0].size > maxSize) {
                    alert('File ảnh quá lớn. Vui lòng chọn file nhỏ hơn 5MB.');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                // Kiểm tra loại file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(input.files[0].type)) {
                    alert('File không hợp lệ. Vui lòng chọn file ảnh (JPG, PNG, GIF, WEBP).');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Xóa preview ảnh
        function removePreview() {
            const preview = document.getElementById('image-preview');
            const fileInput = document.getElementById('car_image');
            fileInput.value = '';
            preview.style.display = 'none';
        }
        
        // Khởi tạo khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            toggleCarFields();
            updatePriceLabel();
            
            // Event listener cho rental_type
            const rentalTypeSelect = document.getElementById('rental_type');
            if (rentalTypeSelect) {
                rentalTypeSelect.addEventListener('change', updatePriceLabel);
            }
        });
    </script>
</body>
</html>
