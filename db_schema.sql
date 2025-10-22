-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 09:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pao_run_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `distances`
--

CREATE TABLE `distances` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'ชื่อระยะทาง เช่น 10 KM',
  `price` decimal(10,2) NOT NULL COMMENT 'ค่าสมัคร',
  `category` varchar(100) DEFAULT NULL COMMENT 'ประเภท เช่น Fun Run',
  `bib_color` varchar(7) DEFAULT '#4f46e5' COMMENT 'รหัสสีสำหรับพื้นหลัง BIB ของระยะนี้',
  `bib_prefix` varchar(20) DEFAULT NULL COMMENT 'คำนำหน้าเลข BIB สำหรับระยะนี้',
  `bib_start_number` int(11) DEFAULT 1 COMMENT 'เลข BIB เริ่มต้นสำหรับระยะนี้',
  `bib_padding` int(11) DEFAULT 4 COMMENT 'จำนวนหลักของเลข BIB',
  `bib_next_number` int(11) DEFAULT NULL COMMENT 'เลข BIB ลำดับถัดไปที่จะใช้'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distances`
--

INSERT INTO `distances` (`id`, `event_id`, `name`, `price`, `category`, `bib_color`, `bib_prefix`, `bib_start_number`, `bib_padding`, `bib_next_number`) VALUES
(3, 2, '25 KM', 1200.00, 'Ultra Trail', '#4f46e5', NULL, 1, 4, NULL),
(4, 2, '10 KM', 900.00, 'Beginner Trail', '#4f46e5', NULL, 1, 4, NULL),
(40, 1, '10 KM', 850.00, 'Mini Marathon', '#4f46e5', NULL, 1, 4, NULL),
(41, 1, '5 KM', 650.00, 'Fun Run', '#4f46e5', NULL, 1, 4, NULL),
(42, 1, '1 KM', 450.00, 'Mini', '#4f46e5', NULL, 1, 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_code` varchar(50) NOT NULL COMMENT 'รหัสอ้างอิงกิจกรรม เช่น sskpa-run-25',
  `name` varchar(255) NOT NULL COMMENT 'ชื่อกิจกรรม',
  `slogan` varchar(255) DEFAULT NULL,
  `theme_color` varchar(20) NOT NULL DEFAULT 'indigo',
  `color_code` varchar(7) NOT NULL DEFAULT '#4f46e5',
  `logo_text` varchar(100) DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_registration_open` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=ปิด, 1=เปิด',
  `organizer` varchar(255) DEFAULT NULL,
  `organizer_phone` varchar(50) DEFAULT NULL,
  `organizer_email` varchar(100) DEFAULT NULL,
  `organizer_line_id` varchar(100) DEFAULT NULL,
  `organizer_logo_url` text DEFAULT NULL,
  `contact_person_name` varchar(255) DEFAULT NULL,
  `contact_person_phone` varchar(50) DEFAULT NULL,
  `payment_bank` varchar(100) DEFAULT NULL,
  `payment_account_name` varchar(255) DEFAULT NULL,
  `payment_account_number` varchar(50) DEFAULT NULL,
  `payment_qr_code_url` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `cover_image_url` text DEFAULT NULL,
  `card_thumbnail_url` text DEFAULT NULL,
  `map_embed_url` text DEFAULT NULL,
  `map_direction_url` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `awards_description` text DEFAULT NULL,
  `bib_prefix` varchar(20) DEFAULT NULL COMMENT 'คำนำหน้า BIB',
  `bib_start_number` int(11) NOT NULL DEFAULT 1 COMMENT 'เลข BIB เริ่มต้น',
  `bib_padding` int(11) NOT NULL DEFAULT 4 COMMENT 'จำนวนหลักของเลข BIB',
  `bib_next_number` int(11) DEFAULT NULL COMMENT 'เลข BIB ลำดับถัดไปที่จะใช้',
  `corral_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'การตั้งค่ากลุ่มปล่อยตัว' CHECK (json_valid(`corral_settings`)),
  `bib_background_url` text DEFAULT NULL COMMENT 'Path to custom BIB background image'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_code`, `name`, `slogan`, `theme_color`, `color_code`, `logo_text`, `is_visible`, `is_cancelled`, `sort_order`, `is_registration_open`, `organizer`, `organizer_phone`, `organizer_email`, `organizer_line_id`, `organizer_logo_url`, `contact_person_name`, `contact_person_phone`, `payment_bank`, `payment_account_name`, `payment_account_number`, `payment_qr_code_url`, `start_date`, `cover_image_url`, `card_thumbnail_url`, `map_embed_url`, `map_direction_url`, `description`, `awards_description`, `bib_prefix`, `bib_start_number`, `bib_padding`, `bib_next_number`, `corral_settings`, `bib_background_url`) VALUES
(1, 'sskpa-run-25', 'kokphet Run For Love 2024', 'วิ่งสร้างสรรค์, เพื่อชุมชนยั่งยืน', 'indigo', '#4f46e5', 'SSKPAO RUN 🏃‍♀️', 1, 0, 1, 1, 'รพสต.โคกเพชร', '045-888-999', 'ssk-pao@run.com', '@sskpaorun', 'uploads/sskpa-run-25/organizer/organizer_68f729c73fa4d.png', 'คุณสมหญิง ใจดี (ฝ่ายทะเบียน)', '087-9617951', 'ธนาคาร SISAKET RUN', 'SISAKET PAO RUN', '123-456-7890', 'uploads/sskpa-run-25/payment/payment_68f50c3e0b33a.jpg', '2025-11-15 18:00:00', 'uploads/sskpa-run-25/cover/cover_68f5d4c9412f0.webp', 'uploads/sskpa-run-25/cover/cover_68f5d4c9416ae.webp', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3889.375005898851!2d104.3005556!3d15.1111111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311a2f5f5c5b4e3d%3A0x8c5b1b4b1a4a4b1c!2sSisaket%20Provincial%20Stadium!5e0!3m2!1sen!2sth!4v1678888888888!5m2!1sen!2sth', 'https://maps.google.com/?q=Sisaket+Provincial+Stadium', '<p><em><strong>Fancy รวมทุกรุ่น 6 รางวัลๆละ 500 พร้อมเกียรติบัตร</strong></em></p>\r\n\r\n<p><strong>รางวัล 1000-600-400</strong></p>\r\n\r\n<p><strong>สมัครวิ่งได้เหรียญทุกคน -ชนะเลิศ 1-2-3 เกียรติบัตร</strong></p>\r\n', '<p>Fancy รวมทุกรุ่น 6 รางวัลๆละ 500 พร้อมเกียรติบัตร</p>\r\n\r\n<p><strong>รางวัล 1000-600-400</strong></p>\r\n\r\n<p><strong>สมัครวิ่งได้เหรียญทุกคน -ชนะเลิศ 1-2-3 เกียรติบัตร</strong></p>\r\n', NULL, 1, 4, 4, NULL, NULL),
(2, 'mountain-trail-challenge-25', 'Mountain Trail Challenge 2025', 'พิชิตยอดเขา.. ท้าทายขีดจำกัด!', 'green', '#10b981', 'TRAIL CHALLENGE ⛰️', 1, 0, 2, 0, 'Thai Trail Runners Club', '090-555-4444', 'trail@run.com', '@thaitrail', NULL, 'คุณอดิศักดิ์ แข็งแรง (Race Director)', '090-111-2222', 'ธนาคาร TRAIL RUN', 'TRAIL RUNNING TEAM', '987-654-3210', 'https://placehold.co/300x300/10b981/ffffff?text=TRAIL+QR+Code', '2025-08-15 06:00:00', 'https://placehold.co/800x300/10b981/ffffff?text=Mountain+Trail+Cover', 'https://placehold.co/400x150/10b981/ffffff?text=Mountain+Trail+Card', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3889.375005898851!2d101.4000000!3d14.4000000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311a2f5f5c5b4e3d%3A0x8c5b1b4b1a4a4b1c!2sKhao%20Yai%20National%20Park!5e0!3m2!1sen!2sth!4v1678888888888!5m2!1sen!2sth', 'https://maps.google.com/?q=Khao+Yai+National+Park', 'การแข่งขันวิ่งเทรลสุดท้าทายในพื้นที่เขาใหญ่', '**รางวัลสำหรับ 25 KM (Ultra Trail):**\r\n\r\n- Overall Male/Female (Top 5): **ถ้วยรางวัล King/Queen of the Mountain**', NULL, 1, 4, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_images`
--

