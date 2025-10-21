<?php
// actions/update_event_order.php

require_once '../config.php';

// --- Session Check & Super Admin Permission ---
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: ../admin/index.php'); 
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id']) && isset($_GET['direction'])) {
    $event_id = intval($_GET['id']);
    $direction = $_GET['direction']; // 'up' or 'down'

    // Get current sort order of the item
    $stmt_current = $mysqli->prepare("SELECT sort_order FROM events WHERE id = ?");
    $stmt_current->bind_param("i", $event_id);
    $stmt_current->execute();
    $current_order = $stmt_current->get_result()->fetch_assoc()['sort_order'];
    $stmt_current->close();

    $target_order = null;
    $target_id = null;

    if ($direction === 'up') {
        // Find the item directly above (with the largest sort_order smaller than current)
        $stmt_target = $mysqli->prepare("SELECT id, sort_order FROM events WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        $stmt_target->bind_param("i", $current_order);
    } else { // down
        // Find the item directly below (with the smallest sort_order larger than current)
        $stmt_target = $mysqli->prepare("SELECT id, sort_order FROM events WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $stmt_target->bind_param("i", $current_order);
    }
    
    $stmt_target->execute();
    $result = $stmt_target->get_result();
    if ($row = $result->fetch_assoc()) {
        $target_id = $row['id'];
        $target_order = $row['sort_order'];
    }
    $stmt_target->close();

    // If a target was found, swap their sort_order values
    if ($target_id !== null && $target_order !== null) {
        $mysqli->begin_transaction();
        try {
            $stmt_swap1 = $mysqli->prepare("UPDATE events SET sort_order = ? WHERE id = ?");
            $stmt_swap1->bind_param("ii", $target_order, $event_id);
            $stmt_swap1->execute();
            
            $stmt_swap2 = $mysqli->prepare("UPDATE events SET sort_order = ? WHERE id = ?");
            $stmt_swap2->bind_param("ii", $current_order, $target_id);
            $stmt_swap2->execute();
            
            $mysqli->commit();
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Failed to swap order: " . $e->getMessage());
        }
    }
}

header('Location: ../admin/index.php');
exit;
?>
