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
$action_feedback = null;

// Handle quick actions for cars (e.g. toggle visibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['car_id'])) {
    $car_id = (int)$_POST['car_id'];
    $action = $_POST['action'];

    // Ensure the car belongs to current user
    $car_stmt = $conn->prepare("SELECT status FROM cars WHERE id = ? AND owner_id = ?");
    $car_stmt->bind_param("ii", $car_id, $user_id);
    $car_stmt->execute();
    $car_result = $car_stmt->get_result();

    if ($car = $car_result->fetch_assoc()) {
        if ($action === 'toggle_visibility') {
            $new_status = $car['status'] === 'available' ? 'maintenance' : 'available';
            $update_stmt = $conn->prepare("UPDATE cars SET status = ? WHERE id = ? AND owner_id = ?");
            $update_stmt->bind_param("sii", $new_status, $car_id, $user_id);
            if ($update_stmt->execute()) {
                $action_feedback = [
                    'type' => 'success',
                    'message' => $new_status === 'available' ? 'Xe đã hiển thị trở lại.' : 'Xe đã được tạm ẩn.'
                ];
            } else {
                $action_feedback = ['type' => 'error', 'message' => 'Không thể cập nhật trạng thái xe.'];
            }
        }
    } else {
        $action_feedback = ['type' => 'error', 'message' => 'Xe không tồn tại hoặc không thuộc sở hữu của bạn.'];
    }
}

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
                        <div class="flex items-center gap-2 p-2">
                            <svg class="w-10 h-10 text-primary" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
                            </svg>
                            <h1 class="text-primary text-2xl font-bold">CarRental</h1>
                        </div>
                        <div class="flex flex-col gap-2 pt-4">
                            <a class="flex items-center gap-3 rounded-lg bg-primary/20 px-3 py-2 text-primary dark:bg-primary/30" href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'dashboard.php'; ?>">
                                <span class="material-symbols-outlined fill">dashboard</span>
                                <p class="text-sm font-bold leading-normal">Tổng quan</p>
                            </a>
                            <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10" href="<?php echo $base_path ? $base_path . '/host/car-bookings.php' : 'car-bookings.php'; ?>">
                                <span class="material-symbols-outlined">calendar_month</span>
                                <p class="text-sm font-medium leading-normal">Lịch xe</p>
                            </a>
                            <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10" href="#">
                                <span class="material-symbols-outlined">bar_chart</span>
                                <p class="text-sm font-medium leading-normal">Thu nhập</p>
                            </a>
                            <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10" href="#">
                                <span class="material-symbols-outlined">star</span>
                                <p class="text-sm font-medium leading-normal">Đánh giá</p>
                            </a>
                            <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10" href="<?php echo $base_path ? $base_path . '/client/profile.php' : '../client/profile.php'; ?>">
                                <span class="material-symbols-outlined">settings</span>
                                <p class="text-sm font-medium leading-normal">Cài đặt</p>
                            </a>
                        </div>
                    </div>
                    <div class="flex flex-col border-t border-border-color dark:border-border-color/20 pt-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="User avatar image" style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=f98006&color=fff");'></div>
                            <div class="flex flex-col">
                                <h1 class="text-text-main dark:text-white text-base font-medium leading-normal"><?php echo htmlspecialchars($user_name); ?></h1>
                                <p class="text-text-muted dark:text-gray-400 text-sm font-normal leading-normal"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                            </div>
                        </div>
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 mt-4 text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10" href="<?php echo $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php'; ?>">
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
                        <a class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-12 px-6 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition-colors" href="<?php echo $base_path ? $base_path . '/host/add-car.php' : 'add-car.php'; ?>">
                            <span class="material-symbols-outlined">add_circle</span>
                            <span class="truncate">Thêm xe mới</span>
                        </a>
                    </div>

                    <?php if ($action_feedback): ?>
                        <div class="mb-6 rounded-xl px-4 py-3 border <?php echo $action_feedback['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700'; ?>">
                            <?php echo htmlspecialchars($action_feedback['message']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 gap-4 py-8 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color dark:bg-background-dark dark:border-border-color/20">
                            <p class="text-text-muted dark:text-gray-400 text-base font-medium leading-normal">Doanh thu tháng này</p>
                            <p class="text-text-main dark:text-white tracking-light text-2xl font-bold leading-tight"><?php echo number_format($monthly_revenue); ?>đ</p>
                            <p class="text-green-600 text-base font-medium leading-normal">+5.2%</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color dark:bg-background-dark dark:border-border-color/20">
                            <p class="text-text-muted dark:text-gray-400 text-base font-medium leading-normal">Tổng số chuyến</p>
                            <p class="text-text-main dark:text-white tracking-light text-2xl font-bold leading-tight"><?php echo $total_bookings; ?></p>
                            <p class="text-green-600 text-base font-medium leading-normal">+1.5%</p>
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
                                    <a class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold" href="<?php echo $base_path ? $base_path . '/host/add-car.php' : 'add-car.php'; ?>">
                                        Thêm xe ngay
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cars as $car): 
                                    $status_styles = [
                                        'available' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                        'rented' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                        'maintenance' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300'
                                    ];
                                    $status_text = [
                                        'available' => 'Đang trống',
                                        'rented' => 'Đã được thuê',
                                        'maintenance' => 'Bảo trì'
                                    ];
                                    $status_class = $status_styles[$car['status']] ?? 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300';
                                    $status_label = $status_text[$car['status']] ?? ucfirst($car['status']);
                                ?>
                                    <div class="flex flex-col sm:flex-row items-start gap-4 rounded-xl border border-border-color bg-white p-4 dark:bg-background-dark dark:border-border-color/20">
                                        <img class="aspect-[4/3] w-full sm:w-48 h-auto object-cover rounded-lg" src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" alt="<?php echo htmlspecialchars($car['name']); ?>" onerror="this.src='<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : '../uploads/default-car.jpg'; ?>'">
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="font-bold text-lg text-text-main dark:text-white"><?php echo htmlspecialchars($car['name']); ?></h3>
                                                    <p class="text-sm text-text-muted dark:text-gray-400"><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></p>
                                                </div>
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                            </div>
                                            <div class="mt-4 flex flex-wrap gap-4 text-sm text-text-muted dark:text-gray-400">
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
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <a class="flex items-center justify-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main dark:bg-white/10 dark:text-white text-xs font-medium hover:bg-gray-200" href="<?php echo $base_path ? $base_path . '/client/car-detail.php?id=' . $car['id'] : '../client/car-detail.php?id=' . $car['id']; ?>">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">open_in_new</span> Xem chi tiết
                                                </a>
                                                <a class="flex items-center justify-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main dark:bg-white/10 dark:text-white text-xs font-medium hover:bg-gray-200" href="<?php echo $base_path ? $base_path . '/host/edit-car.php?id=' . $car['id'] : 'edit-car.php?id=' . $car['id']; ?>">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">edit</span> Chỉnh sửa
                                                </a>
                                                <a class="flex items-center justify-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main dark:bg-white/10 dark:text-white text-xs font-medium hover:bg-gray-200" href="<?php echo $base_path ? $base_path . '/host/car-bookings.php?car_id=' . $car['id'] : 'car-bookings.php?car_id=' . $car['id']; ?>">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">calendar_month</span> Xem lịch
                                                </a>
                                                <form method="POST" class="inline-flex">
                                                    <input type="hidden" name="car_id" value="<?php echo (int)$car['id']; ?>">
                                                    <input type="hidden" name="action" value="toggle_visibility">
                                                    <button type="submit" class="flex items-center justify-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main dark:bg-white/10 dark:text-white text-xs font-medium hover:bg-gray-200">
                                                        <span class="material-symbols-outlined" style="font-size: 16px;">visibility_off</span>
                                                        <?php echo $car['status'] === 'maintenance' ? 'Hiển thị' : 'Tạm ẩn'; ?>
                                                    </button>
                                                </form>
                                                <?php if ($car['pending_bookings'] > 0): ?>
                                                    <a class="flex items-center justify-center gap-1 rounded-md h-9 px-3 bg-primary/10 text-primary text-xs font-semibold hover:bg-primary/20" href="<?php echo $base_path ? $base_path . '/host/car-bookings.php?car_id=' . $car['id'] : 'car-bookings.php?car_id=' . $car['id']; ?>">
                                                        <span class="material-symbols-outlined" style="font-size: 16px;">notifications_active</span>
                                                        <?php echo $car['pending_bookings']; ?> yêu cầu mới
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Right Column: Income & Recent Bookings -->
                        <div class="w-full lg:w-2/5 flex flex-col gap-6">
                            <div>
                                <h2 class="text-text-main dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em] mb-4">Phân tích thu nhập</h2>
                                <div class="flex flex-col gap-2 rounded-xl border border-border-color bg-white p-6 dark:bg-background-dark dark:border-border-color/20">
                                    <p class="text-text-muted dark:text-gray-400 text-base font-medium leading-normal">Tổng doanh thu</p>
                                    <p class="text-text-main dark:text-white tracking-light text-[32px] font-bold leading-tight truncate"><?php echo number_format($monthly_revenue); ?>đ</p>
                                    <div class="flex gap-1">
                                        <p class="text-text-muted dark:text-gray-400 text-base font-normal leading-normal">Tháng này</p>
                                        <p class="text-green-600 text-base font-medium leading-normal">+12.5%</p>
                                    </div>
                                    <div class="flex min-h-[180px] flex-1 flex-col gap-8 py-4">
                                        <svg fill="none" height="148" preserveaspectratio="none" viewbox="-3 0 478 150" width="100%" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <lineargradient id="chart-gradient" x1="0" x2="0" y1="0" y2="100%">
                                                    <stop offset="0%" stop-color="#f98006" stop-opacity="0.3"></stop>
                                                    <stop offset="100%" stop-color="#f98006" stop-opacity="0"></stop>
                                                </lineargradient>
                                            </defs>
                                            <path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25V149H0V109Z" fill="url(#chart-gradient)"></path>
                                            <path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25" stroke="#f98006" stroke-linecap="round" stroke-width="3"></path>
                                        </svg>
                                        <div class="flex justify-around">
                                            <p class="text-text-muted dark:text-gray-400 text-[13px] font-bold leading-normal tracking-[0.015em]">Tuần 1</p>
                                            <p class="text-text-muted dark:text-gray-400 text-[13px] font-bold leading-normal tracking-[0.015em]">Tuần 2</p>
                                            <p class="text-text-muted dark:text-gray-400 text-[13px] font-bold leading-normal tracking-[0.015em]">Tuần 3</p>
                                            <p class="text-text-muted dark:text-gray-400 text-[13px] font-bold leading-normal tracking-[0.015em]">Tuần 4</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h2 class="text-text-main dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em] mb-4">Yêu cầu gần đây</h2>
                                <?php
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
                                        <?php foreach ($recent_bookings as $booking): 
                                            $booking_styles = [
                                                'pending' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                                'confirmed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                                'completed' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
                                                'cancelled' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
                                                'rejected' => 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300'
                                            ];
                                            $booking_labels = [
                                                'pending' => 'Chờ xác nhận',
                                                'confirmed' => 'Đã xác nhận',
                                                'completed' => 'Hoàn thành',
                                                'cancelled' => 'Đã hủy',
                                                'rejected' => 'Đã từ chối'
                                            ];
                                            $booking_class = $booking_styles[$booking['status']] ?? 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300';
                                            $booking_label = $booking_labels[$booking['status']] ?? ucfirst($booking['status']);
                                        ?>
                                            <div class="bg-white dark:bg-background-dark p-4 rounded-xl border border-border-color dark:border-border-color/20">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div>
                                                        <p class="font-bold text-text-main dark:text-white"><?php echo htmlspecialchars($booking['car_name']); ?></p>
                                                        <p class="text-sm text-text-muted dark:text-gray-400"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo $booking_class; ?>"><?php echo $booking_label; ?></span>
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
                </div>
            </main>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
