<?php
/**
 * Concrete Creator
 * Implements the factory method to create Concrete Products
 */

require_once __DIR__ . '/RequestCreator.php';
require_once __DIR__ . '/../products/Request.php';
require_once __DIR__ . '/../products/DocumentRequest.php';
require_once __DIR__ . '/../products/CertificationRequest.php';
require_once __DIR__ . '/../products/PermitRequest.php';
require_once __DIR__ . '/../products/AssistanceRequest.php';
require_once __DIR__ . '/../products/OtherRequest.php';

class RequestFactoryCreator extends RequestCreator {
    /**
     * Factory Method Implementation
     * Creates the appropriate Concrete Product based on type
     * 
     * @param string $type The request type
     * @return Request The created request object (Concrete Product)
     */
    protected function createRequestProduct($type) {
        $type = strtolower(trim($type));
        
        // Map actual system request types to concrete products
        switch ($type) {
            // Document Requests
            case 'barangay clearance':
                return new DocumentRequest($this->conn);

            // Certification Requests
            case 'barangay certificate of residency':
            case 'barangay certificate of indigency':
            case 'barangay certificate of good moral character':
            case 'certificate of solo parent':
            case 'certificate of non-tenancy or non-ownership':
            case 'certificate of live-in partnership or cohabitation':
            case 'certificate of calamity victim':
                return new CertificationRequest($this->conn);

            // Permit Requests
            case 'barangay business clearance':
                return new PermitRequest($this->conn);

            // Assistance Requests
            case 'financial assistance':
                return new AssistanceRequest($this->conn);

            // Legacy/Generic types (for backward compatibility)
            case 'document':
            case 'document request':
                return new DocumentRequest($this->conn);

            case 'certification':
            case 'certification request':
                return new CertificationRequest($this->conn);

            case 'permit':
            case 'permit request':
                return new PermitRequest($this->conn);

            case 'assistance':
            case 'assistance request':
                return new AssistanceRequest($this->conn);

            // Default fallback
            default:
                return new OtherRequest($this->conn);
        }
    }
}
?>

