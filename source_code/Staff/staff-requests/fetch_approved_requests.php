<?php
// Prevent any output before headers
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display, we'll handle errors ourselves

include '../../../database/database-connection.php';

// Clear any previous output
ob_clean();

header('Content-Type: application/json');

try {
    // Use the requests table with status filter
    $sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
            FROM requests r 
            JOIN user u ON r.userID = u.userID 
            WHERE r.status IN ('approved', 'FINISHED', 'finished')
            ORDER BY r.created_at DESC";
            
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $approvedTickets = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $approvedTickets[] = $row;
        }
    }
    
    echo json_encode($approvedTickets);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'sql_state' => $conn->sqlstate,
        'errno' => $conn->errno
    ]);
}

$conn->close();
?>
