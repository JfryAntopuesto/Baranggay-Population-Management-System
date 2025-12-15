<?php
// Clear PHP opcode cache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!<br>";
} else {
    echo "OPcache is not enabled.<br>";
}

// Show current PHP version
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
include 'database/database-connection.php';
if (isset($conn) && !$conn->connect_error) {
    echo "Database connection: OK<br>";
    
    // Test query to requests table
    $test_query = "SELECT COUNT(*) as count FROM requests WHERE status = 'pending'";
    $result = $conn->query($test_query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Requests table accessible: OK (Found " . $row['count'] . " pending requests)<br>";
    } else {
        echo "Error querying requests table: " . $conn->error . "<br>";
    }
    
    // Check if old tables exist
    $check_approved = "SHOW TABLES LIKE 'approved_requests'";
    $result = $conn->query($check_approved);
    if ($result && $result->num_rows > 0) {
        echo "WARNING: approved_requests table still exists!<br>";
    } else {
        echo "approved_requests table does not exist (correct)<br>";
    }
    
    $check_declined = "SHOW TABLES LIKE 'declined_requests'";
    $result = $conn->query($check_declined);
    if ($result && $result->num_rows > 0) {
        echo "WARNING: declined_requests table still exists!<br>";
    } else {
        echo "declined_requests table does not exist (correct)<br>";
    }
} else {
    echo "Database connection: FAILED<br>";
}

echo "<br><strong>Next steps:</strong><br>";
echo "1. Restart Apache in XAMPP Control Panel<br>";
echo "2. Clear your browser cache (Ctrl+Shift+R)<br>";
echo "3. Delete this file (clear_cache.php) after testing<br>";
?>

