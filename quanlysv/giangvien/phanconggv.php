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

// Lấy ID của giảng viên đang đăng nhập
$giangvien_id = $_SESSION['user_id'];

// Xây dựng câu truy vấn SQL để lấy danh sách phân công
$sql = "SELECT l.ma_lop, l.ten_lop, mh.ten_mon, nh.ten_namhoc, hk.ten_hocky
        FROM phancong pc
        JOIN lophoc l ON pc.ma_lop = l.ma_lop
        JOIN monhoc mh ON pc.ma_monhoc = mh.ma_mon
        JOIN namhoc nh ON pc.ma_namhoc = nh.ma_namhoc
        JOIN hocky hk ON pc.ma_hocky = hk.ma_hocky
        WHERE pc.ma_gv = ?";

$params = [$giangvien_id];
$types = "i"; // Định nghĩa kiểu dữ liệu cho tham số (i = integer)
$sql .= " ORDER BY nh.ten_namhoc DESC, hk.ma_hocky ASC, l.ten_lop ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    // bind_param yêu cầu biến $types phải được định nghĩa
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $phancong_list = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $phancong_list = [];
    // Ghi lại lỗi nếu cần
    error_log("SQL Error: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem Phân Công Giảng Dạy</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    
</head>
<body>
    <div class="dashboard-layout">
        <!-- THANH BÊN (SIDEBAR) -->
        <aside class="sidebar">
            <div class="sidebar-logo-container">
                <a href="trangchu.php"><img src="../img/ChatGPT Image 21_18_32 16 thg 10, 2025.png" alt="Logo Hệ thống"></a>
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

        </aside>

        <!-- NỘI DUNG CHÍNH -->
        <main class="content full-width-content">
            <div class="table-container">
                <h1 class="welcome-message">Phân Công Giảng Dạy</h1>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên lớp</th>
                            <th>Tên môn học</th>
                            <th>Năm học</th>
                            <th>Học kỳ</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($phancong_list)): ?>
                            <tr><td colspan="6">Bạn chưa được phân công giảng dạy lớp nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($phancong_list as $index => $pc): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($pc['ten_lop']); ?></td>
                                    <td><?php echo htmlspecialchars($pc['ten_mon']); ?></td>
                                    <td><?php echo htmlspecialchars($pc['ten_namhoc']); ?></td>
                                    <td><?php echo htmlspecialchars($pc['ten_hocky']); ?></td>
                                    <td class="action-cell">
                                        <a href="danhsach_sv.php?ma_lop=<?php echo $pc['ma_lop']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> Xem lớp</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>