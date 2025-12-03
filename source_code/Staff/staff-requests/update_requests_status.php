<?php
// update_request_status.php
// Handles approving/declining requests and moving them between tables with debug output

session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include "../../../database/database-connection.php";
include "../../../database/database-operations.php";

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

error_log("Received data: " . print_r($data, true));

if (!$data || !isset($data['requestID']) || !isset($data['status'])) {
    error_log("Invalid data received");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$requestID = $data['requestID'];
$status = $data['status'];
$staff_comment = $data['staff_comment'] ?? '';

error_log("Processing request ID: " . $requestID . " with status: " . $status);

try {
    $db = new DatabaseOperations($conn);
    
    // Get request data first
    $request_data = $db->getRequestByID($requestID);
    if (!$request_data) {
        throw new Exception("Request not found");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Determine target table and notification content
        if ($status === 'FINISHED') {
            // Insert into approved_requests table
            $insert_sql = "INSERT INTO approved_requests (requestID, type, message, userID, status, created_at, staff_comment)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception('Prepare insert failed: ' . $conn->error);
            }
            $insert_stmt->bind_param("ississs",
                $request_data['requestID'],
                $request_data['type'],
                $request_data['message'],
                $request_data['userID'],
                $status,
                $request_data['created_at'],
                $staff_comment
            );
            if (!$insert_stmt->execute()) {
                throw new Exception('Insert to approved_requests failed: ' . $insert_stmt->error);
            }
            $insert_stmt->close();
            
            // Add notification for finished request
            $notification_content = "Your " . $request_data['type'] . " request has been approved.\n\nSTAFF RESPONSE:\n" . $staff_comment;
            error_log("Adding notification for finished request: " . $notification_content);
            if (!$db->addNotification($request_data['userID'], $notification_content, $staff_comment)) {
                throw new Exception('Failed to add notification for approved request');
            }
            
        } else if ($status === 'DECLINED') {
            // Insert into declined_requests table
            $insert_sql = "INSERT INTO declined_requests (requestID, type, message, userID, status, created_at, staff_comment)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception('Prepare insert declined failed: ' . $conn->error);
            }
            $insert_stmt->bind_param("ississs",
                $request_data['requestID'],
                $request_data['type'],
                $request_data['message'],
                $request_data['userID'],
                $status,
                $request_data['created_at'],
                $staff_comment
            );
            if (!$insert_stmt->execute()) {
                throw new Exception('Insert to declined_requests failed: ' . $insert_stmt->error);
            }
            $insert_stmt->close();
            
            // Add notification for declined request
            $notification_content = "Your " . $request_data['type'] . " request has been declined.\n\nSTAFF RESPONSE:\n" . $staff_comment;
            error_log("Adding notification for declined request: " . $notification_content);
            if (!$db->addNotification($request_data['userID'], $notification_content, $staff_comment)) {
                throw new Exception('Failed to add notification for declined request');
            }
        } else {
            throw new Exception("Invalid status");
        }
        
        // Delete from requests table
        $delete_sql = "DELETE FROM requests WHERE requestID = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param('i', $requestID);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete from requests table");
        }
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        error_log("Request status updated and notification added successfully");
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error updating request status: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} catch (Exception $e) {
    error_log("Error in update_request_status.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>