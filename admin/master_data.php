<?php
// admin/master_data.php - Master Data Management Page (Super Admin)

// --- CORE BOOTSTRAP ---
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$page_title = 'จัดการข้อมูลพื้นฐาน';
// --- END BOOTSTRAP ---

// --- Fetch all master data ---
$master_titles = $mysqli->query("SELECT * FROM master_titles ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$master_shirt_sizes = $mysqli->query("SELECT * FROM master_shirt_sizes ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$master_runner_types = $mysqli->query("SELECT * FROM master_runner_types ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$master_pickup_options = $mysqli->query("SELECT * FROM master_pickup_options ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
// === ดึงข้อมูลเพศ ===
$master_genders = $mysqli->query("SELECT * FROM master_genders ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
// === สิ้นสุด ===

// Check for session messages
$success_message = isset($_SESSION['master_success']) ? $_SESSION['master_success'] : null; unset($_SESSION['master_success']);
$error_message = isset($_SESSION['master_error']) ? $_SESSION['master_error'] : null; unset($_SESSION['master_error']);

include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">จัดการข้อมูลพื้นฐาน (Master Data)</h1>
        <p class="text-gray-600">จัดการตัวเลือกต่างๆ ที่ใช้ในฟอร์มสมัคร</p>
    </div>
</div>

<?php if ($success_message): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($error_message) ?></p></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <?php
    // (ฟังก์ชัน render_master_data_card เหมือนเดิม ไม่ต้องแก้)
    function render_master_data_card($title, $action_prefix, $items, $has_description = false, $has_cost = false) {
        ?>
        <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-bold mb-4"><?= e($title) ?></h2>
            <div class="space-y-2 mb-4 max-h-60 overflow-y-auto pr-2">
                <?php foreach ($items as $item): ?>
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded-md text-sm group">
                        <span><?= e($item['name']) ?> <?= e($item['description'] ?? '') ?> <?= $has_cost ? '('.number_format($item['cost'] ?? 0, 2).' บาท)' : '' ?></span>
                        <div class="flex items-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button" onclick='editMasterData(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>, "<?= e($action_prefix) ?>")' class="text-blue-500 hover:text-blue-700" title="แก้ไข"><i class="fa-solid fa-pencil-alt"></i></button>
                            <form action="../actions/manage_master_data.php" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่?');" class="inline">
                                <input type="hidden" name="action" value="delete_<?= e($action_prefix) ?>">
                                <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700" title="ลบ"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form action="../actions/manage_master_data.php" method="POST" id="form-<?= e($action_prefix) ?>" class="space-y-2 border-t pt-4">
                <input type="hidden" name="action" value="add_<?= e($action_prefix) ?>">
                <input type="hidden" name="id" value="">
                <h3 class="text-md font-semibold text-gray-700" id="form-title-<?= e($action_prefix) ?>">เพิ่มรายการใหม่</h3>
                <div class="flex flex-wrap gap-2">
                    <input type="text" name="name" required class="flex-grow border border-gray-300 rounded-lg shadow-sm p-2 text-sm" placeholder="ชื่อรายการ...">
                    <?php if ($has_description): ?>
                        <input type="text" name="description" class="w-full md:w-auto border border-gray-300 rounded-lg shadow-sm p-2 text-sm" placeholder="คำอธิบาย (ถ้ามี)">
                    <?php endif; ?>
                    <?php if ($has_cost): ?>
                        <input type="number" step="0.01" name="cost" class="w-full md:w-auto border border-gray-300 rounded-lg shadow-sm p-2 text-sm" placeholder="ค่าใช้จ่าย" value="0.00">
                    <?php endif; ?>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="resetForm('<?= e($action_prefix) ?>')" class="text-xs text-gray-600 hover:underline hidden" id="cancel-btn-<?= e($action_prefix) ?>">ยกเลิก</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm" id="submit-btn-<?= e($action_prefix) ?>">เพิ่ม</button>
                </div>
            </form>
        </div>
        <?php
    }

    render_master_data_card('จัดการ คำนำหน้านาม', 'title', $master_titles);
    // === เพิ่มการแสดงผล Gender ===
    render_master_data_card('จัดการ เพศ', 'gender', $master_genders);
    // === สิ้นสุด ===
    render_master_data_card('จัดการ ขนาดเสื้อ', 'shirt_size', $master_shirt_sizes, true);
    render_master_data_card('จัดการ ประเภทผู้สมัคร', 'runner_type', $master_runner_types);
    render_master_data_card('จัดการ การรับเสื้อ', 'pickup_option', $master_pickup_options, false, true);
    ?>

</div>

<script>
    // (JavaScript editMasterData, resetForm เหมือนเดิม ไม่ต้องแก้)
    function editMasterData(item, prefix) {
        const form = document.getElementById(`form-${prefix}`);
        form.querySelector('input[name="action"]').value = `update_${prefix}`;
        form.querySelector('input[name="id"]').value = item.id;
        form.querySelector('input[name="name"]').value = item.name;

        const descriptionInput = form.querySelector('input[name="description"]');
        if (descriptionInput) descriptionInput.value = item.description || '';

        const costInput = form.querySelector('input[name="cost"]');
        if (costInput) costInput.value = item.cost || '0.00';

        document.getElementById(`form-title-${prefix}`).textContent = 'แก้ไขรายการ';
        document.getElementById(`submit-btn-${prefix}`).textContent = 'บันทึก';
        document.getElementById(`cancel-btn-${prefix}`).classList.remove('hidden');
    }

    function resetForm(prefix) {
        const form = document.getElementById(`form-${prefix}`);
        form.reset();
        form.querySelector('input[name="action"]').value = `add_${prefix}`;
        form.querySelector('input[name="id"]').value = '';

        document.getElementById(`form-title-${prefix}`).textContent = 'เพิ่มรายการใหม่';
        document.getElementById(`submit-btn-${prefix}`).textContent = 'เพิ่ม';
        document.getElementById(`cancel-btn-${prefix}`).classList.add('hidden');
    }
</script>

<?php
include 'partials/footer.php';
?>