CREATE TABLE `event_images` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `image_url` text NOT NULL,
  `image_type` enum('merch','medal','detail') NOT NULL COMMENT 'ประเภทรูปภาพ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_images`
--

INSERT INTO `event_images` (`id`, `event_id`, `image_url`, `image_type`) VALUES
(7, 2, 'https://placehold.co/400x400/10b981/ffffff?text=Trail+Shirt+Front', 'merch');

-- --------------------------------------------------------

--
-- Table structure for table `form_fields`
--

CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_type` enum('text','email','tel','date','select','textarea') NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `options` text DEFAULT NULL COMMENT 'JSON encoded options for select type',
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_fields`
--

INSERT INTO `form_fields` (`id`, `event_id`, `field_label`, `field_name`, `field_type`, `is_required`, `options`, `sort_order`) VALUES
(1, 1, 'คำนำหน้าชื่อ', 'title', 'select', 1, '[\"นาย\",\"นาง\",\"นางสาว\"]', 0),
(2, 1, 'ชื่อจริง', 'first_name', 'text', 1, NULL, 1),
(3, 1, 'นามสกุล', 'last_name', 'text', 1, NULL, 2),
(4, 1, 'วัน/เดือน/ปีเกิด', 'birth_date', 'date', 1, NULL, 3),
(5, 1, 'หมายเลขบัตรประชาชน', 'thai_id', 'text', 1, NULL, 4),
(6, 1, 'อีเมล', 'email', 'email', 1, NULL, 5),
(7, 1, 'เบอร์โทรศัพท์', 'phone', 'tel', 1, NULL, 6);

