<?php
/**
 * File này chứa thông tin kết nối đến cơ sở dữ liệu.
 * Các file khác sẽ gọi file này để sử dụng biến $conn.
 */

// Thông tin kết nối cho XAMPP mặc định
$servername = "localhost";    // Hoặc "127.0.0.1"
$username = "root";           // Tên người dùng mặc định của MySQL trong XAMPP
$password = "";               // Mật khẩu mặc định của MySQL trong XAMPP là rỗng
$dbname = "my_db";      // Tên cơ sở dữ liệu bạn đã tạo

// Báo cáo lỗi MySQLi ở chế độ nghiêm ngặt (strict mode)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Tạo kết nối bằng MySQLi (theo hướng đối tượng)
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // Nếu kết nối thất bại, dừng chương trình và hiển thị lỗi
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập bảng mã ký tự cho kết nối để hiển thị tiếng Việt đúng
$conn->set_charset("utf8mb4");
?>