<?php
/**
 * Header chung cho tất cả các trang
 */
if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/session.php';
}
?>
<header>
    <div class="container">
        <h1>🚗 Thuê Xe Tự Lái</h1>
        <nav>
            <ul>
                <li><a href="/webthuexe/index.php">Trang chủ</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole('admin')): ?>
                        <li><a href="/webthuexe/admin/dashboard.php">Quản trị</a></li>
                    <?php elseif (hasRole('host')): ?>
                        <li><a href="/webthuexe/host/dashboard.php">Quản lý xe</a></li>
                    <?php else: ?>
                        <li><a href="/webthuexe/client/my-bookings.php">Đơn đặt của tôi</a></li>
                    <?php endif; ?>
                    <li><a href="/webthuexe/auth/logout.php">Đăng xuất (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="/webthuexe/auth/login.php">Đăng nhập</a></li>
                    <li><a href="/webthuexe/auth/register.php">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>