-- --------------------------------------------------------

--
-- Table structure for table `master_genders`
--

CREATE TABLE `master_genders` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_genders`
--

INSERT INTO `master_genders` (`id`, `name`) VALUES
(1, 'ชาย'),
(2, 'หญิง');

-- --------------------------------------------------------

--
-- Table structure for table `master_pickup_options`
--

CREATE TABLE `master_pickup_options` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'e.g., รับด้วยตนเอง, จัดส่งทางไปรษณีย์',
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_pickup_options`
--

INSERT INTO `master_pickup_options` (`id`, `name`, `cost`) VALUES
(1, 'รับด้วยตนเองในวันงาน', 0.00),
(2, 'จัดส่งทางไปรษณีย์ (มีค่าใช้จ่ายเพิ่มเติม)', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `master_runner_types`
--

CREATE TABLE `master_runner_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'e.g., General, VIP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_runner_types`
--

INSERT INTO `master_runner_types` (`id`, `name`) VALUES
(2, 'VIP'),
(1, 'ทั่วไป (General)');

-- --------------------------------------------------------

--
-- Table structure for table `master_shirt_sizes`
--

CREATE TABLE `master_shirt_sizes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'e.g., XL',
  `description` varchar(100) DEFAULT NULL COMMENT 'e.g., (รอบอก 42")'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_shirt_sizes`
--

INSERT INTO `master_shirt_sizes` (`id`, `name`, `description`) VALUES
(1, 'XS', '(รอบอก 34\")'),
(2, 'S', '(รอบอก 36\")'),
(3, 'M', '(รอบอก 38\")'),
(4, 'L', '(รอบอก 40\")'),
(5, 'XL', '(รอบอก 42\")'),
(6, '2XL', '(รอบอก 44\")');

-- --------------------------------------------------------

--
-- Table structure for table `master_titles`
--

CREATE TABLE `master_titles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_titles`
--

INSERT INTO `master_titles` (`id`, `name`) VALUES
(2, 'นาง'),
(3, 'นางสาว'),
(1, 'นาย'),
(4, 'อื่นๆ');

-- --------------------------------------------------------

--
-- Table structure for table `race_categories`
--

CREATE TABLE `race_categories` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `distance` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('ชาย','หญิง') NOT NULL,
  `minAge` int(11) NOT NULL DEFAULT 0,
  `maxAge` int(11) NOT NULL DEFAULT 99
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `registration_code` varchar(20) NOT NULL COMMENT 'รหัสการสมัคร เช่น R001',
  `user_id` int(11) DEFAULT NULL COMMENT 'เชื่อมกับตาราง users ถ้าสมัครแบบ login',
  `event_id` int(11) NOT NULL,
  `distance_id` int(11) NOT NULL,
  `race_category_id` int(11) DEFAULT NULL,
  `bib_number` varchar(20) DEFAULT NULL,
  `corral` varchar(10) DEFAULT NULL COMMENT 'กลุ่มปล่อยตัวที่นักวิ่งถูกจัดให้อยู่',
  `shirt_size` varchar(10) NOT NULL,
  `status` enum('รอชำระเงิน','รอตรวจสอบ','ชำระเงินแล้ว') NOT NULL DEFAULT 'รอชำระเงิน',
  `payment_slip_url` text DEFAULT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(50) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `line_id` varchar(100) DEFAULT NULL,
  `thai_id` varchar(13) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `disease` varchar(255) DEFAULT 'ไม่มีโรคประจำตัว',
  `disease_detail` text DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `registration_code`, `user_id`, `event_id`, `distance_id`, `race_category_id`, `bib_number`, `corral`, `shirt_size`, `status`, `payment_slip_url`, `registered_at`, `title`, `first_name`, `last_name`, `gender`, `email`, `phone`, `line_id`, `thai_id`, `birth_date`, `disease`, `disease_detail`, `emergency_contact_name`, `emergency_contact_phone`) VALUES
