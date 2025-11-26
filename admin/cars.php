<?php
/**
 * Quản lý xe (Admin)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireRole('admin');

$base_path = getBasePath();

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
    $stmt->execute();
    
    header('Location: cars.php');
    exit();
}

// Lấy danh sách xe
$stmt = $conn->query("SELECT c.*, u.full_name as owner_name,
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id) as total_bookings,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating
    FROM cars c
    JOIN users u ON c.owner_id = u.id
    ORDER BY c.created_at DESC");
$cars = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Xe - CarRental</title>
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
                    <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55 0 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55 0 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
                </svg>
                <span class="text-xl font-bold text-primary">CarRental</span>
            </a>
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-4 py-2 rounded hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="<?php echo $base_path ? $base_path . '/admin/dashboard.php' : 'dashboard.php'; ?>">
                    <span class="material-icons-outlined text-2xl">dashboard</span>
                    <span>Tổng quan</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2 rounded bg-primary/10 text-primary font-semibold" href="<?php echo $base_path ? $base_path . '/admin/cars.php' : 'cars.php'; ?>">
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
            <h1 class="text-3xl font-bold">Quản lý xe</h1>
            <div class="flex items-center gap-4">
                <a href="<?php echo $base_path ? $base_path . '/host/add-car.php' : '../host/add-car.php'; ?>" class="bg-primary text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-orange-600 transition-colors">
                    <span class="material-icons-outlined text-lg">add</span>
                    <span>Thêm xe mới</span>
                </a>
            </div>
        </header>

        <!-- Cars Table -->
        <section class="bg-white dark:bg-zinc-800 p-6 rounded-lg">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-sm text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="py-3 px-4 font-medium">ID</th>
                            <th class="py-3 px-4 font-medium">Hình ảnh</th>
                            <th class="py-3 px-4 font-medium">Tên xe</th>
                            <th class="py-3 px-4 font-medium">Chủ xe</th>
                            <th class="py-3 px-4 font-medium">Loại xe</th>
                            <th class="py-3 px-4 font-medium">Giá/ngày</th>
                            <th class="py-3 px-4 font-medium">Trạng thái</th>
                            <th class="py-3 px-4 font-medium">Đánh giá</th>
                            <th class="py-3 px-4 font-medium">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cars)): ?>
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-500">Chưa có xe nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cars as $car): ?>
                                <tr class="border-b border-slate-200 dark:border-zinc-700">
                                    <td class="py-3 px-4"><?php echo $car['id']; ?></td>
                                    <td class="py-3 px-4">
                                        <img src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($car['name']); ?>"
                                             class="w-16 h-12 object-cover rounded">
                                    </td>
                                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($car['name']); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($car['owner_name']); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300"><?php echo formatCurrency($car['price_per_day']); ?></td>
                                    <td class="py-3 px-4">
                                        <?php
                                        $status_classes = [
                                            'available' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                            'rented' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                            'maintenance' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300'
                                        ];
                                        $status_text = [
                                            'available' => 'Có sẵn',
                                            'rented' => 'Đang thuê',
                                            'maintenance' => 'Bảo trì'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_classes[$car['status']] ?? ''; ?>">
                                            <?php echo $status_text[$car['status']] ?? $car['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                        <?php if ($car['avg_rating']): ?>
                                            <?php echo number_format($car['avg_rating'], 1); ?> ⭐
                                        <?php else: ?>
                                            Chưa có
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">
                                        <a href="?delete=<?php echo $car['id']; ?>" class="hover:text-red-500" onclick="return confirm('Bạn có chắc muốn xóa xe này?')">
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
