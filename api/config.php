<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
// Cấu hình VNPay cho dự án thuê xe (môi trường TEST)
// Thông tin do VNPay cung cấp cho merchant sandbox của bạn
$vnp_TmnCode = "E1W34YKD"; // Mã định danh merchant kết nối (Terminal Id)
$vnp_HashSecret = "PNJVXMCKPHW7F3SZQ3GTA338WL8CZ0IG"; // Secret key dùng để tạo checksum

// URL cổng thanh toán VNPay (TEST)
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";

// URL trang nhận kết quả thanh toán (Return URL) trên dự án hiện tại
// Lưu ý: chỉnh lại nếu bạn deploy trên domain khác
$vnp_Returnurl = "http://localhost/laptrinhweb/api/vnpay_return.php";
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
$apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";
//Config input format
//Expire
$startTime = date("YmdHis");
$expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime)));
