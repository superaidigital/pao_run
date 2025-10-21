<?php
// actions/runner_login.php
// สคริปต์สำหรับประมวลผลการเข้าสู่ระบบของนักวิ่ง (เวอร์ชันอัปเกรด)

require_once '../config.php';
require_once '../functions.php';

// ตรวจสอบว่าเป็น Request แบบ POST เท่านั้น
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. รับข้อมูลจากฟอร์ม ---
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "กรุณากรอกอีเมลและรหัสผ่าน";
        header('Location: ../index.php?page=dashboard');
        exit;
    }

    // --- 2. ค้นหาผู้ใช้จากอีเมลในฐานข้อมูล ---
    try {
        $stmt = $mysqli->prepare("SELECT id, email, password_hash, first_name, last_name, thai_id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // --- 3. ตรวจสอบรหัสผ่าน ---
            if (password_verify($password, $user['password_hash'])) {
                // รหัสผ่านถูกต้อง: สร้าง Session
                
                session_regenerate_id(true); 
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_info'] = [
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ];

                // --- NEW: LINK PAST REGISTRATIONS ---
                // ค้นหาการสมัครเก่าๆ ที่ใช้ "อีเมล" หรือ "เลขบัตรประชาชน" เดียวกัน
                // และยังไม่มีการผูกกับ user_id แล้วทำการผูกให้โดยอัตโนมัติ
                if (!empty($user['thai_id'])) {
                    $link_stmt = $mysqli->prepare("UPDATE registrations SET user_id = ? WHERE (email = ? OR thai_id = ?) AND user_id IS NULL");
                    $link_stmt->bind_param("iss", $user['id'], $user['email'], $user['thai_id']);
                } else {
                    // Fallback for older accounts that might not have a Thai ID
                    $link_stmt = $mysqli->prepare("UPDATE registrations SET user_id = ? WHERE email = ? AND user_id IS NULL");
                    $link_stmt->bind_param("is", $user['id'], $user['email']);
                }
                $link_stmt->execute();
                $link_stmt->close();
                // --- END LINK ---


                // ส่งกลับไปหน้า Dashboard (ซึ่งจะแสดงผลแบบ logged in พร้อมประวัติทั้งหมด)
                header('Location: ../index.php?page=dashboard');
                exit;

            } else {
                // รหัสผ่านไม่ถูกต้อง
                $_SESSION['error_message'] = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
                header('Location: ../index.php?page=dashboard');
                exit;
            }
        } else {
            // ไม่พบอีเมลในระบบ
            $_SESSION['error_message'] = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
            header('Location: ../index.php?page=dashboard');
            exit;
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่อีกครั้ง";
        header('Location: ../index.php?page=dashboard');
        exit;
    }

} else {
    // ถ้าไม่ได้เข้ามาด้วยวิธี POST ให้กลับไปหน้าแรก
    header('Location: ../index.php');
    exit;
}
?>

