<?php
/**
 * Chi tiết xe và đánh giá
 */
require_once '../config/database.php';
require_once '../config/session.php';

$car_id = $_GET['id'] ?? 0;

// Lấy thông tin xe
$stmt = $conn->prepare("SELECT c.*, u.full_name as owner_name, u.phone as owner_phone,
    (SELECT AVG(rating) FROM reviews WHERE car_id = c.id) as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE car_id = c.id) as review_count
    FROM cars c 
    JOIN users u ON c.owner_id = u.id 
    WHERE c.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php');
    exit();
}

$car = $result->fetch_assoc();
$current_user_id = $_SESSION['user_id'] ?? null;

// Lấy đánh giá
$stmt = $conn->prepare("SELECT r.*, u.full_name 
    FROM reviews r 
    JOIN users u ON r.customer_id = u.id 
    WHERE r.car_id = ? 
    ORDER BY r.created_at DESC");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['name']); ?> - Chi tiết xe</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#f98006",
                        "background-light": "#f8f7f5",
                        "background-dark": "#23190f",
                    },
                    fontFamily: {
                        display: ["Plus Jakarta Sans", "sans-serif"]
                    }
                }
            }
        }
    </script>
</head>
<?php
$main_image = '../uploads/' . ($car['image'] ?: 'default-car.jpg');
// Mapping hiển thị cho loại xe / hình thức thuê / địa điểm giống form thêm xe
$car_type_labels = [
    'sedan'     => 'Sedan',
    'suv'       => 'SUV',
    'mpv'       => 'MPV',
    'pickup'    => 'Bán tải',
    'hatchback' => 'Hatchback',
    'van'       => 'Xe khách',
];

$location_labels = [
    'hcm'     => 'TP. Hồ Chí Minh',
    'hanoi'   => 'Hà Nội',
    'danang'  => 'Đà Nẵng',
    'cantho'  => 'Cần Thơ',
    'nhatrang'=> 'Nha Trang',
    'dalat'   => 'Đà Lạt',
    'phuquoc' => 'Phú Quốc',
];

// Trạng thái xe
$status_text = [
    'available'   => 'Còn xe',
    'rented'      => 'Đang cho thuê',
    'maintenance' => 'Bảo trì'
];

$can_book = isLoggedIn()
    && $current_user_id !== (int)$car['owner_id']
    && $car['status'] === 'available';
