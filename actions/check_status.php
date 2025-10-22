<?php
// actions/check_status.php
// สคริปต์สำหรับตรวจสอบสถานะการสมัครจากฐานข้อมูล

require_once '../config.php';
require_once '../functions.php';

// ตรวจสอบว่าเป็น Request แบบ POST เท่านั้น
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search_query = isset($_POST['search_query']) ? e(trim($_POST['search_query'])) : '';

    if (empty($search_query)) {
        $_SESSION['search_error'] = "กรุณากรอกข้อมูลเพื่อค้นหา";
        header('Location: ../index.php?page=dashboard');
        exit;
    }

    // เตรียมคำสั่ง SQL เพื่อค้นหาข้อมูลจากทั้ง thai_id และ registration_code
    $stmt = $mysqli->prepare("
        SELECT 
            r.registration_code, r.title, r.first_name, r.last_name, r.status, r.bib_number, r.shirt_size, r.email, r.phone,
            e.name AS event_name, e.color_code,
            d.name AS distance_name,
            rc.name AS category_name
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        JOIN distances d ON r.distance_id = d.id
        LEFT JOIN race_categories rc ON r.race_category_id = rc.id
        WHERE r.thai_id = ? OR r.registration_code = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $search_query, $search_query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // หากพบข้อมูล ให้เก็บผลลัพธ์ไว้ใน Session
        $_SESSION['search_result'] = $result->fetch_assoc();
    } else {
        // หากไม่พบ ให้เก็บข้อความผิดพลาดไว้ใน Session
        $_SESSION['search_error'] = "ไม่พบข้อมูลการสมัครสำหรับ '" . htmlspecialchars($search_query) . "'";
    }
    $stmt->close();

} else {
    // หากไม่ใช่ POST request ให้แจ้งข้อผิดพลาด
    $_SESSION['search_error'] = "Invalid request method.";
}

// ส่งผู้ใช้กลับไปที่หน้า dashboard เสมอ
header('Location: ../index.php?page=dashboard');
exit;
?>
