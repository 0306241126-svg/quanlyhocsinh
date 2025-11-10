<?php
$password_to_hash = '';
$hashed_password = '';

// Kiểm tra xem form đã được gửi đi chưa
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['password_input'])) {
    $password_to_hash = $_POST['password_input'];
    $hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT); // Mã hóa mật khẩu
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo Mật Khẩu Mã Hóa</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        input[type="text"], textarea { width: 100%; padding: 10px; margin-bottom: 10px; font-size: 1rem; box-sizing: border-box; }
        textarea { height: 100px; }
        input[type="submit"] { padding: 10px 20px; font-size: 1rem; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Công cụ tạo mật khẩu mã hóa</h1>
        <p>Nhập mật khẩu gốc (ví dụ: số CCCD) vào ô bên dưới và nhấn nút "Tạo mã".</p>
        
        <form action="tao_matkhau_mahoa.php" method="POST">
            <label for="password_input">Mật khẩu gốc:</label>
            <input type="text" id="password_input" name="password_input" value="<?php echo htmlspecialchars($password_to_hash); ?>" placeholder="Nhập mật khẩu cần mã hóa...">
            <input type="submit" value="Tạo mã">
        </form>

        <p><strong>Mật khẩu đã mã hóa (để copy vào database):</strong></p>
        <textarea readonly onclick="this.select();"><?php echo $hashed_password; ?></textarea>
    </div>
</body>
</html>