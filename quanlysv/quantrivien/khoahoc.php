<?php
session_start();

// Bảo vệ trang
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

$message = '';
$message_type = '';

// Lấy thông báo từ session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Xử lý yêu cầu xóa khóa học
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $ma_khoahoc_to_delete = (int)$_GET['id'];

    // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();

    try {
        // 1. Lấy ma_namhoc của khóa học sắp xóa
        $ma_namhoc_to_check = null;
        $stmt_get_namhoc = $conn->prepare("SELECT ma_namhoc FROM khoahoc WHERE ma_khoahoc = ? AND ma_khoa = ?");
        $stmt_get_namhoc->bind_param("ii", $ma_khoahoc_to_delete, $ma_khoa_admin);
        $stmt_get_namhoc->execute();
        $result_get_namhoc = $stmt_get_namhoc->get_result();
        if ($row = $result_get_namhoc->fetch_assoc()) {
            $ma_namhoc_to_check = $row['ma_namhoc'];
        }
        $stmt_get_namhoc->close();

        if ($ma_namhoc_to_check === null) {
            throw new Exception("Không tìm thấy khóa học hoặc bạn không có quyền xóa.");
        }

        // 2. Xóa khóa học
        // Chỉ cho phép xóa khóa học thuộc khoa của quản trị viên để bảo mật
        $stmt_delete = $conn->prepare("DELETE FROM khoahoc WHERE ma_khoahoc = ? AND ma_khoa = ?");
        $stmt_delete->bind_param("ii", $ma_khoahoc_to_delete, $ma_khoa_admin);
        $stmt_delete->execute();

        // 3. Kiểm tra xem năm học có còn được sử dụng ở nơi khác không
        $stmt_check_usage = $conn->prepare("SELECT ma_khoahoc FROM khoahoc WHERE ma_namhoc = ?");
        $stmt_check_usage->bind_param("i", $ma_namhoc_to_check);
        $stmt_check_usage->execute();
        $is_namhoc_used = $stmt_check_usage->get_result()->num_rows > 0;
        $stmt_check_usage->close();

        // 4. Nếu không còn được sử dụng, xóa năm học
        if (!$is_namhoc_used) {
            $stmt_delete_namhoc = $conn->prepare("DELETE FROM namhoc WHERE ma_namhoc = ?");
            $stmt_delete_namhoc->bind_param("i", $ma_namhoc_to_check);
            $stmt_delete_namhoc->execute();
            $stmt_delete_namhoc->close();
        }

        $_SESSION['message'] = "Xóa khóa học thành công!";
        $_SESSION['message_type'] = "success";
        $stmt_delete->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Lỗi khi xóa khóa học: " . $e->getMessage() . ". Có thể có dữ liệu liên quan (lớp học, sinh viên) đang tồn tại.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: khoahoc.php");
    exit();
}

