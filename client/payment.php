<?php
/**
 * Trang thanh toán - giao diện mới (stitch_trang_ch_home 4)
 */
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$booking_id = (int)($_GET['booking_id'] ?? 0);
$user_id    = $_SESSION['user_id'] ?? 0;

if (!$booking_id) {
    header('Location: my-bookings.php');
    exit();
}

// Thông tin đơn đặt xe
$stmt = $conn->prepare("
    SELECT b.*, c.name AS car_name, c.image AS car_image, c.location
    FROM bookings b
    JOIN cars c ON b.car_id = c.id
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my-bookings.php');
    exit();
}

$booking = $result->fetch_assoc();
$days = max(1, (strtotime($booking['end_date']) - strtotime($booking['start_date'])) / 86400);
$car_image = '../uploads/' . ($booking['car_image'] ?: 'default-car.jpg');

// Mapping địa điểm
$location_labels = [
    'hcm'     => 'TP. Hồ Chí Minh',
    'hanoi'   => 'Hà Nội',
    'danang'  => 'Đà Nẵng',
    'cantho'  => 'Cần Thơ',
    'nhatrang'=> 'Nha Trang',
    'dalat'   => 'Đà Lạt',
    'phuquoc' => 'Phú Quốc',
    'haiphong'=> 'Hải Phòng',
    'vungtau' => 'Vũng Tàu',
    'hue'     => 'Huế',
    'quynhon' => 'Quy Nhơn',
    'hoian'   => 'Hội An'
];
$location_display = $location_labels[$booking['location']] ?? $booking['location'];

