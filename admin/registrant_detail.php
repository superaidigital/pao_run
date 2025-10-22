<?php
// admin/registrant_detail.php - หน้ารายละเอียดผู้สมัครรายบุคคล (พร้อมคำนวณอายุและตรวจสอบรุ่น)

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

$page_title = 'รายละเอียดผู้สมัคร';

// --- Get Registration ID ---
if (!isset($_GET['reg_id']) || !is_numeric($_GET['reg_id'])) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: Invalid Registration ID.</p>";
    include 'partials/footer.php';
    exit;
}
$reg_id = intval($_GET['reg_id']);

// --- Fetch Registration Data (เพิ่ม e.start_date) ---
$stmt = $mysqli->prepare("
    SELECT
        r.*,
        e.name AS event_name,
        e.start_date,
        d.name AS distance_name, d.price,
        rc.name AS category_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN distances d ON r.distance_id = d.id
    LEFT JOIN race_categories rc ON r.race_category_id = rc.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $reg_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: Registration not found.</p>";
    include 'partials/footer.php';
    exit;
}
$reg = $result->fetch_assoc();
$stmt->close();

// Security Check
if (!$is_super_admin && $reg['event_id'] !== $staff_info['assigned_event_id']) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: You do not have permission to access this registration.</p>";
    include 'partials/footer.php';
    exit;
}

// --- NEW: Improved Age Calculation & Category Verification ---
$age_at_event_str = '-';
$category_verification_html = '';

