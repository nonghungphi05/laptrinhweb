<?php
/**
 * Xóa bài viết
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$post_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($post_id === 0) {
    header('Location: my-posts.php');
    exit();
}

// Kiểm tra quyền sở hữu
$stmt = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my-posts.php');
    exit();
}

// Soft delete - chỉ đổi status
$update_stmt = $conn->prepare("UPDATE posts SET status = 'deleted' WHERE id = ?");
$update_stmt->bind_param("i", $post_id);
$update_stmt->execute();

header('Location: my-posts.php?deleted=1');
exit();

