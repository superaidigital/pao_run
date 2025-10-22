<?php
// actions/process_registration.php
// Script to process data from the multi-step registration form (Fetch API version with Age Calculation & Category Matching)

// --- 1. Load necessary files and set headers ---
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');

function json_response($success, $message, $redirect_url = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($redirect_url) {
        $response['redirect_url'] = $redirect_url;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($mysqli) || $mysqli->connect_error) {
    error_log("MySQLi connection error in process_registration.php: " . ($mysqli->connect_error ?? "mysqli object not found"));
    json_response(false, 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ');
}

// --- 2. Verify that the request is a POST request ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response(false, 'Invalid request method.');
}

// --- 3. Retrieve and sanitize form data ---
try {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $distance_id = isset($_POST['distance_id']) ? intval($_POST['distance_id']) : 0;
    $distance_name = isset($_POST['distance_name']) ? e($_POST['distance_name']) : '';
    $shirt_size = isset($_POST['shirt_size']) ? e($_POST['shirt_size']) : '';
    $title = isset($_POST['title']) ? e($_POST['title']) : '';
    $first_name = isset($_POST['first_name']) ? e($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? e($_POST['last_name']) : '';
    $gender = isset($_POST['gender']) ? e($_POST['gender']) : '';
    $birth_date = isset($_POST['birth_date']) ? e($_POST['birth_date']) : '';
    $thai_id = isset($_POST['thai_id']) ? e($_POST['thai_id']) : '';
    $email = isset($_POST['email']) ? e($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? e($_POST['phone']) : '';
    $line_id = isset($_POST['line_id']) ? e($_POST['line_id']) : null;
    $disease = isset($_POST['disease']) ? e($_POST['disease']) : 'ไม่มีโรคประจำตัว';
    $disease_detail = ($disease === 'มีโรคประจำตัว' && isset($_POST['disease_detail'])) ? e($_POST['disease_detail']) : null;
    $emergency_contact_name = isset($_POST['emergency_contact_name']) ? e($_POST['emergency_contact_name']) : null;
    $emergency_contact_phone = isset($_POST['emergency_contact_phone']) ? e($_POST['emergency_contact_phone']) : null;

    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

    if (empty($event_id) || empty($distance_id) || empty($shirt_size) || empty($first_name) || empty($last_name) || empty($gender) || empty($birth_date) || empty($thai_id) || empty($email) || empty($phone) || !isset($_FILES['payment_slip'])) {
        json_response(false, 'ข้อมูลที่จำเป็นไม่ครบถ้วน กรุณากรอกข้อมูลให้สมบูรณ์');
    }

    if (!validateThaiID($thai_id)) {
        json_response(false, 'รูปแบบหมายเลขบัตรประชาชนไม่ถูกต้อง');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'รูปแบบอีเมลไม่ถูกต้อง');
    }
    $birthDateObj = new DateTime($birth_date);

} catch (Exception $e) {
     error_log("Data Retrieval/Validation Error: ".$e->getMessage());
     json_response(false, 'ข้อมูลที่ส่งมาไม่ถูกต้อง: ' . $e->getMessage());
}

// --- Check for Duplicate Registration ---
try {
    $stmt_check = $mysqli->prepare("SELECT id FROM registrations WHERE event_id = ? AND thai_id = ?");
    if ($stmt_check === false) throw new Exception("Prepare statement failed for duplicate check: " . $mysqli->error);
    $stmt_check->bind_param("is", $event_id, $thai_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        json_response(false, 'หมายเลขบัตรประชาชนนี้ได้ลงทะเบียนเข้าร่วมกิจกรรมนี้ไปแล้ว');
    }
    $stmt_check->close();
} catch (Exception $e) {
    error_log("Duplicate Check Error: ".$e->getMessage());
    json_response(false, "เกิดข้อผิดพลาดในการตรวจสอบข้อมูลซ้ำ");
}

// --- 5. Handle Payment Slip Upload ---
$payment_slip_url = null;
try {
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0775, true)) {
                 throw new Exception('ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดได้');
            }
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
                 throw new Exception('ไม่สามารถบันทึกไฟล์สลิปได้');
            }
        } else {
             throw new Exception('ไฟล์สลิปไม่ถูกต้อง (ต้องเป็น JPG, PNG, PDF และขนาดไม่เกิน 5MB)');
        }
    } else {
        throw new Exception('กรุณาอัปโหลดหลักฐานการชำระเงิน');
    }
} catch (Exception $e) {
    error_log("File Upload Error: ".$e->getMessage());
    json_response(false, "เกิดข้อผิดพลาดในการอัปโหลดไฟล์: " . $e->getMessage());
}

