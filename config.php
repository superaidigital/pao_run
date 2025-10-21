<?php
// config.php
// ไฟล์สำหรับตั้งค่าการเชื่อมต่อฐานข้อมูลและค่าพื้นฐานของระบบ

// --- การตั้งค่าการเชื่อมต่อฐานข้อมูล ---
// กรุณาเปลี่ยนค่าเหล่านี้ให้ตรงกับการตั้งค่าเซิร์ฟเวอร์ของคุณ
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pao_run_db');

// --- เริ่มการทำงานของ Session ---
// ใช้สำหรับจัดการการ login ของผู้ใช้และเจ้าหน้าที่
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- สร้างการเชื่อมต่อฐานข้อมูล ---
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- ตรวจสอบการเชื่อมต่อ ---
if ($mysqli->connect_error) {
    // หากเชื่อมต่อไม่ได้ ให้แสดงข้อผิดพลาดและหยุดการทำงาน
    die("Connection failed: " . $mysqli->connect_error);
}

// --- ตั้งค่า Character Set เป็น UTF-8 ---
// เพื่อให้รองรับภาษาไทยได้อย่างถูกต้อง
$mysqli->set_charset("utf8mb4");

// --- กำหนด Timezone พื้นฐาน ---
date_default_timezone_set('Asia/Bangkok');

?>
