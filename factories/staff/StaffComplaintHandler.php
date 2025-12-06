<?php
/**
 * Staff Complaint Handler - For staff managing complaints
 * 
 * Handles complaint review with severity-based routing and management
 */

require_once __DIR__ . '/../../database/database-connection.php';
require_once __DIR__ . '/../../database/database-operations.php';
require_once __DIR__ . '/../core/ComplaintFactory.php';

class StaffComplaintHandler {
    private $db;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->db = new DatabaseOperations($conn);
    }

    /**
     * Get all pending complaints organized by severity
     * 
     * @return array Complaints organized by severity level
     */
    public function getPendingComplaintsBySeverity() {
        try {
            $complaintsByCategory = [
                'high' => [],
                'moderate' => [],
                'low' => []
            ];
            
            $pending_sql = "SELECT c.*, u.firstname, u.middlename, u.lastname 
                           FROM complaints c 
                           JOIN user u ON c.userID = u.userID 
                           WHERE c.status = 'pending' 
                           ORDER BY c.created_at DESC";
            $result = $this->conn->query($pending_sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $complaint = ComplaintFactory::createComplaint($row['type'], $this->conn);
                    $severity = $complaint->getSeverityLevel();
                    
                    $complaintItem = [
                        'complaintID' => $row['complaintID'],
                        'data' => $row,
                        'severity' => $severity,
                        'details' => $complaint->getComplaintDetails()
                    ];
                    
                    if (array_key_exists($severity, $complaintsByCategory)) {
                        $complaintsByCategory[$severity][] = $complaintItem;
                    }
                }
            }
            
            return $complaintsByCategory;
        } catch (Exception $e) {
            error_log("Error getting complaints by severity: " . $e->getMessage());
            return ['high' => [], 'moderate' => [], 'low' => []];
        }
    }

    /**
     * Get all pending complaints
     * 
     * @return array All pending complaints with type details
     */
    public function getAllPendingComplaints() {
        try {
            $complaints = $this->db->getAllComplaints();
            
            foreach ($complaints as $key => $complaint) {
                $complaintObj = ComplaintFactory::createComplaint($complaint['type'], $this->conn);
                $complaints[$key]['severity'] = $complaintObj->getSeverityLevel();
                $complaints[$key]['typeDetails'] = $complaintObj->getComplaintDetails();
            }
            
            return $complaints;
        } catch (Exception $e) {
            error_log("Error getting all complaints: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get complaints by severity level
     * 
     * @param string $severity Severity level (high, moderate, low)
     * @return array Complaints of specified severity
     */
    public function getComplaintsBySeverity($severity) {
        try {
            $sql = "SELECT c.*, u.firstname, u.middlename, u.lastname 
                   FROM complaints c 
                   JOIN user u ON c.userID = u.userID 
                   WHERE c.status = 'pending' 
                   ORDER BY c.created_at DESC";
            $result = $this->conn->query($sql);
            
            $filtered = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $complaint = ComplaintFactory::createComplaint($row['type'], $this->conn);
                    
                    if ($complaint->getSeverityLevel() === $severity) {
                        $row['severity'] = $complaint->getSeverityLevel();
                        $row['typeDetails'] = $complaint->getComplaintDetails();
                        $filtered[] = $row;
                    }
                }
            }
            
            return $filtered;
        } catch (Exception $e) {
            error_log("Error getting complaints by severity: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Review and approve/decline a complaint
     * 
     * @param string $complaintID Complaint ID
     * @param string $status Status (resolved/declined)
     * @param string $staffComment Staff comment
     * @return array Result with success status
     */
    public function reviewComplaint($complaintID, $status, $staffComment) {
        error_log("Staff reviewing complaint: $complaintID with status: $status");
        
        try {
            $complaintData = $this->db->getComplaintByID($complaintID);
            
            if (!$complaintData) {
                return [
                    'success' => false,
                    'message' => 'Complaint not found'
                ];
            }
            
            $complaint = ComplaintFactory::createComplaint($complaintData['type'], $this->conn);
            $severity = $complaint->getSeverityLevel();
            
            // Store in appropriate table based on status
            $table = ($status === 'resolved') ? 'approved_complaints' : 'declined_complaints';
            $timestamp = date('Y-m-d H:i:s');
            
            $insert_sql = "INSERT INTO $table 
                          (complaintID, type, message, userID, complained_person, status, created_at, staff_comment) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($insert_sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return [
                    'success' => false,
                    'message' => 'Database error occurred'
                ];
            }
            
            $stmt->bind_param(
                "sisssss",
                $complaintData['complaintID'],
                $complaintData['type'],
                $complaintData['message'],
                $complaintData['userID'],
                $complaintData['complained_person'],
                $status,
                $timestamp,
                $staffComment
            );
            
            if ($stmt->execute()) {
                // Delete from pending
                $delete_sql = "DELETE FROM complaints WHERE complaintID = ?";
                $delete_stmt = $this->conn->prepare($delete_sql);
                $delete_stmt->bind_param("s", $complaintID);
                $delete_stmt->execute();
                
                return [
                    'success' => true,
                    'message' => 'Complaint ' . ($status === 'resolved' ? 'resolved' : 'declined') . ' successfully',
                    'severity' => $severity,
                    'details' => $complaint->getComplaintDetails()
                ];
            } else {
                error_log("Execute failed: " . $stmt->error);
                return [
                    'success' => false,
                    'message' => 'Failed to update complaint status'
                ];
            }
        } catch (Exception $e) {
            error_log("Error reviewing complaint: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while reviewing the complaint'
            ];
        }
    }

    /**
     * Get complaint count statistics
     * 
     * @return array Count of complaints by status and severity
     */
    public function getComplaintStatistics() {
        try {
            $total = $this->conn->query("SELECT COUNT(*) as count FROM complaints")->fetch_assoc()['count'];
            $resolved = $this->conn->query("SELECT COUNT(*) as count FROM approved_complaints")->fetch_assoc()['count'];
            $declined = $this->conn->query("SELECT COUNT(*) as count FROM declined_complaints")->fetch_assoc()['count'];
            
            // Count by severity (pending only)
            $high = $this->conn->query(
                "SELECT c.complaintID FROM complaints c 
                 WHERE c.type IN ('behavior', 'property')"
            )->num_rows;
            
            $moderate = $this->conn->query(
                "SELECT c.complaintID FROM complaints c 
                 WHERE c.type IN ('noise', 'animal')"
            )->num_rows;
            
            return [
                'pending' => (int)$total,
                'resolved' => (int)$resolved,
                'declined' => (int)$declined,
                'highSeverity' => (int)$high,
                'moderateSeverity' => (int)$moderate,
                'total' => (int)($total + $resolved + $declined)
            ];
        } catch (Exception $e) {
            error_log("Error getting complaint statistics: " . $e->getMessage());
            return [
                'pending' => 0,
                'resolved' => 0,
                'declined' => 0,
                'highSeverity' => 0,
                'moderateSeverity' => 0,
                'total' => 0
            ];
        }
    }
}
?>
