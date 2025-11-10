<?php
/*
 * QUY TRÌNH FORM MỚI:
 * 1. Chọn [Bậc đào tạo].
 * 2. JS tải [Học kỳ] tương ứng.
 * 3. Chọn [Học kỳ], JS tải [Lớp học] và [Môn học] tương ứng.
 * 4. Chọn [Lớp học], JS điền [Năm học] vào trường ẩn.
 */
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'QuanTriVien') {
    header("Location: ../giangvien/logingv.php");
    exit();
}

include("../connect_db.php");

// --- API Endpoints for Dependent Dropdowns ---

// Endpoint để lấy học kỳ theo bậc đào tạo
if (isset($_GET['get_hocky_by_bac']) && isset($_GET['ma_bac'])) {
    header('Content-Type: application/json');
    $ma_bac = (int)$_GET['ma_bac'];
    $stmt = $conn->prepare("SELECT ma_hocky, ten_hocky FROM hocky WHERE ma_bac = ? ORDER BY ten_hocky ASC");
    $stmt->bind_param("i", $ma_bac);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $conn->close();
    exit();
}

// Endpoint để lấy lớp và môn học theo học kỳ
if (isset($_GET['get_lop_mon_by_hocky']) && isset($_GET['ma_hocky'])) {
    header('Content-Type: application/json');
    $ma_hocky = (int)$_GET['ma_hocky'];
    $ma_khoa_admin = $_SESSION['ma_khoa_admin'] ?? 0; // Lấy từ session
    
    // Lấy danh sách lớp học thuộc học kỳ và khoa của admin
    $stmt_lop = $conn->prepare("SELECT ma_lop, ten_lop, ma_namhoc FROM lophoc WHERE ma_hocky = ? AND ma_khoa = ? ORDER BY ten_lop ASC");
    $stmt_lop->bind_param("ii", $ma_hocky, $ma_khoa_admin);
    $stmt_lop->execute();
    $lophoc_data = $stmt_lop->get_result()->fetch_all(MYSQLI_ASSOC);

    // Lấy danh sách môn học thuộc học kỳ và khoa của admin
    $stmt_mon = $conn->prepare("SELECT ma_mon, ten_mon FROM monhoc WHERE ma_hocky = ? AND ma_khoa = ? ORDER BY ten_mon ASC");
    $stmt_mon->bind_param("ii", $ma_hocky, $ma_khoa_admin);
    $stmt_mon->execute();
    $monhoc_data = $stmt_mon->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['lophoc' => $lophoc_data, 'monhoc' => $monhoc_data]);
    $conn->close();
    exit();
}
// Endpoint để lấy năm học của một lớp cụ thể
if (isset($_GET['get_namhoc_for_lop']) && isset($_GET['ma_lop'])) {
    header('Content-Type: application/json');
    $ma_lop = (int)$_GET['ma_lop'];
    
    $stmt = $conn->prepare("SELECT ma_namhoc FROM lophoc WHERE ma_lop = ?");
    $stmt->bind_param("i", $ma_lop);
    $stmt->execute();
    $lop_info = $stmt->get_result()->fetch_assoc();
    echo json_encode($lop_info);
    $conn->close();
    exit();
}

// Khởi tạo biến
$message = '';
$message_type = '';
$edit_mode = false;
$edit_data = null;

// Biến để lưu các giá trị đã chọn trong form sau khi submit
$selected_form_bac = null;
$selected_form_gv = null;
$selected_form_namhoc = null;
$selected_form_lop = null;
$selected_form_hocky = null;
$selected_form_monhoc = null;

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
        $_SESSION['ma_khoa_admin'] = $ma_khoa_admin; // Lưu vào session để API sử dụng
    }
    $stmt_qtv_khoa->close();
}

// Lấy giảng viên được chọn từ URL để lọc
$selected_gv_id = isset($_GET['ma_gv']) && is_numeric($_GET['ma_gv']) ? (int)$_GET['ma_gv'] : null;
$selected_gv_ten = '';
if ($selected_gv_id) {
    $stmt_gv_name = $conn->prepare("SELECT ho_ten FROM giangvien WHERE ma_gv = ?");
    $stmt_gv_name->bind_param("i", $selected_gv_id);
    $stmt_gv_name->execute();
    $result_gv_name = $stmt_gv_name->get_result();
    if ($gv_name_row = $result_gv_name->fetch_assoc()) {
        $selected_gv_ten = $gv_name_row['ho_ten'];
    }
}

