<?php
// index.php - The main router for the application.
// This file directs traffic to the correct page based on the 'page' URL parameter.

// Load the core configuration and helper functions.
require_once 'config.php';
require_once 'functions.php';

// Determine the requested page from the URL query string. Default to 'home' if not set.
$page = $_GET['page'] ?? 'home';

// Define a whitelist of allowed pages to prevent security vulnerabilities like directory traversal.
$allowed_pages = [
    'home', 
    'microsite', 
    'registration', 
    'dashboard', 
    'register_member', 
    'edit_profile', 
    'forgot_password', 
    'reset_password',
    'ebib',
    'search_runner' // เพิ่มหน้าใหม่ที่นี่
];

// If the requested page is not in the allowed list, default back to the home page.
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Set the page title for the <title> tag based on the current page.
switch ($page) {
    case 'microsite':
        $page_title = 'รายละเอียดกิจกรรม';
        break;
    case 'registration':
        $page_title = 'สมัครเข้าร่วมกิจกรรม';
        break;
    case 'dashboard':
        $page_title = 'แดชบอร์ด';
        break;
    case 'register_member':
        $page_title = 'สมัครสมาชิกใหม่';
        break;
    case 'edit_profile':
        $page_title = 'แก้ไขข้อมูลส่วนตัว';
        break;
    case 'forgot_password':
        $page_title = 'ลืมรหัสผ่าน';
        break;
    case 'reset_password':
        $page_title = 'ตั้งรหัสผ่านใหม่';
        break;
    case 'ebib':
        $page_title = 'E-BIB';
        break;
    case 'search_runner': // เพิ่ม case สำหรับ title
        $page_title = 'ค้นหานักวิ่ง';
        break;
    default:
        $page_title = 'หน้าหลัก';
}

// Include the header template, which contains the opening HTML, <head> section, and top navigation.
include 'templates/header.php';

// Include the specific page content based on the validated $page variable.
include 'pages/' . $page . '.php';

// Include the footer template, which contains the footer content and closing HTML tags.
include 'templates/footer.php';

// Gracefully close the database connection if it was successfully established.
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}
?>

