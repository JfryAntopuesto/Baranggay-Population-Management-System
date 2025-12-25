<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT c.*, u.firstname, u.middlename, u.lastname 
        FROM complaints c 
        JOIN user u ON c.userID = u.userID 
        WHERE c.status = 'resolved' 
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);
$approvedComplaints = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['status'] = 'resolved';
        $approvedComplaints[] = $row;
    }
}
echo json_encode($approvedComplaints);
$conn->close();
