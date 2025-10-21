<?php
// actions/manage_master_data.php
// สคริปต์สำหรับจัดการข้อมูลพื้นฐาน (Master Data)

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Super Admin Permission ---
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: ../admin/login.php');
    exit;
}

// --- POST Request Check ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $action = $_POST['action'];
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? e(trim($_POST['name'])) : '';
    $description = isset($_POST['description']) ? e(trim($_POST['description'])) : null;
    $cost = isset($_POST['cost']) ? floatval($_POST['cost']) : 0.00;

    $table_name = '';
    $fields = ['name']; // Default fields
    $params = [$name];
    $types = 's';

    // Determine table and additional fields based on action prefix
    if (strpos($action, 'title') !== false) {
        $table_name = 'master_titles';
    } elseif (strpos($action, 'shirt_size') !== false) {
        $table_name = 'master_shirt_sizes';
        $fields[] = 'description';
        $params[] = $description;
        $types .= 's';
    } elseif (strpos($action, 'runner_type') !== false) {
        $table_name = 'master_runner_types';
    } elseif (strpos($action, 'pickup_option') !== false) {
        $table_name = 'master_pickup_options';
        $fields[] = 'cost';
        $params[] = $cost;
        $types .= 'd';
    }
    // === เพิ่มเงื่อนไขสำหรับ Gender ===
    elseif (strpos($action, 'gender') !== false) {
        $table_name = 'master_genders';
    }
    // === สิ้นสุด ===
    else {
        $_SESSION['master_error'] = "Invalid action prefix.";
        header('Location: ../admin/master_data.php');
        exit;
    }

    try {
        // --- ADD ACTION ---
        if (strpos($action, 'add_') === 0) {
            if (empty($name)) throw new Exception("ชื่อรายการห้ามว่าง");
            $sql = "INSERT INTO {$table_name} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', array_fill(0, count($fields), '?')) . ")";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) throw new Exception("Database error: " . $stmt->error);
            $_SESSION['master_success'] = "เพิ่มรายการสำเร็จ";
        }
        // --- UPDATE ACTION ---
        elseif (strpos($action, 'update_') === 0) {
            if ($id === 0 || empty($name)) throw new Exception("ข้อมูลไม่ถูกต้อง");
            $set_clause = implode(' = ?, ', $fields) . ' = ?';
            $sql = "UPDATE {$table_name} SET {$set_clause} WHERE id = ?";
            $params[] = $id;
            $types .= 'i';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) throw new Exception("Database error: " . $stmt->error);
            $_SESSION['master_success'] = "อัปเดตรายการสำเร็จ";
        }
        // --- DELETE ACTION ---
        elseif (strpos($action, 'delete_') === 0) {
            if ($id === 0) throw new Exception("Invalid ID.");
            // Check if the item is in use (Example for titles, adapt for others if needed)
            if ($table_name === 'master_titles') {
                $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM registrations WHERE title = (SELECT name FROM master_titles WHERE id = ?)");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->fetch_row()[0] > 0) {
                     throw new Exception("ไม่สามารถลบได้ เนื่องจากมีข้อมูลผู้สมัครใช้รายการนี้อยู่");
                }
                $check_stmt->close();
            }
             // Add similar checks for shirt sizes, genders, etc. if necessary

            $stmt = $mysqli->prepare("DELETE FROM {$table_name} WHERE id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) throw new Exception("Database error: " . $stmt->error);
            $_SESSION['master_success'] = "ลบรายการสำเร็จ";
        }
        else {
             throw new Exception("Invalid action specified.");
        }

        if (isset($stmt)) $stmt->close();

    } catch (Exception $e) {
        $_SESSION['master_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header('Location: ../admin/master_data.php');
    exit;

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>