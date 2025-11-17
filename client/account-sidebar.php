<?php
/**
 * Sidebar điều hướng cho trang tài khoản
 * Yêu cầu các biến:
 * - $base_path: base path của ứng dụng (có thể rỗng)
 * - $active_page: key của trang đang active
 */

if (!isset($base_path) && function_exists('getBasePath')) {
    $base_path = getBasePath();
}

$active_page = $active_page ?? '';

$nav_items = [
    [
        'key' => 'profile',
        'label' => 'Thông tin cá nhân',
        'icon' => 'person',
        'href' => $base_path ? $base_path . '/client/profile.php' : 'profile.php',
    ],
    [
        'key' => 'rentals',
        'label' => 'Quản lý xe cho thuê',
        'icon' => 'directions_car',
        'href' => $base_path ? $base_path . '/host/dashboard.php' : '../host/dashboard.php',
    ],
    [
        'key' => 'bookings',
        'label' => 'Lịch sử đặt xe',
        'icon' => 'history',
        'href' => $base_path ? $base_path . '/client/my-bookings.php' : 'my-bookings.php',
    ],
    [
        'key' => 'payments',
        'label' => 'Lịch sử thanh toán',
        'icon' => 'receipt_long',
        'href' => $base_path ? $base_path . '/client/payment-history.php' : 'payment-history.php',
    ],
    [
        'key' => 'addresses',
        'label' => 'Quản lý địa chỉ',
        'icon' => 'location_on',
        'href' => $base_path ? $base_path . '/client/addresses.php' : 'addresses.php',
    ],
    [
        'key' => 'notifications',
        'label' => 'Thông báo',
        'icon' => 'notifications',
        'href' => $base_path ? $base_path . '/client/notifications.php' : 'notifications.php',
    ],
    [
        'key' => 'logout',
        'label' => 'Đăng xuất',
        'icon' => 'logout',
        'href' => $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php',
        'type' => 'danger',
    ],
];
?>
<aside class="lg:col-span-3">
    <div class="bg-white dark:bg-background-dark/50 p-6 rounded-xl shadow-lg sticky top-28">
        <h2 class="text-lg font-bold text-[#181411] dark:text-white mb-6">Quản lý tài khoản</h2>
        <nav class="space-y-2">
            <?php foreach ($nav_items as $item): ?>
                <?php
                    $is_active = $active_page === $item['key'];
                    $is_danger = ($item['type'] ?? '') === 'danger';
                    $base_classes = 'flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors';
                    if ($is_danger) {
                        $classes = $base_classes . ' text-red-600 dark:text-red-400 hover:bg-red-500/10';
                    } elseif ($is_active) {
                        $classes = $base_classes . ' bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary font-bold';
                    } else {
                        $classes = $base_classes . ' text-gray-700 dark:text-gray-300 hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/20 dark:hover:text-primary';
                    }
                ?>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $classes; ?>">
                    <span class="material-symbols-outlined"><?php echo $item['icon']; ?></span>
                    <span class="font-medium"><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>

