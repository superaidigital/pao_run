<?php
// pages/ebib.php - E-BIB Generation Page (Final Version)

// --- Security Check: Ensure a registration ID is provided and user is logged in ---
if (!isset($_GET['reg_id']) || !is_numeric($_GET['reg_id']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
$reg_id = intval($_GET['reg_id']);
$user_id = intval($_SESSION['user_id']);

// --- Fetch Registration Data ---
// Join all necessary tables to get complete data for the BIB
$stmt = $mysqli->prepare("
    SELECT 
        r.bib_number, r.corral, r.title, r.first_name, r.last_name, r.gender, r.birth_date, r.shirt_size, r.emergency_contact_phone,
        e.name AS event_name, e.logo_text, e.color_code, e.start_date,
        d.name AS distance_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN distances d ON r.distance_id = d.id
    WHERE r.id = ? AND r.user_id = ? AND r.status = 'ชำระเงินแล้ว' AND r.bib_number IS NOT NULL
    LIMIT 1
");
$stmt->bind_param("ii", $reg_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If not found, not paid, no BIB, or doesn't belong to the user, redirect.
    $_SESSION['error_message'] = "ไม่พบข้อมูล E-BIB สำหรับการสมัครนี้ หรือยังไม่ถึงขั้นตอนการรับ BIB";
    header('Location: index.php?page=dashboard');
    exit;
}
$bib_data = $result->fetch_assoc();
$stmt->close();

// Calculate Age Group
$age = '-';
if (!empty($bib_data['birth_date']) && !empty($bib_data['start_date'])) {
    $birthDate = new DateTime($bib_data['birth_date']);
    $eventDate = new DateTime($bib_data['start_date']);
    $ageInterval = $eventDate->diff($birthDate);
    $age = $ageInterval->y;
}

$page_title = 'E-BIB: ' . e($bib_data['first_name']);

// Set custom styles for the E-BIB card background
echo "<style>
    .ebib-bg {
        background: linear-gradient(145deg, " . e($bib_data['color_code']) . " 0%, #1f2937 100%);
    }
</style>";
?>
<div class="max-w-2xl mx-auto text-center">
    <h2 class="text-2xl font-bold mb-4">E-BIB ของคุณ</h2>
    <p class="text-gray-600 mb-6">คุณสามารถบันทึกรูปภาพนี้เพื่อแชร์ หรือใช้แสดงตนในวันรับอุปกรณ์ (Race Kit)</p>

    <!-- E-BIB Card -->
    <div id="ebib-card" class="ebib-bg text-white rounded-2xl shadow-2xl p-6 md:p-8 aspect-[16/10] flex flex-col justify-between font-sans">
        <!-- Header -->
        <div class="flex justify-between items-center opacity-90">
            <h3 class="text-xl font-bold tracking-wider"><?= e($bib_data['logo_text'] ?? $bib_data['event_name']) ?></h3>
            <div class="text-right">
                <p class="text-2xl font-bold"><?= e($bib_data['distance_name']) ?></p>
            </div>
        </div>

        <!-- Main Info -->
        <div class="text-center my-4">
            <div class="flex items-center justify-center gap-4">
                <div class="text-center border-r-2 border-white/50 pr-4">
                    <p class="text-2xl font-semibold">เพศ</p>
                    <p class="text-6xl font-black"><?= e(strtoupper(substr($bib_data['gender'], 0, 1))) ?></p>
                </div>
                 <div class="text-center">
                    <p class="text-2xl font-semibold">กลุ่มอายุ</p>
                    <p class="text-6xl font-black"><?= e($age) ?></p>
                </div>
            </div>
             <div class="mt-4 flex items-end justify-center gap-2">
                <div class="text-6xl md:text-7xl font-black tracking-tighter leading-none bg-white text-black px-4 py-1 rounded-lg shadow-inner"><?= e($bib_data['corral'] ?? '-') ?></div>
                <div class="text-8xl md:text-9xl font-black tracking-tighter leading-none"><?= e($bib_data['bib_number'] ?? 'XXXX') ?></div>
             </div>
            <div class="text-2xl lg:text-3xl font-bold mt-3 tracking-wide"><?= e(strtoupper($bib_data['first_name'] . ' ' . $bib_data['last_name'])) ?></div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center text-sm opacity-90 border-t border-white/20 pt-3">
             <div>
                <p class="font-semibold">EMERGENCY:</p>
                <p><?= e($bib_data['emergency_contact_phone'] ?? '-') ?></p>
            </div>
            <div class="text-right">
                <p class="font-semibold">SHIRT SIZE:</p>
                <p class="text-2xl font-bold"><?= e($bib_data['shirt_size']) ?></p>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <button id="download-btn" class="bg-primary text-white font-bold py-3 px-6 rounded-lg hover:opacity-90 transition shadow-lg">
            <i class="fa-solid fa-download mr-2"></i> ดาวน์โหลด E-BIB
        </button>
    </div>
</div>

<!-- html2canvas library for capturing the div as an image -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById('download-btn').addEventListener('click', function(event) {
    const ebibCard = document.getElementById('ebib-card');
    const runnerName = "<?= e($bib_data['first_name']) ?>";
    const bibNumber = "<?= e($bib_data['bib_number'] ?? 'NO-BIB') ?>";
    
    const downloadBtn = event.currentTarget;
    downloadBtn.disabled = true;
    downloadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> กำลังสร้างรูปภาพ...';

    // Temporarily change font for better compatibility if needed, e.g., to a system font
    // ebibCard.style.fontFamily = 'Arial, sans-serif';

    html2canvas(ebibCard, { 
        scale: 3, // Increase resolution for better quality
        useCORS: true,
        backgroundColor: null, // Use transparent background to capture gradient
        logging: false // Disable logging to console
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = `E-BIB-${bibNumber}-${runnerName}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        // Restore button state and original styles
        downloadBtn.disabled = false;
        downloadBtn.innerHTML = '<i class="fa-solid fa-download mr-2"></i> ดาวน์โหลด E-BIB';
        // ebibCard.style.fontFamily = ''; // Revert font change
    }).catch(err => {
        console.error('Oops, something went wrong!', err);
        alert('เกิดข้อผิดพลาดในการสร้างรูปภาพ E-BIB');
        // Restore button state and original styles
        downloadBtn.disabled = false;
        downloadBtn.innerHTML = '<i class="fa-solid fa-download mr-2"></i> ดาวน์โหลด E-BIB';
        // ebibCard.style.fontFamily = ''; // Revert font change
    });
});
</script>

