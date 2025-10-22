<?php
// actions/update_event_settings.php
// สคริปต์สำหรับอัปเดตการตั้งค่ากิจกรรม (เวอร์ชันสมบูรณ์ + BIB + Corrals)

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
    $event_code = isset($_POST['event_code']) ? e($_POST['event_code']) : ''; 
    
    // Basic Info
    $name = isset($_POST['name']) ? e($_POST['name']) : '';
    $slogan = isset($_POST['slogan']) ? e($_POST['slogan']) : '';
    $start_date = isset($_POST['start_date']) ? e($_POST['start_date']) : '';
    $is_registration_open = isset($_POST['is_registration_open']) ? intval($_POST['is_registration_open']) : 0;
    $theme_color = isset($_POST['theme_color']) ? e($_POST['theme_color']) : 'indigo';
    $color_code = isset($_POST['color_code']) ? e($_POST['color_code']) : '#4f46e5';

    // Contact Info
    $organizer = isset($_POST['organizer']) ? e($_POST['organizer']) : '';
    $contact_person_name = isset($_POST['contact_person_name']) ? e($_POST['contact_person_name']) : '';
    $contact_person_phone = isset($_POST['contact_person_phone']) ? e($_POST['contact_person_phone']) : '';

    // Payment Info
    $payment_bank = isset($_POST['payment_bank']) ? e($_POST['payment_bank']) : '';
    $payment_account_name = isset($_POST['payment_account_name']) ? e($_POST['payment_account_name']) : '';
    $payment_account_number = isset($_POST['payment_account_number']) ? e($_POST['payment_account_number']) : '';

    // Content
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $awards_description = isset($_POST['awards_description']) ? $_POST['awards_description'] : null;
    
    // Map Info
    $map_embed_url = isset($_POST['map_embed_url']) ? $_POST['map_embed_url'] : null;
    $map_direction_url = isset($_POST['map_direction_url']) ? $_POST['map_direction_url'] : null;

    // Relational & File Data
    $posted_distances = isset($_POST['distances']) ? $_POST['distances'] : [];
    $images_to_delete = isset($_POST['delete_images']) ? $_POST['delete_images'] : [];
    
    // [NEW] BIB Settings
    $bib_prefix = isset($_POST['bib_prefix']) ? e(trim($_POST['bib_prefix'])) : null;
    $bib_start_number = isset($_POST['bib_start_number']) ? intval($_POST['bib_start_number']) : 1;
    $bib_padding = isset($_POST['bib_padding']) ? intval($_POST['bib_padding']) : 4;

    // [NEW] Corral Settings
    $corrals = isset($_POST['corrals']) ? $_POST['corrals'] : [];
    $corrals_json = !empty($corrals) ? json_encode(array_values($corrals), JSON_UNESCAPED_UNICODE) : null;


    // --- 2. Validation & Security ---
    if ($event_id === 0 || empty($event_code)) {
        $_SESSION['update_error'] = "Event ID or Event Code is missing.";
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
        // --- PRE-UPDATE: Fetch old bib start number for comparison ---
        $stmt_old_bib = $mysqli->prepare("SELECT bib_start_number FROM events WHERE id = ?");
        $stmt_old_bib->bind_param("i", $event_id);
        $stmt_old_bib->execute();
        $old_bib_start_number = $stmt_old_bib->get_result()->fetch_assoc()['bib_start_number'];
        $stmt_old_bib->close();

        // --- PRE-UPDATE: Handle Single File Uploads ---
        function handle_single_file_upload($file_input_name, $event_code, $sub_folder) {
            if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$file_input_name];
                $upload_dir = "../uploads/" . $event_code . "/" . $sub_folder . "/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
                
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename = $sub_folder . '_' . uniqid() . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    return "uploads/" . $event_code . "/" . $sub_folder . "/" . $new_filename;
                } else {
                    throw new Exception("Failed to upload new file for: " . $file_input_name);
                }
            }
            return null;
        }

        $logo_path_to_update = handle_single_file_upload('organizer_logo', $event_code, 'organizer');
        $qr_code_path_to_update = handle_single_file_upload('payment_qr_code', $event_code, 'payment');
        $cover_path_to_update = handle_single_file_upload('cover_image', $event_code, 'cover');
        $thumbnail_path_to_update = handle_single_file_upload('card_thumbnail', $event_code, 'cover');

        // Fetch old paths for deletion later
        $old_paths = [];
        if ($logo_path_to_update || $qr_code_path_to_update || $cover_path_to_update || $thumbnail_path_to_update) {
            $stmt_old = $mysqli->prepare("SELECT organizer_logo_url, payment_qr_code_url, cover_image_url, card_thumbnail_url FROM events WHERE id = ?");
            $stmt_old->bind_param("i", $event_id);
            $stmt_old->execute();
            if ($row = $stmt_old->get_result()->fetch_assoc()) {
                if($logo_path_to_update) $old_paths['logo'] = $row['organizer_logo_url'];
                if($qr_code_path_to_update) $old_paths['qr'] = $row['payment_qr_code_url'];
                if($cover_path_to_update) $old_paths['cover'] = $row['cover_image_url'];
                if($thumbnail_path_to_update) $old_paths['thumbnail'] = $row['card_thumbnail_url'];
            }
            $stmt_old->close();
        }
        
        // Step A: Build and execute the main `events` table update
        $sql_set_parts = [
            "name = ?", "slogan = ?", "start_date = ?", "is_registration_open = ?",
            "theme_color = ?", "color_code = ?",
            "organizer = ?", "contact_person_name = ?", "contact_person_phone = ?",
            "payment_bank = ?", "payment_account_name = ?", "payment_account_number = ?",
            "description = ?", "awards_description = ?",
            "map_embed_url = ?", "map_direction_url = ?",
            "bib_prefix = ?", "bib_start_number = ?", "bib_padding = ?", "corral_settings = ?"
        ];
        $params = [
            $name, $slogan, $start_date, $is_registration_open,
            $theme_color, $color_code,
            $organizer, $contact_person_name, $contact_person_phone,
            $payment_bank, $payment_account_name, $payment_account_number,
            $description, $awards_description,
            $map_embed_url, $map_direction_url,
            $bib_prefix, $bib_start_number, $bib_padding, $corrals_json
        ];
        $types = "sssisssssssssssssiis";

        if ($logo_path_to_update) { $sql_set_parts[] = "organizer_logo_url = ?"; $params[] = $logo_path_to_update; $types .= "s"; }
        if ($qr_code_path_to_update) { $sql_set_parts[] = "payment_qr_code_url = ?"; $params[] = $qr_code_path_to_update; $types .= "s"; }
        if ($cover_path_to_update) { $sql_set_parts[] = "cover_image_url = ?"; $params[] = $cover_path_to_update; $types .= "s"; }
        if ($thumbnail_path_to_update) { $sql_set_parts[] = "card_thumbnail_url = ?"; $params[] = $thumbnail_path_to_update; $types .= "s"; }

        $params[] = $event_id;
        $types .= "i";
        
        $sql = "UPDATE events SET " . implode(', ', $sql_set_parts) . " WHERE id = ?";
        $stmt_event = $mysqli->prepare($sql);
        if ($stmt_event === false) throw new Exception("Prepare failed: " . $mysqli->error);
        $stmt_event->bind_param($types, ...$params);

        if (!$stmt_event->execute()) throw new Exception("Failed to update event details: " . $stmt_event->error);
        $stmt_event->close();

        // [NEW] Reset bib_next_number if bib_start_number was changed
        if ($old_bib_start_number != $bib_start_number) {
            $stmt_reset_bib = $mysqli->prepare("UPDATE events SET bib_next_number = ? WHERE id = ?");
            $stmt_reset_bib->bind_param("ii", $bib_start_number, $event_id);
            $stmt_reset_bib->execute();
            $stmt_reset_bib->close();
        }

        // Delete old files after DB update is successful
        if (!empty($old_paths['logo'])) @unlink('../' . $old_paths['logo']);
        if (!empty($old_paths['qr'])) @unlink('../' . $old_paths['qr']);
        if (!empty($old_paths['cover'])) @unlink('../' . $old_paths['cover']);
        if (!empty($old_paths['thumbnail'])) @unlink('../' . $old_paths['thumbnail']);

        // Step B: Update `distances` table
        $stmt_delete_dist = $mysqli->prepare("DELETE FROM distances WHERE event_id = ?");
        $stmt_delete_dist->bind_param("i", $event_id);
        if (!$stmt_delete_dist->execute()) throw new Exception("Failed to clear old distances.");
        $stmt_delete_dist->close();

        $stmt_insert_dist = $mysqli->prepare("INSERT INTO distances (event_id, name, category, price) VALUES (?, ?, ?, ?)");
        foreach ($posted_distances as $dist) {
            $dist_name = $dist['name'] ?? '';
            $dist_category = $dist['category'] ?? '';
            $dist_price = !empty($dist['price']) ? floatval($dist['price']) : 0;
            if (!empty($dist_name)) {
                $stmt_insert_dist->bind_param("issd", $event_id, $dist_name, $dist_category, $dist_price);
                if (!$stmt_insert_dist->execute()) throw new Exception("Failed to insert new distance.");
            }
        }
        $stmt_insert_dist->close();
        
        // Step C: Handle Image Deletion
        if (!empty($images_to_delete)) {
            $delete_ids_safe = array_map('intval', $images_to_delete);
            $placeholders = implode(',', array_fill(0, count($delete_ids_safe), '?'));
            
            $stmt_select_paths = $mysqli->prepare("SELECT image_url FROM event_images WHERE id IN ($placeholders) AND event_id = ?");
            $types_del = str_repeat('i', count($delete_ids_safe)) . 'i';
            $params_del = array_merge($delete_ids_safe, [$event_id]);
            $stmt_select_paths->bind_param($types_del, ...$params_del);
            $stmt_select_paths->execute();
            $paths_to_delete = $stmt_select_paths->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_select_paths->close();

            $stmt_delete_img = $mysqli->prepare("DELETE FROM event_images WHERE id IN ($placeholders) AND event_id = ?");
            $stmt_delete_img->bind_param($types_del, ...$params_del);
            if (!$stmt_delete_img->execute()) throw new Exception("Failed to delete image records from DB.");
            $stmt_delete_img->close();

            foreach ($paths_to_delete as $path_row) {
                if (file_exists('../' . $path_row['image_url'])) {
                    @unlink('../' . $path_row['image_url']);
                }
            }
        }

        // Step D: Handle Image Uploads
        function handle_image_uploads($file_input_name, $event_id, $event_code, $image_type, $mysqli) {
            if (isset($_FILES[$file_input_name]) && is_array($_FILES[$file_input_name]['name'])) {
                $files = $_FILES[$file_input_name];
                $upload_dir = "../uploads/" . $event_code . "/" . $image_type . "/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

                $stmt_insert = $mysqli->prepare("INSERT INTO event_images (event_id, image_url, image_type) VALUES (?, ?, ?)");

                foreach ($files['name'] as $key => $name) {
                    if ($files['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $files['tmp_name'][$key];
                        $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $new_filename = uniqid('', true) . '.' . $file_ext;
                        $destination = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $db_path = "uploads/" . $event_code . "/" . $image_type . "/" . $new_filename;
                            $stmt_insert->bind_param("iss", $event_id, $db_path, $image_type);
                            if (!$stmt_insert->execute()) {
                                throw new Exception("Failed to insert image record into DB for " . $name);
                            }
                        }
                    }
                }
                $stmt_insert->close();
            }
        }
        
        handle_image_uploads('detail_images', $event_id, $event_code, 'detail', $mysqli);
        handle_image_uploads('merch_images', $event_id, $event_code, 'merch', $mysqli);
        handle_image_uploads('medal_images', $event_id, $event_code, 'medal', $mysqli);

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

