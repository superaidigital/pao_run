<?php
// actions/update_registration.php
// สคริปต์สำหรับอัปเดตสถานะและ BIB ของผู้สมัคร

require_once '../config.php';
require_once '../functions.php';

// --- Session Check for Staff ---
if (!isset($_SESSION['staff_id'])) {
    // Redirect to login if not logged in
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
    $bib_number = isset($_POST['bib_number']) ? e(trim($_POST['bib_number'])) : null;
    
    // Set BIB to NULL if empty string is submitted
    if ($bib_number === '') {
        $bib_number = null;
    }

    // --- 2. Validation ---
    if ($reg_id === 0 || $event_id === 0 || empty($new_status)) {
        // Handle error - ideally set a session error message
        header('Location: ../admin/index.php'); // Redirect to safety
        exit;
    }

    // --- 3. Security Check: Verify Permission ---
    if (!$is_super_admin) {
        // Fetch the event_id for the registration to double-check
        $stmt_check = $mysqli->prepare("SELECT event_id FROM registrations WHERE id = ?");
        $stmt_check->bind_param("i", $reg_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $reg_event = $result_check->fetch_assoc();
            if ($reg_event['event_id'] !== $staff_info['assigned_event_id']) {
                // Staff is trying to modify a registration outside their scope
                header('Location: ../admin/index.php'); // Redirect to safety
                exit;
            }
        }
        $stmt_check->close();
    }


    // --- 4. Update Database ---
    try {
        $stmt = $mysqli->prepare(
            "UPDATE registrations SET status = ?, bib_number = ? WHERE id = ?"
        );
        $stmt->bind_param("ssi", $new_status, $bib_number, $reg_id);
        
        if ($stmt->execute()) {
            $_SESSION['update_success'] = "ข้อมูลการสมัคร (ID: $reg_id) ได้รับการอัปเดตเรียบร้อยแล้ว";
        } else {
            throw new Exception("Database update failed.");
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log($e->getMessage());
        // Set an error message if you want to display it
        // $_SESSION['update_error'] = "Failed to update registration.";
    }

    // --- 5. Redirect Back to Detail Page ---
    header('Location: ../admin/registrant_detail.php?reg_id=' . $reg_id);
    exit;

} else {
    // Redirect if not a POST request
    header('Location: ../admin/index.php');
    exit;
}
?>

