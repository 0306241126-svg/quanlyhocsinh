<?php
session_start();

// Ngăn trình duyệt cache trang
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kiểm tra nếu người dùng chưa đăng nhập hoặc không phải là Sinh Viên
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SinhVien') {
    header("Location: login_sinhvien.php");
    exit();
}

include("../connect_db.php");

$sinhvien_id = $_SESSION['user_id']; // Lấy ID sinh viên từ session
$user_info = null;

// Lấy thông tin chi tiết của Sinh viên, Khoa và Lớp học
$stmt = $conn->prepare("
    SELECT 
        sv.*, 
        k.ten_khoa,
        l.ten_lop
    FROM sinhvien sv
    LEFT JOIN khoa k ON sv.ma_khoa = k.ma_khoa
    LEFT JOIN lop_sinhvien lsv ON sv.id = lsv.id_sv
    LEFT JOIN lophoc l ON lsv.ma_lop = l.ma_lop
    WHERE sv.id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $sinhvien_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 1) {
        $user_info = $result->fetch_assoc();
    }
    $stmt->close();
} else {
    error_log("Lỗi chuẩn bị truy vấn thông tin Sinh Viên: " . $conn->error);
}

// Nếu không tìm thấy thông tin, có thể là lỗi, hủy session và đăng xuất
if (!$user_info) {
    error_log("Không tìm thấy thông tin cho sinh viên với ID: " . $sinhvien_id);
    session_destroy();
    header("Location: login_sinhvien.php?error=Không tìm thấy thông tin người dùng.");
    exit();
}

