<?php
/**
 * Quản lý xe (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

$base_path = getBasePath();
$message = '';
$error = '';

// Xử lý thay đổi trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_status') {
        $car_id = intval($_POST['car_id']);
        $new_status = $_POST['new_status'];
        
        if (in_array($new_status, ['available', 'rented', 'maintenance', 'hidden'])) {
            $stmt = $conn->prepare("UPDATE cars SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $car_id);
            if ($stmt->execute()) {
                $message = 'Đã cập nhật trạng thái xe thành công!';
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}

// Xử lý xóa xe
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = intval($_GET['delete']);
    
    // Lấy thông tin xe để xóa ảnh
    $stmt = $conn->prepare("SELECT image FROM cars WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $car = $result->fetch_assoc();
        if ($car['image'] && file_exists('../uploads/' . $car['image'])) {
            unlink('../uploads/' . $car['image']);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = 'Đã xóa xe thành công!';
    }
    
    header('Location: cars.php?msg=' . urlencode($message));
    exit();
}

// Lọc
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');

$where_conditions = [];
$params = [];
$types = "";

if ($status_filter && in_array($status_filter, ['available', 'rented', 'maintenance', 'hidden'])) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($type_filter) {
    $where_conditions[] = "c.car_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($search !== '') {
    $where_conditions[] = "(c.name LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Lấy danh sách xe
$query = "SELECT c.*, u.full_name as owner_name, u.email as owner_email,
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id) as total_bookings,
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id AND status IN ('confirmed', 'completed')) as completed_bookings,
    (SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE car_id = c.id AND status IN ('confirmed', 'completed')) as total_revenue,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE car_id = c.id) as review_count
    FROM cars c
    JOIN users u ON c.owner_id = u.id
    $where_clause
    ORDER BY c.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
$cars = $result->fetch_all(MYSQLI_ASSOC);

// Thống kê
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM cars GROUP BY status");
$status_stats = [];
while ($row = $stmt->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}
$total_cars = array_sum($status_stats);

// Thống kê loại xe
$stmt = $conn->query("SELECT car_type, COUNT(*) as count FROM cars GROUP BY car_type ORDER BY count DESC");
$type_stats = $stmt->fetch_all(MYSQLI_ASSOC);

// Lấy message từ URL
if (isset($_GET['msg']) && $_GET['msg']) {
    $message = $_GET['msg'];
}

$car_type_labels = [
    'sedan' => 'Sedan',
    'suv' => 'SUV',
    'mpv' => 'MPV',
    'pickup' => 'Bán tải',
    'hatchback' => 'Hatchback',
    'van' => 'Xe khách'
];

$status_labels = [
    'available' => 'Sẵn sàng',
    'rented' => 'Đang thuê',
    'maintenance' => 'Bảo trì',
    'hidden' => 'Đã ẩn'
];

$status_colors = [
    'available' => 'bg-green-100 text-green-800',
    'rented' => 'bg-blue-100 text-blue-800',
    'maintenance' => 'bg-yellow-100 text-yellow-800',
    'hidden' => 'bg-gray-100 text-gray-800'
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Xe - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#f98006",
                        "background-light": "#F8FAFC",
                        "background-dark": "#18181B",
                    },
                    fontFamily: {
                        display: ["'Be Vietnam Pro'", "sans-serif"],
                    },
                },
            },
        };
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-white dark:bg-zinc-800 p-6 flex flex-col justify-between border-r border-slate-200 dark:border-zinc-700">
        <div>
            <a href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>" class="flex items-center gap-2 mb-10">
                <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
                </svg>
                <span class="text-xl font-bold text-primary">CarRental</span>
            </a>
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-4">Quản trị</p>
            <nav class="flex flex-col gap-1">
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="dashboard.php">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Tổng quan</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary font-semibold" href="cars.php">
                    <span class="material-symbols-outlined">directions_car</span>
                    <span>Quản lý xe</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="users.php">
                    <span class="material-symbols-outlined">group</span>
                    <span>Người dùng</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="bookings.php">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <span>Đơn đặt xe</span>
                </a>
            </nav>
        </div>
        <div class="space-y-1">
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>">
                <span class="material-symbols-outlined">home</span>
                <span>Về trang chủ</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400" href="<?php echo $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php'; ?>">
                <span class="material-symbols-outlined">logout</span>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto">
        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold">Quản lý xe</h1>
                <p class="text-slate-500 mt-1">Quản lý tất cả xe trong hệ thống</p>
            </div>
            <a href="<?php echo $base_path ? $base_path . '/host/add-car.php' : '../host/add-car.php'; ?>" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-orange-600 transition-colors">
                <span class="material-symbols-outlined">add</span>
                Thêm xe mới
            </a>
        </header>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <section class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <a href="?status=" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === '' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-slate-500">Tất cả</p>
                <p class="text-2xl font-bold"><?php echo $total_cars; ?></p>
            </a>
            <a href="?status=available" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'available' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-green-600">Sẵn sàng</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['available'] ?? 0; ?></p>
            </a>
            <a href="?status=rented" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'rented' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-blue-600">Đang thuê</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['rented'] ?? 0; ?></p>
            </a>
            <a href="?status=maintenance" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'maintenance' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-yellow-600">Bảo trì</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['maintenance'] ?? 0; ?></p>
            </a>
            <a href="?status=hidden" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'hidden' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-gray-600">Đã ẩn</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['hidden'] ?? 0; ?></p>
            </a>
        </section>

        <!-- Search & Filter -->
        <section class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200 dark:border-zinc-700 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Tìm theo tên xe, chủ xe..."
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    </div>
                </div>
                <select name="type" class="px-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <option value="">Tất cả loại xe</option>
                    <?php foreach ($car_type_labels as $type => $label): ?>
                        <option value="<?php echo $type; ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-orange-600">
                    Lọc
                </button>
                <?php if ($search || $status_filter || $type_filter): ?>
                    <a href="cars.php" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-100">
                        Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Cars Table -->
        <section class="bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-zinc-900">
                        <tr>
                            <th class="py-4 px-4 font-medium">Xe</th>
                            <th class="py-4 px-4 font-medium">Chủ xe</th>
                            <th class="py-4 px-4 font-medium">Loại</th>
                            <th class="py-4 px-4 font-medium">Giá/ngày</th>
                            <th class="py-4 px-4 font-medium">Trạng thái</th>
                            <th class="py-4 px-4 font-medium">Lượt thuê</th>
                            <th class="py-4 px-4 font-medium">Doanh thu</th>
                            <th class="py-4 px-4 font-medium">Đánh giá</th>
                            <th class="py-4 px-4 font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cars)): ?>
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-500">
                                    <span class="material-symbols-outlined text-4xl mb-2 block">directions_car</span>
                                    Không tìm thấy xe nào
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cars as $car): ?>
                                <tr class="border-t border-slate-100 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-900/50">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <img src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                                                 alt="" class="w-16 h-12 object-cover rounded-lg">
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($car['name']); ?></p>
                                                <p class="text-xs text-slate-500">#<?php echo $car['id']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-sm font-medium"><?php echo htmlspecialchars($car['owner_name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($car['owner_email']); ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-sm">
                                        <?php echo $car_type_labels[$car['car_type']] ?? $car['car_type']; ?>
                                    </td>
                                    <td class="py-4 px-4 font-medium text-primary">
                                        <?php echo formatCurrency($car['price_per_day']); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                            <select name="new_status" onchange="this.form.submit()" 
                                                    class="text-xs px-2 py-1 rounded-full border-0 cursor-pointer <?php echo $status_colors[$car['status']] ?? 'bg-gray-100'; ?>">
                                                <option value="available" <?php echo $car['status'] === 'available' ? 'selected' : ''; ?>>Sẵn sàng</option>
                                                <option value="rented" <?php echo $car['status'] === 'rented' ? 'selected' : ''; ?>>Đang thuê</option>
                                                <option value="maintenance" <?php echo $car['status'] === 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                                                <option value="hidden" <?php echo $car['status'] === 'hidden' ? 'selected' : ''; ?>>Đã ẩn</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="font-medium"><?php echo $car['completed_bookings']; ?></span>
                                        <span class="text-slate-400">/<?php echo $car['total_bookings']; ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-medium">
                                        <?php echo formatCurrency($car['total_revenue']); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($car['avg_rating']): ?>
                                            <span class="inline-flex items-center gap-1 text-yellow-600">
                                                <span class="material-symbols-outlined text-sm">star</span>
                                                <?php echo number_format($car['avg_rating'], 1); ?>
                                            </span>
                                            <span class="text-xs text-slate-400">(<?php echo $car['review_count']; ?>)</span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-sm">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-2">
                                            <a href="<?php echo $base_path ? $base_path . '/client/car-detail.php?id=' . $car['id'] : '../client/car-detail.php?id=' . $car['id']; ?>" 
                                               class="p-1.5 text-slate-500 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors" title="Xem chi tiết">
                                                <span class="material-symbols-outlined">visibility</span>
                                            </a>
                                            <a href="?delete=<?php echo $car['id']; ?>" 
                                               class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Xóa"
                                               onclick="return confirm('Bạn có chắc muốn xóa xe này?')">
                                                <span class="material-symbols-outlined">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
