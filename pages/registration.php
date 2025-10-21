<?php
// pages/registration.php
// Registration form page for an event (Fully Upgraded Version)

// --- 1. Validate and get Event Code ---
if (!isset($_GET['event_code']) || empty($_GET['event_code'])) {
    header('Location: index.php');
    exit;
}
$event_code = $_GET['event_code'];

// --- 2. Fetch event and distance data ---
$event_stmt = $mysqli->prepare("SELECT id, name, is_registration_open, payment_bank, payment_account_name, payment_account_number, payment_qr_code_url, start_date FROM events WHERE event_code = ? LIMIT 1"); // เพิ่ม start_date
$event_stmt->bind_param("s", $event_code);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
if ($event_result->num_rows === 0) {
    // ใช้ header() แทน echo เพื่อ redirect
    header('Location: index.php?page=home&error=event_not_found');
    exit;
}
$event = $event_result->fetch_assoc();
$event_stmt->close();

if (!$event['is_registration_open']) {
    header('Location: index.php?page=microsite&event_code=' . urlencode($event_code));
    exit;
}

$distances_stmt = $mysqli->prepare("SELECT id, name, price, category FROM distances WHERE event_id = ? ORDER BY price DESC");
$distances_stmt->bind_param("i", $event['id']);
$distances_stmt->execute();
$distances_result = $distances_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$distances_stmt->close();

