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
$flash_success = $_GET['success'] ?? '';
$flash_error   = $_GET['error'] ?? '';

// Xử lý hủy chuyến
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancel_id = (int) $_POST['cancel_booking_id'];
    $redirect_status = $_POST['current_status'] ?? $status_filter;

    $stmt = $conn->prepare("SELECT b.status, b.start_date, b.total_price, b.created_at, p.status as payment_status, p.amount as paid_amount 
        FROM bookings b 
        LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
        WHERE b.id = ? AND b.customer_id = ?");
    $stmt->bind_param("ii", $cancel_id, $user_id);
    $stmt->execute();
    $booking_to_cancel = $stmt->get_result()->fetch_assoc();

    if (!$booking_to_cancel) {
        header("Location: my-bookings.php?status=" . urlencode($redirect_status) . "&error=" . urlencode('Không tìm thấy chuyến này.'));
        exit();
    }

    $today = date('Y-m-d');
    $can_cancel = false;
    $refund_amount = 0;
    $refund_message = '';

    // Chỉ cho phép hủy nếu chưa đến ngày nhận xe
    if ($booking_to_cancel['start_date'] > $today && in_array($booking_to_cancel['status'], ['pending', 'confirmed'])) {
        $can_cancel = true;
        
        // Tính số tiền hoàn trả nếu đã thanh toán
        if ($booking_to_cancel['payment_status'] === 'completed' && $booking_to_cancel['paid_amount'] > 0) {
            $booking_created = strtotime($booking_to_cancel['created_at']);
            $hours_since_booking = (time() - $booking_created) / 3600;
            
            if ($hours_since_booking <= 24) {
                // Hủy trong 24h đầu: hoàn 100%
                $refund_amount = $booking_to_cancel['paid_amount'];
                $refund_message = 'Hoàn tiền 100% (' . number_format($refund_amount) . ' VNĐ) - Hủy trong 24h đầu.';
            } else {
                // Sau 24h: hoàn 80% (phí 20%)
                $refund_amount = $booking_to_cancel['paid_amount'] * 0.8;
                $fee = $booking_to_cancel['paid_amount'] * 0.2;
                $refund_message = 'Hoàn tiền 80% (' . number_format($refund_amount) . ' VNĐ) - Phí hủy 20% (' . number_format($fee) . ' VNĐ).';
            }
        }
    }

    if ($can_cancel) {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $cancel_id);
        $stmt->execute();

        // Cập nhật payment status
        if ($booking_to_cancel['payment_status'] === 'completed' && $refund_amount > 0) {
            // Đánh dấu payment là refunded và lưu số tiền hoàn
            $pay_update = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE booking_id = ? AND status = 'completed'");
            $pay_update->bind_param("i", $cancel_id);
            $pay_update->execute();
            
            $success_msg = 'Đã hủy chuyến thành công. ' . $refund_message;
        } else {
            $pay_update = $conn->prepare("UPDATE payments SET status = 'cancelled' WHERE booking_id = ? AND status = 'pending'");
            $pay_update->bind_param("i", $cancel_id);
            $pay_update->execute();
            
            $success_msg = 'Đã hủy chuyến thành công.';
        }

        header("Location: my-bookings.php?status=" . urlencode($redirect_status) . "&success=" . urlencode($success_msg));
        exit();
    } else {
        header("Location: my-bookings.php?status=" . urlencode($redirect_status) . "&error=" . urlencode('Không thể hủy chuyến này. Chỉ có thể hủy trước ngày nhận xe.'));
        exit();
    }
}

// Lấy danh sách đơn đặt
$sql = "SELECT b.*, b.created_at as booking_created_at, c.name as car_name, c.image as car_image,
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

                        <?php if ($flash_success): ?>
                            <div class="rounded-xl border border-green-200 bg-green-50 text-green-700 px-4 py-3 mb-4 text-sm">
                                <?php echo htmlspecialchars($flash_success); ?>
                            </div>
                        <?php elseif ($flash_error): ?>
                            <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 mb-4 text-sm">
                                <?php echo htmlspecialchars($flash_error); ?>
                            </div>
                        <?php endif; ?>
                        
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
                                                <?php if ($booking['payment_status'] !== 'completed' && $booking['status'] !== 'cancelled' && $booking['status'] !== 'rejected'): ?>
                                                <a href="<?php echo $base_path ? $base_path . '/client/payment.php?booking_id=' . $booking['id'] : 'payment.php?booking_id=' . $booking['id']; ?>" 
                                                   class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-9 px-4 bg-emerald-500 text-white text-sm font-medium leading-normal hover:bg-emerald-600 transition-colors">
                                                    <span class="material-symbols-outlined text-base">payments</span>
                                                    <span class="truncate">Thanh toán</span>
                                                </a>
                                                <?php endif; ?>
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
                                                <?php else: ?>
                                                    <?php
                                                        $booking_start_ts = strtotime($booking['start_date']);
                                                        $today_ts = strtotime(date('Y-m-d'));
                                                        // Cho phép hủy nếu chưa đến ngày nhận xe (kể cả đã thanh toán)
                                                        $allow_cancel = in_array($booking['status'], ['pending', 'confirmed']) && $booking_start_ts > $today_ts;
                                                        
                                                        // Tính thông tin hoàn tiền nếu đã thanh toán
                                                        $refund_info = '';
                                                        $confirm_message = 'Bạn chắc chắn muốn hủy chuyến này?';
                                                        if ($allow_cancel && $booking['payment_status'] === 'completed') {
                                                            $booking_created = strtotime($booking['booking_created_at']);
                                                            $hours_since_booking = (time() - $booking_created) / 3600;
                                                            
                                                            if ($hours_since_booking <= 24) {
                                                                $refund_percent = 100;
                                                                $refund_amount = $booking['total_price'];
                                                                $refund_info = 'Hoàn 100%';
                                                                $confirm_message = 'Bạn sẽ được hoàn 100% (' . number_format($refund_amount) . ' VNĐ) do hủy trong 24h đầu. Xác nhận hủy?';
                                                            } else {
                                                                $refund_percent = 80;
                                                                $refund_amount = $booking['total_price'] * 0.8;
                                                                $fee = $booking['total_price'] * 0.2;
                                                                $refund_info = 'Hoàn 80%';
                                                                $confirm_message = 'Bạn sẽ được hoàn 80% (' . number_format($refund_amount) . ' VNĐ). Phí hủy 20% (' . number_format($fee) . ' VNĐ). Xác nhận hủy?';
                                                            }
                                                        }
                                                    ?>
                                                    <?php if ($allow_cancel): ?>
                                                    <form method="POST" class="inline-flex" onsubmit="return confirm('<?php echo $confirm_message; ?>');">
                                                        <input type="hidden" name="cancel_booking_id" value="<?php echo $booking['id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                                        <button type="submit" class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-9 px-4 bg-red-500/10 text-red-600 dark:bg-red-900/50 dark:text-red-300 text-sm font-medium leading-normal hover:bg-red-500/20 transition-colors" title="<?php echo $refund_info ? 'Chính sách: ' . $refund_info : ''; ?>">
                                                            <span class="material-symbols-outlined text-base">cancel</span>
                                                            <span class="truncate">Hủy chuyến<?php echo $refund_info ? ' (' . $refund_info . ')' : ''; ?></span>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
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
