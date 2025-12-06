<?php
/**
 * Concrete Creator
 * Implements the factory method to create Concrete Products
 */

require_once __DIR__ . '/ComplaintCreator.php';
require_once __DIR__ . '/../products/Complaint.php';
require_once __DIR__ . '/../products/NoiseComplaint.php';
require_once __DIR__ . '/../products/BehaviorComplaint.php';
require_once __DIR__ . '/../products/PropertyComplaint.php';
require_once __DIR__ . '/../products/AnimalComplaint.php';
require_once __DIR__ . '/../products/OtherComplaint.php';

class ComplaintFactoryCreator extends ComplaintCreator {
    /**
     * Factory Method Implementation
     * Creates the appropriate Concrete Product based on type
     * 
     * @param string $type The complaint type
     * @return Complaint The created complaint object (Concrete Product)
     */
    protected function createComplaintProduct($type) {
        $type = strtolower(trim($type));
        
        // Map actual system complaint types to concrete products
        switch ($type) {
            // Noise Complaints (moderate severity)
            case 'noise complaint':
            case 'noise':
                return new NoiseComplaint($this->conn);

            // Behavior Complaints (high severity)
            case 'public safety':
            case 'public disturbance':
            case 'behavior':
            case 'behavior complaint':
                return new BehaviorComplaint($this->conn);

            // Property Complaints (high severity)
            case 'property damage':
            case 'property':
            case 'property complaint':
                return new PropertyComplaint($this->conn);

            // Animal Complaints (moderate severity)
            case 'animal':
            case 'animal complaint':
                return new AnimalComplaint($this->conn);

            // Other Complaints (low/moderate severity)
            case 'sanitation':
            case 'street lighting':
            case 'traffic':
            case 'water supply':
            case 'waste management':
            case 'other':
                return new OtherComplaint($this->conn);

            // Default fallback
            default:
                return new OtherComplaint($this->conn);
        }
    }
}
?>

