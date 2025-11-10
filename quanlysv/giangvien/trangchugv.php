<?php
session_start();

// Ngăn trình duyệt cache trang
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kiểm tra nếu người dùng chưa đăng nhập hoặc không phải là Giảng viên
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'GiangVien') {
    header("Location: logingv.php");
    exit();
}
include("../connect_db.php");
$giangvien_id = $_SESSION['user_id']; // Sử dụng user_id thay vì username
$user_info = null;

// Lấy thông tin Giảng viên và Khoa của họ
$stmt_gv = $conn->prepare("SELECT gv.*, k.ten_khoa 
                            FROM giangvien gv
                            LEFT JOIN khoa k ON gv.ma_khoa = k.ma_khoa
                            WHERE gv.ma_gv = ?");
if ($stmt_gv) {
    $stmt_gv->bind_param("i", $giangvien_id);
    $stmt_gv->execute();
    $result_gv = $stmt_gv->get_result();
    if ($result_gv->num_rows == 1) {
        $user_info = $result_gv->fetch_assoc();
    }
    $stmt_gv->close();
} else {
    error_log("Lỗi chuẩn bị truy vấn thông tin Giảng Viên: " . $conn->error);
}

// If user_info is still null, something went wrong or user not found
if (!$user_info) {
    error_log("User info not found for giangvien_id: " . $giangvien_id);
    session_destroy();
    header("Location: logingv.php?error=Không tìm thấy thông tin giảng viên.");
    exit();
}

// For display purposes, get the name
$display_name = $user_info['ho_ten'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bảng điều khiển giảng viên</title>  
  <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Áp dụng style giống trang sinh viên */
    .lecturer-info-container {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        padding: 40px;
        display: flex;
        gap: 30px;
        align-items: flex-start;
        max-width: 900px;
        margin: 30px auto;
        border-top: 15px solid #3b7ddd;
    }
    .avatar-section {
        flex-shrink: 0;
        text-align: center;
        margin-top: 66px;
    }
    .avatar-section img {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border-radius: 12px;
        border: 4px solid #e9ecef;
        background-color: #f8f9fa;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }
    .details-section {
        flex-grow: 1;
    }
    .details-section .info-box-title {
        font-size: 24px; color: #333; margin-bottom: 20px;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .details-section .info-item {
        display: flex; align-items: center; justify-content: space-between;
        border: 1px solid #e9ecef; padding: 12px 20px; border-radius: 8px;
        background-color: #f8f9fa;
    }
    .details-section .info-col {
        display: flex; flex-direction: column; gap: 35px; font-size: 18px;
    }
    .page-footer {
        text-align: center; margin-top: 30px; padding: 20px;
        color: #777; font-size: 14px; border-top: 1px solid #eee;
        background-color: #f9f9f9; border-radius: 8px;
    }
  </style>
</head>
<body>
  <div class="dashboard-layout">
    <!-- THANH BÊN (SIDEBAR) -->
    <aside class="sidebar">
      <div class="sidebar-logo-container">
        <a href="trangchugv.php">
          <img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống">
        </a>
      </div>
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
      <!-- NÚT ĐĂNG XUẤT -->
      <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
      </div>
    </aside>

    <!-- NỘI DUNG CHÍNH -->
    <main class="content" style="display: flex; flex-direction: column;">
      <div style="max-width: 900px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; flex-grow: 1;">
        <!-- Lời chào mừng -->
        <div class="welcome-container">
          <h1 class="welcome-message">Chào mừng giảng viên, <?= htmlspecialchars($display_name) ?>!</h1>
        </div>
  
        <!-- Khung thông tin giảng viên mới -->
        <div class="lecturer-info-container">
            <div class="avatar-section">
                <img src="<?php echo !empty($user_info['avatar']) ? htmlspecialchars($user_info['avatar']) : '../img/default_avatar.png'; ?>" alt="Ảnh đại diện">
            </div>
            <div class="details-section">
                <h3 class="info-box-title"><i class="fas fa-chalkboard-teacher"></i> Thông tin giảng viên</h3>
                <div class="info-col">
                    <div class="info-item"><a>Họ tên:</a><strong><?= htmlspecialchars($user_info['ho_ten']) ?></strong></div>
                    <div class="info-item"><a>Ngày sinh:</a><strong><?= !empty($user_info['ngay_sinh']) ? htmlspecialchars(date('d/m/Y', strtotime($user_info['ngay_sinh']))) : 'Chưa cập nhật' ?></strong></div>
                    <div class="info-item"><a>Giới tính:</a><strong><?= !empty($user_info['gioi_tinh']) ? htmlspecialchars($user_info['gioi_tinh']) : 'Chưa cập nhật' ?></strong></div>
                    <div class="info-item"><a>Email:</a><strong><?= htmlspecialchars($user_info['email']) ?></strong></div>
                    <div class="info-item"><a>Khoa:</a><strong><?= !empty($user_info['ten_khoa']) ? htmlspecialchars($user_info['ten_khoa']) : 'Chưa thuộc khoa nào' ?></strong></div>
                    <div class="info-item"><a>Ngày bắt đầu làm việc:</a><strong><?= !empty($user_info['ngay_bat_dau']) ? htmlspecialchars(date('d/m/Y', strtotime($user_info['ngay_bat_dau']))) : 'Chưa cập nhật' ?></strong></div>
                </div>
            </div>
        </div>

        <!-- Thêm phần footer -->
        <div class="page-footer" style="margin-top: auto;">
            <p>&copy; <?php echo date("Y"); ?> CDKT</p>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
