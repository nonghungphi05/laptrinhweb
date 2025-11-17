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
                <h3 class="text-lg font-bold text-[#181411] dark:text-white mb-4">CarRental</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Nền tảng thuê xe tự lái hàng đầu Việt Nam. Kết nối bạn với hàng ngàn chủ xe uy tín.
                </p>
            </div>
            <div>
                <h3 class="text-lg font-bold text-[#181411] dark:text-white mb-4">Liên kết nhanh</h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Trang chủ</a></li>
                    <li><a href="<?php echo $base_path ? $base_path . '/index.php#featured-cars' : 'index.php#featured-cars'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Danh sách xe</a></li>
                    <li><a href="<?php echo $base_path ? $base_path . '/host/dashboard.php' : 'host/dashboard.php'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Quản lý xe</a></li>
                    <li><a href="<?php echo $base_path ? $base_path . '/index.php#about' : 'index.php#about'; ?>" class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary transition-colors">Về chúng tôi</a></li>
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
