<?php
// actions/staff_login.php
// สคริปต์สำหรับประมวลผลการเข้าสู่ระบบของเจ้าหน้าที่ (เวอร์ชันอัปเกรด)

// --- DEBUGGING: Force display of errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

require_once '../config.php';
require_once '../functions.php';

// --- Explicitly check for mysqli connection object ---
if (!isset($mysqli) || $mysqli->connect_error) {
    error_log("MySQLi connection error in staff_login.php: " . (isset($mysqli) ? $mysqli->connect_error : "mysqli object not found"));
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ";
    header('Location: ../admin/login.php');
    exit;
}


// ตรวจสอบว่าเป็น Request แบบ POST เท่านั้น
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. รับข้อมูลจากฟอร์ม ---
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "กรุณากรอก Username และ Password";
        header('Location: ../admin/login.php');
        exit;
    }

    // --- 2. ค้นหาผู้ใช้จาก username ในฐานข้อมูล ---
    try {
        $stmt = $mysqli->prepare("SELECT id, username, password_hash, full_name, role, assigned_event_id FROM staff WHERE username = ? LIMIT 1");
        if ($stmt === false) {
            throw new Exception("Prepare statement failed: " . $mysqli->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $staff = $result->fetch_assoc();

            // --- 3. ตรวจสอบรหัสผ่าน ---
            if (password_verify($password, $staff['password_hash'])) {
                // รหัสผ่านถูกต้อง: สร้าง Session
                session_regenerate_id(true); 
                
                $_SESSION['staff_id'] = $staff['id'];
                $_SESSION['staff_info'] = [
                    'username' => $staff['username'],
                    'full_name' => $staff['full_name'],
                    'role' => $staff['role'],
                    'assigned_event_id' => $staff['assigned_event_id']
                ];

                // ส่งไปหน้า Admin Dashboard
                header('Location: ../admin/index.php');
                exit;

            } else {
                $_SESSION['error_message'] = "Username หรือ Password ไม่ถูกต้อง";
                header('Location: ../admin/login.php');
                exit;
            }
        } else {
            $_SESSION['error_message'] = "Username หรือ Password ไม่ถูกต้อง";
            header('Location: ../admin/login.php');
            exit;
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่อีกครั้ง";
        header('Location: ../admin/login.php');
        exit;
    }

} else {
    header('Location: ../index.php');
    exit;
}
?>

