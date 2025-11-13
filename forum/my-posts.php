<?php
/**
 * Trang quản lý bài viết của mình (Tailwind CSS Design)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$base_path = getBasePath();
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Xử lý xóa bài viết
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Soft delete - chỉ đổi status
        $update_stmt = $conn->prepare("UPDATE posts SET status = 'deleted' WHERE id = ?");
        $update_stmt->bind_param("i", $delete_id);
        $update_stmt->execute();
        header('Location: my-posts.php?deleted=1');
        exit();
    }
}

// Đếm tổng số bài viết
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ? AND status != 'deleted'");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $per_page);

// Lấy danh sách bài viết
$offset = ($page - 1) * $per_page;
$stmt = $conn->prepare("SELECT p.*, 
        c.name as category_name,
        car.name as car_name, car.image as car_image, car.price_per_day,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comment_count
        FROM posts p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN cars car ON p.car_id = car.id
        WHERE p.user_id = ? AND p.status != 'deleted'
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $per_page, $offset);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Bài viết của tôi - CarRental</title>
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
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "Noto Sans", "sans-serif"]
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
<body class="font-display bg-background-light dark:bg-background-dark">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <div class="flex flex-1 justify-center py-5">
                <div class="layout-content-container flex flex-col w-full max-w-6xl flex-1">
                    <!-- Header -->
                    <?php include '../includes/header.php'; ?>
                    
                    <!-- Main Content -->
                    <main class="flex-1 py-8 px-4">
                        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                            <div>
                                <h1 class="text-4xl font-black leading-tight tracking-[-0.033em] text-[#181411] dark:text-white">Bài viết của tôi</h1>
                                <p class="text-gray-600 dark:text-gray-300 mt-2">Quản lý tất cả bài viết cho thuê xe và thảo luận của bạn.</p>
                            </div>
                            <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : 'create-post.php'; ?>" 
                               class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                <span class="material-symbols-outlined">add</span>
                                <span>Đăng bài mới</span>
                            </a>
                        </div>
                        
                        <?php if (isset($_GET['deleted'])): ?>
                            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <p class="text-sm text-green-600 dark:text-green-400">Đã xóa bài viết thành công!</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($posts)): ?>
                            <div class="bg-white dark:bg-background-dark/50 p-12 rounded-xl shadow-lg text-center">
                                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">article</span>
                                <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">Bạn chưa có bài viết nào.</p>
                                <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : 'create-post.php'; ?>" 
                                   class="inline-block px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold">
                                    Đăng bài đầu tiên
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach ($posts as $post): ?>
                                    <div class="bg-white dark:bg-background-dark/50 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                                        <div class="flex flex-col md:flex-row gap-6">
                                            <!-- Image (nếu là bài cho thuê) -->
                                            <?php if ($post['post_type'] === 'rental' && $post['car_image']): ?>
                                                <div class="w-full md:w-48 h-32 md:h-auto bg-center bg-no-repeat bg-cover rounded-lg flex-shrink-0" 
                                                     style='background-image: url("<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($post['car_image'] ?: 'default-car.jpg'); ?>");'
                                                     onerror="this.style.backgroundImage='url(<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : '../uploads/default-car.jpg'; ?>)'">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Content -->
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                                    <span class="inline-block px-3 py-1 rounded-full <?php echo $post['post_type'] === 'rental' ? 'bg-primary/20 text-primary' : 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300'; ?> text-sm font-medium">
                                                        <?php echo $post['post_type'] === 'rental' ? 'Cho thuê xe' : 'Thảo luận'; ?>
                                                    </span>
                                                    <span class="inline-block px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium">
                                                        <?php echo htmlspecialchars($post['category_name']); ?>
                                                    </span>
                                                    <?php if ($post['status'] === 'closed'): ?>
                                                        <span class="inline-block px-3 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 text-sm font-medium">
                                                            Đã đóng
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <h3 class="text-xl font-bold text-[#181411] dark:text-white mb-2 hover:text-primary transition-colors">
                                                    <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $post['id'] : 'post-detail.php?id=' . $post['id']; ?>">
                                                        <?php echo htmlspecialchars($post['title']); ?>
                                                    </a>
                                                </h3>
                                                
                                                <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                                    <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>
                                                    <?php if (strlen($post['content']) > 200): ?>...<?php endif; ?>
                                                </p>
                                                
                                                <?php if ($post['post_type'] === 'rental' && $post['car_name']): ?>
                                                    <div class="flex items-center gap-4 mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                        <span class="material-symbols-outlined text-primary">directions_car</span>
                                                        <div class="flex-1">
                                                            <p class="font-bold text-[#181411] dark:text-white"><?php echo htmlspecialchars($post['car_name']); ?></p>
                                                            <?php if ($post['price_per_day']): ?>
                                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                                    <?php echo number_format($post['price_per_day']); ?>đ/ngày
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-base">calendar_today</span>
                                                        <?php echo formatDate($post['created_at'], 'd/m/Y H:i'); ?>
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-base">visibility</span>
                                                        <?php echo $post['views']; ?> lượt xem
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-base">comment</span>
                                                        <?php echo $post['comment_count']; ?> bình luận
                                                    </span>
                                                </div>
                                                
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $post['id'] : 'post-detail.php?id=' . $post['id']; ?>" 
                                                       class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium">
                                                        Xem chi tiết
                                                    </a>
                                                    <a href="<?php echo $base_path ? $base_path . '/forum/edit-post.php?id=' . $post['id'] : 'edit-post.php?id=' . $post['id']; ?>" 
                                                       class="px-4 py-2 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors text-sm font-medium">
                                                        <span class="material-symbols-outlined text-base align-middle">edit</span>
                                                        Sửa
                                                    </a>
                                                    <a href="?delete=<?php echo $post['id']; ?>" 
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');"
                                                       class="px-4 py-2 bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/70 transition-colors text-sm font-medium">
                                                        <span class="material-symbols-outlined text-base align-middle">delete</span>
                                                        Xóa
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="mt-8 flex justify-center items-center gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>" 
                                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm font-medium">
                                            ← Trước
                                        </a>
                                    <?php endif; ?>
                                    
                                    <span class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                        Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
                                    </span>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" 
                                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-sm font-medium">
                                            Sau →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </main>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
