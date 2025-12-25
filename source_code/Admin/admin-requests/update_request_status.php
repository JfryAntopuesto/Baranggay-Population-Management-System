<?php
header('Content-Type: application/json');
include '../../../database/database-connection.php';
include '../../../database/database-operations.php';
include '../../../includes/email-helper.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['requestID'], $data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing requestID or status.']);
    exit;
}

$requestID = $conn->real_escape_string($data['requestID']);
$status = $conn->real_escape_string($data['status']);
$staff_comment = isset($data['staff_comment']) ? $conn->real_escape_string($data['staff_comment']) : '';

// Map FINISHED to approved, DECLINED to declined for database
if ($status === 'FINISHED') {
    $dbStatus = 'approved';
} elseif ($status === 'DECLINED') {
    $dbStatus = 'declined';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

$sql = "SELECT r.*, u.email, u.email_notifications 
        FROM requests r 
        JOIN user u ON r.userID = u.userID 
        WHERE r.requestID = '$requestID'";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}
$row = $result->fetch_assoc();

// Update status in the same requests table
$update_sql = "UPDATE requests 
               SET status = '$dbStatus', 
                   staff_comment = '$staff_comment',
                   updated_at = NOW() 
               WHERE requestID = '$requestID'";
$update_result = $conn->query($update_sql);

if (!$update_result) {
    echo json_encode(['success' => false, 'message' => 'Failed to update request status: ' . $conn->error]);
    exit;
}

// Add notification
$db = new DatabaseOperations($conn);
$notification_content = "Your " . $row['type'] . " request has been " . $dbStatus;
$db->addNotification($row['userID'], $notification_content, $staff_comment);

// Send email notification if user has enabled email notifications
// Check email_notifications (can be 0/1 or true/false)
$emailNotificationsEnabled = ($row['email_notifications'] == 1 || $row['email_notifications'] === true || $row['email_notifications'] === '1');
$hasEmail = !empty($row['email']);

error_log("Email notification check - Enabled: " . ($emailNotificationsEnabled ? 'yes' : 'no') . ", Has Email: " . ($hasEmail ? 'yes' : 'no'));

if ($emailNotificationsEnabled && $hasEmail) {
    try {
        error_log("Attempting to send email to: " . $row['email']);
        $emailHelper = new EmailHelper();
        $emailSent = $emailHelper->sendRequestStatusEmail(
            $row['email'],
            $row['type'],
            $dbStatus,
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
