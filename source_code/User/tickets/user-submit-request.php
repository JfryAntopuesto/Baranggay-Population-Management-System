<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit();
}

include_once '../../../database/database-connection.php';
include_once '../../../database/database-operations.php';
include_once '../../../factories/core/RequestFactory.php';

$type = isset($_POST['type']) ? trim($_POST['type']) : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : null;
$userID = $_SESSION['userID'];

if (!$type || !$message) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

try {
    // Step 1: Use Factory Method pattern to CREATE the object
    $requestObj = RequestFactory::createRequest($type, $conn);
    
    // Step 2: Set properties on the object
    $requestObj->setType($type);
    $requestObj->setMessage($message);
    $requestObj->setUserID($userID);

    // Step 3: Validate the object
    if (!$requestObj->validate()) {
        echo json_encode(['success' => false, 'message' => 'Request validation failed. Please check your input.']);
        exit();
    }

    // Step 4: Extract data FROM the object (object is now the source of truth)
    $validatedType = $requestObj->getType();
    $validatedMessage = $requestObj->getMessage();
    $validatedUserID = $requestObj->getUserID();
    
    // Get request details for logging
    $details = $requestObj->getRequestDetails();
    error_log("Factory Pattern: Created " . get_class($requestObj) . " object");
    error_log("User " . $validatedUserID . " submitted request: " . json_encode($details));
    error_log("Using object data - Type: " . $validatedType . ", Message: " . $validatedMessage);

    // Step 5: Save to database using data FROM the object (not raw POST data)
    $db = new DatabaseOperations($conn);
    $result = $db->createRequest($validatedType, $validatedMessage, $validatedUserID);

    if ($result) {
        // Include factory pattern information in response
        echo json_encode([
            'success' => true, 
            'message' => 'Request submitted successfully!',
            'requestDetails' => $details,
            'factoryUsed' => true,
            'productClass' => get_class($requestObj),
            'category' => isset($details['category']) ? $details['category'] : 'Unknown'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit request.']);
    }
} catch (Exception $e) {
    error_log("Error in user-submit-request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request.']);
}
