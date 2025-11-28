<?php
/**
 * Quản lý người dùng (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

$base_path = getBasePath();
$message = '';
$error = '';

// Xử lý thay đổi vai trò
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_role') {
        $user_id = intval($_POST['user_id']);
        $new_role = $_POST['new_role'];
        
        if ($user_id != $_SESSION['user_id'] && in_array($new_role, ['user', 'admin'])) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            if ($stmt->execute()) {
                $message = 'Đã cập nhật vai trò người dùng thành công!';
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}

// Xử lý xóa user
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = intval($_GET['delete']);
    
    // Không cho phép xóa chính mình
    if ($delete_id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = 'Đã xóa người dùng thành công!';
        }
    }
    
    header('Location: users.php?msg=' . urlencode($message));
    exit();
}

// Lọc theo vai trò
$role_filter = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');

// Lấy danh sách người dùng + số liệu tài khoản
$where_conditions = [];
$params = [];
$types = "";

if ($role_filter === 'user') {
    $where_conditions[] = "(u.role = 'user' OR u.role = 'host')";
} elseif ($role_filter === 'admin') {
    $where_conditions[] = "u.role = 'admin'";
}

if ($search !== '') {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)";
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

$user_query = "
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM cars WHERE owner_id = u.id) AS total_cars,
        (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id) AS total_bookings,
        (SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE customer_id = u.id AND status IN ('confirmed', 'completed')) AS total_spent
    FROM users u
    $where_clause
    ORDER BY u.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($user_query);
}
$users = $result->fetch_all(MYSQLI_ASSOC);

// Thống kê
$stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$role_stats = [];
while ($row = $stmt->fetch_assoc()) {
    $role_stats[$row['role']] = $row['count'];
}
$total_users = array_sum($role_stats);

// Lấy message từ URL
if (isset($_GET['msg']) && $_GET['msg']) {
    $message = $_GET['msg'];
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Người Dùng - CarRental</title>
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
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="dashboard.php">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Tổng quan</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="cars.php">
                    <span class="material-symbols-outlined">directions_car</span>
                    <span>Quản lý xe</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary font-semibold" href="users.php">
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
                <h1 class="text-3xl font-bold">Quản lý người dùng</h1>
                <p class="text-slate-500 mt-1">Quản lý tài khoản và phân quyền người dùng</p>
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
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <a href="?role=" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $role_filter === '' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-slate-500">Tất cả</p>
                <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
            </a>
            <a href="?role=user" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $role_filter === 'user' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-slate-500">Người dùng</p>
                <p class="text-2xl font-bold"><?php echo ($role_stats['user'] ?? 0) + ($role_stats['host'] ?? 0); ?></p>
            </a>
            <a href="?role=admin" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $role_filter === 'admin' ? 'border-primary' : 'border-slate-200 dark:border-zinc-700'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-slate-500">Quản trị viên</p>
                <p class="text-2xl font-bold"><?php echo $role_stats['admin'] ?? 0; ?></p>
            </a>
        </section>

        <!-- Search & Filter -->
        <section class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200 dark:border-zinc-700 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Tìm theo tên, email, số điện thoại..."
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-orange-600">
                    Tìm kiếm
                </button>
                <?php if ($search || $role_filter): ?>
                    <a href="users.php" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-100">
                        Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Users Table -->
        <section class="bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-zinc-900">
                        <tr>
                            <th class="py-4 px-4 font-medium">Người dùng</th>
                            <th class="py-4 px-4 font-medium">Liên hệ</th>
                            <th class="py-4 px-4 font-medium">Vai trò</th>
                            <th class="py-4 px-4 font-medium">Số xe</th>
                            <th class="py-4 px-4 font-medium">Đơn đặt</th>
                            <th class="py-4 px-4 font-medium">Chi tiêu</th>
                            <th class="py-4 px-4 font-medium">Ngày tham gia</th>
                            <th class="py-4 px-4 font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    <span class="material-symbols-outlined text-4xl mb-2 block">person_off</span>
                                    Không tìm thấy người dùng nào
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="border-t border-slate-100 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-900/50">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold">
                                                <?php echo strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></p>
                                                <p class="text-sm text-slate-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($user['phone'] ?? 'Chưa có SĐT'); ?></p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="new_role" onchange="this.form.submit()" 
                                                        class="text-xs px-2 py-1 rounded-full border-0 cursor-pointer
                                                        <?php 
                                                        $role = $user['role'] ?? 'user';
                                                        echo match($role) {
                                                            'admin' => 'bg-red-100 text-red-800',
                                                            default => 'bg-gray-100 text-gray-800'
                                                        };
                                                        ?>">
                                                    <option value="user" <?php echo $role === 'user' || $role === 'host' ? 'selected' : ''; ?>>Người dùng</option>
                                                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-800">Admin (Bạn)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-slate-400">directions_car</span>
                                            <?php echo $user['total_cars']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-slate-400">receipt_long</span>
                                            <?php echo $user['total_bookings']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-primary font-medium">
                                        <?php echo formatCurrency($user['total_spent']); ?>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-slate-500">
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete=<?php echo $user['id']; ?>" 
                                               class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                               onclick="return confirm('Bạn có chắc muốn xóa người dùng này? Hành động này không thể hoàn tác.')">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                                Xóa
                                            </a>
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
    </main>
</div>
</body>
</html>
