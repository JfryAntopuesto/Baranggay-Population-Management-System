<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Database connection
include '../database/database-connection.php';
include '../database/database-operations.php';

// Create an instance of DatabaseOperations
$db = new DatabaseOperations($conn);

// Handle GET request to get notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    try {
        $userID = $_SESSION['userID'];
        
        // Get both unread and read notifications
        $unreadNotifications = $db->getUserNotifications($userID);
        $readNotifications = $db->getReadNotifications($userID);
        
        // Combine notifications and mark their status
        $allNotifications = array_merge(
            array_map(function($n) { 
                $n['is_read'] = false; 
                return $n; 
            }, $unreadNotifications),
            array_map(function($n) { 
                $n['is_read'] = true; 
                return $n; 
            }, $readNotifications)
        );
        
        // Sort by datetime, most recent first
        usort($allNotifications, function($a, $b) {
            return strtotime($b['datetime']) - strtotime($a['datetime']);
        });
        
        echo json_encode($allNotifications);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// Handle POST request to mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    try {
        if (!isset($_POST['notifID'])) {
            throw new Exception('Notification ID is required');
        }

        $notifID = $_POST['notifID'];
        
        if ($db->markNotificationAsRead($notifID)) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Failed to mark notification as read');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// If no valid action is provided
echo json_encode(['error' => 'Invalid request']);
exit();
?> 