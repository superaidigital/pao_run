<?php
// admin/registrants.php - หน้าจัดการข้อมูลผู้สมัคร (พร้อม Search & Pagination)

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

$page_title = 'จัดการข้อมูลผู้สมัคร';

// --- Get Event ID and verify access ---
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Invalid Event ID.</p>"; include 'partials/footer.php'; exit;
}
$event_id = intval($_GET['event_id']);

// Security Check
if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: You do not have permission to access this event.</p>"; include 'partials/footer.php'; exit;
}

// --- Fetch Event Info ---
$event_stmt = $mysqli->prepare("SELECT name FROM events WHERE id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
if ($event_result->num_rows === 0) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Event not found.</p>"; include 'partials/footer.php'; exit;
}
$event = $event_result->fetch_assoc();
$event_name = $event['name'];
$event_stmt->close();

// --- PAGINATION & SEARCH LOGIC ---
$limit = 10; // จำนวนผู้สมัครต่อหน้า
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- Build Query ---
$base_query = "FROM registrations r
               JOIN distances d ON r.distance_id = d.id
               LEFT JOIN race_categories rc ON r.race_category_id = rc.id
               WHERE r.event_id = ?";
$where_clause = '';
$params_where = [$event_id]; // พารามิเตอร์สำหรับ WHERE clause เท่านั้น
$types_where = 'i';       // ประเภทสำหรับ WHERE clause เท่านั้น

if (!empty($search_term)) {
    $where_clause = " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.email LIKE ? OR r.registration_code LIKE ? OR r.bib_number LIKE ?)";
    $like_term = "%{$search_term}%";
    $params_where[] = $like_term;
    $params_where[] = $like_term;
    $params_where[] = $like_term;
    $params_where[] = $like_term; // for registration_code
    $params_where[] = $like_term; // for bib_number
    $types_where .= 'sssss';
}

// 1. Count total records for pagination
$count_query = "SELECT COUNT(r.id) " . $base_query . $where_clause;
$count_stmt = $mysqli->prepare($count_query);
// *** ใช้ $types_where และ $params_where สำหรับ count query ***
if (!$count_stmt) die("Prepare failed for count: (" . $mysqli->errno . ") " . $mysqli->error);
$count_stmt->bind_param($types_where, ...$params_where);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();


// 2. Fetch registrants data for the current page
$select_fields = "SELECT r.id, r.title, r.first_name, r.last_name, r.email, r.status, r.registered_at, d.name as distance_name, rc.name as category_name ";
$limit_clause = " ORDER BY r.registered_at DESC LIMIT ? OFFSET ?";

// *** สร้าง $params_main และ $types_main สำหรับ main query ***
$params_main = $params_where; // เริ่มต้นด้วยพารามิเตอร์ของ WHERE
$types_main = $types_where;   // เริ่มต้นด้วยประเภทของ WHERE

$params_main[] = $limit;    // เพิ่ม limit
$params_main[] = $offset;   // เพิ่ม offset
$types_main .= 'ii';        // เพิ่มประเภท 'i' สองตัว

$registrants_query = $select_fields . $base_query . $where_clause . $limit_clause;
$registrants_stmt = $mysqli->prepare($registrants_query);
if ($registrants_stmt === false) {
     die("Prepare failed for main query: (" . $mysqli->errno . ") " . $mysqli->error);
}
// *** ใช้ $types_main และ $params_main สำหรับ bind_param ของ main query ***
$registrants_stmt->bind_param($types_main, ...$params_main); // บรรทัด 100 (ตาม error)
$registrants_stmt->execute();
$registrants = $registrants_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$registrants_stmt->close();


// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">ข้อมูลผู้สมัคร</h1>
        <p class="text-gray-600">กิจกรรม: <?= e($event_name) ?></p>
    </div>
    <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
        <i class="fa-solid fa-arrow-left mr-2"></i> กลับสู่หน้าหลัก
    </a>
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold">รายชื่อผู้สมัครทั้งหมด (<?= number_format($total_records) ?> คน)</h2>
        <div class="flex gap-2">
            <form action="registrants.php" method="GET" class="flex items-center">
                <input type="hidden" name="event_id" value="<?= e($event_id) ?>">
                <input type="text" name="search" value="<?= e($search_term) ?>" placeholder="ค้นหา ชื่อ, อีเมล, รหัสสมัคร, BIB..." class="border border-gray-300 rounded-l-lg p-2 text-sm w-64">
                <button type="submit" class="bg-gray-200 hover:bg-gray-300 p-2 rounded-r-lg text-gray-600"><i class="fa fa-search"></i></button>
            </form>
            <a href="../actions/export_registrants.php?event_id=<?= e($event_id) ?>&search=<?= urlencode($search_term) ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                <i class="fa-solid fa-file-excel mr-2"></i> Export
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ระยะทาง / รุ่น</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่สมัคร</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($registrants)): ?>
                    <tr><td colspan="5" class="text-center py-6 text-gray-500">ไม่พบข้อมูลผู้สมัครที่ตรงกับเงื่อนไข</td></tr>
                <?php else: ?>
                    <?php foreach($registrants as $reg):
                        $status_color = 'gray';
                        if ($reg['status'] == 'ชำระเงินแล้ว') $status_color = 'green';
                        if ($reg['status'] == 'รอตรวจสอบ') $status_color = 'yellow';
                        if ($reg['status'] == 'รอชำระเงิน') $status_color = 'red';
                    ?>
                    <tr>
                        <td class="px-3 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= e($reg['title'] . $reg['first_name'] . ' ' . $reg['last_name']) ?></div>
                            <div class="text-sm text-gray-500"><?= e($reg['email']) ?></div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-700">
                            <div><?= e($reg['distance_name']) ?></div>
                            <div class="text-xs text-gray-500"><?= e($reg['category_name'] ?: '-') ?></div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $status_color ?>-100 text-<?= $status_color ?>-800">
                                <?= e($reg['status']) ?>
                            </span>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date("d M Y, H:i", strtotime($reg['registered_at'])) ?>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="registrant_detail.php?reg_id=<?= e($reg['id']) ?>" class="text-indigo-600 hover:text-indigo-900">ดูรายละเอียด</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-between items-center text-sm">
        <p class="text-gray-600">
            แสดงผล <span class="font-medium"><?= count($registrants) ?></span> จากทั้งหมด <span class="font-medium"><?= number_format($total_records) ?></span> รายการ
        </p>
        <?php if ($total_pages > 1): ?>
        <div class="flex items-center space-x-1">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?event_id=<?= e($event_id) ?>&page=<?= $i ?>&search=<?= urlencode($search_term) ?>"
                   class="px-3 py-1 rounded-md <?= $i == $page ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'partials/footer.php';
?>