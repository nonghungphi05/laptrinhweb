<?php
/**
 * Xử lý kết quả test VNPAY
 */

$vnp_HashSecret = "DEMOSECRETKEY";

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);

$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$success = false;
$message = '';

if ($secureHash == $vnp_SecureHash) {
    if ($_GET['vnp_ResponseCode'] == '00') {
        $success = true;
        $message = 'Giao dịch test thành công!';
    } else {
        $message = 'Giao dịch test thất bại. Mã lỗi: ' . $_GET['vnp_ResponseCode'];
    }
} else {
    $message = 'Chữ ký không hợp lệ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả test VNPAY</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        .result {
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .info p {
            margin-bottom: 0.5rem;
        }
        .button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 0.5rem;
            margin-top: 1rem;
        }
        .button:hover {
            background: #5568d3;
        }
        pre {
            background: #f4f4f4;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Kết quả test VNPAY</h1>
        
        <div class="result <?php echo $success ? 'success' : 'error'; ?>">
            <h2><?php echo $success ? '✓' : '✗'; ?> <?php echo $message; ?></h2>
        </div>
        
        <?php if ($success): ?>
            <div class="info">
                <p><strong>Mã giao dịch:</strong> <?php echo htmlspecialchars($_GET['vnp_TxnRef'] ?? 'N/A'); ?></p>
                <p><strong>Số tiền:</strong> <?php echo number_format($_GET['vnp_Amount'] / 100); ?> VNĐ</p>
                <p><strong>Ngân hàng:</strong> <?php echo htmlspecialchars($_GET['vnp_BankCode'] ?? 'N/A'); ?></p>
                <p><strong>Nội dung:</strong> <?php echo htmlspecialchars($_GET['vnp_OrderInfo'] ?? 'N/A'); ?></p>
                <p><strong>Thời gian:</strong> <?php echo isset($_GET['vnp_PayDate']) ? $_GET['vnp_PayDate'] : 'N/A'; ?></p>
            </div>
        <?php endif; ?>
        
        <details>
            <summary style="cursor: pointer; margin-bottom: 1rem; font-weight: bold;">Xem dữ liệu trả về từ VNPAY</summary>
            <pre><?php print_r($_GET); ?></pre>
        </details>
        
        <a href="test-vnpay.php" class="button">Test lại</a>
        <a href="../index.php" class="button">Về trang chủ</a>
    </div>
</body>
</html>

