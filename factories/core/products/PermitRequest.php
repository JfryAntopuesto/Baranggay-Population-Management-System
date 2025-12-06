<?php
/**
 * Concrete Product
 * Permit Request implementation
 */

require_once __DIR__ . '/Request.php';

class PermitRequest extends Request {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID)) {
            return false;
        }
        return true;
    }

    public function getRequestDetails() {
        return [
            'type' => $this->type ?: 'Permit Request',
            'description' => 'Request for barangay permits and clearances (Business Clearance, etc.)',
            'message' => $this->message,
            'category' => 'Permit'
        ];
    }
}
?>

