<?php
/**
 * Trang quản lý bài viết của mình
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$user_id = $_SESSION['user_id'];
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
        car.name as car_name,
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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài viết của tôi - Diễn đàn Thuê Xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1>📝 Bài viết của tôi</h1>
                <a href="create-post.php" class="btn btn-primary">✏️ Đăng bài mới</a>
            </div>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Đã xóa bài viết thành công!</div>
            <?php endif; ?>
            
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <p>Bạn chưa có bài viết nào.</p>
                    <a href="create-post.php" class="btn btn-primary">Đăng bài đầu tiên</a>
                </div>
            <?php else: ?>
                <div class="posts-list">
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
                                    <?php if ($post['status'] === 'closed'): ?>
                                        <span class="badge badge-warning">Đã đóng</span>
                                    <?php endif; ?>
                                </div>
                                <div class="post-meta">
                                    <span>📅 <?php echo formatDate($post['created_at'], 'd/m/Y H:i'); ?></span>
                                    <span>👁️ <?php echo $post['views']; ?> lượt xem</span>
                                    <span>💬 <?php echo $post['comment_count']; ?> bình luận</span>
                                </div>
                            </div>
                            
                            <h3 class="post-title">
                                <a href="post-detail.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 150))); ?>
                                <?php if (strlen($post['content']) > 150): ?>...<?php endif; ?>
                            </div>
                            
                            <?php if ($post['post_type'] === 'rental' && $post['car_name']): ?>
                                <div class="post-car-info">
                                    <strong>🚗 <?php echo htmlspecialchars($post['car_name']); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-actions">
                                <a href="post-detail.php?id=<?php echo $post['id']; ?>" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-secondary btn-sm">✏️ Sửa</a>
                                <a href="?delete=<?php echo $post['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');">🗑️ Xóa</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-outline">← Trước</a>
                        <?php endif; ?>
                        
                        <span>Trang <?php echo $page; ?> / <?php echo $total_pages; ?></span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-outline">Sau →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

