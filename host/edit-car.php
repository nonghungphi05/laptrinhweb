<?php
/**
 * Sửa thông tin xe (Chủ xe) - Giao diện mới
 */
require_once '../config/database.php';
require_once '../config/session.php';

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

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = '../uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    if ($image && $image !== 'default-car.jpg' && file_exists('../uploads/' . $image)) {
                        unlink('../uploads/' . $image);
                    }
                    $image = $new_filename;
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE cars SET name = ?, description = ?, image = ?, price_per_day = ?, car_type = ?, status = ?, seats = ?, transmission = ?, fuel = ?, location = ? WHERE id = ? AND owner_id = ?");
        $stmt->bind_param("sssdssisssii", $name, $description, $image, $price_per_day, $car_type, $status, $seats, $transmission, $fuel, $location, $car_id, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Cập nhật xe thành công!';
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
            <div>
                <label class="block text-sm font-semibold mb-2">Hình ảnh xe</label>
                <?php if ($car['image']): ?>
                    <img src="<?php echo $base_path; ?>/uploads/<?php echo htmlspecialchars($car['image']); ?>" class="w-full max-w-md h-48 object-cover rounded-lg border mb-3">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" class="block w-full text-sm text-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
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
