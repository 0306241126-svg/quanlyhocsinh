<?php
session_start();

// Ngăn trình duyệt cache trang
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kiểm tra nếu người dùng chưa đăng nhập hoặc không phải là Quản trị viên
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

$user_id = $_SESSION['user_id'];
$user_info = null;

// Lấy thông tin Quản trị viên và Khoa của họ
// Sửa 'K.TenKhoa' thành 'K.ten_khoa' để khớp với cơ sở dữ liệu
$stmt = $conn->prepare("SELECT QV.*, K.ten_khoa, K.dia_chi, K.sdt FROM quantrivien QV 
                        LEFT JOIN khoa K ON QV.ma_khoa = K.ma_khoa 
                        WHERE QV.ma_qtv = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user_info = $result->fetch_assoc();
    }
    $stmt->close();
} else {
    error_log("Lỗi chuẩn bị truy vấn thông tin Quản trị viên: " . $conn->error);
}

// Nếu không tìm thấy thông tin, có thể là lỗi, hủy session và đăng xuất
if (!$user_info) {
    error_log("Không tìm thấy thông tin cho Quản trị viên với ID: " . $user_id);
    session_destroy();
    header("Location: ../giangvien/logingv.php?error=Không tìm thấy thông tin người dùng.");
    exit();
}

// Lấy tên để hiển thị
$display_name = $user_info['ten_khoa'];
$ten_khoa = $user_info['ten_khoa'] ?? 'Không thuộc khoa nào'; // Hiển thị nếu không có khoa
$dia_chi_khoa = $user_info['dia_chi'] ?? 'Chưa có thông tin';
$sdt_khoa = $user_info['sdt'] ?? 'Chưa có thông tin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bảng điều khiển Quản trị viên</title>
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-layout">
    <!-- THANH BÊN (SIDEBAR) -->
    <aside class="sidebar">
      <div class="sidebar-logo-container">
        <a href="trangchu.php">
          <img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống">
        </a>
      </div>
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
      <!-- NÚT ĐĂNG XUẤT -->
      <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
      </div>
    </aside>

    <!-- NỘI DUNG CHÍNH -->
    <main class="content">
      <div class="main-content-column">
        <!-- Lời chào mừng -->
        <div class="welcome-container">
          <h1 class="welcome-message">Trang Chủ Khoa <?= htmlspecialchars($display_name) ?></h1>
        </div>
  
        <!-- Phần Header chính: Thông tin quản trị viên -->
        <div class="main-header-wrapper">
          <div class="info-box">
            <h3 class="info-box-title">Thông tin Khoa Quản lý</h3>
            <div class="info-details">
                <div class="info-col">
                  <div class="info-item"> <a><i class="fa-solid fa-chalkboard-user"></i> Khoa:</a><strong><?= htmlspecialchars($ten_khoa) ?></strong></div>
                  <div class="info-item"> <a><i class="fa-solid fa-location-dot"></i> Địa chỉ:</a><strong><?= htmlspecialchars($dia_chi_khoa) ?></strong></div>
                  <div class="info-item" ><a><i class="fa-solid fa-phone"></i>  Số điện thoại:</a><strong><?= htmlspecialchars($sdt_khoa) ?></strong></div>
                </div>
            </div>
          </div>
        </div>
  
        <!-- Các khối chức năng nhanh -->
        <div class="quick-links-container">
          <a href="them_giangvien.php" class="quick-link-box">
            <h3><i class="fa-solid fa-user-plus"></i>Thêm giảng viên </h3>
            <p>Thêm, sửa, xóa thông tin giảng viên.</p>
          </a>
          <a href="them_sinhvien.php" class="quick-link-box">
            <h3><i class="fa-solid fa-user-plus"></i>Thêm sinh viên </h3>
            <p>Thêm, sửa, xóa thông tin sinh viên.</p>
          </a>
          <a href="khoahoc.php" class="quick-link-box">
            <h3><i class="fa-solid fa-layer-group"></i>Khóa học</h3>
            <p>Tạo và xem danh sách khóa học</p>
          </a>
        </div>

            <div class="quick-links-container">
          <a href="phancong.php" class="quick-link-box" style="border-left-color: #28a745;"> 
            <h3 style="color: #28a745;" ><i class="fa-solid fa-layer-group"></i>Phân công giảng dạy </h3>
            <p>Phân công giảng viên dạy lớp.</p>
          </a>
          <a href="themlop.php" class="quick-link-box" style="border-left-color: #28a745;">
            <h3 style="color: #28a745;"><i class="fa-solid fa-users"></i>Quản lý lớp</h3>
            <p>Tạo danh sách lớp theo khóa.</p>
          </a>
          <a href="quatrinh.php" class="quick-link-box" style="border-left-color: #28a745;">
            <h3 style="color: #28a745;"><i class="fa-solid fa-chart-line"></i>Quá trình sinh viên</h3>
            <p>Tạo và xem danh sách khóa học</p>
          </a>
        </div>



      </div>
    </main>
  </div>
</body>
</html>