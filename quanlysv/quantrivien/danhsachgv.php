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

// Xử lý yêu cầu xóa giảng viên
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];

    // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();
    try {
        // Xóa giảng viên, tài khoản liên quan sẽ tự động bị xóa do có ON DELETE CASCADE
        $stmt = $conn->prepare("DELETE FROM giangvien WHERE ma_gv = ?");
        $stmt->bind_param("i", $id_to_delete);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "Xóa giảng viên thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Không tìm thấy giảng viên để xóa hoặc đã có lỗi xảy ra.";
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['message'] = "Lỗi khi xóa giảng viên: " . $exception->getMessage();
        $_SESSION['message_type'] = "error";
    }

    // Chuyển hướng về lại trang danh sách để làm mới
    header("Location: danhsachgv.php");
    exit();
}

// Xử lý yêu cầu khóa/mở khóa tài khoản
if (isset($_GET['action']) && $_GET['action'] == 'toggle_lock' && isset($_GET['id'])) {
    $gv_id_to_toggle = $_GET['id'];

    // Lấy trạng thái hiện tại của tài khoản
    $stmt_status = $conn->prepare("SELECT status FROM taikhoan WHERE gv_id = ?");
    $stmt_status->bind_param("i", $gv_id_to_toggle);
    $stmt_status->execute();
    $result_status = $stmt_status->get_result();

    if ($result_status->num_rows > 0) {
        $account = $result_status->fetch_assoc();
        $new_status = ($account['status'] == 'active') ? 'inactive' : 'active';
        $action_text = ($new_status == 'inactive') ? 'Khóa' : 'Mở khóa';

        // Cập nhật trạng thái
        $stmt_update = $conn->prepare("UPDATE taikhoan SET status = ? WHERE gv_id = ?");
        $stmt_update->bind_param("si", $new_status, $gv_id_to_toggle);
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

    header("Location: danhsachgv.php");
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

// Lấy danh sách giảng viên từ cơ sở dữ liệu
$giangvien_list = [];
$sql = "SELECT gv.ma_gv, gv.ho_ten, gv.email, gv.cccd, tk.status 
        FROM giangvien gv
        LEFT JOIN taikhoan tk ON gv.ma_gv = tk.gv_id
        WHERE gv.ma_khoa = ?
        ORDER BY SUBSTRING_INDEX(gv.ho_ten, ' ', -1) ASC, gv.ho_ten ASC";
$stmt_list = $conn->prepare($sql);
$stmt_list->bind_param("i", $ma_khoa_admin);
$stmt_list->execute();
$giangvien_list = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Giảng Viên</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
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

        </aside>

        <!-- NỘI DUNG CHÍNH -->
        <main class="content full-width-content">
            <div class="welcome-container">
                <h1 class="welcome-message">Danh Sách Giảng Viên</h1>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ và Tên</th>
                            <th>Email</th>
                            <th>CCCD</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($giangvien_list)): ?>
                            <tr><td colspan="6">Chưa có dữ liệu giảng viên.</td></tr>
                        <?php else: ?>
                            <?php foreach ($giangvien_list as $index => $gv): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($gv['ho_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($gv['email']); ?></td>
                                    <td><?php echo htmlspecialchars($gv['cccd']); ?></td>
                                    <td>
                                        <?php if (isset($gv['status']) && $gv['status'] == 'active'): ?>
                                            <span class="status-active">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="status-locked">Bị khóa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-cell">
                                        <a href="xemgv.php?id=<?php echo $gv['ma_gv']; ?>" class="action-btn view-btn">Xem chi tiết</a>
                                        <a href="phancong.php?ma_gv=<?php echo $gv['ma_gv']; ?>" class="action-btn" style="background-color: #17a2b8;"><i class="fas fa-tasks"></i> Phân công</a>
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
