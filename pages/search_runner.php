<?php
// pages/search_runner.php
// Public page for searching registered and paid runners.

$page_title = 'ค้นหานักวิ่ง';

// --- 1. Check for Session Messages (from the search action) ---
$search_results = $_SESSION['search_results_public'] ?? null;
$search_error = $_SESSION['search_error_public'] ?? null;
$last_query = $_SESSION['last_query_public'] ?? '';

// --- 2. Clear Session Messages ---
unset($_SESSION['search_results_public'], $_SESSION['search_error_public'], $_SESSION['last_query_public']);

?>

<div class="space-y-8">
    <!-- Header and Search Form -->
    <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
        <h2 class="text-3xl font-extrabold text-gray-800 mb-2 flex items-center gap-3">
            <i class="fa-solid fa-magnifying-glass text-primary"></i>
            ค้นหารายชื่อนักวิ่ง
        </h2>
        <p class="text-gray-600 mb-6">ค้นหาจาก ชื่อ-นามสกุล หรือ หมายเลข BIB ของนักวิ่งที่ชำระเงินแล้ว</p>
        
        <form action="actions/search_runner_public.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="flex items-center">
                <input type="text" name="search_query" value="<?= e($last_query) ?>" required class="w-full p-3 border border-gray-300 rounded-l-lg shadow-sm focus:ring-primary focus:border-primary" placeholder="เช่น สมชาย หรือ A1001">
                <button type="submit" class="bg-primary text-white font-bold py-3 px-6 rounded-r-lg hover:opacity-90 transition">
                    <i class="fa-solid fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Results Section -->
    <div id="search-results">
        <?php if ($search_error): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded-md shadow-sm" role="alert">
                <p><?= e($search_error) ?></p>
            </div>
        <?php endif; ?>

        <?php if (is_array($search_results) && !empty($search_results)): ?>
            <h3 class="text-xl font-bold text-gray-800 mb-4">ผลการค้นหาสำหรับ "<?= e($last_query) ?>" (<?= count($search_results) ?> รายการ)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($search_results as $runner): ?>
                <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm flex items-center gap-4">
                    <div class="flex-shrink-0 w-20 h-20 bg-gray-100 rounded-lg flex flex-col items-center justify-center text-center">
                        <span class="text-xs text-gray-500">BIB</span>
                        <span class="font-bold text-2xl text-primary"><?= e($runner['bib_number'] ?? 'N/A') ?></span>
                    </div>
                    <div>
                        <p class="font-bold text-lg text-gray-900"><?= e($runner['title'] . ' ' . $runner['first_name'] . ' ' . $runner['last_name']) ?></p>
                        <p class="text-sm text-gray-600"><?= e($runner['distance_name']) ?></p>
                        <p class="text-xs text-gray-500 mt-1">กิจกรรม: <?= e($runner['event_name']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (is_array($search_results) && empty($search_results)): ?>
             <div class="text-center p-10 border-2 border-dashed border-gray-300 rounded-lg text-gray-500">
                <p>ไม่พบข้อมูลนักวิ่งที่ตรงกับ "<?= e($last_query) ?>"</p>
            </div>
        <?php endif; ?>
    </div>
</div>
