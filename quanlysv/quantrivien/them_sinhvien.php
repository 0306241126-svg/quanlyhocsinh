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

// Xử lý khi người dùng chọn hoặc thay đổi khóa học
if (isset($_POST['set_khoahoc'])) {
    $_SESSION['selected_khoahoc_id'] = $_POST['ma_khoahoc'];
    header("Location: them_sinhvien.php");
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
// Lấy danh sách khóa học cho dropdown
$khoahoc_list = $conn->query("SELECT ma_khoahoc, ten_khoahoc FROM khoahoc ORDER BY ten_khoahoc DESC")->fetch_all(MYSQLI_ASSOC);

// Lấy thông tin khóa học đã chọn từ session
$selected_khoahoc_id = $_SESSION['selected_khoahoc_id'] ?? null;
$selected_khoahoc_ten = '';
if ($selected_khoahoc_id) {
    $stmt_kh = $conn->prepare("SELECT ten_khoahoc FROM khoahoc WHERE ma_khoahoc = ?");
    $stmt_kh->bind_param("i", $selected_khoahoc_id);
    $stmt_kh->execute();
    $selected_khoahoc_ten = $stmt_kh->get_result()->fetch_assoc()['ten_khoahoc'] ?? '';
}

// Xử lý khi form được submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $mssv = $_POST['mssv'];
    $ho_ten = $_POST['ho_ten'];
    $cccd = $_POST['cccd'];

    // Tạo email, username và password theo yêu cầu
    $email = $mssv . '@caothang.edu.vn';
    $username = $mssv;
    $password = $cccd;

    if (!$selected_khoahoc_id) {
        $_SESSION['message'] = "Vui lòng chọn khóa học trước khi thêm sinh viên.";
        $_SESSION['message_type'] = "error";
        header("Location: them_sinhvien.php");
        exit();
    }
    // Mã hóa password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Kiểm tra xem MSSV hoặc email đã tồn tại chưa
    $stmt = $conn->prepare("SELECT mssv FROM sinhvien WHERE mssv = ? OR email = ?");
    $stmt->bind_param("ss", $mssv, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message'] = "MSSV hoặc Email đã tồn tại. Vui lòng kiểm tra lại.";
        $_SESSION['message_type'] = "error";
    } else {
        // Bắt đầu một transaction để đảm bảo cả hai thao tác cùng thành công hoặc thất bại
        $conn->begin_transaction();

        try {
            // 1. Thêm thông tin sinh viên vào bảng `sinhvien` cùng với ma_khoahoc
            $stmt_sv = $conn->prepare("INSERT INTO sinhvien (mssv, ho_ten, email, cccd, ma_khoahoc, ma_khoa) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_sv->bind_param("ssssii", $mssv, $ho_ten, $email, $cccd, $selected_khoahoc_id, $ma_khoa_admin);
            $stmt_sv->execute();

            // Lấy ID của sinh viên vừa được thêm
            $sinhvien_id = $conn->insert_id;

            // 2. Thêm thông tin tài khoản vào bảng `taikhoan`
            $role = 'SinhVien';
            $stmt_tk = $conn->prepare("INSERT INTO taikhoan (username, password, role, sv_id) VALUES (?, ?, ?, ?)");
            $stmt_tk->bind_param("sssi", $username, $hashed_password, $role, $sinhvien_id);
            $stmt_tk->execute();

            // Nếu tất cả thành công, commit transaction
            $conn->commit();
            $_SESSION['message'] = "Thêm sinh viên và tạo tài khoản thành công!";
            $_SESSION['message_type'] = "success";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback(); // Hoàn tác nếu có lỗi
            $_SESSION['message'] = "Lỗi khi thêm sinh viên: " . $exception->getMessage();
            $_SESSION['message_type'] = "error";
        }
    }

    // Chuyển hướng về lại trang này để tránh lỗi "Form Resubmission"
    header("Location: them_sinhvien.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content-columns {
            display: flex;
            gap: 30px;
            width: 100%;
            align-items: flex-start;
        }
        .form-column {
            flex: 1;
            max-width: 600px;
        }
        .list-column {
            flex: 2;
        }
        .form-note {
            font-size: 13px;
            color: #555;
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background-color: #f0f4f8;
            border-radius: 6px;
            border-left: 3px solid #3b7ddd;
        }
        .email-display {
            padding: 12px 15px;
            background-color: #e9ecef;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-family: monospace;
            color: #495057;
        }
    </style>
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

        <!-- NỘI DUNG CHÍNH -->
        <main class="content">
            <div class="main-content-columns">
                <!-- Form nhập thông tin sinh viên -->
                <div class="form-column form-container">
                    <h2 class="form-title">Thêm Sinh Viên Mới</h2>

                    <?php if (!empty($message)): ?>
                        <div class="message <?php echo $message_type; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form chọn khóa học -->
                    <form action="them_sinhvien.php" method="POST" style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #eee;">
                        <div class="form-group">
                            <label for="ma_khoahoc">Chọn Khóa Học</label>
                            <select id="ma_khoahoc" name="ma_khoahoc" required onchange="this.form.submit()">
                                <option value="">-- Vui lòng chọn --</option>
                                <?php foreach ($khoahoc_list as $kh): ?>
                                    <option value="<?php echo $kh['ma_khoahoc']; ?>" <?php echo ($selected_khoahoc_id == $kh['ma_khoahoc']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kh['ten_khoahoc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="set_khoahoc" value="1">
                    </form>

                    <!-- Form thêm sinh viên (chỉ hiện khi đã chọn khóa) -->
                    <?php if ($selected_khoahoc_id): ?>
                        <form action="them_sinhvien.php" method="POST" novalidate>
                            <div class="form-group">
                                <label for="mssv">Mã số sinh viên (MSSV)</label>
                                <input type="text" id="mssv" name="mssv" required onkeyup="updateEmail()">
                            </div>
                            <div class="form-group">
                                <label for="ho_ten">Họ và tên</label>
                                <input type="text" id="ho_ten" name="ho_ten" required>
                            </div>
                            <div class="form-group">
                                <label for="email_display">Email (tự động tạo)</label>
                                <div id="email_display" class="email-display">...</div>
                            </div>
                            <div class="form-group">
                                <label for="cccd">Căn cước công dân</label>
                                <input type="text" id="cccd" name="cccd" required>
                            </div>
                            <div class="form-note">
                                <i class="fas fa-info-circle"></i> Tên đăng nhập mặc định là <strong>MSSV</strong> và mật khẩu là <strong>Căn cước công dân</strong>.
                            </div>
                            <input type="submit" name="add_student" value="Thêm Sinh Viên vào Khóa '<?php echo htmlspecialchars($selected_khoahoc_ten); ?>'" class="btn form-submit-btn">
                        </form>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">Vui lòng chọn khóa học để bắt đầu thêm sinh viên.</p>
                    <?php endif; ?>
                </div>

                <!-- Các khối chức năng nhanh -->
                <div class="quick-links-container" style="margin-top: 0;">
                    <a href="danhsachsv.php" class="quick-link-box">
                        <h3><i class="fa-solid fa-clipboard-list"></i>Danh sách sinh viên</h3>
                        <p>Xem, sửa, và xóa thông tin sinh viên hiện có trong hệ thống.</p>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateEmail() {
            const mssv = document.getElementById('mssv').value;
            const emailDisplay = document.getElementById('email_display');
            if (mssv) {
                emailDisplay.textContent = mssv + '@caothang.edu.vn';
            } else {
                emailDisplay.textContent = '...';
            }
        }
    </script>
</body>
</html>