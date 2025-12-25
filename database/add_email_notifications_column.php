<?php
/**
 * Migration script to add email_notifications column to user table
 * Run this script once to update existing databases
 */

include 'database-connection.php';

try {
    // Check if column already exists
    $check_sql = "SHOW COLUMNS FROM user LIKE 'email_notifications'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        echo "Column 'email_notifications' already exists in user table.\n";
    } else {
        // Add the column
        $alter_sql = "ALTER TABLE user ADD COLUMN email_notifications BOOLEAN NOT NULL DEFAULT FALSE AFTER email";
        
        if ($conn->query($alter_sql)) {
            echo "Successfully added 'email_notifications' column to user table.\n";
        } else {
            throw new Exception("Error adding column: " . $conn->error);
        }
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
