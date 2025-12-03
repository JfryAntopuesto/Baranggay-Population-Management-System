<?php
header('Content-Type: application/json');
include '../../../database/database-connection.php';
include '../../../database/database-operations.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['requestID'], $data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing requestID or status.']);
    exit;
}

$requestID = $conn->real_escape_string($data['requestID']);
$status = $conn->real_escape_string($data['status']);
$staff_comment = isset($data['staff_comment']) ? $conn->real_escape_string($data['staff_comment']) : '';

if ($status === 'FINISHED') {
    $target_table = 'approved_requests';
} elseif ($status === 'DECLINED') {
    $target_table = 'declined_requests';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Get request data first
$sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
        FROM requests r 
        JOIN user u ON r.userID = u.userID 
        WHERE r.requestID = '$requestID'";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}
$row = $result->fetch_assoc();

// Start transaction
$conn->begin_transaction();

try {
    // Insert into target table
    $insert_sql = "INSERT INTO $target_table (requestID, type, message, userID, status, created_at, staff_comment)
                   VALUES ('{$row['requestID']}', '{$row['type']}', '{$row['message']}', '{$row['userID']}', '$status', '{$row['created_at']}', '$staff_comment')";
    $insert_result = $conn->query($insert_sql);

    if (!$insert_result) {
        throw new Exception('Failed to update request status: ' . $conn->error);
    }

    // Add notification
    $db = new DatabaseOperations($conn);
    $notification_content = "Your " . $row['type'] . " request has been " . 
                          ($status === 'FINISHED' ? "approved" : "declined");
    
    if (!$db->addNotification($row['userID'], $notification_content, $staff_comment)) {
        throw new Exception('Failed to add notification');
    }

    // Delete from requests table
    $delete_sql = "DELETE FROM requests WHERE requestID = '$requestID'";
    if (!$conn->query($delete_sql)) {
        throw new Exception('Failed to delete original request');
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error in update_request_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
