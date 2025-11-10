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

// Lấy danh sách lớp và môn học mà giảng viên được phân công
$sql_pc = "SELECT DISTINCT l.ma_lop, l.ten_lop, mh.ma_mon, mh.ten_mon
           FROM phancong pc
           JOIN lophoc l ON pc.ma_lop = l.ma_lop
           JOIN monhoc mh ON pc.ma_monhoc = mh.ma_mon
           WHERE pc.ma_gv = ?
           ORDER BY l.ten_lop, mh.ten_mon";
$stmt_pc = $conn->prepare($sql_pc);
$stmt_pc->bind_param("i", $giangvien_id);
$stmt_pc->execute();
$phancong_result = $stmt_pc->get_result();
$phancong_list = $phancong_result->fetch_all(MYSQLI_ASSOC);
$stmt_pc->close();

$selected_lop = isset($_GET['ma_lop']) ? (int)$_GET['ma_lop'] : 0;
$selected_monhoc = isset($_GET['ma_mon']) ? (int)$_GET['ma_mon'] : 0;
$sinhvien_list = [];

if ($selected_lop > 0 && $selected_monhoc > 0) {
    // Lấy danh sách sinh viên của lớp và điểm số (nếu có)
    $sql_sv = "SELECT sv.id as sv_id, sv.mssv, sv.ho_ten, d.*
               FROM sinhvien sv
               JOIN lop_sinhvien lsv ON sv.id = lsv.id_sv
               LEFT JOIN diem d ON sv.id = d.id_sv AND d.ma_mon = ?
               WHERE lsv.ma_lop = ?
               ORDER BY sv.ho_ten";
    $stmt_sv = $conn->prepare($sql_sv);
    $stmt_sv->bind_param("ii", $selected_monhoc, $selected_lop);
    $stmt_sv->execute();
    $result_sv = $stmt_sv->get_result();
    $sinhvien_list = $result_sv->fetch_all(MYSQLI_ASSOC);
    $stmt_sv->close();
}

// Xử lý lưu điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $ma_lop = $_POST['ma_lop'];
    $ma_monhoc = $_POST['ma_mon'];
    $grades = $_POST['grades'];

    $conn->begin_transaction();
    try {
        foreach ($grades as $sv_id => $diem) {
            // Chuyển đổi giá trị rỗng thành NULL
            foreach ($diem as $key => $value) {
                $diem[$key] = $value === '' ? null : (float)$value;
            }

            // Kiểm tra xem sinh viên đã có điểm cho môn này chưa
            $stmt_check = $conn->prepare("SELECT ma_diem FROM diem WHERE id_sv = ? AND ma_mon = ?");
            $stmt_check->bind_param("ii", $sv_id, $ma_monhoc);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Cập nhật điểm
                $stmt_update = $conn->prepare("UPDATE diem SET diem_chuyencan=?, diem_15p1=?, diem_15p2=?, diem_15p3=?, diem_1tiet1=?, diem_1tiet2=?, diem_thilan1=?, diem_thilan2=? WHERE id_sv = ? AND ma_mon = ?");
                $stmt_update->bind_param("ddddddddii", $diem['cc'], $diem['15p1'], $diem['15p2'], $diem['15p3'], $diem['1tiet1'], $diem['1tiet2'], $diem['thilan1'], $diem['thilan2'], $sv_id, $ma_monhoc);
                $stmt_update->execute();
            } else {
                // Thêm điểm mới
                $stmt_insert = $conn->prepare("INSERT INTO diem (id_sv, ma_mon, diem_chuyencan, diem_15p1, diem_15p2, diem_15p3, diem_1tiet1, diem_1tiet2, diem_thilan1, diem_thilan2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("iidddddddd", $sv_id, $ma_monhoc, $diem['cc'], $diem['15p1'], $diem['15p2'], $diem['15p3'], $diem['1tiet1'], $diem['1tiet2'], $diem['thilan1'], $diem['thilan2']);
                $stmt_insert->execute();
            }
        }
        $conn->commit();
        $_SESSION['message'] = "Lưu điểm thành công!";
        $_SESSION['message_type'] = "success";
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['message'] = "Lỗi khi lưu điểm: " . $exception->getMessage();
        $_SESSION['message_type'] = "error";
    }
    header("Location: nhapdiem.php?ma_lop=$ma_lop&ma_mon=$ma_monhoc");
    exit();
}

