<?php
// ไฟล์: actions/admin_action.php
// หน้าที่: จัดการ Logic สำหรับ Admin Panel ทั้งหมด

session_start();
require_once '../includes/db.php';

// ตรวจสอบสิทธิ์ Admin และ Action
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access Denied. Please log in as admin.'];
    header('Location: ../index.php?page=admin');
    exit();
}
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if (!$action) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid Action.'];
    header('Location: ../index.php?page=admin');
    exit();
}

switch ($action) {
    // --- จัดการกิจกรรม ---
    case 'create_event':
    case 'update_event':
        $id = trim($_POST['id'] ?? $_POST['event_id']);
        $name = trim($_POST['name']);
        $slogan = trim($_POST['slogan']);
        $description = trim($_POST['description']);
        $startDate = $_POST['startDate'];
        $isRegistrationOpen = intval($_POST['isRegistrationOpen']);
        $organizer = trim($_POST['organizer']);
        $contactPersonName = trim($_POST['contactPersonName']);
        $contactPersonPhone = trim($_POST['contactPersonPhone']);
        $paymentBank = trim($_POST['paymentBank']);
        $paymentAccountName = trim($_POST['paymentAccountName']);
        $paymentAccountNumber = trim($_POST['paymentAccountNumber']);
        $paymentQrCodeUrl = trim($_POST['paymentQrCodeUrl']);
        $coverImageUrl = trim($_POST['coverImageUrl']);
        $cardThumbnailUrl = trim($_POST['cardThumbnailUrl']);

        $distances_data = [];
        if (isset($_POST['distance_name']) && is_array($_POST['distance_name'])) {
            foreach ($_POST['distance_name'] as $key => $dist_name) {
                if (!empty($dist_name)) {
                    $distances_data[] = [
                        'name' => $dist_name,
                        'category' => $_POST['distance_category'][$key] ?? '',
                        'price' => floatval($_POST['distance_price'][$key] ?? 0)
                    ];
                }
            }
        }
        $distances_json = json_encode($distances_data);

        if ($action === 'create_event') {
            $sql = "INSERT INTO events (id, name, slogan, description, startDate, isRegistrationOpen, organizer, contactPersonName, contactPersonPhone, paymentBank, paymentAccountName, paymentAccountNumber, paymentQrCodeUrl, coverImageUrl, cardThumbnailUrl, distances) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssissssssssss", $id, $name, $slogan, $description, $startDate, $isRegistrationOpen, $organizer, $contactPersonName, $contactPersonPhone, $paymentBank, $paymentAccountName, $paymentAccountNumber, $paymentQrCodeUrl, $coverImageUrl, $cardThumbnailUrl, $distances_json);
        } else {
            $sql = "UPDATE events SET name = ?, slogan = ?, description = ?, startDate = ?, isRegistrationOpen = ?, organizer = ?, contactPersonName = ?, contactPersonPhone = ?, paymentBank = ?, paymentAccountName = ?, paymentAccountNumber = ?, paymentQrCodeUrl = ?, coverImageUrl = ?, cardThumbnailUrl = ?, distances = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssisssssssssss", $name, $slogan, $description, $startDate, $isRegistrationOpen, $organizer, $contactPersonName, $contactPersonPhone, $paymentBank, $paymentAccountName, $paymentAccountNumber, $paymentQrCodeUrl, $coverImageUrl, $cardThumbnailUrl, $distances_json, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'บันทึกข้อมูลกิจกรรมสำเร็จ'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $stmt->error];
        }
        $stmt->close();
        header('Location: ../index.php?page=admin_events');
        exit();

    case 'delete_event':
        $id = trim($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'ลบกิจกรรมสำเร็จ'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $stmt->error];
        }
        $stmt->close();
        header('Location: ../index.php?page=admin_events');
        exit();

    // --- จัดการข้อมูลทั่วไป ---
    case 'update_globals':
        $slides_data = [];
        if (isset($_POST['slide_imageUrl']) && is_array($_POST['slide_imageUrl'])) {
            foreach ($_POST['slide_imageUrl'] as $key => $imageUrl) {
                if (!empty($imageUrl)) {
                    $slides_data[] = [
                        'imageUrl' => $imageUrl,
                        'title' => $_POST['slide_title'][$key] ?? '',
                        'subtitle' => $_POST['slide_subtitle'][$key] ?? '',
                        'link' => $_POST['slide_link'][$key] ?? '#',
                    ];
                }
            }
        }
        $slides_json = json_encode($slides_data, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("UPDATE globals SET slides = ? WHERE id = 1");
        $stmt->bind_param("s", $slides_json);
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'บันทึกข้อมูลทั่วไปเรียบร้อยแล้ว'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $stmt->error];
        }
        $stmt->close();
        header('Location: ../index.php?page=admin_globals');
        exit();

    // --- จัดการรุ่นการแข่งขัน ---
    case 'create_category':
        $eventId = $_POST['eventId'];
        $distance = $_POST['distance'];
        $name = $_POST['name'];
        $gender = $_POST['gender'];
        $minAge = intval($_POST['minAge']);
        $maxAge = intval($_POST['maxAge']);

        $stmt = $conn->prepare("INSERT INTO race_categories (eventId, distance, name, gender, minAge, maxAge) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $eventId, $distance, $name, $gender, $minAge, $maxAge);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'เพิ่มรุ่นการแข่งขันสำเร็จ'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $stmt->error];
        }
        $stmt->close();
        header("Location: ../index.php?page=admin_event_categories&event_id=$eventId");
        exit();

    case 'delete_category':
        $id = intval($_GET['id']);
        $eventId = $_GET['event_id'];

        $stmt = $conn->prepare("DELETE FROM race_categories WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'ลบรุ่นการแข่งขันสำเร็จ'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $stmt->error];
        }
        $stmt->close();
        header("Location: ../index.php?page=admin_event_categories&event_id=$eventId");
        exit();
        
    default:
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unknown admin action.'];
        header('Location: ../index.php?page=admin');
        exit();
}

$conn->close();
?>

