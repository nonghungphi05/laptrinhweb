<?php
/**
 * Đơn đặt của tôi (Tailwind CSS Design)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$base_path = getBasePath();

// Lấy tham số filter theo status
$status_filter = $_GET['status'] ?? 'all';

// Lấy danh sách đơn đặt
$sql = "SELECT b.*, c.name as car_name, c.image as car_image,
    p.status as payment_status, p.payment_method, p.amount as payment_amount
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.customer_id = ?";
    
$params = [$user_id];
$types = "i";

// Filter theo status (xử lý active và upcoming)
if ($status_filter === 'active') {
    // Đang đi: confirmed và ngày hiện tại nằm trong khoảng start_date và end_date
    $today = date('Y-m-d');
    $sql .= " AND b.status = 'confirmed' AND ? BETWEEN b.start_date AND b.end_date";
    $params[] = $today;
    $types .= "s";
} elseif ($status_filter === 'upcoming') {
    // Sắp tới: confirmed và start_date > ngày hiện tại
    $today = date('Y-m-d');
    $sql .= " AND b.status = 'confirmed' AND b.start_date > ?";
    $params[] = $today;
    $types .= "s";
} elseif ($status_filter !== 'all') {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Lấy thống kê số lượng theo từng status
$stats_sql = "SELECT status, COUNT(*) as count FROM bookings WHERE customer_id = ? GROUP BY status";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}

// Map status to Vietnamese
$status_map = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'rejected' => 'Đã từ chối',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

// Map status to badge colors and active status
$status_badge_classes = [
    'pending' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
    'confirmed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
    'rejected' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
    'completed' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
    'cancelled' => 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300'
];

// Helper function to determine active status
function getActiveStatus($booking) {
    $today = date('Y-m-d');
    $start_date = $booking['start_date'];
    $end_date = $booking['end_date'];
    
    if ($booking['status'] === 'cancelled') {
        return 'cancelled';
    }
    
    if ($booking['status'] === 'completed') {
        return 'completed';
    }
    
    if ($today >= $start_date && $today <= $end_date && $booking['status'] === 'confirmed') {
        return 'active'; // Đang đi
    }
    
    if ($today < $start_date && $booking['status'] === 'confirmed') {
        return 'upcoming'; // Sắp tới
    }
    
    return $booking['status'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Chuyến của tôi - CarRental</title>
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
                        "secondary": "#3C8DAD",
                        "background-light": "#F8F9FA",
                        "background-dark": "#1a1a1a",
                        "text-light": "#343A40",
                        "text-dark": "#E9ECEF",
                        "border-light": "#E9ECEF",
                        "border-dark": "#343A40",
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
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <div class="flex flex-1 justify-center py-5">
                <div class="layout-content-container flex flex-col w-full max-w-5xl flex-1 px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <?php include '../includes/header.php'; ?>
                    
                    <main class="flex-1 py-8">
                        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                            <h1 class="text-4xl font-black leading-tight tracking-[-0.033em]">Chuyến đi của tôi</h1>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="pb-3">
                            <div class="flex border-b border-border-light dark:border-border-dark gap-2 sm:gap-8 overflow-x-auto">
                                <a href="?status=active" 
                                   class="flex flex-col items-center justify-center border-b-[3px] <?php echo $status_filter === 'active' ? 'border-b-secondary text-secondary' : 'border-b-transparent text-gray-500 dark:text-gray-400 hover:text-secondary'; ?> py-3 px-4">
                                    <p class="text-sm font-bold leading-normal tracking-[0.015em] whitespace-nowrap">Đang đi</p>
                                </a>
                                <a href="?status=upcoming" 
                                   class="flex flex-col items-center justify-center border-b-[3px] <?php echo $status_filter === 'upcoming' ? 'border-b-secondary text-secondary' : 'border-b-transparent text-gray-500 dark:text-gray-400 hover:text-secondary'; ?> py-3 px-4">
                                    <p class="text-sm font-bold leading-normal tracking-[0.015em] whitespace-nowrap">Sắp tới</p>
                                </a>
                                <a href="?status=completed" 
                                   class="flex flex-col items-center justify-center border-b-[3px] <?php echo $status_filter === 'completed' ? 'border-b-secondary text-secondary' : 'border-b-transparent text-gray-500 dark:text-gray-400 hover:text-secondary'; ?> py-3 px-4">
                                    <p class="text-sm font-bold leading-normal tracking-[0.015em] whitespace-nowrap">Đã hoàn thành</p>
                                </a>
                                <a href="?status=cancelled" 
                                   class="flex flex-col items-center justify-center border-b-[3px] <?php echo $status_filter === 'cancelled' ? 'border-b-secondary text-secondary' : 'border-b-transparent text-gray-500 dark:text-gray-400 hover:text-secondary'; ?> py-3 px-4">
                                    <p class="text-sm font-bold leading-normal tracking-[0.015em] whitespace-nowrap">Đã hủy</p>
                                </a>
                                <a href="?status=all" 
                                   class="flex flex-col items-center justify-center border-b-[3px] <?php echo $status_filter === 'all' ? 'border-b-secondary text-secondary' : 'border-b-transparent text-gray-500 dark:text-gray-400 hover:text-secondary'; ?> py-3 px-4">
                                    <p class="text-sm font-bold leading-normal tracking-[0.015em] whitespace-nowrap">Tất cả</p>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Bookings List -->
                        <div class="space-y-6 mt-6">
                            <?php if (empty($bookings)): ?>
                                <div class="text-center py-12">
                                    <p class="text-text-light dark:text-text-dark text-lg mb-4">Bạn chưa có chuyến đi nào.</p>
                                    <a href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>" 
                                       class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold">
                                        Tìm xe ngay
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <?php
                                    $active_status = getActiveStatus($booking);
                                    $status_display = [
                                        'active' => ['text' => 'Đang đi', 'class' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300'],
                                        'upcoming' => ['text' => 'Sắp tới', 'class' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300'],
                                        'completed' => ['text' => 'Đã hoàn thành', 'class' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300'],
                                        'cancelled' => ['text' => 'Đã hủy', 'class' => 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300'],
                                        'pending' => ['text' => 'Chờ xác nhận', 'class' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300'],
                                        'confirmed' => ['text' => 'Đã xác nhận', 'class' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300'],
                                        'rejected' => ['text' => 'Đã từ chối', 'class' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300']
                                    ];
                                    
                                    $display = $status_display[$active_status] ?? $status_display[$booking['status']] ?? ['text' => $booking['status'], 'class' => 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300'];
                                    
                                    $start_date = date('d/m/Y', strtotime($booking['start_date']));
                                    $end_date = date('d/m/Y', strtotime($booking['end_date']));
                                    $car_image_url = $base_path ? $base_path . '/uploads/' : '../uploads/';
                                    ?>
                                    <div class="flex flex-col md:flex-row items-stretch justify-between gap-6 rounded-xl bg-white dark:bg-background-dark dark:border dark:border-border-dark p-4 shadow-sm">
                                        <div class="w-full md:w-1/3 bg-center bg-no-repeat aspect-video md:aspect-auto bg-cover rounded-lg flex-1" 
                                             style='background-image: url("<?php echo $car_image_url . htmlspecialchars($booking['car_image'] ?: 'default-car.jpg'); ?>");'
                                             onerror="this.style.backgroundImage='url(<?php echo $car_image_url . 'default-car.jpg'; ?>)'">
                                        </div>
                                        <div class="flex flex-col flex-1 gap-4">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center justify-center rounded-full <?php echo $display['class']; ?> px-2.5 py-0.5 text-xs font-medium">
                                                        <?php echo $display['text']; ?>
                                                    </span>
                                                </div>
                                                <p class="text-xl font-bold leading-tight"><?php echo htmlspecialchars($booking['car_name']); ?></p>
                                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                    <span class="material-symbols-outlined text-base">calendar_today</span>
                                                    <p>Nhận: <?php echo $start_date; ?> - Trả: <?php echo $end_date; ?></p>
                                                </div>
                                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                    <span class="material-symbols-outlined text-base">payments</span>
                                                    <p>Tổng tiền: <?php echo number_format($booking['total_price']); ?> VNĐ</p>
                                                </div>
                                                <?php if ($booking['payment_status'] === 'completed'): ?>
                                                    <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                                                        <span class="material-symbols-outlined text-base">check_circle</span>
                                                        <p>Đã thanh toán</p>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                                        <span class="material-symbols-outlined text-base">cancel</span>
                                                        <p>Chưa thanh toán</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-wrap gap-2 pt-2 mt-auto">
                                                <a href="<?php echo $base_path ? $base_path . '/client/car-detail.php?id=' . $booking['car_id'] : 'car-detail.php?id=' . $booking['car_id']; ?>" 
                                                   class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-9 px-4 bg-gray-100 dark:bg-gray-700 text-text-light dark:text-text-dark text-sm font-medium leading-normal hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                    <span class="material-symbols-outlined text-base">visibility</span>
                                                    <span class="truncate">Xem chi tiết</span>
                                                </a>
                                                
                                                <?php if ($active_status === 'active'): ?>
                                                    <button class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-9 px-4 bg-primary text-white text-sm font-medium leading-normal hover:bg-opacity-90 transition-opacity">
                                                        <span class="material-symbols-outlined text-base">key</span>
                                                        <span class="truncate">Trả xe</span>
                                                    </button>
                                                <?php elseif ($active_status === 'completed' && $booking['payment_status'] === 'completed'): ?>
                                                    <a href="<?php echo $base_path ? $base_path . '/client/review.php?booking_id=' . $booking['id'] : 'review.php?booking_id=' . $booking['id']; ?>" 
                                                       class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-9 px-4 bg-secondary text-white text-sm font-medium leading-normal hover:bg-opacity-90 transition-opacity">
                                                        <span class="material-symbols-outlined text-base">star</span>
                                                        <span class="truncate">Viết đánh giá</span>
                                                    </a>
                                                <?php elseif ($active_status === 'upcoming' || $active_status === 'pending'): ?>
                                                    <button class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-9 px-4 bg-red-500/10 text-red-600 dark:bg-red-900/50 dark:text-red-300 text-sm font-medium leading-normal hover:bg-red-500/20 transition-colors">
                                                        <span class="material-symbols-outlined text-base">cancel</span>
                                                        <span class="truncate">Hủy chuyến</span>
                                                    </button>
                                                <?php elseif ($booking['payment_status'] !== 'completed'): ?>
                                                    <a href="<?php echo $base_path ? $base_path . '/client/payment.php?booking_id=' . $booking['id'] : 'payment.php?booking_id=' . $booking['id']; ?>" 
                                                       class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-9 px-4 bg-primary text-white text-sm font-medium leading-normal hover:bg-opacity-90 transition-opacity">
                                                        <span class="material-symbols-outlined text-base">payments</span>
                                                        <span class="truncate">Thanh toán</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
