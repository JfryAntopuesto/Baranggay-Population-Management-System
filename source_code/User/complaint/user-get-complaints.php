<?php
error_log("Executing user-get-complaints.php");
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include "../../../database/database-connection.php";
include "../../../database/database-operations.php";

header('Content-Type: application/json');

try {
    $complaint = new DatabaseOperations($conn);
    $userID = $_SESSION['userID'];
    $complaints = $complaint->getComplaintsByUserID($userID);
    
    echo json_encode([
        'success' => true,
        'complaints' => $complaints
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch complaints: ' . $e->getMessage()
    ]);
}
?> 