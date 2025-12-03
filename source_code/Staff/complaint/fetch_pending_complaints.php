<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT c.*, u.firstname, u.middlename, u.lastname 
        FROM complaints c 
        JOIN user u ON c.userID = u.userID 
        WHERE c.status = 'pending' 
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);
$pendingComplaints = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pendingComplaints[] = $row;
    }
}
echo json_encode($pendingComplaints);
$conn->close(); 