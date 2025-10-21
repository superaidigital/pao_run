<?php
// admin/create_event.php - หน้าสำหรับสร้างกิจกรรมใหม่ (Super Admin)

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

$page_title = 'สร้างกิจกรรมใหม่';

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">สร้างกิจกรรมใหม่</h1>
        <p class="text-gray-600">กรอกข้อมูลพื้นฐานเพื่อสร้างกิจกรรมใหม่ในระบบ</p>
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

<form action="../actions/create_event.php" method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">ชื่อกิจกรรม <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 focus:ring-primary focus:border-primary" placeholder="เช่น Sisaket Night Run 2026">
    </div>
    <div>
        <label for="event_code" class="block text-sm font-medium text-gray-700">รหัสอ้างอิง (Event Code) <span class="text-red-500">*</span></label>
        <input type="text" id="event_code" name="event_code" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 font-mono" placeholder="เช่น ssk-nightrun-26 (ต้องไม่ซ้ำ)">
        <p class="text-xs text-gray-500 mt-1">ใช้ตัวอักษรภาษาอังกฤษ, ตัวเลข, และขีดกลาง (-) เท่านั้น</p>
    </div>
    <div>
        <label for="start_date" class="block text-sm font-medium text-gray-700">วันที่จัดกิจกรรม <span class="text-red-500">*</span></label>
        <input type="datetime-local" id="start_date" name="start_date" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end pt-6 border-t">
        <button type="submit" class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">
            <i class="fa-solid fa-plus-circle mr-2"></i> สร้างกิจกรรม
        </button>
    </div>
</form>

<?php
include 'partials/footer.php';
?>
