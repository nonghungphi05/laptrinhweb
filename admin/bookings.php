<?php
/**
 * Quản lý đơn đặt (Admin)
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
        $booking_id = intval($_POST['booking_id']);
        $new_status = $_POST['new_status'];
        
        if (in_array($new_status, ['pending', 'confirmed', 'rejected', 'completed', 'cancelled'])) {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $booking_id);
            if ($stmt->execute()) {
                $message = 'Đã cập nhật trạng thái đơn đặt thành công!';
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
    
    if ($_POST['action'] === 'change_payment') {
        $booking_id = intval($_POST['booking_id']);
        $new_payment_status = $_POST['new_payment_status'];
        
        if (in_array($new_payment_status, ['pending', 'completed', 'failed', 'refunded'])) {
            // Kiểm tra xem payment đã tồn tại chưa
            $stmt = $conn->prepare("SELECT id FROM payments WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE booking_id = ?");
                $stmt->bind_param("si", $new_payment_status, $booking_id);
            } else {
                // Lấy total_price từ booking
                $stmt = $conn->prepare("SELECT total_price FROM bookings WHERE id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $booking = $stmt->get_result()->fetch_assoc();
                $amount = $booking['total_price'];
                
                $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (?, ?, 'admin', ?)");
                $stmt->bind_param("ids", $booking_id, $amount, $new_payment_status);
            }
            
            if ($stmt->execute()) {
                $message = 'Đã cập nhật trạng thái thanh toán thành công!';
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}

// Lọc
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$search = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = [];
$params = [];
$types = "";

if ($status_filter && in_array($status_filter, ['pending', 'confirmed', 'rejected', 'completed', 'cancelled'])) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($payment_filter && in_array($payment_filter, ['pending', 'completed', 'failed', 'refunded'])) {
    $where_conditions[] = "p.status = ?";
    $params[] = $payment_filter;
    $types .= "s";
}

if ($search !== '') {
    $where_conditions[] = "(c.name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($date_from) {
    $where_conditions[] = "b.start_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where_conditions[] = "b.end_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Lấy danh sách đơn đặt
$query = "SELECT b.*, 
    c.name as car_name, c.image as car_image,
    u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
    owner.full_name as owner_name,
    p.status as payment_status, p.payment_method, p.amount as payment_amount
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    JOIN users u ON b.customer_id = u.id
    JOIN users owner ON c.owner_id = owner.id
    LEFT JOIN payments p ON b.id = p.booking_id
    $where_clause
    ORDER BY b.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Thống kê trạng thái
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
$status_stats = [];
while ($row = $stmt->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}
$total_bookings = array_sum($status_stats);

// Thống kê thanh toán
$stmt = $conn->query("SELECT p.status, COUNT(*) as count FROM payments p GROUP BY p.status");
$payment_stats = [];
while ($row = $stmt->fetch_assoc()) {
    $payment_stats[$row['status']] = $row['count'];
}

// Tổng doanh thu
$stmt = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE status IN ('confirmed', 'completed')");
$total_revenue = $stmt->fetch_assoc()['total'];

// Lấy message từ URL
if (isset($_GET['msg']) && $_GET['msg']) {
    $message = $_GET['msg'];
}

$status_labels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'rejected' => 'Đã từ chối',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

$status_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'confirmed' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'completed' => 'bg-blue-100 text-blue-800',
    'cancelled' => 'bg-gray-100 text-gray-800'
];

$payment_labels = [
    'pending' => 'Chờ thanh toán',
    'completed' => 'Đã thanh toán',
    'failed' => 'Thất bại',
    'refunded' => 'Đã hoàn tiền'
];

$payment_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'completed' => 'bg-green-100 text-green-800',
    'failed' => 'bg-red-100 text-red-800',
    'refunded' => 'bg-purple-100 text-purple-800'
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Đơn Đặt Xe - CarRental</title>
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
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="cars.php">
                    <span class="material-symbols-outlined">directions_car</span>
                    <span>Quản lý xe</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="users.php">
                    <span class="material-symbols-outlined">group</span>
                    <span>Người dùng</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary font-semibold relative" href="bookings.php">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <span>Đơn đặt xe</span>
                    <?php if (($status_stats['pending'] ?? 0) > 0): ?>
                        <span class="absolute right-3 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $status_stats['pending']; ?></span>
                    <?php endif; ?>
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
                <h1 class="text-3xl font-bold">Quản lý đơn đặt xe</h1>
                <p class="text-slate-500 mt-1">Quản lý tất cả đơn đặt xe trong hệ thống</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500">Tổng doanh thu</p>
                <p class="text-2xl font-bold text-primary"><?php echo formatCurrency($total_revenue); ?></p>
            </div>
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
        <section class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
            <a href="?status=" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === '' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-slate-500">Tất cả</p>
                <p class="text-2xl font-bold"><?php echo $total_bookings; ?></p>
            </a>
            <a href="?status=pending" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'pending' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-yellow-600">Chờ xử lý</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['pending'] ?? 0; ?></p>
            </a>
            <a href="?status=confirmed" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'confirmed' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-green-600">Đã xác nhận</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['confirmed'] ?? 0; ?></p>
            </a>
            <a href="?status=completed" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'completed' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-blue-600">Hoàn thành</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['completed'] ?? 0; ?></p>
            </a>
            <a href="?status=cancelled" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'cancelled' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-gray-600">Đã hủy</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['cancelled'] ?? 0; ?></p>
            </a>
            <a href="?status=rejected" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'rejected' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-red-600">Từ chối</p>
                <p class="text-2xl font-bold"><?php echo $status_stats['rejected'] ?? 0; ?></p>
            </a>
        </section>

        <!-- Search & Filter -->
        <section class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200 dark:border-zinc-700 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Tìm theo tên xe, khách hàng..."
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    </div>
                </div>
                <select name="status" class="px-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($status_labels as $status => $label): ?>
                        <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="payment" class="px-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <option value="">Tất cả thanh toán</option>
                    <?php foreach ($payment_labels as $payment => $label): ?>
                        <option value="<?php echo $payment; ?>" <?php echo $payment_filter === $payment ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                       class="px-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900" placeholder="Từ ngày">
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                       class="px-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900" placeholder="Đến ngày">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-orange-600">
                    Lọc
                </button>
                <?php if ($search || $status_filter || $payment_filter || $date_from || $date_to): ?>
                    <a href="bookings.php" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-100">
                        Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Bookings Table -->
        <section class="bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-zinc-900">
                        <tr>
                            <th class="py-4 px-4 font-medium">Mã đơn</th>
                            <th class="py-4 px-4 font-medium">Xe</th>
                            <th class="py-4 px-4 font-medium">Khách hàng</th>
                            <th class="py-4 px-4 font-medium">Thời gian</th>
                            <th class="py-4 px-4 font-medium">Tổng tiền</th>
                            <th class="py-4 px-4 font-medium">Thanh toán</th>
                            <th class="py-4 px-4 font-medium">Trạng thái</th>
                            <th class="py-4 px-4 font-medium">Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    <span class="material-symbols-outlined text-4xl mb-2 block">receipt_long</span>
                                    Không tìm thấy đơn đặt nào
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr class="border-t border-slate-100 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-900/50">
                                    <td class="py-4 px-4">
                                        <span class="font-bold text-primary">#<?php echo $booking['id']; ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <img src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($booking['car_image'] ?: 'default-car.jpg'); ?>" 
                                                 alt="" class="w-12 h-9 object-cover rounded">
                                            <div>
                                                <p class="font-medium text-sm"><?php echo htmlspecialchars($booking['car_name']); ?></p>
                                                <p class="text-xs text-slate-500">Chủ xe: <?php echo htmlspecialchars($booking['owner_name']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="font-medium text-sm"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($booking['customer_email']); ?></p>
                                        <?php if ($booking['customer_phone']): ?>
                                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($booking['customer_phone']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-sm">
                                        <p><?php echo date('d/m/Y', strtotime($booking['start_date'])); ?></p>
                                        <p class="text-slate-500">đến <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?></p>
                                        <?php 
                                        $days = (strtotime($booking['end_date']) - strtotime($booking['start_date'])) / 86400 + 1;
                                        ?>
                                        <p class="text-xs text-primary"><?php echo $days; ?> ngày</p>
                                    </td>
                                    <td class="py-4 px-4 font-bold">
                                        <?php echo formatCurrency($booking['total_price']); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="change_payment">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <?php $payment_status = $booking['payment_status'] ?? 'pending'; ?>
                                            <select name="new_payment_status" onchange="this.form.submit()" 
                                                    class="text-xs px-2 py-1 rounded-full border-0 cursor-pointer <?php echo $payment_colors[$payment_status] ?? 'bg-gray-100'; ?>">
                                                <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                                                <option value="completed" <?php echo $payment_status === 'completed' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                                <option value="failed" <?php echo $payment_status === 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                                                <option value="refunded" <?php echo $payment_status === 'refunded' ? 'selected' : ''; ?>>Đã hoàn tiền</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <select name="new_status" onchange="this.form.submit()" 
                                                    class="text-xs px-2 py-1 rounded-full border-0 cursor-pointer <?php echo $status_colors[$booking['status']] ?? 'bg-gray-100'; ?>">
                                                <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                                <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                                <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                                <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                                <option value="rejected" <?php echo $booking['status'] === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-slate-500">
                                        <?php echo date('d/m/Y', strtotime($booking['created_at'])); ?>
                                        <br>
                                        <span class="text-xs"><?php echo date('H:i', strtotime($booking['created_at'])); ?></span>
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
