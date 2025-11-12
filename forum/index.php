<?php
/**
 * Trang diễn đàn - Danh sách bài viết
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// Lấy tham số filter
$category_id = $_GET['category'] ?? '';
$post_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$rental_type = $_GET['rental_type'] ?? ''; // Filter theo loại dịch vụ: self-drive, with-driver, long-term
$needs = $_GET['needs'] ?? '';
$trends = $_GET['trends'] ?? '';
$budgets = $_GET['budgets'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

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
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Filter theo location (chỉ áp dụng cho bài viết có car_id)
if (!empty($location)) {
    $where_conditions[] = "(car.location = ? OR p.post_type = 'discussion')";
    $params[] = $location;
    $types .= "s";
}

// Filter theo rental_type (loại dịch vụ: self-drive, with-driver, long-term)
if (!empty($rental_type)) {
    $where_conditions[] = "(car.rental_type = ? OR p.post_type = 'discussion')";
    $params[] = $rental_type;
    $types .= "s";
}

// Filter theo nhu cầu (needs) - map với car_type hoặc description
if (!empty($needs)) {
    $needs_array = explode(',', $needs);
    $needs_conditions = [];
    foreach ($needs_array as $need) {
        $need = trim($need);
        switch ($need) {
            case 'new-driver':
                // Xe dễ lái, phù hợp người mới
                $needs_conditions[] = "(car.car_type IN ('sedan', 'hatchback') OR p.content LIKE ?)";
                $params[] = "%dễ lái%";
                $types .= "s";
                break;
            case 'work-commute':
                // Xe tiết kiệm nhiên liệu
                $needs_conditions[] = "(car.car_type IN ('sedan', 'hatchback') OR p.content LIKE ?)";
                $params[] = "%tiết kiệm%";
                $types .= "s";
                break;
            case 'family':
                // Xe gia đình (SUV, MPV)
                $needs_conditions[] = "(car.car_type IN ('suv', 'mpv') OR p.content LIKE ?)";
                $params[] = "%gia đình%";
                $types .= "s";
                break;
            case 'camping':
                // Xe chở đồ (pickup, SUV)
                $needs_conditions[] = "(car.car_type IN ('pickup', 'suv') OR p.content LIKE ?)";
                $params[] = "%chở đồ%";
                $types .= "s";
                break;
            case 'friends':
                // Xe nhiều chỗ (MPV, SUV)
                $needs_conditions[] = "(car.car_type IN ('mpv', 'suv') OR p.content LIKE ?)";
                $params[] = "%7 chỗ%";
                $types .= "s";
                break;
            case 'party':
                // Xe sang trọng
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

// Filter theo xu hướng (trends) - map với car_type
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

// Filter theo ngân sách (budgets) - filter theo price_per_day
if (!empty($budgets)) {
    $budgets_array = explode(',', $budgets);
    $budget_conditions = [];
    foreach ($budgets_array as $budget) {
        $budget = trim($budget);
        switch ($budget) {
            case 'cheap':
                // Giá rẻ: < 500,000 VNĐ/ngày
                $budget_conditions[] = "(car.price_per_day < 500000 OR p.post_type = 'discussion')";
                break;
            case 'economical':
                // Tiết kiệm: 500,000 - 1,000,000 VNĐ/ngày
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
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comment_count
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diễn đàn Thuê Xe - Trang chủ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="forum-header">
                <h1>📋 Diễn đàn Thuê Xe</h1>
                <p>Nơi bạn có thể vừa thuê xe vừa đăng bài cho thuê xe</p>
                <?php if (isLoggedIn()): ?>
                    <a href="create-post.php" class="btn btn-primary">✏️ Đăng bài mới</a>
                <?php endif; ?>
            </div>
            
            <!-- Filter và Search -->
            <div class="forum-filters">
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label for="search">Tìm kiếm</label>
                        <input type="text" id="search" name="search" placeholder="Tìm kiếm bài viết..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Danh mục</label>
                        <select id="category" name="category">
                            <option value="">Tất cả</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Loại bài viết</label>
                        <select id="type" name="type">
                            <option value="">Tất cả</option>
                            <option value="rental" <?php echo $post_type === 'rental' ? 'selected' : ''; ?>>Cho thuê xe</option>
                            <option value="discussion" <?php echo $post_type === 'discussion' ? 'selected' : ''; ?>>Thảo luận</option>
                        </select>
                    </div>
                    
                    <?php if ($post_type === 'rental' || empty($post_type)): ?>
                    <div class="form-group">
                        <label for="rental_type">Loại dịch vụ</label>
                        <select id="rental_type" name="rental_type">
                            <option value="">Tất cả</option>
                            <option value="self-drive" <?php echo $rental_type === 'self-drive' ? 'selected' : ''; ?>>🚗 Xe tự lái</option>
                            <option value="with-driver" <?php echo $rental_type === 'with-driver' ? 'selected' : ''; ?>>🚕 Xe có tài xế</option>
                            <option value="long-term" <?php echo $rental_type === 'long-term' ? 'selected' : ''; ?>>📅 Thuê xe dài hạn</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Địa điểm</label>
                        <select id="location" name="location">
                            <option value="">Tất cả</option>
                            <option value="hcm" <?php echo $location === 'hcm' ? 'selected' : ''; ?>>TP. Hồ Chí Minh</option>
                            <option value="hanoi" <?php echo $location === 'hanoi' ? 'selected' : ''; ?>>Hà Nội</option>
                            <option value="danang" <?php echo $location === 'danang' ? 'selected' : ''; ?>>Đà Nẵng</option>
                            <option value="nhatrang" <?php echo $location === 'nhatrang' ? 'selected' : ''; ?>>Nha Trang</option>
                            <option value="dalat" <?php echo $location === 'dalat' ? 'selected' : ''; ?>>Đà Lạt</option>
                            <option value="haiphong" <?php echo $location === 'haiphong' ? 'selected' : ''; ?>>Hải Phòng</option>
                            <option value="cantho" <?php echo $location === 'cantho' ? 'selected' : ''; ?>>Cần Thơ</option>
                            <option value="vungtau" <?php echo $location === 'vungtau' ? 'selected' : ''; ?>>Vũng Tàu</option>
                            <option value="phuquoc" <?php echo $location === 'phuquoc' ? 'selected' : ''; ?>>Phú Quốc</option>
                            <option value="hue" <?php echo $location === 'hue' ? 'selected' : ''; ?>>Huế</option>
                            <option value="quynhon" <?php echo $location === 'quynhon' ? 'selected' : ''; ?>>Quy Nhon</option>
                            <option value="hoian" <?php echo $location === 'hoian' ? 'selected' : ''; ?>>Hội An</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-secondary">Lọc</button>
                    <a href="index.php" class="btn btn-outline">Xóa bộ lọc</a>
                </form>
            </div>
            
            <!-- Danh sách bài viết -->
            <div class="posts-list">
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <p>Không có bài viết nào.</p>
                        <?php if (isLoggedIn()): ?>
                            <a href="create-post.php" class="btn btn-primary">Đăng bài đầu tiên</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="post-category">
                                    <span class="badge badge-<?php echo $post['post_type']; ?>">
                                        <?php 
                                        echo $post['post_type'] === 'rental' ? '🚗 Cho thuê' : '💬 Thảo luận';
                                        ?>
                                    </span>
                                    <span class="category-name"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                </div>
                                <div class="post-meta">
                                    <span>👤 <?php echo htmlspecialchars($post['full_name']); ?></span>
                                    <span>📅 <?php echo formatDate($post['created_at'], 'd/m/Y H:i'); ?></span>
                                    <span>👁️ <?php echo $post['views']; ?> lượt xem</span>
                                </div>
                            </div>
                            
                            <h3 class="post-title">
                                <a href="post-detail.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>
                                <?php if (strlen($post['content']) > 200): ?>...<?php endif; ?>
                            </div>
                            
                            <?php if ($post['post_type'] === 'rental' && $post['car_name']): ?>
                                <div class="post-car-info">
                                    <img src="../uploads/<?php echo htmlspecialchars($post['car_image'] ?: 'default-car.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($post['car_name']); ?>" 
                                         class="car-thumbnail">
                                    <div>
                                        <strong><?php echo htmlspecialchars($post['car_name']); ?></strong>
                                        <?php if ($post['rental_type']): ?>
                                            <div class="rental-type-badge">
                                                <?php
                                                $rental_types = [
                                                    'self-drive' => '🚗 Xe tự lái',
                                                    'with-driver' => '🚕 Xe có tài xế',
                                                    'long-term' => '📅 Thuê dài hạn'
                                                ];
                                                echo $rental_types[$post['rental_type']] ?? '🚗 Xe tự lái';
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="price">
                                            <?php 
                                            if ($post['rental_type'] === 'long-term') {
                                                echo formatCurrency($post['price_per_day']) . '/tháng';
                                            } else {
                                                echo formatCurrency($post['price_per_day']) . '/ngày';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-footer">
                                <a href="post-detail.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">
                                    Xem chi tiết
                                </a>
                                <span class="comment-count">💬 <?php echo $post['comment_count']; ?> bình luận</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $post_type ? '&type=' . $post_type : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn btn-outline">← Trước</a>
                            <?php endif; ?>
                            
                            <span>Trang <?php echo $page; ?> / <?php echo $total_pages; ?></span>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $post_type ? '&type=' . $post_type : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn btn-outline">Sau →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

