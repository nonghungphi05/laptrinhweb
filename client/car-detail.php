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
$can_message_owner = isLoggedIn() && $current_user_id !== (int)$car['owner_id'];
$messages_url = '../messages.php?with=' . (int)$car['owner_id'];

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
$gallery_images = [
    $main_image,
    'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1549921296-3b4a4f9536b8?auto=format&fit=crop&w=900&q=80'
];
$status_text = [
    'available' => 'Còn xe',
    'rented' => 'Đang cho thuê',
    'maintenance' => 'Bảo trì'
];
?>
<body class="font-display bg-background-light text-[#181411]">
    <div class="min-h-screen flex flex-col">
        <div class="bg-white shadow">
            <?php include '../includes/header.php'; ?>
        </div>

        <main class="flex-1 px-4 py-8 md:px-8 lg:px-12">
            <div class="max-w-6xl mx-auto">
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                    <a href="../index.php" class="hover:text-primary">Trang chủ</a>
                    <span>/</span>
                    <span>Chi tiết xe</span>
                </div>

                <div class="grid gap-8 lg:grid-cols-5">
                    <div class="lg:col-span-3 space-y-4">
                        <div class="bg-white rounded-2xl shadow overflow-hidden min-h-[380px] bg-cover bg-center flex items-end justify-center" style="background-image:url('<?php echo htmlspecialchars($gallery_images[0]); ?>');">
                            <div class="flex gap-2 mb-4">
                                <?php foreach ($gallery_images as $index => $image): ?>
                                    <span class="w-2 h-2 rounded-full <?php echo $index === 0 ? 'bg-white' : 'bg-white/60'; ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-3">
                            <?php foreach ($gallery_images as $image): ?>
                                <div class="h-24 rounded-xl overflow-hidden bg-cover bg-center border border-white shadow-sm" style="background-image:url('<?php echo htmlspecialchars($image); ?>');"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <div class="bg-white rounded-2xl shadow p-6 space-y-4">
                            <div>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars(ucfirst($car['car_type'])); ?></p>
                                <h1 class="text-3xl font-extrabold text-[#181411]"><?php echo htmlspecialchars($car['name']); ?></h1>
                                <span class="inline-flex mt-2 px-3 py-1 rounded-full text-xs font-semibold <?php echo $car['status'] === 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                    <?php echo $status_text[$car['status']] ?? $car['status']; ?>
                                </span>
                            </div>
                            <?php if ($car['avg_rating']): ?>
                                <div class="flex items-center gap-1 text-[#f5a524]">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="material-symbols-outlined text-base"><?php echo $i <= round($car['avg_rating']) ? 'star' : 'star_rate'; ?></span>
                                    <?php endfor; ?>
                                    <span class="text-sm text-gray-500 ml-2"><?php echo number_format($car['avg_rating'], 1); ?> (<?php echo $car['review_count']; ?> đánh giá)</span>
                                </div>
                            <?php endif; ?>
                            <p class="text-4xl font-black text-primary"><?php echo number_format($car['price_per_day']); ?>đ <span class="text-base text-gray-500 font-normal">/ ngày</span></p>
                            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>

                            <div class="border-t border-gray-100 pt-4 space-y-3">
                                <div>
                                    <p class="text-xs uppercase text-gray-400">Chủ xe</p>
                                    <p class="text-lg font-semibold text-[#181411]"><?php echo htmlspecialchars($car['owner_name']); ?></p>
                                </div>
                                <?php if ($car['owner_phone']): ?>
                                    <div>
                                        <p class="text-xs uppercase text-gray-400">Liên hệ</p>
                                        <p class="font-medium text-gray-700"><?php echo htmlspecialchars($car['owner_phone']); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-3 pt-2">
                                    <?php if ($can_message_owner): ?>
                                        <a href="<?php echo $messages_url; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary text-primary font-semibold hover:bg-primary/5 transition">
                                            <span class="material-symbols-outlined text-base">chat</span>
                                            Chat với chủ xe
                                        </a>
                                    <?php elseif (!isLoggedIn()): ?>
                                        <a href="../auth/login.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary text-primary font-semibold hover:bg-primary/5 transition">
                                            <span class="material-symbols-outlined text-base">chat</span>
                                            Đăng nhập để chat
                                        </a>
                                    <?php endif; ?>

                                    <?php if (isLoggedIn() && hasRole('customer') && $car['status'] === 'available'): ?>
                                        <a href="booking.php?car_id=<?php echo $car['id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white font-semibold shadow hover:bg-primary/90 transition">
                                            <span class="material-symbols-outlined text-base">bolt</span>
                                            Đặt xe ngay
                                        </a>
                                    <?php elseif (!isLoggedIn()): ?>
                                        <a href="../auth/login.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-900 text-white font-semibold">
                                            Đăng nhập để đặt xe
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow p-6 space-y-4">
                            <h3 class="text-lg font-bold">Thông tin cơ bản</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <p class="text-gray-400 text-xs uppercase">Loại xe</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($car['car_type']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs uppercase">Hình thức thuê</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($car['rental_type']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs uppercase">Địa điểm</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($car['location']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-xs uppercase">Giá/ngày</p>
                                    <p class="font-semibold text-gray-800"><?php echo number_format($car['price_per_day']); ?>đ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="mt-12">
                    <h2 class="text-2xl font-bold text-[#181411] mb-4">Đánh giá từ khách hàng</h2>
                    <?php if (empty($reviews)): ?>
                        <p class="text-gray-500">Chưa có đánh giá nào.</p>
                    <?php else: ?>
                        <div class="grid gap-6 md:grid-cols-2">
                            <?php foreach ($reviews as $review): ?>
                                <div class="bg-white rounded-2xl shadow p-5 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold"><?php echo htmlspecialchars($review['full_name']); ?></p>
                                            <p class="text-xs text-gray-400"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></p>
                                        </div>
                                        <div class="flex text-[#f5a524]">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="material-symbols-outlined text-sm"><?php echo $i <= (int)$review['rating'] ? 'star' : 'star_rate'; ?></span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>
</body>
</html>


