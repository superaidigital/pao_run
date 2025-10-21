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
$event_stmt = $mysqli->prepare("SELECT id, name, is_registration_open, payment_bank, payment_account_name, payment_account_number, payment_qr_code_url FROM events WHERE event_code = ? LIMIT 1");
$event_stmt->bind_param("s", $event_code);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
if ($event_result->num_rows === 0) {
    echo "<p class='text-center text-red-500'>ไม่พบข้อมูลกิจกรรม</p>";
    return;
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


// --- 4. Check for logged-in runner and fetch their data ---
$logged_in_runner_data = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_stmt = $mysqli->prepare("SELECT title, first_name, last_name, birth_date, email, phone, line_id, thai_id FROM users WHERE id = ?");
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

<!-- === Main structure for the multi-step form === -->
<div id="multi-step-form-container">
    <h2 class="text-3xl font-extrabold mb-6 text-gray-800">สมัคร: <?= e($event['name']) ?></h2>
    
    <!-- Progress Bar -->
    <div id="progress-bar-container" class="mb-8"></div>
    
    <!-- Form Content Area -->
    <form id="registration-form" onsubmit="return false;">
        <div id="form-step-content">
            <!-- Content for each step will be rendered here by JavaScript -->
        </div>
    </form>
    
    <!-- Navigation Buttons -->
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
let registrationData = {};

// Data from PHP
const currentEvent = <?= json_encode($event, JSON_UNESCAPED_UNICODE) ?>;
const distances = <?= json_encode($distances_result, JSON_UNESCAPED_UNICODE) ?>;
const eventCode = '<?= e($event_code) ?>';
const loggedInRunner = <?= $logged_in_runner_data ? json_encode($logged_in_runner_data, JSON_UNESCAPED_UNICODE) : 'null' ?>;
const masterTitles = <?= json_encode($master_titles, JSON_UNESCAPED_UNICODE) ?>;
const masterShirtSizes = <?= json_encode($master_shirt_sizes, JSON_UNESCAPED_UNICODE) ?>;


// --- Message Box Functions ---
function showMessage(title, text) { 
    const box = document.getElementById('message-box');
    if (box) {
        document.getElementById('message-title').textContent = title;
        document.getElementById('message-text').textContent = text;
        box.classList.remove('hidden');
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
        diseaseRadios.forEach(radio => radio.addEventListener('change', handleDiseaseChange));

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
        }
    }
}

function handleDiseaseChange(event) { 
    const diseaseDetailContainer = document.getElementById('disease-detail-container');
    const diseaseDetailTextarea = document.getElementById('disease_detail');
    if (event.target.value === 'มีโรคประจำตัว') {
        diseaseDetailContainer.classList.remove('hidden');
        diseaseDetailTextarea.required = true;
    } else {
        diseaseDetailContainer.classList.add('hidden');
        diseaseDetailTextarea.required = false;
        diseaseDetailTextarea.value = '';
    }
}

