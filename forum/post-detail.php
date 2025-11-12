<?php
/**
 * Trang chi tiết bài viết với bình luận
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

$post_id = intval($_GET['id'] ?? 0);

if ($post_id === 0) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin bài viết
$stmt = $conn->prepare("SELECT p.*, 
        u.username, u.full_name, u.phone,
        c.name as category_name, c.slug as category_slug,
        car.id as car_id, car.name as car_name, car.description as car_description, 
        car.price_per_day, car.image as car_image, car.car_type, car.status as car_status,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comment_count
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
    header('Location: index.php');
    exit();
}

$post = $result->fetch_assoc();

// Tăng lượt xem
$conn->query("UPDATE posts SET views = views + 1 WHERE id = $post_id");

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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Diễn đàn Thuê Xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">Diễn đàn</a> / 
                <a href="index.php?category=<?php echo $post['category_id']; ?>"><?php echo htmlspecialchars($post['category_name']); ?></a> / 
                <span><?php echo htmlspecialchars($post['title']); ?></span>
            </div>
            
            <!-- Bài viết -->
            <article class="post-detail">
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
                        <span>👤 <strong><?php echo htmlspecialchars($post['full_name']); ?></strong></span>
                        <span>📅 <?php echo formatDate($post['created_at'], 'd/m/Y H:i'); ?></span>
                        <span>👁️ <?php echo $post['views']; ?> lượt xem</span>
                        <span>💬 <?php echo $post['comment_count']; ?> bình luận</span>
                    </div>
                </div>
                
                <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                
                <!-- Thông tin xe (nếu là bài cho thuê) -->
                <?php if ($post['post_type'] === 'rental' && $post['car_id']): ?>
                    <div class="car-detail-section">
                        <h2>🚗 Thông tin xe cho thuê</h2>
                        <div class="car-info-card">
                            <?php if ($post['car_image']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($post['car_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['car_name']); ?>" 
                                     class="car-image">
                            <?php endif; ?>
                            <div class="car-info">
                                <h3><?php echo htmlspecialchars($post['car_name']); ?></h3>
                                <div class="car-details">
                                    <p><strong>Loại xe:</strong> <?php echo htmlspecialchars(ucfirst($post['car_type'])); ?></p>
                                    <p><strong>Giá thuê:</strong> <span class="price-large"><?php echo formatCurrency($post['price_per_day']); ?>/ngày</span></p>
                                    <p><strong>Trạng thái:</strong> 
                                        <span class="status status-<?php echo $post['car_status']; ?>">
                                            <?php 
                                            $status_text = [
                                                'available' => 'Còn xe',
                                                'rented' => 'Đang cho thuê',
                                                'maintenance' => 'Bảo trì'
                                            ];
                                            echo $status_text[$post['car_status']] ?? $post['car_status'];
                                            ?>
                                        </span>
                                    </p>
                                    <?php if ($post['car_description']): ?>
                                        <p><strong>Mô tả:</strong> <?php echo nl2br(htmlspecialchars($post['car_description'])); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Liên hệ:</strong> <?php echo htmlspecialchars($post['full_name']); ?> - <?php echo htmlspecialchars($post['phone'] ?? 'N/A'); ?></p>
                                </div>
                                
                                <?php if (isLoggedIn() && $post['car_status'] === 'available' && $_SESSION['user_id'] != $post['user_id']): ?>
                                    <div class="car-actions">
                                        <a href="../client/booking.php?car_id=<?php echo $post['car_id']; ?>&post_id=<?php echo $post_id; ?>" 
                                           class="btn btn-primary btn-large">📅 Đặt xe ngay</a>
                                    </div>
                                <?php elseif (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                                    <div class="car-actions">
                                        <a href="edit-post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">✏️ Sửa bài viết</a>
                                    </div>
                                <?php elseif (!isLoggedIn()): ?>
                                    <div class="car-actions">
                                        <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                           class="btn btn-primary btn-large">Đăng nhập để đặt xe</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="post-actions">
                    <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                        <a href="edit-post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">✏️ Sửa bài viết</a>
                        <a href="delete-post.php?id=<?php echo $post_id; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');">🗑️ Xóa bài viết</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline">← Quay lại diễn đàn</a>
                </div>
            </article>
            
            <!-- Bình luận -->
            <section class="comments-section">
                <h2>💬 Bình luận (<?php echo $post['comment_count']; ?>)</h2>
                
                <?php if ($comment_error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($comment_error); ?></div>
                <?php endif; ?>
                
                <?php if ($comment_success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($comment_success); ?></div>
                <?php endif; ?>
                
                <!-- Form bình luận -->
                <?php if (isLoggedIn()): ?>
                    <form method="POST" action="" class="comment-form">
                        <input type="hidden" name="comment" value="1">
                        <div class="form-group">
                            <label for="content">Viết bình luận</label>
                            <textarea id="content" name="content" rows="4" required 
                                      placeholder="Nhập bình luận của bạn..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Gửi bình luận</button>
                    </form>
                <?php else: ?>
                    <p class="text-center">
                        <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">Đăng nhập để bình luận</a>
                    </p>
                <?php endif; ?>
                
                <!-- Danh sách bình luận -->
                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <p class="text-center">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong>
                                    <span class="comment-date"><?php echo formatDate($comment['created_at'], 'd/m/Y H:i'); ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                                <?php if (isLoggedIn()): ?>
                                    <div class="comment-actions">
                                        <button class="btn-reply" onclick="showReplyForm(<?php echo $comment['id']; ?>)">Trả lời</button>
                                    </div>
                                    <!-- Form trả lời (ẩn) -->
                                    <div class="reply-form" id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                                        <form method="POST" action="">
                                            <input type="hidden" name="comment" value="1">
                                            <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                            <div class="form-group">
                                                <textarea name="content" rows="3" required placeholder="Nhập trả lời..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">Gửi trả lời</button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="hideReplyForm(<?php echo $comment['id']; ?>)">Hủy</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Replies -->
                                <?php 
                                $replies = getReplies($conn, $comment['id']);
                                if (!empty($replies)): 
                                ?>
                                    <div class="replies">
                                        <?php foreach ($replies as $reply): ?>
                                            <div class="comment reply">
                                                <div class="comment-header">
                                                    <strong><?php echo htmlspecialchars($reply['full_name']); ?></strong>
                                                    <span class="comment-date"><?php echo formatDate($reply['created_at'], 'd/m/Y H:i'); ?></span>
                                                </div>
                                                <div class="comment-content">
                                                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
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
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function showReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'block';
        }
        
        function hideReplyForm(commentId) {
            document.getElementById('reply-form-' + commentId).style.display = 'none';
        }
    </script>
</body>
</html>

