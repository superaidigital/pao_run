<?php
// admin/reports.php - หน้ารายงานสรุปของแต่ละกิจกรรม (ฉบับปรับปรุงพร้อมกราฟ)

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
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Invalid Event ID.</p>"; include 'partials/footer.php'; exit;
}
$event_id = intval($_GET['event_id']);

// Security Check
if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: You do not have permission to access this event's reports.</p>"; include 'partials/footer.php'; exit;
}

// --- Fetch Event Info ---
$event_stmt = $mysqli->prepare("SELECT name, start_date FROM events WHERE id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
if ($event_result->num_rows === 0) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Event not found.</p>"; include 'partials/footer.php'; exit;
}
$event = $event_result->fetch_assoc();
$event_name = $event['name'];
$event_start_date = $event['start_date'];
$event_stmt->close();

// --- Fetch Stats Data ---

// 1. Overall Stats
$stats_stmt = $mysqli->prepare("
    SELECT 
        COUNT(r.id) as total_registrations,
        SUM(CASE WHEN r.status = 'ชำระเงินแล้ว' THEN 1 ELSE 0 END) as paid_registrations,
        SUM(CASE WHEN r.status = 'รอตรวจสอบ' THEN 1 ELSE 0 END) as pending_registrations,
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
$distance_stmt = $mysqli->prepare("SELECT d.name, COUNT(r.id) as count FROM registrations r JOIN distances d ON r.distance_id = d.id WHERE r.event_id = ? AND r.status = 'ชำระเงินแล้ว' GROUP BY d.name ORDER BY d.price DESC");
$distance_stmt->bind_param("i", $event_id);
$distance_stmt->execute();
$distance_breakdown = $distance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$distance_stmt->close();

// 3. Shirt Size Breakdown
$shirt_stmt = $mysqli->prepare("SELECT shirt_size, COUNT(id) as count FROM registrations WHERE event_id = ? AND status = 'ชำระเงินแล้ว' GROUP BY shirt_size ORDER BY FIELD(shirt_size, 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL')");
$shirt_stmt->bind_param("i", $event_id);
$shirt_stmt->execute();
$shirt_breakdown = $shirt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$shirt_stmt->close();

// 4. [NEW] Gender Breakdown
$gender_stmt = $mysqli->prepare("SELECT gender, COUNT(id) as count FROM registrations WHERE event_id = ? AND status = 'ชำระเงินแล้ว' AND gender IS NOT NULL GROUP BY gender");
$gender_stmt->bind_param("i", $event_id);
$gender_stmt->execute();
$gender_breakdown = $gender_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gender_stmt->close();

// 5. [NEW] Age Group Breakdown (for paid registrants)
$age_group_stmt = $mysqli->prepare("
    SELECT
        CASE
            WHEN AGE < 18 THEN 'ต่ำกว่า 18'
            WHEN AGE BETWEEN 18 AND 29 THEN '18-29 ปี'
            WHEN AGE BETWEEN 30 AND 39 THEN '30-39 ปี'
            WHEN AGE BETWEEN 40 AND 49 THEN '40-49 ปี'
            WHEN AGE BETWEEN 50 AND 59 THEN '50-59 ปี'
            ELSE '60 ปีขึ้นไป'
        END as age_group,
        COUNT(*) as count
    FROM (
        SELECT TIMESTAMPDIFF(YEAR, r.birth_date, ?) as AGE
        FROM registrations r
        WHERE r.event_id = ? AND r.status = 'ชำระเงินแล้ว' AND r.birth_date IS NOT NULL
    ) as ages
    GROUP BY age_group
    ORDER BY age_group
");
$age_group_stmt->bind_param("si", $event_start_date, $event_id);
$age_group_stmt->execute();
$age_group_breakdown = $age_group_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$age_group_stmt->close();


// 6. [NEW] Registration Trend (All statuses)
$trend_stmt = $mysqli->prepare("SELECT DATE(registered_at) as registration_date, COUNT(id) as count FROM registrations WHERE event_id = ? GROUP BY DATE(registered_at) ORDER BY registration_date ASC");
$trend_stmt->bind_param("i", $event_id);
$trend_stmt->execute();
$registration_trend = $trend_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$trend_stmt->close();


// --- RENDER VIEW ---
include 'partials/header.php';
?>

<style>
    /* Print-specific styles */
    @media print {
        body {
            background-color: #fff;
        }
        nav, .no-print, #prev-btn, #next-btn {
            display: none !important;
        }
        main {
            max-width: 100%;
            padding: 0;
            margin: 0;
        }
        .printable-content {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            break-inside: avoid; /* Prevents breaking cards across pages */
        }
        .grid {
            grid-template-columns: repeat(2, 1fr) !important; /* Force 2 columns on print */
        }
        h1, h2 {
            color: #000 !important;
        }
    }
</style>

<div class="flex justify-between items-center mb-6 no-print">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">รายงานสรุป</h1>
        <p class="text-gray-600">กิจกรรม: <?= e($event_name) ?></p>
    </div>
    <div class="flex gap-2">
         <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> กลับ
        </a>
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm">
            <i class="fa-solid fa-print mr-2"></i> พิมพ์รายงาน
        </button>
    </div>
</div>

<div class="space-y-6">
    <!-- Overall Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500 printable-content">
            <p class="text-sm text-gray-500">ยอดสมัครทั้งหมด</p>
            <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['total_registrations'] ?? 0) ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500 printable-content">
            <p class="text-sm text-gray-500">ชำระเงินแล้ว</p>
            <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['paid_registrations'] ?? 0) ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-orange-500 printable-content">
            <p class="text-sm text-gray-500">รอตรวจสอบ</p>
            <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['pending_registrations'] ?? 0) ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-yellow-500 printable-content">
            <p class="text-sm text-gray-500">รายรับรวม (ที่ชำระแล้ว)</p>
            <p class="text-3xl font-extrabold text-gray-800"><?= number_format($overall_stats['total_revenue'] ?? 0, 2) ?> ฿</p>
        </div>
    </div>
    
    <!-- Registration Trend Chart -->
    <div class="bg-white p-6 rounded-xl shadow-md printable-content">
        <h2 class="text-xl font-bold mb-4">แนวโน้มการสมัคร (รายวัน)</h2>
        <canvas id="registrationTrendChart"></canvas>
    </div>

    <!-- Breakdown Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-md printable-content">
            <h2 class="text-xl font-bold mb-4">สรุปยอดตามระยะทาง (ที่ชำระเงินแล้ว)</h2>
            <canvas id="distanceChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md printable-content">
            <h2 class="text-xl font-bold mb-4">สรุปยอดสั่งผลิตเสื้อ (ที่ชำระเงินแล้ว)</h2>
            <canvas id="shirtChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md printable-content">
            <h2 class="text-xl font-bold mb-4">สัดส่วนตามเพศ (ที่ชำระเงินแล้ว)</h2>
            <canvas id="genderChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md printable-content">
            <h2 class="text-xl font-bold mb-4">สัดส่วนตามกลุ่มอายุ (ที่ชำระเงินแล้ว)</h2>
            <canvas id="ageGroupChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartColors = ['#3b82f6', '#10b981', '#f97316', '#ef4444', '#8b5cf6', '#ec4899'];
    const chartColorsGender = ['#3b82f6', '#ec4899'];

    // 1. Distance Chart
    const distanceData = <?= json_encode($distance_breakdown) ?>;
    if (distanceData.length > 0) {
        new Chart(document.getElementById('distanceChart'), {
            type: 'bar',
            data: {
                labels: distanceData.map(item => item.name),
                datasets: [{
                    label: 'จำนวนผู้สมัคร',
                    data: distanceData.map(item => item.count),
                    backgroundColor: chartColors,
                    borderColor: chartColors.map(color => color.replace(')', ', 0.2)')),
                    borderWidth: 1
                }]
            },
            options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
        });
    }

    // 2. Shirt Chart
    const shirtData = <?= json_encode($shirt_breakdown) ?>;
    if (shirtData.length > 0) {
        new Chart(document.getElementById('shirtChart'), {
            type: 'bar',
            data: {
                labels: shirtData.map(item => item.shirt_size),
                datasets: [{
                    label: 'จำนวนเสื้อ',
                    data: shirtData.map(item => item.count),
                    backgroundColor: chartColors,
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }
    
    // 3. Gender Chart
    const genderData = <?= json_encode($gender_breakdown) ?>;
    if (genderData.length > 0) {
        new Chart(document.getElementById('genderChart'), {
            type: 'pie',
            data: {
                labels: genderData.map(item => item.gender),
                datasets: [{
                    data: genderData.map(item => item.count),
                    backgroundColor: chartColorsGender,
                }]
            },
            options: { responsive: true }
        });
    }
    
    // 4. Age Group Chart
    const ageGroupData = <?= json_encode($age_group_breakdown) ?>;
     if (ageGroupData.length > 0) {
        new Chart(document.getElementById('ageGroupChart'), {
            type: 'bar',
            data: {
                labels: ageGroupData.map(item => item.age_group),
                datasets: [{
                    label: 'จำนวนผู้สมัคร',
                    data: ageGroupData.map(item => item.count),
                    backgroundColor: chartColors,
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    // 5. Registration Trend Chart
    const trendData = <?= json_encode($registration_trend) ?>;
    if (trendData.length > 0) {
        new Chart(document.getElementById('registrationTrendChart'), {
            type: 'line',
            data: {
                labels: trendData.map(item => item.registration_date),
                datasets: [{
                    label: 'จำนวนผู้สมัคร',
                    data: trendData.map(item => item.count),
                    fill: false,
                    borderColor: '#3b82f6',
                    tension: 0.1
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

});
</script>


<?php
include 'partials/footer.php';
?>

