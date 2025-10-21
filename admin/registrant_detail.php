<?php
// admin/registrant_detail.php - หน้ารายละเอียดผู้สมัครรายบุคคล

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

// --- Fetch Registration Data ---
// === เพิ่ม LEFT JOIN และ rc.name ===
$stmt = $mysqli->prepare("
    SELECT
        r.*,
        e.name AS event_name,
        d.name AS distance_name, d.price,
        rc.name AS category_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN distances d ON r.distance_id = d.id
    LEFT JOIN race_categories rc ON r.race_category_id = rc.id
    WHERE r.id = ?
");
// === สิ้นสุด ===
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

// Security Check: If not super admin, ensure staff can access this event
if (!$is_super_admin && $reg['event_id'] !== $staff_info['assigned_event_id']) {
    include 'partials/header.php';
    echo "<p class='text-red-500'>Error: You do not have permission to access this registration.</p>";
    include 'partials/footer.php';
    exit;
}

// Check for session messages from update action
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
                    <p><strong>อีเมล:</strong> <?= e($reg['email']) ?></p>
                    <p><strong>โทรศัพท์:</strong> <?= e($reg['phone']) ?></p>
                    <p><strong>Line ID:</strong> <?= e($reg['line_id'] ?: '-') ?></p>
                    <p><strong>โรคประจำตัว:</strong> <?= e($reg['disease']) ?></p>
                    <?php if ($reg['disease_detail']): ?>
                    <p class="md:col-span-2"><strong>รายละเอียด:</strong> <?= e($reg['disease_detail']) ?></p>
                    <?php endif; ?>
                    <p><strong>ติดต่อฉุกเฉิน:</strong> <?= e($reg['emergency_contact_name'] ?: '-') ?></p>
                    <p><strong>เบอร์ติดต่อฉุกเฉิน:</strong> <?= e($reg['emergency_contact_phone'] ?: '-') ?></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-bold mb-4 text-primary"><i class="fa-solid fa-flag-checkered mr-2"></i>ข้อมูลการแข่งขัน</h2>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <p><strong>กิจกรรม:</strong> <?= e($reg['event_name']) ?></p>
                    <p><strong>ระยะทาง:</strong> <?= e($reg['distance_name']) ?></p>
                    <p><strong>รุ่นการแข่งขัน:</strong> <?= e($reg['category_name'] ?: '-') ?></p>
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
                        <input type="text" id="bib_number" name="bib_number" value="<?= e($reg['bib_number']) ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="ยังไม่กำหนด">
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