<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../vendor/autoload.php';

requireLogin();

use Pusher\Pusher;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$sender_id   = (int)($_POST['sender_id'] ?? 0);
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

if (!$sender_id || !$receiver_id || $message === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Thiếu dữ liệu gửi tin nhắn.']);
    exit;
}

if ($sender_id !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Bạn không có quyền gửi tin nhắn này.']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param('i', $receiver_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy người nhận.']);
    exit;
}

$insert = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_content) VALUES (?, ?, ?)");
$insert->bind_param('iis', $sender_id, $receiver_id, $message);
$insert->execute();
$message_id = $insert->insert_id;

$payload = [
    'id'          => $message_id,
    'sender_id'   => $sender_id,
    'receiver_id' => $receiver_id,
    'message'     => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
    'created_at'  => date('Y-m-d H:i:s')
];

$pusher = new Pusher(
    '21b6af37da0f37a7ce0c',
    '8aa384016def7b1310ee',
    '2079926',
    ['cluster' => 'ap1', 'useTLS' => true]
);

$pusher->trigger('chat_channel_' . $receiver_id, 'new_message', $payload);

echo json_encode(['success' => true, 'message' => $payload]);

