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

    // Get purok by ID
    $purok = $db->getPurokById($purokID);

    if (!$purok) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['error' => 'Purok not found']);
        exit();
    }

    // Clear any output buffer and output JSON
    ob_clean();
    echo json_encode($purok);
    exit();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>

