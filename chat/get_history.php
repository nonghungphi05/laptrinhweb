<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();

header('Content-Type: application/json');

$current_user_id = (int)$_SESSION['user_id'];
$partner_id = (int)($_GET['partner_id'] ?? 0);

if (!$partner_id || $partner_id === $current_user_id) {
    http_response_code(422);
    echo json_encode(['error' => 'Thiếu hoặc sai đối tác chat.']);
    exit;
}

$stmt = $conn->prepare("SELECT sender_id, receiver_id, message_content, created_at 
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC");
$stmt->bind_param('iiii', $current_user_id, $partner_id, $partner_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'messages' => array_map(function ($message) {
        return [
            'sender_id'   => (int)$message['sender_id'],
            'receiver_id' => (int)$message['receiver_id'],
            'message'     => htmlspecialchars($message['message_content'], ENT_QUOTES, 'UTF-8'),
            'created_at'  => $message['created_at']
        ];
    }, $result)
]);

