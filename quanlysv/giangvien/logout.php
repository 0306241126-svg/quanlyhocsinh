<?php
// Bắt đầu hoặc tiếp tục session hiện tại
session_start();

// Hủy tất cả các biến trong session
$_SESSION = array();

// Nếu bạn muốn hủy session hoàn toàn, bạn cũng nên xóa cookie session.
// Lưu ý: Điều này sẽ phá hủy session, chứ không chỉ dữ liệu session!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Cuối cùng, hủy session
session_destroy();

// Chuyển hướng người dùng về trang đăng nhập
header("Location: logingv.php");
exit();
?>