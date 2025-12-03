<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT t.*, u.firstname, u.middlename, u.lastname 
        FROM approved_requests t 
        JOIN user u ON t.userID = u.userID 
        ORDER BY t.created_at DESC";
$result = $conn->query($sql);
$approvedRequests = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $approvedRequests[] = $row;
    }
}
echo json_encode($approvedRequests);
$conn->close();
