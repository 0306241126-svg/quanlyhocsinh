<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'GiangVien') {
    header("Location: logingv.php");
    exit();
}

include("../connect_db.php");

$giangvien_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Lấy danh sách lớp mà giảng viên được phân công
$sql_lop = "SELECT DISTINCT l.ma_lop, l.ten_lop
            FROM phancong pc
            JOIN lophoc l ON pc.ma_lop = l.ma_lop
            WHERE pc.ma_gv = ?
            ORDER BY l.ten_lop";
$stmt_lop = $conn->prepare($sql_lop);
$stmt_lop->bind_param("i", $giangvien_id);
$stmt_lop->execute();
$lop_result = $stmt_lop->get_result();
$lop_list = $lop_result->fetch_all(MYSQLI_ASSOC);
$stmt_lop->close();

// Xử lý khi form được gửi đi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ma_lop = $_POST['ma_lop'];
    $tieu_de = $_POST['tieu_de'];
    $noi_dung = $_POST['noi_dung'];
    $file_path = null;

    // Xử lý file đính kèm
    if (isset($_FILES['file_dinhkem']) && $_FILES['file_dinhkem']['error'] == 0) {
        $upload_dir = '../uploads/attachments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['file_dinhkem']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['file_dinhkem']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        } else {
            $message = "Lỗi khi tải file lên.";
            $message_type = "error";
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO thongbao (ma_gv, ma_lop, tieu_de, noi_dung, file_dinhkem) VALUES (?, ?, ?, ?, ?)");
        // Thêm cột file_dinhkem vào câu lệnh INSERT
        $stmt->bind_param("iisss", $giangvien_id, $ma_lop, $tieu_de, $noi_dung, $file_path); 
        if ($stmt->execute()) {
            $_SESSION['message'] = "Gửi thông báo thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Lỗi khi gửi thông báo: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }

    // Chuyển hướng để tránh gửi lại form khi làm mới trang
    header("Location: thongbao.php");
    exit();
}

// Xử lý yêu cầu xóa thông báo
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $ma_tb_to_delete = (int)$_GET['id'];

    // Kiểm tra bảo mật: Đảm bảo thông báo này thuộc về giảng viên đang đăng nhập
    $stmt_check = $conn->prepare("SELECT file_dinhkem FROM thongbao WHERE ma_tb = ? AND ma_gv = ?");
    $stmt_check->bind_param("ii", $ma_tb_to_delete, $giangvien_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 1) {
        $notification_to_delete = $result_check->fetch_assoc();

        // Xóa file đính kèm nếu có
        if (!empty($notification_to_delete['file_dinhkem']) && file_exists($notification_to_delete['file_dinhkem'])) {
            unlink($notification_to_delete['file_dinhkem']);
        }

        // Xóa thông báo khỏi cơ sở dữ liệu
        $stmt_delete = $conn->prepare("DELETE FROM thongbao WHERE ma_tb = ?");
        $stmt_delete->bind_param("i", $ma_tb_to_delete);
        if ($stmt_delete->execute()) {
            $_SESSION['message'] = "Xóa thông báo thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Lỗi khi xóa thông báo.";
            $_SESSION['message_type'] = "error";
        }
        $stmt_delete->close();
    }
    $stmt_check->close();

    header("Location: thongbao.php");
    exit();
}

// Lấy danh sách các thông báo đã gửi
$sent_notifications = [];
$sql_sent = "SELECT 
                tb.ma_tb, tb.tieu_de, tb.noi_dung, tb.ngay_gui, tb.file_dinhkem, 
                l.ten_lop 
             FROM thongbao tb
             JOIN lophoc l ON tb.ma_lop = l.ma_lop
             WHERE tb.ma_gv = ?
             ORDER BY tb.ngay_gui DESC";
$stmt_sent = $conn->prepare($sql_sent);
$stmt_sent->bind_param("i", $giangvien_id);
$stmt_sent->execute();
$result_sent = $stmt_sent->get_result();
if ($result_sent->num_rows > 0) {
    $sent_notifications = $result_sent->fetch_all(MYSQLI_ASSOC);
}
$stmt_sent->close();

