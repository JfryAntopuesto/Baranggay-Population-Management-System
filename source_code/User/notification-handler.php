<?php
session_start();
require_once '../../database/database-operations.php';
require_once '../../database/database-connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$db = new DatabaseOperations($conn);

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    error_log("No user session found");
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Handle GET request for fetching notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    error_log("Fetching notifications for user ID: " . $_SESSION['userID']);
    
    try {
        $notifications = $db->getUserNotifications($_SESSION['userID']);
        error_log("Retrieved notifications: " . json_encode($notifications));
        
        // Ensure notifications is an array
        if (!is_array($notifications)) {
            error_log("Notifications is not an array: " . gettype($notifications));
            $notifications = [];
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
    } catch (Exception $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch notifications',
            'debug' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle POST request for marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    error_log("Received POST request with action: " . $_POST['action']);
    
    if ($_POST['action'] === 'mark_read' && isset($_POST['notifID'])) {
        error_log("Marking notification as read: " . $_POST['notifID']);
        try {
            // Validate notification ID
            if (!is_numeric($_POST['notifID'])) {
                throw new Exception('Invalid notification ID: ' . $_POST['notifID']);
            }

            // Check if notification exists and belongs to the current user
            $check_query = "SELECT userID FROM unread_notifications WHERE notifID = ?";
            $check_stmt = $conn->prepare($check_query);
            if (!$check_stmt) {
                throw new Exception("Failed to prepare check statement: " . $conn->error);
            }
            
            $check_stmt->bind_param("i", $_POST['notifID']);
            if (!$check_stmt->execute()) {
                throw new Exception("Failed to execute check statement: " . $check_stmt->error);
            }
            
            $result = $check_stmt->get_result();
            $notification = $result->fetch_assoc();
            
            if (!$notification) {
                throw new Exception("Notification not found or already marked as read");
            }
            
            if ($notification['userID'] != $_SESSION['userID']) {
                throw new Exception("Unauthorized access to notification");
            }

            $success = $db->markNotificationAsRead($_POST['notifID']);
            error_log("Mark as read result: " . ($success ? 'success' : 'failed'));
            
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to mark notification as read');
            }
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Failed to mark notification as read',
                'debug' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// If no valid action was found
error_log("Invalid request received");
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?> 