<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include "../../../database/database-connection.php";
include "../../../database/database-operations.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = isset($_POST['type']) ? $_POST['type'] : null;
    $message = isset($_POST['message']) ? $_POST['message'] : null;
    $complainedPerson = isset($_POST['complained_person']) ? $_POST['complained_person'] : null;
    $userID = $_SESSION['userID'];

    // Get user's full name
    $user_sql = "SELECT CONCAT(firstname, ' ', lastname) as full_name FROM user WHERE userID = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $userID);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_full_name = $user_data['full_name'];

    // Validate required fields
    if (empty($type) || empty($message) || empty($complainedPerson)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }

    // Remove all spaces from both names for comparison
    $complainedPersonNoSpaces = preg_replace('/\s+/', '', $complainedPerson);
    $userFullNameNoSpaces = preg_replace('/\s+/', '', $user_full_name);

    // Check if user is trying to complain about themselves
    if (strcasecmp($complainedPersonNoSpaces, $userFullNameNoSpaces) === 0) {
        echo json_encode(['success' => false, 'message' => 'Error: You cannot file a complaint against yourself.']);
        exit();
    }

    // Validate full name format (after removing extra spaces)
    $nameParts = explode(' ', trim(preg_replace('/\s+/', ' ', $complainedPerson)));
    if (count($nameParts) < 2) {
        echo json_encode(['success' => false, 'message' => 'Please enter both first name and last name of the person you are complaining about']);
        exit();
    }

    try {
        $complaint = new DatabaseOperations($conn);
        $result = $complaint->createComplaint($type, $message, $userID, $complainedPerson);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Complaint submitted successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to submit complaint'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error submitting complaint: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
} 