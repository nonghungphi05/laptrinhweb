<?php
/**
 * Quản lý đánh giá & báo cáo (Admin)
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
    $review_id = intval($_POST['review_id'] ?? 0);
    
    if ($_POST['action'] === 'delete_review' && $review_id > 0) {
        // Xóa đánh giá
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param("i", $review_id);
        if ($stmt->execute()) {
            $message = 'Đã xóa đánh giá thành công!';
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
    
    if ($_POST['action'] === 'dismiss_flag' && $review_id > 0) {
        // Bỏ qua báo cáo (xóa flag)
        $stmt = $conn->prepare("DELETE FROM review_flags WHERE review_id = ?");
        $stmt->bind_param("i", $review_id);
        if ($stmt->execute()) {
            $message = 'Đã bỏ qua báo cáo!';
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
}

// Lọc
$filter = $_GET['filter'] ?? 'all'; // all, flagged
$search = trim($_GET['search'] ?? '');

// Lấy danh sách đánh giá bị báo cáo
$flagged_reviews = [];
$flag_stmt = $conn->query("
    SELECT rf.*, r.comment, r.rating, r.created_at as review_date,
        c.name as car_name, c.id as car_id,
        customer.full_name as customer_name,
        owner.full_name as owner_name, owner.id as owner_id
    FROM review_flags rf
    JOIN reviews r ON rf.review_id = r.id
    JOIN cars c ON r.car_id = c.id
    JOIN users customer ON r.customer_id = customer.id
    JOIN users owner ON rf.owner_id = owner.id
    ORDER BY rf.created_at DESC
");
while ($row = $flag_stmt->fetch_assoc()) {
    $flagged_reviews[$row['review_id']] = $row;
}

// Lấy danh sách tất cả đánh giá
$where_clause = "";
$params = [];
$types = "";

if ($filter === 'flagged') {
    if (!empty($flagged_reviews)) {
        $flagged_ids = array_keys($flagged_reviews);
        $placeholders = implode(',', array_fill(0, count($flagged_ids), '?'));
        $where_clause = "WHERE r.id IN ($placeholders)";
        $params = $flagged_ids;
        $types = str_repeat('i', count($flagged_ids));
    } else {
        $where_clause = "WHERE 1=0"; // Không có báo cáo nào
    }
}

if ($search !== '') {
    $search_condition = "(c.name LIKE ? OR customer.full_name LIKE ? OR r.comment LIKE ?)";
    if ($where_clause) {
        $where_clause .= " AND $search_condition";
    } else {
        $where_clause = "WHERE $search_condition";
    }
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query = "
    SELECT r.*, 
        c.name as car_name, c.id as car_id, c.image as car_image,
        customer.full_name as customer_name, customer.email as customer_email,
        owner.full_name as owner_name
    FROM reviews r
    JOIN cars c ON r.car_id = c.id
    JOIN users customer ON r.customer_id = customer.id
    JOIN users owner ON c.owner_id = owner.id
    $where_clause
    ORDER BY r.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
$reviews = $result->fetch_all(MYSQLI_ASSOC);

// Thống kê
$total_reviews = $conn->query("SELECT COUNT(*) as total FROM reviews")->fetch_assoc()['total'];
$flagged_count = count($flagged_reviews);
$avg_rating = $conn->query("SELECT AVG(rating) as avg FROM reviews")->fetch_assoc()['avg'];

?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản Lý Đánh Giá - CarRental</title>
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
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary font-semibold relative" href="reviews.php">
                    <span class="material-symbols-outlined">star</span>
                    <span>Đánh giá</span>
                    <?php if ($flagged_count > 0): ?>
                        <span class="absolute right-3 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $flagged_count; ?></span>
                    <?php endif; ?>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-slate-300" href="payouts.php">
                    <span class="material-symbols-outlined">payments</span>
                    <span>Rút tiền</span>
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
                <h1 class="text-3xl font-bold">Quản lý đánh giá</h1>
                <p class="text-slate-500 mt-1">Xem và xử lý các đánh giá, báo cáo từ chủ xe</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500">Điểm trung bình</p>
                <p class="text-2xl font-bold text-primary flex items-center gap-1 justify-end">
                    <span class="material-symbols-outlined text-yellow-500">star</span>
                    <?php echo number_format($avg_rating ?? 0, 1); ?>
                </p>
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
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="?filter=all" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $filter === 'all' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-slate-500">Tất cả</p>
                <p class="text-2xl font-bold"><?php echo $total_reviews; ?></p>
            </a>
            <a href="?filter=flagged" class="bg-white dark:bg-zinc-800 p-4 rounded-xl border <?php echo $filter === 'flagged' ? 'border-primary' : 'border-slate-200'; ?> hover:border-primary transition-colors">
                <p class="text-sm text-red-600 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">flag</span> Bị báo cáo
                </p>
                <p class="text-2xl font-bold text-red-600"><?php echo $flagged_count; ?></p>
            </a>
            <div class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200">
                <p class="text-sm text-green-600">5 sao</p>
                <p class="text-2xl font-bold"><?php echo $conn->query("SELECT COUNT(*) as c FROM reviews WHERE rating = 5")->fetch_assoc()['c']; ?></p>
            </div>
            <div class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200">
                <p class="text-sm text-red-600">1-2 sao</p>
                <p class="text-2xl font-bold"><?php echo $conn->query("SELECT COUNT(*) as c FROM reviews WHERE rating <= 2")->fetch_assoc()['c']; ?></p>
            </div>
        </section>

        <!-- Search -->
        <section class="bg-white dark:bg-zinc-800 p-4 rounded-xl border border-slate-200 dark:border-zinc-700 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Tìm theo tên xe, khách hàng, nội dung..."
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-orange-600">
                    Tìm kiếm
                </button>
                <?php if ($search || $filter !== 'all'): ?>
                    <a href="reviews.php" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-100">
                        Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Reviews List -->
        <section class="space-y-4">
            <?php if (empty($reviews)): ?>
                <div class="bg-white dark:bg-zinc-800 p-12 rounded-xl border border-slate-200 text-center">
                    <span class="material-symbols-outlined text-4xl text-slate-400 mb-2">reviews</span>
                    <p class="text-slate-500">Không có đánh giá nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): 
                    $is_flagged = isset($flagged_reviews[$review['id']]);
                    $flag_info = $is_flagged ? $flagged_reviews[$review['id']] : null;
                ?>
                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border <?php echo $is_flagged ? 'border-red-300 bg-red-50/50' : 'border-slate-200'; ?>">
                        <?php if ($is_flagged): ?>
                            <div class="mb-4 p-3 bg-red-100 border border-red-200 rounded-lg flex items-start gap-3">
                                <span class="material-symbols-outlined text-red-600">flag</span>
                                <div class="flex-1">
                                    <p class="font-semibold text-red-800">Đánh giá bị báo cáo</p>
                                    <p class="text-sm text-red-700 mt-1">
                                        <strong>Người báo cáo:</strong> <?php echo htmlspecialchars($flag_info['owner_name']); ?> (Chủ xe)
                                    </p>
                                    <p class="text-sm text-red-700">
                                        <strong>Lý do:</strong> <?php echo htmlspecialchars($flag_info['reason']); ?>
                                    </p>
                                    <p class="text-xs text-red-600 mt-1"><?php echo date('d/m/Y H:i', strtotime($flag_info['created_at'])); ?></p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="dismiss_flag">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300" title="Bỏ qua báo cáo">
                                            Bỏ qua
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa đánh giá này?')">
                                        <input type="hidden" name="action" value="delete_review">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700" title="Xóa đánh giá">
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Car Info -->
                            <div class="flex items-center gap-3 min-w-[200px]">
                                <img src="<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($review['car_image'] ?: 'default-car.jpg'); ?>" 
                                     alt="" class="w-16 h-12 object-cover rounded-lg">
                                <div>
                                    <p class="font-semibold"><?php echo htmlspecialchars($review['car_name']); ?></p>
                                    <p class="text-xs text-slate-500">Chủ xe: <?php echo htmlspecialchars($review['owner_name']); ?></p>
                                </div>
                            </div>
                            
                            <!-- Review Content -->
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="font-semibold"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                                    <span class="text-yellow-500">
                                        <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                    </span>
                                    <span class="text-xs text-slate-500"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                <p class="text-slate-700 dark:text-slate-300"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                            
                            <!-- Actions -->
                            <?php if (!$is_flagged): ?>
                                <div>
                                    <form method="POST" class="inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa đánh giá này?')">
                                        <input type="hidden" name="action" value="delete_review">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Xóa đánh giá">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>