// Lấy thông tin thanh toán mới nhất
$pay_stmt = $conn->prepare("
    SELECT id, amount, payment_method, transaction_id, status, created_at
    FROM payments
    WHERE booking_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$pay_stmt->bind_param('i', $booking_id);
$pay_stmt->execute();
$payment = $pay_stmt->get_result()->fetch_assoc();

$payment_status  = $payment['status'] ?? null;
$already_paid    = $payment_status === 'completed';
$transaction_id  = $payment['transaction_id'] ?? null;
$payment_method  = $payment['payment_method'] ?? 'VNPAY';
$payment_created = $payment['created_at'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Đơn #<?php echo $booking_id; ?></title>
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
                        accent: '#fef7f0',
                        background: '#f8f4ef',
                        slate: '#5e5b58'
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif']
                    },
                    boxShadow: {
                        card: '0 24px 60px rgba(23,26,31,0.08)'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background font-sans text-[#1f1d1a]">
    <div class="min-h-screen flex flex-col">
        <div class="bg-white shadow-sm sticky top-0 z-20">
            <?php include '../includes/header.php'; ?>
        </div>

        <main class="px-4 py-10 flex-1">
            <div class="max-w-5xl mx-auto space-y-8">
                <div class="flex items-center text-sm text-slate gap-2">
                    <a href="../index.php" class="hover:text-primary">Trang chủ</a>
                    <span>/</span>
                    <a href="my-bookings.php" class="hover:text-primary">Đơn đặt</a>
                    <span>/</span>
                    <span>Thanh toán</span>
                </div>

                <?php if ($already_paid): ?>
                    <div class="rounded-2xl bg-green-50 border border-green-200 px-5 py-4 text-green-700 flex items-start gap-3">
                        <span class="material-symbols-outlined">verified</span>
                        <div>
                            <p class="font-semibold">Đơn hàng đã được thanh toán thành công.</p>
                            <?php if ($transaction_id): ?>
                                <p class="text-sm">Mã giao dịch: <?php echo htmlspecialchars($transaction_id); ?></p>
                            <?php endif; ?>
                            <?php if ($payment_created): ?>
                                <p class="text-sm">Thời gian: <?php echo date('d/m/Y H:i', strtotime($payment_created)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 px-5 py-4 text-amber-800 flex items-start gap-3">
                        <span class="material-symbols-outlined">info</span>
                        <div>
                            <p class="font-semibold">Chưa thanh toán</p>
                            <p class="text-sm">Chuyến xe sẽ chỉ được giữ chỗ sau khi bạn hoàn tất thanh toán.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
                    <section class="bg-white rounded-3xl shadow-card p-6 md:p-8 space-y-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.4em] text-slate mb-1">Thanh toán</p>
                                <h1 class="text-3xl font-semibold">Đơn #<?php echo $booking_id; ?></h1>
                                <p class="text-slate mt-1">Hoàn tất bước cuối để xác nhận chuyến đi.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full border border-slate/20 text-slate text-sm">
                                <?php echo $already_paid ? 'Đã thanh toán' : 'Chờ thanh toán'; ?>
                            </span>
                        </div>

                        <div class="rounded-2xl border border-[#eadfd4] p-5 flex flex-col md:flex-row gap-5">
                            <div class="w-full md:w-40 h-40 rounded-2xl bg-gray-100 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($car_image); ?>" alt="<?php echo htmlspecialchars($booking['car_name']); ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 space-y-4">
                                <div>
                                    <p class="text-xs uppercase text-slate">Xe</p>
                                    <h3 class="text-2xl font-bold"><?php echo htmlspecialchars($booking['car_name']); ?></h3>
                                    <?php if (!empty($booking['location'])): ?>
                                        <p class="text-sm text-slate flex items-center gap-1 mt-1">
                                            <span class="material-symbols-outlined text-base">location_on</span>
                                            <?php echo htmlspecialchars($location_display); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm text-slate">
                                    <div>
                                        <p class="text-slate/60">Ngày nhận</p>
                                        <p class="font-medium"><?php echo date('d/m/Y', strtotime($booking['start_date'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate/60">Ngày trả</p>
                                        <p class="font-medium"><?php echo date('d/m/Y', strtotime($booking['end_date'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate/60">Số ngày</p>
                                        <p class="font-medium"><?php echo $days; ?> ngày</p>
                                    </div>
                                    <div>
                                        <p class="text-slate/60">Tổng tiền</p>
                                        <p class="font-semibold text-primary"><?php echo number_format($booking['total_price']); ?> VNĐ</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$already_paid): ?>
                            <div class="space-y-4">
                                <h2 class="text-lg font-semibold">Chọn phương thức thanh toán</h2>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <form action="../api/vnpay-payment.php" method="POST" class="h-full">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                        <button type="submit" class="w-full h-full rounded-2xl border border-primary bg-white p-5 text-left hover:shadow-lg transition flex flex-col gap-2">
                                            <div class="flex items-center gap-3">
                                                <div class="p-3 bg-primary/10 rounded-full">
                                                    <span class="material-symbols-outlined text-primary">contactless</span>
                                                </div>
                                                <div>
                                                    <p class="text-base font-semibold">VNPAY</p>
                                                    <p class="text-slate text-sm">Thanh toán trực tuyến (môi trường test)</p>
                                                </div>
                                            </div>
                                            <ul class="text-sm text-slate list-disc list-inside space-y-1">
                                                <li>Hỗ trợ thẻ nội địa &amp; quốc tế</li>
                                                <li>Bảo mật chuẩn PCI DSS</li>
                                            </ul>
                                        </button>
                                    </form>
                                    <div class="rounded-2xl border border-dashed border-slate/30 p-5 flex flex-col justify-center text-center text-slate gap-1">
                                        <span class="material-symbols-outlined text-3xl text-slate/60 mx-auto">credit_card</span>
                                        <p class="font-semibold mt-2">Stripe / PayPal</p>
                                        <p class="text-sm">Đang phát triển. Vui lòng chọn VNPAY để tiếp tục.</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-green-50 border border-green-200 rounded-2xl p-4 text-green-700 text-sm">
                                Cảm ơn bạn! Đơn hàng đã được xác nhận. Bạn có thể xem chi tiết trong mục “Đơn đặt của tôi”.
                            </div>
                        <?php endif; ?>
                    </section>

                    <aside class="bg-white rounded-3xl shadow-card p-6 md:p-8 space-y-6">
                        <div>
                            <p class="text-xs uppercase tracking-[0.4em] text-slate">Hóa đơn</p>
                            <h3 class="text-2xl font-semibold mt-2">Tóm tắt</h3>
                        </div>
                        <div class="space-y-4 text-sm text-slate">
                            <div class="flex justify-between">
                                <span>Giá thuê (<?php echo $days; ?> ngày)</span>
                                <span><?php echo number_format($booking['total_price']); ?> VNĐ</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Phí dịch vụ</span>
                                <span>0 VNĐ</span>
                            </div>
                            <hr>
                            <div class="flex justify-between font-semibold text-lg">
                                <span>Tổng cộng</span>
                                <span class="text-primary"><?php echo number_format($booking['total_price']); ?> VNĐ</span>
                            </div>
                        </div>

                        <div class="bg-accent rounded-2xl p-4 text-sm text-slate space-y-2">
                            <p class="font-semibold text-primary mb-1">Chính sách</p>
                            <p>- Hủy trong 24h đầu: miễn phí.</p>
                            <p>- Sau 24h: phí 20% tổng số tiền.</p>
                            <p>- Mang theo CMND/CCCD và GPLX khi nhận xe.</p>
                        </div>

                        <div class="space-y-3">
                            <a href="my-bookings.php" class="w-full inline-flex justify-center items-center gap-2 rounded-2xl border border-slate/30 py-3 font-semibold text-slate hover:bg-slate/5 transition">
                                <span class="material-symbols-outlined text-base">assignment</span>
                                Xem đơn đặt của tôi
                            </a>
                            <a href="../index.php" class="w-full inline-flex justify-center items-center gap-2 rounded-2xl border border-slate/30 py-3 font-semibold text-slate hover:bg-slate/5 transition">
                                <span class="material-symbols-outlined text-base">home</span>
                                Về trang chủ
                            </a>
                        </div>

                        <?php if ($payment_status): ?>
                            <div class="text-xs text-slate/70">
                                * Trạng thái thanh toán: <?php echo htmlspecialchars($payment_status); ?><?php echo $payment_method ? ' • ' . htmlspecialchars($payment_method) : ''; ?>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </main>

        <?php include '../includes/footer.php'; ?>
    </div>
</body>
</html>

