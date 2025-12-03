<?php
session_start();
header('Content-Type: application/json');
include '../../database/database-connection.php';
include '../../database/database-operations.php';

$user_id = $_SESSION['userID'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Fetch pending
$sql_pending = "SELECT appointment_id, appointment_date, appointment_time, purpose FROM appointments WHERE userID = ? ORDER BY appointment_date DESC, appointment_time DESC";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
$pending = [];
while ($row = $result_pending->fetch_assoc()) {
    $pending[] = $row;
}

// Fetch approved
$sql_approved = "SELECT appointment_id, appointment_date, appointment_time, purpose, staff_comment FROM approved_appointments WHERE userID = ? ORDER BY approved_at DESC";
$stmt_approved = $conn->prepare($sql_approved);
$stmt_approved->bind_param("i", $user_id);
$stmt_approved->execute();
$result_approved = $stmt_approved->get_result();
$approved = [];
while ($row = $result_approved->fetch_assoc()) {
    $approved[] = $row;
}

// Fetch declined
$sql_declined = "SELECT appointment_id, appointment_date, appointment_time, purpose, staff_comment FROM declined_appointments WHERE userID = ? ORDER BY declined_at DESC";
$stmt_declined = $conn->prepare($sql_declined);
$stmt_declined->bind_param("i", $user_id);
$stmt_declined->execute();
$result_declined = $stmt_declined->get_result();
$declined = [];
while ($row = $result_declined->fetch_assoc()) {
    $declined[] = $row;
}

echo json_encode([
    'success' => true,
    'pending' => $pending,
    'approved' => $approved,
    'declined' => $declined
]); 