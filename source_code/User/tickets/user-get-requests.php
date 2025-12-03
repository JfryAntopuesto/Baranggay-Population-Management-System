<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include "../../../database/database-connection.php";
include "../../../database/database-operations.php";

try {
    $db = new DatabaseOperations($conn);
    $userID = $_SESSION['userID'];
    
    // Get requests for the user
    $requests = $db->getRequestsByUserID($userID);
    
    // Return success response with requests
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
} catch (Exception $e) {
    // Log the error and return error response
    error_log("Error in user-get-requests.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch requests'
    ]);
}
?> 