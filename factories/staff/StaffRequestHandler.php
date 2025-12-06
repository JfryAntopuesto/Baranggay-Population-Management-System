<?php
/**
 * Staff Request Handler - For staff reviewing and managing requests
 * 
 * Handles request review, approval, and declination for staff members
 */

require_once __DIR__ . '/../../database/database-connection.php';
require_once __DIR__ . '/../../database/database-operations.php';
require_once __DIR__ . '/../core/RequestFactory.php';

class StaffRequestHandler {
    private $db;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->db = new DatabaseOperations($conn);
    }

    /**
     * Get all pending requests for staff review
     * 
     * @return array All pending requests with enhanced type information
     */
    public function getPendingRequests() {
        try {
            $pending_sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
                           FROM requests r 
                           JOIN user u ON r.userID = u.userID 
                           WHERE r.status = 'pending' 
                           ORDER BY r.created_at DESC";
            $result = $this->conn->query($pending_sql);
            $requests = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $requestObj = RequestFactory::createRequest($row['type'], $this->conn);
                    $row['typeDetails'] = $requestObj->getRequestDetails();
                    $requests[] = $row;
                }
            }
            
            return $requests;
        } catch (Exception $e) {
            error_log("Error getting pending requests: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Review and approve/decline a request
     * 
     * @param string $requestID Request ID
     * @param string $status Status (approved/declined)
     * @param string $staffComment Staff comment
     * @return array Result with success status
     */
    public function reviewRequest($requestID, $status, $staffComment) {
        error_log("Staff reviewing request: $requestID with status: $status");
        
        try {
            // Get the request details
            $requestData = $this->db->getRequestByID($requestID);
            
            if (!$requestData) {
                return [
                    'success' => false,
                    'message' => 'Request not found'
                ];
            }
            
            // Create appropriate request object using factory
            $request = RequestFactory::createRequest($requestData['type'], $this->conn);
            $request->setRequestID($requestID);
            $request->setType($requestData['type']);
            $request->setMessage($requestData['message']);
            $request->setUserID($requestData['userID']);
            $request->setStatus($status);
            $request->setStaffComment($staffComment);
            
            // Get request details for staff reference
            $details = $request->getRequestDetails();
            error_log("Staff reviewing request type: " . $details['type']);
            
            // Update request status in database
            if ($this->db->updateRequestStatus($requestID, $status, $staffComment)) {
                return [
                    'success' => true,
                    'message' => 'Request ' . ($status === 'approved' ? 'approved' : 'declined') . ' successfully',
                    'requestDetails' => $details
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update request status'
                ];
            }
        } catch (Exception $e) {
            error_log("Error reviewing request: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while reviewing the request'
            ];
        }
    }

    /**
     * Get all approved requests
     * 
     * @return array All approved requests
     */
    public function getApprovedRequests() {
        try {
            $sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
                   FROM approved_requests r 
                   JOIN user u ON r.userID = u.userID 
                   ORDER BY r.created_at DESC";
            $result = $this->conn->query($sql);
            $requests = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $requestObj = RequestFactory::createRequest($row['type'], $this->conn);
                    $row['typeDetails'] = $requestObj->getRequestDetails();
                    $requests[] = $row;
                }
            }
            
            return $requests;
        } catch (Exception $e) {
            error_log("Error getting approved requests: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all declined requests
     * 
     * @return array All declined requests
     */
    public function getDeclinedRequests() {
        try {
            $sql = "SELECT r.*, u.firstname, u.middlename, u.lastname 
                   FROM declined_requests r 
                   JOIN user u ON r.userID = u.userID 
                   ORDER BY r.created_at DESC";
            $result = $this->conn->query($sql);
            $requests = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $requestObj = RequestFactory::createRequest($row['type'], $this->conn);
                    $row['typeDetails'] = $requestObj->getRequestDetails();
                    $requests[] = $row;
                }
            }
            
            return $requests;
        } catch (Exception $e) {
            error_log("Error getting declined requests: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get request count by status
     * 
     * @return array Count of pending, approved, and declined requests
     */
    public function getRequestCounts() {
        try {
            $pending = $this->db->getRequestCountByStatus('pending');
            $approved = $this->conn->query("SELECT COUNT(*) as count FROM approved_requests")->fetch_assoc()['count'];
            $declined = $this->conn->query("SELECT COUNT(*) as count FROM declined_requests")->fetch_assoc()['count'];
            
            return [
                'pending' => (int)$pending,
                'approved' => (int)$approved,
                'declined' => (int)$declined,
                'total' => (int)($pending + $approved + $declined)
            ];
        } catch (Exception $e) {
            error_log("Error getting request counts: " . $e->getMessage());
            return [
                'pending' => 0,
                'approved' => 0,
                'declined' => 0,
                'total' => 0
            ];
        }
    }
}
?>
