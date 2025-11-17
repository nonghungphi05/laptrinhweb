<?php
/**
 * Footer chung cho tất cả các trang - Tailwind CSS Design
 */
if (!function_exists('getBasePath')) {
    require_once __DIR__ . '/../config/session.php';
}
$base_path = getBasePath();
?>
<footer class="bg-white dark:bg-background-dark/50 border-t border-gray-200 dark:border-gray-700 py-8 px-4 md:px-10">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
                    </svg>
                    <h3 class="text-lg font-bold text-primary">CarRental</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Nền tảng thuê xe tự lái hàng đầu Việt Nam. Kết nối bạn với hàng ngàn chủ xe uy tín.
                </p>
            </div>
            <div>
                <h3 class="text-lg font-bold text-[#181411] dark:text-white mb-4">Liên kết nhanh</h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Trang chủ</a></li>
                    <li><a href="<?php echo $base_path ? $base_path . '/cars/index.php' : 'cars/index.php'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Danh sách xe</a></li>
                    <li><a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'host/dashboard.php'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Quản lý xe</a></li>
                    <li><a href="<?php echo $base_path ? $base_path . '/about.php' : 'about.php'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Về chúng tôi</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-bold text-[#181411] dark:text-white mb-4">Liên hệ</h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>Email: contact@carrental.com</li>
                    <li>Hotline: 1900-xxxx</li>
                    <li>Địa chỉ: TP. Hồ Chí Minh, Việt Nam</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 pt-8 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                &copy; 2025 CarRental. Tất cả quyền được bảo lưu.
            </p>
        </div>
    </div>
</footer>
