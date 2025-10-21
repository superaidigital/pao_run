<?php
// pages/microsite.php
// Displays the detailed information for a single event.

// --- 1. Validate and get Event Code ---
if (!isset($_GET['event_code']) || empty($_GET['event_code'])) {
    header('Location: index.php');
    exit;
}
$event_code = $_GET['event_code'];

// --- 2. Fetch primary event data ---
$stmt = $mysqli->prepare("SELECT * FROM events WHERE event_code = ? LIMIT 1");
$stmt->bind_param("s", $event_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-center text-red-500'>ไม่พบข้อมูลกิจกรรมที่คุณต้องการ</p>";
    return; 
}
$event = $result->fetch_assoc();
$stmt->close();

// --- 3. Fetch distances for the event ---
$distances_stmt = $mysqli->prepare("SELECT * FROM distances WHERE event_id = ? ORDER BY price DESC");
$distances_stmt->bind_param("i", $event['id']);
$distances_stmt->execute();
$distances = $distances_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$distances_stmt->close();

// --- 4. Fetch images for the event ---
$images_stmt = $mysqli->prepare("SELECT image_url, image_type FROM event_images WHERE event_id = ?");
$images_stmt->bind_param("i", $event['id']);
$images_stmt->execute();
$images_result = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$images_stmt->close();

// --- 5. Fetch schedule data for the event ---
$schedule_stmt = $mysqli->prepare("SELECT * FROM schedules WHERE event_id = ? ORDER BY date, time ASC");
$schedule_stmt->bind_param("i", $event['id']);
$schedule_stmt->execute();
$schedules_raw = $schedule_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$schedule_stmt->close();

// Group schedules by date
$schedules_grouped = [];
foreach ($schedules_raw as $item) {
    $date_key = $item['date'];
    if (!isset($schedules_grouped[$date_key])) {
        $schedules_grouped[$date_key] = [];
    }
    $schedules_grouped[$date_key][] = $item;
}

// Separate images by type
$merch_images = array_values(array_filter($images_result, fn($img) => $img['image_type'] == 'merch'));
$medal_images = array_values(array_filter($images_result, fn($img) => $img['image_type'] == 'medal'));
$detail_images = array_values(array_filter($images_result, fn($img) => $img['image_type'] == 'detail'));

// Set Page Title and Theme Color
$page_title = $event['name'];
echo "<style>
    :root { --color-primary: " . e($event['color_code']) . "; }
    #schedule-items-container {
        max-height: 180px; /* Initial collapsed height, shows ~3 items */
        overflow: hidden;
        transition: max-height 0.5s ease-in-out;
    }
    #schedule-items-container.expanded {
        max-height: 1000px; /* Large value to accommodate all items */
    }
    /* CKEditor content styling */
    .description-content ul, .description-content ol { list-style: revert; margin-left: 2em; }
    .description-content li { list-style-type: disc; }
</style>";
?>

