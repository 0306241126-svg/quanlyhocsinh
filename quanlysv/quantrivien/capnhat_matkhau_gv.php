<?php
session_start();

// Bảo vệ trang
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

$message = '';
$message_type = '';
$giangvien_info = null;
$new_password = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "ID giảng viên không hợp lệ.";
    $_SESSION['message_type'] = "error";
    header("Location: danhsachgv.php");
    exit();
}

$giangvien_id = $_GET['id'];

// Lấy thông tin giảng viên
$stmt_gv = $conn->prepare("SELECT ho_ten, email FROM giangvien WHERE ma_gv = ?");
$stmt_gv->bind_param("i", $giangvien_id);
$stmt_gv->execute();
$result_gv = $stmt_gv->get_result();
if ($result_gv->num_rows === 1) {
    $giangvien_info = $result_gv->fetch_assoc();
} else {
    $_SESSION['message'] = "Không tìm thấy giảng viên.";
    $_SESSION['message_type'] = "error";
    header("Location: danhsachgv.php");
    exit();
}
$stmt_gv->close();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Vui lòng nhập đầy đủ mật khẩu mới và xác nhận mật khẩu.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Mật khẩu mới và mật khẩu xác nhận không khớp.";
        $message_type = "error";
    } else {
        // Mật khẩu hợp lệ, tiến hành cập nhật
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu trong bảng taikhoan
        $stmt_update = $conn->prepare("UPDATE taikhoan SET password = ? WHERE gv_id = ?");
        $stmt_update->bind_param("si", $hashed_password, $giangvien_id);

        if ($stmt_update->execute()) {
            $message = "Cập nhật mật khẩu thành công!";
            $message_type = "success";
        } else {
            $message = "Lỗi khi cập nhật mật khẩu: " . $conn->error;
            $message_type = "error";
        }
        $stmt_update->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập Nhật Mật Khẩu Giảng Viên</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- THANH BÊN (SIDEBAR) -->
        <aside class="sidebar">
            <div class="sidebar-logo-container">
                <a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a>
            </div>
            <nav class="main-menu">
                <ul>
                    <li><a href="trangchu.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                </ul>
            </nav>

        </aside>

        <!-- NỘI DUNG CHÍNH -->
        <main class="content full-width-content">
            <h1 class="welcome-message">Cập Nhật Mật Khẩu cho Giảng Viên</h1>
            
            <div class="form-container" style="max-width: 600px; margin: 20px auto;">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($giangvien_info): ?>
                    <div class="info-group">
                        <label>Giảng viên:</label>
                        <p><?php echo htmlspecialchars($giangvien_info['ho_ten']); ?> (<?php echo htmlspecialchars($giangvien_info['email']); ?>)</p>
                    </div>
                <?php endif; ?>

                <form action="capnhat_matkhau_gv.php?id=<?php echo $giangvien_id; ?>" method="POST">
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu mới</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <input type="submit" value="Cập Nhật Mật Khẩu" class="btn form-submit-btn">
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="danhsachgv.php" class="action-btn" style="background-color: #6c757d; display: inline-block; text-decoration: none;"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
