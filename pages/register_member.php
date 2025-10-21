<?php
// pages/register_member.php
// หน้าสมัครสมาชิกใหม่สำหรับนักวิ่ง (เวอร์ชันปรับปรุง UI)

$page_title = 'สมัครสมาชิกใหม่';

// ดึงข้อความแจ้งเตือนจาก Session
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
unset($_SESSION['error_message']);

// ดึงข้อมูลฟอร์มเก่าจาก Session (ถ้ามี) เพื่อเติมค่ากลับเข้าไป
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>

<div class="max-w-lg mx-auto bg-white p-8 rounded-xl shadow-lg">
    
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-blue-600 flex items-center justify-center gap-3">
            <i class="fa-solid fa-user-plus"></i>
            สร้างบัญชีผู้ใช้ใหม่
        </h2>
        <p class="mt-3 text-gray-600">กรอกข้อมูลด้านล่างเพื่อสมัครสมาชิก และใช้เข้าสู่ระบบเพื่อความสะดวกในการสมัครกิจกรรมครั้งถัดไป</p>
    </div>

    <?php if ($error_message): ?>
    <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
        <span class="block sm:inline"><?= e($error_message) ?></span>
    </div>
    <?php endif; ?>

    <form action="actions/process_member_registration.php" method="POST" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">ชื่อจริง <span class="text-red-500">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?= e($form_data['first_name'] ?? '') ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">นามสกุล <span class="text-red-500">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?= e($form_data['last_name'] ?? '') ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">อีเมล <span class="text-red-500">*</span></label>
            <input type="email" id="email" name="email" value="<?= e($form_data['email'] ?? '') ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน <span class="text-red-500">*</span></label>
            <input type="password" id="password" name="password" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
            <p class="text-xs text-gray-500 mt-1">ควรมีความยาวอย่างน้อย 8 ตัวอักษร</p>
        </div>
        
        <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่าน <span class="text-red-500">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 transition duration-300 text-lg flex items-center justify-center gap-2">
                <i class="fa-solid fa-check-circle"></i> สมัครสมาชิก
            </button>
        </div>
    </form>
</div>

