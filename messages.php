<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/helpers.php';

requireLogin();

$current_user_id = (int)$_SESSION['user_id'];
$base_path = getBasePath();

$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message_content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conversation_sql = "
    SELECT 
        u.id AS partner_id,
        u.full_name,
        u.email,
        MAX(m.created_at) AS last_time,
        SUBSTRING_INDEX(MAX(CONCAT(m.created_at, '|||', m.message_content)), '|||', -1) AS last_message
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY u.id, u.full_name, u.email
    ORDER BY last_time DESC";
$conversation_stmt = $conn->prepare($conversation_sql);
if ($conversation_stmt) {
    $conversation_stmt->bind_param('iii', $current_user_id, $current_user_id, $current_user_id);
    $conversation_stmt->execute();
    $conversations = $conversation_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    error_log('Failed to prepare conversation query: ' . $conn->error);
    $conversations = [];
}

$active_partner_id = isset($_GET['with']) ? (int)$_GET['with'] : 0;
if (!$active_partner_id && !empty($conversations)) {
    $active_partner_id = (int)$conversations[0]['partner_id'];
}

$active_partner = null;
if ($active_partner_id) {
    $partner_stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
    if ($partner_stmt) {
        $partner_stmt->bind_param('i', $active_partner_id);
        $partner_stmt->execute();
        $active_partner = $partner_stmt->get_result()->fetch_assoc();
    }
}

