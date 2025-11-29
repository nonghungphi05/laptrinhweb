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

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
$new_users_today = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM cars");
$total_cars = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM cars WHERE status = 'available'");
$available_cars = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
$pending_bookings = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'");
$active_bookings = $stmt->fetch_assoc()['total'];

// Doanh thu tháng này
$stmt = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings 
    WHERE status IN ('confirmed', 'completed') 
    AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$revenue_this_month = $stmt->fetch_assoc()['total'];

// Doanh thu tháng trước
$stmt = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings 
    WHERE status IN ('confirmed', 'completed') 
    AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
    AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$revenue_last_month = $stmt->fetch_assoc()['total'];

// Tính % thay đổi doanh thu
$revenue_change = 0;
if ($revenue_last_month > 0) {
    $revenue_change = round((($revenue_this_month - $revenue_last_month) / $revenue_last_month) * 100, 1);
}

// Doanh thu 7 ngày gần đây cho biểu đồ
$revenue_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings 
        WHERE status IN ('confirmed', 'completed') AND DATE(created_at) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $revenue = $stmt->get_result()->fetch_assoc()['total'];
    $revenue_chart_data[] = [
        'date' => date('d/m', strtotime($date)),
        'revenue' => $revenue
    ];
}

// Đơn đặt mới nhất cần xử lý
$stmt = $conn->query("SELECT b.*, c.name as car_name, c.image as car_image, u.full_name as customer_name, u.email as customer_email
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.customer_id = u.id
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC
    LIMIT 5");
$pending_orders = $stmt->fetch_all(MYSQLI_ASSOC);

// Đơn đặt gần đây nhất
$stmt = $conn->query("SELECT b.*, c.name as car_name, u.full_name as customer_name,
    p.status as payment_status
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN payments p ON b.id = p.booking_id
    ORDER BY b.created_at DESC
    LIMIT 10");
$recent_bookings = $stmt->fetch_all(MYSQLI_ASSOC);

// Người dùng mới nhất
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetch_all(MYSQLI_ASSOC);

// Xe được thuê nhiều nhất
$stmt = $conn->query("SELECT c.*, u.full_name as owner_name,
    COUNT(b.id) as booking_count,
    COALESCE(SUM(b.total_price), 0) as total_revenue
    FROM cars c
    JOIN users u ON c.owner_id = u.id
    LEFT JOIN bookings b ON c.id = b.car_id AND b.status IN ('confirmed', 'completed')
    GROUP BY c.id
    ORDER BY booking_count DESC
    LIMIT 5");
$top_cars = $stmt->fetch_all(MYSQLI_ASSOC);

// Thống kê theo loại xe
$stmt = $conn->query("SELECT car_type, COUNT(*) as count FROM cars GROUP BY car_type");
$car_type_stats = $stmt->fetch_all(MYSQLI_ASSOC);

// Thống kê booking theo trạng thái
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
$booking_stats = [];
while ($row = $stmt->fetch_assoc()) {
    $booking_stats[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Trang Quản Trị - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary font-semibold" href="dashboard.php">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Tổng quan</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="cars.php">
                    <span class="material-symbols-outlined">directions_car</span>
                    <span>Quản lý xe</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="users.php">
                    <span class="material-symbols-outlined">group</span>
                    <span>Người dùng</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300 relative" href="bookings.php">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <span>Đơn đặt xe</span>
                    <?php if ($pending_bookings > 0): ?>
                        <span class="absolute right-3 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $pending_bookings; ?></span>
                    <?php endif; ?>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="reviews.php">
                    <span class="material-symbols-outlined">star</span>
                    <span>Đánh giá</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="payouts.php">
                    <span class="material-symbols-outlined">payments</span>
                    <span>Rút tiền</span>
                </a>
            </nav>
            
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-4 mt-8">Báo cáo</p>
            <nav class="flex flex-col gap-1">
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="#revenue-section">
                    <span class="material-symbols-outlined">bar_chart</span>
                    <span>Doanh thu</span>
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
                <h1 class="text-3xl font-bold">Xin chào, <?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?>!</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Đây là tổng quan hoạt động của hệ thống hôm nay.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full w-10 h-10 bg-gray-300" 
                         style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?: $_SESSION['username']); ?>&background=f98006&color=fff");'></div>
                    <div>
                        <p class="font-semibold text-sm"><?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Quản trị viên</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Cards -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tổng người dùng</p>
                        <p class="text-3xl font-bold mt-1"><?php echo number_format($total_users); ?></p>
                        <?php if ($new_users_today > 0): ?>
                            <p class="text-xs text-green-600 mt-1">+<?php echo $new_users_today; ?> hôm nay</p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                        <span class="material-symbols-outlined text-3xl text-blue-500">group</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tổng số xe</p>
                        <p class="text-3xl font-bold mt-1"><?php echo number_format($total_cars); ?></p>
                        <p class="text-xs text-slate-500 mt-1"><?php echo $available_cars; ?> xe sẵn sàng</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
                        <span class="material-symbols-outlined text-3xl text-green-500">directions_car</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Đơn đặt xe</p>
                        <p class="text-3xl font-bold mt-1"><?php echo number_format($total_bookings); ?></p>
                        <?php if ($pending_bookings > 0): ?>
                            <p class="text-xs text-yellow-600 mt-1"><?php echo $pending_bookings; ?> chờ xử lý</p>
                        <?php else: ?>
                            <p class="text-xs text-green-600 mt-1">Đã xử lý hết</p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-xl">
                        <span class="material-symbols-outlined text-3xl text-yellow-600">receipt_long</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Doanh thu tháng</p>
                        <p class="text-3xl font-bold mt-1"><?php echo formatCurrency($revenue_this_month); ?></p>
                        <p class="text-xs mt-1 <?php echo $revenue_change >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $revenue_change >= 0 ? '+' : ''; ?><?php echo $revenue_change; ?>% so với tháng trước
                        </p>
                    </div>
                    <div class="p-3 bg-primary/20 rounded-xl">
                        <span class="material-symbols-outlined text-3xl text-primary">payments</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Charts & Pending Orders -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Revenue Chart -->
            <div id="revenue-section" class="lg:col-span-2 bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold">Doanh thu 7 ngày qua</h2>
                    <div class="text-sm text-slate-500">
                        Tổng: <span class="font-bold text-primary"><?php echo formatCurrency(array_sum(array_column($revenue_chart_data, 'revenue'))); ?></span>
                    </div>
                </div>
                <div style="height: 250px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <!-- Booking Status -->
            <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <h2 class="text-lg font-bold mb-6">Trạng thái đơn đặt</h2>
                <div style="height: 180px;">
                    <canvas id="bookingStatusChart"></canvas>
                </div>
                <div class="mt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500"></span>Chờ xử lý</span>
                        <span class="font-bold"><?php echo $booking_stats['pending'] ?? 0; ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500"></span>Đã xác nhận</span>
                        <span class="font-bold"><?php echo $booking_stats['confirmed'] ?? 0; ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500"></span>Hoàn thành</span>
                        <span class="font-bold"><?php echo $booking_stats['completed'] ?? 0; ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500"></span>Đã hủy</span>
                        <span class="font-bold"><?php echo $booking_stats['cancelled'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <?php if (!empty($pending_orders)): ?>
        <section class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700 mb-8">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold">Đơn đặt chờ xử lý</h2>
                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo count($pending_orders); ?></span>
                </div>
                <a href="bookings.php" class="text-primary hover:underline text-sm font-medium">Xem tất cả →</a>
            </div>
            <div class="space-y-4">
                <?php foreach ($pending_orders as $order): ?>
                    <div class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-center gap-4">
                            <img src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($order['car_image'] ?: 'default-car.jpg'); ?>" 
                                 alt="" class="w-16 h-12 object-cover rounded-lg">
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($order['car_name']); ?></p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Khách: <?php echo htmlspecialchars($order['customer_name']); ?> • 
                                    <?php echo date('d/m/Y', strtotime($order['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($order['end_date'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <p class="font-bold text-primary"><?php echo formatCurrency($order['total_price']); ?></p>
                            <a href="bookings.php" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-orange-600">
                                Xử lý
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Two Columns: Recent Bookings & Top Cars -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Recent Bookings -->
            <section class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold">Đơn đặt gần đây</h2>
                    <a href="bookings.php" class="text-primary hover:underline text-sm font-medium">Xem tất cả</a>
                </div>
                <div class="space-y-4">
                    <?php if (empty($recent_bookings)): ?>
                        <p class="text-center text-slate-500 py-8">Chưa có đơn đặt nào</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_bookings, 0, 5) as $booking): ?>
                            <div class="flex items-center justify-between py-3 border-b border-slate-100 dark:border-zinc-700 last:border-0">
                                <div>
                                    <p class="font-medium">#<?php echo $booking['id']; ?> - <?php echo htmlspecialchars($booking['car_name']); ?></p>
                                    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold"><?php echo formatCurrency($booking['total_price']); ?></p>
                                    <?php
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-blue-100 text-blue-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_texts = [
                                        'pending' => 'Chờ xử lý',
                                        'confirmed' => 'Đã xác nhận',
                                        'completed' => 'Hoàn thành',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    ?>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?php echo $status_colors[$booking['status']] ?? 'bg-gray-100'; ?>">
                                        <?php echo $status_texts[$booking['status']] ?? $booking['status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Top Cars -->
            <section class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold">Xe được thuê nhiều nhất</h2>
                    <a href="cars.php" class="text-primary hover:underline text-sm font-medium">Xem tất cả</a>
                </div>
                <div class="space-y-4">
                    <?php if (empty($top_cars)): ?>
                        <p class="text-center text-slate-500 py-8">Chưa có dữ liệu</p>
                    <?php else: ?>
                        <?php foreach ($top_cars as $index => $car): ?>
                            <div class="flex items-center justify-between py-3 border-b border-slate-100 dark:border-zinc-700 last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full bg-primary/20 text-primary text-xs font-bold flex items-center justify-center">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($car['name']); ?></p>
                                        <p class="text-sm text-slate-500"><?php echo $car['booking_count']; ?> lượt thuê</p>
                                    </div>
                                </div>
                                <p class="font-bold text-primary"><?php echo formatCurrency($car['total_revenue']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Recent Users -->
        <section class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-slate-200 dark:border-zinc-700">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-bold">Người dùng mới</h2>
                <a href="users.php" class="text-primary hover:underline text-sm font-medium">Xem tất cả</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="py-3 px-4 font-medium">Người dùng</th>
                            <th class="py-3 px-4 font-medium">Email</th>
                            <th class="py-3 px-4 font-medium">Số điện thoại</th>
                            <th class="py-3 px-4 font-medium">Vai trò</th>
                            <th class="py-3 px-4 font-medium">Ngày tham gia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr class="border-b border-slate-100 dark:border-zinc-700 last:border-0">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-sm">
                                            <?php echo strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td class="py-3 px-4">
                                    <?php
                                    $role_colors = [
                                        'admin' => 'bg-red-100 text-red-800',
                                        'host' => 'bg-blue-100 text-blue-800',
                                        'user' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $role_texts = ['admin' => 'Admin', 'host' => 'Chủ xe', 'user' => 'Người dùng'];
                                    ?>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?php echo $role_colors[$user['role']] ?? 'bg-gray-100'; ?>">
                                        <?php echo $role_texts[$user['role']] ?? $user['role']; ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($revenue_chart_data, 'date')); ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?php echo json_encode(array_column($revenue_chart_data, 'revenue')); ?>,
            backgroundColor: 'rgba(249, 128, 6, 0.8)',
            borderColor: 'rgba(249, 128, 6, 1)',
            borderWidth: 1,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                    }
                }
            }
        }
    }
});

// Booking Status Chart
const bookingCtx = document.getElementById('bookingStatusChart').getContext('2d');
new Chart(bookingCtx, {
    type: 'doughnut',
    data: {
        labels: ['Chờ xử lý', 'Đã xác nhận', 'Hoàn thành', 'Đã hủy'],
        datasets: [{
            data: [
                <?php echo $booking_stats['pending'] ?? 0; ?>,
                <?php echo $booking_stats['confirmed'] ?? 0; ?>,
                <?php echo $booking_stats['completed'] ?? 0; ?>,
                <?php echo $booking_stats['cancelled'] ?? 0; ?>
            ],
            backgroundColor: [
                '#EAB308',
                '#22C55E',
                '#3B82F6',
                '#EF4444'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        cutout: '70%'
    }
});
</script>
</body>
</html>
