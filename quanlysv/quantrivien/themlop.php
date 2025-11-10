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

// Lấy thông báo từ session nếu có
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Endpoint API để lấy học kỳ theo bậc đào tạo
if (isset($_GET['get_hocky_by_bac']) && isset($_GET['ma_bac'])) {
    header('Content-Type: application/json');
    $ma_bac = (int)$_GET['ma_bac'];
    
    $stmt = $conn->prepare("SELECT ma_hocky, ten_hocky FROM hocky WHERE ma_bac = ? ORDER BY ten_hocky ASC");
    $stmt->bind_param("i", $ma_bac);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    
    $conn->close();
    exit();
}

// Endpoint API để lấy thông tin khóa học (năm bắt đầu, bậc đào tạo)
if (isset($_GET['get_khoahoc_info']) && isset($_GET['ma_khoahoc'])) {
    header('Content-Type: application/json');
    $ma_khoahoc = (int)$_GET['ma_khoahoc'];
    
    $stmt = $conn->prepare("SELECT kh.ma_bac, nh.ten_namhoc 
                            FROM khoahoc kh 
                            JOIN namhoc nh ON kh.ma_namhoc = nh.ma_namhoc
                            WHERE kh.ma_khoahoc = ?");
    $stmt->bind_param("i", $ma_khoahoc);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_assoc());
    
    $conn->close();
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

if ($ma_khoa_admin === null) {
    // Xử lý trường hợp không tìm thấy khoa cho quản trị viên
    die("Lỗi: Không thể xác định khoa của quản trị viên.");
}

// Xử lý yêu cầu xóa lớp học
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['ma_lop'])) {
    $ma_lop_to_delete = (int)$_GET['ma_lop'];

    // Bắt đầu transaction
    $conn->begin_transaction();
    try {
        // Chỉ cho phép xóa lớp học thuộc khoa của quản trị viên để bảo mật
        $stmt_delete = $conn->prepare("DELETE FROM lophoc WHERE ma_lop = ? AND ma_khoa = ?");
        $stmt_delete->bind_param("ii", $ma_lop_to_delete, $ma_khoa_admin);
        $stmt_delete->execute();

        if ($stmt_delete->affected_rows > 0) {
            $_SESSION['message'] = "Xóa lớp học thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Không tìm thấy lớp học để xóa hoặc bạn không có quyền.";
            $_SESSION['message_type'] = "error";
        }
        $stmt_delete->close();
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Lỗi khi xóa lớp học. Lớp có thể đang được sử dụng.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: themlop.php");
    exit();
}

// Xử lý form khi submit để thêm lớp học
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_lop = $_POST['ten_lop'];
    $ma_khoahoc = $_POST['ma_khoahoc'];
    $ma_hocky = $_POST['ma_hocky']; // Lấy ma_hocky từ form

    if (empty($ten_lop) || empty($ma_khoahoc) || empty($ma_hocky)) {
        $_SESSION['message'] = "Vui lòng điền đầy đủ thông tin.";
        $_SESSION['message_type'] = "error";
    } else {
        // --- Tự động xác định Năm học ---
        $ma_namhoc = null;
        $stmt_kh_info = $conn->prepare("SELECT nh.ten_namhoc FROM khoahoc kh JOIN namhoc nh ON kh.ma_namhoc = nh.ma_namhoc WHERE kh.ma_khoahoc = ?");
        $stmt_kh_info->bind_param("i", $ma_khoahoc);
        $stmt_kh_info->execute();
        $kh_info = $stmt_kh_info->get_result()->fetch_assoc();
        $start_year_str = $kh_info['ten_namhoc'];

        $stmt_hk_info = $conn->prepare("SELECT ten_hocky FROM hocky WHERE ma_hocky = ?");
        $stmt_hk_info->bind_param("i", $ma_hocky);
        $stmt_hk_info->execute();
        $hk_info = $stmt_hk_info->get_result()->fetch_assoc();
        $semester_number = (int)filter_var($hk_info['ten_hocky'], FILTER_SANITIZE_NUMBER_INT);

        $start_year_int = (int)substr($start_year_str, 0, 4);
        $current_academic_year_start = $start_year_int + floor(($semester_number - 1) / 2);
        $ten_namhoc_calculated = "{$current_academic_year_start}-" . ($current_academic_year_start + 1);

        // Lấy hoặc tạo ma_namhoc từ ten_namhoc_calculated
        $stmt_get_nh = $conn->prepare("SELECT ma_namhoc FROM namhoc WHERE ten_namhoc = ?");
        $stmt_get_nh->bind_param("s", $ten_namhoc_calculated);
        $stmt_get_nh->execute();
        $nh_result = $stmt_get_nh->get_result();
        if ($nh_row = $nh_result->fetch_assoc()) {
            $ma_namhoc = $nh_row['ma_namhoc'];
        } else {
            $stmt_insert_nh = $conn->prepare("INSERT INTO namhoc (ten_namhoc) VALUES (?)");
            $stmt_insert_nh->bind_param("s", $ten_namhoc_calculated);
            $stmt_insert_nh->execute();
            $ma_namhoc = $conn->insert_id;
        }
        // --- Kết thúc tự động xác định Năm học ---

        // Kiểm tra xem lớp học đã tồn tại trong khoa, năm học và học kỳ này chưa
        $stmt_check = $conn->prepare("SELECT ma_lop FROM lophoc WHERE ten_lop = ? AND ma_namhoc = ? AND ma_hocky = ? AND ma_khoa = ?");
        $stmt_check->bind_param("siii", $ten_lop, $ma_namhoc, $ma_hocky, $ma_khoa_admin);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['message'] = "Lớp học này đã tồn tại trong năm học và học kỳ đã chọn.";
            $_SESSION['message_type'] = "error";
        } else {
            // Thêm lớp học mới
            $stmt_insert = $conn->prepare("INSERT INTO lophoc (ten_lop, ma_khoahoc, ma_namhoc, ma_khoa, ma_hocky) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("siiii", $ten_lop, $ma_khoahoc, $ma_namhoc, $ma_khoa_admin, $ma_hocky);
            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Thêm lớp học thành công!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Lỗi khi thêm lớp học: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    header("Location: themlop.php");
    exit();
}

// Lấy danh sách khóa học và năm học cho form
// Lọc danh sách khóa học theo khoa của quản trị viên
$khoahoc_form_list_query = $conn->prepare("SELECT ma_khoahoc, ten_khoahoc FROM khoahoc WHERE ma_khoa = ? ORDER BY ten_khoahoc DESC");
$khoahoc_form_list_query->bind_param("i", $ma_khoa_admin);
$khoahoc_form_list_query->execute();
$khoahoc_form_list = $khoahoc_form_list_query->get_result()->fetch_all(MYSQLI_ASSOC);

$bacdaotao_list = $conn->query("SELECT ma_bac, ten_bac FROM bacdaotao ORDER BY ten_bac ASC")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách lớp học đã tạo thuộc khoa của quản trị viên
$grouped_lophoc = [];
$sql_lophoc = "SELECT l.ma_lop, l.ten_lop, kh.ten_khoahoc, nh.ten_namhoc, k.ten_khoa, hk.ten_hocky
               FROM lophoc l
               LEFT JOIN khoahoc kh ON l.ma_khoahoc = kh.ma_khoahoc
               LEFT JOIN namhoc nh ON l.ma_namhoc = nh.ma_namhoc
               LEFT JOIN hocky hk ON l.ma_hocky = hk.ma_hocky
               LEFT JOIN khoa k ON l.ma_khoa = k.ma_khoa
               WHERE l.ma_khoa = ?
               ORDER BY kh.ten_khoahoc DESC, nh.ten_namhoc ASC, hk.ma_hocky ASC, l.ten_lop ASC";
$stmt_lophoc = $conn->prepare($sql_lophoc);
$stmt_lophoc->bind_param("i", $ma_khoa_admin);
$stmt_lophoc->execute();
$result_lophoc = $stmt_lophoc->get_result();
if ($result_lophoc->num_rows > 0) {
    // Nhóm các lớp học theo năm học và sau đó theo khóa học
    while ($row = $result_lophoc->fetch_assoc()) { // Nhóm theo Khóa học -> Năm học -> Học kỳ
        $grouped_lophoc[$row['ten_khoahoc']][$row['ten_namhoc']][$row['ten_hocky']][] = $row;
    }
}
$stmt_lophoc->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Lớp Học</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content-columns { display: flex; gap: 30px; width: 100%; align-items: flex-start; }
        .form-column { flex: 1; max-width: 500px; }
        .list-column { flex: 2; min-width: 50%; }
        .year-group {
            margin-bottom: 40px;
        }
        .year-title {
            font-size: 1.5rem; color: #333; margin-bottom: 15px;
            border-bottom: 2px solid #28a745; padding-bottom: 5px;
        }
        .course-group {
            margin-bottom: 30px;
            padding-left: 20px;
        }
        .course-title {
            font-size: 1.2rem; color: #555; margin-bottom: 10px;
            font-weight: 600;
        }
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
                <!-- Form thêm lớp học -->
                <div class="form-column form-container">
                    <h2 class="form-title">Thêm Lớp Học</h2>
                    <?php if (!empty($message)): ?>
                        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="themlop.php" method="POST">
                        <div class="form-group">
                            <label for="ma_khoahoc">Khóa học</label>
                            <select id="ma_khoahoc" name="ma_khoahoc" required>
                                <option value="">-- Chọn khóa học --</option>
                                <?php foreach ($khoahoc_form_list as $kh): ?>
                                    <option value="<?php echo $kh['ma_khoahoc']; ?>"><?php echo htmlspecialchars($kh['ten_khoahoc']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ma_hocky">Học kỳ</label>
                            <select id="ma_hocky" name="ma_hocky" required disabled>
                                <option value="">-- Chọn khóa học trước --</option>
                                <!-- Các học kỳ sẽ được tải bằng JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ten_lop">Tên lớp học</label>
                            <input type="text" id="ten_lop" name="ten_lop" required placeholder="Ví dụ: CD22CT111">
                        </div>
                        <input type="submit" value="Thêm Lớp Học" class="btn form-submit-btn">
                    </form>
                </div>

                <!-- Danh sách lớp học đã tạo -->
                <div class="list-column table-container">
                    <h2 style="text-align: center; margin-bottom: 20px;">Danh Sách Lớp Học</h2>
                    <?php if (empty($grouped_lophoc)): ?>
                        <p style="text-align: center; color: #666;">Chưa có lớp học nào được tạo.</p>
                    <?php else: ?>
                        <?php foreach ($grouped_lophoc as $ten_khoahoc => $years_in_course): ?>
                            <div class="year-group"> <!-- Sử dụng year-group cho khóa học -->
                                <h3 class="year-title">Khóa: <?php echo htmlspecialchars($ten_khoahoc); ?></h3>
                                <?php foreach ($years_in_course as $ten_namhoc => $semesters_in_year): ?>
                                    <div class="course-group">
                                        <h4 class="course-title">Năm học: <?php echo htmlspecialchars($ten_namhoc); ?></h4>
                                        <?php foreach ($semesters_in_year as $ten_hocky => $lophoc_list): ?>
                                            <div style="padding-left: 20px;">
                                                <h5 style="font-size: 1.1rem; color: #666; margin-bottom: 10px;"><?php echo htmlspecialchars($ten_hocky); ?></h5>
                                                <table class="data-table">
                                                    <thead><tr><th>STT</th><th>Tên lớp</th><th>Hành động</th></tr></thead>
                                                    <tbody>
                                                        <?php foreach ($lophoc_list as $index => $lop): ?>
                                                            <tr>
                                                                <td><?php echo $index + 1; ?></td>
                                                                <td><?php echo htmlspecialchars($lop['ten_lop']); ?></td>
                                                                <td class="action-cell">
                                                                    <a href="themsvlop.php?ma_lop=<?php echo $lop['ma_lop']; ?>" class="action-btn view-btn"><i class="fas fa-user-plus"></i> Thêm SV</a>
                                                                    <a href="themlop.php?action=delete&ma_lop=<?php echo $lop['ma_lop']; ?>" class="action-btn delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa lớp học này? Thao tác này không thể hoàn tác.');"><i class="fas fa-trash-alt"></i> Xóa</a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const khoahocSelect = document.getElementById('ma_khoahoc');
        const hockySelect = document.getElementById('ma_hocky');
        const tenLopInput = document.getElementById('ten_lop');

        khoahocSelect.addEventListener('change', function() {
            const maKhoahoc = this.value;

            // Vô hiệu hóa và reset các dropdown phụ thuộc
            hockySelect.disabled = true;
            hockySelect.innerHTML = '<option value="">-- Chọn khóa học trước --</option>';

            if (!maKhoahoc) {
                return;
            }

            hockySelect.innerHTML = '<option value="">-- Đang tải... --</option>';

            // Lấy thông tin của khóa học (bậc đào tạo) để tải học kỳ
            fetch(`themlop.php?get_khoahoc_info=1&ma_khoahoc=${maKhoahoc}`)
                .then(response => response.json())
                .then(khoahocInfo => {
                    if (khoahocInfo && khoahocInfo.ma_bac) {
                        // Dùng ma_bac để tải danh sách học kỳ
                        return fetch(`themlop.php?get_hocky_by_bac=1&ma_bac=${khoahocInfo.ma_bac}`);
                    }
                    throw new Error('Không tìm thấy thông tin bậc đào tạo cho khóa học.');
                })
                .then(response => response.json())
                .then(hockyData => {
                    hockySelect.disabled = false;
                    hockySelect.innerHTML = '<option value="">-- Chọn học kỳ --</option>';
                    if (hockyData.length > 0) {
                        hockyData.forEach(hocky => {
                            const option = document.createElement('option');
                            option.value = hocky.ma_hocky;
                            option.textContent = hocky.ten_hocky;
                            hockySelect.appendChild(option);
                        });
                    } else {
                        hockySelect.innerHTML = '<option value="">-- Không có học kỳ cho bậc này --</option>';
                    }
                })
                .catch(error => {
                    console.error('Lỗi khi tải dữ liệu:', error);
                    hockySelect.innerHTML = '<option value="">-- Lỗi khi tải --</option>';
                });
        });
    });
    </script>
</body>
</html>