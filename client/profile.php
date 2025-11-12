<?php
/**
 * Trang cá nhân của user - Mioto Style
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$base_path = getBasePath();

// Lấy thông tin user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: ' . $base_path . '/auth/login.php');
    exit();
}

// Đếm số chuyến đi
$stmt = $conn->prepare("SELECT COUNT(*) as trip_count FROM bookings WHERE customer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trip_result = $stmt->get_result();
$trip_data = $trip_result->fetch_assoc();
$trip_count = $trip_data['trip_count'] ?? 0;

// Format ngày tham gia
$joined_date = date('d/m/Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi</title>
    <link rel="stylesheet" href="<?php echo $base_path ? $base_path . '/assets/css/style.css' : '../assets/css/style.css'; ?>">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="profile-main">
        <div class="profile-container">
            <!-- Sidebar Menu (Sticky) -->
            <aside class="profile-sidebar">
                <div class="sidebar-greeting">
                    <h2>Xin chào bạn!</h2>
                </div>
                <nav class="sidebar-menu">
                    <a href="<?php echo $base_path ? $base_path . '/client/profile.php' : 'profile.php'; ?>" class="menu-item active">
                        <span class="menu-icon">👤</span>
                        <span class="menu-text">Tài khoản của tôi</span>
                    </a>
                    <a href="<?php echo $base_path ? $base_path . '/forum/my-posts.php' : '../forum/my-posts.php'; ?>" class="menu-item">
                        <span class="menu-icon">🚗</span>
                        <span class="menu-text">Quản lý cho thuê</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="menu-icon">❤️</span>
                        <span class="menu-text">Xe yêu thích</span>
                    </a>
                    <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'my-bookings.php'; ?>" class="menu-item">
                        <span class="menu-icon">🧳</span>
                        <span class="menu-text">Chuyến của tôi</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="menu-icon">📋</span>
                        <span class="menu-text">Đơn hàng Thuê xe dài hạn</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="menu-icon">🎁</span>
                        <span class="menu-text">Quà tặng</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="menu-icon">📍</span>
                        <span class="menu-text">Địa chỉ của tôi</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="menu-icon">🔒</span>
                        <span class="menu-text">Đổi mật khẩu</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="menu-icon">🗑️</span>
                        <span class="menu-text">Yêu cầu xoá tài khoản</span>
                    </a>
                    <a href="<?php echo $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php'; ?>" class="menu-item logout">
                        <span class="menu-icon">🚪</span>
                        <span class="menu-text">Đăng xuất</span>
                    </a>
                </nav>
            </aside>
            
            <!-- Main Content -->
            <div class="profile-content">
                <!-- Thông tin tài khoản -->
                <div class="profile-card">
                    <div class="card-header">
                        <h3>Thông tin tài khoản</h3>
                        <button class="btn-edit" id="btn-edit-account">
                            <span class="edit-icon">✏️</span>
                            Chỉnh sửa
                        </button>
                    </div>
                    
                    <div class="account-info">
                        <div class="account-left">
                            <div class="avatar-wrapper">
                                <div class="avatar">👤</div>
                            </div>
                            <div class="user-basic-info">
                                <h4><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h4>
                                <p class="joined-date">Tham gia: <?php echo $joined_date; ?></p>
                                <div class="points-badge">
                                    <span class="points-icon">⭐</span>
                                    <span class="points-text">0 điểm</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="account-right">
                            <div class="trip-summary">
                                <div class="trip-box">
                                    <span class="trip-icon">🧳</span>
                                    <span class="trip-count"><?php echo $trip_count; ?> chuyến</span>
                                </div>
                            </div>
                            
                            <div class="account-details">
                                <div class="detail-item">
                                    <label>Ngày sinh</label>
                                    <span>--/--/----</span>
                                </div>
                                <div class="detail-item">
                                    <label>Giới tính</label>
                                    <span>Nam</span>
                                </div>
                                <div class="detail-item">
                                    <label>Số điện thoại</label>
                                    <div class="detail-value-with-action">
                                        <span><?php echo htmlspecialchars($user['phone'] ?: 'Chưa cập nhật'); ?></span>
                                        <?php if (!$user['phone']): ?>
                                            <button class="btn-link-edit">
                                                <span class="edit-icon-small">✏️</span>
                                            </button>
                                            <span class="status-badge unverified">Chưa xác thực</span>
                                        <?php else: ?>
                                            <button class="btn-link-edit">
                                                <span class="edit-icon-small">✏️</span>
                                            </button>
                                            <span class="status-badge verified">Đã xác thực</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <label>Email</label>
                                    <div class="detail-value-with-action">
                                        <span><?php echo htmlspecialchars($user['email'] ?: 'Chưa cập nhật'); ?></span>
                                        <button class="btn-link-edit">
                                            <span class="edit-icon-small">✏️</span>
                                        </button>
                                        <?php if (!$user['email']): ?>
                                            <span class="status-badge unverified">Chưa xác thực</span>
                                        <?php else: ?>
                                            <span class="status-badge verified">Đã xác thực</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <label>Facebook</label>
                                    <div class="detail-value-with-action">
                                        <span>Chưa liên kết</span>
                                        <button class="btn-link-add">
                                            <span class="link-icon">🔗</span>
                                            Thêm liên kết
                                        </button>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <label>Google</label>
                                    <div class="detail-value-with-action">
                                        <span>Chưa liên kết</span>
                                        <button class="btn-link-add">
                                            <span class="link-icon">🔗</span>
                                            Thêm liên kết
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Giấy phép lái xe -->
                <div class="profile-card">
                    <div class="card-header">
                        <h3>Giấy phép lái xe</h3>
                        <span class="status-badge unverified">Chưa xác thực</span>
                        <button class="btn-edit" id="btn-edit-license">
                            <span class="edit-icon">✏️</span>
                            Chỉnh sửa
                        </button>
                    </div>
                    
                    <div class="license-warning">
                        <p><strong>Lưu ý:</strong> để tránh phát sinh vấn đề trong quá trình thuê xe, người đặt xe trên Mioto (đã xác thực GPLX) ĐỒNG THỜI phải là người nhận xe.</p>
                    </div>
                    
                    <div class="license-info">
                        <p>Bạn có thể sử dụng GPLX thẻ cứng hoặc GPLX điện tử trên VNeID</p>
                    </div>
                    
                    <div class="license-form">
                        <div class="license-upload">
                            <div class="upload-area">
                                <span class="upload-icon">☁️</span>
                                <p>Hình ảnh</p>
                                <button class="btn-upload">Tải lên</button>
                            </div>
                        </div>
                        
                        <div class="license-fields">
                            <div class="form-group">
                                <label for="license-number">Số GPLX</label>
                                <input type="text" id="license-number" placeholder="Nhập số GPLX đã cấp" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="license-name">Họ và tên</label>
                                <input type="text" id="license-name" placeholder="Nhập đầy đủ họ tên" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="license-birthday">Ngày sinh</label>
                                <input type="text" id="license-birthday" placeholder="01/01/1970" class="form-input">
                            </div>
                        </div>
                    </div>
                    
                    <div class="license-help">
                        <a href="#" class="help-link">
                            <span class="help-icon">❓</span>
                            Vì sao tôi phải xác thực GPLX?
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

