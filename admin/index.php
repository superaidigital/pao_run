<?php
// admin/index.php - หน้า Dashboard หลักสำหรับ Admin/Staff (เวอร์ชันสมบูรณ์)

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


// --- PAGE SETUP & DATA FETCHING ---
$page_title = 'Admin Dashboard';

// Check for session messages from other actions
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

// --- Fetch Stats Data ---
$stats_query = "
    SELECT 
        COUNT(r.id) as total_registrations,
        SUM(CASE WHEN r.status = 'ชำระเงินแล้ว' THEN 1 ELSE 0 END) as paid_registrations,
        SUM(CASE WHEN r.status = 'ชำระเงินแล้ว' THEN d.price ELSE 0 END) as total_revenue
    FROM registrations r
    JOIN distances d ON r.distance_id = d.id
";
$stats_params = [];
$stats_types = '';
if (!$is_super_admin) {
    $stats_query .= " WHERE r.event_id = ?";
    $stats_params[] = $staff_info['assigned_event_id'];
    $stats_types = 'i';
}
$stats_stmt = $mysqli->prepare($stats_query);
if (!$is_super_admin && !empty($stats_params)) {
    $stats_stmt->bind_param($stats_types, ...$stats_params);
}
$stats_stmt->execute();
$overall_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();


// Fetch Events, separating active and cancelled
$active_events = [];
$cancelled_events = [];
// Base query for events
$events_query_sql = "SELECT id, event_code, name, is_registration_open, is_visible, is_cancelled, sort_order FROM events";
if (!$is_super_admin) {
    // If not super admin, only fetch their assigned event, regardless of cancelled status
    $events_query_sql .= " WHERE id = " . intval($staff_info['assigned_event_id']);
}
$events_query_sql .= " ORDER BY sort_order ASC";

$result = $mysqli->query($events_query_sql);
while($row = $result->fetch_assoc()){
    if ($row['is_cancelled']) {
        $cancelled_events[] = $row;
    } else {
        $active_events[] = $row;
    }
}


// Fetch Pending Registrations
$pending_query = "
    SELECT r.id, r.registration_code, e.name as event_name, r.payment_slip_url
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    WHERE r.status = 'รอตรวจสอบ'
";
$pending_params = [];
$pending_types = '';
if (!$is_super_admin) {
    $pending_query .= " AND r.event_id = ?";
    $pending_params[] = $staff_info['assigned_event_id'];
    $pending_types .= 'i';
}
$pending_query .= " ORDER BY r.registered_at ASC LIMIT 5"; 

$pending_stmt = $mysqli->prepare($pending_query);
if(!empty($pending_params)){
    $pending_stmt->bind_param($pending_types, ...$pending_params);
}
$pending_stmt->execute();
$pending_registrations = $pending_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pending_stmt->close();


// --- RENDER VIEW ---
include 'partials/header.php'; 
?>

<h1 class="text-3xl font-bold text-gray-900 mb-6">ภาพรวมระบบ</h1>

