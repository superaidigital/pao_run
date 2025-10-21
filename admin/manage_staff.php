<?php
// actions/manage_staff.php
// สคริปต์สำหรับจัดการ (เพิ่ม/ลบ) บัญชีเจ้าหน้าที่

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Super Admin Permission ---
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: ../admin/login.php'); 
    exit;
}

// --- POST Request Check ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $action = $_POST['action'];

    // --- ACTION: CREATE ---
    if ($action === 'create') {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $full_name = isset($_POST['full_name']) ? e($_POST['full_name']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $role = isset($_POST['role']) ? e($_POST['role']) : 'staff';
        $assigned_event_id = !empty($_POST['assigned_event_id']) ? intval($_POST['assigned_event_id']) : null;
        
        // Validation
        if (empty($username) || empty($full_name) || empty($password) || strlen($password) < 6) {
             $_SESSION['update_error'] = "ข้อมูลไม่ถูกต้อง (Username, ชื่อเต็ม, รหัสผ่าน 6+ ตัวอักษร)";
        } else {
            // Check for duplicate username
            $stmt_check = $mysqli->prepare("SELECT id FROM staff WHERE username = ?");
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                 $_SESSION['update_error'] = "Username นี้มีอยู่ในระบบแล้ว";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $mysqli->prepare("INSERT INTO staff (username, password_hash, full_name, role, assigned_event_id) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("ssssi", $username, $password_hash, $full_name, $role, $assigned_event_id);
                if ($stmt_insert->execute()) {
                    $_SESSION['update_success'] = "สร้างบัญชี '".e($username)."' สำเร็จ";
                } else {
                    $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการสร้างบัญชี";
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }

    // --- ACTION: DELETE ---
    if ($action === 'delete') {
        $staff_id_to_delete = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
        
        // Cannot delete self
        if ($staff_id_to_delete === $_SESSION['staff_id']) {
            $_SESSION['update_error'] = "ไม่สามารถลบบัญชีของตนเองได้";
        } elseif ($staff_id_to_delete > 0) {
            $stmt = $mysqli->prepare("DELETE FROM staff WHERE id = ?");
            $stmt->bind_param("i", $staff_id_to_delete);
            if ($stmt->execute()) {
                $_SESSION['update_success'] = "ลบบัญชีสำเร็จ";
            } else {
                $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการลบบัญชี";
            }
            $stmt->close();
        }
    }
    
    // Redirect back to the management page
    header('Location: ../admin/staff_management.php');
    exit;

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>