if (!empty($reg['birth_date']) && !empty($reg['start_date'])) {
    try {
        $birthDate = new DateTime($reg['birth_date']);
        $eventDate = new DateTime($reg['start_date']);
        $age = $eventDate->diff($birthDate)->y;
        $age_at_event_str = $age . ' ปี';

        // Check if categories are defined for this event/distance at all
        $check_cat_stmt = $mysqli->prepare("SELECT COUNT(id) as total FROM race_categories WHERE event_id = ? AND distance = ?");
        $check_cat_stmt->bind_param("is", $reg['event_id'], $reg['distance_name']);
        $check_cat_stmt->execute();
        $categories_exist = $check_cat_stmt->get_result()->fetch_assoc()['total'] > 0;
        $check_cat_stmt->close();
        
        // Only perform verification if categories are defined for this distance
        if ($categories_exist) {
            // Find the correct category based on rules
            $verify_stmt = $mysqli->prepare("
                SELECT name FROM race_categories 
                WHERE event_id = ? AND distance = ? AND gender = ? AND minAge <= ? AND maxAge >= ?
                LIMIT 1
            ");
            $verify_stmt->bind_param("issii", $reg['event_id'], $reg['distance_name'], $reg['gender'], $age, $age);
            $verify_stmt->execute();
            $correct_category_result = $verify_stmt->get_result();
            
            $correct_category_name = $correct_category_result->fetch_assoc()['name'] ?? null;
            $verify_stmt->close();

            $assigned_category_name = $reg['category_name'];

            if ($correct_category_name === $assigned_category_name) {
                // Correctly assigned
                $category_verification_html = '<i class="fa-solid fa-check-circle text-green-500 ml-2" title="รุ่นการแข่งขันถูกต้อง"></i>';
            } else {
                // Mismatch
                $should_be = $correct_category_name ? e($correct_category_name) : '<i>(ไม่ตรงกับรุ่นใดเลย)</i>';
                $category_verification_html = 
                    '<i class="fa-solid fa-exclamation-triangle text-yellow-500 ml-2" title="รุ่นไม่ตรงกับเกณฑ์อายุ/เพศ"></i>' .
                    '<span class="text-xs text-yellow-600 ml-1">(ควรจะเป็น: ' . $should_be . ')</span>';
            }
        }
        // If categories_exist is false, verification is skipped and the display remains neutral.

    } catch (Exception $e) {
        error_log("Age calculation error for reg ID " . $reg_id . ": " . $e->getMessage());
    }
}

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null;
unset($_SESSION['update_success']);

// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">รายละเอียดผู้สมัคร</h1>
        <p class="text-gray-600 font-mono">ID: <?= e($reg['registration_code']) ?></p>
    </div>
    <a href="registrants.php?event_id=<?= e($reg['event_id']) ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
        <i class="fa-solid fa-arrow-left mr-2"></i> กลับไปหน้ารายชื่อ
    </a>
</div>

<?php if ($success_message): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
    <p><?= e($success_message) ?></p>
</div>
<?php endif; ?>

<form action="../actions/update_registration.php" method="POST">
    <input type="hidden" name="reg_id" value="<?= e($reg['id']) ?>">
    <input type="hidden" name="event_id" value="<?= e($reg['event_id']) ?>">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-bold mb-4 text-primary"><i class="fa-solid fa-user mr-2"></i>ข้อมูลส่วนตัวผู้สมัคร</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <p><strong>ชื่อ-สกุล:</strong> <?= e($reg['title'] . $reg['first_name'] . ' ' . $reg['last_name']) ?></p>
                    <p><strong>เพศ:</strong> <?= e($reg['gender'] ?: '-') ?></p>
                    <p><strong>เลขบัตรประชาชน:</strong> <?= e($reg['thai_id']) ?></p>
                    <p><strong>วันเกิด:</strong> <?= e($reg['birth_date'] ? date("d M Y", strtotime($reg['birth_date'])) : '-') ?></p>
                    <p><strong>อายุ (ณ วันแข่งขัน):</strong> <span class="font-bold"><?= $age_at_event_str ?></span></p>
                    <p><strong>อีเมล:</strong> <?= e($reg['email']) ?></p>
                    <p><strong>โทรศัพท์:</strong> <?= e($reg['phone']) ?></p>
                    <p><strong>Line ID:</strong> <?= e($reg['line_id'] ?: '-') ?></p>
                    <p><strong>โรคประจำตัว:</strong> <?= e($reg['disease']) ?></p>
                    <?php if ($reg['disease_detail']): ?>
                    <p class="md:col-span-2"><strong>รายละเอียด:</strong> <?= e($reg['disease_detail']) ?></p>
                    <?php endif; ?>
                    <p class="md:col-span-2 border-t pt-2 mt-2"><strong>ติดต่อฉุกเฉิน:</strong> <?= e($reg['emergency_contact_name'] ?: '-') ?> (<?= e($reg['emergency_contact_phone'] ?: '-') ?>)</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-bold mb-4 text-primary"><i class="fa-solid fa-flag-checkered mr-2"></i>ข้อมูลการแข่งขัน</h2>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <p><strong>กิจกรรม:</strong> <?= e($reg['event_name']) ?></p>
                    <p><strong>ระยะทาง:</strong> <?= e($reg['distance_name']) ?></p>
                    <p><strong>รุ่นการแข่งขัน:</strong> 
                        <span class="font-semibold"><?= e($reg['category_name'] ?: 'ไม่จำกัดรุ่น') ?></span>
                        <?= $category_verification_html ?>
                    </p>
                    <p><strong>ค่าสมัคร:</strong> <?= number_format($reg['price'], 2) ?> บาท</p>
                    <p><strong>ขนาดเสื้อ:</strong> <?= e($reg['shirt_size']) ?></p>
                 </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-bold mb-4 text-primary"><i class="fa-solid fa-money-check-dollar mr-2"></i>ข้อมูลการชำระเงิน</h2>
                <?php if (!empty($reg['payment_slip_url'])): ?>
                    <a href="../<?= e($reg['payment_slip_url']) ?>" target="_blank" class="block border-2 border-dashed rounded-lg p-4 text-center hover:border-blue-500 transition">
                        <img src="../<?= e($reg['payment_slip_url']) ?>" alt="Payment Slip" class="max-h-60 mx-auto rounded-md">
                        <span class="mt-2 block text-sm text-blue-600 font-semibold">คลิกเพื่อดูภาพสลิปขนาดเต็ม</span>
                    </a>
                <?php else: ?>
                    <p class="text-center text-gray-500">ไม่มีข้อมูลสลิป</p>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md">
                 <h2 class="text-xl font-bold mb-4 text-primary"><i class="fa-solid fa-cogs mr-2"></i>จัดการข้อมูล</h2>
                 <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">เปลี่ยนสถานะ</label>
                        <select id="status" name="status" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                            <option value="รอตรวจสอบ" <?= $reg['status'] == 'รอตรวจสอบ' ? 'selected' : '' ?>>รอตรวจสอบ</option>
                            <option value="ชำระเงินแล้ว" <?= $reg['status'] == 'ชำระเงินแล้ว' ? 'selected' : '' ?>>ชำระเงินแล้ว</option>
                            <option value="รอชำระเงิน" <?= $reg['status'] == 'รอชำระเงิน' ? 'selected' : '' ?>>รอชำระเงิน</option>
                        </select>
                    </div>
                    <div>
                        <label for="bib_number" class="block text-sm font-medium text-gray-700">เลข BIB</label>
                        <input type="text" id="bib_number" name="bib_number" value="<?= e($reg['bib_number']) ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="กำหนดอัตโนมัติเมื่อชำระเงิน">
                    </div>
                     <div>
                        <label for="corral" class="block text-sm font-medium text-gray-700">กลุ่มปล่อยตัว (Corral)</label>
                        <input type="text" id="corral" name="corral" value="<?= e($reg['corral']) ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="กำหนดอัตโนมัติเมื่อชำระเงิน">
                    </div>
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg">
                        <i class="fa-solid fa-save mr-2"></i> บันทึกข้อมูล
                    </button>
                 </div>
            </div>
        </div>
    </div>
</form>

<?php
include 'partials/footer.php';
?>