$messages_history = [];
if ($active_partner_id && $active_partner) {
    $history_stmt = $conn->prepare("SELECT sender_id, receiver_id, message_content, created_at 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC");
    if ($history_stmt) {
        $history_stmt->bind_param('iiii', $current_user_id, $active_partner_id, $active_partner_id, $current_user_id);
        $history_stmt->execute();
        $messages_history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

function formatConversationTime(?string $timestamp): string
{
    if (!$timestamp) {
        return '';
    }
    $ts = strtotime($timestamp);
    if (!$ts) {
        return '';
    }
    if (date('Y-m-d') === date('Y-m-d', $ts)) {
        return date('H:i', $ts);
    }
    return date('d/m', $ts);
}

$send_endpoint = $base_path ? $base_path . '/chat/send_message.php' : 'chat/send_message.php';
$history_endpoint = $base_path ? $base_path . '/chat/get_history.php' : 'chat/get_history.php';
?>
<!DOCTYPE html>
<html lang="vi" class="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hộp thư - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,700;1,400&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#f98006",
                        "background-light": "#f8f7f5",
                        "background-dark": "#23190f"
                    },
                    fontFamily: {
                        display: ["Plus Jakarta Sans", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: "Plus Jakarta Sans", sans-serif; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(0,0,0,.15); border-radius: 999px; }
    </style>
</head>
<body class="font-display bg-background-light text-[#181411]">
<div class="relative flex min-h-screen flex-col">
    <div class="layout-container flex h-full grow flex-col">
        <div class="flex flex-1 justify-center py-5">
            <div class="layout-content-container flex flex-col w-full max-w-6xl flex-1">
                <?php include __DIR__ . '/includes/header.php'; ?>
                <main class="flex-1 py-8">
                    <div class="bg-white rounded-2xl shadow-xl border border-[#f0eae6] overflow-hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-[340px_1fr] h-[70vh]">
                            <aside class="border-r border-[#f0eae6] flex flex-col">
                                <div class="p-4 border-b border-[#f0eae6]">
                                    <h2 class="text-2xl font-bold mb-3">Hộp thư</h2>
                                    <div class="relative">
                                        <span class="material-symbols-outlined text-gray-400 absolute left-3 top-1/2 -translate-y-1/2">search</span>
                                        <input type="text" placeholder="Tìm cuộc trò chuyện" class="w-full h-11 rounded-lg border border-[#ecdcd1] bg-[#fdf9f6] pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary/40" id="conversation-search">
                                    </div>
                                </div>
                                <div id="conversation-list" class="flex-1 overflow-y-auto scrollbar-thin">
                                    <?php if (empty($conversations)): ?>
                                        <div class="p-6 text-center text-sm text-gray-500">
                                            Bạn chưa có cuộc trò chuyện nào. Hãy bắt đầu trao đổi với đối tác đầu tiên!
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($conversations as $conversation): ?>
                                            <?php
                                            $is_active = $conversation['partner_id'] == $active_partner_id;
                                            $last_msg = htmlspecialchars($conversation['last_message'] ?? '', ENT_QUOTES, 'UTF-8');
                                            ?>
                                            <button
                                                class="w-full text-left border-b border-[#f5ece4] px-4 py-3 flex gap-3 items-center <?php echo $is_active ? 'bg-primary/5 border-l-4 border-primary' : 'hover:bg-[#fdf6ef]'; ?> conversation-item"
                                                data-user-id="<?php echo (int)$conversation['partner_id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($conversation['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                <div class="shrink-0">
                                                    <div class="size-12 rounded-full bg-cover bg-center" style="background-image:url('https://ui-avatars.com/api/?name=<?php echo urlencode($conversation['full_name']); ?>&background=f98006&color=fff');"></div>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex justify-between items-center">
                                                        <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($conversation['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                                        <span class="text-xs text-gray-500"><?php echo formatConversationTime($conversation['last_time'] ?? null); ?></span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 line-clamp-1"><?php echo $last_msg ?: 'Bắt đầu trò chuyện'; ?></p>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </aside>
                            <section class="flex flex-col bg-[#fdf9f6]" 
                                     data-current-user="<?php echo $current_user_id; ?>"
                                     data-send-endpoint="<?php echo $send_endpoint; ?>"
                                     data-history-endpoint="<?php echo $history_endpoint; ?>"
                                     data-active-partner="<?php echo $active_partner_id ?: 0; ?>">
                                <header class="flex items-center justify-between p-5 border-b border-[#f0eae6] bg-white">
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <div id="chat-avatar" class="size-12 rounded-full bg-cover bg-center" style="background-image:url('https://ui-avatars.com/api/?name=<?php echo urlencode($active_partner['full_name'] ?? ''); ?>&background=f98006&color=fff');"></div>
                                            <span class="size-3 rounded-full bg-green-500 border border-white absolute bottom-0 right-0"></span>
                                        </div>
                                        <div>
                                            <h3 id="chat-name" class="text-lg font-bold">
                                                <?php echo $active_partner ? htmlspecialchars($active_partner['full_name'], ENT_QUOTES, 'UTF-8') : 'Chọn cuộc trò chuyện'; ?>
                                            </h3>
                                            <p class="text-xs text-green-600 font-semibold">Trực tuyến</p>
                                        </div>
                                    </div>
                                    <button class="p-2 rounded-full hover:bg-gray-100">
                                        <span class="material-symbols-outlined text-gray-500">more_vert</span>
                                    </button>
                                </header>
                                <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-4 scrollbar-thin">
                                    <?php if ($active_partner && !empty($messages_history)): ?>
                                        <?php foreach ($messages_history as $message): ?>
                                            <?php $is_me = (int)$message['sender_id'] === $current_user_id; ?>
                                            <div class="flex <?php echo $is_me ? 'justify-end' : 'items-end gap-2'; ?>">
                                                <?php if (!$is_me): ?>
                                                    <div class="size-8 rounded-full bg-cover bg-center" style="background-image:url('https://ui-avatars.com/api/?name=<?php echo urlencode($active_partner['full_name']); ?>&background=f98006&color=fff');"></div>
                                                <?php endif; ?>
                                                <div class="<?php echo $is_me ? 'bg-white border border-[#eeded0]' : 'bg-primary text-white'; ?> rounded-2xl px-4 py-3 max-w-lg">
                                                    <p class="text-sm"><?php echo htmlspecialchars($message['message_content'], ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <div class="text-right text-[11px] mt-1 <?php echo $is_me ? 'text-gray-400' : 'text-white/70'; ?>">
                                                        <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="h-full flex flex-col items-center justify-center text-center text-gray-400">
                                            <span class="material-symbols-outlined text-5xl mb-3">sms</span>
                                            <p>Chọn một cuộc trò chuyện để bắt đầu nhắn tin.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <footer class="p-4 border-t border-[#f0eae6] bg-white">
                                    <form id="chat-form" class="flex items-center gap-3">
                                        <button type="button" class="size-11 rounded-full bg-[#f5ede6] text-gray-500 flex items-center justify-center hover:bg-[#f2e2d4]">
                                            <span class="material-symbols-outlined">add</span>
                                        </button>
                                        <input id="chat-input" type="text" placeholder="Nhập tin nhắn..." class="flex-1 h-12 rounded-full border border-[#ead7c6] px-5 focus:ring-2 focus:ring-primary/40" autocomplete="off" />
                                        <button type="submit" class="size-11 rounded-full bg-primary text-white flex items-center justify-center hover:opacity-90">
                                            <span class="material-symbols-outlined">send</span>
                                        </button>
                                    </form>
                                </footer>
                            </section>
                        </div>
                    </div>
                </main>
                <?php include __DIR__ . '/includes/footer.php'; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
const chatSection = document.querySelector('section[data-current-user]');
const CURRENT_USER = Number(chatSection?.dataset.currentUser || 0);
const SEND_ENDPOINT = chatSection?.dataset.sendEndpoint;
const HISTORY_ENDPOINT = chatSection?.dataset.historyEndpoint;
let activePartnerId = Number(chatSection?.dataset.activePartner || 0);

const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const chatForm = document.getElementById('chat-form');

function appendMessage({ message, sender_id, created_at }) {
    const mine = Number(sender_id) === CURRENT_USER;
    const wrapper = document.createElement('div');
    wrapper.className = 'flex ' + (mine ? 'justify-end' : 'items-end gap-2');
    wrapper.innerHTML = `
        ${mine ? '' : '<div class="size-8 rounded-full bg-cover bg-center" style="background-image:url(https://ui-avatars.com/api/?name=Chat&background=f98006&color=fff);"></div>'}
        <div class="${mine ? 'bg-white border border-[#eeded0]' : 'bg-primary text-white'} rounded-2xl px-4 py-3 max-w-lg">
            <p class="text-sm">${message}</p>
            <div class="text-right text-[11px] mt-1 ${mine ? 'text-gray-400' : 'text-white/70'}">${created_at || ''}</div>
        </div>`;
    chatMessages.appendChild(wrapper);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

async function loadConversation(partnerId, partnerName) {
    if (!partnerId || !HISTORY_ENDPOINT) return;
    const res = await fetch(`${HISTORY_ENDPOINT}?partner_id=${partnerId}`);
    if (!res.ok) return;
    const data = await res.json();
    chatMessages.innerHTML = '';
    if (data.messages?.length) {
        data.messages.forEach(msg => appendMessage({
            message: msg.message,
            sender_id: msg.sender_id,
            created_at: new Date(msg.created_at).toLocaleTimeString()
        }));
    } else {
        chatMessages.innerHTML = '<div class="h-full flex items-center justify-center text-sm text-gray-400">Hãy gửi tin nhắn đầu tiên cho '+ (partnerName || 'đối tác') +'!</div>';
    }
}

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!activePartnerId || !SEND_ENDPOINT) return;
    const message = chatInput.value.trim();
    if (!message) return;
    chatInput.value = '';
    appendMessage({ message, sender_id: CURRENT_USER, created_at: new Date().toLocaleTimeString() });
    await fetch(SEND_ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            sender_id: CURRENT_USER,
            receiver_id: activePartnerId,
            message
        })
    });
});

