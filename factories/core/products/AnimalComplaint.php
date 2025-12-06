<?php
/**
 * Concrete Product
 * Animal Complaint implementation
 */

require_once __DIR__ . '/Complaint.php';

class AnimalComplaint extends Complaint {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID) || empty($this->complained_person)) {
            return false;
        }
        return true;
    }

    public function getComplaintDetails() {
        return [
            'type' => $this->type ?: 'Animal Complaint',
            'description' => 'Complaint regarding animals, pets, or animal-related issues',
            'message' => $this->message,
            'complained_person' => $this->complained_person,
            'category' => 'Animal'
        ];
    }

    public function getSeverityLevel() {
        return 'moderate';
    }
}
?>

