<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);

// Get purokID from query parameter
if (!isset($_GET['purokID'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Purok ID is required']);
    exit();
}

$purokID = $_GET['purokID'];

// Get households for the specified purok
$households = $db->getHouseholdsByPurok($purokID);

// Format the response
$response = [];
foreach ($households as $household) {
    $response[] = [
        'householdID' => $household['householdID'],
        'household_head' => $household['household_head'],
        'member_count' => $household['member_count']
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
