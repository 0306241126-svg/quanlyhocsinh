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

$sinhvien_id = $_SESSION['user_id'];
$user_info = null;
$grades_by_semester = [];

// 1. Lấy thông tin chi tiết của Sinh viên, Khoa, Lớp và Bậc đào tạo
$stmt_info = $conn->prepare("
    SELECT 
        sv.ho_ten, sv.mssv, sv.trangthai,
        l.ten_lop,
        k.ten_khoa,
        bdt.ten_bac, bdt.ma_bac,
        sv.ma_khoa, kh.ma_khoahoc
    FROM sinhvien sv
    LEFT JOIN lop_sinhvien lsv ON sv.id = lsv.id_sv
    LEFT JOIN lophoc l ON lsv.ma_lop = l.ma_lop
    LEFT JOIN khoahoc kh ON l.ma_khoahoc = kh.ma_khoahoc
    LEFT JOIN bacdaotao bdt ON kh.ma_bac = bdt.ma_bac
    LEFT JOIN khoa k ON sv.ma_khoa = k.ma_khoa
    WHERE sv.id = ?
");
if ($stmt_info) {
    $stmt_info->bind_param("i", $sinhvien_id);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    if ($result_info->num_rows > 0) {
        $user_info = $result_info->fetch_assoc();
    }
    $stmt_info->close();
}

if ($user_info && isset($user_info['ma_bac']) && isset($user_info['ma_khoa'])) {
    $student_ma_bac = $user_info['ma_bac'];
    $student_ma_khoa = $user_info['ma_khoa'];

    // 2. Lấy tất cả môn học thuộc chương trình đào tạo của sinh viên và LEFT JOIN điểm
    $stmt_grades = $conn->prepare("
        SELECT
            hk.ten_hocky,
            mh.ma_mon,
            mh.ten_mon,
            mh.so_tin_chi,
            d.diem_chuyencan, d.tong15p, d.tong1tiet,
            d.diem_thilan1, d.diem_thilan2, d.tongket, d.ghichu
        FROM monhoc mh
        JOIN hocky hk ON mh.ma_hocky = hk.ma_hocky
        LEFT JOIN diem d ON mh.ma_mon = d.ma_mon AND d.id_sv = ?
        WHERE 
            hk.ma_bac = ? 
            AND mh.ma_khoa = ?
        ORDER BY hk.ma_hocky ASC, mh.ten_mon ASC
    ");
    if ($stmt_grades) {
        // Tham số: id_sv, ma_bac, ma_khoa
        $stmt_grades->bind_param("iii", $sinhvien_id, $student_ma_bac, $student_ma_khoa);
        $stmt_grades->execute();
        $result_grades = $stmt_grades->get_result();
        while ($row = $result_grades->fetch_assoc()) {
            $grades_by_semester[$row['ten_hocky']][] = $row;
        }
        $stmt_grades->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Học Tập</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .info-container {
            background: #fff; padding: 25px; border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 30px;
            border-left: 5px solid #3b7ddd;
        }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 30px; }
        .info-item { display: flex; }
        .info-item label { font-weight: 600; color: #555; min-width: 120px; }
        .info-item span { color: #333; }
        .semester-group { margin-bottom: 40px; }
        .semester-title { font-size: 1.5rem; color: #333; margin-bottom: 15px; border-bottom: 2px solid #3b7ddd; padding-bottom: 5px; }
        
        /* Căn giữa tiêu đề chính */
        .welcome-message {
            text-align: center;
        }

        /* Styles for the dropdown menu in the sidebar */
        .main-menu ul li.menu-dropdown {
            position: relative;
        }
        .main-menu ul li.menu-dropdown .dropbtn {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s ease-in-out;
            border-left: 4px solid transparent;
            display: flex; justify-content: space-between; align-items: center;
        }
        .main-menu ul li.menu-dropdown .dropbtn:hover, .main-menu ul li.menu-dropdown.active .dropbtn { background-color: rgba(255, 255, 255, 0.2); border-left-color: #fff; }
        .main-menu ul li.menu-dropdown .dropdown-content { display: none; position: relative; background-color: #3b7ddd; min-width: 200px; z-index: 1001; left: 0; top: 0; border-radius: 0 0 8px 8px; overflow: hidden; }
        .main-menu ul li.menu-dropdown .dropdown-content a { color: #fff; padding: 12px 25px; text-decoration: none; display: block; text-align: left; font-weight: normal; transition: background-color 0.2s, color 0.2s; border-left: 4px solid transparent; }
        .main-menu ul li.menu-dropdown .dropdown-content a:hover, .main-menu ul li.menu-dropdown .dropdown-content a.active { background-color: rgba(255, 255, 255, 0.2); border-left-color: #fff; }
        .main-menu ul li.menu-dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo-container"><a href="trangchusv.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a></div>
        <nav class="main-menu">
            <ul>
                <li><a href="trangchusv.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                <li><a href="thongbaosv.php"><i class="fas fa-bell"></i>Thông Báo</a></li>
                <li class="menu-dropdown active">
                    <a href="#" class="dropbtn"><i class="fas fa-book-reader"></i>Kết quả học tập <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="diemtbkt.php" class="active">Điểm TBKT_Thi</a>
                        <a href="diemkq.php">Điểm kết quả</a>
                    </div>
                </li>
                <li><a href="suathongtin_sv.php"><i class="fas fa-user-edit"></i>Cập nhật Lý Lịch</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer"><a href="logoutsv.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a></div>
    </aside>

    <main class="content full-width-content">
        <h1 class="welcome-message">Bảng Điểm Quá Trình và Thi</h1>

        <?php if ($user_info): ?>
        <div class="info-container">
            <div class="info-grid">
                <div class="info-item"><label>Họ và tên:</label> <span><?php echo htmlspecialchars($user_info['ho_ten']); ?></span></div>
                <div class="info-item"><label>Ngành:</label> <span><?php echo htmlspecialchars($user_info['ten_khoa'] ?? 'Chưa có'); ?></span></div>
                <div class="info-item"><label>MSSV:</label> <span><?php echo htmlspecialchars($user_info['mssv']); ?></span></div>
                <div class="info-item"><label>Bậc đào tạo:</label> <span><?php echo htmlspecialchars($user_info['ten_bac'] ?? 'Chưa có'); ?></span></div>
                <div class="info-item"><label>Lớp:</label> <span><?php echo htmlspecialchars($user_info['ten_lop'] ?? 'Chưa xếp lớp'); ?></span></div>
                <div class="info-item"><label>Tình trạng:</label> <span><?php echo ($user_info['trangthai'] == 1) ? 'Đang học' : 'Đã nghỉ'; ?></span></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($grades_by_semester)): ?>
            <div class="message">Chưa có dữ liệu điểm cho sinh viên này.</div>
        <?php else: ?>
            <?php foreach ($grades_by_semester as $semester_name => $grades): ?>
                <div class="semester-group table-container">
                    <h2 class="semester-title"><?php echo htmlspecialchars($semester_name); ?></h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Tên Môn Học</th>
                                <th>Số TC</th>
                                <th>Chuyên Cần</th>
                                <th>Điểm 15'</th>
                                <th>Điểm 1 Tiết</th>
                                <th>Thi Lần 1</th>
                                <th>Thi Lần 2</th>
                                <th>Tổng Kết</th>
                                <th>Ghi Chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $index => $grade): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($grade['ten_mon']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['so_tin_chi']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['diem_chuyencan'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['tong15p'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['tong1tiet'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['diem_thilan1'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['diem_thilan2'] ?? '-'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($grade['tongket'] ?? '-'); ?></strong></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($grade['ghichu'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>