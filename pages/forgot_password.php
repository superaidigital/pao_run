<?php
// pages/forgot_password.php
// หน้าสำหรับกรอกอีเมลเพื่อขอรีเซ็ตรหัสผ่าน

$page_title = 'ลืมรหัสผ่าน';

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);
?>

<div class="max-w-md mx-auto">
    <div class="text-center mb-6">
        <h2 class="text-3xl font-extrabold text-gray-800">ลืมรหัสผ่าน?</h2>
        <p class="mt-2 text-gray-600">ไม่ต้องกังวล! กรอกอีเมลของคุณด้านล่าง แล้วเราจะส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ไปให้</p>
    </div>

    <?php if ($success_message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p><?= e($error_message) ?></p></div>
    <?php endif; ?>

    <form action="actions/request_password_reset.php" method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">อีเมลที่ลงทะเบียนไว้</label>
            <input type="email" id="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-primary focus:border-primary">
        </div>

        <div class="flex justify-end items-center pt-4 border-t">
             <a href="index.php?page=dashboard" class="text-sm text-gray-600 hover:text-primary mr-4">กลับไปหน้าเข้าสู่ระบบ</a>
            <button type="submit" class="bg-primary text-white font-bold py-2 px-6 rounded-lg hover:opacity-90 transition">
                ส่งลิงก์รีเซ็ตรหัสผ่าน
            </button>
        </div>
    </form>
</div>
