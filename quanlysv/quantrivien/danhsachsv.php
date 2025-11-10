<?php
session_start();

// Ngăn trình duyệt cache trang
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kiểm tra nếu người dùng chưa đăng nhập hoặc không phải là Quản trị viên
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

// Khởi tạo biến thông báo
$message = '';
$message_type = '';

// Lấy thông báo từ session (sau khi được chuyển hướng) và xóa nó đi
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Xử lý yêu cầu xóa sinh viên
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];

    $conn->begin_transaction();
    try {
        // Xóa sinh viên, tài khoản liên quan sẽ tự động bị xóa do có ON DELETE CASCADE
        $stmt = $conn->prepare("DELETE FROM sinhvien WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "Xóa sinh viên thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Không tìm thấy sinh viên để xóa hoặc đã có lỗi xảy ra.";
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['message'] = "Lỗi khi xóa sinh viên: " . $exception->getMessage();
        $_SESSION['message_type'] = "error";
    }

    header("Location: danhsachsv.php");
    exit();
}

// Xử lý yêu cầu khóa/mở khóa tài khoản
if (isset($_GET['action']) && $_GET['action'] == 'toggle_lock' && isset($_GET['id'])) {
    $sv_id_to_toggle = $_GET['id'];

    $stmt_status = $conn->prepare("SELECT status FROM taikhoan WHERE sv_id = ?");
    $stmt_status->bind_param("i", $sv_id_to_toggle);
    $stmt_status->execute();
    $result_status = $stmt_status->get_result();

    if ($result_status->num_rows > 0) {
        $account = $result_status->fetch_assoc();
        $new_status = ($account['status'] == 'active') ? 'inactive' : 'active';
        $action_text = ($new_status == 'inactive') ? 'Khóa' : 'Mở khóa';

        $stmt_update = $conn->prepare("UPDATE taikhoan SET status = ? WHERE sv_id = ?");
        $stmt_update->bind_param("si", $new_status, $sv_id_to_toggle);
        if ($stmt_update->execute()) {
            $_SESSION['message'] = $action_text . " tài khoản thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Lỗi khi cập nhật trạng thái tài khoản.";
            $_SESSION['message_type'] = "error";
        }
        $stmt_update->close();
    } else {
        $_SESSION['message'] = "Không tìm thấy tài khoản để thực hiện hành động.";
        $_SESSION['message_type'] = "error";
    }
    $stmt_status->close();

    header("Location: danhsachsv.php");
    exit();
}

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
// Lấy danh sách khóa học cho bộ lọc
$khoahoc_list_filter = $conn->query("SELECT ma_khoahoc, ten_khoahoc FROM khoahoc ORDER BY ten_khoahoc DESC")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách sinh viên từ cơ sở dữ liệu, có lọc theo khóa học
$sinhvien_list = [];
$selected_khoahoc = $_GET['ma_khoahoc'] ?? '';

$sql = "SELECT sv.id, sv.mssv, sv.ho_ten, sv.email, sv.cccd, tk.status, kh.ten_khoahoc
        FROM sinhvien sv
        LEFT JOIN taikhoan tk ON sv.id = tk.sv_id
        LEFT JOIN khoahoc kh ON sv.ma_khoahoc = kh.ma_khoahoc
        WHERE sv.ma_khoa = ?";

$params = [$ma_khoa_admin];
$types = "i";
if (!empty($selected_khoahoc)) {
    $sql .= " AND sv.ma_khoahoc = ?";
    $params[] = $selected_khoahoc;
    $types .= "i";
}

$sql .= " ORDER BY CAST(SUBSTRING(sv.mssv, -3) AS UNSIGNED) ASC, sv.mssv ASC";

$stmt_list = $conn->prepare($sql);
$stmt_list->bind_param($types, ...$params);
$stmt_list->execute();
$result = $stmt_list->get_result();
$sinhvien_list = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-form {
            max-width: 500px; margin-bottom: 20px; display: flex;
            gap: 15px; align-items: center;
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

        <main class="content full-width-content">
            <div class="welcome-container"><h1 class="welcome-message">Danh Sách Sinh Viên</h1></div>
            <?php if (!empty($message)): ?><div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            
            <!-- Form lọc theo khóa học -->
            <form action="danhsachsv.php" method="GET" class="filter-form">
                <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
                    <label for="ma_khoahoc">Lọc theo Khóa Học:</label>
                    <select id="ma_khoahoc" name="ma_khoahoc" onchange="this.form.submit()">
                        <option value="">-- Hiển thị tất cả --</option>
                        <?php foreach ($khoahoc_list_filter as $kh): ?>
                            <option value="<?php echo $kh['ma_khoahoc']; ?>" <?php echo ($selected_khoahoc == $kh['ma_khoahoc']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kh['ten_khoahoc']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>


            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>MSSV</th>
                            <th>Họ và Tên</th>
                            <th>Email</th>
                            <th>CCCD</th>
                            <th>Khóa học</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sinhvien_list)): ?>
                            <tr><td colspan="8">Không có sinh viên nào phù hợp với tiêu chí lọc.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sinhvien_list as $index => $sv): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($sv['mssv']); ?></td>
                                    <td><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($sv['email']); ?></td>
                                    <td><?php echo htmlspecialchars($sv['cccd']); ?></td>
                                    <td><?php echo htmlspecialchars($sv['ten_khoahoc'] ?? 'Chưa có'); ?></td>
                                    <td>
                                        <?php if (isset($sv['status']) && $sv['status'] == 'active'): ?><span class="status-active">Hoạt động</span><?php else: ?><span class="status-locked">Bị khóa</span><?php endif; ?>
                                    </td>
                                    <td class="action-cell">
                                        <a href="xemsv.php?id=<?php echo $sv['id']; ?>" class="action-btn view-btn">Xem chi tiết</a>
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
