<?php
// admin/reports.php - หน้ารายงานสรุปของแต่ละกิจกรรม

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

$page_title = 'รายงานสรุปกิจกรรม';

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
    echo "<p class='text-red-500'>Error: You do not have permission to access this event's reports.</p>";
    include 'partials/footer.php';
    exit;
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

// --- Fetch Stats Data ---

// 1. Overall Stats
$stats_stmt = $mysqli->prepare("
    SELECT 
        COUNT(r.id) as total_registrations,
        SUM(CASE WHEN r.status = 'ชำระเงินแล้ว' THEN 1 ELSE 0 END) as paid_registrations,
        SUM(CASE WHEN r.status = 'ชำระเงินแล้ว' THEN d.price ELSE 0 END) as total_revenue
    FROM registrations r
    JOIN distances d ON r.distance_id = d.id
    WHERE r.event_id = ?
");
$stats_stmt->bind_param("i", $event_id);
$stats_stmt->execute();
$overall_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// 2. Distance Breakdown
$distance_stmt = $mysqli->prepare("
    SELECT d.name, COUNT(r.id) as count
    FROM registrations r
    JOIN distances d ON r.distance_id = d.id
    WHERE r.event_id = ?
    GROUP BY d.name
    ORDER BY d.price DESC
");
$distance_stmt->bind_param("i", $event_id);
$distance_stmt->execute();
$distance_breakdown = $distance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$distance_stmt->close();

// 3. Shirt Size Breakdown
$shirt_stmt = $mysqli->prepare("
    SELECT shirt_size, COUNT(id) as count
    FROM registrations
    WHERE event_id = ?
    GROUP BY shirt_size
    ORDER BY FIELD(shirt_size, 'XS', 'S', 'M', 'L', 'XL', '2XL')
");
$shirt_stmt->bind_param("i", $event_id);
$shirt_stmt->execute();
$shirt_breakdown = $shirt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$shirt_stmt->close();


// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">รายงานสรุป</h1>
        <p class="text-gray-600">กิจกรรม: <?= e($event_name) ?></p>
    </div>
    <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
        <i class="fa-solid fa-arrow-left mr-2"></i> กลับสู่หน้าหลัก
    </a>
</div>

<div class="space-y-6">
    <!-- Overall Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500">
            <p class="text-sm text-gray-500">ยอดสมัครทั้งหมด</p>
            <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['total_registrations'] ?? 0) ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500">
            <p class="text-sm text-gray-500">ชำระเงินแล้ว</p>
            <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['paid_registrations'] ?? 0) ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500">รายรับรวม (ที่ชำระแล้ว)</p>
            <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['total_revenue'] ?? 0, 2) ?> บาท</p>
        </div>
    </div>

    <!-- Breakdown Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Distance Breakdown -->
        <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-bold mb-4">สรุปยอดตามระยะทาง</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ระยะทาง</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จำนวน (คน)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($distance_breakdown as $item): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($item['name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 text-right font-bold"><?= number_format($item['count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Shirt Size Breakdown -->
        <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-bold mb-4">สรุปยอดสั่งผลิตเสื้อ</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ไซส์เสื้อ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จำนวน (ตัว)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                     <?php foreach ($shirt_breakdown as $item): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= e($item['shirt_size']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 text-right font-bold"><?= number_format($item['count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php
include 'partials/footer.php';
?>
