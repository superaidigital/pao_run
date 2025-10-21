<?php
// actions/update_profile.php
// สคริปต์สำหรับอัปเดตข้อมูลส่วนตัวของนักวิ่ง (เวอร์ชันอัปเกรด)

require_once '../config.php';
require_once '../functions.php';

// --- Session Check for Runner ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); 
    exit;
}
$user_id = $_SESSION['user_id'];

// ฟังก์ชันสำหรับจัดการข้อผิดพลาดและ Redirect
function handle_error($message) {
    $_SESSION['update_error'] = $message;
    // Save form data to repopulate form, but not the password
    unset($_POST['new_password']);
    unset($_POST['confirm_password']);
    $_SESSION['form_data'] = $_POST;
    header('Location: ../index.php?page=edit_profile');
    exit;
}

// --- Check for POST request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Get and Sanitize Data ---
    $first_name = isset($_POST['first_name']) ? e($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? e($_POST['last_name']) : '';
    $phone = isset($_POST['phone']) ? e($_POST['phone']) : '';
    $line_id = isset($_POST['line_id']) ? e($_POST['line_id']) : '';
    $thai_id = isset($_POST['thai_id']) ? e($_POST['thai_id']) : '';
    $address = isset($_POST['address']) ? e($_POST['address']) : '';
    
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // --- 2. Validation ---
    if (empty($first_name) || empty($last_name)) {
        handle_error("กรุณากรอกชื่อและนามสกุลให้ครบถ้วน");
    }

    $password_update_sql = "";
    $params = [$first_name, $last_name, $phone, $line_id, $thai_id, $address];
    $types = "ssssss";

    // --- Password Change Logic ---
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            handle_error("รหัสผ่านใหม่และการยืนยันไม่ตรงกัน");
        }
        if (strlen($new_password) < 8) { // Match form hint
            handle_error("รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร");
        }
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $password_update_sql = ", password_hash = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    $params[] = $user_id;
    $types .= "i";
    
    // --- 3. Update Database ---
    try {
        $query = "UPDATE users SET 
                    first_name = ?, last_name = ?, phone = ?, line_id = ?, thai_id = ?, address = ?
                    $password_update_sql 
                  WHERE id = ?";
                  
        $stmt = $mysqli->prepare($query);
        if ($stmt === false) throw new Exception("Prepare statement failed: " . $mysqli->error);
        
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            unset($_SESSION['form_data']); // Clear old form data on success
            $_SESSION['update_success'] = "อัปเดตข้อมูลโปรไฟล์ของคุณเรียบร้อยแล้ว";
            // Update session info if name changed
            $_SESSION['user_info']['first_name'] = $first_name;
            $_SESSION['user_info']['last_name'] = $last_name;
        } else {
            throw new Exception("Database update failed: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        handle_error("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
    }

    // --- 4. Redirect Back ---
    header('Location: ../index.php?page=edit_profile');
    exit;

} else {
    header('Location: ../index.php');
    exit;
}
?>

