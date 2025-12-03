<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);

if(isset($_GET['id'])) {
    $modID = $_GET['id'];
    if($db->deleteStaff($modID)) {
        header("Location: admin-dashboard.php?success=1");
    } else {
        header("Location: admin-dashboard.php?error=1");
    }
} else {
    header("Location: admin-dashboard.php");
}
exit();
?> 