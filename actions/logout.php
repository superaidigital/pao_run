<?php
// actions/logout.php
// สคริปต์สำหรับออกจากระบบของผู้ใช้ (ทั้งนักวิ่งและเจ้าหน้าที่)

// เริ่มต้น Session เพื่อที่จะทำลายมัน
session_start();

// ลบตัวแปร Session ทั้งหมด
$_SESSION = array();

// ทำลาย Cookie ของ Session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย Session อย่างสมบูรณ์
session_destroy();

// ส่งผู้ใช้กลับไปยังหน้าแรก
header('Location: ../index.php');
exit;
?>