// Lấy thông báo từ session để hiển thị
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gửi Thông Báo</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Đảm bảo textarea có style giống các input khác */
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        /* Style cho danh sách thông báo đã gửi */
        .sent-notifications-container { margin-top: 40px; }
        .notification-list { display: flex; flex-direction: column; gap: 20px; }
        .notification-item { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #6c757d; }
        .notification-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .notification-title { font-size: 1.1rem; font-weight: 600; color: #333; }
        .notification-meta { font-size: 0.9rem; color: #666; }
        .notification-content { margin-bottom: 15px; line-height: 1.6; }
        .notification-attachment a { text-decoration: none; color: #007bff; font-weight: 500; }
    </style>
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo-container"><a href="trangchugv.php"><img src="../img/ChatGPT Image 21_18_32 16 thg 10, 2025.png" alt="Logo"></a></div>
      <nav class="main-menu">
        <ul>
            <li><a href="trangchugv.php"><i class="fas fa-home"></i>Trang chủ</a></li>
            <li><a href="phanconggv.php"><i class="fas fa-tasks"></i>Phân công giảng dạy</a></li>
            <li><a href="nhapdiem.php"><i class="fas fa-marker"></i>Nhập điểm</a></li>
            <li><a href="diemdanh.php"><i class="fas fa-calendar-check"></i>Điểm danh</a></li>
            <li class="active"><a href="thongbao.php"><i class="fas fa-bell"></i>Gửi thông báo</a></li>
            <li><a href="danhsach_sv.php"><i class="fas fa-users"></i>Danh sách sinh viên</a></li>
        </ul>
      </nav>
        <div class="sidebar-footer"><a href="logoutsv.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a></div>
    </aside>

    <main class="content full-width-content">
        <h1 class="welcome-message">Gửi Thông Báo Mới</h1>

        <div class="form-container" style="max-width: 1000px; margin: 20px auto;">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="thongbao.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="ma_lop">Gửi đến lớp</label>
                    <select id="ma_lop" name="ma_lop" required>
                        <option value="">-- Chọn lớp --</option>
                        <?php foreach ($lop_list as $lop): ?>
                            <option value="<?php echo $lop['ma_lop']; ?>"><?php echo htmlspecialchars($lop['ten_lop']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" >
                    <label for="tieu_de">Tiêu đề</label>
                    <input type="text" id="tieu_de" name="tieu_de" required>
                </div>
                <div class="form-group"  >
                    <label for="noi_dung">Nội dung</label>
                    <textarea id="noi_dung" name="noi_dung" rows="8" required></textarea>
                </div>
                <div class="form-group">
                    <label for="file_dinhkem">File đính kèm (tùy chọn)</label>
                    <input type="file" id="file_dinhkem" name="file_dinhkem">
                </div>
                <button type="submit" class="btn form-submit-btn"><i class="fas fa-paper-plane"></i> Gửi Thông Báo</button>
            </form>
        </div>

        <!-- Danh sách thông báo đã gửi -->
        <div class="sent-notifications-container form-container" style="max-width: 1000px; margin: 40px auto;">
            <h2 class="form-title">Lịch Sử Thông Báo Đã Gửi</h2>
            <div class="notification-list">
                <?php if (empty($sent_notifications)): ?>
                    <div class="message">Bạn chưa gửi thông báo nào.</div>
                <?php else: ?>
                    <?php foreach ($sent_notifications as $tb): ?>
                        <div class="notification-item">
                            <div class="notification-header">
                                <h3 class="notification-title"><?php echo htmlspecialchars($tb['tieu_de']); ?></h3>
                                <div class="notification-meta">
                                    Gửi đến lớp: <strong><?php echo htmlspecialchars($tb['ten_lop']); ?></strong> | <?php echo date('d/m/Y H:i', strtotime($tb['ngay_gui'])); ?>
                                </div>
                            </div>
                            <div class="notification-content">
                                <p><?php echo nl2br(htmlspecialchars($tb['noi_dung'])); ?></p>
                            </div>
                            <?php if (!empty($tb['file_dinhkem'])): ?>
                                <div class="notification-attachment">
                                    <i class="fas fa-paperclip"></i> <a href="<?php echo htmlspecialchars($tb['file_dinhkem']); ?>" target="_blank">Xem tệp đính kèm</a>
                                </div>
                            <?php endif; ?>
                            <div style="text-align: right; margin-top: 10px;">
                                <a href="thongbao.php?action=delete&id=<?php echo $tb['ma_tb']; ?>" class="action-btn delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa thông báo này không?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>
</body>
</html>