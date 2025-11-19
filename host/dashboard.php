<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$base_path = getBasePath();
$current_page = $base_path ? $base_path . '/host/dashboard.php' : 'dashboard.php';
$action_feedback = null;

$available_views = ['overview', 'calendar', 'earnings', 'reviews', 'cars', 'messages', 'promotions'];
$active_view = $_GET['view'] ?? 'overview';
if (!in_array($active_view, $available_views, true)) {
    $active_view = 'overview';
}

if (!function_exists('hostNavClass')) {
    function hostNavClass(string $view, string $active_view): string
    {
        return $active_view === $view
            ? 'bg-primary/20 text-primary dark:bg-primary/30 font-bold'
            : 'text-text-muted hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10';
    }
}

$schema_queries = [
    "CREATE TABLE IF NOT EXISTS car_availability_blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        owner_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        note VARCHAR(255),
        status ENUM('blocked','maintenance') DEFAULT 'blocked',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS payout_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        bank_name VARCHAR(120),
        bank_account VARCHAR(120),
        note VARCHAR(255),
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS review_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        owner_id INT NOT NULL,
        reply TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS review_flags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        owner_id INT NOT NULL,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];
foreach ($schema_queries as $sql) {
    $conn->query($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    switch ($action) {
        case 'toggle_visibility':
        case 'delete':
            $car_id = (int)($_POST['car_id'] ?? 0);
            if ($car_id <= 0) {
                $action_feedback = ['type' => 'error', 'message' => 'Thiếu thông tin xe.'];
                break;
            }

            $car_stmt = $conn->prepare("SELECT status, image FROM cars WHERE id = ? AND owner_id = ?");
            $car_stmt->bind_param("ii", $car_id, $user_id);
            $car_stmt->execute();
            $car_result = $car_stmt->get_result();
            $car = $car_result->fetch_assoc();

            if (!$car) {
                $action_feedback = ['type' => 'error', 'message' => 'Xe không tồn tại hoặc không thuộc sở hữu của bạn.'];
                break;
            }

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
            } else {
                if (!empty($car['image']) && file_exists('../uploads/' . $car['image'])) {
                    @unlink('../uploads/' . $car['image']);
                }
                $delete_stmt = $conn->prepare("DELETE FROM cars WHERE id = ? AND owner_id = ?");
                $delete_stmt->bind_param("ii", $car_id, $user_id);
                if ($delete_stmt->execute()) {
                    header('Location: ' . $current_page . '?view=overview');
                    exit;
                }
                $action_feedback = ['type' => 'error', 'message' => 'Không thể xóa xe.'];
            }
            break;

        case 'block_dates':
            $block_car_id = (int)($_POST['block_car_id'] ?? 0);
            $block_start = $_POST['block_start'] ?? '';
            $block_end = $_POST['block_end'] ?? '';
            $block_note = trim($_POST['block_note'] ?? '');

            if (!$block_car_id || !$block_start || !$block_end) {
                $action_feedback = ['type' => 'error', 'message' => 'Vui lòng chọn xe và ngày khóa lịch.'];
                break;
            }

            if (strtotime($block_start) > strtotime($block_end)) {
                $tmp = $block_start;
                $block_start = $block_end;
                $block_end = $tmp;
            }

            $car_check = $conn->prepare("SELECT id FROM cars WHERE id = ? AND owner_id = ?");
            $car_check->bind_param("ii", $block_car_id, $user_id);
            $car_check->execute();
            if (!$car_check->get_result()->fetch_assoc()) {
                $action_feedback = ['type' => 'error', 'message' => 'Không tìm thấy xe phù hợp.'];
                break;
            }

            $block_stmt = $conn->prepare("INSERT INTO car_availability_blocks (car_id, owner_id, start_date, end_date, note, status) VALUES (?, ?, ?, ?, ?, 'blocked')");
            $block_stmt->bind_param("iisss", $block_car_id, $user_id, $block_start, $block_end, $block_note);
            if ($block_stmt->execute()) {
                header('Location: ' . $current_page . '?view=calendar');
                exit;
            }
            $action_feedback = ['type' => 'error', 'message' => 'Không thể khóa lịch.'];
            break;

        case 'request_payout':
            $amount = (float)($_POST['withdraw_amount'] ?? 0);
            $bank_name = trim($_POST['bank_name'] ?? '');
            $bank_account = trim($_POST['bank_account'] ?? '');
            $note = trim($_POST['withdraw_note'] ?? '');

            if ($amount <= 0 || !$bank_name || !$bank_account) {
                $action_feedback = ['type' => 'error', 'message' => 'Vui lòng nhập số tiền và thông tin ngân hàng hợp lệ.'];
                break;
            }

            $payout_stmt = $conn->prepare("INSERT INTO payout_requests (owner_id, amount, bank_name, bank_account, note) VALUES (?, ?, ?, ?, ?)");
            $payout_stmt->bind_param("idsss", $user_id, $amount, $bank_name, $bank_account, $note);
            if ($payout_stmt->execute()) {
                header('Location: ' . $current_page . '?view=earnings');
                exit;
            }
            $action_feedback = ['type' => 'error', 'message' => 'Không thể gửi yêu cầu rút tiền.'];
            break;

        case 'reply_review':
            $review_id = (int)($_POST['review_id'] ?? 0);
            $reply_content = trim($_POST['reply_content'] ?? '');

            if (!$review_id || !$reply_content) {
                $action_feedback = ['type' => 'error', 'message' => 'Vui lòng nhập nội dung phản hồi.'];
                break;
            }

            $review_check = $conn->prepare("SELECT r.id FROM reviews r JOIN cars c ON r.car_id = c.id WHERE r.id = ? AND c.owner_id = ?");
            $review_check->bind_param("ii", $review_id, $user_id);
            $review_check->execute();
            if (!$review_check->get_result()->fetch_assoc()) {
                $action_feedback = ['type' => 'error', 'message' => 'Bạn không thể phản hồi đánh giá này.'];
                break;
            }

            $reply_stmt = $conn->prepare("INSERT INTO review_replies (review_id, owner_id, reply) VALUES (?, ?, ?)");
            $reply_stmt->bind_param("iis", $review_id, $user_id, $reply_content);
            if ($reply_stmt->execute()) {
                header('Location: ' . $current_page . '?view=reviews#review-' . $review_id);
                exit;
            }
            $action_feedback = ['type' => 'error', 'message' => 'Không thể gửi phản hồi.'];
            break;

        case 'flag_review':
            $review_id = (int)($_POST['review_id'] ?? 0);
            $flag_reason = trim($_POST['flag_reason'] ?? '');

            if (!$review_id || !$flag_reason) {
                $action_feedback = ['type' => 'error', 'message' => 'Vui lòng nhập lý do báo cáo.'];
                break;
            }

            $review_check = $conn->prepare("SELECT r.id FROM reviews r JOIN cars c ON r.car_id = c.id WHERE r.id = ? AND c.owner_id = ?");
            $review_check->bind_param("ii", $review_id, $user_id);
            $review_check->execute();
            if (!$review_check->get_result()->fetch_assoc()) {
                $action_feedback = ['type' => 'error', 'message' => 'Bạn không thể báo cáo đánh giá này.'];
                break;
            }

            $flag_stmt = $conn->prepare("INSERT INTO review_flags (review_id, owner_id, reason) VALUES (?, ?, ?)");
            $flag_stmt->bind_param("iis", $review_id, $user_id, $flag_reason);
            if ($flag_stmt->execute()) {
                header('Location: ' . $current_page . '?view=reviews#review-' . $review_id);
                exit;
            }
            $action_feedback = ['type' => 'error', 'message' => 'Không thể báo cáo đánh giá.'];
            break;
    }
}

