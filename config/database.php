<?php
/**
 * File kết nối database
 * Sử dụng mysqli với prepared statements để bảo mật
 */

// Thông tin cấu hình database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carrental');

// Tạo kết nối
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error . "<br>Vui lòng kiểm tra:<br>1. MySQL đã start chưa?<br>2. Database 'carrental' đã được tạo chưa?<br>3. Thông tin trong config/database.php có đúng không?<br>4. Import file schema.sql vào database carrental");
}

// Thiết lập charset UTF-8
$conn->set_charset("utf8mb4");

/**
 * Hàm thực thi prepared statement
 */
function executeQuery($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    return $result ? $stmt : false;
}

/**
 * Hàm lấy kết quả từ prepared statement
 */
function getResults($stmt) {
    $result = $stmt->get_result();
    return $result;
}

?>


