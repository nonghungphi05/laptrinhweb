<?php
/**
 * Quản lý người dùng (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

$base_path = getBasePath();

// Xử lý xóa user
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = intval($_GET['delete']);
    
    // Không cho phép xóa chính mình
    if ($delete_id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
    }
    
    header('Location: users.php');
    exit();
}

// Lấy danh sách người dùng + số liệu tài khoản
$user_query = "
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM cars WHERE owner_id = u.id) AS total_cars,
        (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id) AS total_bookings
    FROM users u
    ORDER BY u.created_at DESC
";

$stmt = $conn->query($user_query);
$users = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Người Dùng - CarRental</title>
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
                <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/admin/dashboard.php' : 'dashboard.php'; ?>">
                    <span class="material-icons-outlined text-2xl">dashboard</span>
                    <span>Tổng quan</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/admin/cars.php' : 'cars.php'; ?>">
                    <span class="material-icons-outlined text-2xl">directions_car</span>
                    <span>Quản lý xe</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2 rounded bg-primary/10 text-primary font-semibold" href="<?php echo $base_path ? $base_path . '/admin/users.php' : 'users.php'; ?>">
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
            <h1 class="text-3xl font-bold">Quản lý người dùng</h1>
        </header>

        <!-- Users Table -->
        <section class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="py-3 px-4 font-medium">ID</th>
                            <th class="py-3 px-4 font-medium">Tên đăng nhập</th>
                            <th class="py-3 px-4 font-medium">Email</th>
                            <th class="py-3 px-4 font-medium">Họ tên</th>
                            <th class="py-3 px-4 font-medium">Số điện thoại</th>
                            <th class="py-3 px-4 font-medium">Vai trò</th>
                            <th class="py-3 px-4 font-medium">Số xe</th>
                            <th class="py-3 px-4 font-medium">Đơn đặt</th>
                            <th class="py-3 px-4 font-medium">Ngày tạo</th>
                            <th class="py-3 px-4 font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-500">Chưa có người dùng nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="border-b border-slate-200 dark:border-zinc-700">
                                    <td class="py-3 px-4"><?php echo $user['id']; ?></td>
                                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $role_classes = [
                                            'admin' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
                                            'user' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300'
                                        ];
                                        $role_text = [
                                            'admin' => 'Admin',
                                            'user' => 'Người dùng'
                                        ];
                                        $role = $user['role'] ?? 'user';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $role_classes[$role] ?? ''; ?>">
                                            <?php echo $role_text[$role] ?? '---'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo $user['total_cars']; ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo $user['total_bookings']; ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete=<?php echo $user['id']; ?>" class="hover:text-red-500" onclick="return confirm('Bạn có chắc muốn xóa người dùng này?')">
                                                <span class="material-icons-outlined text-xl">delete</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-400">Bạn</span>
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
