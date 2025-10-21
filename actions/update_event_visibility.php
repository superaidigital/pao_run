<?php
// actions/update_event_visibility.php

require_once '../config.php';

// --- Session Check & Permission ---
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../admin/login.php'); exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $event_id = intval($_GET['id']);

    // Security check
    if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
        // Not allowed
        header('Location: ../admin/index.php'); exit;
    }

    // Toggle visibility
    $stmt = $mysqli->prepare("UPDATE events SET is_visible = 1 - is_visible WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();
}

header('Location: ../admin/index.php');
exit;
?>
