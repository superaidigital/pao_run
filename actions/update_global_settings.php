<?php
// actions/update_global_settings.php
// สคริปต์สำหรับอัปเดตการตั้งค่าระบบส่วนกลาง

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Super Admin Permission ---
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: ../admin/login.php'); 
    exit;
}

// --- POST Request Check ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['settings'])) {

    $posted_settings = $_POST['settings'];

    // --- Database Transaction ---
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        foreach ($posted_settings as $key => $value) {
            $sanitized_value = e($value);
            $stmt->bind_param("ss", $sanitized_value, $key);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update setting: " . $key);
            }
        }
        $stmt->close();
        
        $mysqli->commit();
        $_SESSION['update_success'] = "การตั้งค่าระบบส่วนกลางได้รับการอัปเดตเรียบร้อยแล้ว";

    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Global Settings Update Failed: " . $e->getMessage());
        $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
    }

    // Redirect back to the settings page
    header('Location: ../admin/global_settings.php');
    exit;

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>
