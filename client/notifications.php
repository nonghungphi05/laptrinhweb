<?php
/**
 * Trang thông báo của user
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$base_path = getBasePath();
$user_id = $_SESSION['user_id'];
$page_url = $base_path ? $base_path . '/client/notifications.php' : 'notifications.php';

function redirectToNotifications($url) {
    header('Location: ' . $url);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_all_read') {
        $stmt = $conn->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        setFlash('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
        redirectToNotifications($page_url);
    }

    if ($action === 'toggle_read') {
        $notification_id = (int) ($_POST['notification_id'] ?? 0);
        $target_state = (int) ($_POST['target_state'] ?? 1);

        $stmt = $conn->prepare("UPDATE user_notifications SET is_read = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $target_state, $notification_id, $user_id);
        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();

        if ($updated > 0) {
            setFlash('success', $target_state ? 'Đã đánh dấu là đã đọc.' : 'Đã chuyển sang chưa đọc.');
        } else {
            setFlash('error', 'Không thể cập nhật thông báo.');
        }

        redirectToNotifications($page_url);
    }

    if ($action === 'delete') {
        $notification_id = (int) ($_POST['notification_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM user_notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted > 0) {
            setFlash('success', 'Đã xóa thông báo.');
        } else {
            setFlash('error', 'Không thể xóa thông báo.');
        }

        redirectToNotifications($page_url);
    }
}

$notifications = [];
$stmt = $conn->prepare("SELECT id, title, message, type, is_read, created_at FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

$unread_count = array_reduce($notifications, function ($carry, $item) {
    return $carry + (int) ($item['is_read'] ? 0 : 1);
}, 0);

$flash = getFlash();

function notificationTypeBadge($type)
{
    switch ($type) {
        case 'success':
            return ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200', 'success'];
        case 'warning':
            return ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200', 'warning'];
        case 'danger':
            return ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200', 'danger'];
        case 'promo':
            return ['bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200', 'promo'];
        default:
            return ['bg-gray-100 text-gray-800 dark:bg-gray-800/60 dark:text-gray-200', 'info'];
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Thông báo - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
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
        <?php include '../includes/header.php'; ?>

        <div class="layout-container flex h-full grow flex-col">
            <div class="px-4 sm:px-6 lg:px-8 py-8">
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <?php
                        $active_page = 'notifications';
                        include __DIR__ . '/account-sidebar.php';
                    ?>

                    <main class="lg:col-span-9">
                        <div class="bg-white dark:bg-background-dark/50 p-6 sm:p-8 rounded-xl shadow-lg">
                            <?php if ($flash): ?>
                                <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?php echo $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-200' : 'border-red-200 bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-200'; ?>">
                                    <?php echo htmlspecialchars($flash['message']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <h1 class="text-2xl font-bold text-[#181411] dark:text-white">Thông báo</h1>
                                        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary dark:bg-primary/20">
                                            <?php echo $unread_count; ?> chưa đọc
                                        </span>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 mt-1">Cập nhật các thông tin quan trọng, ưu đãi và trạng thái đơn hàng.</p>
                                </div>
                                <form method="POST" class="w-full sm:w-auto">
                                    <input type="hidden" name="action" value="mark_all_read">
                                    <button class="flex items-center justify-center gap-2 px-5 py-2.5 text-primary font-bold rounded-lg border border-primary/40 hover:bg-primary/10 dark:hover:bg-gray-700/50 transition-colors w-full text-sm <?php echo $unread_count === 0 ? 'opacity-60 cursor-not-allowed' : ''; ?>" <?php echo $unread_count === 0 ? 'disabled' : ''; ?>>
                                        <span>Đánh dấu tất cả đã đọc</span>
                                    </button>
                                </form>
                            </div>

                            <?php if (empty($notifications)): ?>
                                <div class="border border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center text-gray-500 dark:text-gray-400">
                                    Bạn chưa có thông báo nào.
                                </div>
                            <?php else: ?>
                                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php foreach ($notifications as $notification): ?>
                                        <?php [$badgeClasses, $typeLabel] = notificationTypeBadge($notification['type']); ?>
                                        <div class="py-5 flex flex-col gap-4 <?php echo $notification['is_read'] ? 'opacity-70' : ''; ?>">
                                            <div class="flex items-start gap-4">
                                                <div class="w-2.5 h-2.5 rounded-full <?php echo $notification['is_read'] ? 'bg-transparent border border-gray-300 dark:border-gray-600' : 'bg-primary'; ?> mt-1.5 shrink-0"></div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                                        <div class="flex items-center gap-3">
                                                            <p class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($notification['title']); ?></p>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?php echo $badgeClasses; ?>">
                                                                <?php echo strtoupper($typeLabel); ?>
                                                            </span>
                                                        </div>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">
                                                            <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($notification['message'])): ?>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3 pl-6 sm:pl-10">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="toggle_read">
                                                    <input type="hidden" name="notification_id" value="<?php echo (int) $notification['id']; ?>">
                                                    <input type="hidden" name="target_state" value="<?php echo $notification['is_read'] ? 0 : 1; ?>">
                                                    <button type="submit" class="text-xs font-semibold inline-flex items-center gap-1 px-3 py-1.5 rounded-full border <?php echo $notification['is_read'] ? 'text-primary border-primary hover:bg-primary/10' : 'text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700/50'; ?>">
                                                        <span class="material-symbols-outlined text-sm"><?php echo $notification['is_read'] ? 'visibility' : 'check'; ?></span>
                                                        <?php echo $notification['is_read'] ? 'Đánh dấu chưa đọc' : 'Đánh dấu đã đọc'; ?>
                                                    </button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa thông báo này?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="notification_id" value="<?php echo (int) $notification['id']; ?>">
                                                    <button type="submit" class="text-xs font-semibold inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-red-600 border border-red-200 hover:bg-red-50 dark:text-red-300 dark:border-red-800 dark:hover:bg-red-900/30">
                                                        <span class="material-symbols-outlined text-sm">delete</span>
                                                        Xóa
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

