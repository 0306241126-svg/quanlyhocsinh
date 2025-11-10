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

// Lấy ma_khoa và tên khoa của quản trị viên
$ma_khoa_admin = null;
$ten_khoa = 'Chưa xác định';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt_khoa = $conn->prepare("SELECT K.ma_khoa, K.ten_khoa FROM quantrivien QV 
                                LEFT JOIN khoa K ON QV.ma_khoa = K.ma_khoa 
                                WHERE QV.ma_qtv = ?");
    $stmt_khoa->bind_param("i", $user_id);
    $stmt_khoa->execute();
    if ($khoa_info = $stmt_khoa->get_result()->fetch_assoc()) {
        $ma_khoa_admin = $khoa_info['ma_khoa'];
        $ten_khoa = $khoa_info['ten_khoa'];
    }
}

// Lấy danh sách các bậc đào tạo để hiển thị trong dropdown
$bacdaotao_list = $conn->query("SELECT * FROM bacdaotao ORDER BY ten_bac ASC")->fetch_all(MYSQLI_ASSOC);

// Biến để lưu trữ môn học đã được nhóm theo học kỳ
$grouped_monhoc = [];
$selected_bac = '';

// Kiểm tra xem người dùng đã chọn bậc đào tạo chưa
if (isset($_GET['ma_bac']) && !empty($_GET['ma_bac'])) {
    $selected_bac = (int)$_GET['ma_bac'];

    // Truy vấn để lấy tất cả môn học thuộc các học kỳ của bậc đào tạo đã chọn
    $stmt = $conn->prepare("
        SELECT 
            mh.ten_mon AS ten_monhoc,
            mh.so_tin_chi, 
            hk.ten_hocky
        FROM monhoc mh
        INNER JOIN hocky hk ON mh.ma_hocky = hk.ma_hocky
        WHERE hk.ma_bac = ? AND mh.ma_khoa = ?
        ORDER BY hk.ma_hocky ASC, mh.ten_mon ASC
    ");
    // Lọc theo cả bậc đào tạo và khoa của quản trị viên
    $stmt->bind_param("ii", $selected_bac, $ma_khoa_admin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Nhóm các môn học theo tên học kỳ
        while ($row = $result->fetch_assoc()) {
            $grouped_monhoc[$row['ten_hocky']][] = $row;
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Môn Học</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-form {
            max-width: 500px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .semester-group {
            margin-bottom: 40px;
        }
        .semester-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #3b7ddd;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- THANH BÊN (SIDEBAR) -->
        <aside class="sidebar">
            <div class="sidebar-logo-container">
                <a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a>
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
            <div class="sidebar-footer">
            </div>
        </aside>

        <!-- NỘI DUNG CHÍNH -->
        <main class="content full-width-content">
            <h1 class="welcome-message">Danh Sách Môn Học <?php if (!empty($ten_khoa)) echo " - Khoa " . htmlspecialchars($ten_khoa); ?></h1>

            <!-- Form lọc theo bậc đào tạo -->
            <form action="monhoc.php" method="GET" class="filter-form">
                <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
                    <label for="ma_bac">Chọn Bậc Đào Tạo:</label>
                    <select id="ma_bac" name="ma_bac" onchange="this.form.submit()">
                        <option value="">-- Vui lòng chọn --</option>
                        <?php foreach ($bacdaotao_list as $bdt): ?>
                            <option value="<?php echo $bdt['ma_bac']; ?>" <?php echo ($selected_bac == $bdt['ma_bac']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bdt['ten_bac']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <!-- Hiển thị danh sách môn học -->
            <?php if (!empty($selected_bac)): ?>
                <?php if (empty($grouped_monhoc)): ?>
                    <p style="text-align: center; color: #666;">Không có môn học nào cho bậc đào tạo này.</p>
                <?php else: ?>
                    <?php foreach ($grouped_monhoc as $ten_hocky => $monhoc_list): ?>
                        <div class="semester-group table-container">
                            <h2 class="semester-title"><?php echo htmlspecialchars($ten_hocky); ?></h2>
                            <table class="data-table">
                                <thead><tr><th>STT</th><th>Tên Môn Học</th><th>Số Tín Chỉ</th></tr></thead>
                                <tbody>
                                    <?php foreach ($monhoc_list as $index => $monhoc): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($monhoc['ten_monhoc']); ?></td>
                                            <td><?php echo htmlspecialchars($monhoc['so_tin_chi']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
