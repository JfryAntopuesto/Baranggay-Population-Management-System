<?php
session_start();
include '../../database/database-connection.php';
include '../../database/database-operations.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['modID']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$appointment_date = $data['appointment_date'] ?? '';
$appointment_time = $data['appointment_time'] ?? '';
$current_appointment_id = $data['appointment_id'] ?? null;

if (!$appointment_date || !$appointment_time) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $db = new DatabaseOperations($conn);
    
    // Check for existing approved appointments at the same date and time
    $sql = "SELECT COUNT(*) as count FROM approved_appointments 
            WHERE appointment_date = ? AND appointment_time = ? 
            AND appointment_id != ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $appointment_date, $appointment_time, $current_appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'hasConflict' => $row['count'] > 0
    ]);

} catch (Exception $e) {
    error_log("Error in check-appointment-conflict.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred while checking for conflicts']);
}

$conn->close();
?> 