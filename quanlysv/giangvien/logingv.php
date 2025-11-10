<?php
session_start();
require_once("../connect_db.php"); // Đảm bảo file connect_db.php đã được tạo

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin đăng nhập!";
    } else {
        // 1. Kiểm tra xem có phải là Quản trị viên không
        $stmt_admin = $conn->prepare("SELECT * FROM quantrivien WHERE ten_dang_nhap = ?");
        if ($stmt_admin) {
            $stmt_admin->bind_param("s", $username);
            $stmt_admin->execute();
            $result_admin = $stmt_admin->get_result();
 
            if ($result_admin->num_rows == 1) {
                $admin_account = $result_admin->fetch_assoc();
                // So sánh trực tiếp mật khẩu (giả sử mật khẩu admin không được hash)
                if ($password === $admin_account['mat_khau']) {
                    $_SESSION['user_id'] = $admin_account['ma_qtv'];
                    $_SESSION['username'] = $admin_account['ten_dang_nhap'];
                    $_SESSION['role'] = 'QuanTriVien';
                    header("Location: ../quantrivien/trangchu.php");
                    exit();
                } else {
                    $error = "Sai tài khoản hoặc mật khẩu!";
                }
            }
            $stmt_admin->close();
        }

        // 2. Nếu không phải Quản trị viên, kiểm tra xem có phải là Giảng viên không
        if (empty($_SESSION['user_id'])) { // Chỉ kiểm tra nếu chưa đăng nhập thành công
            $stmt_gv = $conn->prepare("SELECT * FROM taikhoan WHERE username = ? AND role = 'GiangVien'");
            if ($stmt_gv) {
                $stmt_gv->bind_param("s", $username);
                $stmt_gv->execute();
                $result_gv = $stmt_gv->get_result();
                if ($result_gv->num_rows == 1) {
                    $gv_account = $result_gv->fetch_assoc();
                    if (password_verify($password, $gv_account['password'])) {
                        if ($gv_account['status'] == 'active') {
                            $_SESSION['user_id'] = $gv_account['gv_id'];
                            $_SESSION['username'] = $gv_account['username'];
                            $_SESSION['role'] = 'GiangVien';
                            header("Location: trangchugv.php");
                            exit();
                        } else {
                            $error = "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.";
                        }
                    } else {
                        $error = "Sai tài khoản hoặc mật khẩu!";
                    }
                } else {
                    $error = "Sai tài khoản hoặc mật khẩu!";
                }
                $stmt_gv->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập Cán bộ</title>
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet">
  <link rel="stylesheet" href="../css/hinhnen.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div id="stars"></div>
  <div id="stars2"></div>
  <div id="stars3"></div>

  <div class="wrapper">
    <div class="container">
      <h2 id="form-title">Đăng nhập Giảng Viên </h2>

      <?php if ($error != "") echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

      <form method="POST" action="logingv.php">
        <div class="form-group">
          <label for="username">Tên đăng nhập</label>
          <div class="input-icon">
            <span class="icon"><i class="fa fa-user"></i></span>
            <input type="text" name="username" id="username" placeholder="Nhập tên đăng nhập" required>
          </div>
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu</label>
          <div class="input-icon">
            <span class="icon"><i class="fa fa-lock"></i></span>
            <input type="password" name="password" id="password" placeholder="Nhập mật khẩu" required>
          </div>
        </div>
        <button type="submit" class="btn">Đăng nhập</button>

        <div class="links">
          <a href="../sinhvien/login_sinhvien.php">Bạn là Sinh viên?</a>
        </div>
        <div style="text-align: center; margin-top: 15px; font-size: 13px; color: #555;">
          <p>Đăng nhập bằng tài khoản khoa cấp, quên mật khẩu vui lòng liên hệ khoa.</p>
        </div>
      </form>
    </div>

    <div class="right-panel">
      <img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo">
    </div>
  </div>
</body>
</html>
