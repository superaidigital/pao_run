<?php
// pages/dashboard.php
// Runner's dashboard to check status, log in, or view registration history.

$page_title = 'สำหรับผู้สมัคร';

// --- 1. Check for Session Messages ---
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
$last_reg_code = $_SESSION['last_registration_code'] ?? null;

// --- Fetch search results from Session ---
$search_result = $_SESSION['search_result'] ?? null;
$search_error = $_SESSION['search_error'] ?? null;

// --- 2. Clear all Session Messages ---
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['last_registration_code']);
unset($_SESSION['search_result'], $_SESSION['search_error']);

// --- Check Login Status ---
$is_runner_logged_in = isset($_SESSION['user_id']);
$runner_info = $is_runner_logged_in ? $_SESSION['user_info'] : null;

// --- If logged in, fetch all associated registrations ---
$user_registrations = [];
if ($is_runner_logged_in) {
    $user_id = $_SESSION['user_id'];
    // Ensure `r.id` is selected for the E-BIB link
    $stmt = $mysqli->prepare("
        SELECT 
            r.id, r.registration_code, r.status, r.bib_number, r.shirt_size,
            e.name AS event_name, e.event_code,
            d.name AS distance_name,
            rc.name AS category_name
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        JOIN distances d ON r.distance_id = d.id
        LEFT JOIN race_categories rc ON r.race_category_id = rc.id
        WHERE r.user_id = ?
        ORDER BY r.registered_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="space-y-8">

    <?php if ($is_runner_logged_in): ?>
        <!-- === LOGGED-IN DASHBOARD VIEW === -->
        <h2 class="text-3xl font-extrabold text-gray-800">แดชบอร์ดของฉัน</h2>
        
        <div class="p-6 bg-white rounded-xl border-l-4 border-primary shadow-lg flex justify-between items-center">
             <div>
                <p class="text-xl font-bold text-gray-900">สวัสดีคุณ, <?= e($runner_info['first_name']) ?> <?= e($runner_info['last_name']) ?></p>
                <p class="text-sm text-gray-600">นี่คือรายการสมัครทั้งหมดของคุณในระบบ</p>
             </div>
             <a href="index.php?page=edit_profile" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm">
                <i class="fa-solid fa-user-edit mr-2"></i> แก้ไขโปรไฟล์
             </a>
        </div>

        <h3 class="text-2xl font-bold text-gray-800 border-b pb-2">ประวัติการสมัคร</h3>

        <?php if (empty($user_registrations)): ?>
            <div class="text-center p-10 border-2 border-dashed border-gray-300 rounded-lg text-gray-500">
                <i class="fa-solid fa-folder-open text-3xl mb-3"></i>
                <p>คุณยังไม่มีรายการสมัครใดๆ</p>
                <a href="index.php?page=home" class="mt-4 inline-block text-primary hover:underline font-medium">
                    ค้นหากิจกรรมวิ่ง
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
            <?php foreach ($user_registrations as $reg): 
                $status_color = 'gray';
                if ($reg['status'] == 'ชำระเงินแล้ว') $status_color = 'green';
                if ($reg['status'] == 'รอตรวจสอบ') $status_color = 'yellow';
                if ($reg['status'] == 'รอชำระเงิน') $status_color = 'red';
            ?>
                <!-- Registration Card -->
                <div class="p-5 rounded-xl shadow-md border-l-4 border-<?= e($status_color) ?>-500 bg-white transition duration-300 hover:shadow-lg">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-3 pb-3 border-b">
                        <div>
                            <h4 class="text-xl font-bold text-gray-900"><?= e($reg['event_name']) ?></h4>
                            <p class="text-sm text-gray-600 mt-1">
                                ระยะทาง: <?= e($reg['distance_name']) ?> | 
                                รุ่น: <?= e($reg['category_name'] ?? 'ไม่จำกัดรุ่น') ?> | 
                                เสื้อ: <?= e($reg['shirt_size'] ?? '-') ?> | 
                                BIB: <span class="font-mono"><?= e($reg['bib_number'] ?? 'N/A') ?></span>
                            </p>
                        </div>
                        <span class="mt-2 md:mt-0 py-1 px-3 rounded-full text-xs font-semibold bg-<?= e($status_color) ?>-100 text-<?= e($status_color) ?>-800">
                            <?= e($reg['status']) ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="font-mono text-gray-500">ID: <?= e($reg['registration_code']) ?></span>
                        <div class="flex items-center gap-3">
                            <?php if ($reg['status'] == 'ชำระเงินแล้ว' && !empty($reg['bib_number'])): ?>
                            <a href="index.php?page=ebib&reg_id=<?= e($reg['id']) ?>" class="bg-blue-500 text-white font-semibold py-1 px-3 rounded-md text-xs hover:bg-blue-600 transition">
                                <i class="fa-solid fa-download mr-1"></i> E-BIB
                            </a>
                            <?php endif; ?>
                            <a href="index.php?page=microsite&event_code=<?= e($reg['event_code']) ?>" class="text-primary hover:underline font-semibold">ดูรายละเอียดกิจกรรม <i class="fa-solid fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- === GUEST VIEW (LOGIN/CHECK STATUS) === -->
        
        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-md shadow-sm" role="alert">
                <p class="font-bold"><i class="fa-solid fa-check-circle mr-2"></i>สำเร็จ!</p>
                <p><?= e($success_message) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-md shadow-sm" role="alert">
                <p class="font-bold"><i class="fa-solid fa-exclamation-triangle mr-2"></i>เกิดข้อผิดพลาด</p>
                <p><?= e($error_message) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($search_result): ?>
        <div class="p-6 bg-white rounded-xl border-2 border-primary shadow-lg animate-fade-in">
            <h2 class="text-2xl font-bold mb-4 text-primary"><i class="fa-solid fa-id-card mr-2"></i> ผลการค้นหา</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- E-Bib Mockup -->
                <div class="md:col-span-2 e-bib-mockup p-4 rounded-lg text-white" style="--color-primary: <?= e($search_result['color_code'] ?? '#4f46e5') ?>;">
                     <p class="text-sm font-light"><?= e($search_result['event_name']) ?></p>
                     <h5 class="text-5xl font-extrabold my-2"><?= e($search_result['bib_number'] ?? 'XXX') ?></h5>
                     <p class="text-xl font-semibold"><?= e($search_result['title']) . e($search_result['first_name']) . ' ' . e($search_result['last_name']) ?></p>
                     <div class="flex justify-between text-sm mt-2 pt-2 border-t border-white border-opacity-30">
                         <span>ไซส์เสื้อ: <?= e($search_result['shirt_size']) ?> | รุ่น: <?= e($search_result['category_name'] ?? 'ไม่จำกัดรุ่น') ?></span>
                         <span class="font-bold"><?= e($search_result['distance_name']) ?></span>
                     </div>
                </div>
                <!-- Status and Details -->
                <div class="space-y-3 flex flex-col justify-between">
                     <div class="p-3 rounded-lg text-center 
                        <?php 
                            switch ($search_result['status']) {
                                case 'ชำระเงินแล้ว': echo 'bg-green-100 text-green-800'; break;
                                case 'รอตรวจสอบ': echo 'bg-yellow-100 text-yellow-800'; break;
                                default: echo 'bg-red-100 text-red-800'; break;
                            }
                        ?>">
                        <p class="font-bold text-lg"><?= e($search_result['status']) ?></p>
                     </div>
                     <div class="text-sm text-gray-700 space-y-2 bg-gray-50 p-3 rounded-md border">
                         <p><strong>รหัสการสมัคร:</strong><br><span class="font-mono"><?= e($search_result['registration_code']) ?></span></p>
                         <p><strong>อีเมล:</strong><br><?= e(mask_email($search_result['email'])) ?></p>
                         <p><strong>เบอร์โทรศัพท์:</strong><br><?= e(mask_phone($search_result['phone'])) ?></p>
                     </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
         <?php if ($search_error): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded-md shadow-sm" role="alert">
                <p><i class="fa-solid fa-magnifying-glass mr-2"></i><?= e($search_error) ?></p>
            </div>
        <?php endif; ?>

        <!-- Section: Check Registration Status -->
        <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
            <h2 class="text-2xl font-bold mb-4 text-primary"><i class="fa-solid fa-search mr-2"></i> ตรวจสอบสถานะการสมัคร</h2>
            <form action="actions/check_status.php" method="POST" class="space-y-4">
                <div>
                    <label for="search_query" class="block text-sm font-medium text-gray-700 mb-1">กรอกเลขบัตรประชาชน หรือ รหัสการสมัคร</label>
                    <input type="text" id="search_query" name="search_query" required class="w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary" placeholder="เช่น 1234567890123 หรือ RUN2025-XXXX">
                </div>
                <div class="text-right">
                    <button type="submit" class="bg-primary text-white font-bold py-2 px-6 rounded-lg hover:opacity-90 transition">ตรวจสอบสถานะ</button>
                </div>
            </form>
        </div>

        <!-- Section: Login / Register -->
        <div class="p-6 bg-gray-50 rounded-xl border border-gray-200">
            <h2 class="text-2xl font-bold mb-4 text-gray-800"><i class="fa-solid fa-sign-in-alt mr-2"></i> เข้าสู่ระบบ / สมัครสมาชิก</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <form action="actions/runner_login.php" method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                        <input type="email" id="email" name="email" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                     <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                        <input type="password" id="password" name="password" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex items-center justify-between">
                         <a href="index.php?page=forgot_password" class="text-sm text-primary hover:underline">ลืมรหัสผ่าน?</a>
                         <button type="submit" class="bg-gray-700 text-white font-bold py-2 px-5 rounded-lg hover:bg-gray-800 transition">เข้าสู่ระบบ</button>
                    </div>
                </form>
                <div class="flex flex-col items-center justify-center bg-green-50 text-green-800 p-6 rounded-lg text-center">
                    <i class="fa-solid fa-user-plus text-3xl mb-3"></i>
                    <h3 class="font-bold mb-2">ยังไม่เคยสมัครสมาชิก?</h3>
                    <a href="index.php?page=register_member" class="bg-green-600 w-full text-white font-bold py-2 px-5 rounded-lg hover:bg-green-700 transition">สมัครสมาชิกใหม่</a>
                </div>
            </div>
        </div>

    <?php endif; // ปิด if ($is_runner_logged_in) ?>

</div>