// Xử lý form khi submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_khoahoc = $_POST['ten_khoahoc'];
    $ten_namhoc_input = $_POST['ten_namhoc']; // Lấy tên năm học từ input
    $ma_bac = $_POST['ma_bac'];

    if (empty($ten_khoahoc) || empty($ten_namhoc_input) || empty($ma_bac)) {
        $_SESSION['message'] = "Vui lòng điền đầy đủ thông tin.";
        $_SESSION['message_type'] = "error";
    } else {
        // --- Xử lý Năm học ---
        $ma_namhoc = null;
        // Kiểm tra xem năm học đã tồn tại chưa
        $stmt_check_namhoc = $conn->prepare("SELECT ma_namhoc FROM namhoc WHERE ten_namhoc = ?");
        $stmt_check_namhoc->bind_param("s", $ten_namhoc_input);
        $stmt_check_namhoc->execute();
        $result_check_namhoc = $stmt_check_namhoc->get_result();

        if ($row_namhoc = $result_check_namhoc->fetch_assoc()) {
            $ma_namhoc = $row_namhoc['ma_namhoc'];
        } else {
            // Nếu năm học chưa tồn tại, thêm mới
            $stmt_insert_namhoc = $conn->prepare("INSERT INTO namhoc (ten_namhoc) VALUES (?)");
            $stmt_insert_namhoc->bind_param("s", $ten_namhoc_input);
            $stmt_insert_namhoc->execute();
            $ma_namhoc = $conn->insert_id;
            $stmt_insert_namhoc->close();
        }
        $stmt_check_namhoc->close();
        // --- Kết thúc xử lý Năm học ---

        // Kiểm tra khóa học đã tồn tại chưa
        $stmt_check = $conn->prepare("SELECT ma_khoahoc FROM khoahoc WHERE ten_khoahoc = ? AND ma_namhoc = ? AND ma_bac = ? AND ma_khoa = ?");
        $stmt_check->bind_param("siii", $ten_khoahoc, $ma_namhoc, $ma_bac, $ma_khoa_admin);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['message'] = "Khóa học với các thông tin này đã tồn tại.";
            $_SESSION['message_type'] = "error";
        } else {
            // Thêm khóa học mới
            $stmt_insert = $conn->prepare("INSERT INTO khoahoc (ten_khoahoc, ma_namhoc, ma_bac, ma_khoa) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("siii", $ten_khoahoc, $ma_namhoc, $ma_bac, $ma_khoa_admin);
            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Thêm khóa học thành công!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Lỗi khi thêm khóa học: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    header("Location: khoahoc.php");
    exit();
}

// Lấy danh sách năm học và học kỳ cho form
$bacdaotao_list = $conn->query("SELECT * FROM bacdaotao ORDER BY ten_bac ASC")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách khóa học đã tạo
$khoahoc_list = []; // Khởi tạo lại để đảm bảo không bị lỗi nếu không có kết quả
$sql = "SELECT kh.ma_khoahoc, kh.ten_khoahoc, nh.ten_namhoc, bdt.ten_bac
        FROM khoahoc kh
        JOIN namhoc nh ON kh.ma_namhoc = nh.ma_namhoc
        JOIN bacdaotao bdt ON kh.ma_bac = bdt.ma_bac 
        WHERE kh.ma_khoa = ?
        ORDER BY kh.ma_khoahoc DESC";
$stmt_list = $conn->prepare($sql);
$stmt_list->bind_param("i", $ma_khoa_admin);
$stmt_list->execute();
$result = $stmt_list->get_result();
if ($result->num_rows > 0) {
    // Lấy tất cả các dòng kết quả vào một mảng
    $khoahoc_list = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khóa Học</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content-columns { display: flex; gap: 30px; width: 100%; align-items: flex-start; }
        .form-column { flex: 1; max-width: 500px; }
        .list-column { flex: 2; min-width: 50%; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
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

        <!-- Main Content -->
        <main class="content">
            <div class="main-content-columns">
                <!-- Form nhập -->
                <div class="form-column form-container">
                    <h2 class="form-title">Thêm Khóa Học</h2>
                    <?php if (!empty($message)): ?>
                        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="khoahoc.php" method="POST">
                        <div class="form-group">
                            <label for="ten_khoahoc">Tên khóa học</label>
                            <input type="text" id="ten_khoahoc" name="ten_khoahoc" required placeholder="Ví dụ: Khóa 2024-2027">
                        </div>
                        <div class="form-group">
                            <label for="ten_namhoc">Năm học bắt đầu</label>
                            <input type="text" id="ten_namhoc" name="ten_namhoc" required placeholder="Ví dụ: 2024-2025">
                        </div>
                        <div class="form-group">
                            <label for="ma_bac">Bậc đào tạo</label>
                            <select id="ma_bac" name="ma_bac" required>
                                <option value="">-- Chọn bậc đào tạo --</option>
                                <?php foreach ($bacdaotao_list as $bdt): ?>
                                    <option value="<?php echo $bdt['ma_bac']; ?>"><?php echo htmlspecialchars($bdt['ten_bac']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="submit" value="Thêm Khóa Học" class="btn form-submit-btn">
                    </form>
                </div>

                <!-- Danh sách khóa học đã nhập -->
                <div class="list-column table-container">
                    <h2 style="text-align: center; margin-bottom: 20px;">Danh Sách Khóa Học Đã Tạo</h2>
                    <?php if (empty($khoahoc_list)): ?>
                        <p style="text-align: center; color: #666;">Chưa có khóa học nào được tạo.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên khóa học</th>
                                    <th>Bậc đào tạo</th>
                                    <th>Khung đào tạo</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($khoahoc_list as $index => $kh): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($kh['ten_khoahoc']); ?></td>
                                        <td><?php echo htmlspecialchars($kh['ten_bac']); ?></td>
                                        <td>
                                            <?php
                                            $academic_plan_str = '';
                                            $start_year_str = $kh['ten_namhoc']; // e.g., "2024-2025"
                                            $start_year_int = (int)substr($start_year_str, 0, 4); // e.g., 2024

                                            $num_semesters = 0;
                                            if ($kh['ten_bac'] == 'Cao đẳng ngành') {
                                                $num_semesters = 6; // 3 năm, 6 học kỳ
                                            } elseif ($kh['ten_bac'] == 'Cao đẳng nghề') {
                                                $num_semesters = 5; // 2.5 năm, 5 học kỳ
                                            }

                                            if ($num_semesters > 0) {
                                                $academic_plan_str .= '<ul style="list-style-type: none; padding: 0; margin: 0;">';
                                                for ($i = 1; $i <= $num_semesters; $i++) {
                                                    $current_academic_year_start = $start_year_int + floor(($i - 1) / 2);
                                                    $current_academic_year_end = $current_academic_year_start + 1;
                                                    $academic_year_display = "{$current_academic_year_start}-{$current_academic_year_end}";
                                                    $academic_plan_str .= "<li>HK{$i} ({$academic_year_display})</li>";
                                                }
                                                $academic_plan_str .= '</ul>';
                                            } else {
                                                $academic_plan_str = 'Không xác định';
                                            }
                                            echo $academic_plan_str;
                                            ?>
                                        </td>
                                        <td class="action-cell">
                                            <a href="khoahoc.php?action=delete&id=<?php echo $kh['ma_khoahoc']; ?>" class="action-btn delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa khóa học này? Thao tác này không thể hoàn tác và có thể ảnh hưởng đến các dữ liệu liên quan (lớp học, sinh viên).');"><i class="fas fa-trash-alt"></i> Xóa</a>
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