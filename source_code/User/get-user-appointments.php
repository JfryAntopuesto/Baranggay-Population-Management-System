<?php
// Start output buffering to prevent any output before JSON
ob_start();

// Suppress error display for JSON responses
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set custom error handler to prevent HTML output
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error in get-user-appointments.php: [$errno] $errstr in $errfile on line $errline");
    return true; // Suppress default error handler
}
set_error_handler('jsonErrorHandler');

session_start();

try {
    // Enable mysqli exception mode to catch SQL errors BEFORE including database files
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    include '../../database/database-connection.php';
    include '../../database/database-operations.php';
    
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $user_id = $_SESSION['userID'] ?? null;
    if (!$user_id) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    // Fetch pending appointments
    $sql_pending = "SELECT appointment_id, appointment_date, appointment_time, purpose 
                    FROM appointments 
                    WHERE userID = ? AND status = 'pending' 
                    ORDER BY appointment_date DESC, appointment_time DESC";
    $stmt_pending = $conn->prepare($sql_pending);
    if (!$stmt_pending) {
        throw new Exception('Failed to prepare pending query: ' . $conn->error);
    }
    $stmt_pending->bind_param("i", $user_id);
    $stmt_pending->execute();
    $result_pending = $stmt_pending->get_result();
    $pending = [];
    while ($row = $result_pending->fetch_assoc()) {
        $pending[] = $row;
    }

    // Fetch approved appointments
    $sql_approved = "SELECT appointment_id, appointment_date, appointment_time, purpose, staff_comment 
                     FROM appointments 
                     WHERE userID = ? AND status = 'approved' 
                     ORDER BY updated_at DESC";
    $stmt_approved = $conn->prepare($sql_approved);
    if (!$stmt_approved) {
        throw new Exception('Failed to prepare approved query: ' . $conn->error);
    }
    $stmt_approved->bind_param("i", $user_id);
    $stmt_approved->execute();
    $result_approved = $stmt_approved->get_result();
    $approved = [];
    while ($row = $result_approved->fetch_assoc()) {
        $approved[] = $row;
    }

    // Fetch declined appointments
    $sql_declined = "SELECT appointment_id, appointment_date, appointment_time, purpose, staff_comment 
                     FROM appointments 
                     WHERE userID = ? AND status = 'declined' 
                     ORDER BY updated_at DESC";
    $stmt_declined = $conn->prepare($sql_declined);
    if (!$stmt_declined) {
        throw new Exception('Failed to prepare declined query: ' . $conn->error);
    }
    $stmt_declined->bind_param("i", $user_id);
    $stmt_declined->execute();
    $result_declined = $stmt_declined->get_result();
    $declined = [];
    while ($row = $result_declined->fetch_assoc()) {
        $declined[] = $row;
    }

    // Clean output buffer and send JSON
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'pending' => $pending,
        'approved' => $approved,
        'declined' => $declined
    ]);
    
} catch (mysqli_sql_exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    error_log("MySQL Error in get-user-appointments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred while fetching appointments'
    ]);
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    error_log("Exception in get-user-appointments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching appointments'
    ]);
} catch (Error $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    error_log("Fatal error in get-user-appointments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'A fatal error occurred'
    ]);
} 