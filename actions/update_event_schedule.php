<?php
// actions/update_event_schedule.php

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

    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $posted_days = isset($_POST['schedule']) ? $_POST['schedule'] : [];

    // Validation & Security
    if ($event_id === 0) {
        $_SESSION['update_error'] = "Event ID is missing.";
        header('Location: ../admin/index.php'); exit;
    }
    if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
        $_SESSION['update_error'] = "You do not have permission to modify this event.";
        header('Location: ../admin/index.php'); exit;
    }

    $mysqli->begin_transaction();
    try {
        // Delete all existing schedules for the event and re-insert.
        $stmt_delete = $mysqli->prepare("DELETE FROM schedules WHERE event_id = ?");
        $stmt_delete->bind_param("i", $event_id);
        if (!$stmt_delete->execute()) throw new Exception("Failed to clear old schedule.");
        $stmt_delete->close();

        if (!empty($posted_days)) {
            $stmt_insert = $mysqli->prepare("INSERT INTO schedules (event_id, `date`, `time`, activity, is_highlight) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt_insert) throw new Exception("Failed to prepare insert statement: " . $mysqli->error);
            
            foreach ($posted_days as $day) {
                $date = $day['date'] ?? null;
                // Check if the 'items' key exists and is an array before looping
                if (!empty($date) && isset($day['items']) && is_array($day['items'])) {
                    foreach ($day['items'] as $item) {
                        $time = $item['time'] ?? '';
                        $activity = $item['activity'] ?? '';
                        $is_highlight = isset($item['is_highlight']) ? 1 : 0;
                        
                        if (!empty($time) && !empty($activity)) {
                            $stmt_insert->bind_param("isssi", $event_id, $date, $time, $activity, $is_highlight);
                            if (!$stmt_insert->execute()) throw new Exception("Failed to insert new schedule item.");
                        }
                    }
                }
            }
            $stmt_insert->close();
        }

        $mysqli->commit();
        $_SESSION['update_success'] = "กำหนดการแข่งขันได้รับการอัปเดตเรียบร้อยแล้ว";

    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Schedule Update Failed: " . $e->getMessage());
        $_SESSION['update_error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }

    header('Location: ../admin/event_schedule.php?event_id=' . $event_id);
    exit;

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>

