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

$location_labels = [
    'hcm' => 'TP. Hồ Chí Minh',
    'hanoi' => 'Hà Nội',
    'danang' => 'Đà Nẵng',
    'cantho' => 'Cần Thơ',
    'nhatrang' => 'Nha Trang',
    'dalat' => 'Đà Lạt',
    'phuquoc' => 'Phú Quốc'
];

$error = '';
$success = '';

// Xử lý đặt xe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $pickup_time = $_POST['pickup_time'] ?? '17:00';
    $return_time = $_POST['return_time'] ?? '17:00';
    $pickup_type = $_POST['pickup_type'] ?? 'self';
    $pickup_location = $_POST['pickup_location'] ?? '';
    
    if (empty($start_date) || empty($end_date)) {
        $error = 'Vui lòng chọn ngày bắt đầu và kết thúc';
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = 'Ngày bắt đầu phải từ hôm nay trở đi';
    } elseif (strtotime($end_date) <= strtotime($start_date)) {
        $error = 'Ngày trả xe phải sau ngày nhận xe ít nhất 1 ngày';
    } elseif ($pickup_type === 'delivery' && empty($pickup_location)) {
        $error = 'Vui lòng nhập địa chỉ giao xe';
    } elseif (strtotime($start_date . ' ' . $pickup_time) <= time()) {
        $error = 'Giờ nhận xe phải lớn hơn thời điểm hiện tại';
    } else {
        // Kiểm tra trùng lịch (overlap detection)
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
            
            // Nếu giao xe tận nơi, cộng thêm phí
            $delivery_fee = 0;
            if ($pickup_type === 'delivery') {
                $delivery_fee = 100000; // Phí giao xe 100k
                $total_price += $delivery_fee;
            }
            
            // Tạo booking
            $stmt = $conn->prepare("INSERT INTO bookings (car_id, customer_id, start_date, pickup_time, end_date, return_time, pickup_location, pickup_type, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iissssssd", $car_id, $user_id, $start_date, $pickup_time, $end_date, $return_time, $pickup_location, $pickup_type, $total_price);
            
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

// Địa chỉ nhận xe mặc định từ chủ xe
$default_pickup_address = $car['pickup_address'] ?: ($location_labels[$car['location']] ?? $car['location']);

// Lấy danh sách các ngày đã được đặt (confirmed hoặc pending)
$booked_stmt = $conn->prepare("SELECT start_date, end_date, status FROM bookings 
    WHERE car_id = ? AND status IN ('confirmed', 'pending')
    ORDER BY start_date ASC");
$booked_stmt->bind_param("i", $car_id);
$booked_stmt->execute();
$booked_result = $booked_stmt->get_result();
$booked_ranges = [];
while ($row = $booked_result->fetch_assoc()) {
    $booked_ranges[] = [
        'start' => $row['start_date'],
        'end' => $row['end_date'],
        'status' => $row['status']
    ];
}

// Lấy danh sách địa chỉ đã lưu của user
$user_addresses = [];
$addr_stmt = $conn->prepare("SELECT id, label, recipient_name, phone, address_line, district, city, province, is_default 
    FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addr_stmt->bind_param("i", $user_id);
$addr_stmt->execute();
$addr_result = $addr_stmt->get_result();
while ($row = $addr_result->fetch_assoc()) {
    // Tạo địa chỉ đầy đủ
    $full_address_parts = array_filter([
        $row['address_line'],
        $row['district'],
        $row['city'],
        $row['province']
    ]);
    $row['full_address'] = implode(', ', $full_address_parts);
    $user_addresses[] = $row;
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
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
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
                    <div class="rounded-2xl border border-red-200 bg-red-50 text-red-700 px-4 py-3">
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
                                <p><strong>Giá thuê:</strong> <?php echo number_format($car['price_per_day']); ?> VNĐ/ngày</p>
                                <p><strong>Khu vực:</strong> <?php echo htmlspecialchars($location_labels[$car['location']] ?? $car['location']); ?></p>
                            </div>
                        </div>
                        <div class="border border-[#eee1d4] rounded-2xl p-5 bg-[#fffaf4] text-sm text-slate space-y-2">
                            <p class="text-xs uppercase tracking-[0.4em]">Lưu ý</p>
                            <p>• Vui lòng mang theo CMND/CCCD và GPLX khi nhận xe.</p>
                            <p>• <strong>Thuê tối thiểu 1 ngày</strong> (trả xe ngày hôm sau).</p>
                            <p>• Nhận xe: 17:00 - 22:00 | Trả xe: 06:00 - 22:00</p>
                            <p>• Hủy miễn phí trong 24h đầu; sau đó phí 20% tổng tiền.</p>
                            <p>• Phí giao xe tận nơi: 100.000 VNĐ.</p>
                        </div>
                    </div>

                        <form method="POST" id="bookingForm" class="space-y-6">
                            <!-- Thông báo trùng lịch -->
                            <div id="dateConflictWarning" class="hidden rounded-2xl border border-red-300 bg-red-50 text-red-700 px-4 py-3 flex items-center gap-3">
                                <span class="material-symbols-outlined text-red-500">warning</span>
                                <div>
                                    <p class="font-semibold">Xe đã có người đặt trong khoảng thời gian này!</p>
                                    <p class="text-sm" id="conflictDetails"></p>
                                </div>
                            </div>

                            <!-- Ngày và giờ -->
                            <div class="grid gap-4 md:grid-cols-2">
                            <div class="p-4 border border-[#eee1d4] rounded-2xl space-y-4">
                                <p class="font-semibold text-sm flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">login</span>
                                    Nhận xe
                                </p>
                                <div>
                                    <label class="text-sm text-slate block mb-2" for="start_date">Ngày nhận xe *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">calendar_month</span>
                                        <input type="date" id="start_date" name="start_date"
                                               class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary"
                                               min="<?php echo date('Y-m-d'); ?>"
                                               value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm text-slate block mb-2" for="pickup_time">Giờ nhận xe *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">schedule</span>
                                        <select id="pickup_time" name="pickup_time"
                                                class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary appearance-none">
                                            <?php for ($h = 17; $h <= 22; $h++): ?>
                                                <?php $time = sprintf('%02d:00', $h); ?>
                                                <option value="<?php echo $time; ?>" <?php echo ($_POST['pickup_time'] ?? '17:00') === $time ? 'selected' : ''; ?>>
                                                    <?php echo $time; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <p class="text-xs text-slate mt-1">Nhận xe từ 17:00 - 22:00</p>
                                    <p id="pickup_time_warning" class="text-xs text-red-600 mt-1 hidden">Không còn khung giờ hợp lệ hôm nay, vui lòng chọn ngày khác.</p>
                                </div>
                            </div>
                            <div class="p-4 border border-[#eee1d4] rounded-2xl space-y-4">
                                <p class="font-semibold text-sm flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">logout</span>
                                    Trả xe
                                </p>
                                <div>
                                    <label class="text-sm text-slate block mb-2" for="end_date">Ngày trả xe *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">event</span>
                                        <input type="date" id="end_date" name="end_date"
                                               class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary"
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                               value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm text-slate block mb-2" for="return_time">Giờ trả xe *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">schedule</span>
                                        <select id="return_time" name="return_time"
                                                class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary appearance-none">
                                            <?php for ($h = 6; $h <= 22; $h++): ?>
                                                <?php $time = sprintf('%02d:00', $h); ?>
                                                <option value="<?php echo $time; ?>" <?php echo ($_POST['return_time'] ?? '17:00') === $time ? 'selected' : ''; ?>>
                                                    <?php echo $time; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <p class="text-xs text-slate mt-1">Trả xe từ 06:00 - 22:00</p>
                                </div>
                            </div>
                        </div>

                        <!-- Địa điểm nhận xe -->
                        <div class="p-5 border border-[#eee1d4] rounded-2xl space-y-4">
                            <p class="font-semibold text-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">location_on</span>
                                Hình thức nhận xe
                            </p>
                            
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="flex items-start gap-3 p-4 border border-[#e5d5c7] rounded-xl cursor-pointer hover:border-primary transition-colors pickup-option" data-type="self">
                                    <input type="radio" name="pickup_type" value="self" class="mt-1 text-primary focus:ring-primary" 
                                           <?php echo ($_POST['pickup_type'] ?? 'self') === 'self' ? 'checked' : ''; ?>>
                                    <div class="flex-1">
                                        <p class="font-semibold">Tự đến lấy xe</p>
                                        <p class="text-sm text-slate mt-1">
                                            <span class="material-symbols-outlined text-sm align-middle">pin_drop</span>
                                            <?php echo htmlspecialchars($default_pickup_address); ?>
                                        </p>
                                        <p class="text-xs text-green-600 mt-1">Miễn phí</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-start gap-3 p-4 border border-[#e5d5c7] rounded-xl cursor-pointer hover:border-primary transition-colors pickup-option" data-type="delivery">
                                    <input type="radio" name="pickup_type" value="delivery" class="mt-1 text-primary focus:ring-primary"
                                           <?php echo ($_POST['pickup_type'] ?? '') === 'delivery' ? 'checked' : ''; ?>>
                                    <div class="flex-1">
                                        <p class="font-semibold">Giao xe tận nơi</p>
                                        <p class="text-sm text-slate mt-1">Chúng tôi sẽ giao xe đến địa chỉ của bạn</p>
                                        <p class="text-xs text-primary mt-1">+ 100.000 VNĐ</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Địa chỉ giao xe (hiển thị khi chọn giao tận nơi) -->
                            <div id="delivery_address_section" class="<?php echo ($_POST['pickup_type'] ?? 'self') === 'delivery' ? '' : 'hidden'; ?> space-y-4">
                                <?php if (!empty($user_addresses)): ?>
                                <!-- Chọn từ địa chỉ đã lưu -->
                                <div>
                                    <label class="text-sm text-slate block mb-2">Chọn địa chỉ đã lưu</label>
                                    <div class="space-y-2">
                                        <?php foreach ($user_addresses as $addr): ?>
                                            <label class="flex items-start gap-3 p-3 border border-[#e5d5c7] rounded-xl cursor-pointer hover:border-primary transition-colors saved-address-option <?php echo $addr['is_default'] ? 'border-primary bg-primary/5' : ''; ?>">
                                                <input type="radio" name="saved_address" value="<?php echo (int)$addr['id']; ?>" 
                                                       data-address="<?php echo htmlspecialchars($addr['full_address']); ?>"
                                                       class="mt-1 text-primary focus:ring-primary"
                                                       <?php echo $addr['is_default'] ? 'checked' : ''; ?>>
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-semibold text-sm"><?php echo htmlspecialchars($addr['label']); ?></span>
                                                        <?php if ($addr['is_default']): ?>
                                                            <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full">Mặc định</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-xs text-slate mt-1"><?php echo htmlspecialchars($addr['recipient_name']); ?> • <?php echo htmlspecialchars($addr['phone']); ?></p>
                                                    <p class="text-xs text-slate"><?php echo htmlspecialchars($addr['full_address']); ?></p>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                        
                                        <!-- Option nhập địa chỉ mới -->
                                        <label class="flex items-start gap-3 p-3 border border-[#e5d5c7] rounded-xl cursor-pointer hover:border-primary transition-colors saved-address-option" id="new_address_option">
                                            <input type="radio" name="saved_address" value="new" class="mt-1 text-primary focus:ring-primary">
                                            <div class="flex-1">
                                                <span class="font-semibold text-sm flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-base">add_location</span>
                                                    Nhập địa chỉ mới
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Input địa chỉ mới (ẩn khi chọn địa chỉ đã lưu) -->
                                <div id="new_address_input" class="hidden">
                                    <label class="text-sm text-slate block mb-2" for="pickup_location">Địa chỉ giao xe mới *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">home</span>
                                        <input type="text" id="pickup_location_new" 
                                               class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary"
                                               placeholder="Nhập địa chỉ đầy đủ...">
                                    </div>
                                    <p class="text-xs text-slate mt-2 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">info</span>
                                        <a href="addresses.php" class="text-primary hover:underline">Quản lý địa chỉ</a> để lưu địa chỉ thường dùng
                                    </p>
                                </div>
                                
                                <!-- Hidden input để submit -->
                                <input type="hidden" id="pickup_location" name="pickup_location" value="<?php echo htmlspecialchars($_POST['pickup_location'] ?? ($user_addresses[0]['full_address'] ?? '')); ?>">
                                
                                <?php else: ?>
                                <!-- Không có địa chỉ đã lưu - chỉ hiện input -->
                                <div>
                                    <label class="text-sm text-slate block mb-2" for="pickup_location">Địa chỉ giao xe *</label>
                                    <div class="relative">
                                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate">home</span>
                                        <input type="text" id="pickup_location" name="pickup_location"
                                               class="w-full rounded-xl border border-[#e5d5c7] pl-12 py-3 focus:ring-primary focus:border-primary"
                                               placeholder="Nhập địa chỉ đầy đủ..."
                                               value="<?php echo htmlspecialchars($_POST['pickup_location'] ?? ''); ?>">
                                    </div>
                                    <p class="text-xs text-slate mt-2 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">info</span>
                                        <a href="addresses.php" class="text-primary hover:underline">Thêm địa chỉ mới</a> để tiện đặt xe lần sau
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Hiển thị địa chỉ chủ xe khi chọn tự đến -->
                            <div id="self_pickup_info" class="<?php echo ($_POST['pickup_type'] ?? 'self') === 'self' ? '' : 'hidden'; ?>">
                                <div class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                                    <span class="material-symbols-outlined text-blue-600">info</span>
                                    <div class="text-sm">
                                        <p class="font-semibold text-blue-800">Địa chỉ nhận xe:</p>
                                        <p class="text-blue-700"><?php echo htmlspecialchars($default_pickup_address); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 border border-[#eee1d4] rounded-2xl bg-white">
                            <p class="text-xs uppercase text-slate tracking-[0.4em] mb-4">Tổng tiền dự kiến</p>
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-4xl font-bold text-primary"><span id="totalPrice">0</span> VNĐ</p>
                                    <p class="text-slate mt-1">Số ngày: <span id="days">0</span> ngày</p>
                                    <p class="text-sm text-slate" id="deliveryFeeText"></p>
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
        const deliveryFee = 100000;
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const pickupTypeInputs = document.querySelectorAll('input[name="pickup_type"]');
        const deliveryAddressSection = document.getElementById('delivery_address_section');
        const selfPickupInfo = document.getElementById('self_pickup_info');
        const deliveryFeeText = document.getElementById('deliveryFeeText');
        const pickupTimeSelect = document.getElementById('pickup_time');
        const pickupTimeWarning = document.getElementById('pickup_time_warning');
        const dateConflictWarning = document.getElementById('dateConflictWarning');
        const conflictDetails = document.getElementById('conflictDetails');
        const submitBtn = document.querySelector('button[type="submit"]');
        
        // Danh sách các khoảng ngày đã được đặt
        const bookedRanges = <?php echo json_encode($booked_ranges); ?>;
        
        // Hàm format ngày sang DD/MM/YYYY
        function formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('vi-VN');
        }
        
        // Kiểm tra xem 2 khoảng ngày có overlap không
        function checkDateConflict(startDate, endDate) {
            if (!startDate || !endDate) return null;
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            for (const range of bookedRanges) {
                const bookedStart = new Date(range.start);
                const bookedEnd = new Date(range.end);
                
                // Kiểm tra overlap: start1 <= end2 AND end1 >= start2
                if (start <= bookedEnd && end >= bookedStart) {
                    return {
                        start: range.start,
                        end: range.end,
                        status: range.status === 'confirmed' ? 'đã xác nhận' : 'đang chờ xử lý'
                    };
                }
            }
            return null;
        }
        
        // Hiển thị hoặc ẩn cảnh báo trùng lịch
        function updateConflictWarning() {
            const conflict = checkDateConflict(startDateInput.value, endDateInput.value);
            
            if (conflict) {
                conflictDetails.textContent = `Đã có đơn đặt (${conflict.status}) từ ${formatDate(conflict.start)} đến ${formatDate(conflict.end)}. Vui lòng chọn ngày khác.`;
                dateConflictWarning.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                dateConflictWarning.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        function calculateTotal() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            const isDelivery = document.querySelector('input[name="pickup_type"]:checked')?.value === 'delivery';
            
            if (startDate && endDate && endDate > startDate) {
                let days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                
                let total = days * pricePerDay;
                
                if (isDelivery) {
                    total += deliveryFee;
                    deliveryFeeText.textContent = '+ Phí giao xe: 100.000 VNĐ';
                } else {
                    deliveryFeeText.textContent = '';
                }
                
                document.getElementById('days').textContent = days;
                document.getElementById('totalPrice').textContent = total.toLocaleString('vi-VN');
            } else {
                document.getElementById('days').textContent = '0';
                document.getElementById('totalPrice').textContent = '0';
                deliveryFeeText.textContent = '';
            }
        }

        function toggleDeliverySection() {
            const isDelivery = document.querySelector('input[name="pickup_type"]:checked')?.value === 'delivery';
            
            if (isDelivery) {
                deliveryAddressSection.classList.remove('hidden');
                selfPickupInfo.classList.add('hidden');
                document.getElementById('pickup_location').required = true;
            } else {
                deliveryAddressSection.classList.add('hidden');
                selfPickupInfo.classList.remove('hidden');
                document.getElementById('pickup_location').required = false;
            }
            
            calculateTotal();
        }

    function updatePickupTimeOptions() {
        if (!pickupTimeSelect || !startDateInput) return;
        const selectedDate = startDateInput.value;
        const todayStr = new Date().toISOString().split('T')[0];
        const options = Array.from(pickupTimeSelect.options);
        options.forEach(option => option.disabled = false);

        if (selectedDate && selectedDate === todayStr) {
            const now = new Date();
            const currentMinutes = now.getHours() * 60 + now.getMinutes();
            let firstAvailable = null;
            let availableCount = 0;

            options.forEach(option => {
                const [hour, minute] = option.value.split(':');
                const optionMinutes = parseInt(hour, 10) * 60 + parseInt(minute, 10);
                const disable = optionMinutes <= currentMinutes;
                option.disabled = disable;
                if (!disable) {
                    availableCount++;
                    if (!firstAvailable) {
                        firstAvailable = option.value;
                    }
                }
            });

            if (availableCount > 0) {
                pickupTimeSelect.value = firstAvailable;
                if (pickupTimeWarning) pickupTimeWarning.classList.add('hidden');
            } else {
                pickupTimeSelect.selectedIndex = -1;
                if (pickupTimeWarning) pickupTimeWarning.classList.remove('hidden');
            }
        } else {
            if (pickupTimeWarning) pickupTimeWarning.classList.add('hidden');
            if (!pickupTimeSelect.value && options.length > 0) {
                pickupTimeSelect.value = options[0].value;
            }
        }
    }

        startDateInput.addEventListener('change', function() {
            // Cập nhật min của end_date = start_date + 1 ngày
            if (this.value) {
                const startDate = new Date(this.value);
                startDate.setDate(startDate.getDate() + 1);
                const minEndDate = startDate.toISOString().split('T')[0];
                endDateInput.min = minEndDate;
                
                // Nếu end_date hiện tại <= start_date, reset end_date
                if (endDateInput.value && endDateInput.value <= this.value) {
                    endDateInput.value = minEndDate;
                }
            }
            updatePickupTimeOptions();
            calculateTotal();
            updateConflictWarning();
        });
        endDateInput.addEventListener('change', function() {
            calculateTotal();
            updateConflictWarning();
        });
        pickupTypeInputs.forEach(input => {
            input.addEventListener('change', toggleDeliverySection);
        });

        // Highlight selected option
        document.querySelectorAll('.pickup-option').forEach(option => {
            option.querySelector('input').addEventListener('change', function() {
                document.querySelectorAll('.pickup-option').forEach(o => {
                    o.classList.remove('border-primary', 'bg-primary/5');
                    o.classList.add('border-[#e5d5c7]');
                });
                if (this.checked) {
                    option.classList.add('border-primary', 'bg-primary/5');
                    option.classList.remove('border-[#e5d5c7]');
                }
            });
            
            // Initial state
            if (option.querySelector('input').checked) {
                option.classList.add('border-primary', 'bg-primary/5');
                option.classList.remove('border-[#e5d5c7]');
            }
        });

        // Xử lý chọn địa chỉ giao xe
        const savedAddressInputs = document.querySelectorAll('input[name="saved_address"]');
        const newAddressInput = document.getElementById('new_address_input');
        const pickupLocationHidden = document.getElementById('pickup_location');
        const pickupLocationNew = document.getElementById('pickup_location_new');
        
        function updatePickupLocation() {
            const selectedRadio = document.querySelector('input[name="saved_address"]:checked');
            if (!selectedRadio) return;
            
            if (selectedRadio.value === 'new') {
                // Hiện input nhập địa chỉ mới
                if (newAddressInput) newAddressInput.classList.remove('hidden');
                if (pickupLocationNew && pickupLocationHidden) {
                    pickupLocationHidden.value = pickupLocationNew.value;
                }
            } else {
                // Ẩn input và dùng địa chỉ đã chọn
                if (newAddressInput) newAddressInput.classList.add('hidden');
                if (pickupLocationHidden) {
                    pickupLocationHidden.value = selectedRadio.dataset.address || '';
                }
            }
            
            // Highlight selected address
            document.querySelectorAll('.saved-address-option').forEach(opt => {
                opt.classList.remove('border-primary', 'bg-primary/5');
                opt.classList.add('border-[#e5d5c7]');
            });
            if (selectedRadio.closest('.saved-address-option')) {
                selectedRadio.closest('.saved-address-option').classList.add('border-primary', 'bg-primary/5');
                selectedRadio.closest('.saved-address-option').classList.remove('border-[#e5d5c7]');
            }
        }
        
        // Event listeners cho chọn địa chỉ
        savedAddressInputs.forEach(input => {
            input.addEventListener('change', updatePickupLocation);
        });
        
        // Cập nhật hidden input khi nhập địa chỉ mới
        if (pickupLocationNew) {
            pickupLocationNew.addEventListener('input', function() {
                if (pickupLocationHidden) {
                    pickupLocationHidden.value = this.value;
                }
            });
        }
        
        // Initial setup for saved addresses
        updatePickupLocation();

        // Initial calculation
        calculateTotal();
        updatePickupTimeOptions();
        updateConflictWarning();
    </script>
</body>
</html>