// Xử lý yêu cầu Hủy/Xóa phân công
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $ma_pc_to_delete = (int)$_GET['id'];

    $conn->begin_transaction(); // Bắt đầu transaction
    try {
        $stmt_delete = $conn->prepare("DELETE FROM phancong WHERE ma_pc = ?");
        $stmt_delete->bind_param("i", $ma_pc_to_delete);
        $stmt_delete->execute();

        if ($stmt_delete->affected_rows > 0) {
            $_SESSION['message'] = "Hủy phân công thành công!";
            $_SESSION['message_type'] = "success";
            $conn->commit(); // Commit nếu thành công
        } else {
            $_SESSION['message'] = "Không tìm thấy phân công để hủy hoặc đã có lỗi xảy ra.";
            $_SESSION['message_type'] = "error";
            $conn->rollback(); // Rollback nếu không có dòng nào bị ảnh hưởng
        }
        $stmt_delete->close();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback(); // Rollback nếu có lỗi
        $_SESSION['message'] = "Lỗi khi hủy phân công: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    header("Location: phancong.php");
    exit();
}

// Xử lý yêu cầu Chỉnh sửa (lấy dữ liệu để điền vào form)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $ma_pc_to_edit = (int)$_GET['id'];
    $stmt_edit = $conn->prepare("SELECT * FROM phancong WHERE ma_pc = ?");
    $stmt_edit->bind_param("i", $ma_pc_to_edit);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows === 1) {
        $edit_data = $result_edit->fetch_assoc();
    }
}

// Xử lý form submit (Thêm mới hoặc Cập nhật)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ma_gv = $_POST['ma_gv'];
    $ma_lop = $_POST['ma_lop'];
    $ma_monhoc = $_POST['ma_monhoc'];
    $ma_hocky = $_POST['ma_hocky'];
    $ma_namhoc = $_POST['ma_namhoc'];
    $ma_pc = $_POST['ma_pc'] ?? null; // Lấy ID để cập nhật
    
    // Lưu lại các giá trị đã nhập để hiển thị lại trên form
    $selected_form_bac = $_POST['ma_bac'];
    $selected_form_gv = $ma_gv;
    $selected_form_namhoc = $ma_namhoc;
    $selected_form_lop = $ma_lop;
    $selected_form_hocky = $ma_hocky;
    $selected_form_monhoc = $ma_monhoc;

    if (empty($ma_gv) || empty($ma_lop) || empty($ma_monhoc) || empty($ma_hocky) || empty($ma_namhoc)) {
        $message = "Vui lòng điền đầy đủ thông tin.";
        $message_type = "error";
    } else {
        try {
            if ($ma_pc) { // Chế độ Cập nhật
                $stmt = $conn->prepare("UPDATE phancong SET ma_gv=?, ma_lop=?, ma_monhoc=?, ma_hocky=?, ma_namhoc=? WHERE ma_pc=?");
                $stmt->bind_param("iiiiii", $ma_gv, $ma_lop, $ma_monhoc, $ma_hocky, $ma_namhoc, $ma_pc);
                $action_text = "Cập nhật";
            } else { // Chế độ Thêm mới
                $stmt = $conn->prepare("INSERT INTO phancong (ma_gv, ma_lop, ma_monhoc, ma_hocky, ma_namhoc) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiii", $ma_gv, $ma_lop, $ma_monhoc, $ma_hocky, $ma_namhoc);
                $action_text = "Thêm";
            }

            $stmt->execute(); // Dòng này có thể gây ra ngoại lệ

            $message = "$action_text phân công thành công!";
            $message_type = "success";
            $edit_mode = false; // Thoát chế độ edit sau khi thành công
            // Xóa các giá trị đã lưu để form trở về trạng thái trống sau khi thành công
            $selected_form_gv = null;
            $selected_form_namhoc = null;
            $selected_form_lop = null;
            $selected_form_hocky = null;
            $selected_form_monhoc = null;
            
        } catch (mysqli_sql_exception $e) {
            // Lỗi do trùng lặp (UNIQUE KEY) có mã lỗi 1062
            if ($e->getCode() == 1062) {
                $message = "Lỗi: Phân công này đã tồn tại.";
            } else {
                $message = "Lỗi khi $action_text phân công: " . $conn->error;
            }
            $message_type = "error";
        }
        $stmt->close();
    }
    // Không chuyển hướng ở đây nữa để giữ lại trạng thái form
}

