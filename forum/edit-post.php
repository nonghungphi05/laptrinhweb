<?php
/**
 * Trang sửa bài viết
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$post_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Lấy thông tin bài viết
$stmt = $conn->prepare("SELECT p.*, car.id as car_id, car.name as car_name, car.description as car_description,
        car.price_per_day, car.image as car_image, car.car_type, car.status as car_status
        FROM posts p
        LEFT JOIN cars car ON p.car_id = car.id
        WHERE p.id = ? AND p.user_id = ? AND p.status != 'deleted'");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my-posts.php');
    exit();
}

$post = $result->fetch_assoc();

// Lấy danh sách categories
$categories_stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $post_type = $_POST['post_type'] ?? 'discussion';
    
    // Validate
    if (empty($title) || empty($content) || empty($category_id)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } else {
        // Cập nhật bài viết
        $update_stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, category_id = ?, post_type = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("ssisi", $title, $content, $category_id, $post_type, $post_id);
        $update_stmt->execute();
        
        // Nếu là bài cho thuê xe và có thông tin xe
        if ($post_type === 'rental' && $post['car_id']) {
            $car_name = trim($_POST['car_name'] ?? '');
            $car_description = trim($_POST['car_description'] ?? '');
            $price_per_day = floatval($_POST['price_per_day'] ?? 0);
            $car_type = $_POST['car_type'] ?? '';
            $car_status = $_POST['car_status'] ?? 'available';
            
            if (!empty($car_name) && !empty($price_per_day) && !empty($car_type)) {
                // Xử lý upload ảnh mới (nếu có)
                $car_image = $post['car_image'];
                if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
                    $new_image = uploadFile($_FILES['car_image'], '../uploads/');
                    if ($new_image) {
                        $car_image = $new_image;
                    }
                }
                
                // Cập nhật thông tin xe
                $car_update_stmt = $conn->prepare("UPDATE cars SET name = ?, description = ?, image = ?, price_per_day = ?, car_type = ?, status = ? WHERE id = ?");
                $car_update_stmt->bind_param("sssdssi", $car_name, $car_description, $car_image, $price_per_day, $car_type, $car_status, $post['car_id']);
                $car_update_stmt->execute();
            }
        }
        
        $success = 'Cập nhật bài viết thành công!';
        header("Location: post-detail.php?id=$post_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa bài viết - Diễn đàn Thuê Xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="form-container">
                <h1>✏️ Sửa bài viết</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" id="postForm">
                    <div class="form-group">
                        <label for="post_type">Loại bài viết *</label>
                        <select id="post_type" name="post_type" required onchange="toggleCarFields()">
                            <option value="discussion" <?php echo $post['post_type'] === 'discussion' ? 'selected' : ''; ?>>💬 Thảo luận</option>
                            <option value="rental" <?php echo $post['post_type'] === 'rental' ? 'selected' : ''; ?>>🚗 Cho thuê xe</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Danh mục *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $post['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Tiêu đề *</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($post['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Nội dung *</label>
                        <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>
                    
                    <!-- Thông tin xe (chỉ hiển thị khi là bài cho thuê xe) -->
                    <?php if ($post['post_type'] === 'rental' && $post['car_id']): ?>
                        <div id="carFields">
                            <h3>Thông tin xe cho thuê</h3>
                            
                            <div class="form-group">
                                <label for="car_name">Tên xe *</label>
                                <input type="text" id="car_name" name="car_name" 
                                       value="<?php echo htmlspecialchars($post['car_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="car_type">Loại xe *</label>
                                <select id="car_type" name="car_type">
                                    <option value="">-- Chọn loại xe --</option>
                                    <option value="sedan" <?php echo ($post['car_type'] ?? '') === 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                                    <option value="suv" <?php echo ($post['car_type'] ?? '') === 'suv' ? 'selected' : ''; ?>>SUV</option>
                                    <option value="mpv" <?php echo ($post['car_type'] ?? '') === 'mpv' ? 'selected' : ''; ?>>MPV</option>
                                    <option value="pickup" <?php echo ($post['car_type'] ?? '') === 'pickup' ? 'selected' : ''; ?>>Bán tải</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="price_per_day">Giá thuê/ngày (VNĐ) *</label>
                                <input type="number" id="price_per_day" name="price_per_day" min="0" step="1000"
                                       value="<?php echo $post['price_per_day'] ?? ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="car_description">Mô tả xe</label>
                                <textarea id="car_description" name="car_description" rows="5"><?php echo htmlspecialchars($post['car_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="car_image">Hình ảnh xe</label>
                                <?php if ($post['car_image']): ?>
                                    <p>Ảnh hiện tại: <img src="../uploads/<?php echo htmlspecialchars($post['car_image']); ?>" alt="Ảnh xe" style="max-width: 200px;"></p>
                                <?php endif; ?>
                                <input type="file" id="car_image" name="car_image" accept="image/*">
                                <small>Để trống nếu không muốn thay đổi ảnh</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="car_status">Trạng thái</label>
                                <select id="car_status" name="car_status">
                                    <option value="available" <?php echo ($post['car_status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Còn xe</option>
                                    <option value="rented" <?php echo ($post['car_status'] ?? '') === 'rented' ? 'selected' : ''; ?>>Đang cho thuê</option>
                                    <option value="maintenance" <?php echo ($post['car_status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                        <a href="post-detail.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">Hủy</a>
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
            
            if (carFields) {
                if (postType === 'rental') {
                    carFields.style.display = 'block';
                } else {
                    carFields.style.display = 'none';
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            toggleCarFields();
        });
    </script>
</body>
</html>

