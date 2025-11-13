<?php
/**
 * Trang chi tiết bài viết với bình luận (Tailwind CSS Design)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$base_path = getBasePath();
$post_id = intval($_GET['id'] ?? 0);

if ($post_id === 0) {
    header('Location: ' . $base_path . '/forum/index.php');
    exit();
}

// Lấy thông tin bài viết
$stmt = $conn->prepare("SELECT p.*, 
        u.username, u.full_name, u.phone,
        c.name as category_name, c.slug as category_slug,
        car.id as car_id, car.name as car_name, car.description as car_description, 
        car.price_per_day, car.image as car_image, car.car_type, car.status as car_status, car.location, car.rental_type,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comment_count,
        (SELECT AVG(rating) FROM reviews WHERE car_id = car.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE car_id = car.id) as review_count,
        (SELECT COUNT(*) FROM bookings WHERE car_id = car.id) as booking_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN cars car ON p.car_id = car.id
        WHERE p.id = ? AND p.status = 'active'");

if ($stmt === false) {
    die("Lỗi SQL: " . $conn->error);
}

$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . $base_path . '/forum/index.php');
    exit();
}

$post = $result->fetch_assoc();

// Tăng lượt xem
$conn->query("UPDATE posts SET views = views + 1 WHERE id = $post_id");

// Lấy reviews nếu có car_id
$reviews = [];
if ($post['car_id']) {
    $reviews_stmt = $conn->prepare("SELECT r.*, u.full_name, u.username
        FROM reviews r
        JOIN users u ON r.customer_id = u.id
        WHERE r.car_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5");
    if ($reviews_stmt) {
        $reviews_stmt->bind_param("i", $post['car_id']);
        $reviews_stmt->execute();
        $reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $reviews_stmt->close();
    }
}

// Xử lý bình luận
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isLoggedIn()) {
        $comment_error = 'Vui lòng đăng nhập để bình luận';
    } else {
        $comment_content = trim($_POST['content'] ?? '');
        $parent_id = intval($_POST['parent_id'] ?? 0);
        
        if (empty($comment_content)) {
            $comment_error = 'Vui lòng nhập nội dung bình luận';
        } else {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, parent_id, status) VALUES (?, ?, ?, ?, 'active')");
            if ($stmt === false) {
                $comment_error = 'Lỗi SQL: ' . $conn->error;
            } else {
                $parent_id = $parent_id > 0 ? $parent_id : null;
                $stmt->bind_param("iisi", $post_id, $user_id, $comment_content, $parent_id);
                
                if ($stmt->execute()) {
                    $comment_success = 'Bình luận thành công!';
                    $stmt->close();
                    header("Location: post-detail.php?id=$post_id");
                    exit();
                } else {
                    $comment_error = 'Có lỗi xảy ra: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

// Lấy bình luận (chỉ lấy bình luận cha, reply sẽ được lấy riêng)
$comments_stmt = $conn->prepare("SELECT cm.*, u.username, u.full_name 
    FROM comments cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.post_id = ? AND cm.parent_id IS NULL AND cm.status = 'active'
    ORDER BY cm.created_at ASC");

if ($comments_stmt === false) {
    $comments = [];
} else {
    $comments_stmt->bind_param("i", $post_id);
    $comments_stmt->execute();
    $comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $comments_stmt->close();
}

// Lấy replies cho mỗi comment
function getReplies($conn, $parent_id) {
    $stmt = $conn->prepare("SELECT cm.*, u.username, u.full_name 
        FROM comments cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.parent_id = ? AND cm.status = 'active'
        ORDER BY cm.created_at ASC");
    
    if ($stmt === false) {
        return [];
    }
    
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

// Map car type to Vietnamese
$car_type_map = [
    'sedan' => 'Sedan',
    'suv' => 'SUV',
    'pickup' => 'Bán tải',
    'mpv' => 'MPV',
    'hatchback' => 'Hatchback'
];

// Map location to Vietnamese
$location_map = [
    'hcm' => 'TP. Hồ Chí Minh',
    'hanoi' => 'Hà Nội',
    'danang' => 'Đà Nẵng',
    'nhatrang' => 'Nha Trang',
    'dalat' => 'Đà Lạt'
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo htmlspecialchars($post['title']); ?> - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
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
                        "calm-blue": "#3498DB",
                        "vibrant-orange": "#f98006",
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
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <div class="flex flex-1 justify-center p-4 sm:p-6 md:p-8 lg:p-12">
                <div class="layout-content-container flex flex-col w-full max-w-6xl flex-1">
                    <!-- Header -->
                    <?php include '../includes/header.php'; ?>
                    
                    <!-- Breadcrumbs -->
                    <div class="mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="<?php echo $base_path ? $base_path . '/index.php' : '../index.php'; ?>" 
                               class="text-gray-500 dark:text-gray-400 text-sm font-medium hover:text-primary dark:hover:text-primary">Trang chủ</a>
                            <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">/</span>
                            <a href="<?php echo $base_path ? $base_path . '/forum/index.php' : 'index.php'; ?>" 
                               class="text-gray-500 dark:text-gray-400 text-sm font-medium hover:text-primary dark:hover:text-primary">Danh sách xe</a>
                            <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">/</span>
                            <span class="text-gray-800 dark:text-gray-200 text-sm font-medium"><?php echo htmlspecialchars($post['car_name'] ?? $post['title']); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($post['post_type'] === 'rental' && $post['car_id']): ?>
                        <!-- Car Detail Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                            <!-- Left Column: Image Gallery -->
                            <div class="lg:col-span-3">
                                <!-- Header Image -->
                                <div class="mb-4">
                                    <div class="bg-cover bg-center flex flex-col justify-end overflow-hidden bg-white rounded-xl min-h-[400px] shadow-sm" 
                                         style='background-image: linear-gradient(0deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0) 25%), url("<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($post['car_image'] ?: 'default-car.jpg'); ?>");'
                                         onerror="this.style.backgroundImage='url(<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : '../uploads/default-car.jpg'; ?>)'">
                                        <div class="flex justify-center gap-2 p-5">
                                            <div class="size-2 rounded-full bg-white"></div>
                                            <div class="size-2 rounded-full bg-white/60"></div>
                                            <div class="size-2 rounded-full bg-white/60"></div>
                                            <div class="size-2 rounded-full bg-white/60"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Thumbnail Gallery (placeholder - có thể mở rộng sau) -->
                                <div class="grid grid-cols-4 gap-4">
                                    <div class="w-full h-24 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                                    <div class="w-full h-24 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                                    <div class="w-full h-24 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                                    <div class="w-full h-24 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Details & Booking -->
                            <div class="lg:col-span-2 flex flex-col gap-6">
                                <!-- Page Heading -->
                                <div>
                                    <div class="flex min-w-72 flex-col gap-2">
                                        <p class="text-gray-900 dark:text-white text-3xl md:text-4xl font-black leading-tight tracking-[-0.033em]"><?php echo htmlspecialchars($post['car_name']); ?></p>
                                        <p class="text-gray-500 dark:text-gray-400 text-base font-normal leading-normal"><?php echo htmlspecialchars($post['car_description'] ?: $post['content']); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Chips -->
                                <div class="flex gap-2 flex-wrap">
                                    <div class="flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-primary/20 px-4">
                                        <p class="text-primary text-sm font-medium leading-normal"><?php echo $car_type_map[$post['car_type']] ?? ucfirst($post['car_type']); ?></p>
                                    </div>
                                    <?php if ($post['location']): ?>
                                        <div class="flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-calm-blue/20 px-4">
                                            <p class="text-calm-blue text-sm font-medium leading-normal"><?php echo $location_map[$post['location']] ?? ucfirst($post['location']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Icon-based Spec List -->
                                <div class="grid grid-cols-2 gap-4 border-t border-b border-gray-200 dark:border-gray-700 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-calm-blue">group</span>
                                        <div class="flex flex-col">
                                            <p class="text-gray-500 dark:text-gray-400 text-sm font-normal">Số chỗ</p>
                                            <p class="text-gray-800 dark:text-gray-200 text-base font-medium">5 chỗ</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-calm-blue">settings</span>
                                        <div class="flex flex-col">
                                            <p class="text-gray-500 dark:text-gray-400 text-sm font-normal">Hộp số</p>
                                            <p class="text-gray-800 dark:text-gray-200 text-base font-medium">Tự động</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-calm-blue">location_on</span>
                                        <div class="flex flex-col">
                                            <p class="text-gray-500 dark:text-gray-400 text-sm font-normal">Địa điểm</p>
                                            <p class="text-gray-800 dark:text-gray-200 text-base font-medium"><?php echo $location_map[$post['location']] ?? 'N/A'; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-calm-blue">local_gas_station</span>
                                        <div class="flex flex-col">
                                            <p class="text-gray-500 dark:text-gray-400 text-sm font-normal">Nhiên liệu</p>
                                            <p class="text-gray-800 dark:text-gray-200 text-base font-medium">Xăng</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Owner Information -->
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-14 bg-gray-300" 
                                                 style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($post['full_name']); ?>&background=f98006&color=fff");'></div>
                                            <div>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">Chủ xe</p>
                                                <p class="text-lg font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($post['full_name']); ?></p>
                                                <div class="flex items-center gap-1 mt-1">
                                                    <?php if ($post['avg_rating']): ?>
                                                        <span class="material-symbols-outlined text-yellow-500 !text-[16px]">star</span>
                                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo number_format($post['avg_rating'], 1); ?> (<?php echo $post['review_count'] ?? 0; ?> đánh giá)</span>
                                                    <?php else: ?>
                                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Chưa có đánh giá</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="flex items-center justify-center gap-2 h-10 px-4 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                                            <span class="material-symbols-outlined !text-xl">chat</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Booking Widget -->
                                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col gap-4">
                                    <div class="flex justify-between items-baseline">
                                        <div class="flex items-baseline gap-2">
                                            <p class="text-primary text-3xl font-bold"><?php echo number_format($post['price_per_day'] / 1000); ?>k</p>
                                            <p class="text-gray-500 dark:text-gray-400">/ ngày</p>
                                        </div>
                                        <?php if ($post['booking_count'] > 0): ?>
                                            <div class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                <span class="material-symbols-outlined !text-base">local_offer</span>
                                                <p class="text-sm font-medium"><?php echo $post['booking_count']; ?> chuyến đã thuê</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($post['car_status'] === 'available'): ?>
                                        <form method="GET" action="<?php echo $base_path ? $base_path . '/client/booking.php' : '../client/booking.php'; ?>">
                                            <input type="hidden" name="car_id" value="<?php echo $post['car_id']; ?>">
                                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300" for="start-date">Nhận xe</label>
                                                    <input class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-primary focus:ring-primary dark:focus:border-primary dark:focus:ring-primary" 
                                                           id="start-date" name="start_date" type="date" required/>
                                                </div>
                                                <div>
                                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300" for="end-date">Trả xe</label>
                                                    <input class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-primary focus:ring-primary dark:focus:border-primary dark:focus:ring-primary" 
                                                           id="end-date" name="end_date" type="date" required/>
                                                </div>
                                            </div>
                                            <?php if (isLoggedIn() && $_SESSION['user_id'] != $post['user_id']): ?>
                                                <button type="submit" class="w-full bg-primary text-white font-bold py-3 px-4 rounded-lg hover:bg-orange-500 transition-colors shadow-md shadow-primary/30 flex items-center justify-center gap-2 mt-4">
                                                    <span class="material-symbols-outlined">bolt</span>
                                                    <span>Đặt xe ngay</span>
                                                </button>
                                            <?php elseif (!isLoggedIn()): ?>
                                                <a href="<?php echo $base_path ? $base_path . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']) : '../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']); ?>" 
                                                   class="w-full bg-primary text-white font-bold py-3 px-4 rounded-lg hover:bg-orange-500 transition-colors shadow-md shadow-primary/30 flex items-center justify-center gap-2 mt-4">
                                                    <span class="material-symbols-outlined">bolt</span>
                                                    <span>Đăng nhập để đặt xe</span>
                                                </a>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg text-center">
                                            <p class="text-gray-600 dark:text-gray-300">Xe hiện không khả dụng</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reviews Section -->
                        <?php if (!empty($reviews)): ?>
                            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Đánh giá từ khách hàng</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700">
                                            <div class="flex items-center mb-3">
                                                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12 bg-gray-300 mr-4" 
                                                     style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($review['full_name']); ?>&background=3498DB&color=fff");'></div>
                                                <div>
                                                    <p class="font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($review['full_name']); ?></p>
                                                    <div class="flex items-center text-yellow-500">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="material-symbols-outlined !text-base"><?php echo $i <= $review['rating'] ? 'star' : 'star_border'; ?></span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-gray-600 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Discussion Post Layout -->
                        <article class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="mb-4">
                                <span class="inline-block px-3 py-1 rounded-full bg-primary/20 text-primary text-sm font-medium mb-2">
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </span>
                                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    <span>👤 <?php echo htmlspecialchars($post['full_name']); ?></span>
                                    <span>📅 <?php echo formatDate($post['created_at'], 'd/m/Y H:i'); ?></span>
                                    <span>👁️ <?php echo $post['views']; ?> lượt xem</span>
                                    <span>💬 <?php echo $post['comment_count']; ?> bình luận</span>
                                </div>
                            </div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>
                            <div class="prose dark:prose-invert max-w-none">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>
                        </article>
                    <?php endif; ?>
                    
                    <!-- Comments Section -->
                    <section class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">💬 Bình luận (<?php echo $post['comment_count']; ?>)</h2>
                        
                        <?php if ($comment_error): ?>
                            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($comment_error); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($comment_success): ?>
                            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <p class="text-sm text-green-600 dark:text-green-400"><?php echo htmlspecialchars($comment_success); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Form bình luận -->
                        <?php if (isLoggedIn()): ?>
                            <form method="POST" action="" class="mb-8">
                                <input type="hidden" name="comment" value="1">
                                <div class="mb-4">
                                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Viết bình luận</label>
                                    <textarea id="content" 
                                              name="content" 
                                              rows="4" 
                                              required 
                                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white"
                                              placeholder="Nhập bình luận của bạn..."></textarea>
                                </div>
                                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold">
                                    Gửi bình luận
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <a href="<?php echo $base_path ? $base_path . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']) : '../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']); ?>" 
                                   class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold">
                                    Đăng nhập để bình luận
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Danh sách bình luận -->
                        <div class="space-y-6">
                            <?php if (empty($comments)): ?>
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 bg-gray-300" 
                                                 style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($comment['full_name']); ?>&background=3498DB&color=fff");'></div>
                                            <div>
                                                <p class="font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($comment['full_name']); ?></p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo formatDate($comment['created_at'], 'd/m/Y H:i'); ?></p>
                                            </div>
                                        </div>
                                        <p class="text-gray-700 dark:text-gray-300 mb-3"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        
                                        <?php if (isLoggedIn()): ?>
                                            <button onclick="showReplyForm(<?php echo $comment['id']; ?>)" 
                                                    class="text-sm text-primary hover:underline mb-3">
                                                Trả lời
                                            </button>
                                            
                                            <!-- Form trả lời (ẩn) -->
                                            <div id="reply-form-<?php echo $comment['id']; ?>" class="hidden mt-3 pl-8 border-l-2 border-gray-200 dark:border-gray-700">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="comment" value="1">
                                                    <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                                    <textarea name="content" 
                                                              rows="3" 
                                                              required 
                                                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white mb-2"
                                                              placeholder="Nhập trả lời..."></textarea>
                                                    <div class="flex gap-2">
                                                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-bold">
                                                            Gửi trả lời
                                                        </button>
                                                        <button type="button" 
                                                                onclick="hideReplyForm(<?php echo $comment['id']; ?>)" 
                                                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-sm">
                                                            Hủy
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Replies -->
                                        <?php 
                                        $replies = getReplies($conn, $comment['id']);
                                        if (!empty($replies)): 
                                        ?>
                                            <div class="mt-4 pl-8 border-l-2 border-gray-200 dark:border-gray-700 space-y-4">
                                                <?php foreach ($replies as $reply): ?>
                                                    <div class="flex items-center gap-3">
                                                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 bg-gray-300" 
                                                             style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($reply['full_name']); ?>&background=3498DB&color=fff");'></div>
                                                        <div class="flex-1">
                                                            <p class="font-semibold text-sm text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($reply['full_name']); ?></p>
                                                            <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php echo formatDate($reply['created_at'], 'd/m/Y H:i'); ?></p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function showReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).classList.remove('hidden');
        }
        
        function hideReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).classList.add('hidden');
        }
    </script>
</body>
</html>
