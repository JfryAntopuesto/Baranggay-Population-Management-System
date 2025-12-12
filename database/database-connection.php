<?php 
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "baranggay_population_management";

error_log("Attempting database connection to $dbname at $servername");

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
} else {
    error_log("Database connection successful");
    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $conn->error);
    }
}