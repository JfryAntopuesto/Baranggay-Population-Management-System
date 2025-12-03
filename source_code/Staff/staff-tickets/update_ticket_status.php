<?php
// update_ticket_status.php
// Handles approving/declining tickets and moving them between tables with debug output

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

if (!$data || !isset($data['ticketID']) || !isset($data['status'])) {
    error_log("Invalid data received");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$ticketID = $data['ticketID'];
$status = $data['status'];
$staff_comment = $data['staff_comment'] ?? '';

error_log("Processing ticket ID: " . $ticketID . " with status: " . $status);

try {
    $db = new DatabaseOperations($conn);
    
    // Update ticket status
    $result = $db->updateTicketStatus($ticketID, $status, $staff_comment);
    
    if ($result) {
        error_log("Ticket status updated successfully");
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        error_log("Failed to update ticket status");
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to update ticket status']);
    }
} catch (Exception $e) {
    error_log("Error updating ticket status: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>