<?php
/**
 * Concrete Product
 * Document Request implementation
 */

require_once __DIR__ . '/Request.php';

class DocumentRequest extends Request {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID)) {
            return false;
        }
        return true;
    }

    public function getRequestDetails() {
        return [
            'type' => $this->type ?: 'Document Request',
            'description' => 'Request for official documents and clearances (Barangay Clearance, etc.)',
            'message' => $this->message,
            'category' => 'Document'
        ];
    }
}
?>

