<?php
// actions/process_registration.php
// Script to process data from the multi-step registration form (Fetch API version)

// --- DEBUGGING: Force display of errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

// --- 1. Load necessary files and set headers ---
require_once '../config.php';
require_once '../functions.php';

// Set the header to respond with JSON and support Thai characters
header('Content-Type: application/json; charset=utf-8');

// Function to send a JSON response and terminate the script
function json_response($success, $message, $redirect_url = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($redirect_url) {
        $response['redirect_url'] = $redirect_url;
    }
    // Ensure UTF-8 characters are encoded correctly for the JSON response
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Check for mysqli connection object ---
if (!isset($mysqli) || $mysqli->connect_error) {
    error_log("MySQLi connection error in process_registration.php: " . ($mysqli->connect_error ?? "mysqli object not found"));
    json_response(false, 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ');
}

// --- 2. Verify that the request is a POST request ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(false, 'Invalid request method.');
}

// --- 3. Retrieve and sanitize form data ---
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
$distance_id = isset($_POST['distance_id']) ? intval($_POST['distance_id']) : 0;
$shirt_size = isset($_POST['shirt_size']) ? e($_POST['shirt_size']) : '';
$title = isset($_POST['title']) ? e($_POST['title']) : '';
$first_name = isset($_POST['first_name']) ? e($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? e($_POST['last_name']) : '';
$thai_id = isset($_POST['thai_id']) ? e($_POST['thai_id']) : '';
$email = isset($_POST['email']) ? e($_POST['email']) : '';
$phone = isset($_POST['phone']) ? e($_POST['phone']) : '';
$line_id = isset($_POST['line_id']) ? e($_POST['line_id']) : null;
$disease = isset($_POST['disease']) ? e($_POST['disease']) : 'ไม่มีโรคประจำตัว';
$disease_detail = ($disease === 'มีโรคประจำตัว' && isset($_POST['disease_detail'])) ? e($_POST['disease_detail']) : null;

// Check if a user is logged in
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;


// --- 4. Basic Data Validation ---
if (empty($event_id) || empty($distance_id) || empty($shirt_size) || empty($first_name) || empty($last_name) || empty($thai_id) || empty($email) || empty($phone) || !isset($_FILES['payment_slip'])) {
    json_response(false, 'ข้อมูลที่จำเป็นไม่ครบถ้วน กรุณากรอกข้อมูลให้สมบูรณ์');
}

// --- Backend Validation ---
if (!validateThaiID($thai_id)) {
    json_response(false, 'รูปแบบหมายเลขบัตรประชาชนไม่ถูกต้อง');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'รูปแบบอีเมลไม่ถูกต้อง');
}

// --- Check for Duplicate Registration ---
try {
    $stmt_check = $mysqli->prepare("SELECT id FROM registrations WHERE event_id = ? AND thai_id = ?");
    if ($stmt_check === false) throw new Exception("Prepare statement failed for duplicate check.");
    $stmt_check->bind_param("is", $event_id, $thai_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        json_response(false, 'หมายเลขบัตรประชาชนนี้ได้ลงทะเบียนเข้าร่วมกิจกรรมนี้ไปแล้ว');
    }
    $stmt_check->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    json_response(false, "เกิดข้อผิดพลาดในการตรวจสอบข้อมูลซ้ำ");
}


// --- 5. Handle Payment Slip Upload ---
$payment_slip_url = null;
if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {
    $upload_dir = '../uploads/';
    
    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
        json_response(false, 'เกิดข้อผิดพลาดจากฝั่งเซิร์ฟเวอร์: ไม่สามารถเข้าถึงโฟลเดอร์สำหรับอัปโหลดได้');
    }

    $file = $_FILES['payment_slip'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (in_array($file_ext, $allowed_exts) && $file['size'] <= 5 * 1024 * 1024) { // 5 MB
        $new_filename = 'slip_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $payment_slip_url = 'uploads/' . $new_filename;
        } else {
             json_response(false, 'เกิดข้อผิดพลาด: ไม่สามารถบันทึกไฟล์สลิปได้');
        }
    } else {
         json_response(false, 'ไฟล์สลิปไม่ถูกต้อง (ต้องเป็น JPG, PNG, PDF และขนาดไม่เกิน 5MB)');
    }
} else {
    json_response(false, 'กรุณาอัปโหลดหลักฐานการชำระเงิน');
}

// --- 6. Save data to the database ---
try {
    // Generate a unique registration code
    $registration_code = 'RUN' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

    $stmt = $mysqli->prepare(
        "INSERT INTO registrations (registration_code, user_id, event_id, distance_id, shirt_size, title, first_name, last_name, thai_id, email, phone, line_id, disease, disease_detail, payment_slip_url, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'รอตรวจสอบ')"
    );

    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $mysqli->error);
    }

    // 's' for string, 'i' for integer. user_id can be null.
    $stmt->bind_param("siiisssssssssss", 
        $registration_code,
        $user_id,
        $event_id,
        $distance_id,
        $shirt_size,
        $title,
        $first_name,
        $last_name,
        $thai_id,
        $email,
        $phone,
        $line_id,
        $disease,
        $disease_detail,
        $payment_slip_url
    );

    if ($stmt->execute()) {
        // Success: Set session messages and send a success JSON response
        $_SESSION['success_message'] = "การสมัครของคุณเสร็จสมบูรณ์!";
        $_SESSION['last_registration_code'] = $registration_code;
        
        json_response(true, 'Registration successful!', '../index.php?page=dashboard');

    } else {
        throw new Exception("Database execution failed: " . $stmt->error);
    }
    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    json_response(false, "เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง");
}
?>

