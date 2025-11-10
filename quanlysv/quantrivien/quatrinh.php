<?php
session_start();

// Bảo vệ trang, chỉ cho phép Quản trị viên truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

$student_info = null;
$all_grades_by_semester = [];
$error_message = '';

// Kịch bản: Tìm kiếm bằng MSSV
if (isset($_GET['mssv']) && !empty(trim($_GET['mssv']))) {
    $mssv_search = trim($_GET['mssv']);

    // 1. Lấy thông tin sinh viên
    $stmt_sv = $conn->prepare("
        SELECT sv.id, sv.ho_ten, sv.mssv, k.ten_khoa, sv.ma_khoa, kh.ma_bac
        FROM sinhvien sv
        LEFT JOIN khoa k ON sv.ma_khoa = k.ma_khoa
        LEFT JOIN khoahoc kh ON sv.ma_khoahoc = kh.ma_khoahoc
        WHERE sv.mssv = ?
    ");
    $stmt_sv->bind_param("s", $mssv_search);
    $stmt_sv->execute();
    $result_sv = $stmt_sv->get_result();

    if ($result_sv->num_rows === 1) {
        $student_info = $result_sv->fetch_assoc();
        $id_sv = $student_info['id'];

        // 2. Lấy tất cả điểm của sinh viên, nhóm theo học kỳ
        // Sửa đổi: Lấy tất cả môn học trong chương trình đào tạo và LEFT JOIN với điểm
        $stmt_grades = $conn->prepare("
            SELECT 
                hk.ten_hocky,
                mh.ten_mon,
                mh.so_tin_chi,
                d.diem_chuyencan,
                d.tong15p,
                d.tong1tiet,
                d.diem_thilan1,
                d.diem_thilan2,
                d.tongket,
                d.ghichu
            FROM monhoc mh
            JOIN hocky hk ON mh.ma_hocky = hk.ma_hocky
            LEFT JOIN diem d ON mh.ma_mon = d.ma_mon AND d.id_sv = ?
            WHERE 
                hk.ma_bac = ? 
                AND mh.ma_khoa = ?
            ORDER BY hk.ma_hocky ASC, mh.ten_mon ASC
        ");
        // Tham số: id_sv, ma_bac, ma_khoa
        $stmt_grades->bind_param("iii", $id_sv, $student_info['ma_bac'], $student_info['ma_khoa']);
        $stmt_grades->execute();
        $grades_result = $stmt_grades->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_grades->close();

        // Nhóm kết quả theo ten_hocky
        foreach ($grades_result as $grade) {
            $all_grades_by_semester[$grade['ten_hocky']][] = $grade;
        }

    } else {
        $error_message = "Không tìm thấy sinh viên với MSSV: " . htmlspecialchars($mssv_search);
    }
    $stmt_sv->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra Cứu Quá Trình Học Tập</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .student-info-box {
            background: #fff; border-radius: 8px; padding: 20px;
            margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #17a2b8; font-size: 1.05rem;
        }
        .action-btn { background-color: #007bff; color: white; }
        .semester-title {
            font-size: 1.5rem; color: #3b7ddd; margin-top: 40px;
            margin-bottom: 15px; padding-bottom: 10px;
            border-bottom: 2px solid #3b7ddd;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo-container"><a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a></div>
            <nav class="main-menu">
                <ul>
                    <li><a href="trangchu.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                    <li><a href="danhsachgv.php"><i class="fas fa-chalkboard-teacher"></i>Danh sách giảng viên</a></li>
                    <li><a href="danhsachsv.php"><i class="fas fa-user-graduate"></i>Danh sách sinh viên</a></li>
                    <li><a href="khoahoc.php"><i class="fas fa-users"></i>Danh sách khóa</a></li>
                    <li><a href="monhoc.php"><i class="fa-solid fa-book-open"></i>Danh sách môn học</a></li>
                    <li><a href="danhsachpc.php"><i class="fas fa-list-check"></i>Danh sách phân công</a></li>
                    <li><a href="danhsachlop.php"><i class="fas fa-list-alt"></i>Danh sách lớp</a></li>
                </ul>
            </nav>
        </aside>

        <main class="content">
            <div class="table-container">
                <h2 style="text-align: center; margin-bottom: 25px;">Tra Cứu Quá Trình Học Tập</h2>

                <!-- Form tìm kiếm -->
                <form action="quatrinh.php" method="GET" class="filter-form" style="max-width: 500px; margin: 0 auto 30px auto; display: flex; align-items: flex-end; gap: 15px;">
                    <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
                        <label for="mssv">Tìm kiếm theo MSSV</label>
                        <input type="text" id="mssv" name="mssv" placeholder="Nhập MSSV và nhấn Enter..." value="<?php echo htmlspecialchars($_GET['mssv'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" class="action-btn" style="padding: 12px 15px; height: 45px;"><i class="fas fa-search"></i> Tìm</button>
                </form>

                <?php if ($error_message): ?>
                    <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif (!$student_info && !isset($_GET['mssv'])): ?>
                    <div class="message">Vui lòng nhập MSSV để tìm kiếm quá trình học tập của sinh viên.</div>
                <?php elseif ($student_info): ?>
                    <div class="student-info-box">
                        <div class="form-row">
                            <div class="form-group"><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student_info['ho_ten']); ?></div>
                            <div class="form-group"><strong>MSSV:</strong> <?php echo htmlspecialchars($student_info['mssv']); ?></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><strong>Khoa:</strong> <?php echo htmlspecialchars($student_info['ten_khoa'] ?? 'Chưa có'); ?></div>
                        </div>
                    </div>

                    <?php if (empty($all_grades_by_semester)): ?>
                        <p style="text-align: center; color: #666;">Chưa có dữ liệu điểm cho sinh viên này.</p>
                    <?php else: ?>
                        <?php foreach ($all_grades_by_semester as $ten_hocky => $grades_info): ?>
                            <h3 class="semester-title"><?php echo htmlspecialchars($ten_hocky); ?></h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên môn học</th>
                                        <th>Số TC</th>
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
                                            <td><?php echo htmlspecialchars($grade['so_tin_chi']); ?></td>
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>