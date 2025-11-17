<?php
/**
 * Header chung cho tất cả các trang - Tailwind CSS Design
 */
if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
}
if (!function_exists('getBasePath') || !function_exists('isLoggedIn') || !function_exists('hasRole')) {
    require_once __DIR__ . '/../config/session.php';
}

// Lấy base path
$base_path = getBasePath();
$host_cta_link = isLoggedIn()
    ? ($base_path ? $base_path . '/host/dashboard.php' : 'host/dashboard.php')
    : ($base_path ? $base_path . '/auth/login.php' : 'auth/login.php');

$current_request = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$current_request = $current_request === '' ? '/' : $current_request;
$script_path = $_SERVER['SCRIPT_NAME'] ?? $current_request;
$home_paths = [];
$normalized_base = rtrim($base_path ?: '', '/');

if ($normalized_base === '') {
    $home_paths[] = '/';
    $home_paths[] = '/index.php';
} else {
    $home_paths[] = $normalized_base;
    $home_paths[] = $normalized_base . '/index.php';
}

$home_paths = array_unique($home_paths);
$is_home_page = in_array(rtrim($script_path, '/'), $home_paths, true);
?>
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-b-[#f5f2f0] dark:border-b-background-dark/20 px-4 md:px-10 py-3">
    <div class="flex items-center gap-3 text-primary">
        <?php if (!$is_home_page): ?>
            <button type="button"
                class="flex items-center justify-center size-9 rounded-full border border-primary/30 text-primary hover:bg-primary/10 transition-colors"
                onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href='<?php echo $base_path ? $base_path . '/index.php' : '/'; ?>'; }"
                aria-label="Quay lại trang trước">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
            </button>
        <?php endif; ?>
        <div class="size-6">
            <svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_6_319)">
                    <path d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z" fill="currentColor"></path>
                </g>
                <defs>
                    <clippath id="clip0_6_319">
                        <rect fill="white" height="48" width="48"></rect>
                    </clippath>
                </defs>
            </svg>
        </div>
        <a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="text-primary text-xl font-bold leading-tight tracking-[-0.015em] hover:opacity-80 transition-opacity">
            CarRental
        </a>
    </div>
    <nav class="hidden lg:flex flex-1 justify-center gap-8">
        <a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Trang chủ</a>
        <a href="<?php echo $base_path ? $base_path . '/index.php#about' : 'index.php#about'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Về chúng tôi</a>
        <a href="<?php echo $host_cta_link; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Trở thành chủ xe</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'client/my-bookings.php'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Chuyến của tôi</a>
        <?php endif; ?>
        <a href="<?php echo $base_path ? $base_path . '/cars/index.php' : 'cars/index.php'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Danh sách xe</a>
    </nav>
    <div class="flex items-center gap-2">
        <?php if (isLoggedIn()): ?>
            <button class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-transparent text-[#181411] dark:text-white gap-2 text-sm font-bold leading-normal tracking-[0.015em] min-w-0 px-2.5 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">card_giftcard</span>
            </button>
            <div class="user-menu relative group">
                <div class="flex items-center gap-2 cursor-pointer">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 bg-gray-300" 
                         style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?: $_SESSION['username']); ?>&background=f98006&color=fff");'></div>
                </div>
                <div class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                    <a href="<?php echo $base_path ? $base_path . '/client/profile.php' : 'client/profile.php'; ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Tài khoản của tôi</a>
                    <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'client/my-bookings.php'; ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Đơn đặt của tôi</a>
                    <a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'host/dashboard.php'; ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Quản lý xe</a>
                    <?php if (hasRole('admin')): ?>
                        <a href="<?php echo $base_path ? $base_path . '/admin/dashboard.php' : 'admin/dashboard.php'; ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Quản trị</a>
                    <?php endif; ?>
                    <a href="<?php echo $base_path ? $base_path . '/auth/logout.php' : 'auth/logout.php'; ?>" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border-t border-gray-200 dark:border-gray-700">Đăng xuất</a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo $base_path ? $base_path . '/auth/login.php' : 'auth/login.php'; ?>" class="hidden md:flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-[#f5f2f0] dark:bg-background-dark/50 text-[#181411] dark:text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <span class="truncate">Đăng nhập</span>
            </a>
        <?php endif; ?>
        <button class="flex lg:hidden max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-transparent text-[#181411] dark:text-white gap-2 text-sm font-bold leading-normal tracking-[0.015em] min-w-0 px-2.5 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
            <span class="material-symbols-outlined">menu</span>
        </button>
    </div>
</header>
