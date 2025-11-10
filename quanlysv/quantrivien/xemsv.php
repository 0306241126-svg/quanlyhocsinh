<?php
session_start();

// Ngăn trình duyệt cache trang
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kiểm tra nếu người dùng chưa đăng nhập hoặc không phải là Quản trị viên
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

$sinhvien_info = null;
$error_message = '';

// Kiểm tra xem ID sinh viên có được cung cấp không
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "ID sinh viên không hợp lệ.";
} else {
    // Lấy ma_khoa của quản trị viên đang đăng nhập để bảo mật
    $ma_khoa_admin = null;
    if (isset($_SESSION['user_id'])) {
        $qtv_id = $_SESSION['user_id'];
        $stmt_qtv_khoa = $conn->prepare("SELECT ma_khoa FROM quantrivien WHERE ma_qtv = ?");
        $stmt_qtv_khoa->bind_param("i", $qtv_id);
        $stmt_qtv_khoa->execute();
        $result_qtv_khoa = $stmt_qtv_khoa->get_result();
        if ($qtv_info = $result_qtv_khoa->fetch_assoc()) {
            $ma_khoa_admin = $qtv_info['ma_khoa'];
        }
        $stmt_qtv_khoa->close();
    }
    $sinhvien_id = $_GET['id'];

    // Truy vấn thông tin chi tiết của sinh viên
    $stmt = $conn->prepare("SELECT sv.id, sv.mssv, sv.ho_ten, sv.email, sv.cccd, sv.ngay_sinh, sv.gioi_tinh, sv.sdt, sv.dia_chi, sv.avatar, sv.trangthai,
                                   tk.status as account_status,
                                   l.ten_lop
                            FROM sinhvien sv 
                            LEFT JOIN taikhoan tk ON sv.id = tk.sv_id
                            LEFT JOIN lop_sinhvien lsv ON sv.id = lsv.id_sv
                            LEFT JOIN lophoc l ON lsv.ma_lop = l.ma_lop
                            WHERE sv.id = ? AND sv.ma_khoa = ?");
    $stmt->bind_param("ii", $sinhvien_id, $ma_khoa_admin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sinhvien_info = $result->fetch_assoc();
    } else {
        $error_message = "Không tìm thấy thông tin sinh viên.";
    }
    $stmt->close();
}

// Xử lý upload ảnh đại diện nếu có
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar']) && $sinhvien_info) {
    $sinhvien_id = $sinhvien_info['id'];
    $avatar_path = $sinhvien_info['avatar']; // Giữ ảnh cũ nếu không có ảnh mới

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $upload_dir = '../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = 'sv_' . $sinhvien_id . '_' . time() . '_' . basename($_FILES['avatar']['name']);
        $target_file = $upload_dir . $file_name;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar_path = $target_file;
                // Cập nhật đường dẫn ảnh vào CSDL
                $stmt_update_avatar = $conn->prepare("UPDATE sinhvien SET avatar = ? WHERE id = ?");
                $stmt_update_avatar->bind_param("si", $avatar_path, $sinhvien_id);
                $stmt_update_avatar->execute();
                $stmt_update_avatar->close();
                $_SESSION['message'] = "Cập nhật ảnh đại diện thành công!";
                $_SESSION['message_type'] = "success";
                header("Location: xemsv.php?id=" . $sinhvien_id); // Tải lại trang để hiển thị ảnh mới
                exit();
            } else {
                $error_message = "Lỗi khi tải ảnh lên.";
            }
        } else {
            $error_message = "Chỉ cho phép tải lên file ảnh (JPG, JPEG, PNG, GIF).";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Chi Tiết Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 900px;
            margin: 20px auto;
        }
        .info-display-container {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        .info-image-section {
            flex: 1;
            text-align: center;
        }
        .info-details-section {
            flex: 2;
        }
        .info-image-section img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 5px;
            background-color: #fff;
            margin-bottom: 15px;
        }
        .avatar-upload-form {
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            margin-top: 25px;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        .info-group p {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            font-size: 15px;
            margin: 0;
        }
        .action-buttons-container {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .avatar-upload-form input[type="file"] {
            width: 100%;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- THANH BÊN (SIDEBAR) -->
        <aside class="sidebar">
            <div class="sidebar-logo-container">
                <a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a>
            </div>
        <!--Menu trái-->
      <nav class="main-menu">
        <ul>
          <li><a href="trangchu.php"><i class="fas fa-home"></i>Trang chủ</a></li>
          <!-- Thêm các mục menu cho quản trị viên ở đây -->
          <li><a href="danhsachgv.php"><i class="fas fa-chalkboard-teacher"></i>Danh sách giảng viên</a></li>
          <li><a href="danhsachsv.php"><i class="fas fa-user-graduate"></i>Danh sách sinh viên</a></li>
          <li><a href="khoahoc.php"><i class="fas fa-users"></i>Danh sách khóa</a></li>
          <li class="active"><a href="monhoc.php"><i class="fa-solid fa-book-open"></i>Danh sách môn học</a></li>
          <li><a href="danhsachpc.php"><i class="fas fa-list-check"></i>Danh sách phân công</a></li>
          <li><a href="danhsachlop.php"><i class="fas fa-list-alt"></i>Danh sách lớp</a></li>
        </ul>
      </nav>
          
        </aside>

        <!-- NỘI DUNG CHÍNH -->
        <main class="content full-width-content">
            <h1 class="welcome-message">Thông Tin Chi Tiết Sinh Viên</h1>
            
            <div class="form-container">
                <?php if ($error_message): ?>
                    <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif ($sinhvien_info): ?>
                    <div class="info-display-container">
                        <div class="info-image-section">
                            <label>Ảnh đại diện</label>
                            <img src="<?php echo !empty($sinhvien_info['avatar']) ? htmlspecialchars($sinhvien_info['avatar']) : '../img/default_avatar.png'; ?>" alt="Ảnh đại diện">
                            <form action="xemsv.php?id=<?php echo $sinhvien_info['id']; ?>" method="POST" enctype="multipart/form-data" class="avatar-upload-form">
                                <input type="file" name="avatar" id="avatar_upload" accept="image/*" onchange="previewNewAvatar(event)">
                                <button type="submit" name="update_avatar" class="action-btn" style="background-color: #28a745;"><i class="fas fa-upload"></i> Cập nhật ảnh</button>
                            </form>
                            <script>
                                function previewNewAvatar(event) {
                                    var reader = new FileReader(); reader.onload = function(){ var output = document.querySelector('.info-image-section img'); output.src = reader.result; }; reader.readAsDataURL(event.target.files[0]);
                                }
                            </script>
                        </div>
                        <div class="info-details-section">
                            <div class="info-group"><label>Mã số sinh viên</label><p><?php echo htmlspecialchars($sinhvien_info['mssv']); ?></p></div>
                            <div class="info-group"><label>Họ và tên</label><p><?php echo htmlspecialchars($sinhvien_info['ho_ten']); ?></p></div>
                            <div class="info-group"><label>Email</label><p><?php echo htmlspecialchars($sinhvien_info['email']); ?></p></div>
                            <div class="info-group"><label>Căn cước công dân</label><p><?php echo htmlspecialchars($sinhvien_info['cccd']); ?></p></div>
                            <div class="info-group"><label>Ngày sinh</label><p><?php echo !empty($sinhvien_info['ngay_sinh']) ? htmlspecialchars(date('d/m/Y', strtotime($sinhvien_info['ngay_sinh']))) : 'Chưa cập nhật'; ?></p></div>
                            <div class="info-group"><label>Giới tính</label><p><?php echo !empty($sinhvien_info['gioi_tinh']) ? htmlspecialchars($sinhvien_info['gioi_tinh']) : 'Chưa cập nhật'; ?></p></div>
                            <div class="info-group"><label>Số điện thoại</label><p><?php echo !empty($sinhvien_info['sdt']) ? htmlspecialchars($sinhvien_info['sdt']) : 'Chưa cập nhật'; ?></p></div>
                            <div class="info-group"><label>Địa chỉ</label><p><?php echo !empty($sinhvien_info['dia_chi']) ? htmlspecialchars($sinhvien_info['dia_chi']) : 'Chưa cập nhật'; ?></p></div>
                            <div class="info-group"><label>Lớp học</label><p><?php echo !empty($sinhvien_info['ten_lop']) ? htmlspecialchars($sinhvien_info['ten_lop']) : 'Chưa có lớp'; ?></p></div>
                            <div class="info-group"><label>Trạng thái</label><p><?php echo ($sinhvien_info['trangthai'] == 1) ? 'Đang học' : 'Đã nghỉ'; ?></p></div>
                            <div class="info-group"><label>Trạng thái tài khoản</label>
                                <p>
                                    <?php if (isset($sinhvien_info['account_status']) && $sinhvien_info['account_status'] == 'active'): ?>
                                        <span class="status-active">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-locked">Bị khóa</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="action-buttons-container">
                    <?php if ($sinhvien_info): ?>
                        <a href="danhsachsv.php" class="action-btn" style="background-color: #6c757d;"><i class="fas fa-arrow-left"></i> Quay lại</a>
                        <a href="capnhat_matkhau_sv.php?id=<?php echo $sinhvien_info['id']; ?>" class="action-btn update-pwd-btn"><i class="fas fa-key"></i> Cập nhật MK</a>
                        
                        <?php if (isset($sinhvien_info['account_status']) && $sinhvien_info['account_status'] == 'active'): ?>
                            <a href="danhsachsv.php?action=toggle_lock&id=<?php echo $sinhvien_info['id']; ?>" class="action-btn lock-btn" onclick="return confirm('Bạn có chắc muốn khóa tài khoản này?');"><i class="fas fa-lock"></i> Khóa tài khoản</a>
                        <?php else: ?>
                            <a href="danhsachsv.php?action=toggle_lock&id=<?php echo $sinhvien_info['id']; ?>" class="action-btn unlock-btn" onclick="return confirm('Bạn có chắc muốn mở khóa tài khoản này?');"><i class="fas fa-lock-open"></i> Mở khóa</a>
                        <?php endif; ?>

                        <a href="danhsachsv.php?action=delete&id=<?php echo $sinhvien_info['id']; ?>" class="action-btn delete-btn" style="margin-left: auto;"><i class="fas fa-trash-alt"></i> Xóa sinh viên</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>