// --- 3. Fetch Master Data for form options ---
$master_titles = $mysqli->query("SELECT * FROM master_titles ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$master_shirt_sizes = $mysqli->query("SELECT * FROM master_shirt_sizes ORDER BY FIELD(name, 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL')")->fetch_all(MYSQLI_ASSOC);
$master_genders = $mysqli->query("SELECT * FROM master_genders ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// --- 4. Check for logged-in runner and fetch their data ---
$logged_in_runner_data = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // ดึงข้อมูลผู้ใช้ รวมถึง gender และ birth_date
    $user_stmt = $mysqli->prepare("SELECT title, first_name, last_name, gender, birth_date, email, phone, line_id, thai_id, emergency_contact_name, emergency_contact_phone, disease, disease_detail FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $result = $user_stmt->get_result();
    if ($result->num_rows > 0) {
        $logged_in_runner_data = $result->fetch_assoc();
    }
    $user_stmt->close();
}


// Set Page Title
$page_title = 'สมัครเข้าร่วม: ' . e($event['name']);
?>

<div id="multi-step-form-container">
    <h2 class="text-3xl font-extrabold mb-6 text-gray-800">สมัคร: <?= e($event['name']) ?></h2>

    <div id="progress-bar-container" class="mb-8"></div>

    <form id="registration-form" onsubmit="return false;">
        <input type="hidden" name="event_id" value="<?= e($event['id']) ?>">
        <div id="form-step-content">
            </div>
    </form>

    <div class="flex justify-between mt-8">
        <button id="prev-btn" class="py-2 px-6 rounded-lg bg-gray-300 text-gray-800 hover:bg-gray-400 transition font-bold" onclick="prevStep()">
            <i class="fa-solid fa-chevron-left mr-2"></i> ย้อนกลับ
        </button>
        <button id="next-btn" class="py-2 px-6 rounded-lg bg-primary text-white hover:opacity-90 transition font-bold" onclick="nextStep()">
            ถัดไป <i class="fa-solid fa-chevron-right ml-2"></i>
        </button>
    </div>
</div>


<script>
// --- JavaScript for handling the multi-step form ---

// --- 1. State Management ---
let currentStep = 1;
const totalSteps = 3;
let registrationData = {}; // Stores data between steps

// Data from PHP (ensure JSON encoding handles nulls and special characters)
const currentEvent = <?= json_encode($event, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const distances = <?= json_encode($distances_result, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const eventCode = '<?= e($event_code) ?>';
const loggedInRunner = <?= $logged_in_runner_data ? json_encode($logged_in_runner_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null' ?>;
const masterTitles = <?= json_encode($master_titles, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const masterShirtSizes = <?= json_encode($master_shirt_sizes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const masterGenders = <?= json_encode($master_genders, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;


// --- Message Box Functions (Ensure these exist globally or include message_box.php correctly) ---
function showMessage(title, text) {
    const box = document.getElementById('message-box');
    if (box) {
        document.getElementById('message-title').textContent = title;
        document.getElementById('message-text').textContent = text;
        document.getElementById('message-text').classList.remove('hidden'); // Ensure text is shown
        document.getElementById('message-content-container').classList.add('hidden'); // Hide complex content
        document.getElementById('message-action-btn').classList.add('hidden'); // Hide action button
        box.classList.remove('hidden');
    } else {
        console.warn("Message box element not found!");
        alert(`${title}\n\n${text}`); // Fallback alert
    }
}
function hideMessage() {
    const box = document.getElementById('message-box');
    if (box) {
        box.classList.add('hidden');
    }
}

// --- 2. Core Rendering Functions ---

function renderProgressBar() {
    const registrationSteps = [
        { name: 'เลือกระยะทาง' },
        { name: 'กรอกข้อมูลส่วนตัว' },
        { name: 'สรุปและชำระเงิน' }
    ];
    const container = document.getElementById('progress-bar-container');
    if (!container) return; // Add check
    container.innerHTML = `
        <div class="flex justify-between text-xs font-medium text-gray-500 mb-2">
            ${registrationSteps.map((s, index) => `<span class="${index + 1 === currentStep ? 'text-primary font-bold' : ''}">${s.name}</span>`).join('')}
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="bg-primary h-2.5 rounded-full transition-all duration-500" style="width: ${((currentStep - 1) / (totalSteps - 1)) * 100}%"></div>
        </div>
    `;
}

function attachEventListeners() {
    if (currentStep === 2) {
        // Medical condition radio buttons
        const diseaseRadios = document.querySelectorAll('input[name="disease"]');
        diseaseRadios.forEach(radio => {
             if(radio) radio.addEventListener('change', handleDiseaseChange);
        });

        // Live validation for Thai ID input
        const thaiIdInput = document.getElementById('thai_id');
        const thaiIdError = document.getElementById('thai-id-error');
        if (thaiIdInput && thaiIdError) {
            thaiIdInput.addEventListener('blur', () => {
                if (thaiIdInput.value.length > 0 && !validateThaiID(thaiIdInput.value)) {
                    thaiIdError.classList.remove('hidden');
                } else {
                    thaiIdError.classList.add('hidden');
                }
            });
             // Initial check if there's pre-filled data
             if (thaiIdInput.value.length > 0 && !validateThaiID(thaiIdInput.value)) {
                 thaiIdError.classList.remove('hidden');
             }
        }
    }
}

function handleDiseaseChange(event) {
    const diseaseDetailContainer = document.getElementById('disease-detail-container');
    const diseaseDetailTextarea = document.getElementById('disease_detail');
    if (!diseaseDetailContainer || !diseaseDetailTextarea) return; // Add checks

    // Ensure event and event.target exist before accessing value
    const value = event && event.target ? event.target.value : null;

    if (value === 'มีโรคประจำตัว') {
        diseaseDetailContainer.classList.remove('hidden');
        diseaseDetailTextarea.required = true;
    } else {
        diseaseDetailContainer.classList.add('hidden');
        diseaseDetailTextarea.required = false;
        // Don't clear the value if just switching back, user might want to edit later
        // diseaseDetailTextarea.value = '';
    }
}

// Renders the content for the current step
function renderCurrentStep() {
    renderProgressBar();
    const content = document.getElementById('form-step-content');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    if (!content || !prevBtn || !nextBtn) {
        console.error("Core form elements not found!");
        return;
    }

    content.innerHTML = ''; // Clear previous step content

    prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
    nextBtn.innerHTML = currentStep === totalSteps
        ? `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร`
        : `ถัดไป <i class="fa-solid fa-chevron-right ml-2"></i>`;
    nextBtn.disabled = false; // Ensure button is enabled when rendering

    if (currentStep === 1) {
        content.innerHTML = `
            <div class="space-y-4">
                <h3 class="text-xl font-semibold mb-4">1. เลือกระยะทางการแข่งขัน</h3>
                ${distances && distances.length > 0 ? distances.map(d => `
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer transition duration-200 hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-blue-50">
                        <input type="radio" name="distance_id" value="${d.id}" data-distance-name="${d.name || ''}" class="h-5 w-5 text-primary focus:ring-primary"
                               ${registrationData.distance_id == d.id ? 'checked' : ''}>
                        <div class="ml-4 flex justify-between w-full items-center">
                            <span class="font-medium text-gray-900 text-lg">${d.name || 'N/A'} (${d.category || 'N/A'})</span>
                            <span class="font-bold text-primary text-lg">${parseFloat(d.price || 0).toLocaleString('th-TH')} บาท</span>
                        </div>
                    </label>
                `).join('') : '<p class="text-gray-500">ไม่มีระยะทางให้เลือกสำหรับกิจกรรมนี้</p>'}
            </div>
        `;
    } else if (currentStep === 2) {
        const selectedDistance = distances.find(d => d.id == registrationData.distance_id);
        // Use previously saved data if available (e.g., navigating back)
        const userInfo = registrationData.userInfo || loggedInRunner || {};

        // Generate options (add checks for master data existence)
        const titleOptions = masterTitles ? masterTitles.map(t => `<option value="${t.name || ''}" ${userInfo.title === t.name ? 'selected' : ''}>${t.name || ''}</option>`).join('') : '';
        const shirtSizeOptions = masterShirtSizes ? masterShirtSizes.map(s => `<option value="${s.name || ''}" ${userInfo.shirt_size === s.name ? 'selected' : ''}>${s.name || ''} ${s.description || ''}</option>`).join('') : '';
        const genderOptions = masterGenders ? masterGenders.map(g => `<option value="${g.name || ''}" ${userInfo.gender === g.name ? 'selected' : ''}>${g.name || ''}</option>`).join('') : '';

        // Function to safely get value from userInfo
        const getUserInfoValue = (key) => userInfo[key] || '';

        content.innerHTML = `
            <h3 class="text-xl font-semibold mb-4">2. ข้อมูลส่วนตัวและเสื้อ (ระยะทาง: ${selectedDistance ? selectedDistance.name : 'N/A'})</h3>
            <div class="p-6 bg-white rounded-xl border border-gray-200 space-y-4">

                ${loggedInRunner ?
                    `<div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-md">
                        <p class="font-bold"><i class="fa fa-check-circle mr-2"></i>เข้าสู่ระบบในชื่อ ${getUserInfoValue('first_name')}</p>
                        <p class="text-sm">ข้อมูลของคุณถูกกรอกไว้ล่วงหน้าแล้ว กรุณาตรวจสอบและกรอกข้อมูลที่เหลือ</p>
                    </div>` :
                    `<div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md flex justify-between items-center">
                        <div>
                            <p class="font-bold">เคยสมัครกิจกรรมกับเราแล้วใช่ไหม?</p>
                            <p class="text-sm">เข้าสู่ระบบเพื่อกรอกข้อมูลอัตโนมัติ</p>
                        </div>
                        <a href="index.php?page=dashboard" class="bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-600 transition text-sm whitespace-nowrap">
                            <i class="fa-solid fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
                        </a>
                    </div>`
                }

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">คำนำหน้า <span class="text-red-500">*</span></label>
                        <select id="title" name="title" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">${titleOptions}</select>
                    </div>
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อจริง <span class="text-red-500">*</span></label>
                        <input type="text" id="first_name" name="first_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${getUserInfoValue('first_name')}">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" id="last_name" name="last_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${getUserInfoValue('last_name')}">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                     <div>
                         <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">เพศ <span class="text-red-500">*</span></label>
                         <select id="gender" name="gender" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                            <option value="">-- กรุณาเลือก --</option>
                            ${genderOptions}
                         </select>
                    </div>
                    <div>
                        <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">วัน/เดือน/ปีเกิด <span class="text-red-500">*</span></label>
                        <input type="date" id="birth_date" name="birth_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${getUserInfoValue('birth_date')}">
                    </div>
                    <div>
                        <label for="thai_id" class="block text-sm font-medium text-gray-700 mb-1">หมายเลขบัตรประชาชน <span class="text-red-500">*</span></label>
                        <input type="text" id="thai_id" name="thai_id" required pattern="\\d{13}" maxlength="13" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${getUserInfoValue('thai_id')}">
                        <p id="thai-id-error" class="text-xs text-red-500 mt-1 hidden">รูปแบบหมายเลขบัตรประชาชนไม่ถูกต้อง</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${getUserInfoValue('email')}">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                        <input type="tel" id="phone" name="phone" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${getUserInfoValue('phone')}">
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">ข้อมูลผู้ติดต่อฉุกเฉิน</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ติดต่อ <span class="text-red-500">*</span></label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="${getUserInfoValue('emergency_contact_name')}" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ติดต่อ <span class="text-red-500">*</span></label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="${getUserInfoValue('emergency_contact_phone')}" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-md bg-red-50 border border-red-200">
                    <label class="block text-sm font-medium text-red-600 mb-2">ข้อมูลทางการแพทย์ (สำคัญ)</label>
                    <div class="flex items-center space-x-6">
                         <label class="flex items-center">
                            <input type="radio" name="disease" value="ไม่มีโรคประจำตัว" class="form-radio h-4 w-4 text-primary" ${getUserInfoValue('disease') !== 'มีโรคประจำตัว' ? 'checked' : ''}>
                            <span class="ml-2 text-gray-700">ไม่มีโรคประจำตัว</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="disease" value="มีโรคประจำตัว" class="form-radio h-4 w-4 text-primary" ${getUserInfoValue('disease') === 'มีโรคประจำตัว' ? 'checked' : ''}>
                            <span class="ml-2 text-gray-700">มีโรคประจำตัว</span>
                        </label>
                    </div>
                     <div id="disease-detail-container" class="mt-4 ${getUserInfoValue('disease') !== 'มีโรคประจำตัว' ? 'hidden' : ''}">
                         <label for="disease_detail" class="block text-sm font-medium text-gray-700 mb-1">โปรดระบุ:</label>
                         <textarea id="disease_detail" name="disease_detail" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" ${getUserInfoValue('disease') === 'มีโรคประจำตัว' ? 'required' : ''}>${getUserInfoValue('disease_detail')}</textarea>
                    </div>
                </div>

                <div>
                     <label for="shirt_size" class="block text-sm font-medium text-gray-700 mb-1">ไซส์เสื้อ <span class="text-red-500">*</span></label>
                     <select id="shirt_size" name="shirt_size" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="">-- กรุณาเลือก --</option>
                        ${shirtSizeOptions}
                     </select>
                </div>
            </div>
        `;
        // Re-attach event listener and set initial state for disease detail
        const checkedDiseaseRadio = document.querySelector('input[name="disease"]:checked');
        if(checkedDiseaseRadio) {
             handleDiseaseChange({ target: checkedDiseaseRadio });
        }


    } else if (currentStep === 3) {
        const selectedDistance = distances.find(d => d.id == registrationData.distance_id);
        const userInfo = registrationData.userInfo; // Use data saved from step 2

        // Function to safely get value for summary
        const getUserInfoValueSummary = (key) => userInfo && userInfo[key] ? userInfo[key] : '-';

        content.innerHTML = `
             <h3 class="text-xl font-semibold mb-4">3. สรุปข้อมูลการสมัคร</h3>
             <div class="p-6 bg-gray-50 rounded-xl border border-gray-200 mb-6 space-y-2 text-gray-800 text-sm">
                <p><strong>ชื่อ-สกุล:</strong> ${getUserInfoValueSummary('title')} ${getUserInfoValueSummary('first_name')} ${getUserInfoValueSummary('last_name')}</p>
                <p><strong>เพศ:</strong> ${getUserInfoValueSummary('gender')}</p>
                <p><strong>อีเมล:</strong> ${getUserInfoValueSummary('email')}</p>
                <p><strong>โทรศัพท์:</strong> ${getUserInfoValueSummary('phone')}</p>
                <hr class="my-2 border-gray-300">
                <p><strong>ระยะทาง:</strong> ${selectedDistance ? selectedDistance.name : '-'}</p>
                <p><strong>ขนาดเสื้อ:</strong> ${registrationData.shirt_size || '-'}</p> <p><strong>ข้อมูลสุขภาพ:</strong> ${getUserInfoValueSummary('disease') === 'มีโรคประจำตัว' ? getUserInfoValueSummary('disease_detail') : 'ไม่มีโรคประจำตัว'}</p>
                 <p><strong>ผู้ติดต่อฉุกเฉิน:</strong> ${getUserInfoValueSummary('emergency_contact_name')} (${getUserInfoValueSummary('emergency_contact_phone')})</p>
            </div>

            <h3 class="text-xl font-semibold my-4"><i class="fa-solid fa-money-check-dollar mr-2"></i> การชำระเงิน</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <div class="bg-white p-4 rounded-lg border text-center shadow-sm">
                    <p class="font-semibold text-gray-700 mb-2">สแกน QR Code เพื่อชำระเงิน</p>
                    <img src="${currentEvent.payment_qr_code_url || 'https://placehold.co/160x160?text=QR+Code'}" alt="Payment QR Code" class="mx-auto my-2 rounded-md shadow-sm w-40 h-40 object-contain bg-white">
                </div>
                <div class="space-y-4">
                    <div class="bg-white p-4 rounded-lg border shadow-sm">
                        <label for="payment_slip" class="block text-sm font-medium text-gray-700 mb-2">อัปโหลดหลักฐาน <span class="text-red-500">*</span></label>
                        <input type="file" id="payment_slip" name="payment_slip" required accept="image/jpeg,image/png,application/pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-100 hover:file:bg-gray-200 file:cursor-pointer">
                         <p class="text-xs text-gray-500 mt-1">ไฟล์ JPG, PNG, PDF ขนาดไม่เกิน 5MB</p>
                    </div>
                     <div class="bg-primary text-white p-4 rounded-lg text-center shadow-lg">
                        <p class="text-lg">ยอดชำระเงินทั้งหมด</p>
                        <p class="text-4xl font-extrabold">${parseFloat(selectedDistance ? selectedDistance.price : 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} บาท</p>
                    </div>
                </div>
            </div>
        `;
    }

    attachEventListeners(); // Re-attach listeners after rendering content
}

// --- 3. Navigation and Validation ---

// Simplified Thai ID Check (checksum only, regex done in HTML pattern)
function validateThaiID(id) {
    if (!id || id.length !== 13 || !/^\d+$/.test(id)) return false;
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        sum += parseInt(id.charAt(i)) * (13 - i);
    }
    const checkDigit = (11 - (sum % 11)) % 10;
    return parseInt(id.charAt(12)) === checkDigit;
}

function nextStep() {
    const form = document.getElementById('registration-form'); // Get form reference
    if (!form) return; // Exit if form doesn't exist

    if (currentStep === 1) {
        const selectedDistanceRadio = form.querySelector('input[name="distance_id"]:checked');
        if (!selectedDistanceRadio) {
            showMessage('ข้อมูลไม่ครบถ้วน', 'กรุณาเลือกระยะทางการแข่งขัน');
            return;
        }
        registrationData.distance_id = selectedDistanceRadio.value;
        registrationData.distance_name = selectedDistanceRadio.dataset.distanceName;

    } else if (currentStep === 2) {
        // Use form.elements for easier access and validation
        const elements = form.elements;
        const requiredFields = ['title', 'first_name', 'last_name', 'gender', 'birth_date', 'thai_id', 'email', 'phone', 'emergency_contact_name', 'emergency_contact_phone', 'shirt_size'];
        let firstInvalidField = null;

        for (const fieldName of requiredFields) {
            const field = elements[fieldName];
            if (!field || !field.value.trim()) {
                 firstInvalidField = field;
                 break; // Stop at the first invalid field
            }
        }
        // Check disease detail if 'มีโรคประจำตัว' is selected
        const diseaseSelected = elements['disease'] ? elements['disease'].value : null;
        if (diseaseSelected === 'มีโรคประจำตัว' && (!elements['disease_detail'] || !elements['disease_detail'].value.trim())) {
             firstInvalidField = elements['disease_detail'];
        }


        if (firstInvalidField) {
            // Try to get the label text, otherwise use field name
            const label = firstInvalidField.labels && firstInvalidField.labels.length > 0
                          ? firstInvalidField.labels[0].innerText.replace('*','').trim()
                          : firstInvalidField.name;
            showMessage('ข้อมูลไม่ครบถ้วน', `กรุณากรอกข้อมูล: ${label}`);
            firstInvalidField.focus(); // Focus on the invalid field
            return; // Stop processing
        }


        if (!validateThaiID(elements.thai_id.value)) {
            showMessage('ข้อมูลไม่ถูกต้อง', 'รูปแบบหมายเลขบัตรประชาชนไม่ถูกต้อง');
            elements.thai_id.focus();
            return;
        }

        // Save all data from step 2 form into registrationData.userInfo
        const formData = new FormData(form);
        registrationData.userInfo = Object.fromEntries(formData.entries());
        // Also save shirt_size separately for easy access in step 3 summary
        registrationData.shirt_size = elements.shirt_size.value;
    }

    if (currentStep < totalSteps) {
        currentStep++;
        renderCurrentStep();
         window.scrollTo(0, 0); // Scroll to top on step change
    } else {
        completeRegistration();
    }
}

function prevStep() {
    if (currentStep > 1) {
        // Save current form state before going back (optional but good UX)
        if (currentStep === 2) {
             const form = document.getElementById('registration-form');
             if(form) {
                 const formData = new FormData(form);
                 registrationData.userInfo = Object.fromEntries(formData.entries());
                 registrationData.shirt_size = form.elements.shirt_size ? form.elements.shirt_size.value : null;
             }
        } else if (currentStep === 3) {
            // Clear slip file selection if going back from summary
             const slipInput = document.getElementById('payment_slip');
             if(slipInput) slipInput.value = '';
        }
        currentStep--;
        renderCurrentStep();
        window.scrollTo(0, 0); // Scroll to top on step change
    }
}

// --- 4. Final Submission ---
function completeRegistration() {
    console.log("Starting completeRegistration..."); // Debug log
    const slipInput = document.getElementById('payment_slip');
    if (!slipInput || !slipInput.files || slipInput.files.length === 0) {
        showMessage('ข้อมูลไม่ครบถ้วน', 'กรุณาอัปโหลดหลักฐานการชำระเงิน');
        return;
    }
    const slipFile = slipInput.files[0];

    // Basic file validation (client-side)
    const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!allowedTypes.includes(slipFile.type)) {
        showMessage('ไฟล์ไม่ถูกต้อง', 'กรุณาอัปโหลดไฟล์ JPG, PNG, หรือ PDF เท่านั้น');
        return;
    }
    if (slipFile.size > 5 * 1024 * 1024) { // 5MB
         showMessage('ไฟล์มีขนาดใหญ่เกินไป', 'ขนาดไฟล์ต้องไม่เกิน 5MB');
         return;
    }

    console.log("Preparing FormData for submission..."); // Debug log
    const finalFormData = new FormData();
    finalFormData.append('event_id', currentEvent.id);
    finalFormData.append('event_code', eventCode);
    finalFormData.append('distance_id', registrationData.distance_id);
    finalFormData.append('shirt_size', registrationData.shirt_size);
    finalFormData.append('payment_slip', slipFile);
    finalFormData.append('distance_name', registrationData.distance_name);

    // Append user info from saved state
    if (registrationData.userInfo) {
        for (const key in registrationData.userInfo) {
            finalFormData.append(key, registrationData.userInfo[key]);
        }
    } else {
        console.error("userInfo is missing in registrationData!");
        showMessage('เกิดข้อผิดพลาด', 'ข้อมูลผู้สมัครไม่ครบถ้วน กรุณาลองย้อนกลับไปขั้นตอนก่อนหน้า');
        return;
    }

     // Log FormData content (for debugging only, remove sensitive data in production logs)
     console.log("FormData content (excluding file):");
     for (let [key, value] of finalFormData.entries()) {
         if (!(value instanceof File)) {
             console.log(`${key}: ${value}`);
         } else {
              console.log(`${key}: ${value.name} (${value.type}, ${value.size} bytes)`);
         }
     }


    const nextBtn = document.getElementById('next-btn');
    if (!nextBtn) return; // Safety check
    nextBtn.disabled = true;
    nextBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i> กำลังส่งข้อมูล...`;

    console.log("Sending fetch request to actions/process_registration.php..."); // Debug log
    fetch('actions/process_registration.php', {
        method: 'POST',
        body: finalFormData
        // Headers are automatically set for FormData
    })
    .then(response => {
        console.log('Server Response Status:', response.status, response.statusText);
        if (!response.ok) {
            console.error('Network response was not ok. Status:', response.status);
            // Try to get more detailed error from response body
            return response.text().then(text => {
                 console.error('Server Error Response Text:', text);
                 // Try to parse as JSON in case PHP sent a JSON error
                 try {
                     const errorData = JSON.parse(text);
                     throw new Error(errorData.message || text || `Server error ${response.status}`);
                 } catch (e) {
                      // If not JSON, throw the raw text
                      throw new Error(text || `Server error ${response.status}`);
                 }

            });
        }
        console.log('Attempting to parse response as JSON...');
        // Check content type before parsing
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json();
        } else {
             // Handle cases where response is not JSON (e.g., unexpected HTML)
             return response.text().then(text => {
                 console.error("Expected JSON, received non-JSON response:", text);
                 throw new Error("Invalid response format from server.");
             });
        }

    })
    .then(data => {
        console.log('Received data from server:', data); // Log ข้อมูลที่ได้รับ

        // ตรวจสอบโครงสร้าง data
        if (typeof data !== 'object' || data === null) {
             console.error('Invalid data format received:', data);
             // คืนค่าปุ่มก่อนแสดงข้อความ
             nextBtn.disabled = false;
             nextBtn.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร`;
             showMessage('เกิดข้อผิดพลาด', 'ได้รับข้อมูลตอบกลับในรูปแบบที่ไม่ถูกต้องจากเซิร์ฟเวอร์');
             return; // หยุดการทำงานต่อ
        }

        // ตรวจสอบค่า success และ redirect_url
        if (data.success === true && typeof data.redirect_url === 'string' && data.redirect_url.length > 0) {
            console.log('Registration successful according to server.');
            console.log('Redirect URL received from PHP:', data.redirect_url); // --> ../index.php?page=dashboard

            try {
                // --- สร้าง Absolute URL ใหม่ ---
                let absoluteRedirectUrl;

                // 1. หา Base Path ของ Application
                // window.location.pathname คือ path ปัจจุบัน เช่น /pao_run/index.php
                // เราต้องการ /pao_run/
                const currentPath = window.location.pathname; // e.g., /pao_run/index.php
                // หา index ของ '/index.php' หรือ '/pages/' เพื่อหา base
                let basePath = '/'; // Default to root if pattern not found
                const indexPhpPos = currentPath.indexOf('/index.php');
                if (indexPhpPos !== -1) {
                    basePath = currentPath.substring(0, indexPhpPos + 1); // Get path up to and including the slash before index.php, e.g., /pao_run/
                } else {
                     // Fallback: อาจจะอยู่ใน admin หรือ path อื่น, ลองหา last slash
                     basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
                     console.warn("Could not find '/index.php' in path, guessing base path:", basePath);
                     // If still doesn't look right, manually set it: basePath = '/pao_run/';
                }


                // 2. เอา '../' ออกจาก URL ที่ PHP ส่งมา
                const relativeUrlFromBase = data.redirect_url.startsWith('../')
                                            ? data.redirect_url.substring(3) // เอา '../' ออก
                                            : data.redirect_url; // ถ้าไม่มี ../ ก็ใช้ตามเดิม (เผื่อกรณีอื่น)

                // 3. ประกอบ URL เต็ม
                // window.location.origin คือ http://localhost
                absoluteRedirectUrl = window.location.origin + basePath + relativeUrlFromBase;

                console.log('Base Path calculated:', basePath);
                console.log('Relative URL from Base:', relativeUrlFromBase);
                console.log('Constructed Absolute Redirect URL:', absoluteRedirectUrl); // --> ควรจะได้ http://localhost/pao_run/index.php?page=dashboard

                // --- สั่ง Redirect ---
                console.log('Attempting redirect now to:', absoluteRedirectUrl);
                window.location.href = absoluteRedirectUrl; // ใช้ URL เต็มที่สร้างขึ้น

                console.log('Redirect command issued.');
                // ไม่ควรมีโค้ดอื่นทำงานต่อจากนี้

            } catch (e) {
                 console.error("Error constructing or performing redirect:", e);
                 // คืนค่าปุ่มถ้าเกิด Error ตอนสั่ง redirect
                 nextBtn.disabled = false;
                 nextBtn.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร`;
                 showMessage('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการพยายามเปลี่ยนหน้าเว็บ: ' + e.message);
            }

        } else { // กรณี success ไม่ใช่ true หรือ ไม่มี redirect_url
            console.warn('Registration reported as not successful or missing/invalid redirect URL.', data);
            const errorMessage = data.message || 'ไม่สามารถบันทึกข้อมูลได้ หรือ ไม่ได้รับ URL ที่ถูกต้องสำหรับเปลี่ยนหน้า';
            // คืนค่าปุ่มก่อนแสดงข้อความ
            nextBtn.disabled = false;
            nextBtn.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร`;
            showMessage('เกิดข้อผิดพลาด', errorMessage);
        }
    })
    .catch(error => {
        // (ส่วน catch เหมือนเดิม)
        console.error('Error during fetch operation or processing response:', error);
        let displayMessage = 'เกิดข้อผิดพลาดในการส่งข้อมูล: ';
        // ... (โค้ดสร้าง displayMessage เหมือนเดิม) ...
         if (error.message.includes("Network response was not ok") || error.message.includes("Server error")) {
            displayMessage = 'เกิดข้อผิดพลาดในการสื่อสารกับเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแล';
        } else if (error.message.includes("Invalid response format")) {
             displayMessage = 'ได้รับข้อมูลตอบกลับในรูปแบบที่ไม่ถูกต้องจากเซิร์ฟเวอร์';
        } else if (error instanceof SyntaxError) {
             displayMessage = 'เกิดข้อผิดพลาดในการประมวลผลข้อมูลตอบกลับ';
        } else {
             displayMessage += error.message;
        }

        // คืนค่าปุ่มก่อนแสดงข้อความ
        if (typeof nextBtn !== 'undefined' && nextBtn) { // เพิ่มการตรวจสอบ nextBtn ก่อนใช้งาน
            nextBtn.disabled = false;
            nextBtn.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร`;
        }
        showMessage('การเชื่อมต่อล้มเหลว', displayMessage);
    });
} // สิ้นสุด function completeRegistration

// --- Initial Render ---
document.addEventListener('DOMContentLoaded', () => {
    // Pre-fill form if logged in user data exists
    if (loggedInRunner) {
        // Create a copy to avoid modifying original loggedInRunner object
         registrationData.userInfo = { ...loggedInRunner };
         // Ensure shirt_size is also pre-filled if navigating back and forth
         if (registrationData.shirt_size) {
            registrationData.userInfo.shirt_size = registrationData.shirt_size;
         }
    }
    renderCurrentStep(); // Render the initial step
});
</script>