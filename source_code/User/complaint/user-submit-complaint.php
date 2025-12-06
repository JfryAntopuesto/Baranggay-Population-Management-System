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
        // Step 1: Use Factory Method pattern to CREATE the object
        include_once '../../../factories/core/ComplaintFactory.php';
        
        $complaintObj = ComplaintFactory::createComplaint($type, $conn);
        
        // Step 2: Set properties on the object
        $complaintObj->setType($type);
        $complaintObj->setMessage($message);
        $complaintObj->setUserID($userID);
        $complaintObj->setComplainedPerson($complainedPerson);

        // Step 3: Validate the object
        if (!$complaintObj->validate()) {
            echo json_encode(['success' => false, 'message' => 'Complaint validation failed. Please check your input.']);
            exit();
        }

        // Step 4: Extract data FROM the object (object is now the source of truth)
        $validatedType = $complaintObj->getType();
        $validatedMessage = $complaintObj->getMessage();
        $validatedUserID = $complaintObj->getUserID();
        $validatedComplainedPerson = $complaintObj->getComplainedPerson();
        
        // Get complaint details and severity from the object
        $details = $complaintObj->getComplaintDetails();
        $severity = $complaintObj->getSeverityLevel();
        
        error_log("Factory Pattern: Created " . get_class($complaintObj) . " object");
        error_log("User " . $validatedUserID . " filed complaint against " . $validatedComplainedPerson . 
                  " with severity: " . $severity . " - " . json_encode($details));
        error_log("Using object data - Type: " . $validatedType . ", Message: " . $validatedMessage);

        // Step 5: Save to database using data FROM the object (not raw POST data)
        $complaint = new DatabaseOperations($conn);
        $result = $complaint->createComplaint($validatedType, $validatedMessage, $validatedUserID, $validatedComplainedPerson);
        
        if ($result) {
            // Log high-severity complaints for immediate staff attention
            if ($severity === 'high') {
                error_log("HIGH SEVERITY COMPLAINT FILED: " . $result);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Complaint submitted successfully!',
                'severity' => $severity,
                'needsAttention' => ($severity === 'high'),
                'factoryUsed' => true,
                'productClass' => get_class($complaintObj),
                'complaintDetails' => $details,
                'category' => isset($details['category']) ? $details['category'] : 'Unknown'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to submit complaint'
            ]);
        }
    } catch (Exception $e) {
        error_log("Error in user-submit-complaint.php: " . $e->getMessage());
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