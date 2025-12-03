<?php
header('Content-Type: application/json');
include '../../../database/database-connection.php';

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

$sql = "SELECT * FROM requests WHERE requestID = '$requestID'";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}
$row = $result->fetch_assoc();

$insert_sql = "INSERT INTO $target_table (requestID, type, message, userID, status, created_at, staff_comment)
               VALUES ('{$row['requestID']}', '{$row['type']}', '{$row['message']}', '{$row['userID']}', '$status', '{$row['created_at']}', '$staff_comment')
               ON DUPLICATE KEY UPDATE status='$status', staff_comment='$staff_comment'";
$insert_result = $conn->query($insert_sql);

if (!$insert_result) {
    echo json_encode(['success' => false, 'message' => 'Failed to update request status: ' . $conn->error]);
    exit;
}

$delete_sql = "DELETE FROM requests WHERE requestID = '$requestID'";
$conn->query($delete_sql);

echo json_encode(['success' => true]);
$conn->close();
