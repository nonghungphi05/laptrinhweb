<?php
/**
 * Trang đăng ký tài khoản
 */
require_once '../config/database.php';
require_once '../config/session.php';

$error = '';
$success = '';

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = 'user'; // Mặc định là user, có thể vừa thuê xe vừa đăng bài
    
    // Validate
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        // Kiểm tra username/email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Tên đăng nhập hoặc email đã tồn tại';
        } else {
            // Hash password và insert vào DB
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if (!$hashed_password) {
                $error = 'Lỗi hash password. Vui lòng thử lại.';
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $error = 'Lỗi chuẩn bị query: ' . $conn->error;
                } else {
                    $stmt->bind_param("ssssss", $username, $email, $hashed_password, $role, $full_name, $phone);
                    
                    if ($stmt->execute()) {
                        $success = 'Đăng ký thành công! Bạn có thể vừa thuê xe vừa đăng bài cho thuê xe. Vui lòng đăng nhập.';
                        // Clear form
                        $_POST = [];
                    } else {
                        $error = 'Lỗi thêm user: ' . $stmt->error . ' (MySQL Error: ' . $conn->error . ')';
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Diễn đàn Thuê Xe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Đăng ký tài khoản</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Tên đăng nhập *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Họ và tên *</label>
                    <input type="text" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Đăng ký</button>
            </form>
            
            <p class="auth-link">Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
        </div>
    </div>
</body>
</html>


