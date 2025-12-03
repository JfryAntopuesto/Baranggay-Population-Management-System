<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

if(isset($_GET['id'])) {
    $db = new DatabaseOperations($conn);
    $purokId = $_GET['id'];
    
    // First check if there are any households in this purok
    $households = $db->getHouseholdsByPurok($purokId);
    if(count($households) > 0) {
        $_SESSION['error'] = "Cannot delete purok: There are households assigned to this purok.";
        header("Location: admin-purok-list.php");
        exit();
    }
    
    // If no households, proceed with deletion
    if($db->deletePurok($purokId)) {
        $_SESSION['success'] = "Purok deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete purok.";
    }
}

header("Location: admin-purok-list.php");
exit();
?> 