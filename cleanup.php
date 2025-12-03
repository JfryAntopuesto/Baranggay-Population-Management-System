<?php
// This script handles automatic cleanup operations
require_once 'database/database-connection.php';
require_once 'database/database-operations.php';
require_once 'includes/cleanup-handler.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'cleanup.log');

// Create a lock file to prevent multiple instances running simultaneously
$lockFile = 'cleanup.lock';

try {
    error_log("Starting standalone cleanup script at " . date('Y-m-d H:i:s'));
    
    // Check if another instance is already running
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        $currentTime = time();
        
        // If lock file is older than 5 seconds, it might be a stale lock
        if ($currentTime - $lockTime < 5) {
            error_log("Cleanup script already running. Exiting.");
            exit(); // Another instance is running, exit
        }
        // Remove stale lock file
        unlink($lockFile);
        error_log("Removed stale lock file");
    }

    // Create lock file
    if (!touch($lockFile)) {
        error_log("Could not create lock file. Exiting.");
        exit();
    }
    error_log("Created lock file");

    // Initialize database operations
    $db = new DatabaseOperations($conn);
    error_log("Database operations initialized");
    
    // Initialize cleanup handler
    $cleanupHandler = new CleanupHandler($db);

    // Run cleanup
    $cleanupHandler->checkAndRunCleanup();

} catch (Exception $e) {
    error_log("Error during cleanup: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
} finally {
    // Remove lock file
    if (file_exists($lockFile)) {
        unlink($lockFile);
        error_log("Removed lock file");
    }
    
    // Close database connection
    if (isset($conn)) {
        $conn->close();
        error_log("Database connection closed");
    }
    
    error_log("Cleanup script completed at " . date('Y-m-d H:i:s'));
}