(11, 'RUN2025-45A7FB', NULL, 1, 40, NULL, '0003', NULL, 'XL', 'ชำระเงินแล้ว', 'uploads/slip_1761045456_82b1fdae878124d5.jpg', '2025-10-21 11:17:36', 'นาย', 'ปฐวีกานต์', 'ศรีคราม', 'ชาย', 'adminmax@gmail.com', '0981051534', NULL, '1332000000946', '1994-12-07', 'ไม่มีโรคประจำตัว', NULL, 'ปฐวีกานต์', '0981051534');

-- --------------------------------------------------------

--
-- Table structure for table `registration_data`
--

CREATE TABLE `registration_data` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time NOT NULL,
  `activity` varchar(255) NOT NULL,
  `is_highlight` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Highlighted item'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `event_id`, `date`, `time`, `activity`, `is_highlight`) VALUES
(13, 1, '2025-03-15', '16:00:00', 'เปิดประตูงานและบริการรับฝากของ', 0),
(14, 1, '2025-03-15', '18:00:00', 'พิธีเปิดและวอร์มอัพรวม', 0),
(15, 1, '2025-03-15', '19:00:00', 'ปล่อยตัว (Start Time) ระยะ 10 KM', 1),
(16, 1, '2025-03-15', '19:30:00', 'ปล่อยตัว (Start Time) ระยะ 5 KM', 1),
(17, 1, '2025-03-15', '21:00:00', 'พิธีมอบรางวัลและคอนเสิร์ต', 0),
(18, 1, '2025-03-15', '22:00:00', 'กิจกรรมสิ้นสุด', 0);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('system_email', 'support@sisaketpaorun.com'),
('system_phone', '02-XXX-XXXX');

-- --------------------------------------------------------

--
-- Table structure for table `slides`
--

