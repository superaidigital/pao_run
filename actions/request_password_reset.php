<?php
// actions/request_password_reset.php

require_once '../config.php';
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['update_error'] = "รูปแบบอีเมลไม่ถูกต้อง";
        header('Location: ../index.php?page=forgot_password');
        exit;
    }

    try {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // User found, generate token
            $token = bin2hex(random_bytes(32));
            $expires = new DateTime('now + 1 hour');
            $expires_str = $expires->format('Y-m-d H:i:s');

            $update_stmt = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
            $update_stmt->bind_param("sss", $token, $expires_str, $email);
            $update_stmt->execute();

            // ** SIMULATE EMAIL SENDING **
            // In a real application, you would send an email here.
            // For this project, we'll show a message with the link for easy testing.
            $reset_link = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}/../../index.php?page=reset_password&token={$token}";
            $_SESSION['update_success'] = "หากอีเมลนี้มีอยู่ในระบบ ลิงก์สำหรับรีเซ็ตรหัสผ่านจะถูกส่งไปให้ (สำหรับทดสอบ: <a href='{$reset_link}' class='underline'>คลิกที่นี่</a>)";

        } else {
            // Email not found, but we show the same message for security reasons
            $_SESSION['update_success'] = "หากอีเมลนี้มีอยู่ในระบบ ลิงก์สำหรับรีเซ็ตรหัสผ่านจะถูกส่งไปให้";
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['update_error'] = "เกิดข้อผิดพลาดในระบบ";
    }

    header('Location: ../index.php?page=forgot_password');
    exit;
}
?>
