<?php
// pages/reset_password.php
// หน้าสำหรับตั้งรหัสผ่านใหม่

$page_title = 'ตั้งรหัสผ่านใหม่';

// --- Validate Token ---
$token = isset($_GET['token']) ? $_GET['token'] : '';
if (empty($token)) {
    $_SESSION['error_message'] = "Invalid or missing reset token.";
    header('Location: index.php?page=dashboard');
    exit;
}

// Check if token is valid and not expired
$stmt = $mysqli->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว กรุณาลองอีกครั้ง";
    header('Location: index.php?page=forgot_password');
    exit;
}
$stmt->close();


// Check for session messages
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);
?>

<div class="max-w-md mx-auto">
    <div class="text-center mb-6">
        <h2 class="text-3xl font-extrabold text-gray-800">ตั้งรหัสผ่านใหม่</h2>
        <p class="mt-2 text-gray-600">กรอกรหัสผ่านใหม่ของคุณด้านล่าง</p>
    </div>

    <?php if ($error_message): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p><?= e($error_message) ?></p></div>
    <?php endif; ?>

    <form action="actions/reset_password.php" method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-6">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        
        <div>
            <label for="new_password" class="block text-sm font-medium text-gray-700">รหัสผ่านใหม่</label>
            <input type="password" id="new_password" name="new_password" required class="mt-1 block w-full border border-gray-300 rounded-lg p-3">
            <p class="text-xs text-gray-500 mt-1">ความยาวอย่างน้อย 6 ตัวอักษร</p>
        </div>
        <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 block w-full border border-gray-300 rounded-lg p-3">
        </div>

        <div class="flex justify-end pt-4 border-t">
            <button type="submit" class="bg-primary text-white font-bold py-2 px-6 rounded-lg hover:opacity-90 transition">
                บันทึกรหัสผ่านใหม่
            </button>
        </div>
    </form>
</div>