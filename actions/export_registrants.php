<?php
// actions/export_registrants.php
// Script to export registrant data to a CSV file

// --- CORE BOOTSTRAP ---
require_once '../config.php';
require_once '../functions.php';

// --- Session Check & Permission ---
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../admin/login.php'); 
    exit;
}
$staff_info = $_SESSION['staff_info'];
$is_super_admin = ($staff_info['role'] === 'admin');

// --- Get Event ID and verify access ---
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("Invalid Event ID.");
}
$event_id = intval($_GET['event_id']);

// Security Check
if (!$is_super_admin && $event_id !== $staff_info['assigned_event_id']) {
    die("You do not have permission to access this data.");
}

// Get search term if provided
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- Fetch Data ---
$base_query = "FROM registrations r JOIN distances d ON r.distance_id = d.id JOIN events e ON r.event_id = e.id WHERE r.event_id = ?";
$where_clause = '';
$params = [$event_id];
$types = 'i';

if (!empty($search_term)) {
    $where_clause = " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.email LIKE ?)";
    $like_term = "%{$search_term}%";
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= 'sss';
}

$select_fields = "SELECT 
    r.registration_code, r.bib_number, r.status, 
    r.title, r.first_name, r.last_name, 
    r.email, r.phone, r.line_id, 
    r.thai_id, r.shirt_size, 
    d.name as distance_name, 
    r.disease, r.disease_detail,
    r.registered_at
";

$query = $select_fields . $base_query . $where_clause . " ORDER BY r.registered_at ASC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- Generate CSV ---
$filename = "registrants_event_" . $event_id . "_" . date('Ymd') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Prepend BOM to force Excel to read UTF-8 correctly for Thai characters
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Add header row
$header = [
    'Registration Code', 'BIB', 'Status', 'Title', 'First Name', 'Last Name', 
    'Email', 'Phone', 'Line ID', 'Thai ID', 'Shirt Size', 'Distance',
    'Disease', 'Disease Detail', 'Registered At'
];
fputcsv($output, $header);

// Add data rows from database
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$stmt->close();
$mysqli->close();
exit;
?>
