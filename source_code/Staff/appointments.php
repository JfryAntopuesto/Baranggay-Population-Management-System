<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once '../../database/database-connection.php';

// Ensure no output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get status from query parameter
    $status = isset($_GET['status']) ? strtoupper($_GET['status']) : 'PENDING';
    
    // Validate status
    if (!in_array($status, ['PENDING', 'APPROVED', 'DECLINED'])) {
        throw new Exception("Invalid status");
    }

    // Get appointments based on status
    $query = "SELECT a.*, u.firstname, u.lastname 
             FROM appointments a 
             JOIN user u ON a.userID = u.userID 
             WHERE a.status = ?
             ORDER BY a.appointment_date, a.appointment_time";

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = [
            'appointmentID' => $row['appointment_id'],
            'userID' => $row['userID'],
            'userName' => $row['firstname'] . ' ' . $row['lastname'],
            'appointment_date' => $row['appointment_date'],
            'appointment_time' => $row['appointment_time'],
            'purpose' => $row['purpose'],
            'created_at' => $row['created_at'],
            'staff_comment' => $row['staff_comment'] ?? null,
            'status' => $row['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $appointments,
        'count' => count($appointments)
    ]);

} catch (Exception $e) {
    error_log("Error in appointments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching appointments'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}