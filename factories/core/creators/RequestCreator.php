<?php
/**
 * Creator (Abstract)
 * Declares the factory method that returns a Product object
 * 
 * This is the abstract Creator class in the Factory Method pattern.
 * Concrete creators must extend this class and implement the factory method.
 */

abstract class RequestCreator {
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Factory Method (Abstract)
     * Subclasses will implement this to create specific Request products
     * 
     * @param string $type The request type
     * @return Request The created request object
     */
    abstract protected function createRequestProduct($type);

    /**
     * Template method that uses the factory method
     * This is the main method that clients call
     * 
     * @param string $type The request type
     * @return Request The created request object
     */
    public function createRequest($type) {
        $type = strtolower(trim($type));
        $request = $this->createRequestProduct($type);
        return $request;
    }

    /**
     * Get all available request types
     * 
     * @return array List of available request types
     */
    public function getAvailableRequestTypes() {
        return [
            'Barangay Clearance' => 'Barangay Clearance',
            'Barangay Certificate of Residency' => 'Barangay Certificate of Residency',
            'Barangay Certificate of Indigency' => 'Barangay Certificate of Indigency',
            'Barangay Certificate of Good Moral Character' => 'Barangay Certificate of Good Moral Character',
            'Barangay Business Clearance' => 'Barangay Business Clearance',
            'Certificate of Solo Parent' => 'Certificate of Solo Parent',
            'Certificate of Non-Tenancy or Non-Ownership' => 'Certificate of Non-Tenancy or Non-Ownership',
            'Certificate of Live-in Partnership or Cohabitation' => 'Certificate of Live-in Partnership or Cohabitation',
            'Certificate of Calamity Victim' => 'Certificate of Calamity Victim',
            'Financial Assistance' => 'Financial Assistance'
        ];
    }
}
?>

