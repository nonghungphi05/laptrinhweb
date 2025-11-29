<?php
/**
 * Quản lý yêu cầu rút tiền (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

$base_path = getBasePath();
$message = '';
$error = '';

// Xử lý hành động
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payout_id = intval($_POST['payout_id'] ?? 0);
    
    if ($payout_id > 0) {
        if ($_POST['action'] === 'approve') {
            $stmt = $conn->prepare("UPDATE payout_requests SET status = 'approved' WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("i", $payout_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = 'Đã duyệt yêu cầu rút tiền thành công!';
            } else {
                $error = 'Không thể duyệt yêu cầu này.';
            }
        } elseif ($_POST['action'] === 'reject') {
            $stmt = $conn->prepare("UPDATE payout_requests SET status = 'rejected' WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("i", $payout_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = 'Đã từ chối yêu cầu rút tiền.';
            } else {
                $error = 'Không thể từ chối yêu cầu này.';
            }
        }
    }
}

// Lọc
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where_conditions = [];
$params = [];
$types = "";

if ($status_filter && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($search !== '') {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR p.bank_name LIKE ? OR p.bank_account LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Lấy danh sách yêu cầu rút tiền
$query = "SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
    FROM payout_requests p
    JOIN users u ON p.owner_id = u.id
    $where_clause
    ORDER BY 
        CASE p.status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END,
        p.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
$payouts = $result->fetch_all(MYSQLI_ASSOC);

// Thống kê
$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'pending_amount' => 0,
    'approved_amount' => 0
];

$stats_query = $conn->query("SELECT status, COUNT(*) as count, SUM(amount) as total FROM payout_requests GROUP BY status");
while ($row = $stats_query->fetch_assoc()) {
    $stats[$row['status']] = (int)$row['count'];
    if ($row['status'] === 'pending') {
        $stats['pending_amount'] = (float)$row['total'];
    } elseif ($row['status'] === 'approved') {
        $stats['approved_amount'] = (float)$row['total'];
    }
}
$total_requests = $stats['pending'] + $stats['approved'] + $stats['rejected'];

$status_labels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Đã từ chối'
];

$status_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800'
];

?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Rút Tiền - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
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
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="bookings.php">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <span>Đơn đặt xe</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="reviews.php">
                    <span class="material-symbols-outlined">star</span>
                    <span>Đánh giá</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary font-semibold relative" href="payouts.php">
                    <span class="material-symbols-outlined">payments</span>
                    <span>Rút tiền</span>
                    <?php if ($stats['pending'] > 0): ?>
                        <span class="absolute right-3 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $stats['pending']; ?></span>
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
                <h1 class="text-3xl font-bold">Quản lý yêu cầu rút tiền</h1>
                <p class="text-slate-500 mt-1">Xem và xử lý các yêu cầu rút tiền từ chủ xe</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500">Tổng cần thanh toán</p>
                <p class="text-2xl font-bold text-primary"><?php echo number_format($stats['pending_amount']); ?> đ</p>
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
        <section class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <a href="?status=" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === '' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-slate-500">Tất cả</p>
                <p class="text-2xl font-bold"><?php echo $total_requests; ?></p>
            </a>
            <a href="?status=pending" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'pending' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-yellow-600 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">hourglass_top</span> Chờ duyệt
                </p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
            </a>
            <a href="?status=approved" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'approved' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-green-600">Đã duyệt</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['approved']; ?></p>
            </a>
            <a href="?status=rejected" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $status_filter === 'rejected' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-red-600">Đã từ chối</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $stats['rejected']; ?></p>
            </a>
            <div class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200">
                <p class="text-sm text-slate-500">Đã chi trả</p>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($stats['approved_amount']); ?>đ</p>
            </div>
        </section>

        <!-- Search -->
        <section class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200 dark:border-zinc-700 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <?php if ($status_filter): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php endif; ?>
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Tìm theo tên, email, ngân hàng..."
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-orange-600">
                    Tìm kiếm
                </button>
                <?php if ($search || $status_filter): ?>
                    <a href="payouts.php" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-100">
                        Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Payouts Table -->
        <section class="bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-zinc-900">
                        <tr>
                            <th class="py-4 px-4 font-medium">ID</th>
                            <th class="py-4 px-4 font-medium">Chủ xe</th>
                            <th class="py-4 px-4 font-medium">Số tiền</th>
                            <th class="py-4 px-4 font-medium">Ngân hàng</th>
                            <th class="py-4 px-4 font-medium">Số tài khoản</th>
                            <th class="py-4 px-4 font-medium">Ghi chú</th>
                            <th class="py-4 px-4 font-medium">Ngày yêu cầu</th>
                            <th class="py-4 px-4 font-medium">Trạng thái</th>
                            <th class="py-4 px-4 font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payouts)): ?>
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-500">
                                    <span class="material-symbols-outlined text-4xl mb-2 block">payments</span>
                                    Chưa có yêu cầu rút tiền nào
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payouts as $payout): ?>
                                <tr class="border-t border-slate-100 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-900/50 <?php echo $payout['status'] === 'pending' ? 'bg-yellow-50/50' : ''; ?>">
                                    <td class="py-4 px-4 font-semibold text-primary">#<?php echo $payout['id']; ?></td>
                                    <td class="py-4 px-4">
                                        <p class="font-medium"><?php echo htmlspecialchars($payout['owner_name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($payout['owner_email']); ?></p>
                                        <?php if ($payout['owner_phone']): ?>
                                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($payout['owner_phone']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-lg"><?php echo number_format($payout['amount']); ?> đ</td>
                                    <td class="py-4 px-4"><?php echo htmlspecialchars($payout['bank_name']); ?></td>
                                    <td class="py-4 px-4 font-mono text-sm"><?php echo htmlspecialchars($payout['bank_account']); ?></td>
                                    <td class="py-4 px-4 text-sm text-slate-500 max-w-[200px] truncate" title="<?php echo htmlspecialchars($payout['note']); ?>">
                                        <?php echo htmlspecialchars($payout['note'] ?: '—'); ?>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-slate-500">
                                        <?php echo date('d/m/Y', strtotime($payout['created_at'])); ?>
                                        <br>
                                        <span class="text-xs"><?php echo date('H:i', strtotime($payout['created_at'])); ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $status_colors[$payout['status']] ?? 'bg-gray-100'; ?>">
                                            <?php echo $status_labels[$payout['status']] ?? $payout['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($payout['status'] === 'pending'): ?>
                                            <div class="flex gap-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="payout_id" value="<?php echo $payout['id']; ?>">
                                                    <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 flex items-center gap-1" onclick="return confirm('Xác nhận đã chuyển tiền cho chủ xe?')">
                                                        <span class="material-symbols-outlined text-base">check</span> Duyệt
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="payout_id" value="<?php echo $payout['id']; ?>">
                                                    <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 flex items-center gap-1" onclick="return confirm('Từ chối yêu cầu rút tiền này?')">
                                                        <span class="material-symbols-outlined text-base">close</span> Từ chối
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-sm">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Info -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800 flex items-start gap-2">
                <span class="material-symbols-outlined">info</span>
                <span>
                    <strong>Lưu ý:</strong> Sau khi duyệt yêu cầu, hãy chuyển tiền thủ công đến tài khoản ngân hàng của chủ xe. 
                    Hệ thống sẽ cập nhật trạng thái và trừ số dư khả dụng của chủ xe.
                </span>
            </p>
        </div>
    </main>
</div>
</body>
</html>

