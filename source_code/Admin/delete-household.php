<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-dashboard.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);
$householdID = intval($_GET['id']);

// Get the purokID before deleting
$stmt = $conn->prepare("SELECT purokID FROM household WHERE householdID = ?");
$stmt->bind_param("i", $householdID);
$stmt->execute();
$stmt->bind_result($purokID);
$stmt->fetch();
$stmt->close();

// Delete the household
$db->deleteHousehold($householdID);

// Redirect back to the correct XML generator
header("Location: admin-household-lists.php?purokID=$purokID");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: admin-dashboard.php?deleted=1");
exit(); 