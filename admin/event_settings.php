<?php
// admin/event_settings.php - หน้าตั้งค่ากิจกรรม (เวอร์ชันสมบูรณ์)

// --- CORE BOOTSTRAP ---
// This now happens in the header, but we need the data before including it.
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');
// --- END BOOTSTRAP ---

$page_title = 'ตั้งค่าและเนื้อหากิจกรรม';

// --- Get Event ID and verify access ---
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: Invalid Event ID.</p>";
    include 'partials/footer.php';
    exit;
}
$event_id = intval($_GET['event_id']);

// Security Check
if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: You do not have permission to access this event's settings.</p>";
    include 'partials/footer.php';
    exit;
}

// --- Fetch ALL Event Data ---
$stmt = $mysqli->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$event) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Event not found.</p>"; include 'partials/footer.php'; exit;
}

$distances = $mysqli->query("SELECT * FROM distances WHERE event_id = $event_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$images_result = $mysqli->query("SELECT id, image_url, image_type FROM event_images WHERE event_id = $event_id")->fetch_all(MYSQLI_ASSOC);

function filter_images_by_type($images, $type) {
    return array_filter($images, function($img) use ($type) { return $img['image_type'] == $type; });
}
$detail_images = filter_images_by_type($images_result, 'detail');
$merch_images = filter_images_by_type($images_result, 'merch');
$medal_images = filter_images_by_type($images_result, 'medal');


// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">ตั้งค่าและเนื้อหากิจกรรม</h1>
        <p class="text-gray-600">แก้ไขข้อมูลทั้งหมดสำหรับ: <?= e($event['name']) ?></p>
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

<form action="../actions/update_event_settings.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md space-y-8">
    <input type="hidden" name="event_id" value="<?= e($event['id']) ?>">
    <input type="hidden" name="event_code" value="<?= e($event['event_code']) ?>">

    <!-- Section 1: Basic Info -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">1. ข้อมูลพื้นฐาน</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">ชื่อกิจกรรม</label>
                <input type="text" id="name" name="name" value="<?= e($event['name']) ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
            </div>
            <div>
                <label for="slogan" class="block text-sm font-medium text-gray-700">สโลแกน</label>
                <input type="text" id="slogan" name="slogan" value="<?= e($event['slogan']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
            </div>
             <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">วันที่จัดกิจกรรม</label>
                <input type="datetime-local" id="start_date" name="start_date" value="<?= date('Y-m-d\TH:i', strtotime($event['start_date'])) ?>" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
            </div>
            <div>
                <label for="is_registration_open" class="block text-sm font-medium text-gray-700">สถานะรับสมัคร</label>
                <select id="is_registration_open" name="is_registration_open" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                    <option value="1" <?= $event['is_registration_open'] ? 'selected' : '' ?>>เปิดรับสมัคร</option>
                    <option value="0" <?= !$event['is_registration_open'] ? 'selected' : '' ?>>ปิดรับสมัคร</option>
                </select>
            </div>
            <div>
                <label for="theme_color" class="block text-sm font-medium text-gray-700">โทนสี (Tailwind Name)</label>
                <input type="text" id="theme_color" name="theme_color" value="<?= e($event['theme_color']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2" placeholder="e.g., indigo, green, red">
            </div>
            <div>
                <label for="color_code" class="block text-sm font-medium text-gray-700">รหัสสีหลัก (Color Picker)</label>
                <input type="color" id="color_code" name="color_code" value="<?= e($event['color_code']) ?>" class="mt-1 block w-full h-10 border border-gray-300 rounded-lg shadow-sm p-1">
            </div>
        </div>
    </div>
    
    <!-- Section 2: Contact Info -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">2. ข้อมูลผู้จัดและการติดต่อ</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <label for="organizer" class="block text-sm font-medium text-gray-700">ชื่อผู้จัดงาน</label>
                    <input type="text" id="organizer" name="organizer" value="<?= e($event['organizer']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                 <div>
                    <label for="contact_person_name" class="block text-sm font-medium text-gray-700">ผู้ประสานงาน</label>
                    <input type="text" id="contact_person_name" name="contact_person_name" value="<?= e($event['contact_person_name']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                <div>
                    <label for="contact_person_phone" class="block text-sm font-medium text-gray-700">เบอร์โทรศัพท์ติดต่อ</label>
                    <input type="tel" id="contact_person_phone" name="contact_person_phone" value="<?= e($event['contact_person_phone']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
            </div>
            <div class="space-y-2">
                 <label class="block text-sm font-medium text-gray-700">โลโก้ผู้จัดงาน</label>
                <div class="flex items-center gap-4">
                    <?php if (!empty($event['organizer_logo_url'])): ?>
                        <img src="../<?= e($event['organizer_logo_url']) ?>" class="w-24 h-24 object-contain rounded-lg border p-1 bg-white">
                    <?php else: ?>
                        <div class="w-24 h-24 flex items-center justify-center bg-gray-100 rounded-lg border text-xs text-gray-500">No Logo</div>
                    <?php endif; ?>
                    <div class="w-full">
                        <label for="organizer_logo" class="block text-xs font-medium text-gray-500 mb-1">อัปโหลดโลโก้ใหม่ (ถ้าต้องการเปลี่ยน):</label>
                        <input type="file" id="organizer_logo" name="organizer_logo" 
                               class="w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Payment Info -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">3. ข้อมูลการชำระเงิน</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                 <div>
                    <label for="payment_bank" class="block text-sm font-medium text-gray-700">ธนาคาร</label>
                    <input type="text" id="payment_bank" name="payment_bank" value="<?= e($event['payment_bank']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                 <div>
                    <label for="payment_account_name" class="block text-sm font-medium text-gray-700">ชื่อบัญชี</label>
                    <input type="text" id="payment_account_name" name="payment_account_name" value="<?= e($event['payment_account_name']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
                 <div>
                    <label for="payment_account_number" class="block text-sm font-medium text-gray-700">เลขที่บัญชี</label>
                    <input type="text" id="payment_account_number" name="payment_account_number" value="<?= e($event['payment_account_number']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                </div>
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">QR Code สำหรับชำระเงิน</label>
                <div class="flex items-center gap-4">
                    <?php if (!empty($event['payment_qr_code_url'])): ?>
                        <img src="../<?= e($event['payment_qr_code_url']) ?>" class="w-24 h-24 object-contain rounded-lg border p-1 bg-white">
                    <?php else: ?>
                        <div class="w-24 h-24 flex items-center justify-center bg-gray-100 rounded-lg border text-xs text-gray-500">No Image</div>
                    <?php endif; ?>
                    <div class="w-full">
                        <label for="payment_qr_code" class="block text-xs font-medium text-gray-500 mb-1">อัปโหลด QR Code ใหม่ (ถ้าต้องการเปลี่ยน):</label>
                        <input type="file" id="payment_qr_code" name="payment_qr_code" 
                               class="w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Content & Descriptions -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">4. เนื้อหากิจกรรม</h2>
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">รายละเอียดกิจกรรม (Description)</label>
            <textarea id="description" name="description"><?= htmlspecialchars_decode($event['description']) ?></textarea>
        </div>
        <div class="mt-6">
            <label for="awards_description" class="block text-sm font-medium text-gray-700 mb-2">รายละเอียดของรางวัล (Awards)</label>
            <textarea id="awards_description" name="awards_description"><?= htmlspecialchars_decode($event['awards_description']) ?></textarea>
        </div>
    </div>

    <!-- Section 5: Distances & Pricing -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">5. ระยะทางและราคา</h2>
        <div id="distances-container" class="space-y-3">
            <!-- JS will render rows here -->
        </div>
        <button type="button" onclick="addDistanceRow()" class="mt-3 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm">
            <i class="fa-solid fa-plus-circle mr-2"></i> เพิ่มระยะทาง
        </button>
    </div>
    
    <!-- Section 6: Image Galleries -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">6. คลังรูปภาพ (อัปโหลดไฟล์)</h2>
        <div class="space-y-6">
            <?php
            function render_image_uploader_gallery($title, $type, $images) {
                ?>
                <div class="p-4 border rounded-lg bg-gray-50 space-y-4">
                    <label class="block text-sm font-medium text-gray-700"><?= e($title) ?></label>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <?php if (empty($images)): ?>
                            <p class="text-xs text-gray-500 col-span-full">ยังไม่มีรูปภาพ</p>
                        <?php else: ?>
                            <?php foreach ($images as $img): ?>
                                <div class="relative group">
                                    <img src="../<?= e($img['image_url']) ?>" 
                                         onclick="showImagePreviewModal('../<?= e($img['image_url']) ?>')"
                                         class="w-full h-24 object-cover rounded-md cursor-pointer border-2 border-transparent group-hover:border-blue-500 transition shadow-sm">
                                    <label class="absolute top-1 right-1 cursor-pointer bg-white/70 backdrop-blur-sm rounded-full p-0.5 flex items-center justify-center shadow">
                                        <input type="checkbox" name="delete_images[]" value="<?= e($img['id']) ?>" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="border-t pt-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">อัปโหลดรูปภาพใหม่ (เลือกได้หลายรูป):</label>
                        <input type="file" name="<?= e($type) ?>_images[]" multiple accept="image/jpeg,image/png,image/gif"
                               class="w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                    </div>
                </div>
                <?php
            }
            render_image_uploader_gallery('ภาพประกอบกิจกรรม', 'detail', $detail_images);
            render_image_uploader_gallery('ภาพเสื้อที่ระลึก', 'merch', $merch_images);
            render_image_uploader_gallery('ภาพเหรียญรางวัล', 'medal', $medal_images);
            ?>
        </div>
    </div>

    <!-- Section 7: Cover & Thumbnail Images -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">7. ภาพปกและภาพย่อ (Cover & Thumbnail)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">ภาพปกกิจกรรม (Cover Image)</label>
                <div class="flex flex-col items-start gap-4">
                    <img src="../<?= e($event['cover_image_url'] ?? 'https://placehold.co/800x300?text=Cover') ?>" class="w-full h-auto object-contain rounded-lg border p-1 bg-white">
                    <div>
                        <label for="cover_image" class="block text-xs font-medium text-gray-500 mb-1">อัปโหลดภาพใหม่ (ขนาดแนะนำ 800x300 px):</label>
                        <input type="file" id="cover_image" name="cover_image" class="w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                    </div>
                </div>
            </div>
             <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">ภาพย่อสำหรับการ์ด (Card Thumbnail)</label>
                <div class="flex flex-col items-start gap-4">
                    <img src="../<?= e($event['card_thumbnail_url'] ?? 'https://placehold.co/400x150?text=Thumbnail') ?>" class="w-full h-auto object-contain rounded-lg border p-1 bg-white">
                    <div>
                        <label for="card_thumbnail" class="block text-xs font-medium text-gray-500 mb-1">อัปโหลดภาพใหม่ (ขนาดแนะนำ 400x150 px):</label>
                        <input type="file" id="card_thumbnail" name="card_thumbnail" class="w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section 8: Map Information -->
    <div>
        <h2 class="text-xl font-bold text-primary border-b pb-2 mb-4">8. ข้อมูลแผนที่ (Location & Map)</h2>
        <div class="space-y-4">
            <div>
                <label for="map_embed_url" class="block text-sm font-medium text-gray-700">Map Embed URL</label>
                <input type="text" id="map_embed_url" name="map_embed_url" value="<?= e($event['map_embed_url']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2" placeholder="https://www.google.com/maps/embed?pb=...">
                <p class="text-xs text-gray-500 mt-1">URL สำหรับแสดงผลแผนที่ในหน้าเว็บ (จาก Google Maps > Share > Embed a map)</p>
            </div>
            <div>
                <label for="map_direction_url" class="block text-sm font-medium text-gray-700">Map Direction URL</label>
                <input type="text" id="map_direction_url" name="map_direction_url" value="<?= e($event['map_direction_url']) ?>" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2" placeholder="https://maps.google.com/?q=...">
                <p class="text-xs text-gray-500 mt-1">URL สำหรับปุ่ม "นำทาง" (จาก Google Maps > Share > Send a link)</p>
            </div>
        </div>
    </div>


    <!-- Submit Button -->
    <div class="flex justify-end pt-6 border-t">
        <button type="submit" class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">
            <i class="fa-solid fa-save mr-2"></i> บันทึกข้อมูลทั้งหมด
        </button>
    </div>
</form>

<script>
    // --- Initialize CKEditor ---
    CKEDITOR.replace('description', {
        height: 250
    });
    CKEDITOR.replace('awards_description', {
        height: 150
    });

    // --- DISTANCE MANAGEMENT SCRIPT ---
    let distances = <?= json_encode($distances ?? []) ?>;
    const distancesContainer = document.getElementById('distances-container');

    function renderDistances() {
        distancesContainer.innerHTML = '';
        if (distances.length === 0) {
            distancesContainer.innerHTML = '<p class="text-gray-500">ยังไม่มีรายการระยะทาง</p>';
        }
        distances.forEach((dist, index) => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 p-2 border rounded-lg bg-gray-50';
            row.innerHTML = `
                <input type="hidden" name="distances[${index}][id]" value="${dist.id || ''}">
                <input type="text" name="distances[${index}][name]" placeholder="ชื่อระยะทาง (เช่น 10 KM)" value="${dist.name || ''}" class="w-1/3 p-2 border rounded-md">
                <input type="text" name="distances[${index}][category]" placeholder="ประเภท (เช่น Mini Marathon)" value="${dist.category || ''}" class="w-1/3 p-2 border rounded-md">
                <input type="number" step="0.01" name="distances[${index}][price]" placeholder="ราคา" value="${dist.price || ''}" class="w-1/4 p-2 border rounded-md">
                <button type="button" onclick="removeDistanceRow(${index})" class="bg-red-100 text-red-600 hover:bg-red-200 p-2 rounded-md h-10 w-10 flex-shrink-0"><i class="fa-solid fa-trash"></i></button>
            `;
            distancesContainer.appendChild(row);
        });
    }

    function addDistanceRow() {
        distances.push({});
        renderDistances();
    }

    function removeDistanceRow(index) {
        distances.splice(index, 1);
        renderDistances();
    }
    
    // --- INITIAL RENDER ---
    document.addEventListener('DOMContentLoaded', () => {
        renderDistances();
    });
</script>


<?php
include 'partials/footer.php';
?>

