<?php
session_start();

// Bảo vệ trang, chỉ cho phép Quản trị viên truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

$student_info = null;
$grades_info = [];
$error_message = '';
$ten_lop = '';
$ten_hocky = '';

$id_sv = null;
$ma_lop = null;

// Kịch bản 1: Tìm kiếm bằng MSSV
if (isset($_GET['mssv']) && !empty(trim($_GET['mssv']))) {
    $mssv_search = trim($_GET['mssv']);
    
    // Tìm id_sv từ mssv
    $stmt_find_sv = $conn->prepare("SELECT id FROM sinhvien WHERE mssv = ?");
    $stmt_find_sv->bind_param("s", $mssv_search);
    $stmt_find_sv->execute();
    $result_find_sv = $stmt_find_sv->get_result();

    if ($sv_data = $result_find_sv->fetch_assoc()) {
        $id_sv = $sv_data['id'];
        
        // Tìm ma_lop từ id_sv
        $stmt_find_lop = $conn->prepare("SELECT ma_lop FROM lop_sinhvien WHERE id_sv = ?");
        $stmt_find_lop->bind_param("i", $id_sv);
        $stmt_find_lop->execute();
        $result_find_lop = $stmt_find_lop->get_result();
        if ($lop_sv_data = $result_find_lop->fetch_assoc()) {
            $ma_lop = $lop_sv_data['ma_lop'];
        } else {
            $error_message = "Sinh viên này chưa được xếp vào lớp nào.";
        }
        $stmt_find_lop->close();
    } else {
        $error_message = "Không tìm thấy sinh viên với MSSV: " . htmlspecialchars($mssv_search);
    }
    $stmt_find_sv->close();

// Kịch bản 2: Chuyển hướng từ trang danh sách lớp
} elseif (isset($_GET['id_sv']) && is_numeric($_GET['id_sv']) && isset($_GET['ma_lop']) && is_numeric($_GET['ma_lop'])) {
    $id_sv = (int)$_GET['id_sv'];
    $ma_lop = (int)$_GET['ma_lop'];
}

// Nếu có đủ thông tin (id_sv và ma_lop), tiến hành lấy dữ liệu
if ($id_sv && $ma_lop && !$error_message) {
    
    // 1. Lấy thông tin sinh viên (đã có id_sv)
    $stmt_sv = $conn->prepare("SELECT ho_ten, mssv FROM sinhvien WHERE id = ?");
    $stmt_sv->bind_param("i", $id_sv);
    $stmt_sv->execute();
    $result_sv = $stmt_sv->get_result();
    if ($result_sv->num_rows === 1) {
        $student_info = $result_sv->fetch_assoc();
    } else {
        $error_message = "Không tìm thấy thông tin sinh viên.";
    }
    $stmt_sv->close();

    // 2. Lấy thông tin lớp và học kỳ của lớp (đã có ma_lop)
    $stmt_lop = $conn->prepare("
        SELECT l.ten_lop, l.ma_hocky, hk.ten_hocky 
        FROM lophoc l
        JOIN hocky hk ON l.ma_hocky = hk.ma_hocky
        WHERE l.ma_lop = ?
    ");
    $stmt_lop->bind_param("i", $ma_lop);
    $stmt_lop->execute();
    $result_lop = $stmt_lop->get_result();
    if ($lop_data = $result_lop->fetch_assoc()) {
        $ma_hocky = $lop_data['ma_hocky'];
        $ten_lop = $lop_data['ten_lop'];
        $ten_hocky = $lop_data['ten_hocky'];

        // 3. Lấy điểm của sinh viên cho các môn học trong học kỳ đó
        $stmt_grades = $conn->prepare("
            SELECT 
                mh.ten_mon,
                d.diem_chuyencan,
                d.tong15p,
                d.tong1tiet,
                d.diem_thilan1,
                d.diem_thilan2,
                d.tongket,
                d.ghichu
            FROM monhoc mh
            LEFT JOIN diem d ON mh.ma_mon = d.ma_mon AND d.id_sv = ?
            WHERE mh.ma_hocky = ?
            ORDER BY mh.ten_mon ASC
        ");
        $stmt_grades->bind_param("ii", $id_sv, $ma_hocky);
        $stmt_grades->execute();
        $grades_info = $stmt_grades->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_grades->close();

    } else {
        $error_message = "Không tìm thấy thông tin lớp học.";
    }
    $stmt_lop->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quá Trình Học Tập Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .student-info-box {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #17a2b8;
            font-size: 1.05rem;
        }
        .action-btn {
            background-color: #007bff; /* Màu xanh dương */
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo-container"><a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a></div>
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

        <main class="content">
            <div class="table-container">
                <h2 style="text-align: center; margin-bottom: 25px;">Quá Trình Học Tập Sinh Viên</h2>

                <!-- Form tìm kiếm -->
                <form action="quatrinhsv.php" method="GET" class="filter-form" style="max-width: 500px; margin: 0 auto 30px auto; display: flex; align-items: flex-end; gap: 15px;">
                    <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
                        <label for="mssv">Tìm kiếm theo MSSV</label>
                        <input type="text" id="mssv" name="mssv" placeholder="Nhập MSSV và nhấn Enter..." value="<?php echo htmlspecialchars($_GET['mssv'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="action-btn" style="padding: 12px 15px; height: 45px;"><i class="fas fa-search"></i> Tìm</button>
                </form>

                <?php if ($error_message): ?>
                    <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif (!$id_sv && !$ma_lop && !isset($_GET['mssv'])): ?>
                    <!-- Trạng thái ban đầu khi chưa tìm kiếm -->
                    <div class="message">Vui lòng nhập MSSV để tìm kiếm quá trình học tập của sinh viên.</div>
                <?php elseif ($student_info): ?>
                    <div class="student-info-box">
                        <div class="form-row">
                            <div class="form-group"><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student_info['ho_ten']); ?></div>
                            <div class="form-group"><strong>MSSV:</strong> <?php echo htmlspecialchars($student_info['mssv']); ?></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><strong>Lớp:</strong> <?php echo htmlspecialchars($ten_lop); ?></div>
                            <div class="form-group"><strong>Xem điểm cho:</strong> <?php echo htmlspecialchars($ten_hocky); ?></div>
                        </div>
                    </div>

                    <?php if (empty($grades_info)): ?>
                        <p style="text-align: center; color: #666;">Chưa có dữ liệu điểm cho sinh viên này trong học kỳ này.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên môn học</th>
                                    <th>Chuyên cần</th>
                                    <th>Điểm 15'</th>
                                    <th>Điểm 1 tiết</th>
                                    <th>Thi lần 1</th>
                                    <th>Thi lần 2</th>
                                    <th>Tổng kết</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades_info as $index => $grade): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($grade['ten_mon']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['diem_chuyencan'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($grade['tong15p'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($grade['tong1tiet'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($grade['diem_thilan1'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($grade['diem_thilan2'] ?? '-'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($grade['tongket'] ?? '-'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($grade['ghichu'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="themsvlop.php?ma_lop=<?php echo htmlspecialchars($ma_lop ?? ''); ?>" class="action-btn" style="background-color: #6c757d; display: inline-block; text-decoration: none;"><i class="fas fa-arrow-left"></i> Quay lại danh sách lớp</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
