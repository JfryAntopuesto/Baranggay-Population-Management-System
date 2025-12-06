<?php
/**
 * Concrete Product
 * Assistance Request implementation
 */

require_once __DIR__ . '/Request.php';

class AssistanceRequest extends Request {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID)) {
            return false;
        }
        return true;
    }

    public function getRequestDetails() {
        return [
            'type' => $this->type ?: 'Assistance Request',
            'description' => 'Request for barangay assistance and support (Financial Assistance, etc.)',
            'message' => $this->message,
            'category' => 'Assistance'
        ];
    }
}
?>

