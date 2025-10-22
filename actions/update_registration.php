<?php
// actions/update_registration.php
// สคริปต์สำหรับอัปเดตสถานะและ BIB ของผู้สมัคร (เวอร์ชันพัฒนา BIB อัตโนมัติ)

require_once '../config.php';
require_once '../functions.php';

// --- Session Check for Staff ---
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../admin/login.php');
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');


// --- Check for POST request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Get and Sanitize Data ---
    $reg_id = isset($_POST['reg_id']) ? intval($_POST['reg_id']) : 0;
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0; // For redirecting back
    $new_status = isset($_POST['status']) ? e($_POST['status']) : '';
    $manual_bib_number = isset($_POST['bib_number']) ? e(trim($_POST['bib_number'])) : null;
    
    // --- 2. Validation ---
    if ($reg_id === 0 || $event_id === 0 || empty($new_status)) {
        $_SESSION['update_error'] = "ข้อมูลที่ส่งมาไม่ถูกต้อง";
        header('Location: ../admin/registrants.php?event_id=' . $event_id);
        exit;
    }

    // --- 3. Security Check: Verify Permission ---
    // (Existing permission check code is good)
    if (!$is_super_admin) {
        $stmt_check = $mysqli->prepare("SELECT event_id FROM registrations WHERE id = ?");
        $stmt_check->bind_param("i", $reg_id);
        $stmt_check->execute();
        $reg_event = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        if ($reg_event && $reg_event['event_id'] !== $staff_info['assigned_event_id']) {
            $_SESSION['update_error'] = "You do not have permission for this registration.";
            header('Location: ../admin/index.php');
            exit;
        }
    }

    // --- 4. Begin Database Transaction ---
    $mysqli->begin_transaction();

    try {
        // Fetch current registration status and bib number
        $stmt_current_reg = $mysqli->prepare("SELECT status, bib_number FROM registrations WHERE id = ?");
        $stmt_current_reg->bind_param("i", $reg_id);
        $stmt_current_reg->execute();
        $current_reg = $stmt_current_reg->get_result()->fetch_assoc();
        $stmt_current_reg->close();

        $bib_to_update = $manual_bib_number;
        $corral_to_update = null;

        // --- [CORE LOGIC] AUTO-ASSIGN BIB and CORRAL ---
        // This block runs ONLY when status is changed to 'Paid' AND no BIB is assigned yet.
        if ($new_status === 'ชำระเงินแล้ว' && empty($current_reg['bib_number']) && empty($manual_bib_number)) {
            
            // Lock the event row to prevent race conditions when getting the next BIB number
            $stmt_event = $mysqli->prepare("SELECT bib_prefix, bib_padding, bib_next_number, corral_settings FROM events WHERE id = ? FOR UPDATE");
            $stmt_event->bind_param("i", $event_id);
            $stmt_event->execute();
            $event_settings = $stmt_event->get_result()->fetch_assoc();
            $stmt_event->close();

            if ($event_settings) {
                $next_bib_num = $event_settings['bib_next_number'];
                
                // Generate the full BIB number string
                $bib_number_padded = str_pad($next_bib_num, $event_settings['bib_padding'], '0', STR_PAD_LEFT);
                $bib_to_update = ($event_settings['bib_prefix'] ?? '') . $bib_number_padded;

                // Determine Corral based on settings
                if (!empty($event_settings['corral_settings'])) {
                    $corrals = json_decode($event_settings['corral_settings'], true);
                    if (is_array($corrals)) {
                        foreach ($corrals as $corral) {
                            if ($next_bib_num >= $corral['from_bib'] && $next_bib_num <= $corral['to_bib']) {
                                $corral_to_update = $corral['name'];
                                break;
                            }
                        }
                    }
                }
                
                // Increment the next BIB number for the event
                $stmt_update_event = $mysqli->prepare("UPDATE events SET bib_next_number = bib_next_number + 1 WHERE id = ?");
                $stmt_update_event->bind_param("i", $event_id);
                if (!$stmt_update_event->execute()) {
                    throw new Exception("Failed to increment next BIB number for event.");
                }
                $stmt_update_event->close();
            }
        }
        
        // --- 5. Update the registration record ---
        $stmt_update_reg = $mysqli->prepare(
            "UPDATE registrations SET status = ?, bib_number = ?, corral = ? WHERE id = ?"
        );
        $stmt_update_reg->bind_param("sssi", $new_status, $bib_to_update, $corral_to_update, $reg_id);
        
        if (!$stmt_update_reg->execute()) {
            throw new Exception("Database update for registration failed.");
        }
        $stmt_update_reg->close();
        
        // --- 6. Commit Transaction ---
        $mysqli->commit();
        $_SESSION['update_success'] = "ข้อมูลการสมัคร (ID: $reg_id) ได้รับการอัปเดตเรียบร้อยแล้ว";

    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Update Registration Failed: " . $e->getMessage());
        $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }

    // --- 7. Redirect Back to Detail Page ---
    header('Location: ../admin/registrant_detail.php?reg_id=' . $reg_id);
    exit;

} else {
    // Redirect if not a POST request
    header('Location: ../admin/index.php');
    exit;
}
?>

