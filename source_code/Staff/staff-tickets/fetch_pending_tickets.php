<?php
include '../../../database/database-connection.php';
header('Content-Type: application/json');

$sql = "SELECT t.*, u.firstname, u.middlename, u.lastname 
        FROM ticket t 
        JOIN user u ON t.userID = u.userID 
        WHERE t.status = 'pending' 
        ORDER BY t.created_at DESC";
$result = $conn->query($sql);
$pendingTickets = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pendingTickets[] = $row;
    }
}
echo json_encode($pendingTickets);
$conn->close();
