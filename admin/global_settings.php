<?php
// admin/global_settings.php - หน้าตั้งค่าระบบส่วนกลาง (Super Admin)

// --- CORE BOOTSTRAP ---
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');

// Security Check: Super Admin Only
if (!$is_super_admin) {
    header('Location: index.php');
    exit;
}
// --- END BOOTSTRAP ---

$page_title = 'ตั้งค่าระบบส่วนกลาง';

// --- Fetch Data ---
$settings = get_global_settings($mysqli);

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">ตั้งค่าระบบส่วนกลาง</h1>
        <p class="text-gray-600">จัดการข้อมูลที่แสดงผลร่วมกันทั่วทั้งเว็บไซต์</p>
    </div>
     <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
        <i class="fa-solid fa-arrow-left mr-2"></i> กลับสู่หน้าหลัก
    </a>
</div>

<?php if ($success_message): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($error_message) ?></p></div>
<?php endif; ?>

<form action="../actions/update_global_settings.php" method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-6">
    
    <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">ข้อมูลติดต่อ (แสดงใน Footer)</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="system_email" class="block text-sm font-medium text-gray-700">อีเมลติดต่อระบบ (System Email)</label>
            <input type="email" id="system_email" name="settings[system_email]" value="<?= e($settings['system_email'] ?? '') ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 focus:ring-primary focus:border-primary">
        </div>
        <div>
            <label for="system_phone" class="block text-sm font-medium text-gray-700">เบอร์โทรศัพท์ระบบ (System Phone)</label>
            <input type="tel" id="system_phone" name="settings[system_phone]" value="<?= e($settings['system_phone'] ?? '') ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
        </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end pt-6 border-t">
        <button type="submit" class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">
            <i class="fa-solid fa-save mr-2"></i> บันทึกการตั้งค่าระบบ
        </button>
    </div>
</form>

<?php
include 'partials/footer.php';
?>
