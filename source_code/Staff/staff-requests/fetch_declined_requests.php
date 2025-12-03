<?php
// Prevent any output before headers
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display, we'll handle errors ourselves

include '../../../database/database-connection.php';

// Clear any previous output
ob_clean();

header('Content-Type: application/json');

try {
    // First check if the table exists
    $check_table = "SHOW TABLES LIKE 'declined_requests'";
    $table_result = $conn->query($check_table);
    
    if ($table_result->num_rows === 0) {
        // Table doesn't exist, create it
        $create_table = "CREATE TABLE declined_requests (
            requestID INT PRIMARY KEY AUTO_INCREMENT,
            type VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            userID INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            staff_comment TEXT,
            FOREIGN KEY (userID) REFERENCES user(userID)
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Failed to create declined_requests table: " . $conn->error);
        }
    }

    $sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
            FROM declined_requests r 
            JOIN user u ON r.userID = u.userID
            ORDER BY r.created_at DESC";
            
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $declinedRequests = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $declinedRequests[] = $row;
        }
    }
    
    echo json_encode($declinedRequests);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'sql_state' => $conn->sqlstate,
        'errno' => $conn->errno
    ]);
}

$conn->close();
?>
