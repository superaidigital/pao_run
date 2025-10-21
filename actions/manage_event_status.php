<?php
// actions/manage_event_status.php
// Handles delete, cancel, and restore actions for events.

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Super Admin Permission ---
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: ../admin/login.php'); 
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id']) && isset($_GET['action'])) {
    $event_id = intval($_GET['id']);
    $action = $_GET['action'];

    // --- ACTION: DELETE ---
    if ($action === 'delete') {
        // First, check if there are any registrants for this event.
        $check_stmt = $mysqli->prepare("SELECT COUNT(id) as count FROM registrations WHERE event_id = ?");
        $check_stmt->bind_param("i", $event_id);
        $check_stmt->execute();
        $count = $check_stmt->get_result()->fetch_assoc()['count'];
        $check_stmt->close();

        if ($count > 0) {
            // If there are registrants, do not delete. Set an error message.
            $_SESSION['update_error'] = "ไม่สามารถลบกิจกรรมได้ เนื่องจากมีผู้สมัครแล้ว";
        } else {
            // No registrants, proceed with deletion from all related tables.
            $mysqli->begin_transaction();
            try {
                // You should also delete files associated with the event from the '/uploads' directory
                // This part is complex and should be handled with care. For now, we focus on DB records.
                $mysqli->query("DELETE FROM schedules WHERE event_id = $event_id");
                $mysqli->query("DELETE FROM event_images WHERE event_id = $event_id");
                $mysqli->query("DELETE FROM distances WHERE event_id = $event_id");
                $mysqli->query("DELETE FROM events WHERE id = $event_id");
                $mysqli->commit();
                $_SESSION['update_success'] = "ลบกิจกรรมสำเร็จแล้ว";
            } catch (Exception $e) {
                $mysqli->rollback();
                $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการลบกิจกรรม: " . $e->getMessage();
            }
        }
    }
    
    // --- ACTION: CANCEL ---
    elseif ($action === 'cancel') {
        $stmt = $mysqli->prepare("UPDATE events SET is_cancelled = 1, is_visible = 0 WHERE id = ?"); // Also hide it
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['update_success'] = "ยกเลิกกิจกรรมเรียบร้อยแล้ว";
    }

    // --- ACTION: RESTORE ---
    elseif ($action === 'restore') {
        $stmt = $mysqli->prepare("UPDATE events SET is_cancelled = 0 WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['update_success'] = "กู้คืนกิจกรรมเรียบร้อยแล้ว";
    }
}

header('Location: ../admin/index.php');
exit;
?>

