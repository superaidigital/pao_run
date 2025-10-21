<?php
// admin/event_schedule.php - หน้าจัดการกำหนดการแข่งขัน (เวอร์ชันอัปเกรด UI)

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

$page_title = 'จัดการกำหนดการ';

// --- Get Event ID and verify access ---
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Invalid Event ID.</p>"; include 'partials/footer.php'; exit;
}
$event_id = intval($_GET['event_id']);

// Security Check
if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: You do not have permission.</p>"; include 'partials/footer.php'; exit;
}

// --- Fetch Event & Schedule Data ---
$event_stmt = $mysqli->prepare("SELECT name, start_date FROM events WHERE id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event = $event_stmt->get_result()->fetch_assoc();
if (!$event) {
    include 'partials/header.php'; echo "<p class='text-red-500'>Error: Event not found.</p>"; include 'partials/footer.php'; exit;
}
$event_name = $event['name'];
$event_start_date = date('Y-m-d', strtotime($event['start_date']));
$event_stmt->close();

$schedule_stmt = $mysqli->prepare("SELECT * FROM schedules WHERE event_id = ? ORDER BY date, time ASC");
$schedule_stmt->bind_param("i", $event_id);
$schedule_stmt->execute();
$schedules_raw = $schedule_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$schedule_stmt->close();

// Group schedules by date
$schedules_grouped = [];
foreach ($schedules_raw as $item) {
    $schedules_grouped[$item['date']][] = $item;
}

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

// --- RENDER VIEW ---
include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">จัดการกำหนดการแข่งขัน</h1>
        <p class="text-gray-600">กิจกรรม: <?= e($event_name) ?></p>
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

<form action="../actions/update_event_schedule.php" method="POST" class="bg-white p-6 rounded-xl shadow-md space-y-6">
    <input type="hidden" name="event_id" value="<?= e($event_id) ?>">

    <div id="schedule-days-container" class="space-y-6">
        <!-- JS will render day blocks here -->
    </div>

    <div class="flex justify-between items-center pt-6 border-t">
        <button type="button" onclick="addDayBlock()" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm">
            <i class="fa-solid fa-plus-circle mr-2"></i> เพิ่มวัน
        </button>
        <button type="submit" class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">
            <i class="fa-solid fa-save mr-2"></i> บันทึกกำหนดการ
        </button>
    </div>
</form>

<script>
    let dayIndex = 0;
    const scheduleData = <?= json_encode($schedules_grouped, JSON_UNESCAPED_UNICODE) ?>;
    const defaultDate = '<?= e($event_start_date) ?>';

    function renderAllDays() {
        const container = document.getElementById('schedule-days-container');
        container.innerHTML = '';
        dayIndex = 0;
        if (Object.keys(scheduleData).length === 0) {
            container.innerHTML = '<p id="no-schedule-item" class="text-gray-500">ยังไม่มีกำหนดการ</p>';
        } else {
            for (const date in scheduleData) {
                addDayBlock(date, scheduleData[date]);
            }
        }
    }
    
    function addDayBlock(date = defaultDate, items = []) {
        const container = document.getElementById('schedule-days-container');
        const noItemText = document.getElementById('no-schedule-item');
        if (noItemText) noItemText.remove();
        
        const dayBlock = document.createElement('div');
        dayBlock.className = 'p-4 border rounded-lg bg-gray-50 day-block';
        dayBlock.dataset.dayIndex = dayIndex;

        let itemsHtml = '';
        if(items.length > 0) {
            items.forEach((item, itemIdx) => {
                itemsHtml += createItemHtml(dayIndex, itemIdx, item);
            });
        }

        dayBlock.innerHTML = `
            <div class="flex items-center gap-4 mb-4">
                <input type="date" value="${date}" onchange="updateAllDates(this, ${dayIndex})" required class="p-2 border rounded-md font-bold">
                <button type="button" onclick="removeDayBlock(this)" class="text-red-500 hover:text-red-700 ml-auto"><i class="fa-solid fa-trash-alt"></i> ลบวัน</button>
            </div>
            <div class="space-y-2 item-container">${itemsHtml}</div>
            <button type="button" onclick="addItemToDay(${dayIndex})" class="mt-3 text-xs font-bold py-1 px-3 rounded-lg bg-blue-500 text-white hover:bg-blue-600">
                <i class="fa-solid fa-plus mr-1"></i> เพิ่มรายการ
            </button>
        `;
        container.appendChild(dayBlock);
        dayIndex++;
    }

    function addItemToDay(dIndex) {
        const dayBlock = document.querySelector(`.day-block[data-day-index="${dIndex}"]`);
        const itemContainer = dayBlock.querySelector('.item-container');
        const itemIdx = itemContainer.children.length;
        const newItem = document.createElement('div');
        newItem.innerHTML = createItemHtml(dIndex, itemIdx);
        itemContainer.appendChild(newItem.firstElementChild);
    }
    
    function createItemHtml(dIndex, itemIdx, item = {}) {
        return `
            <div class="flex items-center gap-2 pl-4 border-l-2 schedule-item">
                <input type="time" name="schedule[${dIndex}][items][${itemIdx}][time]" value="${item.time || ''}" required class="p-2 border rounded-md">
                <input type="text" name="schedule[${dIndex}][items][${itemIdx}][activity]" value="${item.activity || ''}" required class="flex-grow p-2 border rounded-md" placeholder="ชื่อกิจกรรม">
                <label class="flex items-center text-sm gap-1">
                    <input type="checkbox" name="schedule[${dIndex}][items][${itemIdx}][is_highlight]" value="1" ${item.is_highlight ? 'checked' : ''} class="h-4 w-4 text-red-600">
                    ไฮไลท์
                </label>
                <button type="button" onclick="removeItem(this)" class="bg-red-100 text-red-600 hover:bg-red-200 p-2 rounded-md h-9 w-9 flex-shrink-0"><i class="fa-solid fa-trash"></i></button>
                <input type="hidden" name="schedule[${dIndex}][date]" value="${item.date || defaultDate}" class="day-date-hidden">
            </div>
        `;
    }

    function updateAllDates(dateInput, dIndex) {
        const dayBlock = document.querySelector(`.day-block[data-day-index="${dIndex}"]`);
        const hiddenDateInputs = dayBlock.querySelectorAll('.day-date-hidden');
        hiddenDateInputs.forEach(input => input.value = dateInput.value);
    }
    
    function removeDayBlock(button) {
        button.closest('.day-block').remove();
    }

    function removeItem(button) {
        button.closest('.schedule-item').remove();
    }

    document.addEventListener('DOMContentLoaded', renderAllDays);
</script>

<?php
include 'partials/footer.php';
?>