// Lấy thông báo từ session nếu có
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
} else {
    $message = '';
    $message_type = '';
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập Điểm Sinh Viên</title>
    <link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/table.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-container { overflow-x: auto; }
        .data-table input[type="number"] { width: 60px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; text-align: center; }
        .data-table input:focus { border-color: #3b7ddd; box-shadow: 0 0 0 2px rgba(59, 125, 221, 0.2); }
        .data-table th, .data-table td { white-space: nowrap; text-align: center; vertical-align: middle; }
        .calculated-col { background-color: #e9ecef; font-weight: bold; }
        .header-rotated { height: 140px; position: relative; }
        .header-rotated > div {
            transform: rotate(-65deg);
            width: 30px;
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform-origin: bottom left;
            white-space: nowrap;
        }
        .save-btn-container { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-logo-container"><a href="trangchu.php"><img src="../img/ChatGPT Image 21_18_32 16 thg 10, 2025.png" alt="Logo"></a></div>
      <nav class="main-menu">
        <ul>
            <li class="active"><a href="trangchugv.php"><i class="fas fa-home"></i>Trang chủ</a></li>
            <li><a href="phanconggv.php"><i class="fa-solid fa-chalkboard-user"></i>Danh sách giảng dạy</a></li>
            <li><a href="nhapdiem.php"><i class="fas fa-marker"></i>Nhập điểm</a></li>
            <li><a href="thongbao.php"><i class="fas fa-bell"></i>Thông báo</a></li>
            <li><a href="danhsach_sv.php"><i class="fas fa-users"></i>Danh sách sinh viên</a></li>
            <li><a href="suathongtin.php"><i class="fas fa-user-edit"></i>Cập nhật thông tin</a></li>
        </ul>
      </nav>
    </aside>

    <main class="content full-width-content">
        <h1 class="welcome-message">Quản Lý Điểm Sinh Viên</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>" style="margin-bottom: 20px; text-align: center;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container" style="max-width: 800px; margin: 20px auto;">
            <form id="filter-form" action="nhapdiem.php" method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ma_lop_monhoc">Chọn Lớp và Môn học</label>
                        <select id="ma_lop_monhoc" name="ma_lop_monhoc" required onchange="submitForm()">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($phancong_list as $pc): ?>
                                <option value="<?php echo $pc['ma_lop'] . '-' . $pc['ma_mon']; ?>" <?php if ($selected_lop == $pc['ma_lop'] && $selected_monhoc == $pc['ma_mon']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($pc['ten_lop'] . ' - ' . $pc['ten_mon']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="ma_lop" id="ma_lop" value="<?php echo $selected_lop; ?>">
                        <input type="hidden" name="ma_mon" id="ma_mon" value="<?php echo $selected_monhoc; ?>">
                    </div>
                </div>
            </form>
        </div>

        <?php if ($selected_lop > 0 && $selected_monhoc > 0): ?>
            <form action="nhapdiem.php" method="POST">
                <input type="hidden" name="ma_lop" value="<?php echo $selected_lop; ?>">
                <input type="hidden" name="ma_mon" value="<?php echo $selected_monhoc; ?>">
                <div class="table-container">
                    <table class="data-table" id="grade-table">
                        <thead>
                            <tr>
                                <th rowspan="2">STT</th>
                                <th rowspan="2">MSSV</th>
                                <th rowspan="2">Họ Tên</th>
                                <th class="header-rotated"><div>Chuyên cần</div></th>
                                <th colspan="3">Điểm 15 phút</th>
                                <th colspan="2">Điểm 1 tiết</th>
                                <th colspan="2">Điểm thi</th>
                                <th class="header-rotated"><div>TK 15p</div></th>
                                <th class="header-rotated"><div>TK 1 tiết</div></th>
                                <th class="header-rotated"><div>Điểm TB</div></th>
                            </tr>
                            <tr>
                                <th>(10%)</th>
                                <th>Lần 1</th><th>Lần 2</th><th>Lần 3</th>
                                <th>Lần 1</th><th>Lần 2 </th>
                                <th>Lần 1(50%)</th><th>Lần 2 (50%)</th>
                                <th class="calculated-col"></th>
                                <th class="calculated-col"></th>
                                <th class="calculated-col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sinhvien_list as $index => $sv): ?>
                                <tr data-sv-id="<?php echo $sv['sv_id']; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($sv['mssv']); ?></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($sv['ho_ten']); ?></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][cc]" value="<?php echo $sv['diem_chuyencan']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][15p1]" value="<?php echo $sv['diem_15p1']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][15p2]" value="<?php echo $sv['diem_15p2']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][15p3]" value="<?php echo $sv['diem_15p3']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][1tiet1]" value="<?php echo $sv['diem_1tiet1']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][1tiet2]" value="<?php echo $sv['diem_1tiet2']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][thilan1]" value="<?php echo $sv['diem_thilan1']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td><input type="number" name="grades[<?php echo $sv['sv_id']; ?>][thilan2]" value="<?php echo $sv['diem_thilan2']; ?>" min="0" max="10" step="0.1" oninput="calculateRow(this)"></td>
                                    <td class="calculated-col tk-15p"></td>
                                    <td class="calculated-col tk-1t"></td>
                                    <td class="calculated-col diem-tb"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="save-btn-container">
                    <button type="submit" name="save_grades" class="btn form-submit-btn"><i class="fas fa-save"></i> Lưu Điểm</button>
                </div>
            </form>
        <?php endif; ?>
    </main>
</div>

<script>
function submitForm() {
    const selectLopMonHoc = document.getElementById('ma_lop_monhoc');
    const [ma_lop, ma_mon] = selectLopMonHoc.value.split('-');
    document.getElementById('ma_lop').value = ma_lop || '';
    document.getElementById('ma_mon').value = ma_mon || '';
    document.getElementById('filter-form').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    // Tính toán tất cả các hàng khi tải trang
    document.querySelectorAll('#grade-table tbody tr').forEach(row => {
        calculateRow(row.querySelector('input'));
    });
});

function calculateRow(element) {
    const row = element.closest('tr');
    if (!row) return;

    // Tính điểm trung bình 15 phút
    const diem15p_inputs = [
        parseFloat(row.querySelector('input[name*="[15p1]"]').value),
        parseFloat(row.querySelector('input[name*="[15p2]"]').value),
        parseFloat(row.querySelector('input[name*="[15p3]"]').value)
    ];
    const valid_diem15p = diem15p_inputs.filter(d => !isNaN(d) && d >= 0 && d <= 10);
    const tk15p = valid_diem15p.length > 0 ? valid_diem15p.reduce((a, b) => a + b, 0) / valid_diem15p.length : null;
    row.querySelector('.tk-15p').textContent = tk15p !== null ? tk15p.toFixed(2) : '';

    // Tính điểm trung bình 1 tiết
    const diem1t_inputs = [
        parseFloat(row.querySelector('input[name*="[1tiet1]"]').value),
        parseFloat(row.querySelector('input[name*="[1tiet2]"]').value)
    ];
    const valid_diem1t = diem1t_inputs.filter(d => !isNaN(d) && d >= 0 && d <= 10);
    const tk1t = valid_diem1t.length > 0 ? valid_diem1t.reduce((a, b) => a + b, 0) / valid_diem1t.length : null;
    row.querySelector('.tk-1t').textContent = tk1t !== null ? tk1t.toFixed(2) : '';

    // Tính điểm trung bình cuối kỳ
    const diemCC = parseFloat(row.querySelector('input[name*="[cc]"]').value);
    const diemThiL1 = parseFloat(row.querySelector('input[name*="[thilan1]"]').value);
    const diemThiL2 = parseFloat(row.querySelector('input[name*="[thilan2]"]').value);

    // Ưu tiên điểm thi lần 2 nếu có
    const diemThi = !isNaN(diemThiL2) ? diemThiL2 : (!isNaN(diemThiL1) ? diemThiL1 : null);

    // Tính điểm quá trình
    let diemQuaTrinh = 0;
    if (tk15p !== null && tk1t !== null) {
        diemQuaTrinh = (tk15p + tk1t) / 2;
    } else if (tk15p !== null) {
        diemQuaTrinh = tk15p;
    } else if (tk1t !== null) {
        diemQuaTrinh = tk1t;
    }

    // Chỉ tính điểm TB khi có ít nhất một điểm thành phần
    let diemTB = null;
    if (diemQuaTrinh > 0 || diemThi !== null || !isNaN(diemCC)) {
        diemTB = (diemQuaTrinh * 0.4) + ((!isNaN(diemCC) ? diemCC : 0) * 0.1) + ((diemThi !== null ? diemThi : 0) * 0.5);
    }

    row.querySelector('.diem-tb').textContent = diemTB !== null ? diemTB.toFixed(2) : '';
}
</script>
</body>
</html>