// Renders the content for the current step
function renderCurrentStep() {
    renderProgressBar();
    const content = document.getElementById('form-step-content');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    content.innerHTML = '';
    
    prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
    nextBtn.innerHTML = currentStep === totalSteps 
        ? `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร` 
        : `ถัดไป <i class="fa-solid fa-chevron-right ml-2"></i>`;

    if (currentStep === 1) {
        content.innerHTML = `
            <div class="space-y-4">
                <h3 class="text-xl font-semibold mb-4">1. เลือกระยะทางการแข่งขัน</h3>
                ${distances.map(d => `
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer transition duration-200 hover:border-primary">
                        <input type="radio" name="distance_id" value="${d.id}" class="h-5 w-5 text-primary focus:ring-primary" 
                               ${registrationData.distance_id == d.id ? 'checked' : ''}>
                        <div class="ml-4 flex justify-between w-full items-center">
                            <span class="font-medium text-gray-900 text-lg">${d.name} (${d.category})</span>
                            <span class="font-bold text-primary text-lg">${parseFloat(d.price).toLocaleString('th-TH')} บาท</span>
                        </div>
                    </label>
                `).join('')}
            </div>
        `;
    } else if (currentStep === 2) {
        const selectedDistance = distances.find(d => d.id == registrationData.distance_id);
        const userInfo = loggedInRunner || registrationData.userInfo || {}; 

        // Generate options for select inputs from master data
        const titleOptions = masterTitles.map(t => `<option value="${t.name}" ${userInfo.title === t.name ? 'selected' : ''}>${t.name}</option>`).join('');
        const shirtSizeOptions = masterShirtSizes.map(s => `<option value="${s.name}" ${registrationData.shirt_size === s.name ? 'selected' : ''}>${s.name} ${s.description || ''}</option>`).join('');

        content.innerHTML = `
            <h3 class="text-xl font-semibold mb-4">2. ข้อมูลส่วนตัวและเสื้อ (ระยะทาง: ${selectedDistance.name})</h3>
            <div class="p-6 bg-white rounded-xl border border-gray-200 space-y-4">
                
                ${loggedInRunner ? 
                    `<div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-md">
                        <p class="font-bold"><i class="fa fa-check-circle mr-2"></i>เข้าสู่ระบบในชื่อ ${loggedInRunner.first_name}</p>
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
                        <input type="text" id="first_name" name="first_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${userInfo.first_name || ''}">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" id="last_name" name="last_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${userInfo.last_name || ''}">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">วัน/เดือน/ปีเกิด <span class="text-red-500">*</span></label>
                        <input type="date" id="birth_date" name="birth_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${userInfo.birth_date || ''}">
                    </div>
                    <div>
                        <label for="thai_id" class="block text-sm font-medium text-gray-700 mb-1">หมายเลขบัตรประชาชน <span class="text-red-500">*</span></label>
                        <input type="text" id="thai_id" name="thai_id" required pattern="\\d{13}" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${userInfo.thai_id || ''}">
                        <p id="thai-id-error" class="text-xs text-red-500 mt-1 hidden">รูปแบบหมายเลขบัตรประชาชนไม่ถูกต้อง</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${userInfo.email || ''}">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                        <input type="tel" id="phone" name="phone" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="${userInfo.phone || ''}">
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="border-t pt-4">
                    <h4 class="text-md font-semibold text-gray-800 mb-2">ข้อมูลผู้ติดต่อฉุกเฉิน</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ติดต่อ <span class="text-red-500">*</span></label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ติดต่อ <span class="text-red-500">*</span></label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- Medical Info -->
                <div class="p-4 rounded-md bg-red-50 border border-red-200">
                    <label class="block text-sm font-medium text-red-600 mb-2">ข้อมูลทางการแพทย์ (สำคัญ)</label>
                    <div class="flex items-center space-x-6">
                         <label class="flex items-center">
                            <input type="radio" name="disease" value="ไม่มีโรคประจำตัว" class="form-radio h-4 w-4 text-primary" checked>
                            <span class="ml-2 text-gray-700">ไม่มีโรคประจำตัว</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="disease" value="มีโรคประจำตัว" class="form-radio h-4 w-4 text-primary">
                            <span class="ml-2 text-gray-700">มีโรคประจำตัว</span>
                        </label>
                    </div>
                     <div id="disease-detail-container" class="mt-4 hidden">
                         <label for="disease_detail" class="block text-sm font-medium text-gray-700 mb-1">โปรดระบุ:</label>
                         <textarea id="disease_detail" name="disease_detail" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm"></textarea>
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
    } else if (currentStep === 3) {
        const selectedDistance = distances.find(d => d.id == registrationData.distance_id);
        const userInfo = registrationData.userInfo;
        content.innerHTML = `
             <h3 class="text-xl font-semibold mb-4">3. สรุปข้อมูลการสมัคร</h3>
             <div class="p-6 bg-gray-50 rounded-xl border border-gray-200 mb-6 space-y-2 text-gray-800">
                <p><strong>ชื่อ-สกุล:</strong> ${userInfo.title} ${userInfo.first_name} ${userInfo.last_name}</p>
                <p><strong>อีเมล:</strong> ${userInfo.email}</p>
                <p><strong>โทรศัพท์:</strong> ${userInfo.phone}</p>
                <hr class="my-2">
                <p><strong>ระยะทาง:</strong> ${selectedDistance.name}</p>
                <p><strong>ขนาดเสื้อ:</strong> ${registrationData.shirt_size}</p>
                <p><strong>ข้อมูลสุขภาพ:</strong> ${userInfo.disease === 'มีโรคประจำตัว' ? userInfo.disease_detail : 'ไม่มีโรคประจำตัว'}</p>
                 <p><strong>ผู้ติดต่อฉุกเฉิน:</strong> ${userInfo.emergency_contact_name} (${userInfo.emergency_contact_phone})</p>
            </div>
            
            <h3 class="text-xl font-semibold my-4"><i class="fa-solid fa-money-check-dollar mr-2"></i> การชำระเงิน</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <div class="bg-white p-4 rounded-lg border text-center shadow-sm">
                    <p class="font-semibold text-gray-700 mb-2">สแกน QR Code เพื่อชำระเงิน</p>
                    <img src="${currentEvent.payment_qr_code_url}" alt="Payment QR Code" class="mx-auto my-2 rounded-md shadow-sm w-40 h-40">
                </div>
                <div class="space-y-4">
                    <div class="bg-white p-4 rounded-lg border shadow-sm">
                        <label for="payment_slip" class="block text-sm font-medium text-gray-700 mb-2">อัปโหลดหลักฐาน <span class="text-red-500">*</span></label>
                        <input type="file" id="payment_slip" name="payment_slip" required accept="image/jpeg,image/png,application/pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-100 hover:file:bg-gray-200">
                    </div>
                     <div class="bg-primary text-white p-4 rounded-lg text-center shadow-lg">
                        <p class="text-lg">ยอดชำระเงินทั้งหมด</p>
                        <p class="text-4xl font-extrabold">${parseFloat(selectedDistance.price).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} บาท</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    attachEventListeners();
}

// --- 3. Navigation and Validation ---

function validateThaiID(id) {
    if (!/^\d{13}$/.test(id)) return false;
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        sum += parseInt(id.charAt(i)) * (13 - i);
    }
    const checkDigit = (11 - (sum % 11)) % 10;
    return parseInt(id.charAt(12)) === checkDigit;
}

function nextStep() {
    if (currentStep === 1) {
        const selectedDistance = document.querySelector('input[name="distance_id"]:checked');
        if (!selectedDistance) {
            showMessage('ข้อมูลไม่ครบถ้วน', 'กรุณาเลือกระยะทางการแข่งขัน');
            return;
        }
        registrationData.distance_id = selectedDistance.value;
    } else if (currentStep === 2) {
        const form = document.getElementById('registration-form');
        const requiredFields = ['first_name', 'last_name', 'birth_date', 'thai_id', 'email', 'phone', 'emergency_contact_name', 'emergency_contact_phone', 'shirt_size'];
        
        let allValid = true;
        for(const fieldName of requiredFields) {
            const field = form.elements[fieldName];
            if(!field || !field.value.trim()) {
                allValid = false;
                break;
            }
        }

        if (!allValid) {
            showMessage('ข้อมูลไม่ครบถ้วน', 'กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบทุกช่อง');
            return;
        }

        if (!validateThaiID(form.elements.thai_id.value)) {
            showMessage('ข้อมูลไม่ถูกต้อง', 'รูปแบบหมายเลขบัตรประชาชนไม่ถูกต้อง');
            return;
        }

        const formData = new FormData(form);
        registrationData.userInfo = Object.fromEntries(formData.entries());
        registrationData.shirt_size = form.elements.shirt_size.value;
    }

    if (currentStep < totalSteps) {
        currentStep++;
        renderCurrentStep();
    } else {
        completeRegistration();
    }
}

function prevStep() { 
    if (currentStep > 1) {
        currentStep--;
        renderCurrentStep();
    }
}

// --- 4. Final Submission ---
function completeRegistration() {
    const slipFile = document.getElementById('payment_slip').files[0];
    if (!slipFile) {
        showMessage('ข้อมูลไม่ครบถ้วน', 'กรุณาอัปโหลดหลักฐานการชำระเงิน');
        return;
    }

    const finalFormData = new FormData();
    finalFormData.append('event_id', currentEvent.id);
    finalFormData.append('event_code', eventCode);
    finalFormData.append('distance_id', registrationData.distance_id);
    finalFormData.append('shirt_size', registrationData.shirt_size);
    finalFormData.append('payment_slip', slipFile);

    for (const key in registrationData.userInfo) {
        finalFormData.append(key, registrationData.userInfo[key]);
    }
    
    const nextBtn = document.getElementById('next-btn');
    nextBtn.disabled = true;
    nextBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i> กำลังส่งข้อมูล...`;

    fetch('actions/process_registration.php', {
        method: 'POST',
        body: finalFormData
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if(data.success) {
            window.location.href = data.redirect_url;
        } else {
            showMessage('เกิดข้อผิดพลาด', data.message || 'ไม่สามารถบันทึกข้อมูลได้');
            nextBtn.disabled = false;
            nextBtn.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('การเชื่อมต่อล้มเหลว', 'ไม่สามารถส่งข้อมูลไปยังเซิร์ฟเวอร์ได้');
        nextBtn.disabled = false;
        nextBtn.innerHTML = `<i class="fa-solid fa-check-circle mr-2"></i> ยืนยันการสมัคร`;
    });
}

// --- Initial Render ---
document.addEventListener('DOMContentLoaded', () => {
    renderCurrentStep();
});
</script>