document.getElementById('conversation-list')?.addEventListener('click', (e) => {
    const item = e.target.closest('.conversation-item');
    if (!item) return;
    document.querySelectorAll('.conversation-item').forEach(btn => btn.classList.remove('bg-primary/5','border-l-4','border-primary'));
    item.classList.add('bg-primary/5','border-l-4','border-primary');
    activePartnerId = Number(item.dataset.userId);
    document.getElementById('chat-name').textContent = item.dataset.userName || 'Cuộc trò chuyện';
    loadConversation(activePartnerId, item.dataset.userName);
});

// Bật log để debug (tắt khi production)
Pusher.logToConsole = true;

const pusher = new Pusher('21b6af37da0f37a7ce0c', { 
    cluster: 'ap1', 
    forceTLS: true 
});

const channelName = 'chat_channel_' + CURRENT_USER;
console.log('Đang subscribe channel:', channelName);

const channel = pusher.subscribe(channelName);

channel.bind('pusher:subscription_succeeded', function() {
    console.log('✅ Đã kết nối Pusher thành công!');
});

channel.bind('pusher:subscription_error', function(status) {
    console.error('❌ Lỗi kết nối Pusher:', status);
});

channel.bind('new_message', function(data) {
    console.log('📨 Nhận tin nhắn mới:', data);
    const senderId = Number(data.sender_id);
    
    // Nếu đang xem đúng cuộc trò chuyện với người gửi, hiện tin nhắn ngay
    if (senderId === activePartnerId) {
        appendMessage({
            message: data.message,
            sender_id: data.sender_id,
            created_at: data.created_at ? new Date(data.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) : new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
        });
    } else {
        // Nếu không đang xem, cập nhật conversation list
        console.log('Tin nhắn từ người khác, cần reload conversation list');
        // Có thể reload trang hoặc update conversation list bằng AJAX
        // Tạm thời reload để đơn giản
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
});
</script>
</body>
</html>

