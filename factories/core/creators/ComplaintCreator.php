<?php
/**
 * Creator (Abstract)
 * Declares the factory method that returns a Product object
 * 
 * This is the abstract Creator class in the Factory Method pattern.
 * Concrete creators must extend this class and implement the factory method.
 */

abstract class ComplaintCreator {
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Factory Method (Abstract)
     * Subclasses will implement this to create specific Complaint products
     * 
     * @param string $type The complaint type
     * @return Complaint The created complaint object
     */
    abstract protected function createComplaintProduct($type);

    /**
     * Template method that uses the factory method
     * This is the main method that clients call
     * 
     * @param string $type The complaint type
     * @return Complaint The created complaint object
     */
    public function createComplaint($type) {
        $type = strtolower(trim($type));
        $complaint = $this->createComplaintProduct($type);
        return $complaint;
    }

    /**
     * Get all available complaint types
     * 
     * @return array List of available complaint types
     */
    public function getAvailableComplaintTypes() {
        return [
            'Noise Complaint' => 'Noise Complaint',
            'Property Damage' => 'Property Damage',
            'Public Safety' => 'Public Safety',
            'Sanitation' => 'Sanitation',
            'Street Lighting' => 'Street Lighting',
            'Traffic' => 'Traffic',
            'Water Supply' => 'Water Supply',
            'Waste Management' => 'Waste Management',
            'Public Disturbance' => 'Public Disturbance',
            'Other' => 'Other'
        ];
    }
}
?>

