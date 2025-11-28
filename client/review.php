<?php
/**
 * Đánh giá xe sau khi thuê (Tailwind CSS Design)
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$booking_id = (int)($_GET['booking_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$base_path = getBasePath();

// Lấy thông tin booking
$stmt = $conn->prepare("SELECT b.*, c.id as car_id, c.name as car_name, c.image as car_image, c.car_type,
    u.full_name as owner_name
    FROM bookings b 
    JOIN cars c ON b.car_id = c.id 
    JOIN users u ON c.owner_id = u.id
    WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my-bookings.php?error=' . urlencode('Không tìm thấy chuyến đi hoặc chuyến chưa hoàn thành.'));
    exit();
}

$booking = $result->fetch_assoc();

// Kiểm tra đã đánh giá chưa
$stmt = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$existing_review = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

// Xử lý đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Vui lòng chọn số sao từ 1-5';
    } else {
        if ($existing_review) {
            // Cập nhật đánh giá
            $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE booking_id = ?");
            $stmt->bind_param("isi", $rating, $comment, $booking_id);
        } else {
            // Tạo đánh giá mới
            $car_id = $booking['car_id'];
            $stmt = $conn->prepare("INSERT INTO reviews (car_id, customer_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $car_id, $user_id, $booking_id, $rating, $comment);
        }
        
        if ($stmt->execute()) {
            $success = 'Cảm ơn bạn đã đánh giá!';
            // Reload review
            $stmt = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $existing_review = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
}

$car_type_labels = [
    'sedan' => 'Sedan',
    'suv' => 'SUV',
    'mpv' => 'MPV',
    'pickup' => 'Bán tải',
    'hatchback' => 'Hatchback',
    'van' => 'Xe khách'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá xe - <?php echo htmlspecialchars($booking['car_name']); ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f98006",
                        "secondary": "#3C8DAD",
                        "background-light": "#F8F9FA",
                        "background-dark": "#1a1a1a",
                        "text-light": "#343A40",
                        "text-dark": "#E9ECEF",
                        "border-light": "#E9ECEF",
                        "border-dark": "#343A40",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .star-rating {
            display: flex;
            gap: 0.5rem;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            font-size: 2.5rem;
            color: #d1d5db;
            transition: all 0.2s ease;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #fbbf24;
            transform: scale(1.1);
        }
        .star-rating label:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark min-h-screen">
    <?php include '../includes/header.php'; ?>
    
    <main class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <a href="my-bookings.php" class="hover:text-primary transition-colors">Chuyến của tôi</a>
                <span class="material-symbols-outlined text-base">chevron_right</span>
                <span class="text-text-light dark:text-text-dark">Đánh giá</span>
            </nav>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4">
                    <span class="material-symbols-outlined text-3xl">star</span>
                </div>
                <h1 class="text-3xl font-bold mb-2">Đánh giá chuyến đi</h1>
                <p class="text-gray-500">Chia sẻ trải nghiệm của bạn để giúp những người thuê khác</p>
            </div>

            <?php if ($error): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 mb-6 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined">error</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="rounded-xl border border-green-200 bg-green-50 text-green-700 px-4 py-3 mb-6 text-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined">check_circle</span>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <a href="my-bookings.php?status=completed" class="text-green-800 font-semibold hover:underline">
                        Quay lại đơn đặt →
                    </a>
                </div>
            <?php endif; ?>

            <!-- Car Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-border-light dark:border-border-dark overflow-hidden mb-8">
                <div class="flex flex-col sm:flex-row">
                    <div class="sm:w-1/3">
                        <div class="aspect-[4/3] bg-cover bg-center" 
                             style="background-image: url('<?php echo $base_path ? $base_path . '/uploads/' : '../uploads/'; ?><?php echo htmlspecialchars($booking['car_image'] ?: 'default-car.jpg'); ?>');">
                        </div>
                    </div>
                    <div class="flex-1 p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 px-2.5 py-0.5 text-xs font-medium">
                                <?php echo $car_type_labels[$booking['car_type']] ?? $booking['car_type']; ?>
                            </span>
                            <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 px-2.5 py-0.5 text-xs font-medium">
                                Đã hoàn thành
                            </span>
                        </div>
                        <h2 class="text-xl font-bold mb-3"><?php echo htmlspecialchars($booking['car_name']); ?></h2>
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">person</span>
                                <span>Chủ xe: <?php echo htmlspecialchars($booking['owner_name']); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">calendar_today</span>
                                <span><?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">confirmation_number</span>
                                <span>Mã đơn: #<?php echo $booking_id; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Form -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-border-light dark:border-border-dark p-6 sm:p-8">
                <form method="POST" action="" id="reviewForm">
                    <!-- Rating Stars -->
                    <div class="mb-8">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4 text-center">
                            Bạn đánh giá xe này như thế nào? <span class="text-red-500">*</span>
                        </label>
                        <div class="flex justify-center">
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" 
                                           <?php echo ($existing_review && $existing_review['rating'] == $i) ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> sao">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <span id="ratingText" class="text-sm text-gray-500">
                                <?php 
                                    $rating_texts = [1 => 'Rất tệ', 2 => 'Tệ', 3 => 'Bình thường', 4 => 'Tốt', 5 => 'Tuyệt vời'];
                                    if ($existing_review && $existing_review['rating']) {
                                        echo $rating_texts[$existing_review['rating']];
                                    } else {
                                        echo 'Chọn số sao để đánh giá';
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Comment -->
                    <div class="mb-6">
                        <label for="comment" class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Nhận xét của bạn
                        </label>
                        <textarea id="comment" name="comment" rows="5" 
                                  placeholder="Chia sẻ trải nghiệm của bạn về chiếc xe này: tình trạng xe, độ thoải mái, dịch vụ của chủ xe..."
                                  class="w-full rounded-xl border border-border-light dark:border-border-dark bg-gray-50 dark:bg-gray-900 px-4 py-3 text-base focus:border-primary focus:ring-2 focus:ring-primary/20 resize-none"><?php echo htmlspecialchars($existing_review['comment'] ?? ''); ?></textarea>
                        <p class="mt-2 text-xs text-gray-500">Nhận xét sẽ được hiển thị công khai để giúp người khác đưa ra quyết định</p>
                    </div>

                    <!-- Tips -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-6">
                        <div class="flex gap-3">
                            <span class="material-symbols-outlined text-amber-600 dark:text-amber-400">lightbulb</span>
                            <div class="text-sm text-amber-800 dark:text-amber-300">
                                <p class="font-semibold mb-1">Gợi ý viết đánh giá:</p>
                                <ul class="list-disc list-inside space-y-1 text-amber-700 dark:text-amber-400">
                                    <li>Tình trạng xe có đúng như mô tả không?</li>
                                    <li>Xe có sạch sẽ và thoải mái không?</li>
                                    <li>Chủ xe có hỗ trợ tốt không?</li>
                                    <li>Bạn có gặp vấn đề gì không?</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" 
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-primary text-white px-6 py-3 font-semibold hover:bg-primary/90 transition-colors">
                            <span class="material-symbols-outlined">send</span>
                            <?php echo $existing_review ? 'Cập nhật đánh giá' : 'Gửi đánh giá'; ?>
                        </button>
                        <a href="my-bookings.php?status=completed" 
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 dark:border-gray-600 px-6 py-3 font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <span class="material-symbols-outlined">arrow_back</span>
                            Quay lại
                        </a>
                    </div>
                </form>
            </div>

            <?php if ($existing_review): ?>
            <!-- Current Review Preview -->
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-border-light dark:border-border-dark p-6">
                <h3 class="font-semibold mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">preview</span>
                    Đánh giá hiện tại của bạn
                </h3>
                <div class="flex items-center gap-1 mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="text-xl <?php echo $i <= $existing_review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>">★</span>
                    <?php endfor; ?>
                    <span class="ml-2 text-sm text-gray-500">(<?php echo $existing_review['rating']; ?>/5)</span>
                </div>
                <?php if ($existing_review['comment']): ?>
                    <p class="text-gray-600 dark:text-gray-400 text-sm italic">"<?php echo htmlspecialchars($existing_review['comment']); ?>"</p>
                <?php else: ?>
                    <p class="text-gray-400 text-sm italic">Không có nhận xét</p>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-2">
                    Đánh giá lúc: <?php echo date('H:i d/m/Y', strtotime($existing_review['created_at'])); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        const ratingTexts = {
            1: 'Rất tệ 😞',
            2: 'Tệ 😕',
            3: 'Bình thường 😐',
            4: 'Tốt 😊',
            5: 'Tuyệt vời 🤩'
        };
        
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        const ratingText = document.getElementById('ratingText');
        
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingText.textContent = ratingTexts[this.value];
            });
        });

        // Form validation
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            const selectedRating = document.querySelector('input[name="rating"]:checked');
            if (!selectedRating) {
                e.preventDefault();
                alert('Vui lòng chọn số sao để đánh giá!');
            }
        });
    </script>
</body>
</html>
