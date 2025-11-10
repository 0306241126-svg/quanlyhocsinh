<?php
session_start();
require_once("../connect_db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin đăng nhập!";
    } else {
        // Kiểm tra tài khoản Sinh viên
        $stmt = $conn->prepare("SELECT sv.id, sv.mssv, tk.password, tk.status
                                FROM taikhoan tk
                                JOIN sinhvien sv ON tk.sv_id = sv.id
                                WHERE tk.username = ? AND tk.role = 'SinhVien'");
        if ($stmt === false) {
            $error = "Lỗi truy vấn cơ sở dữ liệu: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
 
            if ($result && $result->num_rows == 1) {
                $user = $result->fetch_assoc();
 
                // Kiểm tra mật khẩu và trạng thái tài khoản
                if (password_verify($password, $user['password'])) {
                    if ($user['status'] == 'active') {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = 'SinhVien';
                        header("Location: trangchusv.php");
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
    
            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập Sinh Viên</title>
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
      <h2 id="form-title">Đăng nhập Sinh Viên</h2>
      <p style="color:red; text-align:center; margin-bottom: 15px;">Sinh viên sử dụng MSSV làm tài khoản và CCCD là mật khẩu.</p>

      <?php if ($error != "") echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

      <form method="POST">
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
          <a href="../giangvien/logingv.php">Bạn là Giảng viên?</a>
        </div>
        <div style="text-align: center; margin-top: 15px; font-size: 13px; color: #555;">
          <p>Sinh viên quên mật khẩu vui lòng liên hệ bộ phận khoa.</p>
        </div>
      </form>
    </div>

    <div class="right-panel">
      <img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo">
    </div>
  </div>
</body>
</html>
