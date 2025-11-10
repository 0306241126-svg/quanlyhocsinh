<?php
session_start();

// Bảo vệ trang, chỉ cho phép Quản trị viên truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

$message = '';
$message_type = '';
$sinhvien_info = null;

// Kiểm tra ID sinh viên từ URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "ID sinh viên không hợp lệ.";
    $_SESSION['message_type'] = "error";
    header("Location: danhsachsv.php");
    exit();
}

$sinhvien_id = $_GET['id'];

// Lấy thông tin sinh viên để hiển thị
$stmt_sv = $conn->prepare("SELECT ho_ten, mssv FROM sinhvien WHERE id = ?");
$stmt_sv->bind_param("i", $sinhvien_id);
$stmt_sv->execute();
$result_sv = $stmt_sv->get_result();
if ($result_sv->num_rows === 1) {
    $sinhvien_info = $result_sv->fetch_assoc();
} else {
    $_SESSION['message'] = "Không tìm thấy sinh viên.";
    $_SESSION['message_type'] = "error";
    header("Location: danhsachsv.php");
    exit();
}
$stmt_sv->close();

// Xử lý khi form được gửi đi
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
        // Mật khẩu hợp lệ, tiến hành mã hóa và cập nhật
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu trong bảng `taikhoan`
        $stmt_update = $conn->prepare("UPDATE taikhoan SET password = ? WHERE sv_id = ?");
        $stmt_update->bind_param("si", $hashed_password, $sinhvien_id);

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
    <title>Cập Nhật Mật Khẩu Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo-container"><a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a></div>
            <nav class="main-menu">
                <ul>
                    <li><a href="trangchu.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                </ul>
            </nav>
        </aside>

        <main class="content full-width-content">
            <h1 class="welcome-message">Cập Nhật Mật Khẩu cho Sinh Viên</h1>
            
            <div class="form-container" style="max-width: 600px; margin: 20px auto;">
                <?php if (!empty($message)): ?><div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($sinhvien_info): ?>
                    <div class="info-group"><label>Sinh viên:</label><p><?php echo htmlspecialchars($sinhvien_info['ho_ten']); ?> (MSSV: <?php echo htmlspecialchars($sinhvien_info['mssv']); ?>)</p></div>
                <?php endif; ?>
                <form action="capnhat_matkhau_sv.php?id=<?php echo $sinhvien_id; ?>" method="POST">
                    <div class="form-group"><label for="new_password">Mật khẩu mới</label><input type="password" id="new_password" name="new_password" required></div>
                    <div class="form-group"><label for="confirm_password">Xác nhận mật khẩu mới</label><input type="password" id="confirm_password" name="confirm_password" required></div>
                    <input type="submit" value="Cập Nhật Mật Khẩu" class="btn form-submit-btn">
                </form>
                <div style="text-align: center; margin-top: 20px;"><a href="danhsachsv.php" class="action-btn" style="background-color: #6c757d; display: inline-block; text-decoration: none;"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a></div>
            </div>
        </main>
    </div>
</body>
</html>