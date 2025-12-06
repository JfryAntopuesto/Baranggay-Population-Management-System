<?php
/**
 * ComplaintFactory - Static Facade for Backward Compatibility
 * 
 * This class maintains the original static interface while using the proper 
 * Factory Method pattern with separated classes.
 * 
 * All components are now in separate files:
 * - Product (Abstract): factories/core/products/Complaint.php
 * - Concrete Products: factories/core/products/NoiseComplaint.php, etc.
 * - Creator (Abstract): factories/core/creators/ComplaintCreator.php
 * - Concrete Creator: factories/core/creators/ComplaintFactoryCreator.php
 */

require_once __DIR__ . '/creators/ComplaintFactoryCreator.php';
require_once __DIR__ . '/products/Complaint.php';

class ComplaintFactory {
    /**
     * Create a complaint object based on the type
     * Uses the proper Factory Method pattern internally
     * 
     * @param string $type The complaint type
     * @param mysqli $conn Database connection
     * @return Complaint The created complaint object
     */
    public static function createComplaint($type, $conn) {
        $creator = new ComplaintFactoryCreator($conn);
        return $creator->createComplaint($type);
    }

    /**
     * Get all available complaint types
     * 
     * @return array List of available complaint types
     */
    public static function getAvailableComplaintTypes() {
        $creator = new ComplaintFactoryCreator(null);
        return $creator->getAvailableComplaintTypes();
    }

    /**
     * Get complaints by severity level
     * Handles both Complaint objects and arrays (database rows)
     * 
     * @param string $severity The severity level (low, moderate, high)
     * @param array $complaints Array of complaints (objects or arrays)
     * @param mysqli|null $conn Database connection (required if $complaints contains arrays)
     * @return array Filtered complaints by severity
     */
    public static function getComplaintsBySeverity($severity, $complaints, $conn = null) {
        $filtered = [];
        
        foreach ($complaints as $complaint) {
            $severityLevel = null;
            
            // Check if it's a Complaint object
            if ($complaint instanceof Complaint) {
                $severityLevel = $complaint->getSeverityLevel();
            }
            // Check if it's an array (database row) with type field
            elseif (is_array($complaint) && isset($complaint['type']) && $conn !== null) {
                // Create a Complaint object to get severity
                $complaintObj = self::createComplaint($complaint['type'], $conn);
                $severityLevel = $complaintObj->getSeverityLevel();
            }
            // Check if it's an array with severity already set
            elseif (is_array($complaint) && isset($complaint['severity'])) {
                $severityLevel = $complaint['severity'];
            }
            
            if ($severityLevel === $severity) {
                $filtered[] = $complaint;
            }
        }
        
        return $filtered;
    }
}
?>
