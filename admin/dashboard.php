<?php
/**
 * Dashboard Admin - Quản trị tổng thể
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

$base_path = getBasePath();

// Lấy thống kê tổng quan
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM cars");
$total_cars = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE status IN ('confirmed', 'completed')");
$total_revenue = $stmt->fetch_assoc()['total'];

// Đơn đặt mới nhất
$stmt = $conn->query("SELECT b.*, c.name as car_name, u.full_name as customer_name
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.customer_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 10");
$recent_bookings = $stmt->fetch_all(MYSQLI_ASSOC);

// Xe mới nhất
$stmt = $conn->query("SELECT c.*, u.full_name as owner_name
    FROM cars c
    JOIN users u ON c.owner_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5");
$recent_cars = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Trang Quản Trị - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
    <style>
        .material-icons-outlined {
            font-size: inherit;
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
                    borderRadius: {
                        DEFAULT: "0.75rem",
                    },
                },
            },
        };
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-white dark:bg-zinc-800 p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-2 mb-10">
                <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
                </svg>
                <span class="text-xl font-bold text-primary">CarRental</span>
            </div>
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-4 py-2 rounded bg-primary/10 text-primary font-semibold" href="<?php echo $base_path ? $base_path . '/admin/dashboard.php' : 'dashboard.php'; ?>">
                    <span class="material-icons-outlined text-2xl">dashboard</span>
                    <span>Tổng quan</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/admin/cars.php' : 'cars.php'; ?>">
                    <span class="material-icons-outlined text-2xl">directions_car</span>
                    <span>Quản lý xe</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/admin/users.php' : 'users.php'; ?>">
                    <span class="material-icons-outlined text-2xl">group</span>
                    <span>Quản lý người dùng</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/admin/bookings.php' : 'bookings.php'; ?>">
                    <span class="material-icons-outlined text-2xl">book_online</span>
                    <span>Quản lý đơn đặt xe</span>
                </a>
            </nav>
        </div>
        <div>
            <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>">
                <span class="material-icons-outlined text-2xl">home</span>
                <span>Về trang chủ</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 mt-2" href="<?php echo $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php'; ?>">
                <span class="material-icons-outlined text-2xl">logout</span>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto">
        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Trang quản trị</h1>
            <div class="flex items-center gap-4">
                <button class="relative text-slate-500 dark:text-slate-400">
                    <span class="material-icons-outlined text-2xl">notifications</span>
                    <span class="absolute top-0 right-0 w-2 h-2 bg-primary rounded-full"></span>
                </button>
                <div class="flex items-center gap-3">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full w-10 h-10 bg-gray-300" 
                         style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?: $_SESSION['username']); ?>&background=f98006&color=fff");'></div>
                    <div>
                        <p class="font-semibold">Quản trị viên</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Cards -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded">
                        <span class="material-icons-outlined text-2xl text-blue-500">directions_car</span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tổng số xe</p>
                        <p class="text-2xl font-bold"><?php echo number_format($total_cars); ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded">
                        <span class="material-icons-outlined text-2xl text-green-500">group</span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Người dùng</p>
                        <p class="text-2xl font-bold"><?php echo number_format($total_users); ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded">
                        <span class="material-icons-outlined text-2xl text-yellow-500">route</span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Chuyến đi</p>
                        <p class="text-2xl font-bold"><?php echo number_format($total_bookings); ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded">
                        <span class="material-icons-outlined text-2xl text-primary">payments</span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Doanh thu (Tháng)</p>
                        <p class="text-2xl font-bold"><?php echo formatCurrency($total_revenue); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Danh sách xe -->
        <section class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Danh sách xe</h2>
                <a href="<?php echo $base_path ? $base_path . '/host/add-car.php' : '../host/add-car.php'; ?>" class="bg-primary text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-orange-600 transition-colors">
                    <span class="material-icons-outlined text-lg">add</span>
                    <span>Thêm xe mới</span>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="py-3 px-4 font-medium">Tên xe</th>
                            <th class="py-3 px-4 font-medium">Chủ xe</th>
                            <th class="py-3 px-4 font-medium">Trạng thái</th>
                            <th class="py-3 px-4 font-medium">Giá thuê/ngày</th>
                            <th class="py-3 px-4 font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_cars)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">Chưa có xe nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_cars as $car): ?>
                                <tr class="border-b border-slate-200 dark:border-zinc-700">
                                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($car['name']); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($car['owner_name'] ?? 'N/A'); ?></td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $status_classes = [
                                            'available' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                            'rented' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                            'maintenance' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300'
                                        ];
                                        $status_text = [
                                            'available' => 'Sẵn sàng',
                                            'rented' => 'Đang thuê',
                                            'maintenance' => 'Bảo trì'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_classes[$car['status']] ?? ''; ?>">
                                            <?php echo $status_text[$car['status']] ?? $car['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo formatCurrency($car['price_per_day']); ?></td>
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">
                                        <a href="<?php echo $base_path ? $base_path . '/admin/cars.php' : 'cars.php'; ?>" class="hover:text-primary">
                                            <span class="material-icons-outlined text-xl">edit</span>
                                        </a>
                                        <a href="<?php echo $base_path ? $base_path . '/admin/cars.php?delete=' . $car['id'] : 'cars.php?delete=' . $car['id']; ?>" class="hover:text-red-500 ml-2" onclick="return confirm('Bạn có chắc muốn xóa xe này?')">
                                            <span class="material-icons-outlined text-xl">delete</span>
                                        </a>
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
