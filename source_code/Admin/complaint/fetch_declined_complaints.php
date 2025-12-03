<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT dc.*, u.firstname, u.middlename, u.lastname 
        FROM declined_complaints dc 
        JOIN user u ON dc.userID = u.userID 
        WHERE dc.status = 'declined' 
        ORDER BY dc.created_at DESC";
$result = $conn->query($sql);
$declinedComplaints = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['status'] = 'declined';
        $declinedComplaints[] = $row;
    }
}
echo json_encode($declinedComplaints);
$conn->close();
