<?php
// templates/header.php
// ส่วนหัวของหน้าเว็บ ประกอบด้วย doctype, head, และ header navigation

// โหลดไฟล์ตั้งค่าและฟังก์ชัน
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// ดึงข้อมูล Global Settings
$global_settings = get_global_settings($mysqli);

// ตรวจสอบสถานะการ Login
$is_staff_logged_in = isset($_SESSION['staff_id']);
$is_runner_logged_in = isset($_SESSION['user_id']);
$runner_info = $is_runner_logged_in ? $_SESSION['user_info'] : null;
$staff_info = $is_staff_logged_in ? $_SESSION['staff_info'] : null;

// กำหนด Title ของหน้า (ตัวแปร $page_title ควรถูกตั้งค่าก่อน include ไฟล์นี้)
$title = isset($page_title) ? e($page_title) . ' | SISAKET PAO RUN' : 'SISAKET PAO RUN (Prototype)';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- NEW: Flatpickr Date Picker Library -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script> <!-- Thai Language File -->

    <style>
        body { font-family: 'Inter', 'Sarabun', sans-serif; transition: background-color 0.5s, color 0.5s; }
        :root { --color-primary: #3b82f6; /* Default: Blue-500 */ }
        .text-primary { color: var(--color-primary); }
        .bg-primary { background-color: var(--color-primary); }
        .border-primary { border-color: var(--color-primary); }
        .e-bib-mockup { background: linear-gradient(135deg, var(--color-primary), #1f2937); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }
        .event-cover-overlay { background: linear-gradient(to top, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.2)); }
        .description-content p { margin-bottom: 12px; line-height: 1.7; }
        .description-content ul { list-style-type: disc; margin-left: 20px; padding-left: 5px; margin-top: 10px; margin-bottom: 10px; }
        .description-content li { margin-bottom: 5px; line-height: 1.6; }
        #carousel-slides { display: flex; transition: transform 0.5s ease-in-out; }
    </style>
</head>
<body id="app-body" class="bg-gray-100 text-gray-900 min-h-screen">

    <!-- โครงสร้างหลักของแอปพลิเคชัน -->
    <div id="app-container" class="max-w-4xl mx-auto p-4 md:p-8">
        <!-- ส่วนหัว (Header) - โลโก้และระบบนำทาง -->
        <header id="app-header" class="flex justify-between items-center p-4 mb-8 rounded-xl shadow-lg bg-white">
            <div id="logo-section" class="flex items-center space-x-2 cursor-pointer" onclick="window.location.href='index.php'">
                <i class="fa-solid fa-running text-2xl text-primary"></i>
                <h1 class="text-xl font-bold">SISAKET PAO RUN</h1>
            </div>
            <nav id="main-nav">
                <?php if ($is_staff_logged_in): ?>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700 hidden sm:inline">
                            <i class="fa-solid fa-user-shield mr-1 text-red-600"></i> สวัสดี, <strong><?= e($staff_info['full_name']) ?></strong>
                        </span>
                        <a href="admin/index.php" class="text-gray-600 hover:text-primary transition"><i class="fa-solid fa-cogs mr-1"></i> หลังบ้าน</a>
                        <a href="admin/logout.php" class="py-2 px-4 rounded-lg bg-red-500 text-white hover:bg-red-600 transition text-sm font-bold">
                            <i class="fa-solid fa-sign-out-alt mr-1"></i> ออกจากระบบ
                        </a>
                    </div>
                <?php elseif ($is_runner_logged_in): ?>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700 hidden sm:inline">
                            <i class="fa-solid fa-user-check mr-1 text-green-600"></i> สวัสดี, <strong><?= e($runner_info['first_name']) ?></strong>
                        </span>
                         <a href="index.php?page=dashboard" class="text-gray-600 hover:text-primary transition">
                            <i class="fa-solid fa-gauge-high mr-1"></i> แดชบอร์ด
                        </a>
                        <a href="actions/logout.php" class="py-2 px-4 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition text-sm font-bold">
                            <i class="fa-solid fa-sign-out-alt mr-1"></i> ออกจากระบบ
                        </a>
                    </div>
                <?php else: ?>
                    <a href="index.php?page=dashboard" class="text-gray-600 hover:text-primary transition">
                        <i class="fa-solid fa-user-circle mr-1"></i> สำหรับผู้สมัคร
                    </a>
                <?php endif; ?>
            </nav>
        </header>

        <!-- เนื้อหาหลักจะถูกโหลดตรงนี้ -->
        <main id="main-content" class="bg-white p-6 rounded-xl shadow-lg">

