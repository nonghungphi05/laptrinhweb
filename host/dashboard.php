<?php
/**
 * Dashboard cho chủ xe - Quản lý xe của mình (Tailwind CSS Design)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin(); // Bất kỳ user nào đăng nhập đều có thể quản lý xe của mình

$user_id = $_SESSION['user_id'];
$base_path = getBasePath();

// Lấy thông tin user
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_info = $user_result->fetch_assoc();
$user_name = $user_info['full_name'] ?? $_SESSION['username'] ?? 'Bạn';

// Lấy danh sách xe của chủ xe
$stmt = $conn->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id) as total_bookings,
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id AND status = 'pending') as pending_bookings,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating
    FROM cars c WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);

// Lấy thống kê
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_cars = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.owner_id = ? AND b.status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_bookings = $stmt->get_result()->fetch_assoc()['total'];

// Doanh thu tháng này
$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT COALESCE(SUM(b.total_price), 0) as total 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE c.owner_id = ? AND b.status IN ('confirmed', 'completed') 
    AND DATE_FORMAT(b.created_at, '%Y-%m') = ?");
$stmt->bind_param("is", $user_id, $current_month);
$stmt->execute();
$monthly_revenue = $stmt->get_result()->fetch_assoc()['total'];

// Xe sẵn sàng (available)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ? AND status = 'available'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_cars = $stmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Trang Quản Lý Dành Cho Chủ Xe - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f98006",
                        "background-light": "#f8f7f5",
                        "background-dark": "#23190f",
                        "secondary": "#00AAB2",
                        "text-main": "#343A40",
                        "text-muted": "#6C757D",
                        "border-color": "#e6e0db"
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-size: 24px;
        }
        .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-text-main dark:text-gray-200">
    <div class="relative flex min-h-screen w-full flex-col group/design-root">
        <div class="flex flex-1">
            <!-- SideNavBar -->
            <aside class="sticky top-0 h-screen w-64 flex-shrink-0 bg-white dark:bg-background-dark dark:border-r dark:border-border-color/20 shadow-sm">
                <div class="flex h-full flex-col justify-between p-4">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-3 p-2">
                            <span class="material-symbols-outlined text-primary text-3xl">directions_car</span>
                            <h1 class="text-text-main dark:text-white text-xl font-bold">CarRental</h1>
                        </div>
                        <div class="flex flex-col gap-2 pt-4">
                            <a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'dashboard.php'; ?>" 
                               class="flex items-center gap-3 rounded-lg bg-primary/20 px-3 py-2 text-primary dark:bg-primary/30">
                                <span class="material-symbols-outlined fill">dashboard</span>
                                <p class="text-sm font-bold leading-normal">Tổng quan</p>
                            </a>
                            <a href="<?php echo $base_path ? $base_path . '/forum/my-posts.php' : '../forum/my-posts.php'; ?>" 
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10">
                                <span class="material-symbols-outlined">car_rental</span>
                                <p class="text-sm font-medium leading-normal">Xe của tôi</p>
                            </a>
                            <a href="#" 
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10">
                                <span class="material-symbols-outlined">bar_chart</span>
                                <p class="text-sm font-medium leading-normal">Thu nhập</p>
                            </a>
                            <a href="#" 
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10">
                                <span class="material-symbols-outlined">star</span>
                                <p class="text-sm font-medium leading-normal">Đánh giá</p>
                            </a>
                            <a href="<?php echo $base_path ? $base_path . '/client/profile.php' : '../client/profile.php'; ?>" 
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10">
                                <span class="material-symbols-outlined">settings</span>
                                <p class="text-sm font-medium leading-normal">Cài đặt</p>
                            </a>
                        </div>
                    </div>
                    <div class="flex flex-col border-t border-border-color dark:border-border-color/20 pt-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" 
                                 style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=f98006&color=fff");'></div>
                            <div class="flex flex-col">
                                <h1 class="text-text-main dark:text-white text-base font-medium leading-normal"><?php echo htmlspecialchars($user_name); ?></h1>
                                <p class="text-text-muted dark:text-gray-400 text-sm font-normal leading-normal"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                            </div>
                        </div>
                        <a href="<?php echo $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php'; ?>" 
                           class="flex items-center gap-3 rounded-lg px-3 py-2 mt-4 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10">
                            <span class="material-symbols-outlined">logout</span>
                            <p class="text-sm font-medium leading-normal">Đăng xuất</p>
                        </a>
                    </div>
                </div>
            </aside>
            
            <!-- Main Content -->
            <main class="flex-1 p-8">
                <div class="mx-auto max-w-7xl">
                    <!-- PageHeading -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                        <div class="flex flex-col gap-2">
                            <h1 class="text-text-main dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Chào mừng trở lại, <?php echo htmlspecialchars(explode(' ', $user_name)[0] ?? $user_name); ?>!</h1>
                            <p class="text-text-muted dark:text-gray-400 text-base font-normal leading-normal">Cùng xem tổng quan hoạt động kinh doanh của bạn hôm nay.</p>
                        </div>
                        <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : '../forum/create-post.php'; ?>" 
                           class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-12 px-6 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition-colors">
                            <span class="material-symbols-outlined">add_circle</span>
                            <span class="truncate">Thêm xe mới</span>
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-1 gap-4 py-8 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color dark:bg-background-dark dark:border-border-color/20">
                            <p class="text-text-muted dark:text-gray-400 text-base font-medium leading-normal">Doanh thu tháng này</p>
                            <p class="text-text-main dark:text-white tracking-light text-2xl font-bold leading-tight"><?php echo number_format($monthly_revenue); ?>đ</p>
                            <p class="text-green-600 text-base font-medium leading-normal">+0%</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color dark:bg-background-dark dark:border-border-color/20">
                            <p class="text-text-muted dark:text-gray-400 text-base font-medium leading-normal">Tổng số chuyến</p>
                            <p class="text-text-main dark:text-white tracking-light text-2xl font-bold leading-tight"><?php echo $total_bookings; ?></p>
                            <p class="text-green-600 text-base font-medium leading-normal">+0%</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color dark:bg-background-dark dark:border-border-color/20">
                            <p class="text-text-muted dark:text-gray-400 text-base font-medium leading-normal">Xe sẵn sàng</p>
                            <p class="text-text-main dark:text-white tracking-light text-2xl font-bold leading-tight"><?php echo $available_cars; ?></p>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color dark:bg-background-dark dark:border-border-color/20">
                            <p class="text-text-muted dark:text-gray-400 text-base font-medium leading-normal">Yêu cầu mới</p>
                            <p class="text-text-main dark:text-white tracking-light text-2xl font-bold leading-tight"><?php echo $pending_bookings; ?></p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col lg:flex-row gap-8">
                        <!-- Left Column: Car Management List -->
                        <div class="w-full lg:w-3/5 flex flex-col gap-4">
                            <h2 class="text-text-main dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Danh sách xe của bạn</h2>
                            
                            <?php if (empty($cars)): ?>
                                <div class="bg-white dark:bg-background-dark p-8 rounded-xl border border-border-color dark:border-border-color/20 text-center">
                                    <p class="text-text-muted dark:text-gray-400 mb-4">Bạn chưa có xe nào.</p>
                                    <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : '../forum/create-post.php'; ?>" 
                                       class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold">
                                        Thêm xe ngay
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cars as $car): ?>
                                    <div class="flex flex-col sm:flex-row items-start gap-4 rounded-xl border border-border-color bg-white p-4 dark:bg-background-dark dark:border-border-color/20">
                                        <img class="aspect-[4/3] w-full sm:w-48 h-auto object-cover rounded-lg" 
                                             src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>"
                                             alt="<?php echo htmlspecialchars($car['name']); ?>"
                                             onerror="this.src='<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : '../uploads/default-car.jpg'; ?>'">
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <h3 class="font-bold text-lg text-text-main dark:text-white"><?php echo htmlspecialchars($car['name']); ?></h3>
                                                    <p class="text-sm text-text-muted dark:text-gray-400"><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></p>
                                                </div>
                                                <span class="inline-flex items-center rounded-full <?php 
                                                    echo $car['status'] === 'available' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : 
                                                        ($car['status'] === 'rented' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300' : 
                                                        'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300'); 
                                                ?> px-2.5 py-0.5 text-xs font-semibold">
                                                    <?php 
                                                    $status_text = [
                                                        'available' => 'Đang trống',
                                                        'rented' => 'Đang cho thuê',
                                                        'maintenance' => 'Bảo trì'
                                                    ];
                                                    echo $status_text[$car['status']] ?? $car['status'];
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-4 text-sm text-text-muted dark:text-gray-400 mb-3">
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-base">payments</span>
                                                    <?php echo number_format($car['price_per_day']); ?>đ/ngày
                                                </span>
                                                <?php if ($car['avg_rating']): ?>
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-base text-yellow-500">star</span>
                                                        <?php echo number_format($car['avg_rating'], 1); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-base">luggage</span>
                                                    <?php echo $car['total_bookings']; ?> chuyến
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <?php if ($car['post_id']): ?>
                                                    <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $car['post_id'] : '../forum/post-detail.php?id=' . $car['post_id']; ?>" 
                                                       class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-text-main dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium">
                                                        Xem bài viết
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?php echo $base_path ? $base_path . '/forum/edit-post.php?car_id=' . $car['id'] : '../forum/edit-post.php?car_id=' . $car['id']; ?>" 
                                                   class="px-4 py-2 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors text-sm font-medium">
                                                    Chỉnh sửa
                                                </a>
                                                <?php if ($car['pending_bookings'] > 0): ?>
                                                    <a href="<?php echo $base_path ? $base_path . '/host/car-bookings.php?car_id=' . $car['id'] : 'car-bookings.php?car_id=' . $car['id']; ?>" 
                                                       class="px-4 py-2 bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/70 transition-colors text-sm font-medium">
                                                        <?php echo $car['pending_bookings']; ?> yêu cầu mới
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Right Column: Recent Bookings -->
                        <div class="w-full lg:w-2/5 flex flex-col gap-4">
                            <h2 class="text-text-main dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Yêu cầu gần đây</h2>
                            <?php
                            // Lấy các booking gần đây
                            $recent_stmt = $conn->prepare("SELECT b.*, c.name as car_name, u.full_name as customer_name
                                FROM bookings b
                                JOIN cars c ON b.car_id = c.id
                                JOIN users u ON b.customer_id = u.id
                                WHERE c.owner_id = ?
                                ORDER BY b.created_at DESC
                                LIMIT 5");
                            $recent_stmt->bind_param("i", $user_id);
                            $recent_stmt->execute();
                            $recent_bookings = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                            
                            <?php if (empty($recent_bookings)): ?>
                                <div class="bg-white dark:bg-background-dark p-6 rounded-xl border border-border-color dark:border-border-color/20 text-center">
                                    <p class="text-text-muted dark:text-gray-400">Chưa có yêu cầu nào.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <div class="bg-white dark:bg-background-dark p-4 rounded-xl border border-border-color dark:border-border-color/20">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <p class="font-bold text-text-main dark:text-white"><?php echo htmlspecialchars($booking['car_name']); ?></p>
                                                    <p class="text-sm text-text-muted dark:text-gray-400"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                                </div>
                                                <span class="inline-flex items-center rounded-full <?php 
                                                    echo $booking['status'] === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300' : 
                                                        ($booking['status'] === 'confirmed' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : 
                                                        'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300'); 
                                                ?> px-2.5 py-0.5 text-xs font-semibold">
                                                    <?php 
                                                    $status_text = [
                                                        'pending' => 'Chờ xác nhận',
                                                        'confirmed' => 'Đã xác nhận',
                                                        'completed' => 'Hoàn thành',
                                                        'cancelled' => 'Đã hủy',
                                                        'rejected' => 'Đã từ chối'
                                                    ];
                                                    echo $status_text[$booking['status']] ?? $booking['status'];
                                                    ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-text-muted dark:text-gray-400 mb-2">
                                                <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                            </p>
                                            <p class="text-base font-bold text-text-main dark:text-white">
                                                <?php echo number_format($booking['total_price']); ?>đ
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
