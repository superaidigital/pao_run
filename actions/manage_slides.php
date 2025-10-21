<?php
// actions/manage_slides.php
// สคริปต์สำหรับจัดการ (เพิ่ม/แก้ไข/ลบ) สไลด์

require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Super Admin Permission ---
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_info']['role'] !== 'admin') {
    header('Location: ../admin/login.php'); 
    exit;
}

// --- File Upload Function ---
function handle_slide_upload($file_input_name) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$file_input_name];
        $upload_dir = "../uploads/slides/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed)) {
            throw new Exception("ไฟล์รูปภาพต้องเป็น .jpg, .jpeg, .png, หรือ .webp เท่านั้น");
        }

        $new_filename = 'slide_' . uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return "uploads/slides/" . $new_filename; // Return path relative to root
        } else {
            throw new Exception("ไม่สามารถย้ายไฟล์ที่อัปโหลดได้");
        }
    }
    return null; // No file uploaded or error
}

// --- POST Request Check ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $action = $_POST['action'];

    try {
        // --- ACTION: CREATE ---
        if ($action === 'create') {
            $title = isset($_POST['title']) ? e($_POST['title']) : '';
            $subtitle = isset($_POST['subtitle']) ? e($_POST['subtitle']) : '';
            $link_url = isset($_POST['link_url']) ? e($_POST['link_url']) : '';
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
            
            $image_path = handle_slide_upload('image');
            if ($image_path === null) {
                throw new Exception("กรุณาอัปโหลดรูปภาพสำหรับสไลด์");
            }

            // Get new sort order
            $result = $mysqli->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM slides");
            $next_order = $result->fetch_assoc()['next_order'];

            $stmt = $mysqli->prepare("INSERT INTO slides (title, subtitle, link_url, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $title, $subtitle, $link_url, $image_path, $is_active, $next_order);
            
            if ($stmt->execute()) {
                $_SESSION['update_success'] = "เพิ่มสไลด์ใหม่สำเร็จ";
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
            $stmt->close();
        }
        
        // --- ACTION: UPDATE ---
        if ($action === 'update') {
            $slide_id = isset($_POST['slide_id']) ? intval($_POST['slide_id']) : 0;
            $title = isset($_POST['title']) ? e($_POST['title']) : '';
            $subtitle = isset($_POST['subtitle']) ? e($_POST['subtitle']) : '';
            $link_url = isset($_POST['link_url']) ? e($_POST['link_url']) : '';
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

            if ($slide_id === 0) throw new Exception("Invalid Slide ID.");

            $image_path = handle_slide_upload('image');
            
            if ($image_path !== null) {
                // New image uploaded, delete old one first
                $stmt_old = $mysqli->prepare("SELECT image_url FROM slides WHERE id = ?");
                $stmt_old->bind_param("i", $slide_id);
                $stmt_old->execute();
                if ($old = $stmt_old->get_result()->fetch_assoc()) {
                    if (!empty($old['image_url']) && file_exists('../' . $old['image_url'])) {
                        @unlink('../' . $old['image_url']);
                    }
                }
                $stmt_old->close();
                
                // Update with new image
                $stmt = $mysqli->prepare("UPDATE slides SET title = ?, subtitle = ?, link_url = ?, is_active = ?, image_url = ? WHERE id = ?");
                $stmt->bind_param("sssisi", $title, $subtitle, $link_url, $is_active, $image_path, $slide_id);
            } else {
                // Update without changing image
                $stmt = $mysqli->prepare("UPDATE slides SET title = ?, subtitle = ?, link_url = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sssii", $title, $subtitle, $link_url, $is_active, $slide_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['update_success'] = "อัปเดตสไลด์สำเร็จ";
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
            $stmt->close();
        }

        // --- ACTION: DELETE ---
        if ($action === 'delete') {
            $slide_id = isset($_POST['slide_id']) ? intval($_POST['slide_id']) : 0;
            if ($slide_id === 0) throw new Exception("Invalid Slide ID.");

            // Get image path before deleting
            $stmt_old = $mysqli->prepare("SELECT image_url FROM slides WHERE id = ?");
            $stmt_old->bind_param("i", $slide_id);
            $stmt_old->execute();
            $old_image_url = $stmt_old->get_result()->fetch_assoc()['image_url'] ?? null;
            $stmt_old->close();

            // Delete from DB
            $stmt = $mysqli->prepare("DELETE FROM slides WHERE id = ?");
            $stmt->bind_param("i", $slide_id);
            if ($stmt->execute()) {
                // Delete file from server
                if ($old_image_url && file_exists('../' . $old_image_url)) {
                    @unlink('../' . $old_image_url);
                }
                $_SESSION['update_success'] = "ลบสไลด์สำเร็จ";
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
            $stmt->close();
        }
    
    } catch (Exception $e) {
        $_SESSION['update_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
    
    header('Location: ../admin/slides_management.php');
    exit;

} else {
    header('Location: ../admin/index.php');
    exit;
}
?>