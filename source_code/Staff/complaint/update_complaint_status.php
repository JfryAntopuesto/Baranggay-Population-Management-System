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
include '../../../includes/event-listeners.php';
include '../../../includes/email-helper.php';

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

    // Determine notification content
    if ($status === 'resolved') {
        $notification_content = "Your complaint has been resolved.";
    } elseif ($status === 'declined') {
        $notification_content = "Your complaint has been declined.";
    } else {
        throw new Exception("Invalid status");
    }

    // Get user email preferences
    $user_sql = "SELECT email, email_notifications FROM user WHERE userID = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param('i', $complaint['userID']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update status in the same complaints table
        $update_sql = "UPDATE complaints 
                      SET status = ?, 
                          staff_comment = ?,
                          updated_at = NOW() 
                      WHERE complaintID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('sss', $status, $staff_comment, $complaintID);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update complaint status: " . $stmt->error);
        }
        $stmt->close();

        // Add notification with staff comment
        error_log("Adding notification for complaint: " . $notification_content);
        if (!$db->addNotification($complaint['userID'], $notification_content, $staff_comment)) {
            throw new Exception("Failed to add notification");
        }

        // Send email notification via observer if enabled
        if ($user_data) {
            $emailNotificationsEnabled = ($user_data['email_notifications'] == 1 || $user_data['email_notifications'] === true || $user_data['email_notifications'] === '1');
            $hasEmail = !empty($user_data['email']);
            
            if ($emailNotificationsEnabled && $hasEmail) {
                $dispatcher = get_event_dispatcher();
                $dispatcher->dispatch('complaint_status_changed', [
                    'email' => $user_data['email'],
                    'complaintType' => $complaint['type'],
                    'status' => $status,
                    'staff_comment' => $staff_comment
                ]);
            } else {
                error_log("Email notification skipped - Not enabled or no email address");
            }
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