$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$user_name = $user_info['full_name'] ?? ($_SESSION['username'] ?? 'Bạn');
$user_email = $user_info['email'] ?? ($_SESSION['email'] ?? '');
$first_name = trim(explode(' ', $user_name)[0] ?? $user_name);

$stmt = $conn->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id) as total_bookings,
    (SELECT COUNT(*) FROM bookings WHERE car_id = c.id AND status = 'pending') as pending_bookings,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating
    FROM cars c WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_cars = (int)$stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_bookings = (int)$stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings b JOIN cars c ON b.car_id = c.id WHERE c.owner_id = ? AND b.status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_bookings = (int)$stmt->get_result()->fetch_assoc()['total'];

$current_month = date('Y-m');
$stmt = $conn->prepare("SELECT COALESCE(SUM(b.total_price), 0) as total 
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    WHERE c.owner_id = ? AND b.status IN ('confirmed', 'completed') 
    AND DATE_FORMAT(b.created_at, '%Y-%m') = ?");
$stmt->bind_param("is", $user_id, $current_month);
$stmt->execute();
$monthly_revenue = (float)$stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cars WHERE owner_id = ? AND status = 'available'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_cars = (int)$stmt->get_result()->fetch_assoc()['total'];

$view_headings = [
    'overview' => 'Chào mừng trở lại, ' . $first_name . '!',
    'calendar' => 'Lịch xe thông minh',
    'earnings' => 'Ví & Thu nhập',
    'reviews' => 'Quản lý đánh giá',
    'cars' => 'Quản lý xe',
    'messages' => 'Tin nhắn với khách',
    'promotions' => 'Chiến dịch khuyến mãi'
];
$view_subheadings = [
    'overview' => 'Cùng xem tổng quan hoạt động kinh doanh của bạn hôm nay.',
    'calendar' => 'Theo dõi trạng thái xe, đặt xe và khóa lịch chỉ với vài thao tác.',
    'earnings' => 'Kiểm tra số dư, giao dịch và yêu cầu rút tiền.',
    'reviews' => 'Lắng nghe phản hồi khách hàng và trả lời kịp thời.',
    'cars' => 'Cập nhật thông tin xe, giá thuê và giấy tờ cần thiết.',
    'messages' => 'Giữ liên lạc với khách về lịch nhận/trả và yêu cầu bổ sung.',
    'promotions' => 'Tạo mã giảm giá để thu hút thêm khách hàng.'
];
$heading_text = $view_headings[$active_view] ?? 'Bảng điều khiển chủ xe';
$subheading_text = $view_subheadings[$active_view] ?? 'Theo dõi mọi hoạt động xe thuê của bạn.';

$calendar_events = [];
$calendar_events_json = '[]';
$upcoming_trips = [];
$earnings_cards = [
    'available_balance' => 0,
    'pending_payout' => 0,
    'total_earnings' => 0
];
$monthly_earning_series = [];
$earning_chart_labels = [];
$earning_chart_values = [];
$transaction_history = [];
$available_months = [];
$history_filters = [
    'month' => $_GET['month'] ?? '',
    'car' => isset($_GET['car_filter']) ? (int)$_GET['car_filter'] : 0
];
$history_filters['month'] = preg_match('/^\d{4}-\d{2}$/', $history_filters['month']) ? $history_filters['month'] : '';
$avg_rating = 0;
$total_reviews = 0;
$rating_distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$reviews_list = [];
$review_replies = [];
$review_flags_map = [];
$message_threads = [];
$promo_list = [];

if ($active_view === 'calendar') {
    $status_colors = [
        'pending' => '#f2c94c',
        'confirmed' => '#0ea5e9',
        'completed' => '#22c55e',
        'cancelled' => '#9ca3af',
        'rejected' => '#9ca3af',
        'blocked' => '#6b7280',
        'maintenance' => '#ef4444'
    ];

    $booking_stmt = $conn->prepare("SELECT b.*, c.name as car_name, c.id as car_id, u.full_name as customer_name, u.phone
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN users u ON b.customer_id = u.id
        WHERE c.owner_id = ?");
    $booking_stmt->bind_param("i", $user_id);
    $booking_stmt->execute();
    $booking_res = $booking_stmt->get_result();
    while ($row = $booking_res->fetch_assoc()) {
        $status = $row['status'];
        $calendar_events[] = [
            'id' => 'booking-' . $row['id'],
            'title' => $row['car_name'] . ' - ' . ucfirst($status),
            'start' => $row['start_date'],
            'end' => date('Y-m-d', strtotime($row['end_date'] . ' +1 day')),
            'color' => $status_colors[$status] ?? '#f98006',
            'car' => $row['car_name'],
            'carId' => (int)$row['car_id'],
            'status' => $status,
            'customer' => $row['customer_name'],
            'phone' => $row['phone'],
            'dates' => date('d/m', strtotime($row['start_date'])) . ' - ' . date('d/m', strtotime($row['end_date']))
        ];
    }

    $block_stmt = $conn->prepare("SELECT b.*, c.name as car_name FROM car_availability_blocks b JOIN cars c ON b.car_id = c.id WHERE b.owner_id = ?");
    $block_stmt->bind_param("i", $user_id);
    $block_stmt->execute();
    $block_res = $block_stmt->get_result();
    while ($block = $block_res->fetch_assoc()) {
        $calendar_events[] = [
            'id' => 'block-' . $block['id'],
            'title' => $block['car_name'] . ' - Khóa lịch',
            'start' => $block['start_date'],
            'end' => date('Y-m-d', strtotime($block['end_date'] . ' +1 day')),
            'color' => $status_colors['blocked'],
            'car' => $block['car_name'],
            'carId' => (int)$block['car_id'],
            'status' => 'blocked',
            'customer' => 'Bạn đã khóa lịch',
            'phone' => '',
            'note' => $block['note'],
            'dates' => date('d/m', strtotime($block['start_date'])) . ' - ' . date('d/m', strtotime($block['end_date']))
        ];
    }

    $upcoming_stmt = $conn->prepare("SELECT b.*, c.name as car_name, u.full_name as customer_name, u.phone
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN users u ON b.customer_id = u.id
        WHERE c.owner_id = ? AND b.start_date >= CURDATE()
        ORDER BY b.start_date ASC
        LIMIT 6");
    $upcoming_stmt->bind_param("i", $user_id);
    $upcoming_stmt->execute();
    $upcoming_trips = $upcoming_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $calendar_events_json = json_encode($calendar_events, JSON_UNESCAPED_UNICODE);
}

if ($active_view === 'earnings') {
    $balance_stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as completed_amount,
        SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END) as pending_amount
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN cars c ON b.car_id = c.id
        WHERE c.owner_id = ?");
    $balance_stmt->bind_param("i", $user_id);
    $balance_stmt->execute();
    $balance_data = $balance_stmt->get_result()->fetch_assoc();
    $earnings_cards['available_balance'] = (float)($balance_data['completed_amount'] ?? 0);
    $earnings_cards['pending_payout'] = (float)($balance_data['pending_amount'] ?? 0);
    $earnings_cards['total_earnings'] = $earnings_cards['available_balance'] + $earnings_cards['pending_payout'];

    $chart_stmt = $conn->prepare("SELECT DATE_FORMAT(b.start_date, '%Y-%m') as month_label, SUM(p.amount) as total
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN cars c ON b.car_id = c.id
        WHERE c.owner_id = ?
        GROUP BY month_label
        ORDER BY month_label DESC
        LIMIT 6");
    $chart_stmt->bind_param("i", $user_id);
    $chart_stmt->execute();
    $monthly_earning_series = array_reverse($chart_stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    foreach ($monthly_earning_series as $row) {
        $earning_chart_labels[] = date('m/Y', strtotime($row['month_label'] . '-01'));
        $earning_chart_values[] = (float)$row['total'];
    }

    $months_stmt = $conn->prepare("SELECT DISTINCT DATE_FORMAT(b.start_date, '%Y-%m') as month_label
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        WHERE c.owner_id = ?
        ORDER BY month_label DESC
        LIMIT 12");
    $months_stmt->bind_param("i", $user_id);
    $months_stmt->execute();
    $available_months = $months_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $history_sql = "SELECT p.id as transaction_id, b.id as booking_id, c.name as car_name, u.full_name as renter_name,
        p.amount, p.status, p.created_at
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN cars c ON b.car_id = c.id
        JOIN users u ON b.customer_id = u.id
        WHERE c.owner_id = ?";
    $history_types = "i";
    $history_params = [$user_id];

    if (!empty($history_filters['month'])) {
        $history_sql .= " AND DATE_FORMAT(b.start_date, '%Y-%m') = ?";
        $history_types .= "s";
        $history_params[] = $history_filters['month'];
    }
    if (!empty($history_filters['car'])) {
        $history_sql .= " AND c.id = ?";
        $history_types .= "i";
        $history_params[] = $history_filters['car'];
    }

    $history_sql .= " ORDER BY p.created_at DESC";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param($history_types, ...$history_params);
    $history_stmt->execute();
    $transaction_history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($active_view === 'reviews') {
    $rating_stmt = $conn->prepare("SELECT AVG(r.rating) as avg_rating, COUNT(*) as total_reviews
        FROM reviews r
        JOIN cars c ON r.car_id = c.id
        WHERE c.owner_id = ?");
    $rating_stmt->bind_param("i", $user_id);
    $rating_stmt->execute();
    $rating_stats = $rating_stmt->get_result()->fetch_assoc();
    $avg_rating = round((float)($rating_stats['avg_rating'] ?? 0), 1);
    $total_reviews = (int)($rating_stats['total_reviews'] ?? 0);

    $distribution_stmt = $conn->prepare("SELECT r.rating, COUNT(*) as total
        FROM reviews r
        JOIN cars c ON r.car_id = c.id
        WHERE c.owner_id = ?
        GROUP BY r.rating");
    $distribution_stmt->bind_param("i", $user_id);
    $distribution_stmt->execute();
    $distribution_res = $distribution_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($distribution_res as $row) {
        $rating_distribution[(int)$row['rating']] = (int)$row['total'];
    }

    $reviews_stmt = $conn->prepare("SELECT r.*, c.name as car_name, u.full_name as customer_name, u.phone, b.start_date, b.end_date
        FROM reviews r
        JOIN cars c ON r.car_id = c.id
        JOIN users u ON r.customer_id = u.id
        LEFT JOIN bookings b ON r.booking_id = b.id
        WHERE c.owner_id = ?
        ORDER BY r.created_at DESC");
    $reviews_stmt->bind_param("i", $user_id);
    $reviews_stmt->execute();
    $reviews_list = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $replies_stmt = $conn->prepare("SELECT * FROM review_replies WHERE owner_id = ? ORDER BY created_at ASC");
    $replies_stmt->bind_param("i", $user_id);
    $replies_stmt->execute();
    $reply_rows = $replies_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($reply_rows as $reply) {
        $review_replies[$reply['review_id']][] = $reply;
    }

    $flags_stmt = $conn->prepare("SELECT review_id FROM review_flags WHERE owner_id = ?");
    $flags_stmt->bind_param("i", $user_id);
    $flags_stmt->execute();
    $flag_rows = $flags_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($flag_rows as $flag) {
        $review_flags_map[$flag['review_id']] = true;
    }
}

if ($active_view === 'messages') {
    $message_stmt = $conn->prepare("SELECT b.*, c.name as car_name, u.full_name as customer_name, u.phone
        FROM bookings b
        JOIN cars c ON b.car_id = c.id
        JOIN users u ON b.customer_id = u.id
        WHERE c.owner_id = ?
        ORDER BY b.created_at DESC
        LIMIT 8");
    $message_stmt->bind_param("i", $user_id);
    $message_stmt->execute();
    $message_threads = $message_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Chủ xe - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <?php if ($active_view === 'calendar'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css"/>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
    <?php endif; ?>
    <?php if ($active_view === 'earnings'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#f98006",
                        "background-light": "#f8f7f5",
                        "background-dark": "#23190f",
                        "text-main": "#343A40",
                        "text-muted": "#6C757D",
                        "border-color": "#e6e0db"
                    },
                    fontFamily: {
                        display: ["Plus Jakarta Sans", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1;
        }
    </style>
</head>
<body class="font-display bg-background-light text-text-main">
<div class="relative flex min-h-screen w-full flex-col">
    <div class="flex flex-1">
        <aside class="sticky top-0 h-screen w-64 flex-shrink-0 bg-white shadow-sm border-r border-[#f0eae6]">
            <div class="flex h-full flex-col justify-between p-4">
                <div class="flex flex-col gap-4">
                    <a href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>" class="flex items-center gap-2 p-2 text-primary hover:opacity-80 transition-opacity">
                        <svg class="w-10 h-10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
                        </svg>
                        <h1 class="text-2xl font-bold">CarRental</h1>
                    </a>
                    <div class="flex flex-col gap-2 pt-4">
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 <?php echo hostNavClass('overview', $active_view); ?>" href="<?php echo $current_page; ?>?view=overview">
                            <span class="material-symbols-outlined <?php echo $active_view === 'overview' ? 'fill' : ''; ?>">dashboard</span>
                            <p class="text-sm leading-normal">Tổng quan</p>
                        </a>
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 <?php echo hostNavClass('calendar', $active_view); ?>" href="<?php echo $current_page; ?>?view=calendar">
                            <span class="material-symbols-outlined <?php echo $active_view === 'calendar' ? 'fill' : ''; ?>">calendar_month</span>
                            <p class="text-sm leading-normal">Lịch xe</p>
                        </a>
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 <?php echo hostNavClass('earnings', $active_view); ?>" href="<?php echo $current_page; ?>?view=earnings">
                            <span class="material-symbols-outlined <?php echo $active_view === 'earnings' ? 'fill' : ''; ?>">bar_chart</span>
                            <p class="text-sm leading-normal">Thu nhập</p>
                        </a>
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 <?php echo hostNavClass('reviews', $active_view); ?>" href="<?php echo $current_page; ?>?view=reviews">
                            <span class="material-symbols-outlined <?php echo $active_view === 'reviews' ? 'fill' : ''; ?>">star</span>
                            <p class="text-sm leading-normal">Đánh giá</p>
                        </a>
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 <?php echo hostNavClass('cars', $active_view); ?>" href="<?php echo $current_page; ?>?view=cars">
                            <span class="material-symbols-outlined <?php echo $active_view === 'cars' ? 'fill' : ''; ?>">directions_car</span>
                            <p class="text-sm leading-normal">Quản lý xe</p>
                        </a>
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 <?php echo hostNavClass('messages', $active_view); ?>" href="<?php echo $current_page; ?>?view=messages">
                            <span class="material-symbols-outlined <?php echo $active_view === 'messages' ? 'fill' : ''; ?>">chat</span>
                            <p class="text-sm leading-normal">Tin nhắn</p>
                        </a>
                        <a class="flex items-center gap-3 rounded-lg px-3 py-2 <?php echo hostNavClass('promotions', $active_view); ?>" href="<?php echo $current_page; ?>?view=promotions">
                            <span class="material-symbols-outlined <?php echo $active_view === 'promotions' ? 'fill' : ''; ?>">sell</span>
                            <p class="text-sm leading-normal">Khuyến mãi</p>
                        </a>
                    </div>
                </div>
                <div class="flex flex-col border-t border-[#f0eae6] pt-4 gap-2">
                    <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-text-muted hover:bg-gray-100" href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>">
                        <span class="material-symbols-outlined">home</span>
                        <p class="text-sm leading-normal">Về trang chủ</p>
                    </a>
                    <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-red-600 hover:bg-red-50" href="<?php echo $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php'; ?>">
                        <span class="material-symbols-outlined">logout</span>
                        <p class="text-sm leading-normal">Đăng xuất</p>
                    </a>
                </div>
            </div>
        </aside>
        <main class="flex-1 p-8 bg-[#fdfaf7]">
            <div class="mx-auto max-w-7xl space-y-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-text-main text-4xl font-black tracking-[-0.03em]"><?php echo htmlspecialchars($heading_text); ?></h1>
                        <p class="text-text-muted text-base"><?php echo htmlspecialchars($subheading_text); ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a class="flex min-w-[160px] items-center justify-center gap-2 rounded-lg h-12 px-4 bg-primary text-white text-sm font-bold shadow-sm hover:bg-primary/90 transition-colors" href="<?php echo $base_path ? $base_path . '/host/add-car.php' : 'add-car.php'; ?>">
                            <span class="material-symbols-outlined">add_circle</span>
                            <span>Thêm xe mới</span>
                        </a>
                        <button class="relative text-text-muted hover:text-text-main">
                            <span class="material-symbols-outlined text-2xl">notifications</span>
                            <?php if ($pending_bookings > 0): ?>
                                <span class="absolute top-0 right-0 w-2 h-2 bg-primary rounded-full"></span>
                            <?php endif; ?>
                        </button>
                        <div class="flex items-center gap-3">
                            <div class="bg-center bg-no-repeat bg-cover rounded-full w-10 h-10" style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=f98006&color=fff");'></div>
                            <div>
                                <p class="text-text-main font-semibold"><?php echo htmlspecialchars($user_name); ?></p>
                                <p class="text-text-muted text-sm"><?php echo htmlspecialchars($user_email); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($action_feedback): ?>
                    <div class="rounded-xl px-4 py-3 border <?php echo $action_feedback['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700'; ?>">
                        <?php echo htmlspecialchars($action_feedback['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($active_view === 'overview'): ?>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color">
                            <p class="text-text-muted text-base">Doanh thu tháng này</p>
                            <p class="text-text-main text-2xl font-bold"><?php echo number_format($monthly_revenue); ?> đ</p>
                            <p class="text-green-600 text-base font-medium">+5.2%</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color">
                            <p class="text-text-muted text-base">Tổng số chuyến</p>
                            <p class="text-text-main text-2xl font-bold"><?php echo $total_bookings; ?></p>
                            <p class="text-green-600 text-base font-medium">+1.5%</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color">
                            <p class="text-text-muted text-base">Xe sẵn sàng</p>
                            <p class="text-text-main text-2xl font-bold"><?php echo $available_cars; ?></p>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 rounded-xl bg-white p-6 border border-border-color">
                            <p class="text-text-muted text-base">Yêu cầu mới</p>
                            <p class="text-text-main text-2xl font-bold"><?php echo $pending_bookings; ?></p>
                        </div>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-8">
                        <div class="w-full lg:w-3/5 flex flex-col gap-4">
                            <h2 class="text-text-main text-[22px] font-bold">Danh sách xe của bạn</h2>
                            <?php if (empty($cars)): ?>
                                <div class="bg-white p-8 rounded-xl border border-border-color text-center">
                                    <p class="text-text-muted mb-4">Bạn chưa có xe nào.</p>
                                    <a class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 font-bold" href="<?php echo $base_path ? $base_path . '/host/add-car.php' : 'add-car.php'; ?>">
                                        <span class="material-symbols-outlined">add_circle</span> Thêm xe ngay
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cars as $car):
                                    $status_styles = [
                                        'available' => 'bg-green-100 text-green-800',
                                        'rented' => 'bg-yellow-100 text-yellow-800',
                                        'maintenance' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_text = [
                                        'available' => 'Đang trống',
                                        'rented' => 'Đã được thuê',
                                        'maintenance' => 'Bảo trì'
                                    ];
                                    $status_class = $status_styles[$car['status']] ?? 'bg-gray-100 text-gray-800';
                                    $status_label = $status_text[$car['status']] ?? ucfirst($car['status']);
                                ?>
                                    <div class="flex flex-col sm:flex-row items-start gap-4 rounded-xl border border-border-color bg-white p-4">
                                        <img class="aspect-[4/3] w-full sm:w-48 h-auto object-cover rounded-lg" src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" alt="<?php echo htmlspecialchars($car['name']); ?>" onerror="this.src='<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : '../uploads/default-car.jpg'; ?>'">
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="font-bold text-lg text-text-main"><?php echo htmlspecialchars($car['name']); ?></h3>
                                                    <p class="text-sm text-text-muted"><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></p>
                                                </div>
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                            </div>
                                            <div class="mt-4 flex flex-wrap gap-4 text-sm text-text-muted">
                                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">payments</span><?php echo number_format($car['price_per_day']); ?>đ/ngày</span>
                                                <?php if ($car['avg_rating']): ?>
                                                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base text-yellow-500">star</span><?php echo number_format($car['avg_rating'], 1); ?></span>
                                                <?php endif; ?>
                                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">luggage</span><?php echo $car['total_bookings']; ?> chuyến</span>
                                            </div>
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <a class="flex items-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main text-xs font-medium hover:bg-gray-200" href="<?php echo $base_path ? $base_path . '/client/car-detail.php?id=' . $car['id'] : '../client/car-detail.php?id=' . $car['id']; ?>">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">open_in_new</span> Chi tiết
                                                </a>
                                                <a class="flex items-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main text-xs font-medium hover:bg-gray-200" href="<?php echo $base_path ? $base_path . '/host/edit-car.php?id=' . $car['id'] : 'edit-car.php?id=' . $car['id']; ?>">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">edit</span> Chỉnh sửa
                                                </a>
                                                <a class="flex items-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main text-xs font-medium hover:bg-gray-200" href="<?php echo $base_path ? $base_path . '/host/car-bookings.php?car_id=' . $car['id'] : 'car-bookings.php?car_id=' . $car['id']; ?>">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">calendar_month</span> Xem lịch
                                                </a>
                                                <form method="POST" class="inline-flex">
                                                    <input type="hidden" name="action" value="toggle_visibility">
                                                    <input type="hidden" name="car_id" value="<?php echo (int)$car['id']; ?>">
                                                    <button type="submit" class="flex items-center gap-1 rounded-md h-9 px-3 bg-gray-100 text-text-main text-xs font-medium hover:bg-gray-200">
                                                        <span class="material-symbols-outlined" style="font-size: 16px;">visibility_off</span>
                                                        <?php echo $car['status'] === 'maintenance' ? 'Hiển thị' : 'Tạm ẩn'; ?>
                                                    </button>
                                                </form>
                                                <?php if ($car['pending_bookings'] > 0): ?>
                                                    <a class="flex items-center gap-1 rounded-md h-9 px-3 bg-primary/10 text-primary text-xs font-semibold hover:bg-primary/20" href="<?php echo $base_path ? $base_path . '/host/car-bookings.php?car_id=' . $car['id'] : 'car-bookings.php?car_id=' . $car['id']; ?>">
                                                        <span class="material-symbols-outlined" style="font-size: 16px;">notifications_active</span>
                                                        <?php echo $car['pending_bookings']; ?> yêu cầu
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="w-full lg:w-2/5 flex flex-col gap-6">
                            <div>
                                <h2 class="text-text-main text-[22px] font-bold mb-4">Phân tích thu nhập</h2>
                                <div class="rounded-xl border border-border-color bg-white p-6">
                                    <p class="text-text-muted text-base">Tổng doanh thu</p>
                                    <p class="text-text-main text-[32px] font-bold"><?php echo number_format($monthly_revenue); ?> đ</p>
                                    <div class="text-text-muted text-sm mb-4">Tháng này <span class="text-green-600 font-medium">+12.5%</span></div>
                                    <div class="flex min-h-[180px] items-center justify-center">
                                        <span class="text-text-muted text-sm">Biểu đồ chi tiết nằm trong mục Thu nhập.</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h2 class="text-text-main text-[22px] font-bold mb-4">Yêu cầu gần đây</h2>
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
                                    <div class="bg-white p-6 rounded-xl border border-border-color text-center text-text-muted">Chưa có yêu cầu nào.</div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php $status_badges = [
                                            'pending' => ['Chờ xác nhận', 'bg-yellow-100 text-yellow-800'],
                                            'confirmed' => ['Đã xác nhận', 'bg-green-100 text-green-800'],
                                            'completed' => ['Hoàn thành', 'bg-blue-100 text-blue-800'],
                                            'cancelled' => ['Đã hủy', 'bg-red-100 text-red-800'],
                                            'rejected' => ['Đã từ chối', 'bg-gray-100 text-gray-800']
                                        ];
                                        foreach ($recent_bookings as $booking):
                                            $badge = $status_badges[$booking['status']] ?? [$booking['status'], 'bg-gray-100 text-gray-800'];
                                        ?>
                                            <div class="bg-white p-4 rounded-xl border border-border-color">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div>
                                                        <p class="font-bold text-text-main"><?php echo htmlspecialchars($booking['car_name']); ?></p>
                                                        <p class="text-sm text-text-muted"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span>
                                                </div>
                                                <p class="text-sm text-text-muted mb-2">
                                                    <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                                </p>
                                                <p class="text-base font-bold text-text-main"><?php echo number_format($booking['total_price']); ?> đ</p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_view === 'calendar'): ?>
                    <div class="grid lg:grid-cols-[2fr_1fr] gap-6">
                        <div class="bg-white rounded-xl border border-border-color p-5">
                            <div class="flex flex-wrap gap-4 mb-4 items-center">
                                <div class="flex-1">
                                    <label for="calendar-car-filter" class="text-sm font-semibold text-text-muted">Lọc theo xe</label>
                                    <select id="calendar-car-filter" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3">
                                        <option value="">Tất cả xe</option>
                                        <?php foreach ($cars as $car): ?>
                                            <option value="<?php echo (int)$car['id']; ?>"><?php echo htmlspecialchars($car['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <form method="POST" class="flex flex-wrap gap-3 items-end">
                                    <input type="hidden" name="action" value="block_dates">
                                    <div>
                                        <label class="text-sm font-semibold text-text-muted">Xe</label>
                                        <select name="block_car_id" class="mt-1 rounded-lg border border-border-color h-11 px-3" required>
                                            <?php foreach ($cars as $car): ?>
                                                <option value="<?php echo (int)$car['id']; ?>"><?php echo htmlspecialchars($car['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-text-muted">Từ ngày</label>
                                        <input type="date" name="block_start" class="mt-1 rounded-lg border border-border-color h-11 px-3" required>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-text-muted">Đến ngày</label>
                                        <input type="date" name="block_end" class="mt-1 rounded-lg border border-border-color h-11 px-3" required>
                                    </div>
                                    <div class="flex-1 min-w-[180px]">
                                        <label class="text-sm font-semibold text-text-muted">Ghi chú</label>
                                        <input type="text" name="block_note" class="mt-1 rounded-lg border border-border-color h-11 px-3" placeholder="Bảo trì, dùng xe riêng,...">
                                    </div>
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg h-11 px-4 bg-primary text-white font-semibold hover:bg-primary/90">
                                        <span class="material-symbols-outlined">lock</span> Khóa lịch
                                    </button>
                                </form>
                            </div>
                            <div id="car-calendar"></div>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="bg-white rounded-xl border border-border-color p-5">
                                <h3 class="text-lg font-bold text-text-main mb-4">Chuyến sắp tới</h3>
                                <?php if (empty($upcoming_trips)): ?>
                                    <p class="text-text-muted text-sm">Chưa có chuyến nào trong thời gian tới.</p>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($upcoming_trips as $trip): ?>
                                            <div class="border border-border-color rounded-lg p-3">
                                                <p class="font-semibold text-text-main"><?php echo htmlspecialchars($trip['car_name']); ?></p>
                                                <p class="text-sm text-text-muted"><?php echo htmlspecialchars($trip['customer_name']); ?> · <?php echo htmlspecialchars($trip['phone'] ?: ''); ?></p>
                                                <p class="text-sm text-text-muted mt-1">
                                                    <?php echo date('d/m', strtotime($trip['start_date'])); ?> - <?php echo date('d/m', strtotime($trip['end_date'])); ?>
                                                </p>
                                                <span class="inline-flex items-center mt-2 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-primary/10 text-primary"><?php echo ucfirst($trip['status']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="bg-white rounded-xl border border-border-color p-5">
                                <h3 class="text-lg font-bold text-text-main mb-3">Quy ước màu</h3>
                                <ul class="space-y-2 text-sm text-text-muted">
                                    <li><span class="inline-block w-3 h-3 bg-[#22c55e] rounded-full mr-2"></span>Đã đặt / Hoàn thành</li>
                                    <li><span class="inline-block w-3 h-3 bg-[#0ea5e9] rounded-full mr-2"></span>Đã xác nhận</li>
                                    <li><span class="inline-block w-3 h-3 bg-[#f2c94c] rounded-full mr-2"></span>Chờ duyệt</li>
                                    <li><span class="inline-block w-3 h-3 bg-[#ef4444] rounded-full mr-2"></span>Bảo trì / Bận</li>
                                    <li><span class="inline-block w-3 h-3 bg-[#6b7280] rounded-full mr-2"></span>Khóa lịch</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div id="calendar-event-modal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50">
                        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <p class="text-sm text-text-muted">Chi tiết lịch</p>
                                    <h4 class="text-xl font-bold text-text-main" id="modal-car-name"></h4>
                                </div>
                                <button type="button" onclick="toggleEventModal(false)" class="text-text-muted hover:text-text-main">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                            <div class="space-y-2 text-sm">
                                <p><strong>Khách:</strong> <span id="modal-customer"></span></p>
                                <p><strong>Liên hệ:</strong> <span id="modal-phone"></span></p>
                                <p><strong>Thời gian:</strong> <span id="modal-dates"></span></p>
                                <p><strong>Ghi chú:</strong> <span id="modal-note"></span></p>
                                <p><strong>Trạng thái:</strong> <span id="modal-status" class="font-semibold"></span></p>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_view === 'earnings'): ?>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-border-color bg-white p-6">
                            <p class="text-text-muted text-sm uppercase tracking-wide">Số dư khả dụng</p>
                            <p class="text-text-main text-3xl font-bold mt-2"><?php echo number_format($earnings_cards['available_balance']); ?> đ</p>
                            <p class="text-text-muted text-sm mt-1">Có thể rút ngay</p>
                        </div>
                        <div class="rounded-xl border border-border-color bg-white p-6">
                            <p class="text-text-muted text-sm uppercase tracking-wide">Đang chờ thanh toán</p>
                            <p class="text-text-main text-3xl font-bold mt-2"><?php echo number_format($earnings_cards['pending_payout']); ?> đ</p>
                            <p class="text-text-muted text-sm mt-1">Đơn đã hoàn tất nhưng đang xử lý</p>
                        </div>
                        <div class="rounded-xl border border-border-color bg-white p-6">
                            <p class="text-text-muted text-sm uppercase tracking-wide">Tổng thu nhập</p>
                            <p class="text-text-main text-3xl font-bold mt-2"><?php echo number_format($earnings_cards['total_earnings']); ?> đ</p>
                            <p class="text-text-muted text-sm mt-1">Kể từ khi tham gia</p>
                        </div>
                    </div>
                    <div class="grid lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 rounded-xl border border-border-color bg-white p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-text-main">Biểu đồ doanh thu</h3>
                                    <p class="text-text-muted text-sm">So sánh doanh thu theo tháng</p>
                                </div>
                            </div>
                            <canvas id="earnings-chart" height="140"></canvas>
                        </div>
                        <div class="rounded-xl border border-border-color bg-white p-6">
                            <h3 class="text-lg font-bold text-text-main mb-4">Yêu cầu rút tiền</h3>
                            <form method="POST" class="space-y-3">
                                <input type="hidden" name="action" value="request_payout">
                                <div>
                                    <label class="text-sm font-semibold text-text-muted">Số tiền (VNĐ)</label>
                                    <input type="number" name="withdraw_amount" min="50000" step="50000" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" placeholder="500000" required>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-text-muted">Ngân hàng</label>
                                    <input type="text" name="bank_name" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" placeholder="Vietcombank" required>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-text-muted">Số tài khoản</label>
                                    <input type="text" name="bank_account" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" placeholder="0123456789" required>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-text-muted">Ghi chú</label>
                                    <textarea name="withdraw_note" rows="2" class="mt-1 w-full rounded-lg border border-border-color px-3 py-2" placeholder="Ví dụ: Rút doanh thu tuần 1."></textarea>
                                </div>
                                <button type="submit" class="w-full rounded-lg h-11 bg-primary text-white font-semibold hover:bg-primary/90">Yêu cầu rút tiền</button>
                            </form>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border-color bg-white p-6 space-y-4">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <input type="hidden" name="view" value="earnings">
                            <div>
                                <label class="text-sm font-semibold text-text-muted">Tháng</label>
                                <select name="month" class="mt-1 rounded-lg border border-border-color h-10 px-3">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($available_months as $month): ?>
                                        <option value="<?php echo htmlspecialchars($month['month_label']); ?>" <?php echo $history_filters['month'] === $month['month_label'] ? 'selected' : ''; ?>>
                                            <?php echo date('m/Y', strtotime($month['month_label'] . '-01')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-text-muted">Xe</label>
                                <select name="car_filter" class="mt-1 rounded-lg border border-border-color h-10 px-3">
                                    <option value="0">Tất cả</option>
                                    <?php foreach ($cars as $car): ?>
                                        <option value="<?php echo (int)$car['id']; ?>" <?php echo $history_filters['car'] === (int)$car['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($car['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="rounded-lg h-10 px-4 bg-primary text-white font-semibold">Lọc dữ liệu</button>
                            </div>
                        </form>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                <tr class="text-left text-text-muted border-b border-border-color">
                                    <th class="py-2">Mã GD</th>
                                    <th class="py-2">Xe</th>
                                    <th class="py-2">Khách</th>
                                    <th class="py-2">Số tiền</th>
                                    <th class="py-2">Ngày</th>
                                    <th class="py-2">Trạng thái</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($transaction_history)): ?>
                                    <tr><td colspan="6" class="py-6 text-center text-text-muted">Chưa có giao dịch nào.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transaction_history as $transaction):
                                        $status_colors = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed' => 'bg-red-100 text-red-800'
                                        ];
                                        $badge = $status_colors[$transaction['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                        <tr class="border-b border-border-color/60">
                                            <td class="py-3 font-semibold">#<?php echo (int)$transaction['transaction_id']; ?></td>
                                            <td class="py-3"><?php echo htmlspecialchars($transaction['car_name']); ?></td>
                                            <td class="py-3"><?php echo htmlspecialchars($transaction['renter_name']); ?></td>
                                            <td class="py-3 font-semibold text-text-main"><?php echo number_format($transaction['amount']); ?> đ</td>
                                            <td class="py-3 text-text-muted"><?php echo date('d/m/Y', strtotime($transaction['created_at'])); ?></td>
                                            <td class="py-3">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo $badge; ?>">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php elseif ($active_view === 'reviews'): ?>
                    <div class="grid lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 rounded-xl border border-border-color bg-white p-6">
                            <p class="text-sm text-text-muted uppercase">Điểm trung bình</p>
                            <div class="flex items-end gap-4">
                                <p class="text-5xl font-black text-text-main"><?php echo number_format($avg_rating, 1); ?></p>
                                <div class="flex flex-col text-text-muted text-sm">
                                    <span><?php echo $total_reviews; ?> đánh giá</span>
                                    <span><?php echo $total_cars; ?> xe nhận phản hồi</span>
                                </div>
                            </div>
                            <div class="mt-6 space-y-2">
                                <?php for ($i = 5; $i >= 1; $i--):
                                    $percent = $total_reviews > 0 ? ($rating_distribution[$i] / $total_reviews) * 100 : 0;
                                ?>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-semibold w-12"><?php echo $i; ?> ★</span>
                                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-primary" style="width: <?php echo $percent; ?>%;"></div>
                                        </div>
                                        <span class="text-xs text-text-muted w-12 text-right"><?php echo $rating_distribution[$i]; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="rounded-xl border border-border-color bg-white p-6">
                            <p class="text-sm text-text-muted uppercase">Mẹo phản hồi</p>
                            <ul class="text-sm text-text-muted space-y-2 mt-3">
                                <li>• Cảm ơn khách vì đã tin tưởng và đưa ra phản hồi.</li>
                                <li>• Giải thích ngắn gọn nếu có sự cố và đưa hướng xử lý.</li>
                                <li>• Khuyến khích khách quay lại bằng ưu đãi nhỏ.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <?php if (empty($reviews_list)): ?>
                            <div class="rounded-xl border border-border-color bg-white p-6 text-center text-text-muted">Chưa có đánh giá nào.</div>
                        <?php else: ?>
                            <?php foreach ($reviews_list as $review):
                                $review_id = (int)$review['id'];
                                $stars = str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']);
                                $flagged = isset($review_flags_map[$review_id]);
                            ?>
                                <div id="review-<?php echo $review_id; ?>" class="rounded-xl border border-border-color bg-white p-6 space-y-3">
                                    <div class="flex flex-wrap items-center justify-between gap-4">
                                        <div>
                                            <p class="font-bold text-text-main"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                                            <p class="text-sm text-text-muted"><?php echo htmlspecialchars($review['car_name']); ?> · <?php echo $stars; ?></p>
                                            <?php if ($review['start_date'] && $review['end_date']): ?>
                                                <p class="text-xs text-text-muted"><?php echo date('d/m', strtotime($review['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($review['end_date'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-text-muted"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <p class="text-text-main text-sm"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <?php if (!empty($review_replies[$review_id])): ?>
                                        <div class="bg-gray-50 rounded-lg p-3 text-sm text-text-muted space-y-2">
                                            <?php foreach ($review_replies[$review_id] as $reply): ?>
                                                <div>
                                                    <p class="font-semibold text-text-main">Phản hồi của bạn</p>
                                                    <p><?php echo nl2br(htmlspecialchars($reply['reply'])); ?></p>
                                                    <p class="text-xs mt-1"><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex flex-wrap gap-3">
                                        <form method="POST" class="flex-1 min-w-[260px] space-y-2">
                                            <input type="hidden" name="action" value="reply_review">
                                            <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">
                                            <textarea name="reply_content" rows="2" class="w-full rounded-lg border border-border-color px-3 py-2 text-sm" placeholder="Gửi phản hồi tới khách..." required></textarea>
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-4 py-2 bg-primary text-white text-sm font-semibold hover:bg-primary/90">
                                                <span class="material-symbols-outlined" style="font-size: 16px;">reply</span> Trả lời
                                            </button>
                                        </form>
                                        <form method="POST" class="flex items-end">
                                            <input type="hidden" name="action" value="flag_review">
                                            <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">
                                            <input type="text" name="flag_reason" class="rounded-lg border border-border-color h-10 px-3 text-sm" placeholder="Lý do báo cáo" <?php echo $flagged ? 'disabled' : 'required'; ?>>
                                            <button type="submit" class="ml-2 rounded-lg h-10 px-3 border <?php echo $flagged ? 'border-gray-200 text-gray-400 cursor-not-allowed' : 'border-red-200 text-red-600'; ?>" <?php echo $flagged ? 'disabled' : ''; ?>>
                                                Báo cáo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php elseif ($active_view === 'cars'): ?>
                    <div class="rounded-xl border border-border-color bg-white p-6 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-text-main">Danh sách xe</h3>
                                <p class="text-sm text-text-muted">Quản lý thông tin, hình ảnh và giấy tờ từng xe.</p>
                            </div>
                            <a href="<?php echo $base_path ? $base_path . '/host/add-car.php' : 'add-car.php'; ?>" class="inline-flex items-center gap-2 rounded-lg h-11 px-4 bg-primary text-white font-semibold">
                                <span class="material-symbols-outlined">directions_car</span> Thêm xe
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                <tr class="text-left text-text-muted border-b border-border-color">
                                    <th class="py-3">Tên xe</th>
                                    <th class="py-3">Loại</th>
                                    <th class="py-3">Giá/ngày</th>
                                    <th class="py-3">Trạng thái</th>
                                    <th class="py-3">Hành động</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($cars)): ?>
                                    <tr><td colspan="5" class="py-6 text-center text-text-muted">Chưa có xe nào.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($cars as $car):
                                        $status_text = [
                                            'available' => 'Đang trống',
                                            'rented' => 'Đã cho thuê',
                                            'maintenance' => 'Bảo trì'
                                        ];
                                    ?>
                                        <tr class="border-b border-border-color/60">
                                            <td class="py-3 font-semibold text-text-main"><?php echo htmlspecialchars($car['name']); ?></td>
                                            <td class="py-3 text-text-muted"><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></td>
                                            <td class="py-3 font-semibold text-text-main"><?php echo number_format($car['price_per_day']); ?> đ</td>
                                            <td class="py-3 text-text-muted"><?php echo $status_text[$car['status']] ?? $car['status']; ?></td>
                                            <td class="py-3">
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="<?php echo $base_path ? $base_path . '/client/car-detail.php?id=' . $car['id'] : '../client/car-detail.php?id=' . $car['id']; ?>" class="px-3 py-1 rounded-lg border border-border-color text-xs">Xem</a>
                                                    <a href="<?php echo $base_path ? $base_path . '/host/edit-car.php?id=' . $car['id'] : 'edit-car.php?id=' . $car['id']; ?>" class="px-3 py-1 rounded-lg border border-border-color text-xs">Sửa</a>
                                                    <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa xe này?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="car_id" value="<?php echo (int)$car['id']; ?>">
                                                        <button type="submit" class="px-3 py-1 rounded-lg border border-red-200 text-red-600 text-xs">Xóa</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border-color bg-white p-6">
                        <h3 class="text-lg font-bold text-text-main mb-3">Checklist cập nhật</h3>
                        <ul class="list-disc pl-6 text-sm text-text-muted space-y-2">
                            <li>Cập nhật ảnh đẹp, rõ nét cho từng xe.</li>
                            <li>Tải lên giấy tờ: đăng kiểm, bảo hiểm, giấy phép kinh doanh nếu có.</li>
                            <li>Thiết lập giá cuối tuần, giá lễ (nếu cần) trong phần chỉnh sửa xe.</li>
                            <li>Kiểm tra trạng thái xe trước khi xác nhận chuyến mới.</li>
                        </ul>
                    </div>
                <?php elseif ($active_view === 'messages'): ?>
                    <div class="grid lg:grid-cols-[320px_1fr] gap-6">
                        <div class="rounded-xl border border-border-color bg-white">
                            <div class="p-4 border-b border-border-color">
                                <p class="text-sm text-text-muted">Khách gần đây</p>
                            </div>
                            <div class="divide-y divide-border-color max-h-[520px] overflow-y-auto">
                                <?php if (empty($message_threads)): ?>
                                    <p class="p-4 text-sm text-text-muted">Chưa có tin nhắn nào.</p>
                                <?php else: ?>
                                    <?php foreach ($message_threads as $thread): ?>
                                        <div class="p-4 hover:bg-[#fdf1e7] cursor-pointer">
                                            <p class="font-semibold text-text-main"><?php echo htmlspecialchars($thread['customer_name']); ?></p>
                                            <p class="text-sm text-text-muted"><?php echo htmlspecialchars($thread['car_name']); ?></p>
                                            <p class="text-xs text-text-muted mt-1"><?php echo date('d/m H:i', strtotime($thread['created_at'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="rounded-xl border border-border-color bg-white flex flex-col">
                            <div class="p-4 border-b border-border-color">
                                <p class="font-bold text-text-main">Hộp thoại</p>
                                <p class="text-sm text-text-muted">Tính năng chat realtime sẽ được bổ sung sớm.</p>
                            </div>
                            <div class="flex-1 p-6 space-y-4 overflow-y-auto">
                                <div class="bg-primary/5 rounded-2xl px-4 py-3 max-w-[70%] text-sm text-text-main">
                                    <p><strong>Khách:</strong> Anh cho mình nhận xe sớm hơn 30 phút được không?</p>
                                    <span class="text-xs text-text-muted mt-1 block">09:15</span>
                                </div>
                                <div class="bg-gray-100 rounded-2xl px-4 py-3 max-w-[70%] text-sm text-text-main ml-auto">
                                    <p><strong>Bạn:</strong> Được nhé, mình sẽ chuẩn bị xe từ 7h30 cho bạn.</p>
                                    <span class="text-xs text-text-muted mt-1 block text-right">09:17</span>
                                </div>
                            </div>
                            <div class="p-4 border-t border-border-color">
                                <form class="flex gap-3">
                                    <input type="text" class="flex-1 rounded-full border border-border-color h-11 px-4" placeholder="Trả lời khách...">
                                    <button type="button" class="rounded-full h-11 w-11 bg-primary text-white flex items-center justify-center">
                                        <span class="material-symbols-outlined">send</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_view === 'promotions'): ?>
                    <div class="grid lg:grid-cols-2 gap-6">
                        <div class="rounded-xl border border-border-color bg-white p-6">
                            <h3 class="text-lg font-bold text-text-main mb-4">Tạo mã khuyến mãi</h3>
                            <form id="promo-form" class="space-y-3">
                                <div>
                                    <label class="text-sm font-semibold text-text-muted">Tên chiến dịch</label>
                                    <input type="text" name="promo_name" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" placeholder="Ưu đãi tháng 11" required>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm font-semibold text-text-muted">Mức giảm (%)</label>
                                        <input type="number" name="promo_percent" min="1" max="50" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" placeholder="10" required>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-text-muted">Giới hạn lượt</label>
                                        <input type="number" name="promo_limit" min="1" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" placeholder="20" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-text-muted">Áp dụng cho xe</label>
                                    <select name="promo_car" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3">
                                        <option value="all">Tất cả xe</option>
                                        <?php foreach ($cars as $car): ?>
                                            <option value="<?php echo htmlspecialchars($car['name']); ?>"><?php echo htmlspecialchars($car['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm font-semibold text-text-muted">Bắt đầu</label>
                                        <input type="date" name="promo_start" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" required>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-text-muted">Kết thúc</label>
                                        <input type="date" name="promo_end" class="mt-1 w-full rounded-lg border border-border-color h-11 px-3" required>
                                    </div>
                                </div>
                                <button type="submit" class="w-full rounded-lg h-11 bg-primary text-white font-semibold">Tạo mã giảm giá</button>
                            </form>
                        </div>
                        <div class="rounded-xl border border-border-color bg-white p-6">
                            <h3 class="text-lg font-bold text-text-main mb-4">Mã ưu đãi gần đây</h3>
                            <div id="promo-list" class="space-y-3 text-sm text-text-muted">
                                <p>Chưa có chiến dịch nào. Tạo mã đầu tiên để thu hút khách!</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
    const calendarEvents = <?php echo $calendar_events_json; ?>;

    function toggleEventModal(show, data = null) {
        const modal = document.getElementById('calendar-event-modal');
        if (!modal) return;
        if (show && data) {
            modal.querySelector('#modal-car-name').textContent = data.car || '';
            modal.querySelector('#modal-customer').textContent = data.customer || 'N/A';
            modal.querySelector('#modal-phone').textContent = data.phone || 'N/A';
            modal.querySelector('#modal-dates').textContent = data.dates || '';
            modal.querySelector('#modal-note').textContent = data.note || '—';
            modal.querySelector('#modal-status').textContent = data.status || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($active_view === 'calendar'): ?>
        const calendarEl = document.getElementById('car-calendar');
        if (calendarEl) {
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 650,
                locale: 'vi',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: calendarEvents,
                eventClick(info) {
                    toggleEventModal(true, info.event.extendedProps);
                }
            });
            calendar.render();

            const filter = document.getElementById('calendar-car-filter');
            filter.addEventListener('change', () => {
                const selected = filter.value;
                calendar.getEvents().forEach(event => {
                    if (!selected || event.extendedProps.carId == selected) {
                        event.setProp('display', 'auto');
                    } else {
                        event.setProp('display', 'none');
                    }
                });
            });
        }

        document.getElementById('calendar-event-modal')?.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                toggleEventModal(false);
            }
        });
        <?php endif; ?>

        <?php if ($active_view === 'earnings'): ?>
        const earningLabels = <?php echo json_encode($earning_chart_labels, JSON_UNESCAPED_UNICODE); ?>;
        const earningValues = <?php echo json_encode($earning_chart_values, JSON_UNESCAPED_UNICODE); ?>;
        const ctx = document.getElementById('earnings-chart');
        if (ctx && earningLabels.length) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: earningLabels,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: earningValues,
                        backgroundColor: '#f98006'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => value.toLocaleString('vi-VN')
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>

        <?php if ($active_view === 'promotions'): ?>
        const promoForm = document.getElementById('promo-form');
        const promoList = document.getElementById('promo-list');
        promoForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(promoForm);
            const promo = {
                name: formData.get('promo_name'),
                percent: formData.get('promo_percent'),
                limit: formData.get('promo_limit'),
                car: formData.get('promo_car'),
                start: formData.get('promo_start'),
                end: formData.get('promo_end')
            };
            const card = document.createElement('div');
            card.className = 'border border-border-color rounded-lg p-3';
            card.innerHTML = `<p class="font-semibold text-text-main">${promo.name}</p>
                <p class="text-sm text-text-muted">Giảm ${promo.percent}% · ${promo.limit} lượt · ${promo.car === 'all' ? 'Tất cả xe' : promo.car}</p>
                <p class="text-xs text-text-muted">Hiệu lực: ${promo.start} → ${promo.end}</p>`;
            if (promoList.children.length && promoList.firstElementChild.textContent.includes('Chưa có')) {
                promoList.innerHTML = '';
            }
            promoList.prepend(card);
            promoForm.reset();
        });
        <?php endif; ?>
    });
</script>
</body>
</html>

