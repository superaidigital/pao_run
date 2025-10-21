<?php
// admin/login.php - หน้าสำหรับเจ้าหน้าที่เข้าสู่ระบบ (เวอร์ชันปรับปรุง UI)
require_once '../config.php';
require_once '../functions.php';

// ถ้า login อยู่แล้ว ให้ redirect ไปหน้า dashboard เลย
if (isset($_SESSION['staff_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
unset($_SESSION['error_message']);

$page_title = 'Staff Login';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | SISAKET PAO RUN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen p-4">

    <div class="w-full max-w-md">
        <div class="bg-white shadow-2xl rounded-2xl px-8 pt-6 pb-8">
            <div class="mb-8 text-center">
                <i class="fa-solid fa-user-shield text-5xl text-red-500"></i>
                <h1 class="text-2xl font-bold text-gray-800 mt-3">Staff / Admin Login</h1>
                <p class="text-gray-500 text-sm">สำหรับเจ้าหน้าที่ผู้จัดงานเท่านั้น</p>
            </div>

            <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <span class="block sm:inline"><?= e($error_message) ?></span>
            </div>
            <?php endif; ?>

            <form action="../actions/staff_login.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username
                    </label>
                    <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-red-500" id="username" name="username" type="text" placeholder="e.g., admin" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-red-500" id="password" name="password" type="password" placeholder="******************" required>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <a class="inline-block align-baseline font-bold text-sm text-red-500 hover:text-red-700" href="#">
                        ลืมรหัสผ่าน?
                    </a>
                    <button class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300" type="submit">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
        <p class="text-center text-gray-500 text-xs mt-4">
            <a href="../index.php" class="hover:underline"><i class="fa-solid fa-arrow-left"></i> กลับสู่หน้าหลัก</a>
        </p>
    </div>

</body>
</html>

