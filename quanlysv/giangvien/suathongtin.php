<?php
session_start(); // Di chuyển session_start lên đầu

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'GiangVien') {
    header("Location: logingv.php");
    exit();
}

include("../connect_db.php");

$giangvien_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $ngay_sinh = !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null;
    $gioi_tinh = $_POST['gioi_tinh'];
    $sdt = $_POST['sdt'];
    $dia_chi = $_POST['dia_chi'];
    $avatar_path = $_POST['current_avatar']; // Giữ lại ảnh cũ nếu không có ảnh mới

    // Xử lý upload ảnh đại diện mới
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $upload_dir = '../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = 'gv_' . $giangvien_id . '_' . time() . '_' . basename($_FILES['avatar']['name']);
        $target_file = $upload_dir . $file_name;

        // Kiểm tra định dạng ảnh
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar_path = $target_file;
            } else {
                $message = "Lỗi khi tải ảnh lên.";
                $message_type = "error";
            }
        } else {
            $message = "Chỉ cho phép tải lên file ảnh (JPG, JPEG, PNG, GIF).";
            $message_type = "error";
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("UPDATE giangvien SET ngay_sinh = ?, gioi_tinh = ?, sdt = ?, dia_chi = ?, avatar = ? WHERE ma_gv = ?");
        $stmt->bind_param("sssssi", $ngay_sinh, $gioi_tinh, $sdt, $dia_chi, $avatar_path, $giangvien_id);
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

// Lấy thông tin hiện tại của giảng viên
$stmt_info = $conn->prepare("SELECT * FROM giangvien WHERE ma_gv = ?");
$stmt_info->bind_param("i", $giangvien_id);
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
    <title>Cập Nhật Thông Tin</title>
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
        <div class="sidebar-logo-container"><a href="trangchu.php"><img src="../img/ChatGPT Image 21_18_32 16 thg 10, 2025.png" alt="Logo"></a></div>
      <nav class="main-menu">
        <ul>
            <li class="active"><a href="trangchugv.php"><i class="fas fa-home"></i>Trang chủ</a></li>
            <li><a href="phanconggv.php"><i class="fa-solid fa-chalkboard-user"></i>Danh sách giảng dạy</a></li>
            <li><a href="nhapdiem.php"><i class="fas fa-marker"></i>Nhập điểm</a></li>
            <li><a href="thongbao.php"><i class="fas fa-bell"></i>Thông báo</a></li>
            <li><a href="danhsach_sv.php"><i class="fas fa-users"></i>Danh sách sinh viên</a></li>
            <li><a href="suathongtin.php"><i class="fas fa-user-edit"></i>Cập nhật thông tin</a></li>
        </ul>
      </nav>
    </aside>

    <main class="content full-width-content">
        <h1 class="welcome-message">Cập Nhật Thông Tin Cá Nhân</h1>

        <div class="form-container" style="max-width: 800px; margin: 20px auto;">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="suathongtin.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_info" value="1">
                <input type="hidden" name="current_avatar" value="<?php echo htmlspecialchars($user_info['avatar']); ?>">

                <div style="text-align: center;">
                    <img src="<?php echo !empty($user_info['avatar']) ? htmlspecialchars($user_info['avatar']) : '../img/default_avatar.png'; ?>" alt="Avatar" class="avatar-preview" id="avatar-preview">
                </div>

                <div class="form-group">
                    <label for="ho_ten">Họ và tên (Không thể thay đổi)</label>
                    <input type="text" id="ho_ten" value="<?php echo htmlspecialchars($user_info['ho_ten']); ?>" readonly>
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
                <div class="form-group">
                    <label for="avatar">Thay đổi ảnh đại diện</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewImage(event)">
                </div>

                <button type="submit" class="btn form-submit-btn"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
            </form>
        </div>
    </main>
</div>
<script>
function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('avatar-preview');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>