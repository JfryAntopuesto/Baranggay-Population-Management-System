<?php
session_start();
header('Content-Type: application/json');

// Database connection
include '../database/database-connection.php';
include '../database/database-operations.php';

// Create an instance of DatabaseOperations
$db = new DatabaseOperations($conn);

// Handle AJAX request to get announcements
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_announcements') {
    try {
        $announcements = $db->getAnnouncements();
        
        // Add user's like status and mark as seen for each announcement
        if (isset($_SESSION['userID'])) {
            foreach ($announcements as &$announcement) {
                $announcement['user_has_liked'] = $db->hasUserLikedAnnouncement($announcement['annID'], $_SESSION['userID']);
                // Mark announcement as seen
                $db->markAnnouncementAsSeen($announcement['annID'], $_SESSION['userID']);
            }
        }
        
        echo json_encode($announcements);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// Handle AJAX request to toggle like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit();
    }

    try {
        $annID = $_POST['annID'];
        $userID = $_SESSION['userID'];
        
        if ($db->toggleAnnouncementLike($annID, $userID)) {
            // Get updated like count
            $announcements = $db->getAnnouncements();
            $updatedAnnouncement = null;
            foreach ($announcements as $ann) {
                if ($ann['annID'] == $annID) {
                    $updatedAnnouncement = $ann;
                    $updatedAnnouncement['user_has_liked'] = $db->hasUserLikedAnnouncement($annID, $userID);
                    break;
                }
            }
            echo json_encode(['success' => true, 'announcement' => $updatedAnnouncement]);
        } else {
            throw new Exception("Failed to update like");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle AJAX request to save announcement (staff only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_announcement') {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
        echo json_encode(['error' => 'Unauthorized access']);
        exit();
    }

    try {
        $content = $_POST['content'];
        if ($db->addAnnouncement($content)) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to save announcement");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?> 