<?php if ($success_message): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($error_message) ?></p></div>
<?php endif; ?>
        
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500">
        <p class="text-sm text-gray-500">ยอดสมัครรวม</p>
        <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['total_registrations'] ?? 0) ?> <span class="text-base font-medium">รายการ</span></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500">
        <p class="text-sm text-gray-500">ชำระเงินแล้ว</p>
        <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['paid_registrations'] ?? 0) ?> <span class="text-base font-medium">รายการ</span></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-yellow-500">
        <p class="text-sm text-gray-500">รายได้รวม (โดยประมาณ)</p>
        <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['total_revenue'] ?? 0, 2) ?> <span class="text-base font-medium">บาท</span></p>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h2 class="text-xl font-bold mb-4">จัดการกิจกรรม (Event Management)</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">กิจกรรม</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ยอดสมัคร</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach($active_events as $index => $event): 
                    $event_stats_stmt = $mysqli->prepare("SELECT COUNT(id) as total FROM registrations WHERE event_id = ?");
                    $event_stats_stmt->bind_param("i", $event['id']);
                    $event_stats_stmt->execute();
                    $total_regs = $event_stats_stmt->get_result()->fetch_assoc()['total'];
                    $event_stats_stmt->close();
                ?>
                <tr>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <div class="flex items-center gap-3">
                            <a href="../actions/update_event_visibility.php?id=<?= e($event['id']) ?>" 
                               class="<?= $event['is_visible'] ? 'text-green-500' : 'text-gray-400' ?>"
                               title="<?= $event['is_visible'] ? 'ซ่อนจากหน้าแรก' : 'แสดงในหน้าแรก' ?>">
                                <i class="fa-solid fa-<?= $event['is_visible'] ? 'eye' : 'eye-slash' ?>"></i>
                            </a>
                            <span><?= e($event['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $event['is_registration_open'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $event['is_registration_open'] ? 'เปิด' : 'ปิด' ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 font-mono"><?= $total_regs ?></td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-3">
                            <?php if ($is_super_admin): ?>
                                <a href="../actions/update_event_order.php?id=<?= e($event['id']) ?>&direction=up" class="<?= $index === 0 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:text-black' ?>" title="เลื่อนขึ้น"><i class="fa-solid fa-arrow-up"></i></a>
                                <a href="../actions/update_event_order.php?id=<?= e($event['id']) ?>&direction=down" class="<?= $index === count($active_events) - 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:text-black' ?>" title="เลื่อนลง"><i class="fa-solid fa-arrow-down"></i></a>
                                <span class="text-gray-300">|</span>
                            <?php endif; ?>
                            
                            <a href="#" onclick="showShareModal('<?= e($event['event_code']) ?>', '<?= e($event['name']) ?>')" class="text-purple-600 hover:text-purple-900" title="แชร์"><i class="fa-solid fa-share-alt"></i></a>
                            <a href="event_schedule.php?event_id=<?= e($event['id']) ?>" class="text-teal-600 hover:text-teal-900" title="กำหนดการ"><i class="fa-solid fa-clock"></i></a>
                            
                            <a href="event_categories.php?event_id=<?= e($event['id']) ?>" class="text-orange-600 hover:text-orange-900" title="จัดการรุ่นการแข่งขัน"><i class="fa-solid fa-sitemap"></i></a>
                            <a href="reports.php?event_id=<?= e($event['id']) ?>" class="text-blue-600 hover:text-blue-900" title="รายงาน"><i class="fa-solid fa-chart-line"></i></a>
                            <a href="registrants.php?event_id=<?= e($event['id']) ?>" class="text-indigo-600 hover:text-indigo-900" title="ผู้สมัคร"><i class="fa-solid fa-users"></i></a>
                            <a href="event_settings.php?event_id=<?= e($event['id']) ?>" class="text-gray-600 hover:text-gray-900" title="แก้ไข"><i class="fa-solid fa-cog"></i></a>
                            
                            <span class="text-gray-300">|</span>
                            <a href="../actions/manage_event_status.php?action=cancel&id=<?= e($event['id']) ?>" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกกิจกรรมนี้?')" class="text-yellow-600 hover:text-yellow-900" title="ยกเลิก"><i class="fa-solid fa-ban"></i></a>
                            
                            <a href="../actions/manage_event_status.php?action=delete&id=<?= e($event['id']) ?>" 
                               onclick="return confirm('คำเตือน! การลบกิจกรรมจะลบข้อมูลทั้งหมดที่เกี่ยวข้องและไม่สามารถกู้คืนได้ คุณแน่ใจหรือไม่?')" 
                               class="<?= $total_regs > 0 ? 'text-gray-300 cursor-not-allowed' : 'text-red-600 hover:text-red-900' ?>" 
                               <?= $total_regs > 0 ? 'aria-disabled="true" onclick="event.preventDefault(); alert(\'ไม่สามารถลบกิจกรรมที่มีผู้สมัครแล้วได้\');"' : '' ?>
                               title="<?= $total_regs > 0 ? 'ไม่สามารถลบได้' : 'ลบถาวร' ?>">
                               <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($is_super_admin && !empty($cancelled_events)): ?>
<div class="bg-gray-50 p-6 rounded-xl shadow-md mt-8 border border-dashed">
    <h2 class="text-xl font-bold mb-4 text-gray-600">กิจกรรมที่ยกเลิก (Cancelled Events)</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
             <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">กิจกรรม</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach($cancelled_events as $event): ?>
                <tr>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-500 line-through"><?= e($event['name']) ?></td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                         <a href="../actions/manage_event_status.php?action=restore&id=<?= e($event['id']) ?>" class="text-green-600 hover:text-green-900" title="กู้คืนกิจกรรม">
                            <i class="fa-solid fa-undo mr-1"></i> กู้คืน
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<div class="bg-white p-6 rounded-xl shadow-md mt-8">
    <h2 class="text-xl font-bold mb-4">รายการที่รอการตรวจสอบล่าสุด</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัส</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">กิจกรรม</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">หลักฐาน</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($pending_registrations)): ?>
                    <tr><td colspan="4" class="text-center py-6 text-gray-500">ไม่มีรายการที่รอการตรวจสอบ</td></tr>
                <?php else: ?>
                    <?php foreach($pending_registrations as $reg): ?>
                    <tr class="hover:bg-yellow-50">
                        <td class="px-3 py-4 whitespace-nowrap text-sm font-mono text-gray-700"><?= e($reg['registration_code']) ?></td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900"><?= e($reg['event_name']) ?></td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                            <button onclick="showSlipModal('<?= e($reg['payment_slip_url']) ?>', '<?= e($reg['registration_code']) ?>')" class="bg-blue-100 text-blue-800 hover:bg-blue-200 font-bold py-1 px-3 rounded-lg text-xs">
                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i> ดูสลิป
                            </button>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                            <a href="registrant_detail.php?reg_id=<?= e($reg['id']) ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-lg text-xs">
                                <i class="fa-solid fa-search-plus mr-1"></i> ตรวจสอบ
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function showShareModal(eventCode, eventName) {
        const modalBox = document.getElementById('message-box');
        const modalTitle = document.getElementById('message-title');
        const modalContent = document.getElementById('message-content-container');
        const baseUrl = window.location.origin + window.location.pathname.replace('/admin/index.php', '');
        const shareUrl = `${baseUrl}/index.php?page=microsite&event_code=${eventCode}`;
        const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(shareUrl)}`;

        modalTitle.textContent = `โปรโมทกิจกรรม: ${eventName}`;
        modalContent.innerHTML = `
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">ลิงก์สำหรับแชร์:</label>
                    <div class="flex mt-1">
                        <input type="text" id="share-url-input" value="${shareUrl}" readonly class="w-full border-gray-300 rounded-l-lg p-2 bg-gray-100 text-sm focus:outline-none">
                        <button onclick="copyToClipboard('share-url-input')" class="bg-gray-200 hover:bg-gray-300 px-4 rounded-r-lg text-gray-700" title="Copy to clipboard">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="text-center border-t pt-4">
                    <img src="${qrCodeUrl}" alt="QR Code for event link" class="mx-auto w-48 h-48 rounded-lg border p-1 bg-white">
                    <a href="${qrCodeUrl}" download="qr-code-${eventCode}.png" class="mt-2 inline-block text-sm text-blue-600 hover:underline">
                        <i class="fa-solid fa-download mr-1"></i> ดาวน์โหลด QR Code
                    </a>
                </div>
            </div>
        `;
        modalBox.classList.remove('hidden');
    }

    function showSlipModal(slipUrl, regCode) {
        const modalBox = document.getElementById('message-box');
        const modalTitle = document.getElementById('message-title');
        const modalContent = document.getElementById('message-content-container');
        const fullSlipUrl = `../${slipUrl}`;

        modalTitle.textContent = `หลักฐานการชำระเงิน: ${regCode}`;
        modalContent.innerHTML = `
            <div class="text-center">
                <img src="${fullSlipUrl}" alt="Payment Slip" class="max-h-96 mx-auto rounded-lg border shadow-sm" onerror="this.onerror=null; this.src='https://placehold.co/400x400/e0e0e0/999999?text=Image+Not+Found';">
            </div>
        `;
        modalBox.classList.remove('hidden');
    }

    function copyToClipboard(elementId) {
        const input = document.getElementById(elementId);
        input.select();
        input.setSelectionRange(0, 99999);
        try {
            document.execCommand('copy');
            alert('คัดลอกลิงก์สำเร็จแล้ว!');
        } catch (err) {
            alert('ไม่สามารถคัดลอกลิงก์ได้');
        }
    }
</script>

<?php
include 'partials/footer.php';
?>