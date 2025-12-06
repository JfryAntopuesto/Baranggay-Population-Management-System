<?php
/**
 * Concrete Product
 * Behavior Complaint implementation
 */

require_once __DIR__ . '/Complaint.php';

class BehaviorComplaint extends Complaint {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID) || empty($this->complained_person)) {
            return false;
        }
        return true;
    }

    public function getComplaintDetails() {
        return [
            'type' => $this->type ?: 'Behavior Complaint',
            'description' => 'Complaint regarding public safety, public disturbance, or inappropriate behavior',
            'message' => $this->message,
            'complained_person' => $this->complained_person,
            'category' => 'Behavior'
        ];
    }

    public function getSeverityLevel() {
        return 'high';
    }
}
?>

