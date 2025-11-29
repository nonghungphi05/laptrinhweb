<?php
/**
 * Đăng xe mới (rental-only)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$base_path = getBasePath();
$error = '';
$success = '';
$owner_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $car_type = $_POST['car_type'] ?? '';
    $location = $_POST['location'] ?? 'hcm';
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = 'available'; // Xe mới đăng luôn có trạng thái sẵn sàng

if ($name === '' || $car_type === '' || $location === '' || $price_per_day <= 0) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } else {
$uploaded_images = [];
        if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
            $files = $_FILES['car_images'];
            $max_files = min(count($files['name']), 5);

            for ($i = 0; $i < $max_files; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $error = 'Upload ảnh thất bại. Vui lòng thử lại.';
                    break;
                }

                $single_file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];

                $uploaded = uploadFile($single_file, '../uploads/');
                if (!$uploaded) {
                    $error = 'Upload ảnh thất bại. Chỉ nhận JPG, PNG, GIF, WEBP tối đa 5MB.';
                    break;
                }

                $uploaded_images[] = $uploaded;
            }
        }

        if ($error === '') {
            $primary_image = $uploaded_images[0] ?? '';

            $stmt = $conn->prepare("INSERT INTO cars (owner_id, name, description, image, price_per_day, car_type, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "isssdsss",
                $owner_id,
                $name,
                $description,
                $primary_image,
                $price_per_day,
                $car_type,
                $location,
                $status
            );

            if ($stmt->execute()) {
                $car_id = $stmt->insert_id;

                if (!empty($uploaded_images)) {
                    $image_stmt = $conn->prepare("INSERT INTO car_images (car_id, file_path, is_primary) VALUES (?, ?, ?)");
                    foreach ($uploaded_images as $index => $file_path) {
                        $is_primary = $index === 0 ? 1 : 0;
                        $image_stmt->bind_param("isi", $car_id, $file_path, $is_primary);
                        $image_stmt->execute();
                    }
                }

                $success = 'Xe của bạn đã được đăng!';
                $_POST = [];
            } else {
                $error = 'Không thể lưu. Vui lòng thử lại.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng xe mới - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#FF7A00",
                        "background-light": "#F7F8FA",
                        "background-dark": "#121212",
                        "border-color": "#E0E0E0"
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-[#1d1d1f] dark:text-gray-100">
    <?php include '../includes/header.php'; ?>

    <main class="px-4 sm:px-6 lg:px-10 py-8">
        <div class="max-w-5xl mx-auto space-y-8">
            <div class="flex flex-col gap-2">
                <p class="text-primary text-sm font-semibold uppercase tracking-wide">Đăng xe</p>
                <h1 class="text-3xl sm:text-4xl font-black text-[#0f172a] dark:text-white">Chia sẻ chiếc xe của bạn và bắt đầu kiếm tiền</h1>
                <p class="text-gray-500 dark:text-gray-400 text-base">Hoàn thành các bước dưới đây. Thông tin càng rõ ràng, khách càng dễ lựa chọn.</p>
            </div>

            <?php if ($error): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm font-semibold">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="rounded-xl border border-green-200 bg-green-50 text-green-700 px-4 py-3 text-sm font-semibold flex items-center justify-between flex-wrap gap-2">
                    <span><?php echo htmlspecialchars($success); ?></span>
                    <a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'dashboard.php'; ?>" class="text-primary underline font-bold">Về trang quản lý</a>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-gray-900/60 border border-border-color dark:border-gray-800 rounded-2xl shadow-sm">
                <form method="POST" enctype="multipart/form-data" class="p-6 sm:p-10 space-y-10">
                    <section class="space-y-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-semibold text-primary uppercase tracking-wide">Bước 1</p>
                                <h2 class="text-xl font-bold text-[#0f172a] dark:text-white">Thông tin cơ bản</h2>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tên xe *</span>
                                <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="Ví dụ: Toyota Vios 2023"
                                       class="rounded-xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30">
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Loại xe *</span>
                                <select name="car_type" required class="rounded-xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30">
                                    <option value="">Chọn loại xe</option>
                                    <?php
                                    $car_types = [
                                        'sedan' => 'Sedan',
                                        'suv' => 'SUV',
                                        'mpv' => 'MPV',
                                        'pickup' => 'Bán tải',
                                        'hatchback' => 'Hatchback',
                                        'van' => 'Xe khách'
                                    ];
                                    foreach ($car_types as $value => $label):
                                    ?>
                                        <option value="<?php echo $value; ?>" <?php echo (($_POST['car_type'] ?? '') === $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Địa điểm *</span>
                                <select name="location" required class="rounded-xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30">
                                    <?php
                                    $locations = [
                                        'hcm' => 'TP. Hồ Chí Minh',
                                        'hanoi' => 'Hà Nội',
                                        'danang' => 'Đà Nẵng',
                                        'cantho' => 'Cần Thơ',
                                        'nhatrang' => 'Nha Trang',
                                        'dalat' => 'Đà Lạt',
                                        'phuquoc' => 'Phú Quốc'
                                    ];
                                    foreach ($locations as $code => $label):
                                    ?>
                                        <option value="<?php echo $code; ?>" <?php echo (($_POST['location'] ?? 'hcm') === $code) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Giá thuê (VNĐ) *</span>
                                <input type="number" step="1000" min="0" name="price_per_day" required value="<?php echo htmlspecialchars($_POST['price_per_day'] ?? ''); ?>"
                                       class="rounded-xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30"
                                       placeholder="Ví dụ: 500000 (theo ngày)">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Nhập giá thuê theo ngày hoặc theo chuyến tùy dịch vụ.</span>
                            </label>
                            <label class="flex flex-col gap-2 md:col-span-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mô tả chi tiết</span>
                                <textarea name="description" rows="4" placeholder="Ghi rõ tình trạng xe, tiện nghi, quy định giao nhận..."
                                    class="rounded-xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </label>
                            <div class="md:col-span-2 space-y-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Hình ảnh xe (tối đa 5 ảnh)</span>
                                <div class="rounded-xl border border-dashed border-border-color dark:border-gray-700 p-6 bg-gray-50 dark:bg-gray-800/50 text-center">
                                    <span class="material-symbols-outlined text-4xl text-primary mx-auto">image</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Chọn tối đa 5 ảnh rõ nét, chấp nhận JPG/PNG/GIF/WEBP (tối đa 5MB mỗi ảnh).</p>
                                    <input type="file" name="car_images[]" accept="image/*" multiple
                                        class="w-full mt-4 rounded-lg border border-border-color dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-col sm:flex-row sm:justify-between gap-4 pt-6 border-t border-border-color dark:border-gray-800">
                        <a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'dashboard.php'; ?>" class="inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-6 py-3 font-semibold text-gray-600 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 w-full sm:w-auto">Hủy</a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-primary text-white px-8 py-3 font-semibold shadow-sm hover:bg-primary/90 w-full sm:w-auto">Đăng xe ngay</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