$display_name = $user_info['ho_ten'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bảng điều khiển sinh viên</title>  
  <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .student-info-container {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); /* Tăng bóng đổ để khung nổi bật hơn */
        padding: 40px; /* Tăng padding để làm khung cao hơn */
        display: flex;
        gap: 30px;
        align-items: flex-start; /* Căn các mục từ trên xuống */
        max-width: 900px; /* Tăng chiều rộng tối đa của khung */
        align-items: flex-start; /* Căn các mục từ trên xuống */
        margin: 30px auto;
        border-top: 15px solid #3b7ddd; /* Làm cho đường viền dày hơn nữa */
    }
    .avatar-section {
        flex-shrink: 0;
        text-align: center;
        margin-top: 66px; /* Đẩy ảnh xuống để ngang hàng với "Họ tên" */
    }
    .avatar-section img {
        width: 160px; /* Tăng kích thước ảnh đại diện */
        height: 160px; /* Tăng kích thước ảnh đại diện */
        object-fit: cover;
        border-radius: 12px; /* Đổi từ hình tròn sang hình vuông bo góc */
        border: 4px solid #e9ecef;
        background-color: #f8f9fa;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }
    .avatar-section .action-btn {
        font-size: 13px;
        padding: 8px 15px;
    }
    .details-section {
        flex-grow: 1;
    }
    .details-section .info-box-title {
        font-size: 24px;
        color: #333;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center; /* Căn giữa tiêu đề */
        gap: 10px;
    }
    .avatar-section {
        flex-shrink: 0;
        display: flex; /* Sử dụng flexbox */
        flex-direction: column; /* Xếp các mục theo chiều dọc */
        align-items: center; /* Căn giữa theo chiều ngang */
    }
    .details-section .info-item {
        display: flex;
        align-items: center;
        justify-content: space-between; /* Đẩy label và value ra hai bên */
        border: 1px solid #e9ecef; /* Thêm viền mỏng */
        padding: 12px 20px; /* Thêm padding bên trong */
        border-radius: 8px; /* Bo tròn góc */
        background-color: #f8f9fa; /* Thêm màu nền nhẹ */
    }
    .details-section .info-col {
        display: flex;
        flex-direction: column;
        gap: 25px; /* Tăng khoảng cách giữa các dòng thông tin */
        font-size: 18px; /* Tăng kích thước chữ */
    }

    /* Styles for the dropdown menu in the sidebar */
    .main-menu ul li.menu-dropdown {
        position: relative;
    }

    .main-menu ul li.menu-dropdown .dropbtn {
        /* Inherit styles from main-menu ul li a */
        display: block;
        padding: 15px 25px;
        color: white;
        text-decoration: none;
        font-weight: bold;
        transition: background-color 0.2s ease-in-out;
        border-left: 4px solid transparent;
        /* Thêm khoảng cách giữa chữ và icon mũi tên */
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .main-menu ul li.menu-dropdown .dropbtn:hover {
        background-color: rgba(255, 255, 255, 0.2);
        border-left-color: #fff;
    }

    .main-menu ul li.menu-dropdown .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9; /* Nền sáng cho menu con */
        min-width: 200px; /* Điều chỉnh chiều rộng tối thiểu */
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1001; /* Đảm bảo menu con hiển thị trên các phần tử khác */
        left: 0; /* Căn chỉnh với mục cha */
        top: 100%; /* Hiển thị ngay bên dưới mục cha */
        border-radius: 0 0 8px 8px; /* Bo tròn góc dưới */
        overflow: hidden; /* Đảm bảo nội dung không tràn ra ngoài bo góc */
    }

    .main-menu ul li.menu-dropdown .dropdown-content a {
        color: #333; /* Màu chữ tối cho mục con */
        padding: 12px 16px;
        text-decoration: none;
        display: block; /* Đảm bảo là khối để căn giữa hoạt động */
        text-align: center; /* Căn giữa chữ */
        font-weight: normal; /* Mục con không in đậm */
        transition: background-color 0.2s, color 0.2s;
        border-left: none; /* Không có viền trái cho mục con */
    }

    .main-menu ul li.menu-dropdown .dropdown-content a:hover {
        background-color: #ddd; /* Màu nền khi hover */
        color: #3b7ddd; /* Màu chữ khi hover */
    }

    /* Hiển thị menu con khi hover vào mục cha */
    .main-menu ul li.menu-dropdown:hover .dropdown-content {
        display: block;
        position: relative; /* Thay đổi từ absolute thành relative */
    }

  </style>
</head>
<body>
  <div class="dashboard-layout">
    <!-- THANH BÊN (SIDEBAR) -->
    <aside class="sidebar">
      <div class="sidebar-logo-container">
        <a href="trangchusv.php">
          <img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống">
        </a>
      </div>
      <nav class="main-menu">
        <ul>
          <li class="active"><a href="trangchusv.php"><i class="fas fa-home"></i>Trang chủ</a></li>
          <li><a href="thongbaosv.php"><i class="fas fa-bell"></i>Thông Báo</a></li>
          <li class="menu-dropdown">
            <a href="" class="dropbtn"><i class="fas fa-book-reader"></i>Kết quả học tập <i class="fas fa-caret-down"></i></a>
            <div class="dropdown-content">
              <a href="diemtbkt.php">Điểm TBKT_Thi</a>
              <a href="diemkq.php">Điểm kết quả</a> <!-- Placeholder, bạn có thể tạo trang này sau -->
            </div>
          </li>
          <li><a href="suathongtin_sv.php"><i class="fas fa-user-edit"></i>Cập nhật Lý Lịch</a></li>
          <!-- Thêm các mục menu khác cho sinh viên nếu cần -->
        </ul>
      </nav>
      <!-- NÚT ĐĂNG XUẤT -->
      <div class="sidebar-footer">
        <a href="logoutsv.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
      </div>
    </aside>

    <!-- NỘI DUNG CHÍNH -->
    <main class="content" style="display: block;">
        <div style="max-width: 900px; margin: 0 auto;"> <!-- Container để giới hạn chiều rộng và căn giữa -->
            <!-- Lời chào mừng -->
            <div class="welcome-container">
              <h1 class="welcome-message">Chào mừng, <?= htmlspecialchars($display_name) ?>!</h1>
            </div>


        <!-- Khung thông tin sinh viên mới -->
        <div class="student-info-container">
            <div class="avatar-section">
                <img src="<?php echo !empty($user_info['avatar']) ? htmlspecialchars($user_info['avatar']) : '../img/default_avatar.png'; ?>" alt="Ảnh đại diện">
              
            </div>
            <div class="details-section">
                <h3 class="info-box-title"><i class="fas fa-user-graduate"></i> Thông tin sinh viên</h3>
                <div class="info-col">
                    <div class="info-item"><a> Họ tên:</a><strong><?= htmlspecialchars($user_info['ho_ten']) ?></strong></div>
                    <div class="info-item"><a> MSSV:</a><strong><?= htmlspecialchars($user_info['mssv']) ?></strong></div>
                    <div class="info-item"><a>Ngày sinh:</a><strong><?= !empty($user_info['ngay_sinh']) ? htmlspecialchars(date('d/m/Y', strtotime($user_info['ngay_sinh']))) : 'Chưa cập nhật' ?></strong></div>
                    <div class="info-item"><a>Giới tính:</a><strong><?= !empty($user_info['gioi_tinh']) ? htmlspecialchars($user_info['gioi_tinh']) : 'Chưa cập nhật' ?></strong></div>
                    <div class="info-item"><a> Email:</a><strong><?= htmlspecialchars($user_info['email']) ?></strong></div>
                    <div class="info-item"><a> Lớp:</a><strong><?= !empty($user_info['ten_lop']) ? htmlspecialchars($user_info['ten_lop']) : 'Chưa xếp lớp' ?></strong></div>
                    <div class="info-item"><a> Khoa:</a><strong><?= !empty($user_info['ten_khoa']) ? htmlspecialchars($user_info['ten_khoa']) : 'Chưa có' ?></strong></div>
                </div>
            </div>
        </div> <!-- Kết thúc student-info-container -->

        <!-- Thêm phần footer -->
        <div class="page-footer">
            <p>&copy; <?php echo date("Y"); ?> CDKT</p>
        </div>
        </div>
    </main>
  </div>
  <style>
    .page-footer {
        text-align: center;
        margin-top: 30px; /* Tăng khoảng cách với nội dung trên */
        padding: 20px;
        color: #777;
        font-size: 14px;
        border-top: 1px solid #eee;
        background-color: #f9f9f9;
        border-radius: 8px;
    }
  </style>
</body>
</html>