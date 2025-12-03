<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT ac.*, u.firstname, u.middlename, u.lastname 
        FROM approved_complaints ac 
        JOIN user u ON ac.userID = u.userID 
        ORDER BY ac.created_at DESC";
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
