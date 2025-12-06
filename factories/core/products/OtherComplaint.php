<?php
/**
 * Concrete Product
 * Other Complaint implementation (default/fallback)
 */

require_once __DIR__ . '/Complaint.php';

class OtherComplaint extends Complaint {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID) || empty($this->complained_person)) {
            return false;
        }
        return true;
    }

    public function getComplaintDetails() {
        return [
            'type' => $this->type ?: 'Other Complaint',
            'description' => 'Other types of complaints (Sanitation, Street Lighting, Traffic, Water Supply, Waste Management, etc.)',
            'message' => $this->message,
            'complained_person' => $this->complained_person,
            'category' => 'Other'
        ];
    }

    public function getSeverityLevel() {
        return 'low';
    }
}
?>

