<?php
/**
 * Trang test thanh toán VNPAY
 * Dùng để test payment gateway mà không cần tạo booking thực
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test VNPAY Payment</title>
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
        .info-box {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        .info-box h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        .info-box p {
            margin-bottom: 0.5rem;
            color: #666;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .note {
            background: #fff3cd;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            border-left: 4px solid #ffc107;
        }
        .note strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test VNPAY Payment Gateway</h1>
        
        <div class="info-box">
            <h3>Thông tin VNPAY Sandbox</h3>
            <p><strong>Môi trường:</strong> Sandbox (Test)</p>
            <p><strong>TMN Code:</strong> DEMO</p>
            <p><strong>Hash Secret:</strong> DEMOSECRETKEY</p>
            <p><strong>URL:</strong> https://sandbox.vnpayment.vn/</p>
        </div>
        
        <form action="vnpay-test-process.php" method="POST">
            <div class="form-group">
                <label for="amount">Số tiền (VNĐ)</label>
                <input type="number" id="amount" name="amount" value="100000" min="10000" required>
            </div>
            
            <div class="form-group">
                <label for="order_info">Nội dung thanh toán</label>
                <input type="text" id="order_info" name="order_info" value="Test thanh toan VNPAY" required>
            </div>
            
            <button type="submit">Thanh toán thử nghiệm</button>
        </form>
        
        <div class="note">
            <strong>Lưu ý:</strong> Đây là môi trường test. Bạn có thể sử dụng thông tin thẻ test từ VNPAY để thực hiện thanh toán thử nghiệm. Không có tiền thật được giao dịch.
        </div>
        
        <div class="info-box" style="margin-top: 1.5rem;">
            <h3>Thẻ test VNPAY</h3>
            <p><strong>Ngân hàng:</strong> NCB</p>
            <p><strong>Số thẻ:</strong> 9704198526191432198</p>
            <p><strong>Tên chủ thẻ:</strong> NGUYEN VAN A</p>
            <p><strong>Ngày phát hành:</strong> 07/15</p>
            <p><strong>Mật khẩu OTP:</strong> 123456</p>
        </div>
    </div>
</body>
</html>