<!-- === HTML Display Section Starts Here === -->
<div class="space-y-6">
    <!-- Cover Image Section -->
    <div class="relative rounded-xl overflow-hidden shadow-2xl text-white h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?= e($event['cover_image_url']) ?>');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-black/20 flex flex-col justify-end p-6 md:p-8">
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight"><?= e($event['name']) ?></h2>
            <p class="mt-1 text-lg font-semibold"><?= e($event['slogan']) ?></p>
        </div>
    </div>
    
    <!-- Top Section: Contact and Countdown -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Contact Info Card -->
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 shadow-sm">
             <h3 class="font-bold text-lg mb-3 text-primary"><i class="fa-solid fa-user-headset mr-2"></i>ข้อมูลการติดต่อ (ผู้จัดงาน)</h3>
             <div class="flex items-center gap-4">
                <?php if (!empty($event['organizer_logo_url'])): ?>
                    <img src="./<?= e($event['organizer_logo_url']) ?>" class="w-16 h-16 object-contain rounded-full bg-white border shadow-sm shrink-0">
                <?php endif; ?>
                <div class="text-sm space-y-1 text-gray-700">
                    <p><strong>ผู้จัด:</strong> <?= e($event['organizer']) ?></p>
                    <p><strong>ผู้ประสานงาน:</strong> <?= e($event['contact_person_name']) ?></p>
                    <p><strong>โทรศัพท์:</strong> <?= e($event['contact_person_phone']) ?></p>
                </div>
             </div>
        </div>
        <!-- Countdown Card -->
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 shadow-sm">
             <h3 class="font-bold text-lg mb-2 text-primary"><i class="fa-solid fa-stopwatch mr-2"></i>เหลือเวลาอีก</h3>
             <div id="countdown-timer">
                <!-- Countdown will be injected here by JavaScript -->
             </div>
        </div>
    </div>

    <!-- Race Day Schedule Card (Collapsible) -->
    <?php if (!empty($schedules_grouped)): ?>
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <h3 class="font-bold text-lg mb-4 text-primary"><i class="fa-solid fa-calendar-day mr-2"></i>กำหนดการ</h3>
        <div id="schedule-items-wrapper">
            <div id="schedule-items-container" class="space-y-6">
                <?php foreach ($schedules_grouped as $date => $items): ?>
                <div>
                    <h4 class="font-bold text-md text-gray-700 mb-3 border-b pb-2">
                        <i class="fa-solid fa-calendar-alt mr-2 text-gray-400"></i>
                        <?= formatThaiDate($date) ?>
                    </h4>
                    <div class="relative pl-8">
                        <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-gray-200"></div>
                        <div class="space-y-6">
                        <?php foreach($items as $item): ?>
                            <div class="relative flex items-start">
                                <div class="absolute -left-2 top-1.5 h-4 w-4 rounded-full <?= $item['is_highlight'] ? 'bg-red-500 ring-4 ring-red-100' : 'bg-gray-300' ?> z-10"></div>
                                <div class="pl-4">
                                    <p class="font-bold text-gray-800"><?= date("H:i", strtotime($item['time'])) ?> น.</p>
                                    <p class="text-sm text-gray-600 <?= $item['is_highlight'] ? 'font-semibold text-red-700' : '' ?>"><?= e($item['activity']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if(count($schedules_raw) > 3): // Only show button if there are more than 3 items ?>
        <div class="mt-4 border-t pt-3 text-center">
            <button id="toggle-schedule-btn" onclick="toggleSchedule()" class="text-sm font-semibold text-primary hover:underline">
                <i class="fa-solid fa-chevron-down mr-2"></i> ดูทั้งหมด
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Description Card -->
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <h3 class="font-bold text-lg mb-2 text-primary">รายละเอียดกิจกรรม</h3>
        <div class="text-sm text-gray-700 leading-relaxed description-content">
            <?= $event['description'] // Output raw HTML from CKEditor ?>
        </div>
    </div>

    <!-- Map Card -->
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <h3 class="font-bold text-lg mb-3 text-primary"><i class="fa-solid fa-map-marked-alt mr-2"></i>แผนที่สถานที่จัดกิจกรรม</h3>
        <div class="w-full rounded-lg overflow-hidden shadow-md mb-3" style="height: 300px;">
            <iframe src="<?= e($event['map_embed_url']) ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
        <a href="<?= e($event['map_direction_url']) ?>" target="_blank" class="w-full flex items-center justify-center py-2 px-4 text-sm font-bold rounded-lg text-white bg-red-500 hover:bg-red-600 transition">
            <i class="fa-solid fa-location-arrow mr-2"></i> เปิด Google Maps นำทาง
        </a>
    </div>
    
    <!-- Detail Images Gallery Card -->
    <?php if(!empty($detail_images)): ?>
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <h3 class="font-bold text-lg mb-3 text-primary">ภาพประกอบกิจกรรม (คลิกเพื่อดูแกลเลอรี)</h3>
        <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
            <?php foreach($detail_images as $index => $img): ?>
                <div class="relative group overflow-hidden rounded-lg aspect-square cursor-pointer" onclick='openGallery("ภาพประกอบกิจกรรม", <?= json_encode(array_column($detail_images, "image_url")) ?>, <?= $index ?>)'>
                    <img src="./<?= e($img['image_url']) ?>" class="w-full h-full object-cover transition duration-300 group-hover:scale-110" alt="ภาพประกอบ">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Souvenirs Section -->
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <h3 class="font-bold text-lg mb-3 text-primary">ของที่ระลึก (คลิกเพื่อดูสรุปภาพรวม)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card 1: Merchandise -->
            <div class="bg-gray-50 p-3 rounded-lg flex flex-col items-center border cursor-pointer" onclick='openGallery("ภาพชุดเสื้อ", <?= json_encode(array_column($merch_images, "image_url")) ?>)'>
                <div class="w-20 h-20 flex items-center justify-center mb-2">
                <?php if (!empty($merch_images)): ?>
                    <img src="./<?= e($merch_images[0]['image_url']) ?>" class="w-full h-full object-cover rounded-md shadow-sm" alt="เสื้อที่ระลึก">
                <?php else: ?>
                    <i class="fa-solid fa-tshirt text-4xl text-gray-400"></i>
                <?php endif; ?>
                </div>
                <h4 class="font-semibold text-sm">เสื้อที่ระลึก</h4>
                <p class="text-xs text-gray-500"><?= count($merch_images) ?> รูปภาพ</p>
            </div>
            <!-- Card 2: Medal -->
            <div class="bg-gray-50 p-3 rounded-lg flex flex-col items-center border cursor-pointer" onclick='openGallery("ภาพชุดเหรียญ", <?= json_encode(array_column($medal_images, "image_url")) ?>)'>
                <div class="w-20 h-20 flex items-center justify-center mb-2">
                <?php if (!empty($medal_images)): ?>
                    <img src="./<?= e($medal_images[0]['image_url']) ?>" class="w-full h-full object-cover rounded-md shadow-sm" alt="เหรียญ Finisher">
                <?php else: ?>
                    <i class="fa-solid fa-medal text-4xl text-gray-400"></i>
                <?php endif; ?>
                </div>
                <h4 class="font-semibold text-sm">เหรียญ Finisher</h4>
                <p class="text-xs text-gray-500"><?= count($medal_images) ?> รูปภาพ</p>
            </div>
            <!-- Card 3: Awards -->
            <div class="bg-gray-50 p-3 rounded-lg flex flex-col items-center border cursor-pointer" onclick="showAwardsDetail()">
                 <div class="w-20 h-20 flex items-center justify-center mb-2">
                     <i class="fa-solid fa-trophy text-4xl text-yellow-500"></i>
                 </div>
                <h4 class="font-semibold text-sm">ของรางวัล</h4>
                <p class="text-xs text-gray-500">(Overall/Age Group)</p>
            </div>
        </div>
    </div>

    <!-- Distances Table Card -->
    <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
        <h3 class="font-bold text-lg mb-3 text-primary">รายการระยะทางและค่าสมัคร</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ระยะทาง</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">ราคา</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($distances as $dist): ?>
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?= e($dist['name']) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= e($dist['category']) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800 text-right font-semibold"><?= number_format($dist['price'], 2) ?> บาท</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Registration Button -->
    <div class="text-center pt-4">
        <?php if ($event['is_registration_open']): ?>
            <a href="index.php?page=registration&event_code=<?= e($event['event_code']) ?>" class="w-full md:w-auto inline-block py-3 px-8 text-lg font-bold rounded-lg text-white bg-primary hover:opacity-90 transition shadow-lg shadow-primary/50">
                <i class="fa-solid fa-user-plus mr-2"></i> สมัครเข้าร่วมกิจกรรมนี้
            </a>
        <?php else: ?>
            <div class="p-4 rounded-lg bg-red-100 text-red-700">
                <p class="font-bold">ปิดรับสมัครแล้ว</p>
                <p class="text-sm">โปรดติดตามกิจกรรมถัดไป</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// --- Countdown Timer Logic ---
document.addEventListener('DOMContentLoaded', function() {
    const targetDate = new Date("<?= e($event['start_date']) ?>").getTime();
    const countdownContainer = document.getElementById('countdown-timer');
    if (!countdownContainer) return;

    const countdownInterval = setInterval(function() {
        const now = new Date().getTime();
        const distance = targetDate - now;

        if (distance < 0) {
            clearInterval(countdownInterval);
            countdownContainer.innerHTML = "<div class='text-center font-bold text-lg text-primary'>กิจกรรมเริ่มแล้ว!</div>";
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownContainer.innerHTML = `
            <div class="flex space-x-2 md:space-x-3 text-center">
                <div class="flex-1"><div class="text-3xl font-bold text-gray-800">${String(days).padStart(2, '0')}</div><div class="text-xs text-gray-500">วัน</div></div>
                <div class="flex-1"><div class="text-3xl font-bold text-gray-800">${String(hours).padStart(2, '0')}</div><div class="text-xs text-gray-500">ชั่วโมง</div></div>
                <div class="flex-1"><div class="text-3xl font-bold text-gray-800">${String(minutes).padStart(2, '0')}</div><div class="text-xs text-gray-500">นาที</div></div>
                <div class="flex-1"><div class="text-3xl font-bold text-gray-800">${String(seconds).padStart(2, '0')}</div><div class="text-xs text-gray-500">วินาที</div></div>
            </div>
        `;
    }, 1000);
});

// --- Schedule Toggle Logic ---
function toggleSchedule() {
    const container = document.getElementById('schedule-items-container');
    const btn = document.getElementById('toggle-schedule-btn');
    if (container && btn) {
        if (container.classList.contains('expanded')) {
            container.classList.remove('expanded');
            btn.innerHTML = '<i class="fa-solid fa-chevron-down mr-2"></i> ดูทั้งหมด';
        } else {
            container.classList.add('expanded');
            btn.innerHTML = '<i class="fa-solid fa-chevron-up mr-2"></i> ย่อลง';
        }
    }
}

// --- Modal/Popup Logic ---
// Note: The main modal functions like openGallery() are in footer.php

function showAwardsDetail() {
    const awardsHtml = <?= json_encode($event['awards_description'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    
    document.getElementById('message-title').textContent = `รายละเอียดของรางวัล`;
    document.getElementById('message-text').classList.add('hidden');
    const contentContainer = document.getElementById('message-content-container');
    contentContainer.innerHTML = `<div class="text-gray-700 leading-relaxed text-base description-content space-y-3">${awardsHtml}</div>`;
    contentContainer.classList.remove('hidden');
    document.getElementById('message-action-btn').classList.add('hidden');
    document.getElementById('message-box').classList.remove('hidden');
}
</script>

