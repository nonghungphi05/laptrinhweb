<?php
/**
 * Danh sách xe cho thuê
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

function updateQueryString(array $new_params): string {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return '?' . http_build_query($params);
}

$base_path = getBasePath();

$search = trim($_GET['search'] ?? '');
$location = $_GET['location'] ?? '';
$rental_type = $_GET['rental_type'] ?? '';
$car_type = trim($_GET['car_type'] ?? '');
$budget = $_GET['budget'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$conditions = ["c.status = 'available'"];
$params = [];
$types = "";

if ($search !== '') {
    $conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $param = "%$search%";
    $params[] = $param;
    $params[] = $param;
    $types .= "ss";
}

if ($location !== '') {
    $conditions[] = "c.location = ?";
    $params[] = $location;
    $types .= "s";
}

if ($rental_type !== '') {
    $conditions[] = "c.rental_type = ?";
    $params[] = $rental_type;
    $types .= "s";
}

if ($car_type !== '') {
    $conditions[] = "c.car_type = ?";
    $params[] = $car_type;
    $types .= "s";
}

if ($budget !== '') {
    switch ($budget) {
        case 'cheap':
            $conditions[] = "c.price_per_day < 500000";
            break;
        case 'mid':
            $conditions[] = "c.price_per_day BETWEEN 500000 AND 1000000";
            break;
        case 'premium':
            $conditions[] = "c.price_per_day > 1000000";
            break;
    }
}

$where_clause = '';
if (!empty($conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $conditions);
}

$count_sql = "SELECT COUNT(*) as total
              FROM cars c
              $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($types) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, ceil($total_rows / $per_page));
$count_stmt->close();

$data_sql = "SELECT c.*, u.full_name
             FROM cars c
             JOIN users u ON c.owner_id = u.id
             $where_clause
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);
$bind_params = $params;
$bind_types = $types . "ii";
$bind_params[] = $per_page;
$bind_params[] = $offset;
$data_stmt->bind_param($bind_types, ...$bind_params);
$data_stmt->execute();
$cars = $data_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$data_stmt->close();

// Lấy ds địa điểm
$locations = [
    'hcm'      => 'TP. Hồ Chí Minh',
    'hanoi'    => 'Hà Nội',
    'danang'   => 'Đà Nẵng',
    'cantho'   => 'Cần Thơ',
    'nhatrang' => 'Nha Trang',
    'dalat'    => 'Đà Lạt',
    'phuquoc'  => 'Phú Quốc'
];

// Nhãn hiển thị đồng bộ với form thêm xe & trang chi tiết
$car_type_labels = [
    'sedan'     => 'Sedan',
    'suv'       => 'SUV',
    'mpv'       => 'MPV',
    'pickup'    => 'Bán tải',
    'hatchback' => 'Hatchback',
    'van'       => 'Xe khách'
];

$rental_type_labels = [
    'self-drive' => 'Xe tự lái',
    'with-driver'=> 'Xe có tài xế',
    'long-term'  => 'Thuê dài hạn',
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Danh sách xe - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f98006",
                        "background-light": "#f8f7f5",
                        "background-dark": "#23190f",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
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
<body class="font-display bg-background-light dark:bg-background-dark text-[#181411] dark:text-white">
    <div class="min-h-screen flex flex-col">
        <?php include '../includes/header.php'; ?>

        <main class="flex-1 px-4 sm:px-6 lg:px-10 py-10">
            <div class="max-w-6xl mx-auto space-y-8">
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-semibold text-primary uppercase tracking-wide">Thuê xe</p>
                    <h1 class="text-3xl font-black text-[#0f172a] dark:text-white">Chọn chiếc xe phù hợp cho hành trình của bạn</h1>
                    <p class="text-gray-500 dark:text-gray-400">Bộ lọc thông minh giúp bạn tìm đúng chiếc xe với nhu cầu và ngân sách mong muốn.</p>
                </div>

                <form method="GET" class="bg-white dark:bg-gray-900/60 border border-[#e6e0db] dark:border-gray-800 rounded-2xl shadow-sm p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Từ khóa</span>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tên xe, mô tả..."
                                class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 focus:border-primary focus:ring-primary/30">
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Địa điểm</span>
                            <select name="location" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 focus:border-primary focus:ring-primary/30">
                                <option value="">Tất cả</option>
                                <?php foreach ($locations as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo $location === $code ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Loại dịch vụ</span>
                            <select name="rental_type" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 focus:border-primary focus:ring-primary/30">
                                <option value="">Tất cả</option>
                                <option value="self-drive" <?php echo $rental_type === 'self-drive' ? 'selected' : ''; ?>>Xe tự lái</option>
                                <option value="with-driver" <?php echo $rental_type === 'with-driver' ? 'selected' : ''; ?>>Xe có tài xế</option>
                                <option value="long-term" <?php echo $rental_type === 'long-term' ? 'selected' : ''; ?>>Thuê dài hạn</option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Loại xe</span>
                            <select name="car_type" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 focus:border-primary focus:ring-primary/30">
                                <option value="">Tất cả</option>
                                <?php
                                    $car_type_options = [
                                        'sedan' => 'Sedan',
                                        'suv' => 'SUV',
                                        'mpv' => 'MPV',
                                        'pickup' => 'Bán tải',
                                        'hatchback' => 'Hatchback',
                                        'van' => 'Xe khách'
                                    ];
                                ?>
                                <?php foreach ($car_type_options as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $car_type === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Ngân sách</span>
                            <select name="budget" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 focus:border-primary focus:ring-primary/30">
                                <option value="">Tất cả</option>
                                <option value="cheap" <?php echo $budget === 'cheap' ? 'selected' : ''; ?>>Dưới 500.000đ</option>
                                <option value="mid" <?php echo $budget === 'mid' ? 'selected' : ''; ?>>500.000đ - 1.000.000đ</option>
                                <option value="premium" <?php echo $budget === 'premium' ? 'selected' : ''; ?>>Trên 1.000.000đ</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-3 justify-end">
                        <a href="<?php echo $base_path ? $base_path . '/cars/index.php' : 'index.php'; ?>" class="px-5 py-2.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors">Xóa bộ lọc</a>
                        <button type="submit" class="px-6 py-2.5 rounded-full bg-primary text-white font-semibold hover:bg-primary/90 transition-colors">Tìm xe</button>
                    </div>
                </form>

                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tìm thấy <?php echo $total_rows; ?> xe <?php echo $location && isset($locations[$location]) ? 'tại ' . $locations[$location] : ''; ?>
                    </p>
                    <?php if ($total_pages > 1): ?>
                        <div class="text-sm text-gray-500">Trang <?php echo $page; ?> / <?php echo $total_pages; ?></div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($cars)): ?>
                        <div class="col-span-full text-center py-16">
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Không tìm thấy xe phù hợp. Vui lòng thử lại với bộ lọc khác.</p>
                            <?php if (isLoggedIn()): ?>
                                <a href="<?php echo $base_path ? $base_path . '/host/add-car.php' : '../host/add-car.php'; ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-white hover:bg-primary/90 transition-colors">
                                    <span class="material-symbols-outlined text-base">add_circle</span>
                                    Đăng xe của bạn
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cars as $car): ?>
                            <a href="<?php echo $base_path ? $base_path . '/client/car-detail.php?id=' . $car['id'] : '../client/car-detail.php?id=' . $car['id']; ?>"
                               class="bg-white dark:bg-gray-900/60 border border-[#e6e0db] dark:border-gray-800 rounded-2xl p-4 flex flex-col gap-3 hover:shadow-lg transition-shadow">
                                <div class="rounded-xl overflow-hidden aspect-video bg-gray-100">
                                    <img src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>"
                                         alt="<?php echo htmlspecialchars($car['name']); ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.src='<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : '../uploads/default-car.jpg'; ?>'">
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                        <?php echo htmlspecialchars($locations[$car['location']] ?? strtoupper($car['location'])); ?>
                                    </p>
                                    <h3 class="text-lg font-bold text-[#0f172a] dark:text-white"><?php echo htmlspecialchars($car['name']); ?></h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php
                                            $type_key = $car['car_type'];
                                            $rt_key   = $car['rental_type'];
                                            $type_label = $car_type_labels[$type_key] ?? ucfirst($type_key);
                                            $rt_label   = $rental_type_labels[$rt_key] ?? $rt_key;
                                            echo htmlspecialchars($type_label . ' • ' . $rt_label);
                                        ?>
                                    </p>
                                    <p class="text-primary font-bold text-xl mt-2"><?php echo number_format($car['price_per_day']); ?>đ<span class="text-sm text-gray-500">/ngày</span></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="flex items-center justify-center gap-2 pt-8">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo htmlspecialchars(updateQueryString(['page' => $page - 1])); ?>" class="px-4 py-2 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Trước</a>
                        <?php endif; ?>
                        <span class="px-4 py-2 rounded-full bg-primary text-white font-semibold">Trang <?php echo $page; ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo htmlspecialchars(updateQueryString(['page' => $page + 1])); ?>" class="px-4 py-2 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Sau</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>
</body>
</html>

