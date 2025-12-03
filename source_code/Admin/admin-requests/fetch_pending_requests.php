<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
        FROM requests r 
        JOIN user u ON r.userID = u.userID 
        WHERE r.status = 'pending' 
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);
$pendingRequests = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pendingRequests[] = $row;
    }
}
echo json_encode($pendingRequests);
$conn->close();
