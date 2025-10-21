<?php
// pages/ebib.php - E-BIB Generation Page

// --- Security Check: Ensure a registration ID is provided ---
if (!isset($_GET['reg_id']) || !is_numeric($_GET['reg_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
$reg_id = intval($_GET['reg_id']);

// --- Fetch Registration Data ---
// Only fetch if the status is 'Paid'
$stmt = $mysqli->prepare("
    SELECT 
        r.bib_number, r.title, r.first_name, r.last_name,
        e.name AS event_name, e.logo_text, e.color_code,
        d.name AS distance_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN distances d ON r.distance_id = d.id
    WHERE r.id = ? AND r.status = 'ชำระเงินแล้ว'
    LIMIT 1
");
$stmt->bind_param("i", $reg_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If not found or not paid, redirect with an error
    $_SESSION['error_message'] = "ไม่พบข้อมูลการสมัครที่ชำระเงินแล้วสำหรับ ID นี้ หรือยังไม่ได้รับ BIB Number";
    header('Location: index.php?page=dashboard');
    exit;
}
$bib_data = $result->fetch_assoc();
$stmt->close();

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
    <p class="text-gray-600 mb-6">คุณสามารถบันทึกรูปภาพนี้เพื่อแชร์ หรือใช้แสดงในวันงานได้</p>

    <!-- E-BIB Card -->
    <div id="ebib-card" class="ebib-bg text-white rounded-2xl shadow-2xl p-8 aspect-[8/5] flex flex-col justify-between">
        <!-- Header -->
        <div class="text-left">
            <h3 class="text-xl font-bold opacity-80"><?= e($bib_data['logo_text'] ?? $bib_data['event_name']) ?></h3>
        </div>

        <!-- Main Info -->
        <div class="text-center">
            <div class="text-8xl lg:text-9xl font-black tracking-tighter leading-none"><?= e($bib_data['bib_number'] ?? 'XXX') ?></div>
            <div class="text-2xl lg:text-3xl font-bold mt-2"><?= e($bib_data['title'] . $bib_data['first_name'] . ' ' . $bib_data['last_name']) ?></div>
        </div>

        <!-- Footer -->
        <div class="text-right">
            <span class="bg-white/20 text-white text-lg font-semibold px-4 py-1 rounded-full"><?= e($bib_data['distance_name']) ?></span>
        </div>
    </div>

    <div class="mt-6">
        <button onclick="downloadEbib()" class="bg-primary text-white font-bold py-3 px-6 rounded-lg hover:opacity-90 transition">
            <i class="fa-solid fa-download mr-2"></i> ดาวน์โหลด E-BIB
        </button>
    </div>
</div>

<!-- html2canvas library for capturing the div as an image -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function downloadEbib() {
    const ebibCard = document.getElementById('ebib-card');
    const runnerName = "<?= e($bib_data['first_name']) ?>";
    const bibNumber = "<?= e($bib_data['bib_number'] ?? 'NO-BIB') ?>";
    
    // Show a loading indicator
    const downloadBtn = event.target;
    downloadBtn.disabled = true;
    downloadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> กำลังสร้างรูปภาพ...';

    html2canvas(ebibCard, { 
        scale: 3, // Increase resolution for better quality
        useCORS: true, // Important if your assets are on a different domain
        backgroundColor: null // Use transparent background
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = `E-BIB-${bibNumber}-${runnerName}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        // Restore button state
        downloadBtn.disabled = false;
        downloadBtn.innerHTML = '<i class="fa-solid fa-download mr-2"></i> ดาวน์โหลด E-BIB';
    }).catch(err => {
        console.error('oops, something went wrong!', err);
        alert('เกิดข้อผิดพลาดในการสร้างรูปภาพ');
        // Restore button state
        downloadBtn.disabled = false;
        downloadBtn.innerHTML = '<i class="fa-solid fa-download mr-2"></i> ดาวน์โหลด E-BIB';
    });
}
</script>

