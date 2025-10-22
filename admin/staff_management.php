<?php
// admin/staff_management.php - หน้าจัดการบัญชีเจ้าหน้าที่ (สำหรับ Super Admin)

// --- CORE BOOTSTRAP ---
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');

// Security Check: Super Admin Only
if (!$is_super_admin) {
    header('Location: index.php');
    exit;
}
// --- END BOOTSTRAP ---

$page_title = 'จัดการบัญชีเจ้าหน้าที่';

// --- PAGINATION & SEARCH LOGIC ---
$limit = 5; // จำนวนรายการต่อหน้า
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- Fetch Data ---
$where_clause = '';
$params = [];
$types = '';

if (!empty($search_term)) {
    $where_clause = " WHERE s.username LIKE ? OR s.full_name LIKE ?";
    $like_term = "%{$search_term}%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types = 'ss';
}

// 1. Count total records for pagination
$count_stmt = $mysqli->prepare("SELECT COUNT(*) FROM staff s" . $where_clause);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// 2. Fetch staff accounts for the current page
$staff_query = "SELECT s.*, e.name as event_name 
                FROM staff s 
                LEFT JOIN events e ON s.assigned_event_id = e.id " 
               . $where_clause . " ORDER BY s.role, s.username LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$staff_stmt = $mysqli->prepare($staff_query);
$staff_stmt->bind_param($types, ...$params);
$staff_stmt->execute();
$all_staff = $staff_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$staff_stmt->close();

