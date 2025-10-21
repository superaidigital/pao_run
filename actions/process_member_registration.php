<?php
// actions/process_member_registration.php
// สคริปต์สำหรับประมวลผลการสมัครสมาชิกใหม่ของนักวิ่ง (เวอร์ชันปรับปรุง UI)

// --- DEBUGGING ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

require_once '../config.php';
require_once '../functions.php';

// --- Explicitly check for mysqli connection object ---
if (!isset($mysqli) || $mysqli->connect_error) {
    error_log("MySQLi connection error in process_member_registration.php: " . (isset($mysqli) ? $mysqli->connect_error : "mysqli object not found"));
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ";
    header('Location: ../index.php?page=register_member');
    exit;
}

// ฟังก์ชันสำหรับจัดการข้อผิดพลาดและ Redirect
function handle_error($message) {
    $_SESSION['error_message'] = $message;
    // Save form data to repopulate form, but not the password
    unset($_POST['password']);
    unset($_POST['confirm_password']);
    $_SESSION['form_data'] = $_POST;
    header('Location: ../index.php?page=register_member');
    exit;
}


// ตรวจสอบว่าเป็น Request แบบ POST เท่านั้น
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. รับและ Sanitize ข้อมูล ---
    $first_name = isset($_POST['first_name']) ? e($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? e($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // NOTE: thai_id and phone are no longer required for registration from this form.

    // --- 2. ตรวจสอบความถูกต้องของข้อมูล (Validation) ---
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        handle_error("กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน");
    }

    if ($password !== $confirm_password) {
        handle_error("รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน");
    }

    // Changed to 8 characters to match the form hint
    if (strlen($password) < 8) {
        handle_error("รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handle_error("รูปแบบอีเมลไม่ถูกต้อง");
    }

    // --- 3. ตรวจสอบข้อมูลซ้ำในฐานข้อมูล ---
    try {
        // ตรวจสอบ Email
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt === false) throw new Exception("Prepare statement failed for email check.");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            handle_error("อีเมลนี้ถูกใช้งานแล้วในระบบ");
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        handle_error("เกิดข้อผิดพลาดในการตรวจสอบข้อมูล");
    }


    // --- 4. เข้ารหัสรหัสผ่านและบันทึกข้อมูล ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $mysqli->prepare(
            "INSERT INTO users (email, password_hash, first_name, last_name) 
            VALUES (?, ?, ?, ?)"
        );
        if ($stmt === false) throw new Exception("Prepare statement failed for user insert.");
        
        $stmt->bind_param("ssss", $email, $password_hash, $first_name, $last_name);
        
        if ($stmt->execute()) {
            // Clear any form data from session on success
            unset($_SESSION['form_data']);
            $_SESSION['success_message'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบเพื่อใช้งาน";
            header('Location: ../index.php?page=dashboard');
            exit;
        } else {
            throw new Exception("Database execution failed.");
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        handle_error("เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง");
    }

} else {
    // ถ้าไม่ได้เข้ามาด้วยวิธี POST ให้กลับไปหน้าแรก
    header('Location: ../index.php');
    exit;
}
?>

