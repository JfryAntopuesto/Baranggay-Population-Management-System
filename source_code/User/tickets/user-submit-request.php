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

$type = isset($_POST['type']) ? trim($_POST['type']) : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : null;
$userID = $_SESSION['userID'];

if (!$type || !$message) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

$request = new DatabaseOperations($conn);
$result = $request->createRequest($type, $message, $userID);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Request submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit request.']);
}
