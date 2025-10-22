<?php
// actions/search_runner_public.php
// Handles the public search for paid runners by name or BIB number.

require_once '../config.php';
require_once '../functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    validate_csrf_token();

    $search_query = isset($_POST['search_query']) ? trim($_POST['search_query']) : '';
    
    // Store the query to display it back on the search page
    $_SESSION['last_query_public'] = $search_query;

    if (empty($search_query)) {
        $_SESSION['search_error_public'] = "กรุณากรอกข้อมูลเพื่อค้นหา";
        header('Location: ../index.php?page=search_runner');
        exit;
    }

    try {
        $like_query = "%{$search_query}%";

        // Prepare a statement to search by first name, last name, or BIB number
        // Only fetch runners who are paid and have a BIB number.
        $stmt = $mysqli->prepare("
            SELECT 
                r.title, r.first_name, r.last_name, r.bib_number,
                d.name AS distance_name,
                e.name AS event_name
            FROM registrations r
            JOIN distances d ON r.distance_id = d.id
            JOIN events e ON r.event_id = e.id
            WHERE 
                (r.first_name LIKE ? OR r.last_name LIKE ? OR r.bib_number LIKE ?)
                AND r.status = 'ชำระเงินแล้ว'
            ORDER BY e.start_date DESC, r.first_name ASC
            LIMIT 50 
        ");
        $stmt->bind_param("sss", $like_query, $like_query, $like_query);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['search_results_public'] = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            // Set results to an empty array to indicate a search was performed but found nothing
            $_SESSION['search_results_public'] = []; 
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Public runner search error: " . $e->getMessage());
        $_SESSION['search_error_public'] = "เกิดข้อผิดพลาดในระบบขณะค้นหาข้อมูล";
    }

} else {
    $_SESSION['search_error_public'] = "Invalid request method.";
}

// Redirect back to the search page to display results or errors
header('Location: ../index.php?page=search_runner');
exit;
?>
