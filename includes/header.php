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
$messages_link = isLoggedIn()
    ? ($base_path ? $base_path . '/messages.php' : 'messages.php')
    : ($base_path ? $base_path . '/auth/login.php' : 'auth/login.php');
?>
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-b-[#f5f2f0] dark:border-b-background-dark/20 px-4 md:px-10 py-3">
    <div class="flex items-center gap-2 text-primary">
        <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
        </svg>
        <a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="text-primary text-xl font-bold leading-tight tracking-[-0.015em] hover:opacity-80 transition-opacity">
            CarRental
        </a>
    </div>
    <nav class="hidden lg:flex flex-1 justify-center gap-8">
        <a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Trang chủ</a>
        <a href="<?php echo $base_path ? $base_path . '/about.php' : 'about.php'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Về chúng tôi</a>
        <a href="<?php echo $host_cta_link; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Trở thành chủ xe</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'client/my-bookings.php'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Chuyến của tôi</a>
        <?php endif; ?>
        <a href="<?php echo $base_path ? $base_path . '/cars/index.php' : 'cars/index.php'; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Danh sách xe</a>
        <a href="<?php echo $messages_link; ?>" class="text-[#181411] dark:text-gray-300 text-sm font-medium leading-normal hover:text-primary dark:hover:text-primary transition-colors">Hộp thư</a>
    </nav>
    <div class="flex items-center gap-2">
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo $messages_link; ?>" class="hidden md:flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-transparent text-[#181411] dark:text-white gap-2 text-sm font-bold leading-normal tracking-[0.015em] min-w-0 px-2.5 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined">chat</span>
            </a>
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
                    <a href="<?php echo $messages_link; ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Hộp thư</a>
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
