<?php
/**
 * Quản lý đơn đặt (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

$base_path = getBasePath();

// Lấy danh sách đơn đặt
$stmt = $conn->query("SELECT b.*, 
    c.name as car_name,
    u.full_name as customer_name, u.email as customer_email,
    p.status as payment_status
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN payments p ON b.id = p.booking_id
    ORDER BY b.created_at DESC");
$bookings = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Đơn Đặt Xe - CarRental</title>
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
            <a href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>" class="flex items-center gap-2 mb-10">
                <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
                </svg>
                <span class="text-xl font-bold text-primary">CarRental</span>
            </a>
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/admin/dashboard.php' : 'dashboard.php'; ?>">
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
                <a class="flex items-center gap-3 px-4 py-2 rounded bg-primary/10 text-primary font-semibold" href="<?php echo $base_path ? $base_path . '/admin/bookings.php' : 'bookings.php'; ?>">
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
            <h1 class="text-3xl font-bold">Quản lý đơn đặt xe</h1>
        </header>

        <!-- Bookings Table -->
        <section class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="py-3 px-4 font-medium">Mã đơn</th>
                            <th class="py-3 px-4 font-medium">Khách hàng</th>
                            <th class="py-3 px-4 font-medium">Xe</th>
                            <th class="py-3 px-4 font-medium">Ngày thuê</th>
                            <th class="py-3 px-4 font-medium">Tổng tiền</th>
                            <th class="py-3 px-4 font-medium">Thanh toán</th>
                            <th class="py-3 px-4 font-medium">Trạng thái</th>
                            <th class="py-3 px-4 font-medium">Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">Chưa có đơn đặt nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr class="border-b border-slate-200 dark:border-zinc-700">
                                    <td class="py-3 px-4 font-medium">#<?php echo $booking['id']; ?></td>
                                    <td class="py-3 px-4">
                                        <div class="text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                        <div class="text-xs text-slate-500"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($booking['car_name']); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300 text-sm">
                                        <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?><br>
                                        đến <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo formatCurrency($booking['total_price']); ?></td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $payment_classes = [
                                            'completed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                            'pending' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                            'failed' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300'
                                        ];
                                        $payment_text = [
                                            'completed' => 'Đã thanh toán',
                                            'pending' => 'Chờ thanh toán',
                                            'failed' => 'Thất bại'
                                        ];
                                        $payment_status = $booking['payment_status'] ?? 'pending';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $payment_classes[$payment_status] ?? ''; ?>">
                                            <?php echo $payment_text[$payment_status] ?? 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $status_classes = [
                                            'pending' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                            'confirmed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                            'rejected' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
                                            'completed' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
                                            'cancelled' => 'bg-slate-100 dark:bg-slate-900/50 text-slate-800 dark:text-slate-300'
                                        ];
                                        $status_text = [
                                            'pending' => 'Chờ xác nhận',
                                            'confirmed' => 'Đã xác nhận',
                                            'rejected' => 'Đã từ chối',
                                            'completed' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_classes[$booking['status']] ?? ''; ?>">
                                            <?php echo $status_text[$booking['status']] ?? $booking['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300 text-sm"><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
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
