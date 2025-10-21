<?php
// ไฟล์: pages/admin_event_categories.php
// หน้าที่: จัดการรุ่นการแข่งขันของแต่ละ Event

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) { header('Location: index.php?page=admin'); exit(); }
if (!isset($_GET['event_id'])) { header('Location: index.php?page=admin_events'); exit(); }
$event_id = $_GET['event_id'];

// ดึงข้อมูล Event และ Categories
$event_stmt = $conn->prepare("SELECT name, distances FROM events WHERE id = ?");
$event_stmt->bind_param("s", $event_id);
$event_stmt->execute();
$event = $event_stmt->get_result()->fetch_assoc();
$distances = json_decode($event['distances'] ?? '[]', true);

$cat_stmt = $conn->prepare("SELECT * FROM race_categories WHERE eventId = ? ORDER BY distance, gender, minAge");
$cat_stmt->bind_param("s", $event_id);
$cat_stmt->execute();
$categories = $cat_stmt->get_result();
?>
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-3xl font-bold">จัดการรุ่นการแข่งขัน</h2>
        <p class="text-gray-600">สำหรับกิจกรรม: <?php echo htmlspecialchars($event['name']); ?></p>
    </div>
    <a href="index.php?page=admin_events" class="text-sm text-gray-600 hover:underline">&larr; กลับไปหน้ารายการกิจกรรม</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: Form for adding a new category -->
    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg border">
        <h3 class="text-xl font-semibold mb-4">เพิ่มรุ่นใหม่</h3>
        <form action="actions/admin_action.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_category">
            <input type="hidden" name="eventId" value="<?php echo htmlspecialchars($event_id); ?>">
            <div>
                <label for="distance">ระยะทาง</label>
                <select name="distance" id="distance" required class="mt-1 w-full p-2 border-gray-300 rounded-md">
                    <?php foreach($distances as $dist): ?>
                        <option value="<?php echo htmlspecialchars($dist['name']); ?>"><?php echo htmlspecialchars($dist['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="name">ชื่อรุ่น</label>
                <input type="text" name="name" id="name" required placeholder="เช่น ชาย 18-29 ปี" class="mt-1 w-full p-2 border-gray-300 rounded-md">
            </div>
             <div>
                <label for="gender">เพศ</label>
                <select name="gender" id="gender" required class="mt-1 w-full p-2 border-gray-300 rounded-md">
                    <option value="ชาย">ชาย</option>
                    <option value="หญิง">หญิง</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="minAge">อายุต่ำสุด</label>
                    <input type="number" name="minAge" id="minAge" required value="0" class="mt-1 w-full p-2 border-gray-300 rounded-md">
                </div>
                 <div>
                    <label for="maxAge">อายุสูงสุด</label>
                    <input type="number" name="maxAge" id="maxAge" required value="99" class="mt-1 w-full p-2 border-gray-300 rounded-md">
                </div>
            </div>
            <button type="submit" class="w-full py-2 px-4 rounded-lg bg-primary text-white font-bold hover:opacity-90">เพิ่มรุ่น</button>
        </form>
    </div>

    <!-- Right: List of existing categories -->
    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg border">
        <h3 class="text-xl font-semibold mb-4">รุ่นการแข่งขันทั้งหมด</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2">ระยะทาง</th>
                        <th class="p-2">ชื่อรุ่น</th>
                        <th class="p-2">เพศ</th>
                        <th class="p-2">กลุ่มอายุ</th>
                        <th class="p-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($categories->num_rows > 0): ?>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="p-2"><?php echo htmlspecialchars($cat['distance']); ?></td>
                        <td class="p-2 font-semibold"><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td class="p-2"><?php echo htmlspecialchars($cat['gender']); ?></td>
                        <td class="p-2"><?php echo $cat['minAge'] . '-' . $cat['maxAge']; ?> ปี</td>
                        <td class="p-2 text-center">
                            <a href="actions/admin_action.php?action=delete_category&id=<?php echo $cat['id']; ?>&event_id=<?php echo $event_id; ?>"
                               onclick="return confirm('ยืนยันการลบ?')" class="text-red-500 hover:text-red-700">
                                <i class="fa-solid fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center p-4 text-gray-500">ยังไม่มีการสร้างรุ่นการแข่งขัน</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
