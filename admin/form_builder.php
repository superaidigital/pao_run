<?php
// admin/form_builder.php - Dynamic Form Builder Interface

// --- CORE BOOTSTRAP ---
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
$page_title = 'จัดการฟอร์มสมัคร';
// --- END BOOTSTRAP ---

// --- Get Event ID and verify access ---
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    // Error handling
}
$event_id = intval($_GET['event_id']);

// --- Fetch existing form fields ---
$stmt = $mysqli->prepare("SELECT * FROM form_fields WHERE event_id = ? ORDER BY sort_order ASC");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$fields = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'partials/header.php';
?>

<h1 class="text-3xl font-bold text-gray-900 mb-6">จัดการฟอร์มสมัคร</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Form Preview / Builder Area -->
    <div class="md:col-span-2 bg-white p-6 rounded-xl shadow-md">
        <h2 class="text-xl font-bold mb-4">ตัวอย่างฟอร์ม</h2>
        <form action="../actions/save_form.php" method="POST">
            <input type="hidden" name="event_id" value="<?= e($event_id) ?>">
            <div id="form-builder-canvas" class="space-y-4 border p-4 rounded-lg min-h-[300px]">
                <?php foreach($fields as $field): ?>
                    <!-- Render existing fields -->
                <?php endforeach; ?>
            </div>
            <div class="mt-6 text-right">
                <button type="submit" class="bg-red-600 text-white font-bold py-2 px-6 rounded-lg">บันทึกโครงสร้างฟอร์ม</button>
            </div>
        </form>
    </div>

    <!-- Available Fields -->
    <div class="bg-white p-6 rounded-xl shadow-md">
        <h2 class="text-xl font-bold mb-4">เครื่องมือ</h2>
        <div id="field-toolbox" class="space-y-2">
            <div class="p-2 border rounded bg-gray-50 cursor-move" data-type="text">Text Input</div>
            <div class="p-2 border rounded bg-gray-50 cursor-move" data-type="email">Email Input</div>
            <div class="p-2 border rounded bg-gray-50 cursor-move" data-type="select">Dropdown Select</div>
            <!-- Add more field types here -->
        </div>
    </div>
</div>

<!-- Include SortableJS for drag-and-drop functionality -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('form-builder-canvas');
        new Sortable(canvas, {
            animation: 150,
            group: 'shared',
            ghostClass: 'bg-blue-100'
        });

        const toolbox = document.getElementById('field-toolbox');
        new Sortable(toolbox, {
            group: {
                name: 'shared',
                pull: 'clone',
                put: false
            },
            sort: false, // To prevent sorting in the toolbox
            onEnd: function (evt) {
                // When a new item is dropped into the canvas from the toolbox
                const itemEl = evt.item; // The dragged element
                // You would typically replace this with a more detailed settings modal
                const label = prompt("Enter a label for this field:", "New Field");
                if (label) {
                    itemEl.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700">${label}</label>
                        <input type="text" disabled class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2 bg-gray-100">
                        <input type="hidden" name="fields[][field_label]" value="${label}">
                        <input type="hidden" name="fields[][field_type]" value="${itemEl.dataset.type}">
                    `;
                } else {
                    itemEl.remove(); // Remove if the user cancels
                }
            }
        });
    });
</script>

<?php include 'partials/footer.php'; ?>
