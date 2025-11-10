<?php
session_start();

// Bảo vệ trang, chỉ cho phép Quản trị viên truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

$message = '';
$message_type = '';
$ten_lop = '';
$ten_hocky = ''; // Thêm biến để lưu tên học kỳ
$ma_lop = null;

// Lấy thông báo từ session nếu có
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Lấy ma_lop từ URL và kiểm tra tính hợp lệ
if (!isset($_GET['ma_lop']) || !is_numeric($_GET['ma_lop'])) {
    $_SESSION['message'] = "ID lớp học không hợp lệ.";
    $_SESSION['message_type'] = "error";
    header("Location: themlop.php");
    exit();
}
$ma_lop = (int)$_GET['ma_lop'];

// Lấy tên lớp và tên học kỳ để hiển thị
$stmt_lop = $conn->prepare("
    SELECT l.ten_lop, hk.ten_hocky 
    FROM lophoc l
    JOIN hocky hk ON l.ma_hocky = hk.ma_hocky
    WHERE l.ma_lop = ?
");
$stmt_lop->bind_param("i", $ma_lop);
$stmt_lop->execute();
$result_lop = $stmt_lop->get_result();
if ($result_lop->num_rows === 1) {
    $lop_info = $result_lop->fetch_assoc();
    $ten_lop = $lop_info['ten_lop'];
    $ten_hocky = $lop_info['ten_hocky'];
} else {
    $_SESSION['message'] = "Không tìm thấy lớp học.";
    $_SESSION['message_type'] = "error";
    header("Location: themlop.php");
    exit();
}
$stmt_lop->close();

// Xử lý yêu cầu xóa sinh viên khỏi lớp
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id_sv'])) {
    $id_sv_to_delete = (int)$_GET['id_sv'];
    $stmt_delete = $conn->prepare("DELETE FROM lop_sinhvien WHERE id_sv = ? AND ma_lop = ?");
    $stmt_delete->bind_param("ii", $id_sv_to_delete, $ma_lop);
    if ($stmt_delete->execute()) {
        $_SESSION['message'] = "Xóa sinh viên khỏi lớp thành công!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Lỗi khi xóa sinh viên khỏi lớp.";
        $_SESSION['message_type'] = "error";
    }
    $stmt_delete->close();
    header("Location: themsvlop.php?ma_lop=" . $ma_lop);
    exit();
}

// Xử lý form thêm sinh viên vào lớp
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mssv'])) {
    $mssv = trim($_POST['mssv']);

    if (empty($mssv)) {
        $_SESSION['message'] = "Vui lòng nhập MSSV.";
        $_SESSION['message_type'] = "error";
    } else {
        // 1. Tìm sinh viên dựa trên MSSV
        $stmt_sv = $conn->prepare("SELECT id FROM sinhvien WHERE mssv = ?");
        $stmt_sv->bind_param("s", $mssv);
        $stmt_sv->execute();
        $result_sv = $stmt_sv->get_result();

        if ($result_sv->num_rows === 0) {
            $_SESSION['message'] = "Không tìm thấy sinh viên với MSSV này.";
            $_SESSION['message_type'] = "error";
        } else {
            $sv_info = $result_sv->fetch_assoc();
            $id_sv = $sv_info['id'];

            // 2. Kiểm tra xem sinh viên đã có trong LỚP NÀY chưa
            $stmt_check = $conn->prepare("SELECT ma_lop FROM lop_sinhvien WHERE id_sv = ? AND ma_lop = ?");
            $stmt_check->bind_param("ii", $id_sv, $ma_lop);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $_SESSION['message'] = "Sinh viên này đã có trong lớp học này.";
                $_SESSION['message_type'] = "error";
            } else {
                // 3. Thêm sinh viên vào lớp
                $stmt_insert = $conn->prepare("INSERT INTO lop_sinhvien (ma_lop, id_sv) VALUES (?, ?)");
                $stmt_insert->bind_param("ii", $ma_lop, $id_sv);
                if ($stmt_insert->execute()) {
                    $_SESSION['message'] = "Thêm sinh viên vào lớp thành công!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Lỗi khi thêm sinh viên vào lớp.";
                    $_SESSION['message_type'] = "error";
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
        $stmt_sv->close();
    }
    header("Location: themsvlop.php?ma_lop=" . $ma_lop);
    exit();
}

// Lấy danh sách sinh viên của lớp, sắp xếp theo 2 số cuối MSSV
$sinhvien_list = [];
$sql_sv_list = "SELECT sv.id, sv.mssv, sv.ho_ten, sv.email
                FROM sinhvien sv
                JOIN lop_sinhvien lsv ON sv.id = lsv.id_sv
                WHERE lsv.ma_lop = ?
                ORDER BY CAST(SUBSTRING(sv.mssv, -2) AS UNSIGNED) ASC, sv.mssv ASC";
$stmt_sv_list = $conn->prepare($sql_sv_list);
$stmt_sv_list->bind_param("i", $ma_lop);
$stmt_sv_list->execute();
$result_sv_list = $stmt_sv_list->get_result();
if ($result_sv_list->num_rows > 0) {
    $sinhvien_list = $result_sv_list->fetch_all(MYSQLI_ASSOC);
}
$stmt_sv_list->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Sinh Viên vào Lớp</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content-columns { display: flex; gap: 30px; width: 100%; align-items: flex-start; }
        .form-column { flex: 1; max-width: 400px; }
        .list-column { flex: 2; min-width: 60%; }
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
            <div class="main-content-columns">
                <!-- Form thêm sinh viên -->
                <div class="form-column form-container">
                    <h2 class="form-title">Thêm Sinh Viên</h2>
                    <?php if (!empty($message)): ?>
                        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="themsvlop.php?ma_lop=<?php echo $ma_lop; ?>" method="POST">
                        <div class="form-group">
                            <label for="mssv">Nhập MSSV</label>
                            <input type="text" id="mssv" name="mssv" required placeholder="Nhập mã số sinh viên">
                        </div>
                        <input type="submit" value="Thêm vào lớp" class="btn form-submit-btn">
                    </form>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="themlop.php" class="action-btn" style="background-color: #6c757d; display: inline-block; text-decoration: none;"><i class="fas fa-arrow-left"></i> Quay lại Quản lý lớp</a>
                    </div>
                </div>

                <!-- Danh sách sinh viên trong lớp -->
                <div class="list-column table-container">
                    <h2 style="text-align: center; margin-bottom: 20px;">Danh Sách Sinh Viên Lớp: <?php echo htmlspecialchars($ten_lop); ?> (<?php echo htmlspecialchars($ten_hocky); ?>)</h2>
                    <?php if (empty($sinhvien_list)): ?>
                        <p style="text-align: center; color: #666;">Chưa có sinh viên nào trong lớp này.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead><tr><th>STT</th><th>MSSV</th><th>Họ và Tên</th><th>Email</th><th>Hành động</th></tr></thead>
                            <tbody>
                                <?php foreach ($sinhvien_list as $index => $sv): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($sv['mssv']); ?></td>
                                        <td><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                        <td><?php echo htmlspecialchars($sv['email']); ?></td>
                                        <td class="action-cell">
                                            <a href="quatrinhsv.php?id_sv=<?php echo $sv['id']; ?>&ma_lop=<?php echo $ma_lop; ?>" class="action-btn view-btn" style="background-color: #17a2b8;"><i class="fas fa-chart-line"></i> Xem quá trình</a>
                                            <a href="themsvlop.php?ma_lop=<?php echo $ma_lop; ?>&action=delete&id_sv=<?php echo $sv['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên này khỏi lớp?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>