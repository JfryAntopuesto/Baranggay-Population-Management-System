<?php
/**
 * Product (Abstract)
 * Defines the interface for objects the factory method creates
 * 
 * This is the abstract Product class in the Factory Method pattern.
 * All concrete request products must extend this class.
 */

abstract class Request {
    protected $requestID;
    protected $type;
    protected $message;
    protected $userID;
    protected $status;
    protected $created_at;
    protected $staff_comment;
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getRequestID() {
        return $this->requestID;
    }

    public function setRequestID($requestID) {
        $this->requestID = $requestID;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function setUserID($userID) {
        $this->userID = $userID;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
    }

    public function getStaffComment() {
        return $this->staff_comment;
    }

    public function setStaffComment($staff_comment) {
        $this->staff_comment = $staff_comment;
    }

    /**
     * Abstract method that must be implemented by subclasses
     * 
     * @return bool True if request is valid, false otherwise
     */
    abstract public function validate();

    /**
     * Abstract method for handling request-specific logic
     * 
     * @return array Request details specific to the type
     */
    abstract public function getRequestDetails();
}
?>

