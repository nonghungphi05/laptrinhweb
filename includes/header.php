<?php
/**
 * Header chung cho tất cả các trang - Design giống Mioto
 */
if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/session.php';
}

// Lấy base path - fix cho header
if (!function_exists('getBasePathForHeader')) {
    function getBasePathForHeader() {
        $script_name = $_SERVER['SCRIPT_NAME'];
        $script_dir = dirname($script_name);
        $parts = explode('/', trim($script_dir, '/'));
        
        // Nếu đang ở root (index.php), lấy từ REQUEST_URI
        if (basename($script_name) === 'index.php' && empty($parts[0])) {
            $uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
            if (!empty($uri_parts[0])) {
                return '/' . $uri_parts[0];
            }
            return '';
        }
        
        // Tìm các thư mục con
        $subdirs = ['auth', 'forum', 'admin', 'client', 'host', 'api', 'config', 'includes'];
        foreach ($subdirs as $subdir) {
            $pos = array_search($subdir, $parts);
            if ($pos !== false && $pos > 0) {
                $base_parts = array_slice($parts, 0, $pos);
                return '/' . implode('/', $base_parts);
            }
        }
        
        // Nếu không tìm thấy
        if (!empty($parts[0])) {
            return '/' . $parts[0];
        }
        return '';
    }
}

$base_path = getBasePathForHeader();
?>
<header class="main-header">
    <div class="header-container">
        <div class="header-left">
            <a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="logo">
                <span class="logo-icon">🚗</span>
                <span class="logo-text">THUÊ XE</span>
            </a>
        </div>
        
        <nav class="header-nav">
            <a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" class="nav-link">Về chúng tôi</a>
            <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : 'forum/create-post.php'; ?>" class="nav-link">Trở thành chủ xe</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'client/my-bookings.php'; ?>" class="nav-link">Chuyến của tôi</a>
            <?php endif; ?>
        </nav>
        
        <div class="header-right">
            <?php if (isLoggedIn()): ?>
                <div class="header-icons">
                    <a href="#" class="icon-link" title="Thông báo">
                        <span class="icon">🔔</span>
                    </a>
                    <a href="#" class="icon-link" title="Tin nhắn">
                        <span class="icon">💬</span>
                    </a>
                </div>
                <div class="user-menu">
                    <div class="user-info">
                        <a href="<?php echo $base_path ? $base_path . '/client/profile.php' : 'client/profile.php'; ?>" class="user-name-link">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?></span>
                        </a>
                        <span class="dropdown-icon">▼</span>
                    </div>
                    <div class="user-dropdown">
                        <a href="<?php echo $base_path ? $base_path . '/client/profile.php' : 'client/profile.php'; ?>">Tài khoản của tôi</a>
                        <a href="<?php echo $base_path ? $base_path . '/forum/my-posts.php' : 'forum/my-posts.php'; ?>">Bài viết của tôi</a>
                        <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'client/my-bookings.php'; ?>">Đơn đặt của tôi</a>
                        <?php if (hasRole('admin')): ?>
                            <a href="<?php echo $base_path ? $base_path . '/admin/dashboard.php' : 'admin/dashboard.php'; ?>">Quản trị</a>
                        <?php endif; ?>
                        <a href="<?php echo $base_path ? $base_path . '/auth/logout.php' : 'auth/logout.php'; ?>">Đăng xuất</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $base_path ? $base_path . '/auth/login.php' : 'auth/login.php'; ?>" class="btn-login">Đăng nhập</a>
                <a href="<?php echo $base_path ? $base_path . '/auth/register.php' : 'auth/register.php'; ?>" class="btn-register">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>
