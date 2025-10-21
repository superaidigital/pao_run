<?php
// admin/slides_management.php - หน้าจัดการสไลด์หน้าแรก

// --- CORE BOOTSTRAP ---
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$page_title = 'จัดการสไลด์หน้าแรก';
// --- END BOOTSTRAP ---

// --- Fetch all slides ---
$slides = $mysqli->query("SELECT * FROM slides ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">จัดการสไลด์ (Slides)</h1>
        <p class="text-gray-600">จัดการรูปภาพและลิงก์ที่แสดงในหน้าแรก</p>
    </div>
</div>

<?php if ($success_message): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($error_message) ?></p></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
        <h2 class="text-xl font-bold mb-4">รายการสไลด์ทั้งหมด</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">รูปภาพ</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">หัวข้อ</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ลำดับ</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($slides)): ?>
                        <tr><td colspan="5" class="text-center py-6 text-gray-500">ยังไม่มีสไลด์</td></tr>
                    <?php else: ?>
                        <?php foreach ($slides as $index => $slide): ?>
                        <tr>
                            <td class="px-3 py-4">
                                <img src="../<?= e($slide['image_url']) ?>" alt="Slide Image" class="w-24 h-12 object-cover rounded-md bg-gray-100" onerror="this.src='https://placehold.co/800x250?text=No+Image';">
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm font-semibold"><?= e($slide['title']) ?></div>
                                <div class="text-xs text-gray-500 truncate max-w-xs"><?= e($slide['link_url']) ?></div>
                            </td>
                            <td class="px-3 py-4 text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $slide['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $slide['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-3 py-4 text-sm">
                                <a href="../actions/update_slide_order.php?id=<?= e($slide['id']) ?>&direction=up" 
                                   class="<?= $index === 0 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:text-black' ?>" 
                                   title="เลื่อนขึ้น"><i class="fa-solid fa-arrow-up"></i></a>
                                <a href="../actions/update_slide_order.php?id=<?= e($slide['id']) ?>&direction=down" 
                                   class="<?= $index === count($slides) - 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:text-black' ?>" 
                                   title="เลื่อนลง"><i class="fa-solid fa-arrow-down"></i></a>
                            </td>
                            <td class="px-3 py-4 text-right">
                                <div class="flex items-center justify-end space-x-4">
                                    <button type="button" onclick='editSlide(<?= json_encode($slide, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="text-blue-500 hover:text-blue-700" title="แก้ไข">
                                        <i class="fa-solid fa-pencil-alt"></i>
                                    </button>
                                    <form action="../actions/manage_slides.php" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสไลด์นี้?');" class="inline-block">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slide_id" value="<?= e($slide['id']) ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700" title="ลบ">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="form-container" class="bg-white p-6 rounded-xl shadow-md h-fit">
        <div class="flex justify-between items-start">
            <h2 id="form-title" class="text-xl font-bold mb-4">เพิ่มสไลด์ใหม่</h2>
            <button id="cancel-edit-btn" onclick="resetForm()" class="hidden text-sm text-gray-500 hover:text-gray-700">&times; ยกเลิก</button>
        </div>
        
        <form id="slide-form" action="../actions/manage_slides.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="slide_id" id="slide-id-input" value="">
            
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">หัวข้อ (Title) <span class="text-red-500">*</span></label>
                <input type="text" id="title-input" name="title" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
            </div>
            <div>
                <label for="subtitle" class="block text-sm font-medium text-gray-700">คำอธิบาย (Subtitle)</label>
                <textarea id="subtitle-input" name="subtitle" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2"></textarea>
            </div>
             <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700">ลิงก์ (Link URL)</label>
                <input type="text" id="link-url-input" name="link_url" placeholder="เช่น ?page=microsite&event_code=..." class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
            </div>
             <div>
                <label for="image" class="block text-sm font-medium text-gray-700">รูปภาพ <span id="image-required" class="text-red-500">*</span></label>
                <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                <p id="image-helper" class="text-xs text-gray-500 mt-1">ขนาดแนะนำ 800x250 px</p>
            </div>
            <div>
                <label for="is_active" class="block text-sm font-medium text-gray-700">สถานะ</label>
                <select id="is-active-input" name="is_active" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                    <option value="1">Active (แสดง)</option>
                    <option value="0">Inactive (ซ่อน)</option>
                </select>
            </div>
            <button id="submit-btn" type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg">
                <i class="fa-solid fa-plus-circle mr-2"></i> เพิ่มสไลด์
            </button>
        </form>
    </div>
</div>

<script>
    const formTitle = document.getElementById('form-title');
    const slideForm = document.getElementById('slide-form');
    const formAction = document.getElementById('form-action');
    const slideIdInput = document.getElementById('slide-id-input');
    const titleInput = document.getElementById('title-input');
    const subtitleInput = document.getElementById('subtitle-input');
    const linkUrlInput = document.getElementById('link-url-input');
    const imageInput = document.getElementById('image-input');
    const imageRequired = document.getElementById('image-required');
    const imageHelper = document.getElementById('image-helper');
    const isActiveInput = document.getElementById('is-active-input');
    const submitBtn = document.getElementById('submit-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');

    function editSlide(slideData) {
        formTitle.textContent = 'แก้ไขสไลด์';
        formAction.value = 'update';
        slideIdInput.value = slideData.id;
        
        titleInput.value = slideData.title;
        subtitleInput.value = slideData.subtitle || '';
        linkUrlInput.value = slideData.link_url || '';
        isActiveInput.value = slideData.is_active;

        imageInput.required = false;
        imageRequired.classList.add('hidden');
        imageHelper.textContent = 'อัปโหลดไฟล์ใหม่เฉพาะเมื่อต้องการเปลี่ยนรูปภาพ';
        
        submitBtn.innerHTML = '<i class="fa-solid fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง';
        cancelBtn.classList.remove('hidden');

        document.getElementById('form-container').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        formTitle.textContent = 'เพิ่มสไลด์ใหม่';
        slideForm.reset();
        
        formAction.value = 'create';
        slideIdInput.value = '';
        
        imageInput.required = true;
        imageRequired.classList.remove('hidden');
        imageHelper.textContent = 'ขนาดแนะนำ 800x250 px';

        submitBtn.innerHTML = '<i class="fa-solid fa-plus-circle mr-2"></i> เพิ่มสไลด์';
        cancelBtn.classList.add('hidden');
    }
</script>

<?php
include 'partials/footer.php';
?>