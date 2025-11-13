<?php
/**
 * Trang cá nhân của user - Tailwind CSS Design
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

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

// Lấy số xe đang cho thuê
$stmt = $conn->prepare("SELECT COUNT(*) as car_count FROM cars WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$car_result = $stmt->get_result();
$car_data = $car_result->fetch_assoc();
$car_count = $car_data['car_count'] ?? 0;

// Format ngày tham gia
$joined_date = date('d/m/Y', strtotime($user['created_at']));
$joined_month = date('F Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quản lý tài khoản - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f98006",
                        "background-light": "#f8f7f5",
                        "background-dark": "#23190f",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "Noto Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
        <!-- Header -->
        <?php include '../includes/header.php'; ?>
        
        <div class="layout-container flex h-full grow flex-col">
            <div class="px-4 sm:px-6 lg:px-8 py-8">
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Sidebar Menu (Sticky) -->
                    <aside class="lg:col-span-3">
                        <div class="bg-white dark:bg-background-dark/50 p-6 rounded-xl shadow-lg sticky top-28">
                            <h2 class="text-lg font-bold text-[#181411] dark:text-white mb-6">Quản lý tài khoản</h2>
                            <nav class="space-y-2">
                                <a href="<?php echo $base_path ? $base_path . '/client/profile.php' : 'profile.php'; ?>" 
                                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary transition-colors font-bold">
                                    <span class="material-symbols-outlined">person</span>
                                    <span class="font-medium">Thông tin cá nhân</span>
                                </a>
                                <a href="<?php echo $base_path ? $base_path . '/forum/my-posts.php' : '../forum/my-posts.php'; ?>" 
                                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/20 dark:hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">directions_car</span>
                                    <span class="font-medium">Quản lý cho thuê</span>
                                </a>
                                <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'my-bookings.php'; ?>" 
                                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/20 dark:hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">history</span>
                                    <span class="font-medium">Lịch sử đặt xe</span>
                                </a>
                                <a href="#" 
                                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/20 dark:hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">receipt_long</span>
                                    <span class="font-medium">Lịch sử thanh toán</span>
                                </a>
                                <a href="#" 
                                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/20 dark:hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">location_on</span>
                                    <span class="font-medium">Quản lý địa chỉ</span>
                                </a>
                                <a href="<?php echo $base_path ? $base_path . '/auth/logout.php' : '../auth/logout.php'; ?>" 
                                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-500/10 transition-colors">
                                    <span class="material-symbols-outlined">logout</span>
                                    <span class="font-medium">Đăng xuất</span>
                                </a>
                            </nav>
                        </div>
                    </aside>
                    
                    <!-- Main Content -->
                    <main class="lg:col-span-9">
                        <div class="bg-white dark:bg-background-dark/50 p-6 sm:p-8 rounded-xl shadow-lg">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                                <div>
                                    <h1 class="text-2xl font-bold text-[#181411] dark:text-white">Thông tin cá nhân</h1>
                                    <p class="text-gray-600 dark:text-gray-300 mt-1">Quản lý thông tin tài khoản và cài đặt cá nhân của bạn.</p>
                                </div>
                                <button class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors w-full sm:w-auto">
                                    <span class="material-symbols-outlined">edit</span>
                                    <span>Chỉnh sửa</span>
                                </button>
                            </div>
                            
                            <div class="space-y-6">
                                <!-- Avatar và thông tin cơ bản -->
                                <div class="flex flex-col sm:flex-row items-start gap-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-32 ring-4 ring-primary/20" 
                                         style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name'] ?: $user['username']); ?>&background=f98006&color=fff&size=128");'>
                                    </div>
                                    <div class="flex-1">
                                        <h2 class="text-2xl font-bold text-[#181411] dark:text-white mb-2"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Thành viên từ <?php echo $joined_month; ?></p>
                                        
                                        <!-- Stats -->
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div class="flex items-center gap-4 rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-background-light dark:bg-background-dark">
                                                <span class="material-symbols-outlined text-primary text-3xl">directions_car</span>
                                                <div class="flex-1">
                                                    <p class="font-bold text-lg text-[#181411] dark:text-white"><?php echo $car_count; ?></p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Xe cho thuê</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4 rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-background-light dark:bg-background-dark">
                                                <span class="material-symbols-outlined text-primary text-3xl">luggage</span>
                                                <div class="flex-1">
                                                    <p class="font-bold text-lg text-[#181411] dark:text-white"><?php echo $trip_count; ?></p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Chuyến đã thuê</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4 rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-background-light dark:bg-background-dark">
                                                <span class="material-symbols-outlined text-primary text-3xl">star</span>
                                                <div class="flex-1">
                                                    <p class="font-bold text-lg text-[#181411] dark:text-white">--</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Đánh giá</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Thông tin chi tiết -->
                                <div class="space-y-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 border border-gray-200 dark:border-gray-700 rounded-xl">
                                        <div class="flex items-start gap-4">
                                            <span class="material-symbols-outlined text-primary mt-1">person</span>
                                            <div>
                                                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Họ và tên</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($user['full_name'] ?: 'Chưa cập nhật'); ?></p>
                                            </div>
                                        </div>
                                        <button class="p-2 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-primary rounded-full hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    </div>
                                    
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 border border-gray-200 dark:border-gray-700 rounded-xl">
                                        <div class="flex items-start gap-4">
                                            <span class="material-symbols-outlined text-primary mt-1">email</span>
                                            <div>
                                                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Email</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 mt-1">Đã xác thực</span>
                                            </div>
                                        </div>
                                        <button class="p-2 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-primary rounded-full hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    </div>
                                    
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 border border-gray-200 dark:border-gray-700 rounded-xl">
                                        <div class="flex items-start gap-4">
                                            <span class="material-symbols-outlined text-primary mt-1">phone</span>
                                            <div>
                                                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Số điện thoại</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($user['phone'] ?: 'Chưa cập nhật'); ?></p>
                                                <?php if ($user['phone']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 mt-1">Đã xác thực</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 mt-1">Chưa xác thực</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button class="p-2 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-primary rounded-full hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    </div>
                                    
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 border border-gray-200 dark:border-gray-700 rounded-xl">
                                        <div class="flex items-start gap-4">
                                            <span class="material-symbols-outlined text-primary mt-1">calendar_today</span>
                                            <div>
                                                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Ngày tham gia</h3>
                                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo $joined_date; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Giấy phép lái xe -->
                                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-xl font-bold text-[#181411] dark:text-white">Giấy phép lái xe</h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">Chưa xác thực</span>
                                    </div>
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                            <strong>Lưu ý:</strong> Bạn cần cập nhật giấy phép lái xe để có thể thuê xe. Vui lòng tải lên hình ảnh và điền thông tin.
                                        </p>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hình ảnh giấy phép lái xe</label>
                                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary transition-colors cursor-pointer">
                                                <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">cloud_upload</span>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Kéo thả hoặc click để tải ảnh</p>
                                                <button type="button" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-bold">
                                                    Tải ảnh lên
                                                </button>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <label for="license-number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Số GPLX</label>
                                                <input type="text" id="license-number" 
                                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                                                       placeholder="Nhập số giấy phép lái xe" 
                                                       value="">
                                            </div>
                                            <div>
                                                <label for="license-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Họ và tên trên GPLX</label>
                                                <input type="text" id="license-name" 
                                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                                                       placeholder="Nhập họ và tên" 
                                                       value="">
                                            </div>
                                            <div>
                                                <label for="license-dob" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ngày sinh trên GPLX</label>
                                                <input type="date" id="license-dob" 
                                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                                                       value="">
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-primary hover:underline cursor-pointer">
                                            <span class="material-symbols-outlined">help</span>
                                            <span>Hướng dẫn cập nhật giấy phép lái xe</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
