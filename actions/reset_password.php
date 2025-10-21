<?php
// actions/reset_password.php

require_once '../config.php';
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['update_error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header('Location: ../index.php?page=reset_password&token=' . urlencode($token));
        exit;
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['update_error'] = "รหัสผ่านใหม่และการยืนยันไม่ตรงกัน";
        header('Location: ../index.php?page=reset_password&token=' . urlencode($token));
        exit;
    }
    
    if (strlen($new_password) < 6) {
        $_SESSION['update_error'] = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        header('Location: ../index.php?page=reset_password&token=' . urlencode($token));
        exit;
    }

    try {
        // Find user by valid token
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            // Hash the new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password and clear token
            $update_stmt = $mysqli->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
            $update_stmt->bind_param("si", $password_hash, $user_id);
            $update_stmt->execute();

            $_SESSION['success_message'] = "เปลี่ยนรหัสผ่านสำเร็จแล้ว! กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่";
            header('Location: ../index.php?page=dashboard');
            exit;

        } else {
            $_SESSION['error_message'] = "ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว";
            header('Location: ../index.php?page=forgot_password');
            exit;
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในระบบ";
        header('Location: ../index.php?page=forgot_password');
        exit;
    }
}
?>
