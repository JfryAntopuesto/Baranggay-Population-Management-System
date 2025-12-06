<?php
/**
 * Concrete Product
 * Other Request implementation (default/fallback)
 */

require_once __DIR__ . '/Request.php';

class OtherRequest extends Request {
    public function validate() {
        if (empty($this->type) || empty($this->message) || empty($this->userID)) {
            return false;
        }
        return true;
    }

    public function getRequestDetails() {
        return [
            'type' => $this->type ?: 'Other Request',
            'description' => 'Other types of barangay requests',
            'message' => $this->message,
            'category' => 'Other'
        ];
    }
}
?>

