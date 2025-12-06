<?php
/**
 * Concrete Product
 * Certification Request implementation
 */

require_once __DIR__ . '/Request.php';

class CertificationRequest extends Request {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID)) {
            return false;
        }
        return true;
    }

    public function getRequestDetails() {
        return [
            'type' => $this->type ?: 'Certification Request',
            'description' => 'Request for various barangay certifications (Residency, Indigency, Good Moral Character, Solo Parent, etc.)',
            'message' => $this->message,
            'category' => 'Certification'
        ];
    }
}
?>

