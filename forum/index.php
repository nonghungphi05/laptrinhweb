<?php
/**
 * Trang diễn đàn - Danh sách bài viết (Tailwind CSS Design)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// Lấy base path
$base_path = getBasePath();

// Lấy tham số filter
$category_id = $_GET['category'] ?? '';
$post_type = $_GET['type'] ?? 'rental'; // Mặc định là rental để hiển thị xe
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$rental_type = $_GET['rental_type'] ?? '';
$needs = $_GET['needs'] ?? '';
$trends = $_GET['trends'] ?? '';
$budgets = $_GET['budgets'] ?? '';
$car_type_filter = isset($_GET['car_type']) && is_array($_GET['car_type']) ? implode(',', $_GET['car_type']) : ($_GET['car_type'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

// Build query conditions
$where_conditions = ["p.status = 'active'"];
$params = [];
$types = "";

if (!empty($category_id)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($post_type)) {
    $where_conditions[] = "p.post_type = ?";
    $params[] = $post_type;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ? OR car.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Filter theo location (chỉ áp dụng cho bài viết có car_id)
if (!empty($location)) {
    $where_conditions[] = "(car.location = ? OR p.post_type = 'discussion')";
    $params[] = $location;
    $types .= "s";
}

// Filter theo rental_type
if (!empty($rental_type)) {
    $where_conditions[] = "(car.rental_type = ? OR p.post_type = 'discussion')";
    $params[] = $rental_type;
    $types .= "s";
}

// Filter theo car_type
if (!empty($car_type_filter)) {
    $car_types = explode(',', $car_type_filter);
    $car_type_conditions = [];
    foreach ($car_types as $ct) {
        $ct = trim($ct);
        $car_type_conditions[] = "car.car_type = ?";
        $params[] = $ct;
        $types .= "s";
    }
    if (!empty($car_type_conditions)) {
        $where_conditions[] = "(" . implode(" OR ", $car_type_conditions) . " OR p.post_type = 'discussion')";
    }
}

// Filter theo nhu cầu (needs)
if (!empty($needs)) {
    $needs_array = explode(',', $needs);
    $needs_conditions = [];
    foreach ($needs_array as $need) {
        $need = trim($need);
        switch ($need) {
            case 'new-driver':
                $needs_conditions[] = "(car.car_type IN ('sedan', 'hatchback') OR p.content LIKE ?)";
                $params[] = "%dễ lái%";
                $types .= "s";
                break;
            case 'work-commute':
                $needs_conditions[] = "(car.car_type IN ('sedan', 'hatchback') OR p.content LIKE ?)";
                $params[] = "%tiết kiệm%";
                $types .= "s";
                break;
            case 'family':
                $needs_conditions[] = "(car.car_type IN ('suv', 'mpv') OR p.content LIKE ?)";
                $params[] = "%gia đình%";
                $types .= "s";
                break;
            case 'camping':
                $needs_conditions[] = "(car.car_type IN ('pickup', 'suv') OR p.content LIKE ?)";
                $params[] = "%chở đồ%";
                $types .= "s";
                break;
            case 'friends':
                $needs_conditions[] = "(car.car_type IN ('mpv', 'suv') OR p.content LIKE ?)";
                $params[] = "%7 chỗ%";
                $types .= "s";
                break;
            case 'party':
                $needs_conditions[] = "(car.car_type IN ('sedan', 'suv') OR p.content LIKE ?)";
                $params[] = "%sang trọng%";
                $types .= "s";
                break;
        }
    }
    if (!empty($needs_conditions)) {
        $where_conditions[] = "(" . implode(" OR ", $needs_conditions) . " OR p.post_type = 'discussion')";
    }
}

// Filter theo xu hướng (trends)
if (!empty($trends)) {
    $trends_array = explode(',', $trends);
    $trend_conditions = [];
    foreach ($trends_array as $trend) {
        $trend = trim($trend);
        switch ($trend) {
            case 'electric':
                $trend_conditions[] = "(car.car_type LIKE ? OR p.content LIKE ?)";
                $params[] = "%electric%";
                $params[] = "%điện%";
                $types .= "ss";
                break;
            case 'hybrid':
                $trend_conditions[] = "(car.car_type LIKE ? OR p.content LIKE ?)";
                $params[] = "%hybrid%";
                $params[] = "%hybrid%";
                $types .= "ss";
                break;
            case 'sports':
                $trend_conditions[] = "(car.car_type LIKE ? OR p.content LIKE ?)";
                $params[] = "%sports%";
                $params[] = "%thể thao%";
                $types .= "ss";
                break;
        }
    }
    if (!empty($trend_conditions)) {
        $where_conditions[] = "(" . implode(" OR ", $trend_conditions) . " OR p.post_type = 'discussion')";
    }
}

// Filter theo ngân sách (budgets)
if (!empty($budgets)) {
    $budgets_array = explode(',', $budgets);
    $budget_conditions = [];
    foreach ($budgets_array as $budget) {
        $budget = trim($budget);
        switch ($budget) {
            case 'cheap':
                $budget_conditions[] = "(car.price_per_day < 500000 OR p.post_type = 'discussion')";
                break;
            case 'economical':
                $budget_conditions[] = "(car.price_per_day BETWEEN 500000 AND 1000000 OR p.post_type = 'discussion')";
                break;
        }
    }
    if (!empty($budget_conditions)) {
        $where_conditions[] = "(" . implode(" OR ", $budget_conditions) . ")";
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Đếm tổng số bài viết
$count_sql = "SELECT COUNT(*) as total 
              FROM posts p
              JOIN users u ON p.user_id = u.id
              JOIN categories c ON p.category_id = c.id
              LEFT JOIN cars car ON p.car_id = car.id
              $where_clause";

$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) {
    die("Lỗi SQL: " . $conn->error);
}

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_posts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $per_page);
$count_stmt->close();

// Lấy danh sách bài viết với phân trang
$offset = ($page - 1) * $per_page;
$sql = "SELECT p.*, 
        u.username, u.full_name,
        c.name as category_name, c.slug as category_slug,
        car.name as car_name, car.price_per_day, car.image as car_image, car.rental_type, car.car_type, car.location,
        (SELECT COUNT(*) FROM bookings WHERE car_id = car.id) as booking_count,
        (SELECT AVG(rating) FROM reviews WHERE car_id = car.id) as avg_rating
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN cars car ON p.car_id = car.id
        $where_clause
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";

$limit_params = array_merge($params, [$per_page, $offset]);
$limit_types = $types . "ii";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi SQL: " . $conn->error);
}

if (!empty($limit_params)) {
    $stmt->bind_param($limit_types, ...$limit_params);
}
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lấy danh sách categories
$categories_stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Car Rental Listing - CarRental</title>
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
                        "secondary": "#2C3E50",
                        "background-light": "#f8f7f5",
                        "background-dark": "#181411",
                        "text-light": "#343A40",
                        "text-dark": "#f8f7f5",
                        "text-muted-light": "#8c755f",
                        "text-muted-dark": "#a19182",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
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
    <div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
        <!-- Header -->
        <?php include '../includes/header.php'; ?>
        
        <main class="container mx-auto px-4 py-8">
            <!-- PageHeading & SearchBar -->
            <div class="mb-8">
                <div class="flex flex-wrap justify-between gap-3 mb-6">
                    <div class="flex min-w-72 flex-col gap-2">
                        <p class="text-4xl font-black leading-tight tracking-[-0.033em] text-text-light dark:text-text-dark">Khám phá &amp; Thuê xe dễ dàng</p>
                        <p class="text-base font-normal leading-normal text-text-muted-light dark:text-text-muted-dark">Tìm kiếm, so sánh và chọn xe một cách nhanh chóng và trực quan.</p>
                    </div>
                </div>
                <form method="GET" action="">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($post_type); ?>">
                    <label class="flex flex-col min-w-40 h-14 w-full">
                        <div class="flex w-full flex-1 items-stretch rounded-lg h-full shadow-sm">
                            <div class="text-text-muted-light dark:text-text-muted-dark flex bg-white dark:bg-background-dark items-center justify-center pl-4 rounded-l-lg border-y border-l border-gray-200 dark:border-gray-700">
                                <span class="material-symbols-outlined">search</span>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-r-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 bg-white dark:bg-background-dark h-full placeholder:text-text-muted-light dark:placeholder:text-text-muted-dark px-4 pl-2 text-base font-normal leading-normal border-y border-r border-gray-200 dark:border-gray-700" 
                                   placeholder="Tìm kiếm theo địa điểm, ngày nhận/trả xe..." 
                                   value="<?php echo htmlspecialchars($search); ?>"/>
                            <button type="submit" class="px-6 bg-primary text-white rounded-r-lg hover:bg-primary/90 transition-colors font-bold">
                                Tìm
                            </button>
                        </div>
                    </label>
                </form>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar Filters -->
                <aside class="lg:col-span-1">
                    <div class="sticky top-28 p-6 rounded-xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                        <h3 class="text-lg font-bold mb-6">Bộ lọc</h3>
                        <form method="GET" action="" id="filter-form">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($post_type); ?>">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            
                            <div class="space-y-6">
                                <!-- Filter Section: Car Type -->
                                <div>
                                    <h4 class="font-semibold mb-3">Loại xe</h4>
                                    <div class="space-y-2">
                                        <?php
                                        $selected_car_types = [];
                                        if (!empty($car_type_filter)) {
                                            $selected_car_types = explode(',', $car_type_filter);
                                            $selected_car_types = array_map('trim', $selected_car_types);
                                        }
                                        ?>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" 
                                                   name="car_type[]" 
                                                   value="sedan" 
                                                   class="form-checkbox rounded text-primary focus:ring-primary/50"
                                                   <?php echo in_array('sedan', $selected_car_types) ? 'checked' : ''; ?>>
                                            <span>Sedan</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" 
                                                   name="car_type[]" 
                                                   value="suv" 
                                                   class="form-checkbox rounded text-primary focus:ring-primary/50"
                                                   <?php echo in_array('suv', $selected_car_types) ? 'checked' : ''; ?>>
                                            <span>SUV</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" 
                                                   name="car_type[]" 
                                                   value="pickup" 
                                                   class="form-checkbox rounded text-primary focus:ring-primary/50"
                                                   <?php echo in_array('pickup', $selected_car_types) ? 'checked' : ''; ?>>
                                            <span>Bán tải</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" 
                                                   name="car_type[]" 
                                                   value="mpv" 
                                                   class="form-checkbox rounded text-primary focus:ring-primary/50"
                                                   <?php echo in_array('mpv', $selected_car_types) ? 'checked' : ''; ?>>
                                            <span>MPV</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Filter Section: Price Range -->
                                <div>
                                    <h4 class="font-semibold mb-3">Mức giá</h4>
                                    <input type="range" 
                                           class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-primary" 
                                           min="500000" 
                                           max="2000000" 
                                           step="100000" 
                                           value="2000000"
                                           id="price-range"/>
                                    <div class="flex justify-between text-xs text-text-muted-light dark:text-text-muted-dark mt-1">
                                        <span>500k</span>
                                        <span>2tr</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-8 flex flex-col gap-2">
                                <button type="submit" class="w-full h-11 flex items-center justify-center rounded-full bg-primary text-white font-bold transition-transform hover:scale-105">Áp dụng</button>
                                <a href="?type=<?php echo htmlspecialchars($post_type); ?>" class="w-full h-11 flex items-center justify-center rounded-full bg-gray-200 dark:bg-zinc-800 text-text-light dark:text-text-dark font-bold hover:bg-gray-300 dark:hover:bg-zinc-700">Xóa bộ lọc</a>
                            </div>
                        </form>
                    </div>
                </aside>
                
                <!-- Car Listing Grid -->
                <div class="lg:col-span-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php if (empty($posts)): ?>
                            <div class="col-span-full text-center py-12">
                                <p class="text-text-muted-light dark:text-text-muted-dark text-lg mb-4">Không tìm thấy xe nào.</p>
                                <?php if (isLoggedIn()): ?>
                                    <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : 'create-post.php'; ?>" 
                                       class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold">
                                        Đăng xe đầu tiên
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <?php if ($post['post_type'] === 'rental' && $post['car_name']): ?>
                                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-zinc-900 hover:shadow-lg transition-shadow">
                                        <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $post['id'] : 'post-detail.php?id=' . $post['id']; ?>">
                                            <div class="w-full bg-center bg-no-repeat aspect-video bg-cover rounded-lg" 
                                                 style='background-image: url("<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($post['car_image'] ?: 'default-car.jpg'); ?>");'
                                                 onerror="this.style.backgroundImage='url(<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : '../uploads/default-car.jpg'; ?>)'">
                                            </div>
                                        </a>
                                        <div>
                                            <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $post['id'] : 'post-detail.php?id=' . $post['id']; ?>">
                                                <p class="text-base font-bold leading-normal hover:text-primary transition-colors"><?php echo htmlspecialchars($post['car_name']); ?></p>
                                            </a>
                                            <div class="flex items-center gap-4 text-sm text-text-muted-light dark:text-text-muted-dark mt-1">
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-base">luggage</span> 
                                                    <?php echo $post['booking_count'] ?? 0; ?> chuyến
                                                </span>
                                                <?php if ($post['avg_rating']): ?>
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-base text-yellow-500">star</span> 
                                                        <?php echo number_format($post['avg_rating'], 1); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-base font-bold leading-normal mt-2">
                                                Từ <span class="text-primary"><?php echo number_format($post['price_per_day']); ?>₫</span>/ngày
                                            </p>
                                        </div>
                                        <div class="flex gap-2 mt-2">
                                            <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $post['id'] : 'post-detail.php?id=' . $post['id']; ?>" 
                                               class="flex-1 flex items-center justify-center h-10 rounded-full bg-primary text-white text-sm font-bold transition-transform hover:scale-105">
                                                Đặt xe nhanh
                                            </a>
                                            <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $post['id'] : 'post-detail.php?id=' . $post['id']; ?>" 
                                               class="flex-1 flex items-center justify-center h-10 rounded-full bg-gray-200 dark:bg-zinc-800 text-sm font-bold hover:bg-gray-300 dark:hover:bg-zinc-700">
                                                Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-12 flex justify-center">
                            <nav class="flex items-center gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&type=<?php echo htmlspecialchars($post_type); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $location ? '&location=' . urlencode($location) : ''; ?>" 
                                       class="flex items-center justify-center size-10 rounded-full bg-gray-200 dark:bg-zinc-800 text-text-muted-light dark:text-text-muted-dark hover:bg-gray-300 dark:hover:bg-zinc-700">
                                        <span class="material-symbols-outlined">chevron_left</span>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1): ?>
                                    <a href="?page=1&type=<?php echo htmlspecialchars($post_type); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $location ? '&location=' . urlencode($location) : ''; ?>" 
                                       class="flex items-center justify-center size-10 rounded-full hover:bg-gray-200 dark:hover:bg-zinc-800">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="flex items-center justify-center size-10">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&type=<?php echo htmlspecialchars($post_type); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $location ? '&location=' . urlencode($location) : ''; ?>" 
                                       class="flex items-center justify-center size-10 rounded-full <?php echo $i == $page ? 'bg-primary text-white' : 'hover:bg-gray-200 dark:hover:bg-zinc-800'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="flex items-center justify-center size-10">...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?php echo $total_pages; ?>&type=<?php echo htmlspecialchars($post_type); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $location ? '&location=' . urlencode($location) : ''; ?>" 
                                       class="flex items-center justify-center size-10 rounded-full hover:bg-gray-200 dark:hover:bg-zinc-800"><?php echo $total_pages; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&type=<?php echo htmlspecialchars($post_type); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $location ? '&location=' . urlencode($location) : ''; ?>" 
                                       class="flex items-center justify-center size-10 rounded-full bg-gray-200 dark:bg-zinc-800 text-text-muted-light dark:text-text-muted-dark hover:bg-gray-300 dark:hover:bg-zinc-700">
                                        <span class="material-symbols-outlined">chevron_right</span>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <?php include '../includes/footer.php'; ?>
    </div>
</body>
</html>
