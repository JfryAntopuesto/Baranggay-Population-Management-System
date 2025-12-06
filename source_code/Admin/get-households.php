<?php
// Prevent any output before JSON
ob_start();

// Set error handling to return JSON instead of HTML
function handleError($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $errstr]);
    exit;
}
set_error_handler('handleError');

// Set headers first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    ob_clean();
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    include "../../database/database-connection.php";
    include "../../database/database-operations.php";

    $db = new DatabaseOperations($conn);

    // Get purokID from query parameter
    if (!isset($_GET['purokID'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Purok ID is required']);
        exit();
    }

    $purokID = intval($_GET['purokID']);

    if ($purokID <= 0) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Purok ID']);
        exit();
    }

    // Get all households for the specified purok (use large limit to get all)
    // The method signature is: getHouseholdsByPurok($purokID, $offset = 0, $limit = 10)
    // We'll use a large limit to get all households
    $households = $db->getHouseholdsByPurok($purokID, 0, 10000);

    // Format the response - only include fields that exist
    $response = [];
    foreach ($households as $household) {
        $response[] = [
            'householdID' => $household['householdID'],
            'household_head' => isset($household['household_head']) ? $household['household_head'] : 'Unknown'
        ];
    }

    // Clear any output buffer and output JSON
    ob_clean();
    echo json_encode($response);
    exit();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
