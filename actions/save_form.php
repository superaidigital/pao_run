<?php
// actions/save_form.php
require_once '../config.php';
// ... (Session check & permission)

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $fields = $_POST['fields'] ?? [];

    $mysqli->begin_transaction();
    try {
        // Delete old form structure for this event
        $stmt_delete = $mysqli->prepare("DELETE FROM form_fields WHERE event_id = ?");
        $stmt_delete->bind_param("i", $event_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Insert new form structure
        $stmt_insert = $mysqli->prepare("INSERT INTO form_fields (event_id, field_label, field_name, field_type, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($fields as $index => $field) {
            $label = $field['field_label'];
            $type = $field['field_type'];
            $name = strtolower(str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9\s]/', '', $label)));
            $required = isset($field['is_required']) ? 1 : 0;
            $order = $index;
            
            $stmt_insert->bind_param("isssii", $event_id, $label, $name, $type, $required, $order);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
        
        $mysqli->commit();
        $_SESSION['update_success'] = "บันทึกโครงสร้างฟอร์มสำเร็จ";

    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['update_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
    header('Location: ../admin/form_builder.php?event_id=' . $event_id);
    exit;
}
?>
