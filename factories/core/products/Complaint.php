<?php
/**
 * Product (Abstract)
 * Defines the interface for objects the factory method creates
 * 
 * This is the abstract Product class in the Factory Method pattern.
 * All concrete complaint products must extend this class.
 */

abstract class Complaint {
    protected $complaintID;
    protected $type;
    protected $message;
    protected $userID;
    protected $complained_person;
    protected $status;
    protected $created_at;
    protected $staff_comment;
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getComplaintID() {
        return $this->complaintID;
    }

    public function setComplaintID($complaintID) {
        $this->complaintID = $complaintID;
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

    public function getComplainedPerson() {
        return $this->complained_person;
    }

    public function setComplainedPerson($complained_person) {
        $this->complained_person = $complained_person;
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
     * @return bool True if complaint is valid, false otherwise
     */
    abstract public function validate();

    /**
     * Abstract method for handling complaint-specific logic
     * 
     * @return array Complaint details specific to the type
     */
    abstract public function getComplaintDetails();

    /**
     * Abstract method for severity assessment
     * 
     * @return string Severity level (low, moderate, high)
     */
    abstract public function getSeverityLevel();
}
?>

