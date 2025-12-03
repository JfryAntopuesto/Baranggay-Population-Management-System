<?php
// Prevent any output before JSON response
ob_start();

// Set error handling to return JSON instead of HTML
function handleError($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $errstr]);
    exit;
}
set_error_handler('handleError');

// Set headers first
header('Content-Type: application/json');

try {
    include "../../database/database-connection.php";
    include "../../database/database-operations.php";

    // Get total population (households + members)
    $total_sql = "SELECT 
        (SELECT COUNT(*) FROM household) + 
        (SELECT COUNT(*) FROM members) as total";
    $total_result = $conn->query($total_sql);
    if (!$total_result) {
        throw new Exception('Error getting total population: ' . $conn->error);
    }
    
    $total = $total_result->fetch_assoc()['total'];

    // Clear any output buffer
    ob_end_clean();
    
    // Send JSON response
    echo json_encode(['total' => (int)$total]);

} catch (Exception $e) {
    // Clear any output buffer
    ob_end_clean();
    
    // Send error response
    echo json_encode(['error' => $e->getMessage()]);
} 