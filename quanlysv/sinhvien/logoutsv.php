<?php
// Bắt đầu hoặc tiếp tục session hiện tại
session_start();

// Xóa tất cả các biến trong session
// Điều này đảm bảo rằng không còn thông tin đăng nhập nào được lưu trữ
$_SESSION = array();

// Nếu hệ thống sử dụng cookie để quản lý session,
// thì cần phải xóa cả cookie session.
// Lưu ý: Điều này sẽ phá hủy session, và không chỉ dữ liệu session!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Cuối cùng, hủy session.
session_destroy();

// Chuyển hướng người dùng về trang đăng nhập của sinh viên
header("Location: login_sinhvien.php");
exit(); // Đảm bảo không có mã nào khác được thực thi sau khi chuyển hướng
?>