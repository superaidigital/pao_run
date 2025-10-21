<?php
// actions/manage_categories.php
// สคริปต์สำหรับจัดการ (เพิ่ม/ลบ) รุ่นการแข่งขัน

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Permission ---
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../admin/login.php'); 
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');

// --- POST Request Check ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $action = $_POST['action'];
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

    // --- Security Check ---
    if ($event_id === 0) {
        $_SESSION['update_error'] = "Invalid Event ID.";
        header('Location: ../admin/index.php');
        exit;
    }
    if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
        $_SESSION['update_error'] = "You do not have permission for this event.";
        header('Location: ../admin/index.php');
        exit;
    }

    try {
        // --- ACTION: CREATE CATEGORY ---
        if ($action === 'create_category') {
            $distance = isset($_POST['distance']) ? e($_POST['distance']) : '';
            $name = isset($_POST['name']) ? e($_POST['name']) : '';
            $gender = isset($_POST['gender']) ? e($_POST['gender']) : '';
            $minAge = isset($_POST['minAge']) ? intval($_POST['minAge']) : 0;
            $maxAge = isset($_POST['maxAge']) ? intval($_POST['maxAge']) : 99;

            if (empty($distance) || empty($name) || empty($gender)) {
                throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
            }
            
            $stmt = $mysqli->prepare("INSERT INTO race_categories (event_id, distance, name, gender, minAge, maxAge) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssii", $event_id, $distance, $name, $gender, $minAge, $maxAge);
            
            if ($stmt->execute()) {
                $_SESSION['update_success'] = "เพิ่มรุ่นการแข่งขันสำเร็จ";
            } else {
                throw new Exception("Database insert failed: " . $stmt->error);
            }
            $stmt->close();
        }

        // --- ACTION: DELETE CATEGORY ---
        if ($action === 'delete_category') {
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            
            if ($category_id === 0) {
                throw new Exception("Invalid Category ID.");
            }

            $stmt = $mysqli->prepare("DELETE FROM race_categories WHERE id = ? AND event_id = ?");
            $stmt->bind_param("ii", $category_id, $event_id);
            
            if ($stmt->execute()) {
                $_SESSION['update_success'] = "ลบรุ่นการแข่งขันสำเร็จ";
            } else {
                throw new Exception("Database delete failed: " . $stmt->error);
            }
            $stmt->close();
        }

    } catch (Exception $e) {
        $_SESSION['update_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
    
    // Redirect back to the categories page
    header('Location: ../admin/event_categories.php?event_id=' . $event_id);
    exit;

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>