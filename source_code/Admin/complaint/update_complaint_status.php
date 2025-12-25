<?php
// TEMP: Enable PHP error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
include '../../../database/database-connection.php';
include '../../../database/database-operations.php';
include '../../../includes/email-helper.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['complaintID'], $data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing complaintID or status.']);
    exit;
}

$complaintID = $conn->real_escape_string($data['complaintID']);
$status = $conn->real_escape_string($data['status']);
$staff_comment = isset($data['staff_comment']) ? $conn->real_escape_string($data['staff_comment']) : '';

// Validate status
if ($status !== 'resolved' && $status !== 'declined') {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Fetch the complaint from the complaints table with user email preferences
$sql = "SELECT c.*, u.email, u.email_notifications 
        FROM complaints c 
        JOIN user u ON c.userID = u.userID 
        WHERE c.complaintID = '$complaintID'";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
    exit;
}
$row = $result->fetch_assoc();

// Update status in the same complaints table
$update_sql = "UPDATE complaints 
               SET status = '$status', 
                   staff_comment = '$staff_comment',
                   updated_at = NOW() 
               WHERE complaintID = '$complaintID'";
$update_result = $conn->query($update_sql);

if (!$update_result) {
    echo json_encode(['success' => false, 'message' => 'Failed to update complaint status: ' . $conn->error]);
    exit;
}

// Add notification
$db = new DatabaseOperations($conn);
$notification_content = "Your complaint has been " . $status . ".";
$db->addNotification($row['userID'], $notification_content, $staff_comment);

// Send email notification if user has enabled email notifications
// Check email_notifications (can be 0/1 or true/false)
$emailNotificationsEnabled = ($row['email_notifications'] == 1 || $row['email_notifications'] === true || $row['email_notifications'] === '1');
$hasEmail = !empty($row['email']);

error_log("Email notification check - Enabled: " . ($emailNotificationsEnabled ? 'yes' : 'no') . ", Has Email: " . ($hasEmail ? 'yes' : 'no'));

if ($emailNotificationsEnabled && $hasEmail) {
    try {
        error_log("Attempting to send complaint email to: " . $row['email']);
        $emailHelper = new EmailHelper();
        $emailSent = $emailHelper->sendComplaintStatusEmail(
            $row['email'],
            $row['type'],
            $status,
            $staff_comment
        );
        error_log("Email send result: " . ($emailSent ? 'success' : 'failed'));
    } catch (Exception $e) {
        error_log("Failed to send email notification: " . $e->getMessage());
        // Don't fail if email fails
    }
} else {
    error_log("Email notification skipped - Not enabled or no email address");
}

echo json_encode(['success' => true]);
$conn->close();
