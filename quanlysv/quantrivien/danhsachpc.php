<?php
session_start();

// Bảo vệ trang, chỉ cho phép Quản trị viên truy cập
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

if ($ma_khoa_admin === null) {
    die("Lỗi: Không thể xác định khoa của quản trị viên.");
}

// Lấy các giá trị lọc từ URL
$selected_lop = isset($_GET['ma_lop']) ? (int)$_GET['ma_lop'] : 0;
$selected_gv = isset($_GET['ma_gv']) ? (int)$_GET['ma_gv'] : 0;

// Lấy danh sách cho các bộ lọc
$lophoc_list_filter_query = $conn->prepare("SELECT ma_lop, ten_lop FROM lophoc WHERE ma_khoa = ? ORDER BY ten_lop ASC");
$lophoc_list_filter_query->bind_param("i", $ma_khoa_admin);
$lophoc_list_filter_query->execute();
$lophoc_list_filter = $lophoc_list_filter_query->get_result()->fetch_all(MYSQLI_ASSOC);
$giangvien_list_filter = $conn->query("SELECT ma_gv, ho_ten FROM giangvien WHERE ma_khoa = $ma_khoa_admin ORDER BY ho_ten ASC")->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách phân công đã tạo
$phancong_data = [];
$sql_phancong = "SELECT pc.ma_pc, gv.ma_gv, gv.ho_ten, l.ten_lop, mh.ten_mon, hk.ten_hocky, nh.ten_namhoc
                 FROM phancong pc
                 JOIN giangvien gv ON pc.ma_gv = gv.ma_gv
                 JOIN lophoc l ON pc.ma_lop = l.ma_lop
                 JOIN monhoc mh ON pc.ma_monhoc = mh.ma_mon
                 JOIN hocky hk ON pc.ma_hocky = hk.ma_hocky
                 JOIN namhoc nh ON pc.ma_namhoc = nh.ma_namhoc
                 WHERE gv.ma_khoa = ? ";

$params = [$ma_khoa_admin];
$types = "i";

if ($selected_lop > 0) {
    $sql_phancong .= " AND pc.ma_lop = ?";
    $params[] = $selected_lop;
    $types .= "i";
}
if ($selected_gv > 0) {
    $sql_phancong .= " AND pc.ma_gv = ?";
    $params[] = $selected_gv;
    $types .= "i";
}

$sql_phancong .= " ORDER BY gv.ho_ten ASC, nh.ten_namhoc DESC, hk.ten_hocky ASC, l.ten_lop ASC, mh.ten_mon ASC";
$stmt_phancong = $conn->prepare($sql_phancong);
$stmt_phancong->bind_param($types, ...$params);
$stmt_phancong->execute();
$result_phancong = $stmt_phancong->get_result();

if ($result_phancong->num_rows > 0) {
    $phancong_data = $result_phancong->fetch_all(MYSQLI_ASSOC);
}

$grouped_phancong = [];
if (!empty($phancong_data)) {
    foreach ($phancong_data as $pc) {
        $grouped_phancong[$pc['ma_gv']]['info'] = ['ho_ten' => $pc['ho_ten']];
        $grouped_phancong[$pc['ma_gv']]['assignments'][] = $pc;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Phân Công</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; }
        .filter-form .form-group { flex: 1; min-width: 200px; margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo-container"><a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a></div>
            <nav class="main-menu">
                <ul>
                    <li><a href="trangchu.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                    <li><a href="danhsachgv.php"><i class="fas fa-chalkboard-teacher"></i>Danh sách giảng viên</a></li>
                    <li><a href="danhsachsv.php"><i class="fas fa-user-graduate"></i>Danh sách sinh viên</a></li>
                    <li><a href="phancong.php"><i class="fas fa-tasks"></i>Phân công giảng dạy</a></li>
                    <li><a href="khoahoc.php"><i class="fas fa-users"></i>Danh sách khóa</a></li>
                    <li><a href="monhoc.php"><i class="fa-solid fa-book-open"></i>Danh sách môn học</a></li>
                    <li><a href="danhsachlop.php"><i class="fas fa-list-alt"></i>Danh sách lớp</a></li>
                </ul>
            </nav>
        </aside>

        <main class="content full-width-content">
            <h1 class="welcome-message">Danh Sách Phân Công Giảng Dạy</h1>

            <div class="filter-container">
                <form action="danhsachpc.php" method="GET" class="filter-form" id="filterForm">
                    <div class="form-group">
                        <label for="ma_lop">Lọc theo lớp</label>
                        <select id="ma_lop" name="ma_lop" onchange="this.form.submit()">
                            <option value="0">-- Tất cả lớp --</option>
                            <?php foreach ($lophoc_list_filter as $lop): ?>
                                <option value="<?php echo $lop['ma_lop']; ?>" <?php if ($selected_lop == $lop['ma_lop']) echo 'selected'; ?>><?php echo htmlspecialchars($lop['ten_lop']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ma_gv">Lọc theo giảng viên</label>
                        <select id="ma_gv" name="ma_gv" onchange="this.form.submit()">
                            <option value="0">-- Tất cả giảng viên --</option>
                            <?php foreach ($giangvien_list_filter as $gv): ?>
                                <option value="<?php echo $gv['ma_gv']; ?>" <?php if ($selected_gv == $gv['ma_gv']) echo 'selected'; ?>><?php echo htmlspecialchars($gv['ho_ten']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <?php if (empty($grouped_phancong)): ?>
                    <p style="text-align: center; color: #666;">Không có dữ liệu phân công nào phù hợp với bộ lọc.</p>
                <?php else: ?>
                    <?php foreach ($grouped_phancong as $gv_id => $gv_data): ?>
                        <div class="lecturer-group" style="margin-bottom: 30px;">
                            <h3 style="margin-top: 20px; margin-bottom: 10px; color: #3b7ddd; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                                Giảng viên: <?php echo htmlspecialchars($gv_data['info']['ho_ten']); ?>
                            </h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Lớp</th>
                                        <th>Môn học</th>
                                        <th>Học kỳ</th>
                                        <th>Năm học</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gv_data['assignments'] as $index => $pc): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($pc['ten_lop']); ?></td>
                                            <td><?php echo htmlspecialchars($pc['ten_mon']); ?></td>
                                            <td><?php echo htmlspecialchars($pc['ten_hocky']); ?></td>
                                            <td><?php echo htmlspecialchars($pc['ten_namhoc']); ?></td>
                                            <td class="action-cell">
                                                <a href="phancong.php?action=edit&id=<?php echo $pc['ma_pc']; ?>" class="action-btn view-btn" style="background-color: #ffc107; border-color: #ffc107;"><i class="fas fa-edit"></i> Sửa</a>
                                                <a href="phancong.php?action=delete&id=<?php echo $pc['ma_pc']; ?>" class="action-btn delete-btn" onclick="return confirm('Bạn có chắc chắn muốn hủy phân công này?');"><i class="fas fa-trash-alt"></i> Hủy</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>