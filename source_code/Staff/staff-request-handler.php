<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Database connection handling
require_once "../../database/database-connection.php";

// Use the $conn variable from database-connection.php
$db = $conn;

try {
    // Check if database connection was established
    if (!isset($db) || !($db instanceof mysqli)) {
        throw new Exception('Database connection not properly initialized');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get the action from the request
$action = $_GET['action'] ?? '';

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
    if ($status === 'finished') {
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
            // Verify required parameters
            if (!isset($_POST['requestID'], $_POST['status'])) {
                throw new Exception('Missing required parameters');
            }
            
            $requestId = $_POST['requestID'];
            $status = $_POST['status'];
            $staffNotes = $_POST['staff_comment'] ?? '';
            
            // Get staff ID from session (you'll need to set this when staff logs in)
            session_start();
            $staffId = $_SESSION['modID'] ?? null;
            
            // Validate status
            $validStatuses = ['pending', 'processing', 'finished', 'declined'];
            if (!in_array(strtolower($status), $validStatuses)) {
                throw new Exception('Invalid status. Must be one of: ' . implode(', ', $validStatuses));
            }
            
            // Prepare the update query
            $query = "UPDATE requests 
                     SET status = ?, 
                         staff_comment = ?,
                         updated_at = NOW() 
                     WHERE requestID = ?";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param('sss', $status, $staffNotes, $requestId);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update request status: ' . $stmt->error);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Request status updated successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Close database connection
    if (isset($db)) {
        $db->close();
    }
}