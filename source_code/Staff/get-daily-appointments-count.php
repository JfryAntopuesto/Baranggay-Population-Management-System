<?php
session_start();
include '../../database/database-connection.php';
include '../../database/database-operations.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['modID']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila'); // Set your timezone
$current_date = date('Y-m-d');

try {
    $db = new DatabaseOperations($conn);
    $count = $db->getAppointmentsCountForDate($current_date);
    echo json_encode(['success' => true, 'count' => $count]);

} catch (Exception $e) {
    error_log("Error fetching daily appointment count: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to fetch appointment count']);
}

$conn->close();
?> 