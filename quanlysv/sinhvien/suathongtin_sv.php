<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SinhVien') {
    header("Location: login_sinhvien.php");
    exit();
}

include("../connect_db.php");

$sinhvien_id = $_SESSION['user_id']; // Lấy ID sinh viên từ session
$message = '';
$message_type = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $ngay_sinh = !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null;
    $gioi_tinh = $_POST['gioi_tinh'];
    $sdt = $_POST['sdt'];
    $dia_chi = $_POST['dia_chi'];
    // Không xử lý avatar ở đây nữa, avatar_path sẽ không được dùng
    
    if (empty($message)) {
        $stmt = $conn->prepare("UPDATE sinhvien SET ngay_sinh = ?, gioi_tinh = ?, sdt = ?, dia_chi = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $ngay_sinh, $gioi_tinh, $sdt, $dia_chi, $sinhvien_id);
        if ($stmt->execute()) {
            $message = "Cập nhật thông tin thành công!";
            $message_type = "success";
        } else {
            $message = "Lỗi khi cập nhật thông tin: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Lấy thông tin hiện tại của sinh viên
$stmt_info = $conn->prepare("SELECT * FROM sinhvien WHERE id = ?");
$stmt_info->bind_param("i", $sinhvien_id);
$stmt_info->execute();
$result_info = $stmt_info->get_result();
$user_info = $result_info->fetch_assoc();
$stmt_info->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập Nhật Thông Tin Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .avatar-preview { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #ddd; margin-bottom: 15px; }
        .form-group input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo-container"><a href="trangchusv.php"><img src="../img/ChatGPT Image 21_18_32 16 thg 10, 2025.png" alt="Logo"></a></div>
        <nav class="main-menu">
            <ul>
                <li><a href="trangchusv.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                <li><a href="thongbaosv.php"><i class="fas fa-bell"></i>Thông Báo</a></li>
                <li><a href="diemtbkt.php"><i class="fas fa-book-reader"></i>Kết quả học tập</a></li>
                <li class="active"><a href="suathongtin_sv.php"><i class="fas fa-user-edit"></i>Cập nhật Lý Lịch</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer"><a href="logoutsv.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a></div>
    </aside>

    <main class="content full-width-content">
        <h1 class="welcome-message">Cập Nhật Thông Tin Cá Nhân</h1>

        <div class="form-container" style="max-width: 800px; margin: 20px auto;">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="suathongtin_sv.php" method="POST">
                <input type="hidden" name="update_info" value="1">

                <div style="text-align: center;">
                    <img src="<?php echo !empty($user_info['avatar']) ? htmlspecialchars($user_info['avatar']) : '../img/default_avatar.png'; ?>" alt="Avatar" class="avatar-preview" id="avatar-preview">
                </div>

                <div class="form-group">
                    <label for="ho_ten">Họ và tên (Không thể thay đổi)</label>
                    <input type="text" id="ho_ten" value="<?php echo htmlspecialchars($user_info['ho_ten']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="mssv">MSSV (Không thể thay đổi)</label>
                    <input type="text" id="mssv" value="<?php echo htmlspecialchars($user_info['mssv']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="email">Email (Không thể thay đổi)</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="cccd">CCCD (Không thể thay đổi)</label>
                    <input type="text" id="cccd" value="<?php echo htmlspecialchars($user_info['cccd']); ?>" readonly>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ngay_sinh">Ngày sinh</label>
                        <input type="date" id="ngay_sinh" name="ngay_sinh" value="<?php echo htmlspecialchars($user_info['ngay_sinh']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gioi_tinh">Giới tính</label>
                        <select id="gioi_tinh" name="gioi_tinh">
                            <option value="">-- Chọn --</option>
                            <option value="Nam" <?php echo ($user_info['gioi_tinh'] == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                            <option value="Nữ" <?php echo ($user_info['gioi_tinh'] == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sdt">Số điện thoại</label>
                        <input type="tel" id="sdt" name="sdt" value="<?php echo htmlspecialchars($user_info['sdt']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="dia_chi">Địa chỉ</label>
                    <input type="text" id="dia_chi" name="dia_chi" value="<?php echo htmlspecialchars($user_info['dia_chi']); ?>">
                </div>

                <button type="submit" class="btn form-submit-btn"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>