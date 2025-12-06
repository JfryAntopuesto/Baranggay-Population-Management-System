<?php
/**
 * Admin Complaint Handler - For admins managing all complaints
 * 
 * Handles comprehensive complaint management, reporting, and severity-based analytics
 */

require_once __DIR__ . '/../../database/database-connection.php';
require_once __DIR__ . '/../../database/database-operations.php';
require_once __DIR__ . '/../core/ComplaintFactory.php';

class AdminComplaintHandler {
    private $db;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->db = new DatabaseOperations($conn);
    }

    /**
     * Get all complaints with full details
     * 
     * @param string $status Optional filter by status
     * @return array All complaints with type and severity information
     */
    public function getAllComplaints($status = null) {
        try {
            $complaints = [];
            
            if ($status === null || $status === 'pending') {
                $pending = $this->db->getAllComplaints();
                foreach ($pending as $comp) {
                    $complaintObj = ComplaintFactory::createComplaint($comp['type'], $this->conn);
                    $comp['severity'] = $complaintObj->getSeverityLevel();
                    $comp['typeDetails'] = $complaintObj->getComplaintDetails();
                    $comp['status_category'] = 'pending';
                    $complaints[] = $comp;
                }
            }
            
            if ($status === null || $status === 'resolved') {
                $sql = "SELECT * FROM approved_complaints ORDER BY created_at DESC";
                $result = $this->conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($comp = $result->fetch_assoc()) {
                        $complaintObj = ComplaintFactory::createComplaint($comp['type'], $this->conn);
                        $comp['severity'] = $complaintObj->getSeverityLevel();
                        $comp['typeDetails'] = $complaintObj->getComplaintDetails();
                        $comp['status_category'] = 'resolved';
                        $complaints[] = $comp;
                    }
                }
            }
            
            if ($status === null || $status === 'declined') {
                $sql = "SELECT * FROM declined_complaints ORDER BY created_at DESC";
                $result = $this->conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($comp = $result->fetch_assoc()) {
                        $complaintObj = ComplaintFactory::createComplaint($comp['type'], $this->conn);
                        $comp['severity'] = $complaintObj->getSeverityLevel();
                        $comp['typeDetails'] = $complaintObj->getComplaintDetails();
                        $comp['status_category'] = 'declined';
                        $complaints[] = $comp;
                    }
                }
            }
            
            return $complaints;
        } catch (Exception $e) {
            error_log("Error getting all complaints: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get complaints organized by severity level
     * 
     * @return array Complaints grouped by severity
     */
    public function getComplaintsBySeverity() {
        try {
            $allComplaints = $this->getAllComplaints();
            $bySeverity = [
                'high' => [],
                'moderate' => [],
                'low' => []
            ];
            
            foreach ($allComplaints as $comp) {
                $severity = $comp['severity'];
                if (array_key_exists($severity, $bySeverity)) {
                    $bySeverity[$severity][] = $comp;
                }
            }
            
            return $bySeverity;
        } catch (Exception $e) {
            error_log("Error organizing complaints by severity: " . $e->getMessage());
            return ['high' => [], 'moderate' => [], 'low' => []];
        }
    }

    /**
     * Get complaints organized by type
     * 
     * @return array Complaints grouped by type
     */
    public function getComplaintsByType() {
        try {
            $allComplaints = $this->getAllComplaints();
            $byType = [];
            
            foreach ($allComplaints as $comp) {
                $type = $comp['type'];
                if (!isset($byType[$type])) {
                    $byType[$type] = [];
                }
                $byType[$type][] = $comp;
            }
            
            return $byType;
        } catch (Exception $e) {
            error_log("Error organizing complaints by type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get comprehensive complaint analytics
     * 
     * @return array Complaint statistics and metrics by severity and type
     */
    public function getComplaintAnalytics() {
        try {
            $pending = $this->conn->query("SELECT COUNT(*) as count FROM complaints")->fetch_assoc()['count'];
            $resolved = $this->conn->query("SELECT COUNT(*) as count FROM approved_complaints")->fetch_assoc()['count'];
            $declined = $this->conn->query("SELECT COUNT(*) as count FROM declined_complaints")->fetch_assoc()['count'];
            
            // Count by severity (from all complaints)
            $severityStats = [
                'high' => 0,
                'moderate' => 0,
                'low' => 0
            ];
            
            $allComplaints = $this->getAllComplaints();
            foreach ($allComplaints as $comp) {
                $severity = $comp['severity'];
                if (array_key_exists($severity, $severityStats)) {
                    $severityStats[$severity]++;
                }
            }
            
            // Count by type
            $typeStats = [];
            foreach (ComplaintFactory::getAvailableComplaintTypes() as $type => $label) {
                $count = 0;
                foreach ($allComplaints as $comp) {
                    if ($comp['type'] === $type) {
                        $count++;
                    }
                }
                $typeStats[$type] = $count;
            }
            
            // Resolution rate
            $total = (int)($pending + $resolved + $declined);
            $resolutionRate = ($total > 0) ? round(($resolved / $total) * 100, 2) : 0;
            $declineRate = ($total > 0) ? round(($declined / $total) * 100, 2) : 0;
            
            return [
                'total' => $total,
                'pending' => (int)$pending,
                'resolved' => (int)$resolved,
                'declined' => (int)$declined,
                'resolutionRate' => $resolutionRate,
                'declineRate' => $declineRate,
                'bySeverity' => $severityStats,
                'byType' => $typeStats,
                'requiresAttention' => $severityStats['high']
            ];
        } catch (Exception $e) {
            error_log("Error getting complaint analytics: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'resolved' => 0,
                'declined' => 0,
                'resolutionRate' => 0,
                'declineRate' => 0,
                'bySeverity' => ['high' => 0, 'moderate' => 0, 'low' => 0],
                'byType' => [],
                'requiresAttention' => 0
            ];
        }
    }

    /**
     * Export all complaints as array for reporting
     * 
     * @return array Complaints formatted for export/reporting
     */
    public function exportComplaints($format = 'array') {
        try {
            $complaints = $this->getAllComplaints();
            $exported = [];
            
            foreach ($complaints as $comp) {
                $exported[] = [
                    'ID' => $comp['complaintID'],
                    'Complainant' => $comp['firstname'] . ' ' . $comp['lastname'],
                    'Against' => $comp['complained_person'],
                    'Type' => $comp['typeDetails']['type'],
                    'Severity' => strtoupper($comp['severity']),
                    'Message' => $comp['message'],
                    'Status' => $comp['status_category'],
                    'Created' => $comp['created_at'],
                    'Staff Comment' => $comp['staff_comment'] ?? 'N/A'
                ];
            }
            
            return $exported;
        } catch (Exception $e) {
            error_log("Error exporting complaints: " . $e->getMessage());
            return [];
        }
    }
}
?>
