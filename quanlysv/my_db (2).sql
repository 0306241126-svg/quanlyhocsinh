-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 10, 2025 lúc 02:16 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `my_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bacdaotao`
--

CREATE TABLE `bacdaotao` (
  `ma_bac` int(11) NOT NULL,
  `ten_bac` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bacdaotao`
--

INSERT INTO `bacdaotao` (`ma_bac`, `ten_bac`) VALUES
(1, 'Cao đẳng ngành'),
(2, 'Cao đẳng nghề');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diem`
--

CREATE TABLE `diem` (
  `ma_diem` int(11) NOT NULL,
  `id_sv` int(11) DEFAULT NULL,
  `ma_mon` int(11) DEFAULT NULL,
  `diem_chuyencan` float DEFAULT NULL,
  `diem_15p1` float DEFAULT NULL,
  `diem_15p2` float DEFAULT NULL,
  `diem_15p3` float DEFAULT NULL,
  `diem_1tiet1` float DEFAULT NULL,
  `diem_1tiet2` float DEFAULT NULL,
  `diem_1tiet3` float DEFAULT NULL,
  `diem_thilan1` float DEFAULT NULL,
  `diem_thilan2` float DEFAULT NULL,
  `tong15p` float DEFAULT NULL,
  `tong1tiet` float DEFAULT NULL,
  `tongket` float DEFAULT NULL,
  `ghichu` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `diem`
--

INSERT INTO `diem` (`ma_diem`, `id_sv`, `ma_mon`, `diem_chuyencan`, `diem_15p1`, `diem_15p2`, `diem_15p3`, `diem_1tiet1`, `diem_1tiet2`, `diem_1tiet3`, `diem_thilan1`, `diem_thilan2`, `tong15p`, `tong1tiet`, `tongket`, `ghichu`) VALUES
(1, 32, 31, 10, 7, NULL, NULL, 6, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL),
(2, 33, 31, NULL, 8, NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 34, 31, NULL, 5, NULL, NULL, 4, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 37, 31, NULL, 6, NULL, NULL, 4.5, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 38, 31, NULL, 8, NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 39, 31, NULL, 9, NULL, NULL, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 40, 31, NULL, 4, NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 41, 31, NULL, 5, NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 42, 31, NULL, 7, NULL, NULL, 7.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 43, 31, NULL, 8, NULL, NULL, 7.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 84, 31, NULL, 4, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 44, 31, NULL, 9, NULL, NULL, 7.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 45, 31, NULL, 9, NULL, NULL, 8.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 46, 31, NULL, 7, NULL, NULL, 6.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 47, 31, NULL, 5, NULL, NULL, 6.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 51, 31, NULL, 7, NULL, NULL, 5.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 48, 31, NULL, 4, NULL, NULL, 4.5, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 49, 31, NULL, 3, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 36, 31, NULL, 9, NULL, NULL, 8.5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 50, 31, NULL, 5, NULL, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 93, 8, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 87, 8, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 92, 8, NULL, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 90, 8, NULL, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 85, 8, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 88, 8, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 94, 8, NULL, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 86, 8, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 91, 8, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 89, 8, NULL, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giangvien`
--

CREATE TABLE `giangvien` (
  `ma_gv` int(11) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `cccd` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `gioi_tinh` varchar(10) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `dia_chi` varchar(200) DEFAULT NULL,
  `ngay_bat_dau` date DEFAULT NULL,
  `avatar` varchar(200) DEFAULT NULL,
  `ma_khoa` int(11) DEFAULT NULL,
  `trangthai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giangvien`
--

INSERT INTO `giangvien` (`ma_gv`, `ho_ten`, `cccd`, `email`, `ngay_sinh`, `gioi_tinh`, `sdt`, `dia_chi`, `ngay_bat_dau`, `avatar`, `ma_khoa`, `trangthai`) VALUES
(7, 'Hồ Ngọc Nga', '083845697889', 'hnn@caothang.edu.vn', NULL, NULL, NULL, NULL, '0000-00-00', NULL, 1, 1),
(9, 'Ngô Văn Hoàng', '036998745165', 'nvh@caothang.edu.vn', NULL, NULL, NULL, NULL, '2023-07-18', NULL, 1, 1),
(10, 'Nguyễn Xuân Có', '089997744564', 'nxc@caothang.edu.vn', NULL, NULL, NULL, NULL, '2021-12-14', NULL, 1, 1),
(11, 'Hồ Ngọc Hà', '03546789456', 'hnh@caothang.edu.vn', NULL, NULL, NULL, NULL, '2025-08-03', NULL, 1, 1),
(12, 'Lê Thị Ái', '09874561230', 'lta@caothang.edu.vn', '1998-02-11', 'Nữ', '0894561230', 'Ấp 1,Phương Hạnh Thông,Thành Phố Hồ Chí Minh', '2021-07-16', '../uploads/avatars/gv_12_1762671093_z6488813125550_d2373526f99e64b83900d1cd7180ec56.jpg', 1, 1),
(13, 'Nguyễn Hoài Ánh', '08974561238', 'nha@caothang.edu.vn', NULL, NULL, NULL, NULL, '2020-09-17', NULL, 1, 1),
(15, 'Nguyễn Đinh Bắc', '07885564123', 'ndb@caothang.edu.vn', NULL, NULL, NULL, NULL, '2020-09-18', NULL, 1, 1),
(19, 'Trần Ngọc Ngân', '083897456123', 'trangngocngan@caothang.edu.vn', NULL, NULL, NULL, NULL, '2023-04-01', NULL, 1, 1),
(20, 'Nguyễn Văn Tuyền', '0812345678911', 'nguyenvantuyen@caothang.edu.vn', NULL, NULL, NULL, NULL, '2024-11-09', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hocky`
--

CREATE TABLE `hocky` (
  `ma_hocky` int(11) NOT NULL,
  `ten_hocky` varchar(50) NOT NULL,
  `ma_bac` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hocky`
--

INSERT INTO `hocky` (`ma_hocky`, `ten_hocky`, `ma_bac`) VALUES
(1, 'Học kỳ 1', 1),
(2, 'Học kỳ 2', 1),
(3, 'Học kỳ 3', 1),
(4, 'Học kỳ 4', 1),
(5, 'Học kỳ 5', 1),
(6, 'Học kỳ 6', 1),
(7, 'Học kỳ 1', 2),
(8, 'Học kỳ 2', 2),
(9, 'Học kỳ 3', 2),
(10, 'Học kỳ 4', 2),
(11, 'Học kỳ 5', 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoa`
--

CREATE TABLE `khoa` (
  `ma_khoa` int(11) NOT NULL,
  `ten_khoa` varchar(100) NOT NULL,
  `dia_chi` varchar(200) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khoa`
--

INSERT INTO `khoa` (`ma_khoa`, `ten_khoa`, `dia_chi`, `sdt`) VALUES
(1, 'Công nghệ thông tin', 'Tầng 7, Nhà F, Phòng F7.5', '0888469788'),
(2, 'Điện, điện tử', 'Tầng 5, Nhà F,F5.5', '0838445666');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoahoc`
--

CREATE TABLE `khoahoc` (
  `ma_khoahoc` int(11) NOT NULL,
  `ten_khoahoc` varchar(50) NOT NULL,
  `ma_namhoc` int(11) DEFAULT NULL,
  `ma_hocky` int(11) DEFAULT NULL,
  `ma_bac` int(11) DEFAULT NULL,
  `ma_khoa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `khoahoc`
--

INSERT INTO `khoahoc` (`ma_khoahoc`, `ten_khoahoc`, `ma_namhoc`, `ma_hocky`, `ma_bac`, `ma_khoa`) VALUES
(24, 'K24', 12, NULL, 1, 1),
(28, 'K25', 15, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lophoc`
--

CREATE TABLE `lophoc` (
  `ma_lop` int(11) NOT NULL,
  `ten_lop` varchar(100) NOT NULL,
  `ma_khoahoc` int(11) DEFAULT NULL,
  `ma_namhoc` int(11) DEFAULT NULL,
  `ma_khoa` int(11) DEFAULT NULL,
  `ma_hocky` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lophoc`
--

INSERT INTO `lophoc` (`ma_lop`, `ten_lop`, `ma_khoahoc`, `ma_namhoc`, `ma_khoa`, `ma_hocky`) VALUES
(37, 'CĐ CNTT 24B', 24, 10, 1, 2),
(38, 'CĐ CNTT 24B', 24, 10, 1, 1),
(40, 'CĐ CNTT 24B', 24, 10, 1, 3),
(41, 'CĐ CNTT 25A', 28, 14, 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lop_sinhvien`
--

CREATE TABLE `lop_sinhvien` (
  `ma_lop` int(11) NOT NULL,
  `id_sv` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lop_sinhvien`
--

INSERT INTO `lop_sinhvien` (`ma_lop`, `id_sv`) VALUES
(37, 32),
(37, 33),
(37, 34),
(37, 36),
(37, 37),
(37, 38),
(37, 39),
(37, 40),
(37, 41),
(37, 42),
(37, 43),
(37, 44),
(37, 45),
(37, 46),
(37, 47),
(37, 48),
(37, 49),
(37, 50),
(37, 51),
(37, 84),
(38, 32),
(38, 33),
(38, 34),
(38, 36),
(38, 37),
(38, 38),
(38, 39),
(38, 40),
(38, 41),
(38, 42),
(38, 43),
(38, 44),
(38, 45),
(38, 46),
(38, 47),
(38, 48),
(38, 49),
(38, 50),
(38, 51),
(38, 84),
(40, 32),
(40, 33),
(40, 34),
(40, 36),
(40, 37),
(40, 38),
(40, 39),
(40, 40),
(40, 41),
(40, 42),
(40, 43),
(40, 44),
(40, 45),
(40, 46),
(40, 47),
(40, 48),
(40, 49),
(40, 50),
(40, 51),
(40, 84),
(41, 85),
(41, 86),
(41, 87),
(41, 88),
(41, 89),
(41, 90),
(41, 91),
(41, 92),
(41, 93),
(41, 94);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `monhoc`
--

CREATE TABLE `monhoc` (
  `ma_mon` int(11) NOT NULL,
  `ten_mon` varchar(100) NOT NULL,
  `so_tin_chi` int(11) NOT NULL,
  `ma_bac` int(11) DEFAULT NULL,
  `ma_hocky` int(11) DEFAULT NULL,
  `ma_khoa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `monhoc`
--

INSERT INTO `monhoc` (`ma_mon`, `ten_mon`, `so_tin_chi`, `ma_bac`, `ma_hocky`, `ma_khoa`) VALUES
(1, 'Tiếng anh 1', 3, 1, 1, 1),
(2, 'Toán cao cấp', 3, 1, 1, 1),
(3, 'Giáo dục thể chất', 1, 1, 1, 1),
(4, 'Pháp luật', 2, 1, 1, 1),
(5, 'Toán rời rạc và lý thuyết đồ thị', 3, 1, 1, 1),
(6, 'Phần cứng máy tính', 3, 1, 1, 1),
(7, 'Thực tập phần cứng máy tinh', 3, 1, 1, 1),
(8, 'Nhập môn lập trình', 5, 1, 1, 1),
(9, 'Thực tập nhập môn lập trình', 2, 1, 1, 1),
(10, 'Tin học ứng dụng', 3, 1, 1, 1),
(11, 'Tiếng anh 2', 3, 1, 2, 1),
(12, 'Cấu trúc dữ liệu & giải thuật', 3, 1, 2, 1),
(13, 'Giáo dục thể chất 2', 1, 1, 2, 1),
(14, 'Vật lý đại cương', 4, 1, 2, 1),
(15, 'Cơ sở dữ liệu', 5, 1, 2, 1),
(16, 'Thực tập CTDL & GT', 1, 1, 2, 1),
(17, 'Mang máy tính', 3, 1, 2, 1),
(18, 'Thực tập mạng máy tinh', 1, 1, 2, 1),
(19, 'Thiết kế web', 3, 1, 2, 1),
(20, 'TT Thiết kế web', 1, 1, 2, 1),
(21, 'Thiết kế web', 3, 1, 2, 1),
(22, 'Giáo dục quốc phong & an ninh', 3, 1, 3, 1),
(23, 'Giáo dục chính trị 1', 3, 1, 3, 1),
(24, 'Tiếng anh 3', 5, 1, 3, 1),
(25, 'Hệ quản trị cơ sở dữ liệu', 2, 1, 3, 1),
(26, 'Quản trị hệ thống mạng windows', 3, 1, 3, 1),
(27, 'Phương pháp lập trình hướng đối tượng', 3, 1, 3, 1),
(28, 'Lập trình web PHP cơ bản', 3, 1, 3, 1),
(29, 'TT hệ quản trị cơ sở dữ liệu', 1, 1, 3, 1),
(30, 'TT quản trị hệ thống mạng windows ', 1, 1, 3, 1),
(31, 'TT Phương pháp lập trình hướng đối tượng ', 1, 1, 3, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `namhoc`
--

CREATE TABLE `namhoc` (
  `ma_namhoc` int(11) NOT NULL,
  `ten_namhoc` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `namhoc`
--

INSERT INTO `namhoc` (`ma_namhoc`, `ten_namhoc`) VALUES
(10, '2024-2025'),
(12, '2023-2024'),
(14, '2025-2026'),
(15, '2025');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancong`
--

CREATE TABLE `phancong` (
  `ma_pc` int(11) NOT NULL,
  `ma_gv` int(11) NOT NULL,
  `ma_lop` int(11) NOT NULL,
  `ma_monhoc` int(11) NOT NULL,
  `ma_hocky` int(11) NOT NULL,
  `ma_namhoc` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phancong`
--

INSERT INTO `phancong` (`ma_pc`, `ma_gv`, `ma_lop`, `ma_monhoc`, `ma_hocky`, `ma_namhoc`) VALUES
(35, 7, 38, 5, 1, 10),
(30, 9, 38, 6, 1, 10),
(31, 9, 38, 7, 1, 10),
(28, 11, 38, 8, 1, 10),
(29, 11, 38, 9, 1, 10),
(38, 12, 40, 27, 3, 10),
(39, 12, 40, 31, 3, 10),
(36, 12, 41, 8, 1, 14),
(37, 12, 41, 9, 1, 14),
(32, 15, 38, 10, 1, 10),
(33, 19, 38, 2, 1, 10);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quantrivien`
--

CREATE TABLE `quantrivien` (
  `ma_qtv` int(11) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ma_khoa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `quantrivien`
--

INSERT INTO `quantrivien` (`ma_qtv`, `ten_dang_nhap`, `mat_khau`, `ma_khoa`) VALUES
(1, 'cntt', 'admin123@', 1),
(2, 'ddt', 'admin123@', 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sinhvien`
--

CREATE TABLE `sinhvien` (
  `id` int(11) NOT NULL,
  `mssv` char(10) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cccd` varchar(20) NOT NULL,
  `ma_khoahoc` int(11) DEFAULT NULL,
  `ma_khoa` int(11) DEFAULT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `gioi_tinh` varchar(10) DEFAULT NULL,
  `dia_chi` varchar(200) DEFAULT NULL,
  `sdt` varchar(15) DEFAULT NULL,
  `avatar` varchar(200) DEFAULT NULL,
  `trangthai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sinhvien`
--

INSERT INTO `sinhvien` (`id`, `mssv`, `ho_ten`, `email`, `cccd`, `ma_khoahoc`, `ma_khoa`, `ngay_sinh`, `gioi_tinh`, `dia_chi`, `sdt`, `avatar`, `trangthai`) VALUES
(32, '0306241101', 'An Văn Bình', '0306241101@caothang.edu.vn', '081234567801', 24, 1, '2005-10-19', 'Nam', 'Ấp 1, Thới Thuận, Vĩnh Long', '0838426997', '../uploads/avatars/sv_32_1762522340_ChatGPT Image Apr 15, 2025, 07_03_14 PM.png', 1),
(33, '0306241102', 'Bùi Minh Châu', '0306241102@caothang.edu.vn', '081234567802', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(34, '0306241103', 'Cao Hữu Dũng', '0306241103@caothang.edu.vn', '081234567803', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(36, '0306241105', 'Đinh Hoàng Gia', '0306241105@caothang.edu.vn', '081234567805', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(37, '0306241106', 'Hoàng Minh Hiếu', '0306241106@caothang.edu.vn', '081234567806', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(38, '0306241107', 'Lê Anh Hòa', '0306241107@caothang.edu.vn', '081234567807', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(39, '0306241108', 'Lý Gia Hưng', '0306241108@caothang.edu.vn', '081234567808', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(40, '0306241109', 'Mai Trí Khang', '0306241109@caothang.edu.vn', '081234567809', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(41, '0306241110', 'Ngô Phúc Khôi', '0306241110@caothang.edu.vn', '081234567810', 24, 1, '2005-12-08', NULL, 'Ấp 1, Xã Thạnh Trị, Tỉnh Vĩnh Long', '0838469997', '../uploads/avatars/sv_41_1762441935_z6488813125550_d2373526f99e64b83900d1cd7180ec56.jpg', 1),
(42, '0306241111', 'Nguyễn Bảo Long', '0306241111@caothang.edu.vn', '081234567811', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(43, '0306241112', 'Nguyễn Thị Minh', '0306241112@caothang.edu.vn', '081234567812', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(44, '0306241113', 'Phạm Hoàng Nam', '0306241113@caothang.edu.vn', '081234567813', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(45, '0306241114', 'Phan Quang Nghĩa', '0306241114@caothang.edu.vn', '081234567814', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(46, '0306241115', 'Trần Quốc Phát', '0306241115@caothang.edu.vn', '081234567815', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(47, '0306241116', 'Trịnh Gia Phúc', '0306241116@caothang.edu.vn', '081234567816', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(48, '0306241117', 'Võ Đức Quân', '0306241117@caothang.edu.vn', '081234567817', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(49, '0306241118', 'Vũ Thị Thanh', '0306241118@caothang.edu.vn', '081234567818', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(50, '0306241119', 'Đỗ Minh Thiện', '0306241119@caothang.edu.vn', '081234567819', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(51, '0306241120', 'Trương Gia Vinh', '0306241120@caothang.edu.vn', '081234567820', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(84, '0306241121', 'Nguyễn Xuân Vĩnh', '0306241121@caothang.edu.vn', '0897456123', 24, 1, NULL, NULL, NULL, NULL, NULL, 1),
(85, '0306251101', 'Nguyễn Hoàng An', '0306251101@caothang.edu.vn', '081234567801', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(86, '0306251102', 'Trần Minh Bảo', '0306251102@caothang.edu.vn', '081234567802', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(87, '0306251103', 'Lê Đức Bình', '0306251103@caothang.edu.vn', '081234567803', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(88, '0306251104', 'Phạm Gia Cường', '0306251104@caothang.edu.vn', '081234567804', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(89, '0306251105', 'Đỗ Hải Dương', '0306251105@caothang.edu.vn', '081234567804', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(90, '0306251106', 'Ngô Quang Dũng', '0306251106@caothang.edu.vn', '081234567806', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(91, '0306251107', 'Trương Minh Đức', '0306251107@caothang.edu.vn', '081234567807', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(92, '0306251108', 'Lý Ngọc Hà', '0306251108@caothang.edu.vn', '081234567808', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(93, '0306251109', 'Bùi Thanh Hòa', '0306251109@caothang.edu.vn', '08123456780708', 28, 1, NULL, NULL, NULL, NULL, NULL, 1),
(94, '0306251110', 'Phan Thị Kim', '0306251110@caothang.edu.vn', '01234567810', 28, 1, NULL, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taikhoan`
--

CREATE TABLE `taikhoan` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('GiangVien','SinhVien') NOT NULL,
  `gv_id` int(11) DEFAULT NULL,
  `sv_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `taikhoan`
--

INSERT INTO `taikhoan` (`id`, `username`, `password`, `role`, `gv_id`, `sv_id`, `status`, `created_at`) VALUES
(17, '0306241101', '$2y$10$QGyGWONTGYhpPWOZqgu4O.xtIQ2kHwSR/PYSoEPGQW09SNTHOzpUq', 'SinhVien', NULL, 32, 'active', '2025-11-06 12:52:57'),
(18, '0306241102', '$2y$10$d7njq0tAvl2d8kD93sFJke.F2EJGs8SiW3v4X1nSDVLtyb.r2V8vG', 'SinhVien', NULL, 33, 'active', '2025-11-06 12:52:57'),
(19, '0306241103', '$2y$10$AfLQ8nXZXeGDrELpipUAe.X5mkr.bRv9dowu77x4At7Gz/mKK/DXK', 'SinhVien', NULL, 34, 'active', '2025-11-06 12:52:57'),
(123, '0306241105', '$2y$10$Euh7ABDnqE0J0C3A2LaWDevhRHVmKzN8vqSysbdUq3.SD.qxL3TcW', 'SinhVien', NULL, 36, 'active', '2025-11-06 13:46:09'),
(124, '0306241106', '$2y$10$9Or0F.SCZ/oed4NGLi1mx.auLM1z6MnBDraYdS64d.Bx78K5yWzs2', 'SinhVien', NULL, 37, 'active', '2025-11-06 13:46:09'),
(125, '0306241107', '$2y$10$.TDpB9kKZ0ktFuDpGQSseuZyfu5VvZgeTcYXJvSxVSZgR8FYMVaCq', 'SinhVien', NULL, 38, 'active', '2025-11-06 13:46:09'),
(126, '0306241108', '$2y$10$wIdt8p/Se8.uXvC2GdJb0.HlBK19s87mZ/UOO0YP.6a3n0zuvd5ne', 'SinhVien', NULL, 39, 'active', '2025-11-06 13:46:09'),
(127, '0306241109', '$2y$10$vnj.876.zxA8SDOzZJOnUOznFOd09k/f9Wn2Cw.3X.ifXvuhojkva', 'SinhVien', NULL, 40, 'active', '2025-11-06 13:46:09'),
(128, '0306241110', '$2y$10$//phC6b3uK1PCnfs8cNGneTaU/XS726NMY3D5HYQOGQQgnbwger9i', 'SinhVien', NULL, 41, 'active', '2025-11-06 13:46:09'),
(129, '0306241111', '$2y$10$VDGjsu2L7mzUecL.bweU1uPva77Tw54Xiqd94RV4StFeV6xZbiVQ.', 'SinhVien', NULL, 42, 'active', '2025-11-06 13:46:09'),
(130, '0306241112', '$2y$10$a3/6jezyq//1Oz.trHnZQO5ylD0vZLjS5y.acOkP5KD/LjAazmrte', 'SinhVien', NULL, 43, 'active', '2025-11-06 13:46:09'),
(131, '0306241113', '$2y$10$4vVopi5PP5Ly4gox19BB9u24eHiZkG4QW39d36iqkin4yenFr2KQ6', 'SinhVien', NULL, 44, 'active', '2025-11-06 13:46:09'),
(132, '0306241114', '$2y$10$16YCDz8kQlaQ7HNtK6gQjO8c2ApnwOJn2B15GxTVD.4tRTCdBSRva', 'SinhVien', NULL, 45, 'active', '2025-11-06 13:46:09'),
(133, '0306241115', '$2y$10$znnLSYdGFPV0aZr1/FRFg.m1dWvJA0ptebeFAaOSQZubqe/RUD7n6', 'SinhVien', NULL, 46, 'active', '2025-11-06 13:46:09'),
(134, '0306241116', '$2y$10$fUnbdOXHUoslJHv/JY/smOLVpdU812cC.7O6hnHWaEOF5/jnJcEvi', 'SinhVien', NULL, 47, 'active', '2025-11-06 13:46:09'),
(135, '0306241117', '$2y$10$Nlajwm5VDjqsy1zbbchZnODF8qyUbudj4a9aQXaJ1w9RuSqeVvmRa', 'SinhVien', NULL, 48, 'active', '2025-11-06 13:46:09'),
(136, '0306241118', '$2y$10$f0Qq8uDyjOf46dZV5STFHez5YHWp8Mmyb/yhYNTI5MfDpnMyiYfwO', 'SinhVien', NULL, 49, 'active', '2025-11-06 13:46:09'),
(137, '0306241119', '$2y$10$IZlLZUcSClVQjn472hYqPOVbG68n6VkjioeTzkXe34j4ehqF0oT.a', 'SinhVien', NULL, 50, 'active', '2025-11-06 13:46:09'),
(138, '0306241120', '$2y$10$1B/UKtfZPyq7cRLCo7MDuuvTWX9/RqJ7CjxsVOLWYFD7e4Yx4.dJS', 'SinhVien', NULL, 51, 'active', '2025-11-06 13:46:09'),
(139, 'hnn@caothang.edu.vn', '$2y$10$baSZC0KzYHbiGQExOEYnfuYsuGEfJFmm1O4CaeLWpq/0Mpx2Ly4S6', 'GiangVien', 7, NULL, 'active', '2025-11-06 14:18:40'),
(141, 'nvh@caothang.edu.vn', '$2y$10$cAQxu3uOjPaN2Y4V74R9juKiboBp8RCHJBAwKDjPI/pmF4tiTrzoG', 'GiangVien', 9, NULL, 'active', '2025-11-06 14:19:14'),
(142, 'nxc@caothang.edu.vn', '$2y$10$Up2CC6B1WQxGjLootGh7g.szaOfJ/psEUsYXk0AJ8t0SCEPLX4HFi', 'GiangVien', 10, NULL, 'active', '2025-11-06 14:19:30'),
(143, 'hnh@caothang.edu.vn', '$2y$10$p8nLllbJSW6oJalhGm6ZUe9yYPl8Fnm/xPgzqaL9kCZAgVQRaupKa', 'GiangVien', 11, NULL, 'active', '2025-11-06 14:21:22'),
(144, 'lta@caothang.edu.vn', '$2y$10$Gq8ADrUqxJSYqcZ3P7cvr.Bydo5NErAa3.P7EG1utD414oD6aV9vm', 'GiangVien', 12, NULL, 'active', '2025-11-06 14:21:55'),
(145, 'nha@caothang.edu.vn', '$2y$10$w4DWBExCxJE26SXlOzxcSOq7KRRVLXDThxI6doJTRvAvGNwmmRXjy', 'GiangVien', 13, NULL, 'active', '2025-11-06 14:22:09'),
(147, 'ndb@caothang.edu.vn', '$2y$10$s08E.UQkgwsatgcPA4Pp5.E991Blo5enazRwyz9kfdIIhbSQplaEC', 'GiangVien', 15, NULL, 'active', '2025-11-06 14:22:47'),
(151, 'trangngocngan@caothang.edu.vn', '$2y$10$MMm4DUo4LhCvYt2ySQH2buHG3G5GoSD4cCHYRjL9MaOX7RRQHynJG', 'GiangVien', 19, NULL, 'active', '2025-11-09 06:50:46'),
(153, '0306241121', '$2y$10$aplZG1pddXT6hU6/51Uktuc5E1vqhdTPvL9be61Ourv5cpiGikUhq', 'SinhVien', NULL, 84, 'active', '2025-11-09 07:06:37'),
(154, '0306251101', '$2y$10$IqY.cNwj9FvWyNxvXtzD..PYL9ts6N96B.r6Fy/6PFifm74bEoDhO', 'SinhVien', NULL, 85, 'active', '2025-11-10 08:10:09'),
(155, '0306251102', '$2y$10$bv5X0jtQrplVnjGE/gsw2ODiT3tTOEahY/MnYAWT3T6KnjJ8f93BG', 'SinhVien', NULL, 86, 'active', '2025-11-10 08:10:28'),
(156, '0306251103', '$2y$10$y/xnAcViAumY/MznDOH.N.//QAfzBabAsVQRFmeaTANWf0ilwx4By', 'SinhVien', NULL, 87, 'active', '2025-11-10 08:10:43'),
(157, '0306251104', '$2y$10$9fIbDcYa.Q8Lgl3g4Zke7OcuXQlsYYveJRa7D9tt22XM7tm94ucPK', 'SinhVien', NULL, 88, 'active', '2025-11-10 08:10:58'),
(158, '0306251105', '$2y$10$48X7aMZcmeh4ViMd1093aemzS0E8WOY7aSSB/ShA8OuMifNQ0GDae', 'SinhVien', NULL, 89, 'active', '2025-11-10 08:11:11'),
(159, '0306251106', '$2y$10$fe8pOlEqC/GS8lSKsvz.gucmOLKMAXxcbqcB4CBO4Olkkn8Eoo4eq', 'SinhVien', NULL, 90, 'active', '2025-11-10 08:12:07'),
(160, '0306251107', '$2y$10$kWcGH7oYswEybUUP.kZx6.w3x5OXCxRNtBtXfrKa3kKJjB5.TP17u', 'SinhVien', NULL, 91, 'active', '2025-11-10 08:12:24'),
(161, '0306251108', '$2y$10$nbIGfTSo6C9YVWiJmO6Bu.PH/W.2TIjm/BcNDqgsZoT9dFAwulOee', 'SinhVien', NULL, 92, 'active', '2025-11-10 08:12:37'),
(162, '0306251109', '$2y$10$MfRu3l8Q0WUHtXUUkcvdkemvEwfB0tFTe4VXanVkY7rnmqKcr51HO', 'SinhVien', NULL, 93, 'active', '2025-11-10 08:13:16'),
(163, '0306251110', '$2y$10$Hkb3i9EttRmEIPcvTy5h3O7L0TkNKHZ42ozpWCykr55./rvriYod.', 'SinhVien', NULL, 94, 'active', '2025-11-10 08:14:20'),
(164, 'nguyenvantuyen@caothang.edu.vn', '$2y$10$gqAZ0kEHMYUL50di9nv5rOC0Pher268RWfM/pQbPRcHPnTys66MuC', 'GiangVien', 20, NULL, 'active', '2025-11-10 08:26:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thongbao`
--

CREATE TABLE `thongbao` (
  `ma_tb` int(11) NOT NULL,
  `ma_gv` int(11) DEFAULT NULL,
  `ma_lop` int(11) DEFAULT NULL,
  `tieu_de` varchar(200) DEFAULT NULL,
  `noi_dung` text DEFAULT NULL,
  `file_dinhkem` varchar(255) DEFAULT NULL,
  `ngay_gui` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thongbao`
--

INSERT INTO `thongbao` (`ma_tb`, `ma_gv`, `ma_lop`, `tieu_de`, `noi_dung`, `file_dinhkem`, `ngay_gui`) VALUES
(6, 12, 40, 'Thông Báo', 'Kiểm tra', NULL, '2025-11-10 16:09:03');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bacdaotao`
--
ALTER TABLE `bacdaotao`
  ADD PRIMARY KEY (`ma_bac`);

--
-- Chỉ mục cho bảng `diem`
--
ALTER TABLE `diem`
  ADD PRIMARY KEY (`ma_diem`),
  ADD KEY `id_sv` (`id_sv`),
  ADD KEY `ma_mon` (`ma_mon`);

--
-- Chỉ mục cho bảng `giangvien`
--
ALTER TABLE `giangvien`
  ADD PRIMARY KEY (`ma_gv`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `ma_khoa` (`ma_khoa`);

--
-- Chỉ mục cho bảng `hocky`
--
ALTER TABLE `hocky`
  ADD PRIMARY KEY (`ma_hocky`),
  ADD KEY `ma_bac` (`ma_bac`);

--
-- Chỉ mục cho bảng `khoa`
--
ALTER TABLE `khoa`
  ADD PRIMARY KEY (`ma_khoa`);

--
-- Chỉ mục cho bảng `khoahoc`
--
ALTER TABLE `khoahoc`
  ADD PRIMARY KEY (`ma_khoahoc`),
  ADD KEY `ma_namhoc` (`ma_namhoc`),
  ADD KEY `fk_khoahoc_hocky` (`ma_hocky`),
  ADD KEY `fk_khoahoc_bacdaotao` (`ma_bac`),
  ADD KEY `fk_khoahoc_khoa` (`ma_khoa`);

--
-- Chỉ mục cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  ADD PRIMARY KEY (`ma_lop`),
  ADD KEY `ma_khoahoc` (`ma_khoahoc`),
  ADD KEY `ma_namhoc` (`ma_namhoc`),
  ADD KEY `ma_khoa` (`ma_khoa`);

--
-- Chỉ mục cho bảng `lop_sinhvien`
--
ALTER TABLE `lop_sinhvien`
  ADD PRIMARY KEY (`ma_lop`,`id_sv`),
  ADD KEY `id_sv` (`id_sv`);

--
-- Chỉ mục cho bảng `monhoc`
--
ALTER TABLE `monhoc`
  ADD PRIMARY KEY (`ma_mon`),
  ADD KEY `ma_bac` (`ma_bac`),
  ADD KEY `ma_hocky` (`ma_hocky`),
  ADD KEY `fk_monhoc_khoa` (`ma_khoa`);

--
-- Chỉ mục cho bảng `namhoc`
--
ALTER TABLE `namhoc`
  ADD PRIMARY KEY (`ma_namhoc`);

--
-- Chỉ mục cho bảng `phancong`
--
ALTER TABLE `phancong`
  ADD PRIMARY KEY (`ma_pc`),
  ADD UNIQUE KEY `unique_assignment` (`ma_gv`,`ma_lop`,`ma_monhoc`,`ma_hocky`,`ma_namhoc`),
  ADD KEY `fk_pc_gv` (`ma_gv`),
  ADD KEY `fk_pc_lop` (`ma_lop`),
  ADD KEY `fk_pc_monhoc` (`ma_monhoc`),
  ADD KEY `fk_pc_hocky` (`ma_hocky`),
  ADD KEY `fk_pc_namhoc` (`ma_namhoc`);

--
-- Chỉ mục cho bảng `quantrivien`
--
ALTER TABLE `quantrivien`
  ADD PRIMARY KEY (`ma_qtv`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  ADD KEY `ma_khoa` (`ma_khoa`);

--
-- Chỉ mục cho bảng `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `mssv` (`mssv`),
  ADD KEY `sinhvien_ibfk_1` (`ma_khoahoc`);

--
-- Chỉ mục cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `gv_id` (`gv_id`),
  ADD KEY `sv_id` (`sv_id`);

--
-- Chỉ mục cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`ma_tb`),
  ADD KEY `ma_gv` (`ma_gv`),
  ADD KEY `ma_lop` (`ma_lop`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bacdaotao`
--
ALTER TABLE `bacdaotao`
  MODIFY `ma_bac` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `diem`
--
ALTER TABLE `diem`
  MODIFY `ma_diem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `giangvien`
--
ALTER TABLE `giangvien`
  MODIFY `ma_gv` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `hocky`
--
ALTER TABLE `hocky`
  MODIFY `ma_hocky` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `khoa`
--
ALTER TABLE `khoa`
  MODIFY `ma_khoa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `khoahoc`
--
ALTER TABLE `khoahoc`
  MODIFY `ma_khoahoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  MODIFY `ma_lop` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT cho bảng `monhoc`
--
ALTER TABLE `monhoc`
  MODIFY `ma_mon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `namhoc`
--
ALTER TABLE `namhoc`
  MODIFY `ma_namhoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `phancong`
--
ALTER TABLE `phancong`
  MODIFY `ma_pc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT cho bảng `quantrivien`
--
ALTER TABLE `quantrivien`
  MODIFY `ma_qtv` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `sinhvien`
--
ALTER TABLE `sinhvien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `ma_tb` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `diem`
--
ALTER TABLE `diem`
  ADD CONSTRAINT `diem_ibfk_1` FOREIGN KEY (`id_sv`) REFERENCES `sinhvien` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diem_ibfk_2` FOREIGN KEY (`ma_mon`) REFERENCES `monhoc` (`ma_mon`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `giangvien`
--
ALTER TABLE `giangvien`
  ADD CONSTRAINT `giangvien_ibfk_1` FOREIGN KEY (`ma_khoa`) REFERENCES `khoa` (`ma_khoa`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hocky`
--
ALTER TABLE `hocky`
  ADD CONSTRAINT `hocky_ibfk_1` FOREIGN KEY (`ma_bac`) REFERENCES `bacdaotao` (`ma_bac`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lophoc`
--
ALTER TABLE `lophoc`
  ADD CONSTRAINT `lophoc_ibfk_1` FOREIGN KEY (`ma_khoahoc`) REFERENCES `khoahoc` (`ma_khoahoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `lophoc_ibfk_2` FOREIGN KEY (`ma_namhoc`) REFERENCES `namhoc` (`ma_namhoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `lophoc_ibfk_3` FOREIGN KEY (`ma_khoa`) REFERENCES `khoa` (`ma_khoa`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lop_sinhvien`
--
ALTER TABLE `lop_sinhvien`
  ADD CONSTRAINT `lop_sinhvien_ibfk_1` FOREIGN KEY (`ma_lop`) REFERENCES `lophoc` (`ma_lop`) ON DELETE CASCADE,
  ADD CONSTRAINT `lop_sinhvien_ibfk_2` FOREIGN KEY (`id_sv`) REFERENCES `sinhvien` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `monhoc`
--
ALTER TABLE `monhoc`
  ADD CONSTRAINT `fk_monhoc_khoa` FOREIGN KEY (`ma_khoa`) REFERENCES `khoa` (`ma_khoa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `monhoc_ibfk_1` FOREIGN KEY (`ma_bac`) REFERENCES `bacdaotao` (`ma_bac`) ON DELETE CASCADE,
  ADD CONSTRAINT `monhoc_ibfk_2` FOREIGN KEY (`ma_hocky`) REFERENCES `hocky` (`ma_hocky`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `phancong`
--
ALTER TABLE `phancong`
  ADD CONSTRAINT `fk_pc_gv` FOREIGN KEY (`ma_gv`) REFERENCES `giangvien` (`ma_gv`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_hocky` FOREIGN KEY (`ma_hocky`) REFERENCES `hocky` (`ma_hocky`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_lop` FOREIGN KEY (`ma_lop`) REFERENCES `lophoc` (`ma_lop`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_monhoc` FOREIGN KEY (`ma_monhoc`) REFERENCES `monhoc` (`ma_mon`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_namhoc` FOREIGN KEY (`ma_namhoc`) REFERENCES `namhoc` (`ma_namhoc`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `quantrivien`
--
ALTER TABLE `quantrivien`
  ADD CONSTRAINT `quantrivien_ibfk_1` FOREIGN KEY (`ma_khoa`) REFERENCES `khoa` (`ma_khoa`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD CONSTRAINT `sinhvien_ibfk_1` FOREIGN KEY (`ma_khoahoc`) REFERENCES `khoahoc` (`ma_khoahoc`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD CONSTRAINT `taikhoan_ibfk_2` FOREIGN KEY (`gv_id`) REFERENCES `giangvien` (`ma_gv`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `taikhoan_ibfk_3` FOREIGN KEY (`sv_id`) REFERENCES `sinhvien` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`ma_gv`) REFERENCES `giangvien` (`ma_gv`) ON DELETE CASCADE,
  ADD CONSTRAINT `thongbao_ibfk_2` FOREIGN KEY (`ma_lop`) REFERENCES `lophoc` (`ma_lop`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
