<?php
session_start();

// Ngăn trình duyệt cache trang
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kiểm tra nếu người dùng chưa đăng nhập hoặc không phải là Sinh Viên
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SinhVien') {
    header("Location: login_sinhvien.php");
    exit();
}

include("../connect_db.php");

$sinhvien_id = $_SESSION['user_id'];
$notifications = [];

// 1. Lấy danh sách các lớp học của sinh viên
$stmt_lsv = $conn->prepare("SELECT ma_lop FROM lop_sinhvien WHERE id_sv = ?");
$stmt_lsv->bind_param("i", $sinhvien_id);
$stmt_lsv->execute();
$result_lsv = $stmt_lsv->get_result();
$ma_lop_list = [];
while ($row = $result_lsv->fetch_assoc()) {
    $ma_lop_list[] = $row['ma_lop'];
}
$stmt_lsv->close();

// 2. Nếu sinh viên có tham gia lớp học, lấy các thông báo
if (!empty($ma_lop_list)) {
    // Tạo chuỗi placeholder cho câu lệnh IN
    $placeholders = implode(',', array_fill(0, count($ma_lop_list), '?'));
    $types = str_repeat('i', count($ma_lop_list));

    $sql_notifications = "
        SELECT 
            tb.tieu_de,
            tb.noi_dung,
            tb.file_dinhkem, 
            tb.ngay_gui,
            gv.ho_ten AS ten_giang_vien,
            l.ten_lop
        FROM thongbao tb
        JOIN giangvien gv ON tb.ma_gv = gv.ma_gv
        JOIN lophoc l ON tb.ma_lop = l.ma_lop
        WHERE tb.ma_lop IN ($placeholders)
        ORDER BY tb.ngay_gui DESC
    ";

    $stmt_notifications = $conn->prepare($sql_notifications);
    $stmt_notifications->bind_param($types, ...$ma_lop_list);
    $stmt_notifications->execute();
    $result_notifications = $stmt_notifications->get_result();
    $notifications = $result_notifications->fetch_all(MYSQLI_ASSOC);
    $stmt_notifications->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hộp Thư Thông Báo</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notification-list { 
            display: flex; flex-direction: column; gap: 20px;
            max-width: 1000px; /* Giới hạn chiều rộng của danh sách */
            margin: 20px auto; /* Căn giữa danh sách */
        }
        .notification-item {
            background: #fff; border-radius: 8px; padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #3b7ddd;
        }
        .notification-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .notification-title { font-size: 1.2rem; font-weight: 600; color: #333; }
        .notification-meta { font-size: 0.9rem; color: #666; }
        .notification-meta .sender { font-weight: bold; }
        .notification-content { margin-bottom: 15px; line-height: 1.6; }
        .notification-attachment a {
            text-decoration: none; color: #007bff; font-weight: 500;
            transition: color 0.2s;
        }
        .notification-attachment a:hover { color: #0056b3; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo-container"><a href="trangchusv.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a></div>
        <nav class="main-menu">
            <ul>
                <li><a href="trangchusv.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                <li class="active"><a href="thongbaosv.php"><i class="fas fa-bell"></i>Thông Báo</a></li>
                <li class="menu-dropdown">
                    <a href="#" class="dropbtn"><i class="fas fa-book-reader"></i>Kết quả học tập <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="diemtbkt.php">Điểm TBKT_Thi</a>
                        <a href="diemkq.php">Điểm kết quả</a>
                    </div>
                </li>
                <li><a href="suathongtin_sv.php"><i class="fas fa-user-edit"></i>Cập nhật Lý Lịch</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer"><a href="logoutsv.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a></div>
    </aside>

    <main class="content full-width-content">
        <h1 class="welcome-message">Hộp Thư Thông Báo</h1>

        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div class="message">Bạn chưa có thông báo nào.</div>
            <?php else: ?>
                <?php foreach ($notifications as $tb): ?>
                    <div class="notification-item">
                        <div class="notification-header">
                            <h2 class="notification-title"><?php echo htmlspecialchars($tb['tieu_de']); ?></h2>
                            <div class="notification-meta">
                                Gửi từ: <span class="sender"><?php echo htmlspecialchars($tb['ten_giang_vien']); ?></span> | Lớp: <?php echo htmlspecialchars($tb['ten_lop']); ?> | <?php echo date('d/m/Y H:i', strtotime($tb['ngay_gui'])); ?>
                            </div>
                        </div>
                        <div class="notification-content">
                            <p><?php echo nl2br(htmlspecialchars($tb['noi_dung'])); ?></p>
                        </div>
                        <?php if (!empty($tb['file_dinhkem'])): ?>
                            <div class="notification-attachment">
                                <i class="fas fa-paperclip"></i> <a href="<?php echo htmlspecialchars($tb['file_dinhkem']); ?>" target="_blank">Tải về tệp đính kèm</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>