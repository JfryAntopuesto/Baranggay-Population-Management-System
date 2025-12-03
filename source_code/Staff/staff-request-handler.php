<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if staff is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include '../../database/database-connection.php';
include '../../database/database-operations.php';

header('Content-Type: application/json');

// Get the action from the request
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_all_requests') {
    try {
        $db = new DatabaseOperations($conn);
        
        // Get pending requests
        $pending_sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
                       FROM requests r 
                       JOIN user u ON r.userID = u.userID 
                       WHERE r.status = 'pending' 
                       ORDER BY r.created_at DESC";
        $pending_result = $conn->query($pending_sql);
        $pending_requests = [];
        if ($pending_result && $pending_result->num_rows > 0) {
            while ($row = $pending_result->fetch_assoc()) {
                $pending_requests[] = $row;
            }
        }

        // Get finished requests
        $finished_sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
                        FROM approved_requests r 
                        JOIN user u ON r.userID = u.userID 
                        ORDER BY r.created_at DESC";
        $finished_result = $conn->query($finished_sql);
        $finished_requests = [];
        if ($finished_result && $finished_result->num_rows > 0) {
            while ($row = $finished_result->fetch_assoc()) {
                $finished_requests[] = $row;
            }
        }

        // Get declined requests
        $declined_sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
                        FROM declined_requests r 
                        JOIN user u ON r.userID = u.userID 
                        ORDER BY r.created_at DESC";
        $declined_result = $conn->query($declined_sql);
        $declined_requests = [];
        if ($declined_result && $declined_result->num_rows > 0) {
            while ($row = $declined_result->fetch_assoc()) {
                $declined_requests[] = $row;
            }
        }

        echo json_encode([
            'pending' => $pending_requests,
            'finished' => $finished_requests,
            'declined' => $declined_requests
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST request for updating request status
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['requestID'], $data['status'])) {
        echo json_encode(['error' => 'Missing requestID or status']);
        exit();
    }

    $requestID = $data['requestID'];
    $status = $data['status'];
    $staff_comment = isset($data['staff_comment']) ? $data['staff_comment'] : '';

    try {
        $db = new DatabaseOperations($conn);
        
        // Get request data first
        $request_data = $db->getRequestByID($requestID);
        if (!$request_data) {
            throw new Exception("Request not found");
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            if ($status === 'FINISHED') {
                // Insert into approved_requests table
                $insert_sql = "INSERT INTO approved_requests (requestID, type, message, userID, status, created_at, staff_comment)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ississs",
                    $request_data['requestID'],
                    $request_data['type'],
                    $request_data['message'],
                    $request_data['userID'],
                    $status,
                    $request_data['created_at'],
                    $staff_comment
                );
                $insert_stmt->execute();
                $insert_stmt->close();

                // Add notification
                $notification_content = "Your " . $request_data['type'] . " request has been approved.\n\nSTAFF RESPONSE:\n" . $staff_comment;
                $db->addNotification($request_data['userID'], $notification_content, $staff_comment);
            } else if ($status === 'DECLINED') {
                // Insert into declined_requests table
                $insert_sql = "INSERT INTO declined_requests (requestID, type, message, userID, status, created_at, staff_comment)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ississs",
                    $request_data['requestID'],
                    $request_data['type'],
                    $request_data['message'],
                    $request_data['userID'],
                    $status,
                    $request_data['created_at'],
                    $staff_comment
                );
                $insert_stmt->execute();
                $insert_stmt->close();

                // Add notification
                $notification_content = "Your " . $request_data['type'] . " request has been declined.\n\nSTAFF RESPONSE:\n" . $staff_comment;
                $db->addNotification($request_data['userID'], $notification_content, $staff_comment);
            }

            // Delete from requests table
            $delete_sql = "DELETE FROM requests WHERE requestID = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $requestID);
            $delete_stmt->execute();
            $delete_stmt->close();

            // Commit transaction
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

$conn->close();
?> 