// Lấy dữ liệu cho các dropdown
$giangvien_list = $conn->query("SELECT ma_gv, ho_ten FROM giangvien WHERE ma_khoa = $ma_khoa_admin ORDER BY ho_ten ASC")->fetch_all(MYSQLI_ASSOC);
$bacdaotao_list = $conn->query("SELECT ma_bac, ten_bac FROM bacdaotao ORDER BY ten_bac ASC")->fetch_all(MYSQLI_ASSOC);

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
if ($selected_gv_id) {
    $sql_phancong .= " AND pc.ma_gv = ?";
    $params[] = $selected_gv_id;
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
if (!$selected_gv_id && !empty($phancong_data)) { // Chỉ nhóm nếu không lọc theo giảng viên cụ thể
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
    <title>Phân Công Giảng Dạy</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content-columns { display: flex; gap: 30px; width: 100%; align-items: flex-start; }
        .form-column { flex: 1; max-width: 500px; }
        .list-column { flex: 2; min-width: 50%; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo-container"><a href="trangchu.php"><img src="../img/ChatGPT Image 20_11_34 7 thg 11, 2025.png" alt="Logo Hệ thống"></a></div>
        <!--Menu trái-->
      <nav class="main-menu">
        <ul>
          <li><a href="trangchu.php"><i class="fas fa-home"></i>Trang chủ</a></li>
          <!-- Thêm các mục menu cho quản trị viên ở đây -->
          <li><a href="danhsachgv.php"><i class="fas fa-chalkboard-teacher"></i>Danh sách giảng viên</a></li>
          <li class="active"><a href="phancong.php"><i class="fas fa-tasks"></i>Phân công giảng dạy</a></li>
          <li><a href="khoahoc.php"><i class="fas fa-users"></i>Danh sách khóa</a></li>
          <li><a href="danhsachpc.php"><i class="fas fa-list-check"></i>Danh sách phân công</a></li>
          <li class="active"><a href="monhoc.php"><i class="fa-solid fa-book-open"></i>Danh sách môn học</a></li>
          <li><a href="danhsachlop.php"><i class="fas fa-list-alt"></i>Danh sách lớp</a></li>
        </ul>
      </nav>
        </aside>

        <main class="content">
            <div class="main-content-columns">
                <!-- Form phân công -->
                <div class="form-column form-container">
                    <h2 class="form-title"><?php echo $edit_mode ? 'Chỉnh Sửa Phân Công' : 'Tạo Phân Công Mới'; ?></h2>
                    <?php if (!empty($message)): ?>
                        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="phancong.php" method="POST">
                        <?php if ($edit_mode && $edit_data): ?>
                            <input type="hidden" name="ma_pc" value="<?php echo $edit_data['ma_pc']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="ma_bac">Bậc đào tạo</label>
                            <select id="ma_bac" name="ma_bac" required>
                                <option value="">-- Chọn bậc đào tạo --</option>
                                <?php foreach ($bacdaotao_list as $bdt): ?>
                                    <option value="<?php echo $bdt['ma_bac']; ?>" <?php echo (($edit_mode && isset($edit_data['ma_bac'])) || $selected_form_bac == $bdt['ma_bac']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bdt['ten_bac']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ma_hocky">Học kỳ</label>
                            <select id="ma_hocky" name="ma_hocky" required disabled>
                                <option value="">-- Chọn bậc đào tạo trước --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ma_lop">Lớp học</label>
                            <select id="ma_lop" name="ma_lop" required disabled>
                                <option value="">-- Chọn học kỳ trước --</option>
                            </select>
                        </div>
                        <!-- Trường ẩn để lưu ma_namhoc -->
                        <input type="hidden" id="ma_namhoc" name="ma_namhoc" value="<?php echo $edit_mode ? $edit_data['ma_namhoc'] : ($selected_form_namhoc ?? ''); ?>">

                        <div class="form-group">
                            <label for="ma_monhoc">Môn học</label>
                            <select id="ma_monhoc" name="ma_monhoc" required disabled>
                                <option value="">-- Chọn học kỳ trước --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ma_gv">Giảng viên</label>
                            <select id="ma_gv" name="ma_gv" required>
                                <option value="">-- Chọn giảng viên --</option>
                                <?php foreach ($giangvien_list as $gv): ?>
                                    <option value="<?php echo $gv['ma_gv']; ?>" <?php echo (($edit_mode && $edit_data['ma_gv'] == $gv['ma_gv']) || $selected_form_gv == $gv['ma_gv'] || ($selected_gv_id == $gv['ma_gv'])) ? 'selected' : ''; ?>><?php echo htmlspecialchars($gv['ho_ten']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="submit" value="<?php echo $edit_mode ? 'Cập Nhật' : 'Thêm Phân Công'; ?>" class="btn form-submit-btn">
                        <?php if ($edit_mode): ?>
                            <a href="phancong.php" class="action-btn" style="background-color: #6c757d; display: block; text-align: center; text-decoration: none; margin-top: 10px;">Hủy Chỉnh Sửa</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Danh sách phân công -->
                <div class="list-column table-container">
                    <h2 style="text-align: center; margin-bottom: 20px;">
                        Danh Sách Phân Công
                        <?php if ($selected_gv_ten): ?>
                            cho Giảng viên: <?php echo htmlspecialchars($selected_gv_ten); ?>
                        <?php endif; ?>
                    </h2>
                    <?php if (empty($phancong_data)): ?>
                        <p style="text-align: center; color: #666;">Chưa có phân công nào được tạo.</p>
                    <?php else: ?>
                        <?php if ($selected_gv_id): // Hiển thị bảng phẳng nếu đã lọc theo giảng viên cụ thể ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Giảng viên</th>
                                        <th>Lớp</th>
                                    <th>Môn học</th>
                                    <th>Học kỳ</th>
                                    <th>Năm học</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($phancong_data as $index => $pc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pc['ho_ten']); ?></td>
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
                        <?php else: // Hiển thị nhóm theo giảng viên nếu không có lọc cụ thể ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bacdaotaoSelect = document.getElementById('ma_bac');
    const hockySelect = document.getElementById('ma_hocky');
    const lopSelect = document.getElementById('ma_lop');
    const monhocSelect = document.getElementById('ma_monhoc');
    const namhocInput = document.getElementById('ma_namhoc');

    // Hàm để tải Học kỳ dựa trên Bậc đào tạo
    function loadHocKy(maBac, selectedHocKyId = null) {
        hockySelect.disabled = true;
        hockySelect.innerHTML = '<option value="">-- Đang tải... --</option>';
        // Reset các dropdown phụ thuộc
        monhocSelect.disabled = true;
        monhocSelect.innerHTML = '<option value="">-- Chọn học kỳ trước --</option>';
        lopSelect.disabled = true;
        lopSelect.innerHTML = '<option value="">-- Chọn học kỳ trước --</option>';

        if (!maBac) {
            hockySelect.innerHTML = '<option value="">-- Chọn bậc đào tạo trước --</option>';
            return;
        }

        fetch(`phancong.php?get_hocky_by_bac=1&ma_bac=${maBac}`)
            .then(response => response.json())
            .then(data => {
                hockySelect.innerHTML = '<option value="">-- Chọn học kỳ --</option>';
                if (data.length > 0) {
                    data.forEach(hocky => {
                        const option = new Option(hocky.ten_hocky, hocky.ma_hocky);
                        if (selectedHocKyId && hocky.ma_hocky == selectedHocKyId) { // Chú ý so sánh giá trị
                            option.selected = true;
                        }
                        hockySelect.appendChild(option);
                    });
                    hockySelect.disabled = false;
                    // Nếu có học kỳ được chọn sẵn (chế độ sửa hoặc postback lỗi), tự động tải lớp và môn
                    if (selectedHocKyId) {
                        hockySelect.dispatchEvent(new Event('change'));
                    }
                } else {
                    hockySelect.innerHTML = '<option value="">-- Không có học kỳ --</option>';
                }
            })
            .catch(error => console.error('Lỗi tải học kỳ:', error));
    }

    // Hàm để tải Lớp và Môn học dựa trên Học kỳ
    function loadLopAndMonHoc(maHocKy, selectedLopId = null, selectedMonHocId = null) {
        lopSelect.disabled = true;
        lopSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
        monhocSelect.disabled = true;
        monhocSelect.innerHTML = '<option value="">-- Đang tải... --</option>';

        if (!maHocKy) {
            lopSelect.innerHTML = '<option value="">-- Chọn học kỳ trước --</option>';
            monhocSelect.innerHTML = '<option value="">-- Chọn học kỳ trước --</option>';
            return;
        }

        fetch(`phancong.php?get_lop_mon_by_hocky=1&ma_hocky=${maHocKy}`)
            .then(response => response.json())
            .then(data => {
                // Tải lớp học
                lopSelect.innerHTML = '<option value="">-- Chọn lớp học --</option>';
                if (data.lophoc && data.lophoc.length > 0) {
                    data.lophoc.forEach(lop => {
                        const option = new Option(lop.ten_lop, lop.ma_lop);
                        option.dataset.maNamhoc = lop.ma_namhoc; // Lưu ma_namhoc vào data attribute
                        if (selectedLopId && lop.ma_lop == selectedLopId) {
                            option.selected = true;
                        }
                        lopSelect.appendChild(option);
                    });
                    lopSelect.disabled = false;
                } else {
                    lopSelect.innerHTML = '<option value="">-- Không có lớp --</option>';
                }

                // Tải môn học
                monhocSelect.innerHTML = '<option value="">-- Chọn môn học --</option>';
                if (data.monhoc && data.monhoc.length > 0) {
                    data.monhoc.forEach(monhoc => {
                        const option = new Option(monhoc.ten_mon, monhoc.ma_mon);
                        if (selectedMonHocId && monhoc.ma_mon == selectedMonHocId) {
                            option.selected = true;
                        }
                        monhocSelect.appendChild(option);
                    });
                    monhocSelect.disabled = false;
                } else {
                    monhocSelect.innerHTML = '<option value="">-- Không có môn --</option>';
                }
            })
            .catch(error => console.error('Lỗi tải lớp và môn học:', error));
    }

    bacdaotaoSelect.addEventListener('change', () => loadHocKy(bacdaotaoSelect.value));
    hockySelect.addEventListener('change', () => loadLopAndMonHoc(hockySelect.value));
    lopSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        namhocInput.value = selectedOption.dataset.maNamhoc || '';
    });

    // Nếu đang ở chế độ sửa hoặc có lỗi postback, tải lại dữ liệu đã chọn
    <?php if ($edit_mode && $edit_data): ?>
        // Cần lấy ma_bac từ DB để khởi tạo
        // Tuy nhiên, để đơn giản, ta giả định người dùng phải chọn lại Bậc đào tạo
        // Hoặc ta có thể thêm ma_bac vào edit_data
        // Tạm thời, ta sẽ chỉ tải học kỳ nếu có ma_bac
        const selectedBac = <?php echo json_encode($selected_form_bac); ?>;
        if (selectedBac) {
            loadHocKy(selectedBac, <?php echo json_encode($edit_data['ma_hocky']); ?>);
            loadLopAndMonHoc(<?php echo json_encode($edit_data['ma_hocky']); ?>, <?php echo json_encode($edit_data['ma_lop']); ?>, <?php echo json_encode($edit_data['ma_monhoc']); ?>);
        }
    <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        const selectedBac = <?php echo json_encode($selected_form_bac); ?>;
        if (selectedBac) {
            loadHocKy(selectedBac, <?php echo json_encode($selected_form_hocky); ?>);
            loadLopAndMonHoc(<?php echo json_encode($selected_form_hocky); ?>, <?php echo json_encode($selected_form_lop); ?>, <?php echo json_encode($selected_form_monhoc); ?>);
        }
    <?php endif; ?>
});
</script>
</body>
</html>