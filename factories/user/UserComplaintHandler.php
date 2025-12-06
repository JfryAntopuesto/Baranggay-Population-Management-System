<?php
/**
 * User Complaint Handler - For regular users filing complaints
 * 
 * Handles complaint creation and submission for regular users
 */

require_once __DIR__ . '/../../database/database-connection.php';
require_once __DIR__ . '/../../database/database-operations.php';
require_once __DIR__ . '/../core/ComplaintFactory.php';

class UserComplaintHandler {
    private $db;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->db = new DatabaseOperations($conn);
    }

    /**
     * File a new complaint
     * 
     * @param string $type Complaint type
     * @param string $message Complaint message
     * @param int $userID User ID
     * @param string $complainedPerson Name of person being complained about
     * @return array Result with success status and details
     */
    public function fileComplaint($type, $message, $userID, $complainedPerson) {
        // Create factory and instantiate appropriate complaint type
        $complaint = ComplaintFactory::createComplaint($type, $this->conn);
        
        // Set complaint properties
        $complaint->setType($type);
        $complaint->setMessage($message);
        $complaint->setUserID($userID);
        $complaint->setComplainedPerson($complainedPerson);

        // Validate complaint
        if (!$complaint->validate()) {
            error_log("Invalid complaint data provided by user $userID");
            return [
                'success' => false,
                'message' => 'Complaint validation failed. Please check your input.',
                'errors' => ['All fields are required']
            ];
        }

        // Get complaint details and severity
        $details = $complaint->getComplaintDetails();
        $severity = $complaint->getSeverityLevel();
        
        error_log("User " . $userID . " filed complaint against " . $complainedPerson . 
                  " with severity: " . $severity . " - " . json_encode($details));

        // Create complaint in database
        $complaintID = $this->db->createComplaint($type, $message, $userID, $complainedPerson);

        if ($complaintID) {
            // Log high-severity complaints for immediate staff attention
            if ($severity === 'high') {
                error_log("HIGH SEVERITY COMPLAINT FILED: " . $complaintID);
                // Could trigger immediate notification here
            }
            
            return [
                'success' => true,
                'message' => 'Complaint filed successfully!',
                'complaintID' => $complaintID,
                'details' => $details,
                'severity' => $severity,
                'needsAttention' => ($severity === 'high')
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to file complaint. Please try again.'
            ];
        }
    }

    /**
     * Get user's complaints by status
     * 
     * @param int $userID User ID
     * @return array Complaints organized by status
     */
    public function getUserComplaints($userID) {
        try {
            $complaints = $this->db->getComplaintsByUserID($userID);
            
            // Enhance with type details and severity
            foreach ($complaints as $status => $statusComplaints) {
                foreach ($statusComplaints as $key => $complaint) {
                    $complaintObj = ComplaintFactory::createComplaint($complaint['type'], $this->conn);
                    $complaints[$status][$key]['typeDetails'] = $complaintObj->getComplaintDetails();
                    $complaints[$status][$key]['severity'] = $complaintObj->getSeverityLevel();
                }
            }
            
            return $complaints;
        } catch (Exception $e) {
            error_log("Error getting user complaints: " . $e->getMessage());
            return [
                'pending' => [],
                'resolved' => [],
                'declined' => []
            ];
        }
    }

    /**
     * Get available complaint types for user form
     * 
     * @return array Available complaint types
     */
    public function getAvailableComplaintTypes() {
        return ComplaintFactory::getAvailableComplaintTypes();
    }

    /**
     * Validate complaint data before submission
     * 
     * @param string $type Complaint type
     * @param string $message Message
     * @param int $userID User ID
     * @param string $person Person being complained about
     * @return array Validation result
     */
    public function validateComplaint($type, $message, $userID, $person) {
        $complaint = ComplaintFactory::createComplaint($type, $this->conn);
        $complaint->setType($type);
        $complaint->setMessage($message);
        $complaint->setUserID($userID);
        $complaint->setComplainedPerson($person);

        if ($complaint->validate()) {
            return [
                'valid' => true,
                'severity' => $complaint->getSeverityLevel()
            ];
        } else {
            return [
                'valid' => false,
                'message' => 'All fields are required'
            ];
        }
    }
}
?>
