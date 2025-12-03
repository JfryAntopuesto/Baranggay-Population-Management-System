<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new DatabaseOperations($conn);

    // Get form data
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = 'staff';

    // Log the received data
    error_log("Attempting to add staff with data:");
    error_log("Firstname: " . $firstname);
    error_log("Middlename: " . $middlename);
    error_log("Lastname: " . $lastname);
    error_log("Birthdate: " . $birthdate);
    error_log("Gender: " . $gender);
    error_log("Username: " . $username);
    error_log("Role: " . $role);

    // Check if username already exists
    if ($db->checkUsernameExists($username)) {
        error_log("Username already exists: " . $username);
        header("Location: admin-dashboard.php?error=username_exists");
        exit();
    }

    try {
        // Insert new staff
        $result = $db->insertStaff(
            $firstname,
            $middlename,
            $lastname,
            $birthdate,
            $gender,
            $username,
            $password,
            $role
        );

        if ($result) {
            error_log("Staff added successfully");
            header("Location: admin-dashboard.php?success=staff_added");
        } else {
            error_log("Failed to add staff - insertStaff returned false");
            header("Location: admin-dashboard.php?error=staff_add_failed");
        }
    } catch (Exception $e) {
        error_log("Exception while adding staff: " . $e->getMessage());
        header("Location: admin-dashboard.php?error=staff_add_failed");
    }
    exit();
}
?> 