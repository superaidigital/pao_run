<?php
// admin/logout.php
// สคริปต์สำหรับออกจากระบบของเจ้าหน้าที่

// เริ่มต้น Session เพื่อที่จะทำลายมัน
session_start();

// ลบตัวแปร Session ที่เกี่ยวข้องกับ staff
unset($_SESSION['staff_id']);
unset($_SESSION['staff_info']);

// ทำลาย Session หากไม่มีข้อมูลอื่นเหลืออยู่
// หรือจะทำลายทั้งหมดเลยก็ได้ ขึ้นอยู่กับว่ามี session สำหรับส่วนอื่นหรือไม่
if (empty($_SESSION)) {
    session_destroy();
}

// ส่งผู้ใช้กลับไปยังหน้า Login ของ Admin
header('Location: login.php');
exit;
?>
