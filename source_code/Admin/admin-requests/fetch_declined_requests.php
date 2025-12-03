<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
        FROM declined_requests r 
        JOIN user u ON r.userID = u.userID
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);
$declinedRequests = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $declinedRequests[] = $row;
    }
}
echo json_encode($declinedRequests);
$conn->close();
