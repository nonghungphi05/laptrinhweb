<?php
/**
 * Quản lý đơn đặt xe (Chủ xe) - Giao diện mới
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin(); // Quyền sở hữu xe được kiểm tra bên dưới

$car_id = $_GET['car_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$base_path = getBasePath();

// Kiểm tra xe thuộc sở hữu của user
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $car_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$car = $result->fetch_assoc();
$feedback = null;

// Xử lý xác nhận/từ chối đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action'];
    
    if ($action === 'confirm') {
        // Kiểm tra đơn đã thanh toán chưa
        $check_stmt = $conn->prepare("SELECT p.status as payment_status FROM bookings b LEFT JOIN payments p ON b.id = p.booking_id WHERE b.id = ? AND b.car_id = ?");
        $check_stmt->bind_param("ii", $booking_id, $car_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        
        if ($check_result && $check_result['payment_status'] === 'completed') {
            $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND car_id = ? AND status = 'pending'");
            $stmt->bind_param("ii", $booking_id, $car_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $feedback = ['type' => 'success', 'message' => 'Đã xác nhận đơn đặt xe!'];
            }
        } else {
            $feedback = ['type' => 'error', 'message' => 'Không thể xác nhận đơn chưa thanh toán!'];
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ? AND car_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $booking_id, $car_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $feedback = ['type' => 'success', 'message' => 'Đã từ chối đơn đặt xe.'];
        }
    } elseif ($action === 'complete') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND car_id = ? AND status = 'confirmed'");
        $stmt->bind_param("ii", $booking_id, $car_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $feedback = ['type' => 'success', 'message' => 'Đã hoàn thành chuyến xe!'];
        }
    }
}

// Lấy danh sách đơn đặt
$stmt = $conn->prepare("SELECT b.*, u.full_name, u.email, u.phone, u.avatar,
    p.status as payment_status, p.amount as payment_amount
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.car_id = ?
    ORDER BY 
        CASE b.status 
            WHEN 'pending' THEN 1 
            WHEN 'confirmed' THEN 2 
            WHEN 'completed' THEN 3 
            ELSE 4 
        END,
        b.created_at DESC");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status_config = [
    'pending' => ['label' => 'Chờ xác nhận', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800'],
    'confirmed' => ['label' => 'Đã xác nhận', 'bg' => 'bg-blue-100', 'text' => 'text-blue-800'],
    'completed' => ['label' => 'Hoàn thành', 'bg' => 'bg-green-100', 'text' => 'text-green-800'],
    'cancelled' => ['label' => 'Đã hủy', 'bg' => 'bg-gray-100', 'text' => 'text-gray-800'],
    'rejected' => ['label' => 'Đã từ chối', 'bg' => 'bg-red-100', 'text' => 'text-red-800']
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn đặt - <?php echo htmlspecialchars($car['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#f98006",
                        background: { light: "#fcfaf8" },
                        "text-main": "#1c160c",
                        "text-muted": "#9c8d7d",
                        "border-color": "#e6e0db"
                    },
                    fontFamily: { display: ['"Plus Jakarta Sans"', "sans-serif"] }
                }
            }
        }
    </script>
</head>
<body class="font-display bg-background-light text-text-main min-h-screen">
    <header class="sticky top-0 z-50 bg-white border-b border-border-color">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="<?php echo $base_path; ?>/index.php" class="flex items-center gap-2 text-primary font-bold text-xl">
                <span class="material-symbols-outlined text-3xl">directions_car</span> CarRental
            </a>
            <a href="dashboard.php" class="text-text-muted hover:text-text-main flex items-center gap-1">
                <span class="material-symbols-outlined">arrow_back</span> Quay lại Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold">Quản lý đơn đặt xe</h1>
                <p class="text-text-muted mt-1"><?php echo htmlspecialchars($car['name']); ?></p>
            </div>
            <div class="flex gap-2">
                <a href="edit-car.php?id=<?php echo $car_id; ?>" class="inline-flex items-center gap-2 h-10 px-4 bg-gray-100 text-text-main font-medium rounded-lg hover:bg-gray-200">
                    <span class="material-symbols-outlined text-xl">edit</span> Sửa xe
                </a>
                <a href="dashboard.php?view=calendar" class="inline-flex items-center gap-2 h-10 px-4 bg-primary/10 text-primary font-medium rounded-lg hover:bg-primary/20">
                    <span class="material-symbols-outlined text-xl">calendar_month</span> Xem lịch
                </a>
            </div>
        </div>

        <?php if ($feedback): ?>
            <div class="mb-4 p-4 rounded-lg flex items-center gap-2 <?php echo $feedback['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <span class="material-symbols-outlined"><?php echo $feedback['type'] === 'success' ? 'check_circle' : 'error'; ?></span>
                <?php echo htmlspecialchars($feedback['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-xl border border-border-color p-12 text-center">
                <span class="material-symbols-outlined text-6xl text-text-muted mb-4">event_busy</span>
                <p class="text-lg text-text-muted">Chưa có đơn đặt nào cho xe này.</p>
                <a href="dashboard.php" class="inline-flex items-center gap-2 mt-4 text-primary hover:underline">
                    <span class="material-symbols-outlined">arrow_back</span> Quay lại dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($bookings as $booking): 
                    $status = $status_config[$booking['status']] ?? $status_config['pending'];
                ?>
                    <div class="bg-white rounded-xl border border-border-color p-5 hover:shadow-md transition-shadow">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                            <!-- Customer Info -->
                            <div class="flex items-center gap-3 min-w-[200px]">
                                <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if ($booking['avatar']): ?>
                                        <img src="<?php echo $base_path; ?>/uploads/<?php echo htmlspecialchars($booking['avatar']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-2xl text-text-muted">person</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-text-main"><?php echo htmlspecialchars($booking['full_name']); ?></p>
                                    <p class="text-sm text-text-muted"><?php echo htmlspecialchars($booking['phone'] ?? $booking['email']); ?></p>
                                </div>
                            </div>

                            <!-- Booking Details -->
                            <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-text-muted">Mã đơn</p>
                                    <p class="font-semibold">#<?php echo $booking['id']; ?></p>
                                </div>
                                <div>
                                    <p class="text-text-muted">Thời gian thuê</p>
                                    <?php
                                        $start_datetime = $booking['start_date'] . ' ' . ($booking['pickup_time'] ?? '00:00:00');
                                        $end_datetime = $booking['end_date'] . ' ' . ($booking['return_time'] ?? '00:00:00');
                                    ?>
                                    <p class="font-semibold">
                                        <?php echo date('d/m/Y H:i', strtotime($start_datetime)); ?>
                                        <span class="text-text-muted">→</span>
                                        <?php echo date('d/m/Y H:i', strtotime($end_datetime)); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-text-muted">Tổng tiền</p>
                                    <p class="font-semibold text-primary"><?php echo number_format($booking['total_price']); ?> đ</p>
                                </div>
                                <div>
                                    <p class="text-text-muted">Thanh toán</p>
                                    <?php if ($booking['payment_status'] === 'completed'): ?>
                                        <p class="font-semibold text-green-600 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-base">check_circle</span> Đã TT
                                        </p>
                                    <?php else: ?>
                                        <p class="font-semibold text-red-600 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-base">cancel</span> Chưa TT
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status & Actions -->
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $status['bg'] . ' ' . $status['text']; ?>">
                                    <?php echo $status['label']; ?>
                                </span>

                                <?php if ($booking['status'] === 'pending'): ?>
                                    <?php if ($booking['payment_status'] === 'completed'): ?>
                                        <form method="POST" class="inline-flex">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="inline-flex items-center gap-1 h-9 px-4 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                                                <span class="material-symbols-outlined text-lg">check</span> Xác nhận
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 h-9 px-4 bg-gray-200 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed" title="Khách chưa thanh toán">
                                            <span class="material-symbols-outlined text-lg">lock</span> Chờ thanh toán
                                        </span>
                                    <?php endif; ?>
                                    <form method="POST" class="inline-flex">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="inline-flex items-center gap-1 h-9 px-4 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700" onclick="return confirm('Bạn chắc chắn muốn từ chối đơn này?')">
                                            <span class="material-symbols-outlined text-lg">close</span> Từ chối
                                        </button>
                                    </form>
                                <?php elseif ($booking['status'] === 'confirmed'): ?>
                                    <form method="POST" class="inline-flex">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="inline-flex items-center gap-1 h-9 px-4 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                                            <span class="material-symbols-outlined text-lg">done_all</span> Hoàn thành
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($booking['pickup_location'] || $booking['return_location']): ?>
                            <div class="mt-4 pt-4 border-t border-border-color grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-green-600">location_on</span>
                                    <div>
                                        <p class="text-text-muted">Nhận xe</p>
                                        <p class="font-medium"><?php echo htmlspecialchars($booking['pickup_location'] ?? 'Chưa xác định'); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-red-600">flag</span>
                                    <div>
                                        <p class="text-text-muted">Trả xe</p>
                                        <p class="font-medium"><?php echo htmlspecialchars($booking['return_location'] ?? 'Chưa xác định'); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="mt-6 bg-white rounded-xl border border-border-color p-5">
                <h3 class="font-bold text-lg mb-4">Tổng quan</h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-center">
                    <?php
                    $stats = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'rejected' => 0];
                    foreach ($bookings as $b) {
                        if (isset($stats[$b['status']])) $stats[$b['status']]++;
                    }
                    ?>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                        <p class="text-sm text-text-muted">Chờ xác nhận</p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['confirmed']; ?></p>
                        <p class="text-sm text-text-muted">Đã xác nhận</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['completed']; ?></p>
                        <p class="text-sm text-text-muted">Hoàn thành</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-600"><?php echo $stats['cancelled']; ?></p>
                        <p class="text-sm text-text-muted">Đã hủy</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <p class="text-2xl font-bold text-red-600"><?php echo $stats['rejected']; ?></p>
                        <p class="text-sm text-text-muted">Đã từ chối</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
