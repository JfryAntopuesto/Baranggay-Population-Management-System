<?php
/**
 * Concrete Product
 * Property Complaint implementation
 */

require_once __DIR__ . '/Complaint.php';

class PropertyComplaint extends Complaint {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID) || empty($this->complained_person)) {
            return false;
        }
        return true;
    }

    public function getComplaintDetails() {
        return [
            'type' => $this->type ?: 'Property Complaint',
            'description' => 'Complaint regarding property damage, disputes, or property-related issues',
            'message' => $this->message,
            'complained_person' => $this->complained_person,
            'category' => 'Property'
        ];
    }

    public function getSeverityLevel() {
        return 'high';
    }
}
?>

