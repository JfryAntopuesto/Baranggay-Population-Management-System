<?php
class CleanupHandler {
    private $db;
    private $cleanupInterval = 30; // 30 seconds after creation
    private $timeFile;

    public function __construct($db) {
        $this->db = $db;
        $this->timeFile = __DIR__ . '/../cleanup_time.txt';
        error_log("CleanupHandler initialized at " . date('Y-m-d H:i:s'));
    }

    public function checkAndRunCleanup() {
        error_log("Checking for records to cleanup...");
        
        // Get current time
        $currentTime = time();
        error_log("Current time: " . date('Y-m-d H:i:s', $currentTime));
        
        // Run cleanup operations
        error_log("Running cleanup operations...");
        
        // Delete requests that are exactly 30 seconds old
        $this->db->deleteOldRequests();
        
        // Delete read notifications that are exactly 30 seconds old
        $this->db->deleteOldNotifications();
        
        // Delete complaints that are exactly 30 seconds old
        $this->db->deleteOldComplaints();
        
        error_log("Cleanup operations completed");
    }
}
?> 