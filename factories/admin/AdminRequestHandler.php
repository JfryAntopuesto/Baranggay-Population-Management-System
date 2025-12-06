<?php
/**
 * Admin Request Handler - For admins managing all requests
 * 
 * Handles comprehensive request management, reporting, and analytics
 */

require_once __DIR__ . '/../../database/database-connection.php';
require_once __DIR__ . '/../../database/database-operations.php';
require_once __DIR__ . '/../core/RequestFactory.php';

class AdminRequestHandler {
    private $db;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->db = new DatabaseOperations($conn);
    }

    /**
     * Get all requests with full details
     * 
     * @param string $status Optional filter by status
     * @return array All requests with type information
     */
    public function getAllRequests($status = null) {
        try {
            $requests = [];
            
            if ($status === null || $status === 'pending') {
                $pending = $this->db->getAllRequests();
                foreach ($pending as $req) {
                    $requestObj = RequestFactory::createRequest($req['type'], $this->conn);
                    $req['typeDetails'] = $requestObj->getRequestDetails();
                    $req['status_category'] = 'pending';
                    $requests[] = $req;
                }
            }
            
            if ($status === null || $status === 'approved') {
                $sql = "SELECT * FROM approved_requests ORDER BY created_at DESC";
                $result = $this->conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($req = $result->fetch_assoc()) {
                        $requestObj = RequestFactory::createRequest($req['type'], $this->conn);
                        $req['typeDetails'] = $requestObj->getRequestDetails();
                        $req['status_category'] = 'approved';
                        $requests[] = $req;
                    }
                }
            }
            
            if ($status === null || $status === 'declined') {
                $sql = "SELECT * FROM declined_requests ORDER BY created_at DESC";
                $result = $this->conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while ($req = $result->fetch_assoc()) {
                        $requestObj = RequestFactory::createRequest($req['type'], $this->conn);
                        $req['typeDetails'] = $requestObj->getRequestDetails();
                        $req['status_category'] = 'declined';
                        $requests[] = $req;
                    }
                }
            }
            
            return $requests;
        } catch (Exception $e) {
            error_log("Error getting all requests: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get requests organized by type
     * 
     * @return array Requests grouped by type
     */
    public function getRequestsByType() {
        try {
            $allRequests = $this->getAllRequests();
            $byType = [];
            
            foreach ($allRequests as $req) {
                $type = $req['type'];
                if (!isset($byType[$type])) {
                    $byType[$type] = [];
                }
                $byType[$type][] = $req;
            }
            
            return $byType;
        } catch (Exception $e) {
            error_log("Error organizing requests by type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get comprehensive request analytics
     * 
     * @return array Request statistics and metrics
     */
    public function getRequestAnalytics() {
        try {
            $pending = $this->db->getRequestCountByStatus('pending');
            $approved = $this->conn->query("SELECT COUNT(*) as count FROM approved_requests")->fetch_assoc()['count'];
            $declined = $this->conn->query("SELECT COUNT(*) as count FROM declined_requests")->fetch_assoc()['count'];
            
            // Count by type
            $typeStats = [];
            foreach (RequestFactory::getAvailableRequestTypes() as $type => $label) {
                $count = $this->conn->query(
                    "SELECT COUNT(*) as count FROM requests WHERE type = '$type'"
                )->fetch_assoc()['count'];
                $typeStats[$type] = (int)$count;
            }
            
            // Approval rate
            $total = (int)($pending + $approved + $declined);
            $approvalRate = ($total > 0) ? round(($approved / $total) * 100, 2) : 0;
            $declineRate = ($total > 0) ? round(($declined / $total) * 100, 2) : 0;
            
            return [
                'total' => $total,
                'pending' => (int)$pending,
                'approved' => (int)$approved,
                'declined' => (int)$declined,
                'approvalRate' => $approvalRate,
                'declineRate' => $declineRate,
                'byType' => $typeStats
            ];
        } catch (Exception $e) {
            error_log("Error getting request analytics: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'declined' => 0,
                'approvalRate' => 0,
                'declineRate' => 0,
                'byType' => []
            ];
        }
    }

    /**
     * Export all requests as array for reporting
     * 
     * @return array Requests formatted for export/reporting
     */
    public function exportRequests($format = 'array') {
        try {
            $requests = $this->getAllRequests();
            $exported = [];
            
            foreach ($requests as $req) {
                $exported[] = [
                    'ID' => $req['requestID'],
                    'User' => $req['firstname'] . ' ' . $req['lastname'],
                    'Type' => $req['typeDetails']['type'],
                    'Message' => $req['message'],
                    'Status' => $req['status_category'],
                    'Created' => $req['created_at'],
                    'Staff Comment' => $req['staff_comment'] ?? 'N/A'
                ];
            }
            
            return $exported;
        } catch (Exception $e) {
            error_log("Error exporting requests: " . $e->getMessage());
            return [];
        }
    }
}
?>
