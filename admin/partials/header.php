<?php
// admin/partials/header.php
// ส่วนหัวและ Navigation Bar ที่ใช้ร่วมกันในระบบ Admin

// --- CORE BOOTSTRAP (Centralized) ---
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');
// --- END BOOTSTRAP ---

// The $page_title variable should be set in the parent page before including this header.
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Admin Panel') ?> | SISAKET PAO RUN</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    
    <style> 
        body { 
            font-family: 'Inter', 'Sarabun', sans-serif; 
        } 
    </style>
</head>
<body class="bg-gray-100">

    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="flex items-center">
                        <i class="fa-solid fa-user-shield text-2xl text-red-500"></i>
                        <span class="ml-3 font-bold text-xl text-gray-800">Admin Panel</span>
                    </a>
                    <?php if ($is_super_admin): ?>
                    <div class="hidden sm:flex items-center space-x-4 border-l pl-6">
                        <a href="create_event.php" class="text-gray-600 hover:text-red-500 text-sm font-medium">สร้างกิจกรรมใหม่</a>
                        <a href="staff_management.php" class="text-gray-600 hover:text-red-500 text-sm font-medium">จัดการเจ้าหน้าที่</a>
                        
                        <a href="slides_management.php" class="text-gray-600 hover:text-red-500 text-sm font-medium">จัดการสไลด์</a>
                        <a href="master_data.php" class="text-gray-600 hover:text-red-500 text-sm font-medium">ข้อมูลพื้นฐาน</a>
                        <a href="global_settings.php" class="text-gray-600 hover:text-red-500 text-sm font-medium">ตั้งค่าระบบ</a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center">
                    <span class="hidden sm:inline text-sm text-gray-600 mr-4">สวัสดี, <strong><?= e($staff_info['full_name']) ?></strong></span>
                    <a href="../index.php" target="_blank" class="text-gray-500 hover:text-blue-600 mr-4 text-sm" title="กลับสู่หน้าหลัก">
                        <i class="fa-solid fa-home text-lg"></i>
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                        ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">