?>
<body class="font-display bg-background-light text-text-light">
    <div class="relative flex min-h-screen flex-col bg-background-light">
        <div class="bg-white shadow">
            <?php include '../includes/header.php'; ?>
        </div>

        <main class="container mx-auto px-4 py-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <a href="../index.php" class="hover:text-primary">Trang chủ</a>
                <span>/</span>
                <span>Chi tiết xe</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 mb-8">
                <div class="bg-cover bg-center min-h-[420px] rounded-2xl shadow" style="background-image:url('<?php echo htmlspecialchars($main_image); ?>');"></div>
                <!-- Bỏ gallery ảnh mẫu, chỉ hiển thị ảnh chính của xe -->
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-8">
                <section class="space-y-8">
                    <div class="border-b border-border-light pb-6">
                        <div class="flex flex-wrap justify-between gap-3 mb-4">
                            <div>
                                <p class="text-sm text-secondary uppercase tracking-wide">
                                    <?php
                                        $type_key = $car['car_type'];
                                        echo htmlspecialchars($car_type_labels[$type_key] ?? ucfirst($type_key));
                                    ?>
                                </p>
                                <h1 class="text-4xl font-black text-secondary"><?php echo htmlspecialchars($car['name']); ?></h1>
                                <div class="flex items-center gap-2 text-gray-600 mt-2">
                                    <?php if ($car['avg_rating']): ?>
                                        <span class="material-symbols-outlined text-primary">star</span>
                                        <span><?php echo number_format($car['avg_rating'], 1); ?> (<?php echo $car['review_count']; ?> đánh giá)</span>
                                        <span class="text-gray-400">•</span>
                                    <?php endif; ?>
                                    <span class="material-symbols-outlined text-primary">location_on</span>
                                    <span>
                                        <?php
                                            $loc_key = $car['location'];
                                            echo htmlspecialchars($location_labels[$loc_key] ?? $loc_key);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-4 py-1 rounded-full text-sm font-semibold <?php echo $car['status'] === 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                <?php echo $status_text[$car['status']] ?? $car['status']; ?>
                            </span>
                        </div>
                        <p class="text-3xl font-black text-primary mb-2"><?php echo number_format($car['price_per_day']); ?>đ <span class="text-base text-gray-500 font-normal">/ ngày</span></p>
                        <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                    </div>

                    <div class="border-b border-border-light pb-6 space-y-6">
                        <h3 class="text-2xl font-bold text-secondary">Thông tin chi tiết</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-center">
                            <div class="p-4 rounded-2xl bg-white shadow-sm">
                                <span class="material-symbols-outlined text-3xl text-primary">directions_car</span>
                                <p class="text-sm text-gray-500 mt-2">Loại xe</p>
                                <p class="font-semibold">
                                    <?php
                                        $type_key = $car['car_type'];
                                        echo htmlspecialchars($car_type_labels[$type_key] ?? ucfirst($type_key));
                                    ?>
                                </p>
                            </div>
                            <div class="p-4 rounded-2xl bg-white shadow-sm">
                                <span class="material-symbols-outlined text-3xl text-primary">location_on</span>
                                <p class="text-sm text-gray-500 mt-2">Địa điểm</p>
                                <p class="font-semibold">
                                    <?php
                                        $loc_key = $car['location'];
                                        echo htmlspecialchars($location_labels[$loc_key] ?? $loc_key);
                                    ?>
                                </p>
                            </div>
                            <div class="p-4 rounded-2xl bg-white shadow-sm">
                                <span class="material-symbols-outlined text-3xl text-primary">payments</span>
                                <p class="text-sm text-gray-500 mt-2">Giá/ngày</p>
                                <p class="font-semibold"><?php echo number_format($car['price_per_day']); ?>đ</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-border-light pb-6 space-y-5">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold text-secondary">Đánh giá từ khách hàng</h3>
                            <?php if ($car['avg_rating']): ?>
                                <div class="flex items-center gap-2 text-primary">
                                    <span class="material-symbols-outlined">star</span>
                                    <span class="text-xl font-bold"><?php echo number_format($car['avg_rating'], 1); ?></span>
                                    <span class="text-gray-500">(<?php echo $car['review_count']; ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($reviews)): ?>
                            <p class="text-gray-500">Chưa có đánh giá nào.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($reviews as $review): ?>
                                    <article class="bg-white rounded-2xl shadow p-5 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-semibold"><?php echo htmlspecialchars($review['full_name']); ?></p>
                                                <p class="text-xs text-gray-400"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></p>
                                            </div>
                                            <div class="flex text-primary">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="material-symbols-outlined text-sm"><?php echo $i <= (int)$review['rating'] ? 'star' : 'star_rate'; ?></span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <aside class="space-y-6">
                    <div class="bg-white rounded-2xl shadow p-6 space-y-4">
                        <h3 class="text-lg font-bold text-secondary">Thông tin chủ xe</h3>
                        <div class="flex items-center gap-4">
                            <div class="size-16 rounded-full bg-cover bg-center" style="background-image:url('https://ui-avatars.com/api/?name=<?php echo urlencode($car['owner_name']); ?>&background=f48c25&color=fff');"></div>
                            <div>
                                <p class="text-lg font-bold text-secondary"><?php echo htmlspecialchars($car['owner_name']); ?></p>
                                <p class="text-sm text-gray-500">Tham gia từ 2023</p>
                                <?php if ($car['owner_phone']): ?>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($car['owner_phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3">
                            <?php if ($can_book): ?>
                                <a href="booking.php?car_id=<?php echo $car['id']; ?>" class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-xl bg-primary text-white font-semibold shadow hover:bg-primary/90 transition">
                                    <span class="material-symbols-outlined text-base">event_available</span>
                                    Thuê xe ngay
                                </a>
                            <?php elseif (!isLoggedIn()): ?>
                                <a href="../auth/login.php" class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">
                                    Đăng nhập để thuê xe
                                </a>
                            <?php else: ?>
                                <span class="inline-flex justify-center items-center gap-2 px-4 py-2 rounded-xl bg-gray-200 text-gray-500 font-semibold">
                                    Xe hiện không khả dụng
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow p-6 space-y-4">
                        <h3 class="text-lg font-bold text-secondary">Địa điểm xe</h3>
                        <div class="w-full h-48 bg-gray-200 rounded-2xl overflow-hidden">
                            <div class="w-full h-full bg-cover bg-center" style="background-image:url('https://maps.googleapis.com/maps/api/staticmap?center=<?php echo urlencode($car['location']); ?>&zoom=13&size=640x360&scale=2&markers=color:red%7C<?php echo urlencode($car['location']); ?>');"></div>
                        </div>
                    </div>
                </aside>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>
</body>
</html>


