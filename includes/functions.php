<?php
// Utility functions

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['userID']);
}

// Function to check if user is staff
function is_staff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to format date
function format_date($date) {
    return date('F d, Y', strtotime($date));
}

// Function to format datetime
function format_datetime($datetime) {
    return date('F d, Y h:i A', strtotime($datetime));
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to display error message
function display_error($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

// Function to display success message
function display_success($message) {
    return "<div class='alert alert-success'>$message</div>";
}
?> 