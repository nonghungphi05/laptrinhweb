<?php
/**
 * Đăng xe mới (rental-only)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

if (!function_exists('ensureCarExtendedColumns')) {
    function ensureCarExtendedColumns(mysqli $conn): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $dbResult = $conn->query("SELECT DATABASE() AS db");
        $dbName = $dbResult && $dbResult->num_rows > 0 ? $dbResult->fetch_assoc()['db'] : DB_NAME;
        $columns = [
            'license_plate' => "ALTER TABLE cars ADD COLUMN license_plate VARCHAR(20) DEFAULT NULL AFTER owner_id",
            'brand' => "ALTER TABLE cars ADD COLUMN brand VARCHAR(80) DEFAULT NULL AFTER license_plate",
            'model' => "ALTER TABLE cars ADD COLUMN model VARCHAR(120) DEFAULT NULL AFTER brand",
            'seats' => "ALTER TABLE cars ADD COLUMN seats TINYINT UNSIGNED DEFAULT NULL AFTER model",
            'production_year' => "ALTER TABLE cars ADD COLUMN production_year SMALLINT DEFAULT NULL AFTER seats",
            'transmission' => "ALTER TABLE cars ADD COLUMN transmission ENUM('automatic','manual') DEFAULT 'automatic' AFTER production_year",
            'fuel_type' => "ALTER TABLE cars ADD COLUMN fuel_type ENUM('gasoline','diesel','electric','hybrid') DEFAULT 'gasoline' AFTER transmission",
            'fuel_consumption' => "ALTER TABLE cars ADD COLUMN fuel_consumption VARCHAR(20) DEFAULT NULL AFTER fuel_type",
            'features' => "ALTER TABLE cars ADD COLUMN features TEXT DEFAULT NULL AFTER fuel_consumption",
            'discount_enabled' => "ALTER TABLE cars ADD COLUMN discount_enabled TINYINT(1) DEFAULT 0 AFTER status",
            'discount_percent' => "ALTER TABLE cars ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT 0 AFTER discount_enabled",
            'car_address' => "ALTER TABLE cars ADD COLUMN car_address VARCHAR(255) DEFAULT NULL AFTER discount_percent",
            'delivery_enabled' => "ALTER TABLE cars ADD COLUMN delivery_enabled TINYINT(1) DEFAULT 0 AFTER car_address",
            'delivery_distance_km' => "ALTER TABLE cars ADD COLUMN delivery_distance_km INT DEFAULT NULL AFTER delivery_enabled",
            'delivery_fee' => "ALTER TABLE cars ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT NULL AFTER delivery_distance_km",
            'mileage_limit_enabled' => "ALTER TABLE cars ADD COLUMN mileage_limit_enabled TINYINT(1) DEFAULT 0 AFTER delivery_fee",
            'mileage_limit_per_day' => "ALTER TABLE cars ADD COLUMN mileage_limit_per_day INT DEFAULT NULL AFTER mileage_limit_enabled",
            'mileage_over_fee' => "ALTER TABLE cars ADD COLUMN mileage_over_fee DECIMAL(10,2) DEFAULT NULL AFTER mileage_limit_per_day",
            'rental_terms' => "ALTER TABLE cars ADD COLUMN rental_terms TEXT DEFAULT NULL AFTER mileage_over_fee",
            'gallery_images' => "ALTER TABLE cars ADD COLUMN gallery_images TEXT DEFAULT NULL AFTER image"
        ];

        $checkStmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'cars' AND COLUMN_NAME = ?
        ");

        foreach ($columns as $column => $alterSql) {
            $checkStmt->bind_param("ss", $dbName, $column);
            $checkStmt->execute();
            $result = $checkStmt->get_result()->fetch_assoc();
            if ((int)$result['total'] === 0) {
                $conn->query($alterSql);
            }
        }

        $checkStmt->close();
        $ensured = true;
    }
}

ensureCarExtendedColumns($conn);

$base_path = getBasePath();
$error = '';
$success = '';
$owner_id = $_SESSION['user_id'];
$initial_step = max(1, min(3, intval($_POST['wizard_step'] ?? 1)));

$transmission_options = [
    'automatic' => 'Số tự động',
    'manual' => 'Số sàn'
];

$fuel_type_options = [
    'gasoline' => 'Xăng',
    'diesel' => 'Dầu',
    'electric' => 'Điện',
    'hybrid' => 'Hybrid'
];

$car_feature_catalog = [
    ['key' => 'navigation', 'label' => 'Bản đồ', 'icon' => 'map'],
    ['key' => 'bluetooth', 'label' => 'Bluetooth', 'icon' => 'bluetooth'],
    ['key' => 'camera360', 'label' => 'Camera 360', 'icon' => 'videocam'],
    ['key' => 'curb_camera', 'label' => 'Camera cập lề', 'icon' => 'switch_video'],
    ['key' => 'dashcam', 'label' => 'Camera hành trình', 'icon' => 'video_camera_front'],
    ['key' => 'rear_camera', 'label' => 'Camera lùi', 'icon' => 'camera_rear'],
    ['key' => 'tire_sensor', 'label' => 'Cảm biến lốp', 'icon' => 'sensors'],
    ['key' => 'collision_sensor', 'label' => 'Cảm biến va chạm', 'icon' => 'safety_check'],
    ['key' => 'speed_warning', 'label' => 'Cảnh báo tốc', 'icon' => 'speed'],
    ['key' => 'sunroof', 'label' => 'Cửa sổ trời', 'icon' => 'roofing'],
    ['key' => 'gps', 'label' => 'Định vị GPS', 'icon' => 'location_searching'],
    ['key' => 'leather_seat', 'label' => 'Ghế da', 'icon' => 'event_seat'],
    ['key' => 'usb_port', 'label' => 'Khe cắm USB', 'icon' => 'usb'],
    ['key' => 'spare_tire', 'label' => 'Lốp dự phòng', 'icon' => 'donut_small'],
    ['key' => 'dvd_screen', 'label' => 'Màn hình DVD', 'icon' => 'smart_display'],
    ['key' => 'etc_card', 'label' => 'ETC', 'icon' => 'credit_card'],
    ['key' => 'airbag', 'label' => 'Túi khí an toàn', 'icon' => 'airbag']
];

$feature_keys = array_column($car_feature_catalog, 'key');
$selected_features = [];
$discount_enabled = 0;
$discount_percent = 0;
$car_address = '';
$delivery_enabled = 0;
$delivery_distance_km = 10;
$delivery_fee = '';
$mileage_limit_enabled = 0;
$mileage_limit_per_day = 300;
$mileage_over_fee = '';
$rental_terms = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $license_plate = strtoupper(trim($_POST['license_plate'] ?? ''));
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $seats = (int)($_POST['seats'] ?? 4);
    $production_year = (int)($_POST['production_year'] ?? date('Y'));
    $transmission = $_POST['transmission'] ?? 'automatic';
    $fuel_type = $_POST['fuel_type'] ?? 'gasoline';
    $fuel_consumption = trim($_POST['fuel_consumption'] ?? '');
    $selected_features = array_values(array_intersect($feature_keys, (array)($_POST['features'] ?? [])));
    $discount_enabled = isset($_POST['discount_enabled']) ? 1 : 0;
    $discount_percent = $discount_enabled ? max(0, min(20, floatval($_POST['discount_percent'] ?? 0))) : 0;
    $car_address = trim($_POST['car_address'] ?? '');
    $delivery_enabled = isset($_POST['delivery_enabled']) ? 1 : 0;
    $delivery_distance_km = $delivery_enabled ? max(5, min(50, (int)($_POST['delivery_distance_km'] ?? 10))) : 10;
    $delivery_fee = $delivery_enabled ? max(0, floatval($_POST['delivery_fee'] ?? 0)) : '';
    $mileage_limit_enabled = isset($_POST['mileage_limit_enabled']) ? 1 : 0;
    $mileage_limit_per_day = $mileage_limit_enabled ? max(100, min(500, (int)($_POST['mileage_limit_per_day'] ?? 300))) : 300;
    $mileage_over_fee = $mileage_limit_enabled ? max(0, floatval($_POST['mileage_over_fee'] ?? 0)) : '';
    $rental_terms = trim($_POST['rental_terms'] ?? '');

    $db_delivery_distance_km = $delivery_enabled ? $delivery_distance_km : null;
    $db_delivery_fee = $delivery_enabled ? $delivery_fee : null;
    $db_mileage_limit_per_day = $mileage_limit_enabled ? $mileage_limit_per_day : null;
    $db_mileage_over_fee = $mileage_limit_enabled ? $mileage_over_fee : null;

    if (!array_key_exists($transmission, $transmission_options)) {
        $transmission = 'automatic';
    }
    if (!array_key_exists($fuel_type, $fuel_type_options)) {
        $fuel_type = 'gasoline';
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '' && ($brand !== '' || $model !== '')) {
        $name = trim($brand . ' ' . $model);
    }

    $car_type = $_POST['car_type'] ?? '';
    $rental_type = $_POST['rental_type'] ?? '';
    $location = $_POST['location'] ?? 'hcm';
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'available';

    if (
        $license_plate === '' ||
        strlen($license_plate) < 5 ||
        $brand === '' ||
        $model === '' ||
        $seats <= 0 ||
        $production_year < 1990 ||
        $name === '' ||
        $car_type === '' ||
        $rental_type === '' ||
        $location === '' ||
        $price_per_day <= 0
    ) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } else {
        $image = '';
        $gallery_files = [];
        if (isset($_FILES['car_images']) && is_array($_FILES['car_images']['name']) && $_FILES['car_images']['name'][0] !== '') {
            $file_count = count($_FILES['car_images']['name']);
            if ($file_count > 6) {
                $error = 'Vui lòng chọn tối đa 6 hình ảnh.';
            } else {
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['car_images']['error'][$i] !== UPLOAD_ERR_OK) {
                        if ($_FILES['car_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        $error = 'Có lỗi khi tải ảnh lên. Vui lòng thử lại.';
                        break;
                    }

                    $singleFile = [
                        'name' => $_FILES['car_images']['name'][$i],
                        'type' => $_FILES['car_images']['type'][$i],
                        'tmp_name' => $_FILES['car_images']['tmp_name'][$i],
                        'error' => $_FILES['car_images']['error'][$i],
                        'size' => $_FILES['car_images']['size'][$i],
                    ];

                    $uploaded = uploadFile($singleFile, '../uploads/');
                    if (!$uploaded) {
                        $error = 'Upload ảnh thất bại. Chỉ nhận JPG, PNG, GIF, WEBP tối đa 5MB.';
                        break;
                    }

                    if ($image === '') {
                        $image = $uploaded;
                    } else {
                        $gallery_files[] = $uploaded;
                    }
                }
            }
        }

        if ($image === '' && $error === '') {
            $error = 'Vui lòng tải lên ít nhất một hình ảnh.';
        }

        if ($error === '') {
            $features_json = json_encode($selected_features, JSON_UNESCAPED_UNICODE);
            $gallery_json = !empty($gallery_files) ? json_encode($gallery_files, JSON_UNESCAPED_UNICODE) : null;
            $stmt = $conn->prepare("INSERT INTO cars (
                    owner_id, license_plate, brand, model, seats, production_year, transmission, fuel_type, fuel_consumption, features,
                    name, description, image, gallery_images, price_per_day, car_type, rental_type, location, status,
                    discount_enabled, discount_percent, car_address,
                    delivery_enabled, delivery_distance_km, delivery_fee,
                    mileage_limit_enabled, mileage_limit_per_day, mileage_over_fee,
                    rental_terms
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "isssiissssssssdssssidsiidiids",
                $owner_id,
                $license_plate,
                $brand,
                $model,
                $seats,
                $production_year,
                $transmission,
                $fuel_type,
                $fuel_consumption,
                $features_json,
                $name,
                $description,
                $image,
                $gallery_json,
                $price_per_day,
                $car_type,
                $rental_type,
                $location,
                $status,
                $discount_enabled,
                $discount_percent,
                $car_address,
                $delivery_enabled,
                $db_delivery_distance_km,
                $db_delivery_fee,
                $mileage_limit_enabled,
                $db_mileage_limit_per_day,
                $db_mileage_over_fee,
                $rental_terms
            );

            if ($stmt->execute()) {
                $success = 'Xe của bạn đã được đăng!';
                $_POST = [];
                $selected_features = [];
                $discount_enabled = $delivery_enabled = $mileage_limit_enabled = 0;
                $discount_percent = 0;
                $car_address = '';
                $delivery_distance_km = 10;
                $delivery_fee = '';
                $mileage_limit_per_day = 300;
                $mileage_over_fee = '';
                $rental_terms = '';
                $initial_step = 1;
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

    <main class="bg-[#f4f5f7] dark:bg-background-dark px-4 sm:px-6 lg:px-10 py-10">
        <div class="max-w-5xl mx-auto space-y-8">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <a href="<?php echo $base_path ? $base_path . '/' : '/'; ?>" class="hover:text-primary font-medium">Trang chủ</a>
                    <span>›</span>
                    <a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'dashboard.php'; ?>" class="hover:text-primary font-medium">Trở thành chủ xe</a>
                    <span>›</span>
                    <span class="text-gray-700 dark:text-gray-200 font-semibold">Đăng xe mới</span>
                </div>
                <div class="space-y-3">
                    <p class="text-primary text-sm font-semibold uppercase tracking-[0.3em]">Đăng xe</p>
                    <h1 class="text-3xl sm:text-4xl font-black text-[#0f172a] dark:text-white leading-tight">
                        Chia sẻ chiếc xe của bạn và bắt đầu kiếm tiền
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-base max-w-3xl">
                        Hoàn thành các bước bên dưới để xe của bạn xuất hiện trên CarRental. Chúng tôi khuyến khích mô tả chi tiết,
                        hình ảnh rõ nét và mức giá cạnh tranh để tăng tỷ lệ được đặt.
                    </p>
                </div>
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

            <div class="bg-white dark:bg-gray-900/60 border border-border-color dark:border-gray-800 rounded-[32px] shadow-lg shadow-gray-200/40 dark:shadow-black/20">
                <div class="border-b border-border-color dark:border-gray-800 px-6 sm:px-10 py-6">
                    <div class="flex flex-wrap items-center gap-4 text-sm font-semibold">
                        <?php
                        $wizard_steps = [
                            ['title' => 'Thông tin', 'subtitle' => 'Biển số & thông số'],
                            ['title' => 'Cho thuê', 'subtitle' => 'Giá & khu vực'],
                            ['title' => 'Hình ảnh', 'subtitle' => 'Album xe'],
                        ];
                        foreach ($wizard_steps as $index => $step):
                            $step_number = $index + 1;
                            $isActive = $initial_step === $step_number;
                        ?>
                            <div class="progress-step flex items-center gap-3 <?php echo $isActive ? 'text-primary' : 'text-gray-400'; ?>" data-progress-step="<?php echo $step_number; ?>">
                                <div class="progress-dot flex items-center justify-center rounded-full w-10 h-10 text-sm font-bold border <?php echo $isActive ? 'border-primary bg-primary text-white' : 'border-gray-200 bg-white text-gray-500'; ?>">
                                    <?php echo $step_number; ?>
                                </div>
                                <div>
                                    <p class="text-sm <?php echo $isActive ? 'text-primary' : 'text-gray-500'; ?>"><?php echo $step['title']; ?></p>
                                    <p class="text-xs text-gray-400"><?php echo $step['subtitle']; ?></p>
                                </div>
                            </div>
                            <?php if ($index < count($wizard_steps) - 1): ?>
                                <span class="progress-connector hidden sm:block flex-1 h-px <?php echo $initial_step > $step_number ? 'bg-primary' : 'bg-gray-200'; ?>" data-progress-connector="<?php echo $step_number; ?>"></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-6 sm:p-10 space-y-12" data-wizard-form data-initial-step="<?php echo $initial_step; ?>">
                    <input type="hidden" name="wizard_step" value="<?php echo $initial_step; ?>" id="wizard-step-input">
                    <section class="space-y-6 step-panel <?php echo $initial_step === 1 ? '' : 'hidden'; ?>" data-step="1">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-primary uppercase tracking-wide">Bước 1 · Thông tin xe</p>
                            <h2 class="text-2xl font-bold text-[#0f172a] dark:text-white">Biển số xe</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Lưu ý: Biển số xe sẽ được xác minh và không thể thay đổi sau khi đăng.</p>
                        </div>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Biển số *</span>
                            <input type="text" name="license_plate" required value="<?php echo htmlspecialchars($_POST['license_plate'] ?? ''); ?>"
                                   placeholder="93A-345.43"
                                   class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-lg font-semibold uppercase tracking-wide focus:border-primary focus:ring-primary/30 shadow-sm">
                        </label>
                    </section>

                    <section class="space-y-6 step-panel <?php echo $initial_step === 1 ? '' : 'hidden'; ?>" data-step="1">
                        <div class="space-y-2">
                            <h3 class="text-xl font-bold text-[#0f172a] dark:text-white">Thông tin cơ bản</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Những thông tin này giúp khách hình dung rõ chiếc xe của bạn.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Hãng xe *</span>
                                <input type="text" name="brand" required value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>"
                                       placeholder="Ví dụ: Audi"
                                       class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mẫu xe *</span>
                                <input type="text" name="model" required value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>"
                                       placeholder="Ví dụ: A1 Hatchback"
                                       class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Số ghế *</span>
                                <input type="number" min="2" max="45" name="seats" required value="<?php echo htmlspecialchars($_POST['seats'] ?? '4'); ?>"
                                       class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Năm sản xuất *</span>
                                <input type="number" min="1990" max="<?php echo date('Y') + 1; ?>" name="production_year" required value="<?php echo htmlspecialchars($_POST['production_year'] ?? date('Y')); ?>"
                                       class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Truyền động *</span>
                                <select name="transmission" required class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                                    <?php foreach ($transmission_options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (($_POST['transmission'] ?? 'automatic') === $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Loại nhiên liệu *</span>
                                <select name="fuel_type" required class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                                    <?php foreach ($fuel_type_options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (($_POST['fuel_type'] ?? 'gasoline') === $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mức tiêu thụ nhiên liệu (L/100km)</span>
                                <input type="number" min="1" step="0.1" name="fuel_consumption" value="<?php echo htmlspecialchars($_POST['fuel_consumption'] ?? ''); ?>"
                                       placeholder="Ví dụ: 7.5"
                                       class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tên xe hiển thị</span>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="Ví dụ: Audi A1 Hatchback 2025"
                                       class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Nếu để trống, hệ thống sẽ tự ghép từ hãng + mẫu xe.</span>
                            </label>
                        </div>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mô tả *</span>
                            <textarea name="description" rows="4" required placeholder="Ghi rõ tình trạng xe, tiện nghi, quy định giao nhận..."
                                class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </label>
                    </section>

                    <section class="space-y-4 step-panel <?php echo $initial_step === 1 ? '' : 'hidden'; ?>" data-step="1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-[#0f172a] dark:text-white">Tính năng</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Đánh dấu các tiện nghi nổi bật để thu hút khách.</p>
                            </div>
                            <span class="text-xs text-gray-400">Chọn tối đa 8 mục</span>
                        </div>
                        <p id="feature-limit-message" class="text-xs text-red-500 hidden">Bạn chỉ có thể chọn tối đa 8 tính năng.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($car_feature_catalog as $feature):
                                $isSelected = in_array($feature['key'], $selected_features, true);
                            ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="features[]" value="<?php echo $feature['key']; ?>" class="sr-only peer" <?php echo $isSelected ? 'checked' : ''; ?>>
                                    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 px-4 py-3 flex items-center gap-3 shadow-sm transition peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary">
                                        <span class="material-symbols-outlined text-xl"><?php echo $feature['icon']; ?></span>
                                        <span class="font-medium text-sm"><?php echo $feature['label']; ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="space-y-6 step-panel <?php echo $initial_step === 2 ? '' : 'hidden'; ?>" data-step="2">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-primary uppercase tracking-wide">Bước 2 · Cho thuê</p>
                            <h3 class="text-2xl font-bold text-[#0f172a] dark:text-white">Thiết lập dịch vụ</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Loại dịch vụ *</span>
                                <select name="rental_type" required class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                                    <option value="">Chọn loại dịch vụ</option>
                                    <option value="self-drive" <?php echo (($_POST['rental_type'] ?? '') === 'self-drive') ? 'selected' : ''; ?>>Xe tự lái</option>
                                    <option value="with-driver" <?php echo (($_POST['rental_type'] ?? '') === 'with-driver') ? 'selected' : ''; ?>>Xe có tài xế</option>
                                    <option value="long-term" <?php echo (($_POST['rental_type'] ?? '') === 'long-term') ? 'selected' : ''; ?>>Thuê dài hạn</option>
                                </select>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Loại xe *</span>
                                <select name="car_type" required class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
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
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Địa điểm giao nhận *</span>
                                <select name="location" required class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
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
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Trạng thái *</span>
                                <select name="status" class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                                    <option value="available" <?php echo (($_POST['status'] ?? 'available') === 'available') ? 'selected' : ''; ?>>Còn xe</option>
                                    <option value="maintenance" <?php echo (($_POST['status'] ?? '') === 'maintenance') ? 'selected' : ''; ?>>Bảo trì</option>
                                </select>
                            </label>
                        </div>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Giá thuê (VNĐ) *</span>
                            <input type="number" step="1000" min="0" name="price_per_day" required value="<?php echo htmlspecialchars($_POST['price_per_day'] ?? ''); ?>"
                                   class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm"
                                   placeholder="<?php echo ($_POST['rental_type'] ?? '') === 'long-term' ? 'Ví dụ: 8.000.000 (theo tháng)' : 'Ví dụ: 500.000 (theo ngày)'; ?>">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Bạn có thể thay đổi giá bất cứ lúc nào trong trang quản lý.</span>
                        </label>
                        <div class="rounded-2xl border border-border-color dark:border-gray-800 p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">Giảm giá</p>
                                    <p class="text-xs text-gray-500">Kích hoạt giảm giá để tăng tỷ lệ đặt xe.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="discount_enabled" class="sr-only peer" data-toggle-input data-toggle-target="#discount-section" <?php echo $discount_enabled ? 'checked' : ''; ?>>
                                    <span class="w-12 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-primary transition"></span>
                                    <span class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-6 shadow"></span>
                                </label>
                            </div>
                            <div id="discount-section" class="<?php echo $discount_enabled ? '' : 'hidden'; ?>">
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Giảm giá tối đa 20%</span>
                                    <div class="flex items-center gap-4">
                                        <input type="range" min="0" max="20" step="1" name="discount_percent" value="<?php echo htmlspecialchars($discount_percent); ?>"
                                               class="flex-1 accent-primary">
                                        <span class="text-lg font-semibold text-primary" id="discount-value"><?php echo htmlspecialchars($discount_percent); ?>%</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Tối ưu nhận xe - địa chỉ giao/nhận</span>
                            <input type="text" name="car_address" value="<?php echo htmlspecialchars($car_address); ?>" placeholder="Nhập địa chỉ đón trả cố định của xe"
                                   class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                        </label>

                        <div class="rounded-2xl border border-border-color dark:border-gray-800 p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">Giao xe tận nơi</p>
                                    <p class="text-xs text-gray-500">Hỗ trợ giao xe trong bán kính định sẵn.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="delivery_enabled" class="sr-only peer" data-toggle-input data-toggle-target="#delivery-section" <?php echo $delivery_enabled ? 'checked' : ''; ?>>
                                    <span class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-primary transition"></span>
                                    <span class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-6 shadow"></span>
                                </label>
                            </div>
                            <div id="delivery-section" class="space-y-4 <?php echo $delivery_enabled ? '' : 'hidden'; ?>">
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Quãng đường giao xe tối đa (km)</span>
                                    <div class="flex items-center gap-4">
                                        <input type="range" min="5" max="50" step="1" name="delivery_distance_km" value="<?php echo htmlspecialchars($delivery_distance_km ?? 10); ?>"
                                               class="flex-1 accent-primary">
                                        <span class="text-base font-semibold text-primary" id="delivery-distance-label"><?php echo htmlspecialchars($delivery_distance_km ?? 10); ?> km</span>
                                    </div>
                                </label>
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Phí giao nhận (VNĐ/chuyến)</span>
                                    <input type="number" min="0" step="1000" name="delivery_fee" value="<?php echo htmlspecialchars($delivery_fee ?? ''); ?>" class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-border-color dark:border-gray-800 p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">Giới hạn số km</p>
                                    <p class="text-xs text-gray-500">Cài đặt giới hạn để quản lý hao mòn xe.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="mileage_limit_enabled" class="sr-only peer" data-toggle-input data-toggle-target="#mileage-section" <?php echo $mileage_limit_enabled ? 'checked' : ''; ?>>
                                    <span class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-primary transition"></span>
                                    <span class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-6 shadow"></span>
                                </label>
                            </div>
                            <div id="mileage-section" class="space-y-4 <?php echo $mileage_limit_enabled ? '' : 'hidden'; ?>">
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Số km mỗi ngày</span>
                                    <div class="flex items-center gap-4">
                                        <input type="range" min="100" max="500" step="10" name="mileage_limit_per_day" value="<?php echo htmlspecialchars($mileage_limit_per_day ?? 300); ?>"
                                               class="flex-1 accent-primary">
                                        <span class="text-base font-semibold text-primary" id="mileage-limit-label"><?php echo htmlspecialchars($mileage_limit_per_day ?? 300); ?> km</span>
                                    </div>
                                </label>
                                <label class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Phí vượt km (VNĐ/km)</span>
                                    <input type="number" min="0" step="500" name="mileage_over_fee" value="<?php echo htmlspecialchars($mileage_over_fee ?? ''); ?>"
                                           class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm">
                                </label>
                            </div>
                        </div>

                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Điều khoản thuê xe</span>
                            <textarea name="rental_terms" rows="4" placeholder="Nhập điều kiện sử dụng, phí phát sinh, yêu cầu giấy tờ..."
                                      class="rounded-2xl border border-border-color dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-base focus:border-primary focus:ring-primary/30 shadow-sm"><?php echo htmlspecialchars($rental_terms); ?></textarea>
                        </label>
                    </section>

                    <section class="space-y-6 step-panel <?php echo $initial_step === 3 ? '' : 'hidden'; ?>" data-step="3">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-primary uppercase tracking-wide">Bước 3 · Hình ảnh</p>
                            <h3 class="text-2xl font-bold text-[#0f172a] dark:text-white">Đăng hình xe của bạn</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tải tối đa 6 ảnh chất lượng cao để khách dễ tham khảo. Ảnh đầu tiên sẽ dùng làm ảnh đại diện.</p>
                        </div>
                        <div class="rounded-3xl border-2 border-dashed border-border-color dark:border-gray-700 bg-white dark:bg-gray-900/50 p-8 text-center space-y-4">
                            <span class="material-symbols-outlined text-5xl text-primary mx-auto">cloud_upload</span>
                            <div class="space-y-1">
                                <p class="text-lg font-semibold text-[#0f172a] dark:text-white">Kéo thả hoặc bấm để chọn hình</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Hỗ trợ JPG, PNG, GIF, WEBP · Tối đa 5MB/ảnh</p>
                            </div>
                            <label class="inline-flex items-center justify-center rounded-full bg-primary text-white px-8 py-3 font-semibold shadow-lg shadow-primary/40 hover:bg-[#ff7800] transition cursor-pointer">
                                Chọn hình
                                <input type="file" accept="image/*" multiple class="sr-only" id="car-images-picker">
                            </label>
                            <input type="file" name="car_images[]" accept="image/*" multiple class="hidden" id="car-images-input">
                            <p id="image-upload-feedback" class="text-xs text-gray-500 dark:text-gray-400"></p>
                            <div id="image-upload-list" class="grid grid-cols-1 sm:grid-cols-3 gap-3"></div>
                        </div>
                    </section>

                    <div class="flex flex-col sm:flex-row sm:justify-between gap-4 pt-6 border-t border-border-color dark:border-gray-800">
                        <button type="button" data-action="prev" class="inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-6 py-3 font-semibold text-gray-600 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition w-full sm:w-auto <?php echo $initial_step === 1 ? 'hidden' : ''; ?>">Quay lại</button>
                        <div class="flex flex-1 justify-end gap-3">
                            <a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'dashboard.php'; ?>" class="inline-flex items-center justify-center rounded-full border border-gray-200 text-gray-500 px-5 py-3 font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition w-full sm:w-auto">Hủy</a>
                            <button type="button" data-action="next" class="inline-flex items-center justify-center rounded-full bg-primary text-white px-8 py-3 font-semibold shadow-lg shadow-primary/40 hover:bg-[#ff7800] transition w-full sm:w-auto <?php echo $initial_step === 3 ? 'hidden' : ''; ?>">
                                Kế tiếp
                            </button>
                            <button type="submit" data-action="submit" class="inline-flex items-center justify-center rounded-full bg-primary text-white px-8 py-3 font-semibold shadow-lg shadow-primary/40 hover:bg-[#ff7800] transition w-full sm:w-auto <?php echo $initial_step === 3 ? '' : 'hidden'; ?>">
                                Đăng xe ngay
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const featureInputs = document.querySelectorAll('input[name="features[]"]');
            const warning = document.getElementById('feature-limit-message');
            const limit = 8;
            let warningTimeout = null;

            const showWarning = () => {
                if (!warning) return;
                warning.classList.remove('hidden');
                if (warningTimeout) {
                    clearTimeout(warningTimeout);
                }
                warningTimeout = setTimeout(() => {
                    warning.classList.add('hidden');
                }, 2200);
            };

            featureInputs.forEach(input => {
                input.addEventListener('change', () => {
                    const checkedCount = Array.from(featureInputs).filter(item => item.checked).length;
                    if (checkedCount > limit) {
                        input.checked = false;
                        showWarning();
                    }
                });
            });

            document.querySelectorAll('[data-toggle-input]').forEach(toggle => {
                const targetSelector = toggle.dataset.toggleTarget;
                if (!targetSelector) return;
                const target = document.querySelector(targetSelector);
                const updateVisibility = () => {
                    if (!target) return;
                    target.classList.toggle('hidden', !toggle.checked);
                };
                updateVisibility();
                toggle.addEventListener('change', updateVisibility);
            });

            const bindRangeLabel = (inputSelector, labelSelector, suffix = '') => {
                const input = document.querySelector(inputSelector);
                const label = document.querySelector(labelSelector);
                if (!input || !label) return;
                const update = () => {
                    label.textContent = `${input.value}${suffix}`;
                };
                input.addEventListener('input', update);
                update();
            };

            bindRangeLabel('input[name="discount_percent"]', '#discount-value', '%');
            bindRangeLabel('input[name="delivery_distance_km"]', '#delivery-distance-label', ' km');
            bindRangeLabel('input[name="mileage_limit_per_day"]', '#mileage-limit-label', ' km');

            const wizardForm = document.querySelector('[data-wizard-form]');
            if (wizardForm) {
                const stepPanels = Array.from(wizardForm.querySelectorAll('.step-panel'));
                const totalSteps = stepPanels.reduce((max, panel) => Math.max(max, Number(panel.dataset.step)), 1);
                let currentStep = Number(wizardForm.dataset.initialStep || 1);
                const prevBtn = wizardForm.querySelector('[data-action="prev"]');
                const nextBtn = wizardForm.querySelector('[data-action="next"]');
                const submitBtn = wizardForm.querySelector('[data-action="submit"]');
                const stepInput = document.getElementById('wizard-step-input');
                const progressSteps = document.querySelectorAll('[data-progress-step]');
                const connectors = document.querySelectorAll('[data-progress-connector]');

                const toggleStepPanels = () => {
                    stepPanels.forEach(panel => {
                        const stepNumber = Number(panel.dataset.step);
                        panel.classList.toggle('hidden', stepNumber !== currentStep);
                    });
                    if (prevBtn) prevBtn.classList.toggle('hidden', currentStep === 1);
                    if (nextBtn) nextBtn.classList.toggle('hidden', currentStep === totalSteps);
                    if (submitBtn) submitBtn.classList.toggle('hidden', currentStep !== totalSteps);
                    if (stepInput) stepInput.value = currentStep;

                    progressSteps.forEach(stepEl => {
                        const stepNumber = Number(stepEl.dataset.progressStep);
                        const dot = stepEl.querySelector('.progress-dot');
                        const isActive = stepNumber === currentStep;
                        const isCompleted = stepNumber < currentStep;

                        stepEl.classList.toggle('text-primary', isActive);
                        stepEl.classList.toggle('text-gray-400', !isActive);

                        if (dot) {
                            dot.classList.toggle('border-primary', isActive || isCompleted);
                            dot.classList.toggle('bg-primary', isActive || isCompleted);
                            dot.classList.toggle('text-white', isActive || isCompleted);
                            dot.classList.toggle('border-gray-200', !(isActive || isCompleted));
                            dot.classList.toggle('bg-white', !(isActive || isCompleted));
                            dot.classList.toggle('text-gray-500', !(isActive || isCompleted));
                        }
                    });

                    connectors.forEach(connector => {
                        const stepNumber = Number(connector.dataset.progressConnector);
                        connector.classList.toggle('bg-primary', currentStep > stepNumber);
                        connector.classList.toggle('bg-gray-200', currentStep <= stepNumber);
                    });
                };

                const validateStep = (step) => {
                    const panels = stepPanels.filter(panel => Number(panel.dataset.step) === step);
                    for (const panel of panels) {
                        const fields = panel.querySelectorAll('input, select, textarea');
                        for (const field of fields) {
                            if (field.disabled || field.type === 'hidden') {
                                continue;
                            }
                            if (!field.checkValidity()) {
                                field.reportValidity();
                                return false;
                            }
                        }
                    }
                    return true;
                };

                toggleStepPanels();

                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        if (!validateStep(currentStep)) {
                            return;
                        }
                        if (currentStep < totalSteps) {
                            currentStep += 1;
                            toggleStepPanels();
                        }
                    });
                }

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        if (currentStep > 1) {
                            currentStep -= 1;
                            toggleStepPanels();
                        }
                    });
                }

                wizardForm.addEventListener('submit', (event) => {
                    for (let step = 1; step <= totalSteps; step++) {
                        if (!validateStep(step)) {
                            event.preventDefault();
                            currentStep = step;
                            toggleStepPanels();
                            return;
                        }
                    }
                    if (stepInput) {
                        stepInput.value = totalSteps;
                    }
                });
            }

            const fileInput = document.getElementById('car-images-input');
            const filePicker = document.getElementById('car-images-picker');
            const feedbackEl = document.getElementById('image-upload-feedback');
            const listEl = document.getElementById('image-upload-list');
            const MAX_FILES = 6;
            let selectedFiles = [];

            const syncInputFiles = () => {
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(file => dataTransfer.items.add(file));
                if (fileInput) {
                    fileInput.files = dataTransfer.files;
                }
            };

            const renderFileList = () => {
                if (!listEl) return;
                listEl.innerHTML = '';
                if (!selectedFiles.length) {
                    if (feedbackEl) feedbackEl.textContent = 'Chưa có hình nào.';
                    return;
                }
                if (feedbackEl) {
                    feedbackEl.textContent = `${selectedFiles.length}/${MAX_FILES} hình đã chọn`;
                }
                selectedFiles.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative rounded-2xl overflow-hidden border border-gray-200 bg-white dark:bg-gray-800 shadow-sm';
                        wrapper.innerHTML = `
                            <img src="${event.target.result}" alt="${file.name}" class="w-full h-28 object-cover">
                            <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-[11px] text-white px-2 py-1 truncate">${file.name}</div>
                        `;
                        listEl.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                });
            };

            filePicker?.addEventListener('change', (event) => {
                const newFiles = Array.from(event.target.files || []);
                if (!newFiles.length) {
                    renderFileList();
                    return;
                }
                const remainingSlots = MAX_FILES - selectedFiles.length;
                const accepted = newFiles.slice(0, remainingSlots);
                selectedFiles = selectedFiles.concat(accepted);

                if (newFiles.length > accepted.length) {
                    alert(`Chỉ có thể chọn tối đa ${MAX_FILES} hình. ${newFiles.length - accepted.length} hình còn lại đã được bỏ qua.`);
                }

                syncInputFiles();
                renderFileList();

                // reset input so người dùng có thể chọn thêm
                filePicker.value = '';
            });

            renderFileList();
        });
    </script>
</body>
</html>

