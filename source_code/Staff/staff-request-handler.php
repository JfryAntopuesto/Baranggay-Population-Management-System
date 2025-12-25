<?php
// Enable error reporting but don't display errors (log them instead)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Start output buffering to catch any unexpected output
ob_start();

// Database connection handling
try {
    require_once "../../database/database-connection.php";
    
    // Check if database connection was established
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Database connection not properly initialized');
    }
    
    // Check if connection is still valid
    if ($conn->connect_error) {
        throw new Exception('Database connection error: ' . $conn->connect_error);
    }
    
    // Use the $conn variable from database-connection.php
    $db = $conn;
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("Database connection error in staff-request-handler.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    ob_end_flush();
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    error_log("Fatal error in staff-request-handler.php (database connection): " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    ob_end_flush();
    exit;
}

// Get the action from the request (GET or JSON body)
$action = $_GET['action'] ?? '';

// If no action in GET, try to get it from JSON body
if (empty($action)) {
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);
    if ($jsonData && isset($jsonData['action'])) {
        $action = $jsonData['action'];
    }
}

// Process the requested action
try {
    switch ($action) {
        case 'get_all_requests':
            // Get filter and search parameters if provided
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            
            // Build the base query
            $query = "SELECT r.*, 
                             u.firstname, 
                             u.middlename, 
                             u.lastname
                      FROM requests r
                      JOIN user u ON r.userID = u.userID
                      WHERE 1=1";
            
            $params = [];
            $types = '';
            
            // Add status filter if provided
            if (!empty($status) && $status !== 'all') {
                $query .= " AND r.status = ?";
                $params[] = $status;
                $types .= 's';
            }
            
            // Add search filter if provided
            if (!empty($search)) {
                $searchTerm = "%$search%";
                $query .= " AND (r.requestID LIKE ? 
                              OR r.type LIKE ? 
                              OR u.firstname LIKE ? 
                              OR u.lastname LIKE ?)";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                $types .= str_repeat('s', 4);
            }
            
            // Add sorting
            $query .= " ORDER BY r.created_at DESC";
            
            // Prepare and execute the query
            $stmt = $db->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute query: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $requests = [];
            while ($row = $result->fetch_assoc()) {
                // Format the name with proper handling of middle name
                $fullName = $row['firstname'];
                if (!empty($row['middlename'])) {
                    $fullName .= ' ' . $row['middlename'];
                }
                $fullName .= ' ' . $row['lastname'];
                
                // Add formatted name to the request data
                $row['requester_name'] = $fullName;
                $requests[] = $row;
            }
            
           // Categorize requests by status
$pending = [];
$finished = [];
$declined = [];

foreach ($requests as $request) {
    $status = strtolower($request['status']);
    if ($status === 'finished' || $status === 'approved') {
        $finished[] = $request;
    } elseif ($status === 'declined') {
        $declined[] = $request;
    } else { // pending and any other status
        $pending[] = $request;
    }
}

echo json_encode([
    'success' => true,
    'pending' => $pending,
    'finished' => $finished,
    'declined' => $declined
]);
            break;
            
        case 'update_request_status':
            // Get JSON data if Content-Type is application/json, otherwise use $_POST
            $input = file_get_contents('php://input');
            $jsonData = json_decode($input, true);
            
            if ($jsonData) {
                // Data sent as JSON
                if (!isset($jsonData['requestID'], $jsonData['status'])) {
                    throw new Exception('Missing required parameters');
                }
                $requestId = $jsonData['requestID'];
                $status = $jsonData['status'];
                $staffNotes = $jsonData['staff_comment'] ?? '';
            } else {
                // Data sent as form data
                if (!isset($_POST['requestID'], $_POST['status'])) {
                    throw new Exception('Missing required parameters');
                }
                $requestId = $_POST['requestID'];
                $status = $_POST['status'];
                $staffNotes = $_POST['staff_comment'] ?? '';
            }
            
            // Validate status - must be FINISHED or DECLINED
            if ($status !== 'FINISHED' && $status !== 'DECLINED') {
                throw new Exception('Invalid status. Must be FINISHED or DECLINED');
            }
            
            // Map FINISHED to approved, DECLINED to declined for database
            $dbStatus = ($status === 'FINISHED') ? 'approved' : 'declined';
            
            // Get request data first
            error_log("Looking for request ID: " . $requestId . " (type: " . gettype($requestId) . ")");
            $query = "SELECT r.*, u.firstname, u.middlename, u.lastname, u.email, u.email_notifications 
                     FROM requests r 
                     JOIN user u ON r.userID = u.userID 
                     WHERE r.requestID = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('s', $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if (!$result || $result->num_rows === 0) {
                error_log("Request not found with ID: " . $requestId);
                throw new Exception('Request not found');
            }
            
            $row = $result->fetch_assoc();
            error_log("Found request. Current status: " . $row['status']);
            $stmt->close();
            
            // Start transaction
            $db->begin_transaction();
            
            try {
                // Update status in the same requests table
                error_log("Updating request ID: $requestId, Status: $dbStatus, Comment: $staffNotes");
                $update_sql = "UPDATE requests 
                              SET status = ?, 
                                  staff_comment = ?,
                                  updated_at = NOW() 
                              WHERE requestID = ?";
                $stmt = $db->prepare($update_sql);
                $stmt->bind_param('sss', $dbStatus, $staffNotes, $requestId);
                
                if (!$stmt->execute()) {
                    error_log("Update failed: " . $stmt->error);
                    throw new Exception('Failed to update request status: ' . $stmt->error);
                }
                
                $affectedRows = $stmt->affected_rows;
                error_log("Update executed. Affected rows: $affectedRows");
                $stmt->close();
                
                if ($affectedRows === 0) {
                    throw new Exception('No rows were updated. Request ID might not exist or status is already set.');
                }
                
                // Add notification
                if (!class_exists('DatabaseOperations')) {
                    require_once "../../database/database-operations.php";
                }
                $dbOps = new DatabaseOperations($db);
                $notification_content = "Your " . $row['type'] . " request has been " . $dbStatus;
                
                if (!$dbOps->addNotification($row['userID'], $notification_content, $staffNotes)) {
                    throw new Exception('Failed to add notification');
                }
                
                // Dispatch event (Observer) for email notification
                require_once "../../includes/event-listeners.php";
                $dispatcher = get_event_dispatcher();
                $emailNotificationsEnabled = ($row['email_notifications'] == 1 || $row['email_notifications'] === true || $row['email_notifications'] === '1');
                if ($emailNotificationsEnabled && !empty($row['email'])) {
                    $dispatcher->dispatch('request_status_changed', [
                        'email' => $row['email'],
                        'requestType' => $row['type'],
                        'status' => $dbStatus,
                        'staff_comment' => $staffNotes
                    ]);
                } else {
                    error_log("Email notification skipped - Not enabled or no email address");
                }
                
                // Commit transaction
                $db->commit();
                
                // Verify the update by fetching the updated request
                $verify_sql = "SELECT requestID, status FROM requests WHERE requestID = ?";
                $verify_stmt = $db->prepare($verify_sql);
                $verify_stmt->bind_param('s', $requestId);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                $updated_request = $verify_result->fetch_assoc();
                $verify_stmt->close();
                
                error_log("Verification - Request ID: " . $updated_request['requestID'] . ", Status: " . $updated_request['status']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Request status updated successfully',
                    'requestID' => $requestId,
                    'newStatus' => $updated_request['status']
                ]);
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollback();
                throw $e;
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    http_response_code(400);
    error_log("Error in staff-request-handler.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    // Catch PHP fatal errors
    ob_clean();
    
    http_response_code(500);
    error_log("Fatal error in staff-request-handler.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An internal error occurred. Please check the server logs.'
    ]);
} finally {
    // End output buffering
    ob_end_flush();
    
    // Close database connection
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}