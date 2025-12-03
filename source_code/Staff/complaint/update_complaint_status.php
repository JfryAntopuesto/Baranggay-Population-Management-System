<?php
// update_complaint_status.php
// Handles resolving/declining complaints and moving them between tables

session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include '../../../database/database-connection.php';
include '../../../database/database-operations.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['complaintID']) || !isset($data['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$complaintID = $data['complaintID'];
$status = $data['status'];
$staff_comment = $data['staff_comment'] ?? '';

try {
    // Create database operations instance
    $db = new DatabaseOperations($conn);
    
    // Fetch the complaint
    $sql = "SELECT * FROM complaints WHERE complaintID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $complaintID);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaint = $result->fetch_assoc();
    $stmt->close();

    if (!$complaint) {
        echo json_encode(['error' => 'Complaint not found']);
        exit();
    }

    // Determine target table and notification content
    if ($status === 'resolved') {
        $target_table = 'approved_complaints';
        $notification_content = "Your complaint has been resolved.";
    } elseif ($status === 'declined') {
        $target_table = 'declined_complaints';
        $notification_content = "Your complaint has been declined.";
    } else {
        throw new Exception("Invalid status");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into target table
        $insert_sql = "INSERT INTO $target_table (complaintID, type, message, userID, complained_person, status, created_at, staff_comment) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('sssissss', 
            $complaint['complaintID'],
            $complaint['type'],
            $complaint['message'],
            $complaint['userID'],
            $complaint['complained_person'],
            $status,
            $complaint['created_at'],
            $staff_comment
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into $target_table");
        }
        $stmt->close();

        // Delete from complaints table
        $delete_sql = "DELETE FROM complaints WHERE complaintID = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param('s', $complaintID);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete from complaints table");
        }
        $stmt->close();

        // Add notification with staff comment
        error_log("Adding notification for complaint: " . $notification_content);
        if (!$db->addNotification($complaint['userID'], $notification_content, $staff_comment)) {
            throw new Exception("Failed to add notification");
        }

        // Commit transaction
        $conn->commit();
        error_log("Successfully updated complaint status and added notification");
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in update_complaint_status: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }

} catch (Exception $e) {
    error_log("Error in update_complaint_status.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?> 