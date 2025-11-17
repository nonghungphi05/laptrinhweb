<?php
/**
 * Trang lịch sử thanh toán
 */
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

requireLogin();

$base_path = getBasePath();
$user_id = $_SESSION['user_id'];

$status_filter = $_GET['status'] ?? 'all';
$allowed_statuses = ['pending', 'completed', 'failed'];
$is_filtered = in_array($status_filter, $allowed_statuses, true);

$sql = "SELECT payments.id, payments.amount, payments.payment_method, payments.transaction_id, payments.status, payments.created_at,
               bookings.id AS booking_id, bookings.start_date, bookings.end_date,
               cars.name AS car_name
        FROM payments
        INNER JOIN bookings ON payments.booking_id = bookings.id
        INNER JOIN cars ON bookings.car_id = cars.id
        WHERE bookings.customer_id = ?";

if ($is_filtered) {
    $sql .= " AND payments.status = ?";
}

$sql .= " ORDER BY payments.created_at DESC";

$stmt = $conn->prepare($sql);
if ($is_filtered) {
    $stmt->bind_param("is", $user_id, $status_filter);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
$summary = [
    'total_paid' => 0,
    'completed' => 0,
    'failed' => 0,
    'pending' => 0,
];

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
    if ($row['status'] === 'completed') {
        $summary['total_paid'] += (float) $row['amount'];
        $summary['completed']++;
    } elseif ($row['status'] === 'failed') {
        $summary['failed']++;
    } elseif ($row['status'] === 'pending') {
        $summary['pending']++;
    }
}
$stmt->close();

function getStatusBadgeClasses($status) {
    switch ($status) {
        case 'completed':
            return ['bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', 'Thành công'];
        case 'failed':
            return ['bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', 'Thất bại'];
        default:
            return ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200', 'Đang xử lý'];
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Lịch sử thanh toán - CarRental</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet"/>
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
        <?php include '../includes/header.php'; ?>

        <div class="layout-container flex h-full grow flex-col">
            <div class="px-4 sm:px-6 lg:px-8 py-8">
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <?php
                        $active_page = 'payments';
                        include __DIR__ . '/account-sidebar.php';
                    ?>

                    <main class="lg:col-span-9">
                        <div class="bg-white dark:bg-background-dark/50 p-6 sm:p-8 rounded-xl shadow-lg">
                            <div class="flex flex-col gap-3 mb-8">
                                <h1 class="text-2xl font-bold text-[#181411] dark:text-white">Lịch sử thanh toán</h1>
                                <p class="text-gray-600 dark:text-gray-300">Xem lại tất cả các giao dịch thanh toán cho các chuyến thuê xe của bạn.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-2">
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-background-light dark:bg-background-dark">
                                        <p class="text-xs uppercase text-gray-500 dark:text-gray-400 mb-1">Tổng đã thanh toán</p>
                                        <p class="text-xl font-bold text-[#181411] dark:text-white"><?php echo formatCurrency($summary['total_paid']); ?></p>
                                    </div>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-background-light dark:bg-background-dark">
                                        <p class="text-xs uppercase text-gray-500 dark:text-gray-400 mb-1">Giao dịch thành công</p>
                                        <p class="text-xl font-bold text-green-600 dark:text-green-300"><?php echo $summary['completed']; ?></p>
                                    </div>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-background-light dark:bg-background-dark">
                                        <p class="text-xs uppercase text-gray-500 dark:text-gray-400 mb-1">Giao dịch thất bại</p>
                                        <p class="text-xl font-bold text-red-600 dark:text-red-300"><?php echo $summary['failed']; ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                                <div class="inline-flex flex-wrap gap-3">
                                    <?php
                                        $filters = [
                                            'all' => 'Tất cả',
                                            'completed' => 'Thành công',
                                            'pending' => 'Đang xử lý',
                                            'failed' => 'Thất bại',
                                        ];
                                        foreach ($filters as $key => $label):
                                            $active = $status_filter === $key || ($key === 'all' && !$is_filtered);
                                            $filter_url = '?status=' . $key;
                                    ?>
                                        <a href="<?php echo htmlspecialchars($filter_url); ?>"
                                           class="px-4 py-2 rounded-full text-sm font-semibold border transition-colors <?php echo $active ? 'bg-primary text-white border-primary' : 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                                            <?php echo $label; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Tổng cộng: <?php echo count($transactions); ?> giao dịch
                                </div>
                            </div>

                            <?php if (empty($transactions)): ?>
                                <div class="border border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-10 text-center text-gray-500 dark:text-gray-400">
                                    Không tìm thấy giao dịch nào.
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 rounded-l-lg">Ngày giao dịch</th>
                                                <th scope="col" class="px-6 py-3">Mã đơn</th>
                                                <th scope="col" class="px-6 py-3">Xe</th>
                                                <th scope="col" class="px-6 py-3">Số tiền</th>
                                                <th scope="col" class="px-6 py-3">Phương thức</th>
                                                <th scope="col" class="px-6 py-3">Trạng thái</th>
                                                <th scope="col" class="px-6 py-3 rounded-r-lg">Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $transaction): ?>
                                                <?php [$badgeClass, $statusLabel] = getStatusBadgeClasses($transaction['status']); ?>
                                                <tr class="bg-white dark:bg-background-dark/50 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600/20">
                                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                                    <td class="px-6 py-4">#<?php echo str_pad($transaction['booking_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                    <td class="px-6 py-4">
                                                        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($transaction['car_name']); ?></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            <?php echo date('d/m', strtotime($transaction['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($transaction['end_date'])); ?>
                                                        </p>
                                                    </td>
                                                    <td class="px-6 py-4 font-semibold text-[#181411] dark:text-white"><?php echo formatCurrency($transaction['amount']); ?></td>
                                                    <td class="px-6 py-4"><?php echo htmlspecialchars($transaction['payment_method']); ?></td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                                                            <?php echo $statusLabel; ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                        <?php echo htmlspecialchars($transaction['transaction_id'] ?? ''); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

