<?php
/**
 * Trang đăng ký tài khoản - CarRental
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
                        // Đăng ký thành công: lưu thông báo ngắn gọn và chuyển sang trang đăng nhập
                        $_SESSION['success'] = 'Đăng ký thành công';
                        header('Location: login.php');
                        exit;
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
<html class="light" lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Tạo tài khoản mới - CarRental</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#f48c25",
            "background-light": "#f8f7f5",
            "background-dark": "#221910",
            "secondary": "#005a8d",
            "text-light-primary": "#181411",
            "text-light-secondary": "#8a7560",
            "text-dark-primary": "#f8f7f5",
            "text-dark-secondary": "#a19b95",
            "border-light": "#e6e0db",
            "border-dark": "#3a3026"
          },
          fontFamily: {
            "display": ["Plus Jakarta Sans", "sans-serif"]
          },
          borderRadius: {
            "DEFAULT": "1rem",
            "lg": "2rem",
            "xl": "3rem",
            "full": "9999px"
          },
        },
      },
    }
</script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<div class="relative flex min-h-screen w-full flex-col group/design-root overflow-x-hidden">
<div class="layout-container flex h-screen grow flex-col">
<div class="flex flex-1 items-stretch justify-center">
<div class="flex w-full overflow-hidden bg-white dark:bg-background-dark/50">
<div class="w-full lg:w-1/2 px-6 py-6 sm:px-12 sm:py-8 flex flex-col justify-center overflow-y-auto">
<div class="max-w-md mx-auto w-full">
<div class="flex items-center gap-2 mb-4 text-primary">
<svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
<path d="M18.92 5.01C18.72 4.42 18.16 4 17.5 4h-11c-.66 0-1.21.42-1.42 1.01L3 11v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 15c-.83 0-1.5-.67-1.5-1.5S5.67 12 6.5 12s1.5.67 1.5 1.5S7.33 15 6.5 15zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 10l1.5-4.5h11L19 10H5z"/>
</svg>
<h2 class="text-xl font-bold leading-tight tracking-[-0.015em]">CarRental</h2>
</div>
<div class="flex flex-col gap-1 mb-4">
<h1 class="text-text-light-primary dark:text-text-dark-primary text-2xl font-bold leading-tight tracking-tight">Tạo tài khoản mới</h1>
<p class="text-text-light-secondary dark:text-text-dark-secondary text-sm font-normal leading-normal">Bắt đầu hành trình của bạn chỉ với vài bước đơn giản.</p>
</div>

<?php if ($error): ?>
<div class="mb-3 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="mb-3 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<form method="POST" action="" class="flex flex-col gap-3">
<label class="flex flex-col w-full">
<p class="text-text-light-primary dark:text-text-dark-primary text-xs font-medium leading-normal pb-1.5">Họ và Tên</p>
<input name="full_name" required class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded text-text-light-primary dark:text-text-dark-primary focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-transparent focus:border-primary/50 h-10 placeholder:text-text-light-secondary dark:placeholder:text-text-dark-secondary px-3 text-sm font-normal leading-normal" placeholder="Nhập họ và tên của bạn" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"/>
</label>

<label class="flex flex-col w-full">
<p class="text-text-light-primary dark:text-text-dark-primary text-xs font-medium leading-normal pb-1.5">Tên đăng nhập</p>
<input name="username" required class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded text-text-light-primary dark:text-text-dark-primary focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-transparent focus:border-primary/50 h-10 placeholder:text-text-light-secondary dark:placeholder:text-text-dark-secondary px-3 text-sm font-normal leading-normal" placeholder="Nhập tên đăng nhập" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"/>
</label>

<label class="flex flex-col w-full">
<p class="text-text-light-primary dark:text-text-dark-primary text-xs font-medium leading-normal pb-1.5">Email</p>
<input name="email" required type="email" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded text-text-light-primary dark:text-text-dark-primary focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-transparent focus:border-primary/50 h-10 placeholder:text-text-light-secondary dark:placeholder:text-text-dark-secondary px-3 text-sm font-normal leading-normal" placeholder="Nhập địa chỉ email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"/>
</label>

<label class="flex flex-col w-full">
<p class="text-text-light-primary dark:text-text-dark-primary text-xs font-medium leading-normal pb-1.5">Số điện thoại</p>
<input name="phone" type="tel" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded text-text-light-primary dark:text-text-dark-primary focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-transparent focus:border-primary/50 h-10 placeholder:text-text-light-secondary dark:placeholder:text-text-dark-secondary px-3 text-sm font-normal leading-normal" placeholder="Nhập số điện thoại" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"/>
</label>

<label class="flex flex-col w-full">
<p class="text-text-light-primary dark:text-text-dark-primary text-xs font-medium leading-normal pb-1.5">Mật khẩu</p>
<div class="relative flex items-center">
<input name="password" id="password" required type="password" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded text-text-light-primary dark:text-text-dark-primary focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-transparent focus:border-primary/50 h-10 placeholder:text-text-light-secondary dark:placeholder:text-text-dark-secondary px-3 pr-10 text-sm font-normal leading-normal" placeholder="Nhập mật khẩu"/>
<button class="absolute right-2 text-text-light-secondary dark:text-text-dark-secondary" type="button" onclick="togglePassword('password', this)">
<span class="material-symbols-outlined text-lg">visibility_off</span>
</button>
</div>
</label>

<label class="flex flex-col w-full">
<p class="text-text-light-primary dark:text-text-dark-primary text-xs font-medium leading-normal pb-1.5">Xác nhận mật khẩu</p>
<div class="relative flex items-center">
<input name="confirm_password" id="confirm_password" required type="password" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded text-text-light-primary dark:text-text-dark-primary focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-transparent focus:border-primary/50 h-10 placeholder:text-text-light-secondary dark:placeholder:text-text-dark-secondary px-3 pr-10 text-sm font-normal leading-normal" placeholder="Nhập lại mật khẩu"/>
<button class="absolute right-2 text-text-light-secondary dark:text-text-dark-secondary" type="button" onclick="togglePassword('confirm_password', this)">
<span class="material-symbols-outlined text-lg">visibility_off</span>
</button>
</div>
</label>

<button type="submit" class="flex min-w-[84px] w-full mt-2 cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition-colors">
<span class="truncate">Đăng ký</span>
</button>
</form>

<div class="relative flex items-center my-4">
<div class="flex-grow border-t border-border-light dark:border-border-dark"></div>
<span class="mx-3 flex-shrink text-xs text-text-light-secondary dark:text-text-dark-secondary">Hoặc</span>
<div class="flex-grow border-t border-border-light dark:border-border-dark"></div>
</div>

<div class="flex flex-col sm:flex-row gap-2">
<button type="button" class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-border-light dark:border-border-dark h-9 px-3 text-text-light-primary dark:text-text-dark-primary text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
</svg>
<span>Google</span>
</button>
<button type="button" class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-border-light dark:border-border-dark h-9 px-3 text-text-light-primary dark:text-text-dark-primary text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
<svg class="w-4 h-4 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
</svg>
<span>Facebook</span>
</button>
</div>

<p class="mt-4 text-center text-xs text-text-light-secondary dark:text-text-dark-secondary">
Đã có tài khoản? <a class="font-bold text-secondary hover:underline" href="login.php">Đăng nhập</a>
</p>
</div>
</div>

<div class="hidden lg:block w-1/2 relative h-full">
<div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCPNDCKL8rTiUesQekdovpr5uQxYSObwNt8HK8l1OG_3V4IBR3801Ux76qTuQyBAhEEEeOFemeYm3diSQV-J05JHeC19iDWAKer3m1hYidizWMHYZSnJUBWTAUZyyp0oQZLVD7No4HnSqa9_aZLd8irRDwsroSqYa8F0rhHX3NHxubD4bVcNInzgfNVrqWOhS_001Q5ueCvwFx9YlCjZRL-MYWI-nPMDBlSYXaNz2AaxPYJd9ukflv4LYC01BBWvjJFKaLWWo3XEnmE');"></div>
</div>
</div>
</div>
</div>
</div>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('.material-symbols-outlined');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility_off';
    }
}
</script>
</body>
</html>
