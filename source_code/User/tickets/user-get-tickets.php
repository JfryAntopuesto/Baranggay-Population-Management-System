<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log('Session contents: ' . print_r($_SESSION, true));

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    error_log('Unauthorized access attempt. Session type: ' . ($_SESSION['user_type'] ?? 'not set'));
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'session' => $_SESSION // Include session data for debugging
    ]);
    exit();
}

if (!isset($_SESSION['userID'])) {
    error_log('No userID in session');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'No user ID found in session',
        'session' => $_SESSION
    ]);
    exit();
}

include "../../../database/database-connection.php";
include "../../../database/database-operations.php";

$request = new DatabaseOperations($conn);
$userID = $_SESSION['userID'];

// Get requests for the current user
$requests = $request->getRequestsByUserID($userID);

// Log the requests for debugging
error_log('Requests retrieved for user ' . $userID . ': ' . print_r($requests, true));

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'requests' => $requests
]);
?>