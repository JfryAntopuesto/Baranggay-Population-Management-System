<?php
/**
 * Concrete Product
 * Noise Complaint implementation
 */

require_once __DIR__ . '/Complaint.php';

class NoiseComplaint extends Complaint {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID) || empty($this->complained_person)) {
            return false;
        }
        return true;
    }

    public function getComplaintDetails() {
        return [
            'type' => $this->type ?: 'Noise Complaint',
            'description' => 'Complaint regarding excessive noise and disturbances',
            'message' => $this->message,
            'complained_person' => $this->complained_person,
            'category' => 'Noise'
        ];
    }

    public function getSeverityLevel() {
        return 'moderate';
    }
}
?>

