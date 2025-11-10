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

// Xử lý khi form được submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy phần đầu của email và ghép với domain cố định
    $email_prefix = $_POST['email_prefix'];
    $email = $email_prefix . '@caothang.edu.vn';

    $ho_ten = $_POST['ho_ten'];
    $cccd = $_POST['cccd'];
    $ngay_bat_dau = !empty($_POST['ngay_bat_dau']) ? $_POST['ngay_bat_dau'] : null;

    // Tạo tài khoản giảng viên: email là username, cccd là password
    $username = $email;
    $password = $cccd;

    // Mã hóa password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Kiểm tra xem email đã tồn tại chưa
    $stmt = $conn->prepare("SELECT email FROM giangvien WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Email đã tồn tại. Vui lòng sử dụng email khác.";
        $_SESSION['message_type'] = "error";
    } else {
        // Bắt đầu một transaction để đảm bảo cả hai thao tác cùng thành công hoặc thất bại
        $conn->begin_transaction();

        try {
            // Lấy ma_khoa của quản trị viên đang đăng nhập
            $qtv_id = $_SESSION['user_id'];
            $stmt_khoa = $conn->prepare("SELECT ma_khoa FROM quantrivien WHERE ma_qtv = ?");
            $stmt_khoa->bind_param("i", $qtv_id);
            $stmt_khoa->execute();
            $result_khoa = $stmt_khoa->get_result();
            $qtv_info = $result_khoa->fetch_assoc();
            $ma_khoa = $qtv_info['ma_khoa'];
            $stmt_khoa->close();

            // 1. Thêm thông tin giảng viên vào bảng `giangvien`
            $stmt_gv = $conn->prepare("INSERT INTO giangvien (ho_ten, email, cccd, ngay_bat_dau, ma_khoa) VALUES (?, ?, ?, ?, ?)");
            $stmt_gv->bind_param("ssssi", $ho_ten, $email, $cccd, $ngay_bat_dau, $ma_khoa);
            $stmt_gv->execute();

            // Lấy ID của giảng viên vừa được thêm
            $giangvien_id = $conn->insert_id;

            // 2. Thêm thông tin tài khoản vào bảng `taikhoan`
            $role = 'GiangVien';
            $stmt_tk = $conn->prepare("INSERT INTO taikhoan (username, password, role, gv_id) VALUES (?, ?, ?, ?)");
            $stmt_tk->bind_param("sssi", $username, $hashed_password, $role, $giangvien_id);
            $stmt_tk->execute();

            // Nếu tất cả thành công, commit transaction
            $conn->commit();
            $_SESSION['message'] = "Thêm giảng viên và tạo tài khoản thành công!";
            $_SESSION['message_type'] = "success";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback(); // Hoàn tác nếu có lỗi
            $_SESSION['message'] = "Lỗi khi thêm giảng viên: " . $exception->getMessage();
            $_SESSION['message_type'] = "error";
        }
    }

    // Chuyển hướng về lại trang này để tránh lỗi "Form Resubmission"
    header("Location: them_giangvien.php");
    exit();
}


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Giảng Viên</title>
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

        /* CSS cho trường nhập email tùy chỉnh */
        .email-input-group {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .email-input-group input {
            border: none !important;
            box-shadow: none !important;
            flex-grow: 1;
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
                <!-- Form nhập thông tin giảng viên -->
                <div class="form-column form-container">
                    <h2 class="form-title">Thêm Giảng Viên Mới</h2>

                    <?php if (!empty($message)): ?>
                        <div class="message <?php echo $message_type; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form action="them_giangvien.php" method="POST" novalidate>
                        <div class="form-group">
                            <label for="ho_ten">Họ và tên</label>
                            <input type="text" id="ho_ten" name="ho_ten" required>
                        </div>
                        <div class="form-group">
                            <label for="email_prefix">Email</label>
                            <div class="email-input-group">
                                <input type="text" id="email_prefix" name="email_prefix" required placeholder="nhập phần đầu email">
                                <span>@caothang.edu.vn</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cccd">Căn cước công dân</label>
                            <input type="text" id="cccd" name="cccd" required>
                        </div>
                        <div class="form-group">
                            <label for="ngay_bat_dau">Ngày bắt đầu làm việc</label>
                            <input type="date" id="ngay_bat_dau" name="ngay_bat_dau">
                        </div>
                        <div class="form-note">
                            <i class="fas fa-info-circle"></i> Tên đăng nhập mặc định là <strong>email</strong> và mật khẩu là <strong>Căn cước công dân</strong>.
                        </div>
                        <input type="submit" value="Thêm Giảng Viên" class="btn form-submit-btn">
                    </form>
                </div>

                <!-- Danh sách giảng viên -->
   <!-- Các khối chức năng nhanh -->
        <div class="quick-links-container">
          <a href="danhsachgv.php" class="quick-link-box">
            <h3><i class="fa-solid fa-clipboard-list"></i>Danh sách giảng viên </h3>
            <p>Xem,sửa, xóa  giảng viên.</p>
          </a>
            </div>
        </main>
    </div>
</body>
</html>