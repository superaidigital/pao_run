<?php
// admin/edit_staff.php - หน้าสำหรับแก้ไขข้อมูลเจ้าหน้าที่

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

$page_title = 'แก้ไขข้อมูลเจ้าหน้าที่';

// --- Get Staff ID from URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: Invalid Staff ID.</p>";
    include 'partials/footer.php';
    exit;
}
$staff_id_to_edit = intval($_GET['id']);

// --- Fetch Data for the staff member ---
$stmt = $mysqli->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $staff_id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: Staff member not found.</p>";
    include 'partials/footer.php';
    exit;
}
$staff_data = $result->fetch_assoc();
$stmt->close();

// Fetch all events for the dropdown
$all_events = $mysqli->query("SELECT id, name FROM events ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">แก้ไขข้อมูลบัญชี</h1>
        <p class="text-gray-600">กำลังแก้ไขบัญชี: <?= e($staff_data['username']) ?></p>
    </div>
    <a href="staff_management.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
        <i class="fa-solid fa-arrow-left mr-2"></i> กลับหน้ารายชื่อ
    </a>
</div>

<div class="bg-white p-6 rounded-xl shadow-md max-w-lg mx-auto">
    <form action="../actions/manage_staff.php" method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="staff_id" value="<?= e($staff_data['id']) ?>">
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" value="<?= e($staff_data['username']) ?>" disabled class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2 bg-gray-100 cursor-not-allowed">
        </div>
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700">ชื่อเต็ม</label>
            <input type="text" name="full_name" value="<?= e($staff_data['full_name']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่านใหม่ (กรอกเพื่อเปลี่ยน)</label>
            <input type="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2" placeholder="ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน">
             <p class="text-xs text-gray-500 mt-1">ต้องมีความยาวอย่างน้อย 6 ตัวอักษร</p>
        </div>
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">ระดับสิทธิ์</label>
            <select name="role" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2" <?= ($staff_data['id'] == $staff_info['id']) ? 'disabled' : '' ?>>
                <option value="staff" <?= $staff_data['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $staff_data['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div>
            <label for="assigned_event_id" class="block text-sm font-medium text-gray-700">มอบหมายให้ดูแลกิจกรรม</label>
            <select name="assigned_event_id" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                <option value="">-- ไม่กำหนด --</option>
                <?php foreach ($all_events as $event): ?>
                    <option value="<?= e($event['id']) ?>" <?= $staff_data['assigned_event_id'] == $event['id'] ? 'selected' : '' ?>>
                        <?= e($event['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg">
            <i class="fa-solid fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
        </button>
    </form>
</div>


<?php
include 'partials/footer.php';
?>
