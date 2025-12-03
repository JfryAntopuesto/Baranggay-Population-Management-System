<?php
// TEMP: Enable PHP error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
include '../../../database/database-connection.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['complaintID'], $data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing complaintID or status.']);
    exit;
}

$complaintID = $conn->real_escape_string($data['complaintID']);
$status = $conn->real_escape_string($data['status']);
$staff_comment = isset($data['staff_comment']) ? $conn->real_escape_string($data['staff_comment']) : '';

// Determine which table to move the complaint to
if ($status === 'resolved') {
    $target_table = 'approved_complaints';
} elseif ($status === 'declined') {
    $target_table = 'declined_complaints';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Fetch the complaint from the complaints table
$sql = "SELECT * FROM complaints WHERE complaintID = '$complaintID'";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
    exit;
}
$row = $result->fetch_assoc();

// Insert into the target table
$insert_sql = "INSERT INTO $target_table (complaintID, type, message, userID, complained_person, status, created_at, staff_comment)
               VALUES ('{$row['complaintID']}', '{$row['type']}', '{$row['message']}', '{$row['userID']}', '{$row['complained_person']}', '$status', '{$row['created_at']}', '$staff_comment')
               ON DUPLICATE KEY UPDATE status='$status', staff_comment='$staff_comment'";
$insert_result = $conn->query($insert_sql);

if (!$insert_result) {
    echo json_encode(['success' => false, 'message' => 'Failed to update complaint status: ' . $conn->error]);
    exit;
}

// Delete from the complaints table if moving to another table
$delete_sql = "DELETE FROM complaints WHERE complaintID = '$complaintID'";
$conn->query($delete_sql);

echo json_encode(['success' => true]);
$conn->close();
