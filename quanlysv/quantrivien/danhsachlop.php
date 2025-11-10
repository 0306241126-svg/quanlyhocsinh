<?php
session_start();

// Bảo vệ trang, chỉ cho phép Quản trị viên truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

// Lấy ma_khoa của quản trị viên đang đăng nhập
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

if ($ma_khoa_admin === null) {
    die("Lỗi: Không thể xác định khoa của quản trị viên.");
}

// Lấy các giá trị lọc từ URL
$selected_namhoc = isset($_GET['ma_namhoc']) ? (int)$_GET['ma_namhoc'] : 0;
$selected_khoahoc = isset($_GET['ma_khoahoc']) ? (int)$_GET['ma_khoahoc'] : 0;
$selected_hocky = isset($_GET['ma_hocky']) ? (int)$_GET['ma_hocky'] : 0;
$selected_lop = isset($_GET['ma_lop']) ? (int)$_GET['ma_lop'] : 0;

// Lấy danh sách cho các bộ lọc
$khoahoc_list = [];
$hocky_list = [];
$lophoc_list = [];
$sinhvien_list = [];

// Lấy danh sách khóa học cho bộ lọc
$stmt_kh = $conn->prepare("SELECT ma_khoahoc, ten_khoahoc FROM khoahoc WHERE ma_khoa = ? ORDER BY ten_khoahoc ASC");
$stmt_kh->bind_param("i", $ma_khoa_admin);
$stmt_kh->execute();
$khoahoc_list = $stmt_kh->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_kh->close();

// Nếu đã chọn khóa học, lấy danh sách học kỳ tương ứng
if ($selected_khoahoc > 0) {
    $stmt_hk = $conn->prepare("SELECT DISTINCT hk.ma_hocky, hk.ten_hocky FROM hocky hk JOIN khoahoc kh ON hk.ma_bac = kh.ma_bac WHERE kh.ma_khoahoc = ? ORDER BY hk.ma_hocky ASC");
    $stmt_hk->bind_param("i", $selected_khoahoc);
    $stmt_hk->execute();
    $hocky_list = $stmt_hk->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_hk->close();
}

