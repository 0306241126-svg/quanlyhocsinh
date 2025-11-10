<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'GiangVien') {
    header("Location: logingv.php");
    exit();
}

include("../connect_db.php");

$giangvien_id = $_SESSION['user_id'];

// Lấy danh sách lớp mà giảng viên được phân công để hiển thị trong dropdown
$sql_lop = "SELECT DISTINCT l.ma_lop, l.ten_lop
            FROM phancong pc
            JOIN lophoc l ON pc.ma_lop = l.ma_lop
            WHERE pc.ma_gv = ?
            ORDER BY l.ten_lop";
$stmt_lop = $conn->prepare($sql_lop);
$stmt_lop->bind_param("i", $giangvien_id);
$stmt_lop->execute();
$lop_result = $stmt_lop->get_result();
$lop_list = $lop_result->fetch_all(MYSQLI_ASSOC);
$stmt_lop->close();

$selected_lop = isset($_GET['ma_lop']) ? (int)$_GET['ma_lop'] : 0;
$sinhvien_list = [];
$ten_lop_selected = '';

if ($selected_lop > 0) {
    // Kiểm tra xem giảng viên có thực sự được phân công lớp này không (bảo mật)
    $is_assigned = false;
    foreach ($lop_list as $lop) {
        if ($lop['ma_lop'] == $selected_lop) {
            $is_assigned = true;
            $ten_lop_selected = $lop['ten_lop'];
            break;
        }
    }

    if ($is_assigned) {
        // Lấy danh sách sinh viên của lớp đã chọn
        $sql_sv = "SELECT sv.*, k.ten_khoa
                   FROM sinhvien sv
                   JOIN lop_sinhvien lsv ON sv.id = lsv.id_sv
                   LEFT JOIN khoa k ON sv.ma_khoa = k.ma_khoa
                   WHERE lsv.ma_lop = ?
                   ORDER BY CAST(SUBSTRING(sv.mssv, -3) AS UNSIGNED) ASC, sv.mssv ASC";
        $stmt_sv = $conn->prepare($sql_sv);
        $stmt_sv->bind_param("i", $selected_lop);
        $stmt_sv->execute();
        $result_sv = $stmt_sv->get_result();
        $sinhvien_list = $result_sv->fetch_all(MYSQLI_ASSOC);
        $stmt_sv->close();
    } else {
        // Nếu giảng viên không được phân công lớp này, không hiển thị gì cả
        $selected_lop = 0; 
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo-container"><a href="trangchugv.php"><img src="../img/ChatGPT Image 21_18_32 16 thg 10, 2025.png" alt="Logo"></a></div>
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
        <h1 class="welcome-message">Xem Danh Sách Sinh Viên</h1>

        <div class="form-container" style="max-width: 600px; margin: 20px auto;">
            <form action="danhsach_sv.php" method="GET">
                <div class="form-group">
                    <label for="ma_lop">Chọn lớp để xem danh sách</label>
                    <select id="ma_lop" name="ma_lop" required onchange="this.form.submit()">
                        <option value="">-- Chọn lớp --</option>
                        <?php foreach ($lop_list as $lop): ?>
                            <option value="<?php echo $lop['ma_lop']; ?>" <?php if ($selected_lop == $lop['ma_lop']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($lop['ten_lop']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selected_lop > 0): ?>
            <div class="table-container">
                <h2 style="text-align: center; margin-bottom: 20px;">Danh sách sinh viên lớp: <?php echo htmlspecialchars($ten_lop_selected); ?></h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>MSSV</th>
                            <th>Họ Tên</th>
                            <th>Email</th>
                            <th>Ngày Sinh</th>
                            <th>Khoa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sinhvien_list)): ?>
                            <tr><td colspan="6">Lớp này chưa có sinh viên.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sinhvien_list as $index => $sv): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($sv['mssv']); ?></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($sv['email']); ?></td>
                                    <td><?php echo !empty($sv['ngay_sinh']) ? htmlspecialchars(date('d/m/Y', strtotime($sv['ngay_sinh']))) : 'Chưa cập nhật'; ?></td>
                                    <td><?php echo htmlspecialchars($sv['ten_khoa'] ?? 'Chưa có'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>