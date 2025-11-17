<?php
/**
 * Trang quản lý địa chỉ của user
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$base_path = getBasePath();
$page_url = $base_path ? $base_path . '/client/addresses.php' : 'addresses.php';

function redirectToAddresses($url) {
    header('Location: ' . $url);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $label = sanitize($_POST['label'] ?? '');
        $recipient_name = sanitize($_POST['recipient_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address_line = sanitize($_POST['address_line'] ?? '');
        $district = sanitize($_POST['district'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $province = sanitize($_POST['province'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if (empty($label) || empty($recipient_name) || empty($phone) || empty($address_line)) {
            setFlash('error', 'Vui lòng nhập đầy đủ thông tin địa chỉ.');
            redirectToAddresses($page_url);
        }

        // Nếu user chưa có địa chỉ nào thì tự động gán mặc định
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM user_addresses WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (($count_result['cnt'] ?? 0) == 0) {
            $is_default = 1;
        }

        if ($is_default) {
            $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }

        $insert = $conn->prepare("INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_line, district, city, province, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("isssssssi", $user_id, $label, $recipient_name, $phone, $address_line, $district, $city, $province, $is_default);

        if ($insert->execute()) {
            setFlash('success', 'Đã thêm địa chỉ mới thành công.');
        } else {
            setFlash('error', 'Không thể thêm địa chỉ. Vui lòng thử lại.');
        }
        $insert->close();

        redirectToAddresses($page_url);
    }

    if ($action === 'set_default') {
        $address_id = (int) ($_POST['address_id'] ?? 0);
        $stmt = $conn->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $conn->begin_transaction();
            try {
                $reset = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                $reset->bind_param("i", $user_id);
                $reset->execute();
                $reset->close();

                $set = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                $set->bind_param("ii", $address_id, $user_id);
                $set->execute();
                $set->close();

                $conn->commit();
                setFlash('success', 'Đã cập nhật địa chỉ mặc định.');
            } catch (Exception $e) {
                $conn->rollback();
                setFlash('error', 'Không thể cập nhật địa chỉ mặc định.');
            }
        } else {
            setFlash('error', 'Địa chỉ không tồn tại.');
        }

        redirectToAddresses($page_url);
    }

    if ($action === 'delete') {
        $address_id = (int) ($_POST['address_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $deleted_rows = $stmt->affected_rows;
        $stmt->close();

        if ($deleted_rows > 0) {
            // Đảm bảo luôn có 1 địa chỉ mặc định nếu còn địa chỉ
            $stmt = $conn->prepare("SELECT id, is_default FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $last = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($last && (int) $last['is_default'] === 0) {
                $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?");
                $stmt->bind_param("i", $last['id']);
                $stmt->execute();
                $stmt->close();
            }

            setFlash('success', 'Đã xóa địa chỉ thành công.');
        } else {
            setFlash('error', 'Không thể xóa địa chỉ.');
        }

        redirectToAddresses($page_url);
    }
}

// Lấy danh sách địa chỉ
$addresses = [];
$stmt = $conn->prepare("SELECT id, label, recipient_name, phone, address_line, district, city, province, is_default FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}
$stmt->close();

$flash = getFlash();
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản lý địa chỉ - CarRental</title>
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
                        $active_page = 'addresses';
                        include __DIR__ . '/account-sidebar.php';
                    ?>

                    <main class="lg:col-span-9">
                        <div class="bg-white dark:bg-background-dark/50 p-6 sm:p-8 rounded-xl shadow-lg">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                                <div>
                                    <h1 class="text-2xl font-bold text-[#181411] dark:text-white">Quản lý địa chỉ</h1>
                                    <p class="text-gray-600 dark:text-gray-300 mt-1">Lưu các địa chỉ thường dùng để nhận và trả xe, giúp quá trình đặt xe nhanh hơn.</p>
                                </div>
                                <button class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors w-full sm:w-auto">
                                    <span class="material-symbols-outlined">add</span>
                                    <span>Thêm địa chỉ mới</span>
                                </button>
                            </div>

                            <?php if ($flash): ?>
                                <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?php echo $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-200' : 'border-red-200 bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-200'; ?>">
                                    <?php echo htmlspecialchars($flash['message']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="space-y-6">
                                <?php if (empty($addresses)): ?>
                                    <div class="p-5 border border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-center text-sm text-gray-500 dark:text-gray-400">
                                        Bạn chưa có địa chỉ nào. Hãy thêm địa chỉ mới để tiện đặt xe nhanh hơn.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($addresses as $address): ?>
                                        <div class="p-5 border border-gray-200 dark:border-gray-700 rounded-xl flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                            <div class="flex items-start gap-4">
                                                <span class="material-symbols-outlined text-primary mt-1"><?php echo $address['is_default'] ? 'home' : 'location_on'; ?></span>
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                                        <h3 class="text-base font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($address['label']); ?></h3>
                                                        <?php if ($address['is_default']): ?>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary dark:bg-primary/20">Mặc định</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium"><?php echo htmlspecialchars($address['recipient_name']); ?> • <?php echo htmlspecialchars($address['phone']); ?></p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        <?php
                                                            $parts = array_filter([
                                                                $address['address_line'],
                                                                $address['district'],
                                                                $address['city'],
                                                                $address['province']
                                                            ]);
                                                            echo htmlspecialchars(implode(', ', $parts));
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                                                <?php if (!$address['is_default']): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="set_default">
                                                        <input type="hidden" name="address_id" value="<?php echo (int) $address['id']; ?>">
                                                        <button class="px-3 py-1.5 text-xs font-semibold text-primary border border-primary rounded-full hover:bg-primary/10 transition-colors" type="submit">
                                                            Đặt mặc định
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="address_id" value="<?php echo (int) $address['id']; ?>">
                                                    <button class="p-2 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors" type="submit">
                                                        <span class="material-symbols-outlined">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="mt-10 pt-8 border-t border-gray-200 dark:border-gray-700">
                                <h2 class="text-xl font-bold text-[#181411] dark:text-white mb-4">Thêm địa chỉ mới</h2>
                                <form method="POST" class="grid grid-cols-1 gap-5">
                                    <input type="hidden" name="action" value="create">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Loại địa chỉ *</label>
                                            <input type="text" name="label" required placeholder="Nhà riêng, Công ty..."
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Họ và tên người nhận *</label>
                                            <input type="text" name="recipient_name" required placeholder="Tên người nhận"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Số điện thoại *</label>
                                            <input type="text" name="phone" required placeholder="090..."
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quận / Huyện</label>
                                            <input type="text" name="district" placeholder="Quận 1, Tân Bình..."
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Thành phố</label>
                                            <input type="text" name="city" placeholder="TP. Hồ Chí Minh"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tỉnh / Thành</label>
                                            <input type="text" name="province" placeholder="TP. Hồ Chí Minh"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Địa chỉ chi tiết *</label>
                                        <textarea name="address_line" rows="3" required placeholder="Số nhà, tên đường..."
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white"></textarea>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="is_default" id="is_default" class="rounded border-gray-300 text-primary focus:ring-primary">
                                        <label for="is_default" class="text-sm text-gray-700 dark:text-gray-300">Đặt làm địa chỉ mặc định</label>
                                    </div>
                                    <div>
                                        <button type="submit" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors w-full sm:w-auto">
                                            <span class="material-symbols-outlined">add</span>
                                            <span>Thêm địa chỉ</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

