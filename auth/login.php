<?php
/**
 * Trang đăng nhập
 */
require_once '../config/database.php';
require_once '../config/session.php';

$error = '';

// Nếu đã đăng nhập, chuyển về trang chủ
if (isLoggedIn()) {
    header('Location: /webthuexe/index.php');
    exit();
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu';
    } else {
        // Tìm user trong database
        $stmt = $conn->prepare("SELECT id, username, email, password, role, full_name FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra password
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công - Lưu vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Chuyển hướng theo role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: /webthuexe/admin/dashboard.php');
                        break;
                    case 'host':
                        header('Location: /webthuexe/host/dashboard.php');
                        break;
                    default:
                        header('Location: /webthuexe/index.php');
                }
                exit();
            } else {
                $error = 'Mật khẩu không đúng';
            }
        } else {
            $error = 'Tên đăng nhập không tồn tại';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Thuê Xe Tự Lái Online</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Đăng nhập</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Tên đăng nhập hoặc Email</label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Đăng nhập</button>
            </form>
            
            <p class="auth-link">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
            
            <div class="demo-accounts">
                <p><strong>Tài khoản demo:</strong></p>
                <p>Admin: admin / 123456</p>
                <p>Chủ xe: host1 / 123456</p>
                <p>Khách hàng: customer1 / 123456</p>
            </div>
        </div>
    </div>
</body>
</html>


