<?php
// admin/event_categories.php - หน้าจัดการรุ่นการแข่งขัน (ชาย/หญิง/อายุ)

// --- CORE BOOTSTRAP ---
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');
// --- END BOOTSTRAP ---

$page_title = 'จัดการรุ่นการแข่งขัน';

// --- Get Event ID and verify access ---
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Invalid Event ID.</p>"; include 'partials/footer.php'; exit;
}
$event_id = intval($_GET['event_id']);

// Security Check
if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: You do not have permission.</p>"; include 'partials/footer.php'; exit;
}

// --- Fetch Event & Distances ---
$event_stmt = $mysqli->prepare("SELECT name FROM events WHERE id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event = $event_stmt->get_result()->fetch_assoc();
if (!$event) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Event not found.</p>"; include 'partials/footer.php'; exit;
}

$distances_stmt = $mysqli->prepare("SELECT name FROM distances WHERE event_id = ? ORDER BY price DESC");
$distances_stmt->bind_param("i", $event_id);
$distances_stmt->execute();
$distances = $distances_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch Existing Categories ---
$cat_stmt = $mysqli->prepare("SELECT * FROM race_categories WHERE event_id = ? ORDER BY distance, gender, minAge");
$cat_stmt->bind_param("i", $event_id);
$cat_stmt->execute();
$categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cat_stmt->close();

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">จัดการรุ่นการแข่งขัน</h1>
        <p class="text-gray-600">กิจกรรม: <?= e($event['name']) ?></p>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-md h-fit">
        <h3 class="text-xl font-semibold mb-4">เพิ่มรุ่นใหม่</h3>
        <form action="../actions/manage_categories.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_category">
            <input type="hidden" name="event_id" value="<?= e($event_id) ?>">
            
            <div>
                <label for="distance" class="block text-sm font-medium text-gray-700">ระยะทาง</label>
                <select name="distance" id="distance" required class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
                    <?php if (empty($distances)): ?>
                        <option value="">-- กรุณาเพิ่มระยะทางก่อน --</option>
                    <?php else: ?>
                        <?php foreach($distances as $dist): ?>
                            <option value="<?= e($dist['name']) ?>"><?= e($dist['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">ชื่อรุ่น</label>
                <input type="text" name="name" id="name" required placeholder="เช่น รุ่นทั่วไป, 18-29 ปี" class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>
            
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700">เพศ</label>
                <select name="gender" id="gender" required class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
                    <option value="ชาย">ชาย</option>
                    <option value="หญิง">หญิง</option>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="minAge" class="block text-sm font-medium text-gray-700">อายุต่ำสุด</label>
                    <input type="number" name="minAge" id="minAge" required value="0" class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
                </div>
                 <div>
                    <label for="maxAge" class="block text-sm font-medium text-gray-700">อายุสูงสุด</label>
                    <input type="number" name="maxAge" id="maxAge" required value="99" class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
                </div>
            </div>
            
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg" <?= empty($distances) ? 'disabled' : '' ?>>
                <i class="fa-solid fa-plus-circle mr-2"></i> เพิ่มรุ่นการแข่งขัน
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
        <h3 class="text-xl font-semibold mb-4">รุ่นการแข่งขันทั้งหมด</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ระยะทาง</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อรุ่น</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">เพศ</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">กลุ่มอายุ</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">ลบ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($categories)): ?>
                    <tr><td colspan="5" class="text-center py-6 text-gray-500">ยังไม่มีการสร้างรุ่นการแข่งขัน</td></tr>
                <?php else: ?>
                    <?php foreach($categories as $cat): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-4 text-sm font-semibold text-gray-800"><?= e($cat['distance']) ?></td>
                        <td class="px-3 py-4 text-sm text-gray-700"><?= e($cat['name']) ?></td>
                        <td class="px-3 py-4 text-sm text-gray-700"><?= e($cat['gender']) ?></td>
                        <td class="px-3 py-4 text-sm text-gray-700"><?= e($cat['minAge']) ?> - <?= e($cat['maxAge']) ?> ปี</td>
                        <td class="px-3 py-4 text-right">
                            <form action="../actions/manage_categories.php" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรุ่นนี้?');">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_id" value="<?= e($cat['id']) ?>">
                                <input type="hidden" name="event_id" value="<?= e($event_id) ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700" title="ลบ">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include 'partials/footer.php';
?>