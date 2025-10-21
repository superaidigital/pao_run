<?php
// actions/update_event_settings.php
// สคริปต์สำหรับอัปเดตการตั้งค่ากิจกรรม (เวอร์ชันอัปเกรดสมบูรณ์)

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Permission ---
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../admin/login.php'); exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');

// --- POST Request Check ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Get and Sanitize Data ---
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    
    // Section 1: Basic Info
    $name = isset($_POST['name']) ? e($_POST['name']) : '';
    $slogan = isset($_POST['slogan']) ? e($_POST['slogan']) : '';
    $start_date = isset($_POST['start_date']) ? e($_POST['start_date']) : '';
    $is_registration_open = isset($_POST['is_registration_open']) ? intval($_POST['is_registration_open']) : 0;

    // Section 2: Contact Info
    $organizer = isset($_POST['organizer']) ? e($_POST['organizer']) : '';
    $contact_person_name = isset($_POST['contact_person_name']) ? e($_POST['contact_person_name']) : '';
    $contact_person_phone = isset($_POST['contact_person_phone']) ? e($_POST['contact_person_phone']) : '';

    // Section 3: Payment Info
    $payment_bank = isset($_POST['payment_bank']) ? e($_POST['payment_bank']) : '';
    $payment_account_name = isset($_POST['payment_account_name']) ? e($_POST['payment_account_name']) : '';
    $payment_account_number = isset($_POST['payment_account_number']) ? e($_POST['payment_account_number']) : '';

    // Section 4: Rich Content
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $awards_description = isset($_POST['awards_description']) ? $_POST['awards_description'] : null;

    // Section 5 & 6: Relational Data
    $posted_distances = isset($_POST['distances']) ? $_POST['distances'] : [];
    
    function process_textarea_urls($textarea_name) {
        return isset($_POST[$textarea_name]) ? array_filter(array_map('trim', explode("\n", $_POST[$textarea_name]))) : [];
    }
    $detail_images = process_textarea_urls('detail_images');
    $merch_images = process_textarea_urls('merch_images');
    $medal_images = process_textarea_urls('medal_images');

    // --- 2. Validation & Security ---
    if ($event_id === 0) {
        $_SESSION['update_error'] = "Event ID is missing.";
        header('Location: ../admin/index.php'); exit;
    }
    if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
        $_SESSION['update_error'] = "You do not have permission to modify this event.";
        header('Location: ../admin/index.php'); exit;
    }
    if (empty($name) || empty($start_date)) {
        $_SESSION['update_error'] = "ข้อมูลไม่ครบถ้วน (ชื่อกิจกรรม และ วันที่จัดงาน จำเป็นต้องกรอก)";
        header('Location: ../admin/event_settings.php?event_id=' . $event_id);
        exit;
    }


    // --- 3. Database Transaction ---
    $mysqli->begin_transaction();

    try {
        // Step A: Update `events` table with all fields
        $stmt_event = $mysqli->prepare(
            "UPDATE events SET 
                name = ?, slogan = ?, start_date = ?, is_registration_open = ?,
                organizer = ?, contact_person_name = ?, contact_person_phone = ?,
                payment_bank = ?, payment_account_name = ?, payment_account_number = ?,
                description = ?, awards_description = ?
            WHERE id = ?"
        );
        $stmt_event->bind_param("sssissssssssi", 
            $name, $slogan, $start_date, $is_registration_open,
            $organizer, $contact_person_name, $contact_person_phone,
            $payment_bank, $payment_account_name, $payment_account_number,
            $description, $awards_description,
            $event_id
        );
        if (!$stmt_event->execute()) throw new Exception("Failed to update event details: " . $stmt_event->error);
        $stmt_event->close();

        // Step B: Update `distances` table (Delete and Re-insert)
        $stmt_delete_dist = $mysqli->prepare("DELETE FROM distances WHERE event_id = ?");
        $stmt_delete_dist->bind_param("i", $event_id);
        if (!$stmt_delete_dist->execute()) throw new Exception("Failed to clear old distances.");
        $stmt_delete_dist->close();

        $stmt_insert_dist = $mysqli->prepare("INSERT INTO distances (event_id, name, category, price) VALUES (?, ?, ?, ?)");
        foreach ($posted_distances as $dist) {
            $dist_name = $dist['name'] ?? '';
            $dist_category = $dist['category'] ?? '';
            $dist_price = !empty($dist['price']) ? floatval($dist['price']) : 0;
            if (!empty($dist_name) && !empty($dist_category)) { // Insert only valid rows
                $stmt_insert_dist->bind_param("issd", $event_id, $dist_name, $dist_category, $dist_price);
                if (!$stmt_insert_dist->execute()) throw new Exception("Failed to insert new distance.");
            }
        }
        $stmt_insert_dist->close();
        
        // Step C: Update `event_images` table (Delete and Re-insert)
        $stmt_delete_img = $mysqli->prepare("DELETE FROM event_images WHERE event_id = ?");
        $stmt_delete_img->bind_param("i", $event_id);
        if (!$stmt_delete_img->execute()) throw new Exception("Failed to clear old images.");
        $stmt_delete_img->close();

        $stmt_insert_img = $mysqli->prepare("INSERT INTO event_images (event_id, image_url, image_type) VALUES (?, ?, ?)");
        function insert_images($stmt, $event_id, $images, $type) {
            foreach ($images as $url) {
                $stmt->bind_param("iss", $event_id, $url, $type);
                if (!$stmt->execute()) throw new Exception("Failed to insert image type $type.");
            }
        }
        insert_images($stmt_insert_img, $event_id, $detail_images, 'detail');
        insert_images($stmt_insert_img, $event_id, $merch_images, 'merch');
        insert_images($stmt_insert_img, $event_id, $medal_images, 'medal');
        $stmt_insert_img->close();

        // If all successful, commit the transaction
        $mysqli->commit();
        $_SESSION['update_success'] = "ข้อมูลกิจกรรมทั้งหมดได้รับการอัปเดตเรียบร้อยแล้ว";

    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Event Update Failed: " . $e->getMessage());
        $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }

    // --- 5. Redirect Back ---
    header('Location: ../admin/event_settings.php?event_id=' . $event_id);
    exit;

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>

