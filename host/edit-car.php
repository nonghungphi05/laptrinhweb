<?php
/**
 * Sửa thông tin xe (Chủ xe) - Giao diện mới
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin(); // Quyền sở hữu xe được kiểm tra bên dưới

$car_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$base_path = getBasePath();

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

// Lấy danh sách ảnh hiện có
$img_stmt = $conn->prepare("SELECT * FROM car_images WHERE car_id = ? ORDER BY is_primary DESC, id ASC");
$img_stmt->bind_param("i", $car_id);
$img_stmt->execute();
$car_images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Xử lý xóa ảnh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_image') {
    $image_id = (int)($_POST['image_id'] ?? 0);
    
    // Lấy thông tin ảnh
    $del_stmt = $conn->prepare("SELECT ci.*, c.image as primary_image FROM car_images ci JOIN cars c ON ci.car_id = c.id WHERE ci.id = ? AND ci.car_id = ? AND c.owner_id = ?");
    $del_stmt->bind_param("iii", $image_id, $car_id, $user_id);
    $del_stmt->execute();
    $image_to_delete = $del_stmt->get_result()->fetch_assoc();
    
    if ($image_to_delete) {
        // Xóa file
        if ($image_to_delete['file_path'] && file_exists('../uploads/' . $image_to_delete['file_path'])) {
            @unlink('../uploads/' . $image_to_delete['file_path']);
        }
        
        // Xóa record
        $del_stmt = $conn->prepare("DELETE FROM car_images WHERE id = ?");
        $del_stmt->bind_param("i", $image_id);
        $del_stmt->execute();
        
        // Nếu là ảnh chính, cập nhật ảnh chính mới
        if ($image_to_delete['is_primary'] || $image_to_delete['file_path'] === $image_to_delete['primary_image']) {
            // Lấy ảnh đầu tiên còn lại làm ảnh chính
            $new_primary_stmt = $conn->prepare("SELECT file_path FROM car_images WHERE car_id = ? ORDER BY id ASC LIMIT 1");
            $new_primary_stmt->bind_param("i", $car_id);
            $new_primary_stmt->execute();
            $new_primary = $new_primary_stmt->get_result()->fetch_assoc();
            
            $new_image = $new_primary ? $new_primary['file_path'] : '';
            $update_car = $conn->prepare("UPDATE cars SET image = ? WHERE id = ?");
            $update_car->bind_param("si", $new_image, $car_id);
            $update_car->execute();
            
            // Cập nhật is_primary
            if ($new_primary) {
                $conn->query("UPDATE car_images SET is_primary = 0 WHERE car_id = $car_id");
                $conn->query("UPDATE car_images SET is_primary = 1 WHERE car_id = $car_id ORDER BY id ASC LIMIT 1");
            }
        }
        
        header("Location: edit-car.php?id=$car_id&deleted=1");
        exit();
    }
}

// Xử lý đặt ảnh chính
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_primary') {
    $image_id = (int)($_POST['image_id'] ?? 0);
    
    // Kiểm tra ảnh thuộc xe này
    $check_stmt = $conn->prepare("SELECT file_path FROM car_images WHERE id = ? AND car_id = ?");
    $check_stmt->bind_param("ii", $image_id, $car_id);
    $check_stmt->execute();
    $img_result = $check_stmt->get_result()->fetch_assoc();
    
    if ($img_result) {
        // Reset tất cả is_primary
        $reset_stmt = $conn->prepare("UPDATE car_images SET is_primary = 0 WHERE car_id = ?");
        $reset_stmt->bind_param("i", $car_id);
        $reset_stmt->execute();
        
        // Đặt ảnh này làm chính
        $set_stmt = $conn->prepare("UPDATE car_images SET is_primary = 1 WHERE id = ?");
        $set_stmt->bind_param("i", $image_id);
        $set_stmt->execute();
        
        // Cập nhật ảnh chính trong bảng cars
        $update_car = $conn->prepare("UPDATE cars SET image = ? WHERE id = ?");
        $update_car->bind_param("si", $img_result['file_path'], $car_id);
        $update_car->execute();
        
        header("Location: edit-car.php?id=$car_id&primary=1");
        exit();
    }
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_per_day = $_POST['price_per_day'] ?? 0;
    $car_type = $_POST['car_type'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $seats = (int)($_POST['seats'] ?? 4);
    $transmission = $_POST['transmission'] ?? 'auto';
    $fuel = $_POST['fuel'] ?? 'gasoline';
    $location = trim($_POST['location'] ?? '');
    
    if (empty($name) || empty($price_per_day) || empty($car_type)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } elseif ($price_per_day <= 0) {
        $error = 'Giá thuê phải lớn hơn 0';
    } else {
        $image = $car['image'];
        
        // Upload nhiều ảnh mới
        $uploaded_images = [];
        if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
            $files = $_FILES['car_images'];
            $current_image_count = count($car_images);
            $max_new_images = 5 - $current_image_count;
            $max_files = min(count($files['name']), $max_new_images);
            
            for ($i = 0; $i < $max_files; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                $single_file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                
                $uploaded = uploadFile($single_file, '../uploads/');
                if ($uploaded) {
                    $uploaded_images[] = $uploaded;
                }
            }
        }
        
        // Lưu ảnh mới vào database
        if (!empty($uploaded_images)) {
            $is_first = empty($car_images) && empty($image);
            $image_stmt = $conn->prepare("INSERT INTO car_images (car_id, file_path, is_primary) VALUES (?, ?, ?)");
            
            foreach ($uploaded_images as $index => $file_path) {
                $is_primary = ($is_first && $index === 0) ? 1 : 0;
                $image_stmt->bind_param("isi", $car_id, $file_path, $is_primary);
                $image_stmt->execute();
                
                // Cập nhật ảnh chính trong bảng cars nếu chưa có
                if ($is_primary || (empty($image) && $index === 0)) {
                    $image = $file_path;
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE cars SET name = ?, description = ?, image = ?, price_per_day = ?, car_type = ?, status = ?, seats = ?, transmission = ?, fuel = ?, location = ? WHERE id = ? AND owner_id = ?");
        $stmt->bind_param("sssdssisssii", $name, $description, $image, $price_per_day, $car_type, $status, $seats, $transmission, $fuel, $location, $car_id, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Cập nhật xe thành công!';
            
            // Reload thông tin xe
            $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND owner_id = ?");
            $stmt->bind_param("ii", $car_id, $user_id);
            $stmt->execute();
            $car = $stmt->get_result()->fetch_assoc();
            
            // Reload danh sách ảnh
            $img_stmt = $conn->prepare("SELECT * FROM car_images WHERE car_id = ? ORDER BY is_primary DESC, id ASC");
            $img_stmt->bind_param("i", $car_id);
            $img_stmt->execute();
            $car_images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
}

// Hiển thị thông báo từ redirect
if (isset($_GET['deleted'])) {
    $success = 'Đã xóa ảnh thành công!';
}
if (isset($_GET['primary'])) {
    $success = 'Đã đặt ảnh chính thành công!';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa thông tin xe - CarRental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#f98006",
                        background: { light: "#fcfaf8" },
                        "text-main": "#1c160c",
                        "text-muted": "#9c8d7d",
                        "border-color": "#e6e0db"
                    },
                    fontFamily: { display: ['"Plus Jakarta Sans"', "sans-serif"] }
                }
            }
        }
    </script>
</head>
<body class="font-display bg-background-light text-text-main min-h-screen">
    <header class="sticky top-0 z-50 bg-white border-b border-border-color">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="<?php echo $base_path; ?>/index.php" class="flex items-center gap-2 text-primary font-bold text-xl">
                <span class="material-symbols-outlined text-3xl">directions_car</span> CarRental
            </a>
            <a href="dashboard.php" class="text-text-muted hover:text-text-main flex items-center gap-1">
                <span class="material-symbols-outlined">arrow_back</span> Quay lại
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Sửa thông tin xe</h1>
            <p class="text-text-muted mt-1">Cập nhật thông tin cho xe của bạn</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">error</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border border-border-color p-6 space-y-6">
            <!-- Hình ảnh xe -->
            <div>
                <label class="block text-sm font-semibold mb-2">Hình ảnh xe (tối đa 5 ảnh)</label>
                
                <?php if (!empty($car_images)): ?>
                    <!-- Gallery ảnh hiện có -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mb-4">
                        <?php foreach ($car_images as $img): ?>
                            <div class="relative group">
                                <img src="<?php echo $base_path; ?>/uploads/<?php echo htmlspecialchars($img['file_path']); ?>" 
                                     class="w-full h-32 object-cover rounded-lg border <?php echo $img['is_primary'] ? 'ring-2 ring-primary' : ''; ?>"
                                     onerror="this.src='<?php echo $base_path; ?>/uploads/default-car.jpg'">
                                
                                <?php if ($img['is_primary']): ?>
                                    <span class="absolute top-2 left-2 bg-primary text-white text-xs px-2 py-1 rounded-full">Ảnh chính</span>
                                <?php endif; ?>
                                
                                <!-- Actions overlay -->
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                                    <?php if (!$img['is_primary']): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="set_primary">
                                            <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                            <button type="submit" class="p-2 bg-white rounded-full text-primary hover:bg-primary hover:text-white transition-colors" title="Đặt làm ảnh chính">
                                                <span class="material-symbols-outlined text-sm">star</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa ảnh này?');">
                                        <input type="hidden" name="action" value="delete_image">
                                        <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                        <button type="submit" class="p-2 bg-white rounded-full text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Xóa ảnh">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-text-muted mb-3">
                        <span class="material-symbols-outlined text-sm align-middle">info</span>
                        Di chuột vào ảnh để xem các tùy chọn. Đã có <?php echo count($car_images); ?>/5 ảnh.
                    </p>
                <?php elseif ($car['image']): ?>
                    <!-- Fallback: Chỉ có ảnh chính trong bảng cars -->
                    <div class="mb-4">
                        <img src="<?php echo $base_path; ?>/uploads/<?php echo htmlspecialchars($car['image']); ?>" 
                             class="w-full max-w-md h-48 object-cover rounded-lg border"
                             onerror="this.src='<?php echo $base_path; ?>/uploads/default-car.jpg'">
                        <p class="text-xs text-text-muted mt-2">Ảnh hiện tại</p>
                    </div>
                <?php endif; ?>
                
                <!-- Upload ảnh mới -->
                <?php $remaining_slots = 5 - count($car_images); ?>
                <?php if ($remaining_slots > 0): ?>
                    <div class="rounded-xl border border-dashed border-border-color p-6 bg-gray-50 text-center">
                        <span class="material-symbols-outlined text-4xl text-primary mx-auto">add_photo_alternate</span>
                        <p class="text-sm text-text-muted mt-2">Thêm tối đa <?php echo $remaining_slots; ?> ảnh mới</p>
                        <p class="text-xs text-text-muted">Chấp nhận JPG, PNG, GIF, WEBP (tối đa 5MB mỗi ảnh)</p>
                        <input type="file" name="car_images[]" accept="image/*" multiple
                               class="w-full mt-4 text-sm text-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-dashed border-gray-300 p-4 bg-gray-50 text-center text-text-muted">
                        <span class="material-symbols-outlined text-2xl">check_circle</span>
                        <p class="text-sm mt-1">Đã đạt tối đa 5 ảnh. Xóa bớt ảnh để thêm ảnh mới.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Tên xe <span class="text-red-500">*</span></label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($car['name']); ?>" class="w-full h-12 px-4 rounded-lg border border-border-color focus:outline-none focus:ring-2 focus:ring-primary/50">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Loại xe <span class="text-red-500">*</span></label>
                    <select name="car_type" required class="w-full h-12 px-4 rounded-lg border border-border-color">
                        <option value="sedan" <?php echo $car['car_type'] === 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                        <option value="suv" <?php echo $car['car_type'] === 'suv' ? 'selected' : ''; ?>>SUV</option>
                        <option value="mpv" <?php echo $car['car_type'] === 'mpv' ? 'selected' : ''; ?>>MPV</option>
                        <option value="hatchback" <?php echo $car['car_type'] === 'hatchback' ? 'selected' : ''; ?>>Hatchback</option>
                        <option value="pickup" <?php echo $car['car_type'] === 'pickup' ? 'selected' : ''; ?>>Bán tải</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Số chỗ</label>
                    <select name="seats" class="w-full h-12 px-4 rounded-lg border border-border-color">
                        <?php for ($i = 2; $i <= 16; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($car['seats'] ?? 4) == $i ? 'selected' : ''; ?>><?php echo $i; ?> chỗ</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Hộp số</label>
                    <select name="transmission" class="w-full h-12 px-4 rounded-lg border border-border-color">
                        <option value="auto" <?php echo ($car['transmission'] ?? '') === 'auto' ? 'selected' : ''; ?>>Tự động</option>
                        <option value="manual" <?php echo ($car['transmission'] ?? '') === 'manual' ? 'selected' : ''; ?>>Số sàn</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Nhiên liệu</label>
                    <select name="fuel" class="w-full h-12 px-4 rounded-lg border border-border-color">
                        <option value="gasoline" <?php echo ($car['fuel'] ?? '') === 'gasoline' ? 'selected' : ''; ?>>Xăng</option>
                        <option value="diesel" <?php echo ($car['fuel'] ?? '') === 'diesel' ? 'selected' : ''; ?>>Dầu</option>
                        <option value="electric" <?php echo ($car['fuel'] ?? '') === 'electric' ? 'selected' : ''; ?>>Điện</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Giá thuê/ngày (VNĐ) <span class="text-red-500">*</span></label>
                <input type="number" name="price_per_day" required min="0" step="10000" value="<?php echo $car['price_per_day']; ?>" class="w-full h-12 px-4 rounded-lg border border-border-color">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Vị trí xe</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($car['location'] ?? ''); ?>" placeholder="VD: Quận 1, TP.HCM" class="w-full h-12 px-4 rounded-lg border border-border-color">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Mô tả</label>
                <textarea name="description" rows="4" class="w-full px-4 py-3 rounded-lg border border-border-color resize-none"><?php echo htmlspecialchars($car['description']); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2">Trạng thái</label>
                <select name="status" class="w-full h-12 px-4 rounded-lg border border-border-color">
                    <option value="available" <?php echo $car['status'] === 'available' ? 'selected' : ''; ?>>🟢 Sẵn sàng cho thuê</option>
                    <option value="rented" <?php echo $car['status'] === 'rented' ? 'selected' : ''; ?>>🔵 Đang cho thuê</option>
                    <option value="maintenance" <?php echo $car['status'] === 'maintenance' ? 'selected' : ''; ?>>🟡 Tạm ngừng</option>
                </select>
            </div>

            <div class="flex flex-wrap gap-3 pt-4 border-t border-border-color">
                <button type="submit" class="inline-flex items-center gap-2 h-12 px-6 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90">
                    <span class="material-symbols-outlined">save</span> Lưu thay đổi
                </button>
                <a href="dashboard.php" class="inline-flex items-center gap-2 h-12 px-6 bg-gray-100 text-text-main font-semibold rounded-lg hover:bg-gray-200">
                    <span class="material-symbols-outlined">close</span> Hủy
                </a>
                <a href="<?php echo $base_path; ?>/client/car-detail.php?id=<?php echo $car_id; ?>" target="_blank" class="inline-flex items-center gap-2 h-12 px-6 bg-blue-100 text-blue-700 font-semibold rounded-lg hover:bg-blue-200 ml-auto">
                    <span class="material-symbols-outlined">visibility</span> Xem trang xe
                </a>
            </div>
        </form>
    </main>
</body>
</html>
