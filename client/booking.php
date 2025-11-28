<?php
/**
 * Đặt xe - Tất cả user đều có thể đặt xe
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$car_id = $_GET['car_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy thông tin xe
$stmt = $conn->prepare("SELECT c.*, u.full_name as owner_name 
    FROM cars c 
    JOIN users u ON c.owner_id = u.id 
    WHERE c.id = ? AND c.status = 'available'");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php');
    exit();
}

$car = $result->fetch_assoc();

// Mapping hiển thị cho loại xe và hình thức thuê
$car_type_labels = [
    'sedan'     => 'Sedan',
    'suv'       => 'SUV',
    'mpv'       => 'MPV',
    'pickup'    => 'Bán tải',
    'hatchback' => 'Hatchback',
    'van'       => 'Xe khách',
];

$rental_type_labels = [
    'self-drive'  => 'Xe tự lái',
    'with-driver' => 'Xe có tài xế',
    'long-term'   => 'Thuê dài hạn',
];

$error = '';
$success = '';

// Xử lý đặt xe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (empty($start_date) || empty($end_date)) {
        $error = 'Vui lòng chọn ngày bắt đầu và kết thúc';
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = 'Ngày bắt đầu phải từ hôm nay trở đi';
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $error = 'Ngày kết thúc phải sau ngày bắt đầu';
    } else {
        // Kiểm tra trùng lịch (overlap detection)
        // 2 khoảng thời gian overlap nếu: start_date_moi <= end_date_cu AND end_date_moi >= start_date_cu
        $stmt = $conn->prepare("SELECT id FROM bookings 
            WHERE car_id = ? 
            AND status = 'confirmed'
            AND start_date <= ? 
            AND end_date >= ?");
        $stmt->bind_param("iss", $car_id, $end_date, $start_date);
        $stmt->execute();
        $conflict = $stmt->get_result();
        
        if ($conflict->num_rows > 0) {
            $error = 'Xe đã có người đặt (đã thanh toán) trong khoảng thời gian này. Vui lòng chọn ngày khác.';
        } else {
            // Tính tổng tiền
            $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
            $total_price = $days * $car['price_per_day'];
            
            // Tạo booking
            $stmt = $conn->prepare("INSERT INTO bookings (car_id, customer_id, start_date, end_date, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iissd", $car_id, $user_id, $start_date, $end_date, $total_price);
            
            if ($stmt->execute()) {
                $booking_id = $conn->insert_id;
                
                // Chuyển sang trang thanh toán
                header("Location: payment.php?booking_id=$booking_id");
                exit();
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt xe - <?php echo htmlspecialchars($car['name']); ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#f48c25',
                        background: '#f9f5f0',
                        slate: '#6c6a67'
                    },
                    fontFamily: { display: ['Plus Jakarta Sans', 'sans-serif'] },
                    boxShadow: { card: '0 24px 70px rgba(15,23,42,0.08)' }
                }
            }
        }
    </script>
</head>
<body class="font-display bg-background text-[#1e1c1a]">
    <div class="min-h-screen flex flex-col">
        <div class="bg-white shadow-sm sticky top-0 z-10">
            <?php include '../includes/header.php'; ?>
        </div>

        <main class="px-4 py-10 flex-1">
            <div class="max-w-5xl mx-auto space-y-6">
                <div class="flex items-center text-sm text-slate gap-2">
                    <a href="../index.php" class="hover:text-primary">Trang chủ</a>
                    <span>/</span>
                    <a href="car-detail.php?id=<?php echo (int)$car_id; ?>" class="hover:text-primary">Chi tiết xe</a>
                    <span>/</span>
                    <span>Đặt xe</span>
                </div>

                <?php if ($error): ?>
                    <div class="rounded-2xl border border-red-200 bg-red-50 text-red-700 px 4 py-3">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-3xl shadow-card p-6 md:p-10 space-y-8">
                    <div>
                        <p class="text-xs uppercase tracking-[0.4em] text-slate mb-2">Đặt xe</p>
                        <h1 class="text-3xl md:text-4xl font-semibold"><?php echo htmlspecialchars($car['name']); ?></h1>
                        <p class="text-slate mt-2">Hoàn tất thông tin để giữ chỗ và chuyển sang bước thanh toán.</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="border border-[#eee1d4] rounded-2xl p-5 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#f7c68a] to-[#f48c25] flex items-center justify-center text-white">
                                    <span class="material-symbols-outlined text-2xl">garage_home</span>
                                </div>
                                <div>
                                    <p class="text-xs uppercase text-slate">Chủ xe</p>
                                    <p class="font-semibold text-lg"><?php echo htmlspecialchars($car['owner_name']); ?></p>
                                </div>
                            </div>
                            <div class="text-sm text-slate space-y-1">
                                <p><strong>Loại xe:</strong> <?php echo htmlspecialchars($car_type_labels[$car['car_type']] ?? ucfirst($car['car_type'])); ?></p>
                                <p><strong>Hình thức thuê:</strong> <?php echo htmlspecialchars($rental_type_labels[$car['rental_type']] ?? $car['rental_type']); ?></p>
                                <p><strong>Giá thuê:</strong> <?php echo number_format($car['price_per_day']); ?> VNĐ/ngày</p>
                            </div>
                        </div>
                        <div class="border border-[#eee1d4] rounded-2xl p-5 bg-[#fffaf4] text-sm text-slate space-y-2">
                            <p class="text-xs uppercase tracking-[0.4em]">Lưu ý</p>
                            <p>• Vui lòng mang theo CMND/CCCD và GPLX khi nhận xe.</p>
                            <p>• Hủy miễn phí trong 24h đầu; sau đó phí 20% tổng tiền.</p>
                            <p>• Chủ xe có quyền từ chối nếu thông tin không chính xác.</p>
                        </div>
                    </div>

                    <form method="POST" id="bookingForm" class="space-y-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="p-4 border border-[#eee1d4] rounded-2xl">
                                <label class="text-sm text-slate block mb-2" for="start_date">Ngày bắt đầu *</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">calendar_month</span>
                                    <input type="date" id="start_date" name="start_date"
                                           class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="p-4 border border-[#eee1d4] rounded-2xl">
                                <label class="text-sm text-slate block mb-2" for="end_date">Ngày kết thúc *</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">event</span>
                                    <input type="date" id="end_date" name="end_date"
                                           class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary"
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                           value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 border border-[#eee1d4] rounded-2xl bg-white">
                            <p class="text-xs uppercase text-slate tracking-[0.4em] mb-4">Tổng tiền dự kiến</p>
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-4xl font-bold text-primary"><span id="totalPrice">0</span> VNĐ</p>
                                    <p class="text-slate mt-1">Số ngày: <span id="days">0</span> ngày</p>
                                </div>
                                <div class="text-sm text-slate bg-[#fff2e3] border border-dashed border-primary/40 rounded-xl px-4 py-3">
                                    <p><strong>Chính sách:</strong></p>
                                    <p>- Đặt cọc sẽ hoàn lại sau khi trả xe</p>
                                    <p>- Hủy sau 24h: phí 20% tổng tiền</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <button type="submit" class="flex-1 inline-flex justify-center items-center gap-2 py-4 rounded-2xl bg-primary text-white text-lg font-semibold shadow hover:bg-[#e47a0f] transition">
                                <span class="material-symbols-outlined">credit_score</span>
                                Tiếp tục thanh toán
                            </button>
                            <a href="car-detail.php?id=<?php echo (int)$car_id; ?>" class="flex-1 inline-flex justify-center items-center gap-2 py-4 rounded-2xl border border-[#e0d8ce] text-slate font-semibold hover:bg-[#f3ece3] transition">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Quay lại xem xe
                            </a>
                        </div>
                    </form>
                </section>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>

    <script>
        const pricePerDay = <?php echo $car['price_per_day']; ?>;
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        function calculateTotal() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            if (startDate && endDate && endDate > startDate) {
                const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                const total = days * pricePerDay;
                document.getElementById('days').textContent = days;
                document.getElementById('totalPrice').textContent = total.toLocaleString('vi-VN');
            } else {
                document.getElementById('days').textContent = '0';
                document.getElementById('totalPrice').textContent = '0';
            }
        }

        startDateInput.addEventListener('change', calculateTotal);
        endDateInput.addEventListener('change', calculateTotal);
    </script>
</body>
</html>