CREATE TABLE `slides` (
  `id` int(11) NOT NULL,
  `image_url` text NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `link_url` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slides`
--

INSERT INTO `slides` (`id`, `image_url`, `title`, `subtitle`, `link_url`, `sort_order`, `is_active`) VALUES
(1, 'https://placehold.co/800x250/ef4444/ffffff?text=PROMO+SLIDE', 'โปรโมชั่นพิเศษ! สมัครคู่ถูกกว่า 10%', 'เฉพาะ 100 คู่แรกของกิจกรรม SSKPAO RUN ถึง 31 ตุลาคมนี้เท่านั้น!', '?page=microsite&amp;event_code=sskpa-run-25', 1, 0),
(2, 'https://placehold.co/800x250/3b82f6/ffffff?text=NEWS+UPDATE', 'ประกาศ: เปลี่ยนเส้นทาง Mountain Trail ระยะ 25KM', 'โปรดตรวจสอบแผนที่ใหม่ในหน้ากิจกรรมก่อนการแข่งขัน', '?page=microsite&event_code=mountain-trail-challenge-25', 2, 1),
(3, 'https://placehold.co/800x250/22c55e/ffffff?text=LAST+CALL', 'โค้งสุดท้าย! SSKPAO RUN ปิดรับสมัครสัปดาห์หน้า', 'อย่าพลาดโอกาสในการเป็นส่วนหนึ่งของการวิ่งครั้งสำคัญนี้!', '?page=microsite&event_code=sskpa-run-25', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `assigned_event_id` int(11) DEFAULT NULL COMMENT 'ID กิจกรรมที่รับผิดชอบ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `username`, `password_hash`, `full_name`, `role`, `assigned_event_id`) VALUES
(1, 'admin', '$2a$12$zk8PKOASddu96PjkItPoFu9JyLOZMeoC4gg4.BoVElSsBKyKtcss2', 'Super Admin', 'admin', NULL),
(2, 'staff01', '$2a$12$zk8PKOASddu96PjkItPoFu9JyLOZMeoC4gg4.BoVElSsBKyKtcss2', 'คุณสมหญิง ใจดี', 'staff', 1),
(3, 'staff02', '$2a$12$zk8PKOASddu96PjkItPoFu9JyLOZMeoC4gg4.BoVElSsBKyKtcss2', 'คุณอดิศักดิ์ แข็งแรง', 'staff', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `thai_id` varchar(13) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `line_id` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `distances`
--
ALTER TABLE `distances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_code` (`event_code`);

--
-- Indexes for table `event_images`
--
ALTER TABLE `event_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `form_fields`
--
ALTER TABLE `form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `master_genders`
--
ALTER TABLE `master_genders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_pickup_options`
--
ALTER TABLE `master_pickup_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_runner_types`
--
ALTER TABLE `master_runner_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_shirt_sizes`
--
ALTER TABLE `master_shirt_sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `master_titles`
--
ALTER TABLE `master_titles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `race_categories`
--
ALTER TABLE `race_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `distance_id` (`distance_id`),
  ADD KEY `race_category_id` (`race_category_id`);

--
-- Indexes for table `registration_data`
--
ALTER TABLE `registration_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `thai_id` (`thai_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `distances`
--
ALTER TABLE `distances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `event_images`
--
ALTER TABLE `event_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `form_fields`
--
ALTER TABLE `form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `master_genders`
--
ALTER TABLE `master_genders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `master_pickup_options`
--
ALTER TABLE `master_pickup_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `master_runner_types`
--
ALTER TABLE `master_runner_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `master_shirt_sizes`
--
ALTER TABLE `master_shirt_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `master_titles`
--
ALTER TABLE `master_titles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `race_categories`
--
ALTER TABLE `race_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `registration_data`
--
ALTER TABLE `registration_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `slides`
--
ALTER TABLE `slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `race_categories`
--
ALTER TABLE `race_categories`
  ADD CONSTRAINT `fk_event_categories` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `fk_registration_category` FOREIGN KEY (`race_category_id`) REFERENCES `race_categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
