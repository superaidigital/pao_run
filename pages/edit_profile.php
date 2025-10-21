<?php
// pages/edit_profile.php
// หน้าสำหรับนักวิ่งที่เข้าสู่ระบบเพื่อแก้ไขข้อมูลส่วนตัว

// --- CORE BOOTSTRAP ---
// ตรวจสอบว่า Login อยู่หรือไม่ ถ้าไม่ ให้กลับไปหน้า dashboard
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
$user_id = $_SESSION['user_id'];
// --- END BOOTSTRAP ---

$page_title = 'แก้ไขข้อมูลส่วนตัว';

// --- Fetch Current User Data ---
$stmt = $mysqli->prepare("SELECT first_name, last_name, email, phone, line_id, thai_id, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Should not happen if user is logged in, but as a safeguard
    session_destroy();
    header('Location: index.php');
    exit;
}
$user = $result->fetch_assoc();
$stmt->close();

// Check for session messages from update action
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-800">แก้ไขข้อมูลส่วนตัว</h2>
            <p class="text-gray-600">จัดการข้อมูลและรหัสผ่านของคุณ</p>
        </div>
        <a href="index.php?page=dashboard" class="text-sm text-gray-600 hover:text-primary"><i class="fa fa-arrow-left mr-1"></i> กลับสู่แดชบอร์ด</a>
    </div>

    <?php if ($success_message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert"><p><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p><?= e($error_message) ?></p></div>
    <?php endif; ?>

    <form action="actions/update_profile.php" method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-6">
        
        <!-- Personal Information Section -->
        <h3 class="text-xl font-bold text-primary border-b pb-2">ข้อมูลโปรไฟล์</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">ชื่อจริง</label>
                <input type="text" id="first_name" name="first_name" value="<?= e($user['first_name']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">นามสกุล</label>
                <input type="text" id="last_name" name="last_name" value="<?= e($user['last_name']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>
             <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">เบอร์โทรศัพท์</label>
                <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>
             <div>
                <label for="line_id" class="block text-sm font-medium text-gray-700">Line ID</label>
                <input type="text" id="line_id" name="line_id" value="<?= e($user['line_id']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>
            <div class="md:col-span-2">
                <label for="thai_id" class="block text-sm font-medium text-gray-700">หมายเลขบัตรประชาชน</label>
                <input type="text" id="thai_id" name="thai_id" value="<?= e($user['thai_id']) ?>" pattern="\d{13}" title="กรุณากรอกเลข 13 หลัก" class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>
            <div class="md:col-span-2">
                <label for="address" class="block text-sm font-medium text-gray-700">ที่อยู่ (สำหรับจัดส่ง Race Kit)</label>
                <textarea id="address" name="address" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg p-2"><?= e($user['address']) ?></textarea>
            </div>
             <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-500">อีเมล (ไม่สามารถเปลี่ยนได้)</label>
                <input type="email" value="<?= e($user['email']) ?>" disabled class="mt-1 block w-full border border-gray-300 rounded-lg p-2 bg-gray-100 cursor-not-allowed">
            </div>
        </div>

        <!-- Password Change Section -->
        <h3 class="text-xl font-bold text-primary border-b pb-2 pt-4">เปลี่ยนรหัสผ่าน</h3>
        <p class="text-sm text-gray-500">กรอกข้อมูลเฉพาะในกรณีที่ต้องการเปลี่ยนรหัสผ่านเท่านั้น</p>
         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700">รหัสผ่านใหม่</label>
                <input type="password" id="new_password" name="new_password" class="mt-1 block w-full border border-gray-300 rounded-lg p-2" placeholder="อย่างน้อย 8 ตัวอักษร">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" id="confirm_password" name="confirm_password" class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>
        </div>
        
        <!-- Submit Button -->
        <div class="flex justify-end pt-6 border-t">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">
                <i class="fa-solid fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>

