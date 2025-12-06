<?php
/**
 * User Request Handler - For regular users submitting requests
 * 
 * Handles request creation and submission for regular users
 */

require_once __DIR__ . '/../../database/database-connection.php';
require_once __DIR__ . '/../../database/database-operations.php';
require_once __DIR__ . '/../core/RequestFactory.php';

class UserRequestHandler {
    private $db;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->db = new DatabaseOperations($conn);
    }

    /**
     * Create and submit a new request
     * 
     * @param string $type Request type
     * @param string $message Request message
     * @param int $userID User ID
     * @return array Result with success status and details
     */
    public function submitRequest($type, $message, $userID) {
        // Create factory and instantiate appropriate request type
        $request = RequestFactory::createRequest($type, $this->conn);
        
        // Set request properties
        $request->setType($type);
        $request->setMessage($message);
        $request->setUserID($userID);

        // Validate request
        if (!$request->validate()) {
            error_log("Invalid request data provided by user $userID");
            return [
                'success' => false,
                'message' => 'Request validation failed. Please check your input.',
                'errors' => ['All fields are required']
            ];
        }

        // Get request details for logging
        $details = $request->getRequestDetails();
        error_log("User " . $userID . " submitted request: " . json_encode($details));

        // Create request in database
        $requestID = $this->db->createRequest($type, $message, $userID);

        if ($requestID) {
            return [
                'success' => true,
                'message' => 'Request submitted successfully!',
                'requestID' => $requestID,
                'details' => $details
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create request. Please try again.'
            ];
        }
    }

    /**
     * Get user's requests by status
     * 
     * @param int $userID User ID
     * @return array Requests organized by status
     */
    public function getUserRequests($userID) {
        try {
            $requests = $this->db->getRequestsByUserID($userID);
            
            // Enhance with type details
            foreach ($requests as $status => $statusRequests) {
                foreach ($statusRequests as $key => $request) {
                    $requestObj = RequestFactory::createRequest($request['type'], $this->conn);
                    $requests[$status][$key]['typeDetails'] = $requestObj->getRequestDetails();
                }
            }
            
            return $requests;
        } catch (Exception $e) {
            error_log("Error getting user requests: " . $e->getMessage());
            return [
                'pending' => [],
                'finished' => [],
                'declined' => []
            ];
        }
    }

    /**
     * Get available request types for user form
     * 
     * @return array Available request types
     */
    public function getAvailableRequestTypes() {
        return RequestFactory::getAvailableRequestTypes();
    }
}
?>