// Nếu đã chọn khóa học và học kỳ, lấy danh sách lớp học
if ($selected_khoahoc > 0 && $selected_hocky > 0) {
    $stmt_lop = $conn->prepare("SELECT l.ma_lop, l.ten_lop, nh.ten_namhoc 
                                FROM lophoc l 
                                JOIN namhoc nh ON l.ma_namhoc = nh.ma_namhoc 
                                WHERE l.ma_khoahoc = ? AND l.ma_hocky = ? AND l.ma_khoa = ? 
                                ORDER BY l.ten_lop ASC");
    $stmt_lop->bind_param("iii", $selected_khoahoc, $selected_hocky, $ma_khoa_admin);
    $stmt_lop->execute();
    $lophoc_list = $stmt_lop->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_lop->close();
}

// Nếu đã chọn lớp, lấy danh sách sinh viên của lớp đó
if ($selected_lop > 0) {
    $sql_sv_list = "SELECT sv.id, sv.mssv, sv.ho_ten, sv.email, sv.ngay_sinh
                    FROM sinhvien sv
                    JOIN lop_sinhvien lsv ON sv.id = lsv.id_sv
                    WHERE lsv.ma_lop = ?
                    ORDER BY CAST(SUBSTRING(sv.mssv, -3) AS UNSIGNED) ASC, sv.mssv ASC";
    $stmt_sv_list = $conn->prepare($sql_sv_list);
    $stmt_sv_list->bind_param("i", $selected_lop);
    $stmt_sv_list->execute();
    $sinhvien_list = $stmt_sv_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_sv_list->close();
}

// Lấy tên đầy đủ của lớp đã chọn để hiển thị trong tiêu đề
$selected_lop_ten_full = '';
if ($selected_lop > 0) {
    $stmt_lop_full = $conn->prepare("SELECT l.ten_lop, hk.ten_hocky, nh.ten_namhoc FROM lophoc l JOIN hocky hk ON l.ma_hocky = hk.ma_hocky JOIN namhoc nh ON l.ma_namhoc = nh.ma_namhoc WHERE l.ma_lop = ?");
    $stmt_lop_full->bind_param("i", $selected_lop);
    $stmt_lop_full->execute();
    if ($lop_data = $stmt_lop_full->get_result()->fetch_assoc()) {
        $selected_lop_ten_full = htmlspecialchars($lop_data['ten_lop']) . ' ' . htmlspecialchars($lop_data['ten_hocky']) . ' (' . htmlspecialchars($lop_data['ten_namhoc']) . ')';
    }
    $stmt_lop_full->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Lớp</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }
        .filter-form .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
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
                    <li class="active"><a href="monhoc.php"><i class="fa-solid fa-book-open"></i>Danh sách môn học</a></li>
                    <li><a href="danhsachpc.php"><i class="fas fa-list-check"></i>Danh sách phân công</a></li>
                    <!-- Thêm link tới trang này -->
                    <li><a href="danhsachlop.php"><i class="fas fa-list-alt"></i>Danh sách lớp</a></li>
                </ul>
            </nav>
        </aside>

        <main class="content full-width-content">
            <h1 class="welcome-message">Xem Danh Sách Lớp</h1>
            <div class="filter-container">
                <form action="danhsachlop.php" method="GET" class="filter-form" id="filterForm">
                    <!-- Khóa học -->
                    <div class="form-group">
                        <label for="ma_khoahoc">Khóa học</label>
                        <select id="ma_khoahoc" name="ma_khoahoc" onchange="document.getElementById('filterForm').submit();">
                            <option value="0">-- Chọn khóa học --</option>
                            <?php foreach ($khoahoc_list as $kh): ?>
                                <option value="<?php echo $kh['ma_khoahoc']; ?>" <?php if ($selected_khoahoc == $kh['ma_khoahoc']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($kh['ten_khoahoc']); ?> 
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Học kỳ -->
                    <div class="form-group">
                        <label for="ma_hocky">Học kỳ</label>
                        <select id="ma_hocky" name="ma_hocky" onchange="this.form.submit()" <?php if (!$selected_khoahoc) echo 'disabled'; ?>>
                            <option value="0">-- Chọn học kỳ --</option>
                            <?php foreach ($hocky_list as $hk): ?>
                                <option value="<?php echo $hk['ma_hocky']; ?>" <?php if ($selected_hocky == $hk['ma_hocky']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($hk['ten_hocky']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Lớp học -->
                    <div class="form-group">
                        <label for="ma_lop">Lớp học</label>
                        <select id="ma_lop" name="ma_lop" onchange="this.form.submit()" <?php if (!$selected_hocky) echo 'disabled'; ?>>
                            <option value="0">-- Chọn lớp --</option>
                            <?php foreach ($lophoc_list as $lop): ?>
                                <option value="<?php echo $lop['ma_lop']; ?>" <?php if ($selected_lop == $lop['ma_lop']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($lop['ten_lop']) . ' (' . htmlspecialchars($lop['ten_namhoc']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($selected_lop > 0): ?>
                <div class="table-container">
                    <h2 style="text-align: center; margin-bottom: 20px;">Danh Sách Sinh Viên Lớp: <?php echo $selected_lop_ten_full; ?></h2>
                    <?php if (empty($sinhvien_list)): ?>
                        <p style="text-align: center; color: #666;">Lớp này chưa có sinh viên nào.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>MSSV</th>
                                    <th>Họ và Tên</th>
                                    <th>Ngày Sinh</th>
                                    <th>Email</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sinhvien_list as $index => $sv): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($sv['mssv']); ?></td>
                                        <td><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                        <td><?php echo !empty($sv['ngay_sinh']) ? htmlspecialchars(date('d/m/Y', strtotime($sv['ngay_sinh']))) : 'Chưa có'; ?></td>
                                        <td><?php echo htmlspecialchars($sv['email']); ?></td>
                                        <td class="action-cell">
                                            <a href="xemsv.php?id=<?php echo $sv['id']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> Xem</a>
                                            <a href="quatrinh.php?mssv=<?php echo $sv['mssv']; ?>" class="action-btn" style="background-color: #17a2b8;"><i class="fas fa-chart-line"></i> Quá trình</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php elseif (isset($_GET['ma_khoahoc'])): ?>
                <div class="message">Vui lòng chọn đầy đủ Năm học, Khóa học và Lớp học để xem danh sách.</div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>