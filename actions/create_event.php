<?php
// actions/create_event.php
// สคริปต์สำหรับสร้างกิจกรรมใหม่

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Super Admin Permission ---
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: ../admin/login.php'); 
    exit;
}

// --- POST Request Check ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Get and Sanitize Data ---
    $name = isset($_POST['name']) ? e($_POST['name']) : '';
    $event_code = isset($_POST['event_code']) ? trim($_POST['event_code']) : '';
    $start_date = isset($_POST['start_date']) ? e($_POST['start_date']) : '';

    // --- 2. Validation ---
    if (empty($name) || empty($event_code) || empty($start_date)) {
        $_SESSION['update_error'] = "กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน";
        header('Location: ../admin/create_event.php');
        exit;
    }

    // Validate event_code format (alphanumeric and dashes)
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $event_code)) {
         $_SESSION['update_error'] = "รหัสอ้างอิง (Event Code) สามารถใช้ได้เฉพาะตัวอักษรภาษาอังกฤษ, ตัวเลข, และขีดกลาง (-) เท่านั้น";
         header('Location: ../admin/create_event.php');
         exit;
    }

    // --- 3. Check for Duplicate Event Code ---
    $stmt_check = $mysqli->prepare("SELECT id FROM events WHERE event_code = ?");
    $stmt_check->bind_param("s", $event_code);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['update_error'] = "รหัสอ้างอิง '".e($event_code)."' นี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น";
        $stmt_check->close();
        header('Location: ../admin/create_event.php');
        exit;
    }
    $stmt_check->close();

    // --- 4. Insert into Database ---
    try {
        $stmt_insert = $mysqli->prepare("INSERT INTO events (name, event_code, start_date) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $name, $event_code, $start_date);

        if ($stmt_insert->execute()) {
            $new_event_id = $mysqli->insert_id;
            $_SESSION['update_success'] = "สร้างกิจกรรม '".e($name)."' สำเร็จแล้ว! กรุณาตั้งค่ารายละเอียดเพิ่มเติม";
            // Redirect to the settings page of the newly created event
            header('Location: ../admin/event_settings.php?event_id=' . $new_event_id);
            exit;
        } else {
            throw new Exception("Database insert failed: " . $stmt_insert->error);
        }
        $stmt_insert->close();

    } catch (Exception $e) {
        error_log("Event Creation Failed: " . $e->getMessage());
        $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการสร้างกิจกรรม";
        header('Location: ../admin/create_event.php');
        exit;
    }

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>