// --- 6. Calculate Age and Find Race Category ---
$race_category_id = null; // Default to null
try {
    // Get event date
    $event_date_stmt = $mysqli->prepare("SELECT start_date FROM events WHERE id = ?");
    if (!$event_date_stmt) throw new Exception("Failed to prepare event date statement: " . $mysqli->error);
    $event_date_stmt->bind_param("i", $event_id);
    $event_date_stmt->execute();
    $event_start_date_result = $event_date_stmt->get_result();
    if ($event_start_date_result->num_rows === 0) {
        throw new Exception("Event with ID {$event_id} not found.");
    }
    $event_start_date_str = $event_start_date_result->fetch_assoc()['start_date'];
    $event_date_stmt->close();

    if (!$event_start_date_str) throw new Exception("Could not find event start date.");
    
    // Calculate age
    $event_date = new DateTime($event_start_date_str);
    $age = $event_date->diff($birthDateObj)->y;

    // --- NEW: Conditional Category Matching ---
    // First, check if any categories are defined for this specific event and distance
    $check_cat_stmt = $mysqli->prepare("SELECT COUNT(id) as total FROM race_categories WHERE event_id = ? AND distance = ?");
    $check_cat_stmt->bind_param("is", $event_id, $distance_name);
    $check_cat_stmt->execute();
    $total_cats = $check_cat_stmt->get_result()->fetch_assoc()['total'];
    $check_cat_stmt->close();

    // Only try to match a category if categories have been defined
    if ($total_cats > 0) {
        // Find matching category based on age, gender, distance
        $cat_stmt = $mysqli->prepare("
            SELECT id
            FROM race_categories
            WHERE event_id = ?
              AND distance = ?
              AND gender = ?
              AND minAge <= ?
              AND maxAge >= ?
            LIMIT 1
        ");
        if (!$cat_stmt) throw new Exception("Failed to prepare category statement: " . $mysqli->error);
        
        $cat_stmt->bind_param("isssi", $event_id, $distance_name, $gender, $age, $age);
        $cat_stmt->execute();
        $result = $cat_stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $race_category_id = intval($row['id']); // Assign the found ID
        } else {
            // Log if a runner doesn't fit any defined category
            error_log("No matching race category found for event_id={$event_id}, distance='{$distance_name}', gender='{$gender}', age={$age}, even though categories exist.");
        }
        $cat_stmt->close();
    }
    // If $total_cats is 0, $race_category_id remains null, implying "Open/ไม่จำกัดรุ่น".

} catch (Exception $e) {
    error_log("Age/Category Calculation Error: " . $e->getMessage());
    json_response(false, "เกิดข้อผิดพลาดในการคำนวณรุ่นการแข่งขัน: " . $e->getMessage());
}


// --- 7. Save data to the database ---
$mysqli->begin_transaction();
try {
    $registration_code = 'RUN' . date('Y') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    $stmt = $mysqli->prepare(
        "INSERT INTO registrations (
            registration_code, user_id, event_id, distance_id, race_category_id,
            shirt_size, title, first_name, last_name, gender,
            birth_date, thai_id, email, phone, line_id,
            disease, disease_detail,
            emergency_contact_name, emergency_contact_phone,
            payment_slip_url, status
         )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'รอตรวจสอบ')"
    );

    if ($stmt === false) {
        throw new Exception("Failed to prepare insert statement: " . $mysqli->error);
    }
    
    $stmt->bind_param("siiiisssssssssssssss",
        $registration_code,
        $user_id,
        $event_id,
        $distance_id,
        $race_category_id, // This will be null if no category was found/defined
        $shirt_size,
        $title,
        $first_name,
        $last_name,
        $gender,
        $birth_date,
        $thai_id,
        $email,
        $phone,
        $line_id,
        $disease,
        $disease_detail,
        $emergency_contact_name,
        $emergency_contact_phone,
        $payment_slip_url
    );

    if (!$stmt->execute()) {
         throw new Exception("Database execution failed: " . $stmt->error . " (Code: " . $stmt->errno . ")");
    }
    $stmt->close();

    if ($user_id !== null && !empty($gender)) {
        $update_user_stmt = $mysqli->prepare("UPDATE users SET gender = ? WHERE id = ? AND (gender IS NULL OR gender = '')");
        if($update_user_stmt) {
             $update_user_stmt->bind_param("si", $gender, $user_id);
             $update_user_stmt->execute();
             $update_user_stmt->close();
        }
    }

    $mysqli->commit();

    $_SESSION['success_message'] = "การสมัครของคุณเสร็จสมบูรณ์! รหัสสมัครคือ: " . $registration_code;
    $_SESSION['last_registration_code'] = $registration_code;
    json_response(true, 'Registration successful!', 'index.php?page=dashboard');

} catch (Exception $e) {
    $mysqli->rollback();
    error_log("Database Save Error: ".$e->getMessage());
    if ($mysqli->errno === 1062) {
         json_response(false, "เกิดข้อผิดพลาด: ข้อมูลซ้ำซ้อน อาจมีการสมัครด้วยข้อมูลนี้แล้ว");
    } else {
        json_response(false, "เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง (" . $e->getMessage() . ")");
    }
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli && $mysqli->thread_id) {
         $mysqli->close();
    }
}
?>

