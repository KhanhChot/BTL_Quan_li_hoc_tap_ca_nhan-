<?php
/**
 * FILE CẤU HÌNH KẾT NỐI DATABASE
 * 
 * File này chứa thông tin kết nối MySQL
 * Hỗ trợ cả XAMPP (local) và môi trường khác
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '181204');
define('DB_NAME', 'study_management');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection()
{
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            throw new Exception("Kết nối database thất bại: " . $conn->connect_error);
        }

        $conn->set_charset(DB_CHARSET);

        return $conn;
    } catch (Exception $e) {
        die("Lỗi: " . $e->getMessage() . "<br>Vui lòng kiểm tra: <br>1. XAMPP MySQL đã chạy chưa<br>2. Database 'study_management' đã tạo chưa<br>3. Thông tin kết nối trong config/database.php");
    }
}

$conn = getDBConnection();
?>