// 3. All Events (for assignment dropdown)
$all_events = $mysqli->query("SELECT id, name FROM events ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Check for session messages
$success_message = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : null; unset($_SESSION['update_success']);
$error_message = isset($_SESSION['update_error']) ? $_SESSION['update_error'] : null; unset($_SESSION['update_error']);

include 'partials/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">จัดการบัญชีเจ้าหน้าที่</h1>
        <p class="text-gray-600">เพิ่ม, แก้ไข, และลบบัญชีผู้ดูแลและเจ้าหน้าที่</p>
    </div>
</div>

<?php if ($success_message): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($error_message) ?></p></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Staff List -->
    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">รายชื่อบัญชีทั้งหมด</h2>
            <form action="staff_management.php" method="GET" class="flex items-center">
                <input type="text" name="search" value="<?= e($search_term) ?>" placeholder="ค้นหา Username หรือชื่อ..." class="border border-gray-300 rounded-l-lg p-2 text-sm">
                <button type="submit" class="bg-gray-200 hover:bg-gray-300 p-2 rounded-r-lg text-gray-600"><i class="fa fa-search"></i></button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อเต็ม</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">สิทธิ์</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">กิจกรรมที่ดูแล</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($all_staff)): ?>
                        <tr><td colspan="5" class="text-center py-6 text-gray-500">ไม่พบข้อมูลบัญชีที่ตรงกับเงื่อนไข</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_staff as $staff_member): ?>
                        <tr>
                            <td class="px-3 py-4 font-mono text-sm"><?= e($staff_member['username']) ?></td>
                            <td class="px-3 py-4 text-sm"><?= e($staff_member['full_name']) ?></td>
                            <td class="px-3 py-4 text-sm font-semibold <?= $staff_member['role'] == 'admin' ? 'text-red-600' : 'text-gray-700' ?>"><?= ucfirst($staff_member['role']) ?></td>
                            <td class="px-3 py-4 text-sm text-gray-500">
                                <?= e($staff_member['event_name'] ?? '-') ?>
                            </td>
                            <td class="px-3 py-4 text-right">
                                 <div class="flex items-center justify-end space-x-4">
                                    <?php if (isset($staff_member['id'])): ?>
                                        <button type="button" onclick='editStaff(<?= json_encode($staff_member, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="text-blue-500 hover:text-blue-700" title="แก้ไขข้อมูล">
                                            <i class="fa-solid fa-pencil-alt"></i>
                                        </button>
                                        <form action="../actions/manage_staff.php" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีนี้?');" class="inline-block">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="staff_id" value="<?= e($staff_member['id']) ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed" <?= ($staff_member['id'] == $_SESSION['staff_id']) ? 'disabled' : '' ?> title="ลบบัญชี">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="mt-4 flex justify-between items-center text-sm">
             <p class="text-gray-600">
                แสดงผล <span class="font-medium"><?= count($all_staff) ?></span> จากทั้งหมด <span class="font-medium"><?= $total_records ?></span> รายการ
            </p>
            <?php if ($total_pages > 1): ?>
            <div class="flex items-center space-x-1">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search_term) ?>" 
                       class="px-3 py-1 rounded-md <?= $i == $page ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right: Add/Edit Form -->
    <div id="form-container" class="bg-white p-6 rounded-xl shadow-md">
        <div class="flex justify-between items-start">
            <h2 id="form-title" class="text-xl font-bold mb-4">เพิ่มบัญชีใหม่</h2>
            <button id="cancel-edit-btn" onclick="resetForm()" class="hidden text-sm text-gray-500 hover:text-gray-700">&times; ยกเลิก</button>
        </div>
        <form id="staff-form" action="../actions/manage_staff.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="staff_id" id="staff-id-input" value="">
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username-input" name="username" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
            </div>
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">ชื่อเต็ม</label>
                <input type="text" id="full-name-input" name="full_name" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
                <input type="password" id="password-input" name="password" required class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2" placeholder="อย่างน้อย 6 ตัวอักษร">
                <p id="password-helper" class="text-xs text-gray-500 mt-1"></p>
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">ระดับสิทธิ์</label>
                <select id="role-input" name="role" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <label for="assigned_event_id" class="block text-sm font-medium text-gray-700">มอบหมายให้ดูแลกิจกรรม</label>
                <select id="assigned-event-input" name="assigned_event_id" class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-2">
                    <option value="">-- ไม่กำหนด --</option>
                    <?php foreach ($all_events as $event): ?>
                        <option value="<?= e($event['id']) ?>"><?= e($event['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="submit-btn" type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg">
                <i class="fa-solid fa-plus-circle mr-2"></i> สร้างบัญชี
            </button>
        </form>
    </div>
</div>

<script>
    const formTitle = document.getElementById('form-title');
    const staffForm = document.getElementById('staff-form');
    const formAction = document.getElementById('form-action');
    const staffIdInput = document.getElementById('staff-id-input');
    const usernameInput = document.getElementById('username-input');
    const fullNameInput = document.getElementById('full-name-input');
    const passwordInput = document.getElementById('password-input');
    const passwordHelper = document.getElementById('password-helper');
    const roleInput = document.getElementById('role-input');
    const assignedEventInput = document.getElementById('assigned-event-input');
    const submitBtn = document.getElementById('submit-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const currentStaffId = <?= $_SESSION['staff_id'] ?>;

    function editStaff(staffData) {
        formTitle.textContent = 'แก้ไขข้อมูลบัญชี';
        formAction.value = 'update';
        staffIdInput.value = staffData.id;
        
        usernameInput.value = staffData.username;
        usernameInput.disabled = true;
        usernameInput.classList.add('bg-gray-100', 'cursor-not-allowed');

        fullNameInput.value = staffData.full_name;
        
        passwordInput.placeholder = 'ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน';
        passwordInput.required = false;
        passwordHelper.textContent = 'กรอกรหัสผ่านใหม่เพื่อทำการเปลี่ยนแปลง';

        roleInput.value = staffData.role;
        if (staffData.id == currentStaffId) {
            roleInput.disabled = true;
            roleInput.classList.add('bg-gray-100', 'cursor-not-allowed');
        } else {
            roleInput.disabled = false;
            roleInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }

        assignedEventInput.value = staffData.assigned_event_id || '';
        
        submitBtn.innerHTML = '<i class="fa-solid fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง';
        cancelBtn.classList.remove('hidden');

        document.getElementById('form-container').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        formTitle.textContent = 'เพิ่มบัญชีใหม่';
        staffForm.reset();
        
        formAction.value = 'create';
        staffIdInput.value = '';
        
        usernameInput.disabled = false;
        usernameInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        
        passwordInput.placeholder = 'อย่างน้อย 6 ตัวอักษร';
        passwordInput.required = true;
        passwordHelper.textContent = '';
        
        roleInput.disabled = false;
        roleInput.classList.remove('bg-gray-100', 'cursor-not-allowed');

        submitBtn.innerHTML = '<i class="fa-solid fa-plus-circle mr-2"></i> สร้างบัญชี';
        cancelBtn.classList.add('hidden');
    }
</script>

<?php
include 'partials/footer.php';
?>
