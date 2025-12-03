<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
// Use default PHP error log
ini_set('error_log', 'php_errors.log');

error_log('staff-dashboard-handler.php accessed.'); // Log access

// Set JSON content type
header('Content-Type: application/json');

// Check if staff is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    error_log('Unauthorized access attempt to staff-dashboard-handler.php');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Database connection
include '../../database/database-connection.php';
include '../../database/database-operations.php';

// Check if database connection is successful
if (!$conn) {
    error_log('Database connection failed in staff-dashboard-handler.php');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Create an instance of DatabaseOperations
$db = new DatabaseOperations($conn);

$data = [
    'pendingRequests' => 0,
    'pendingAppointments' => 0,
    'pendingComplaints' => 0,
    'recentAnnouncements' => []
];

try {
    error_log('Fetching counts and announcements...'); // Log before fetching

    // Get pending requests count (count rows in the requests table)
    $sql = "SELECT COUNT(*) as count FROM requests";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $data['pendingRequests'] = $row['count'];
        error_log('Fetched pending requests count: ' . $data['pendingRequests']);
    }

    // Get pending appointments count (count rows in the appointments table)
    $sql = "SELECT COUNT(*) as count FROM appointments";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $data['pendingAppointments'] = $row['count'];
        error_log('Fetched pending appointments count: ' . $data['pendingAppointments']);
    }

    // Get pending complaints count (count rows in the complaints table)
    $sql = "SELECT COUNT(*) as count FROM complaints";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $data['pendingComplaints'] = $row['count'];
        error_log('Fetched pending complaints count: ' . $data['pendingComplaints']);
    }

    // Get recent announcements
    $sql = "SELECT content, datetime FROM announcement ORDER BY datetime DESC LIMIT 5";
    $result = $conn->query($sql);
    $announcements = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }

    // Get barangay name (assuming there's only one entry in baranggay_profile)
    $barangay_name = '';
    $sql_barangay = "SELECT baranggay_name FROM baranggay_profile LIMIT 1";
    $result_barangay = $conn->query($sql_barangay);
    if ($result_barangay && $result_barangay->num_rows > 0) {
        $row_barangay = $result_barangay->fetch_assoc();
        $barangay_name = $row_barangay['baranggay_name'];
    }

    // Add barangay name as title to each announcement
    foreach ($announcements as &$announcement) {
        $announcement['title'] = $barangay_name;
    }
    unset($announcement); // Break the reference

    $data['recentAnnouncements'] = $announcements;
    error_log('Fetched ' . count($data['recentAnnouncements']) . ' recent announcements with barangay name.');

    // Send the response
    echo json_encode($data);
    error_log('Response sent successfully.'); // Log successful response

} catch (Exception $e) {
    error_log('Error in staff-dashboard-handler.php: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while fetching data: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 