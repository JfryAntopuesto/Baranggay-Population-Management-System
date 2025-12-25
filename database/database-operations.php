<?php
include "database-connection.php";

class DatabaseOperations {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // User Operations
    public function checkUsernameExists($username) {
        $check_sql = "SELECT * FROM user WHERE username = ?";
        $stmt = $this->conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function insertUser($firstname, $lastname, $middlename, $birthdate, $username, $password, $email = null, $email_notifications = false) {
        $insert_sql = "INSERT INTO user (firstname, lastname, middlename, birthdate, username, password, email, email_notifications) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($insert_sql);
        $email_notifications_int = $email_notifications ? 1 : 0;
        $stmt->bind_param("sssssssi", $firstname, $lastname, $middlename, $birthdate, $username, $password, $email, $email_notifications_int);
        return $stmt->execute();
    }

    // Moderator Operations
    public function checkModeratorLogin($username, $password) {
        $sql = "SELECT * FROM moderators WHERE username = ? AND password = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function checkUserLogin($username, $password) {
        $sql = "SELECT * FROM user WHERE username = ? AND password = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function getUserByEmail($email) {
        $sql = "SELECT * FROM user WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function getStaffCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM moderators WHERE role = 'staff'";
            $result = $this->conn->query($sql);
            if ($result) {
                $row = $result->fetch_assoc();
                return (int)$row['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error in getStaffCount: " . $e->getMessage());
            return 0;
        }
    }

    public function insertStaff($firstname, $middlename, $lastname, $birthdate, $gender, $username, $password, $role) {
        try {
            error_log("Starting insertStaff operation");
            
            // Check staff count limit
            if ($this->getStaffCount() >= 2) {
                error_log("Staff limit reached (2)");
                throw new Exception("Maximum number of staff members (2) has been reached");
            }
            
            // Start transaction
            $this->conn->begin_transaction();
            error_log("Transaction started");

            // First insert into moderators table
            $mod_sql = "INSERT INTO moderators (role, username, password) VALUES (?, ?, ?)";
            $mod_stmt = $this->conn->prepare($mod_sql);
            if (!$mod_stmt) {
                throw new Exception("Failed to prepare moderators insert statement: " . $this->conn->error);
            }
            
            $mod_stmt->bind_param("sss", $role, $username, $password);
            error_log("Attempting to insert into moderators table");
            
            if (!$mod_stmt->execute()) {
                throw new Exception("Failed to insert into moderators table: " . $mod_stmt->error);
            }
            
            // Get the inserted modID
            $modID = $this->conn->insert_id;
            error_log("Inserted into moderators table with modID: " . $modID);
            
            // Calculate age from birthdate
            $birthDate = new DateTime($birthdate);
            $today = new DateTime('today');
            $age = $birthDate->diff($today)->y;
            error_log("Calculated age: " . $age . " from birthdate: " . $birthdate);
            
            // Then insert into staff_details table
            $details_sql = "INSERT INTO staff_details (modID, firstname, middlename, lastname, birthdate, age, gender) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $details_stmt = $this->conn->prepare($details_sql);
            if (!$details_stmt) {
                throw new Exception("Failed to prepare staff_details insert statement: " . $this->conn->error);
            }
            
            $details_stmt->bind_param("issssis", $modID, $firstname, $middlename, $lastname, $birthdate, $age, $gender);
            error_log("Attempting to insert into staff_details table");
            
            if (!$details_stmt->execute()) {
                throw new Exception("Failed to insert into staff_details table: " . $details_stmt->error);
            }

            // Commit transaction
            $this->conn->commit();
            error_log("Transaction committed successfully");
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error in insertStaff: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function deleteStaff($modID) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // First delete from staff_details
            $details_sql = "DELETE FROM staff_details WHERE modID = ?";
            $details_stmt = $this->conn->prepare($details_sql);
            $details_stmt->bind_param("i", $modID);
            
            if (!$details_stmt->execute()) {
                throw new Exception("Failed to delete from staff_details");
            }

            // Then delete from moderators
            $mod_sql = "DELETE FROM moderators WHERE modID = ? AND role = 'staff'";
            $mod_stmt = $this->conn->prepare($mod_sql);
            $mod_stmt->bind_param("i", $modID);
            
            if (!$mod_stmt->execute()) {
                throw new Exception("Failed to delete from moderators");
            }

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error in deleteStaff: " . $e->getMessage());
            return false;
        }
    }

    public function getStaffMembers() {
        try {
            $sql = "SELECT m.modID, m.username, m.role, 
                           s.firstname, s.middlename, s.lastname, 
                           s.birthdate, s.age, s.gender
                    FROM moderators m
                    JOIN staff_details s ON m.modID = s.modID
                    WHERE m.role = 'staff'
                    ORDER BY s.lastname, s.firstname";
            
            $result = $this->conn->query($sql);
            $staff = array();
            
            while ($row = $result->fetch_assoc()) {
                $staff[] = $row;
            }
            
            return $staff;
        } catch (Exception $e) {
            error_log("Error in getStaffMembers: " . $e->getMessage());
            return array();
        }
    }

    // Purok Operations
    public function searchPuroks($searchTerm = '') {
        require_once __DIR__ . '/../config/barangay-config.php';
        
        $allowedPuroks = getAllowedPuroks();
        $placeholders = str_repeat('?,', count($allowedPuroks) - 1) . '?';
        
        $sql = "SELECT purokID, purok_name, araw, purok_pres, purok_code 
                FROM puroks 
                WHERE purok_name IN ($placeholders)";
        $params = $allowedPuroks;
        $types = str_repeat('s', count($allowedPuroks));

        if (!empty($searchTerm)) {
            $sql .= " AND (purok_name LIKE ? OR purok_code LIKE ?)";
            $searchPattern = '%' . $searchTerm . '%';
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $types .= 'ss';
        }

        $sql .= " ORDER BY purok_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $puroks = [];
        while ($row = $result->fetch_assoc()) {
            $puroks[] = $row;
        }

        return $puroks;
    }

    public function generatePurokCode($purok_name) {
        // Generate a unique code based on purok name and random numbers
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $purok_name), 0, 3));
        $random = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . $random;
    }

    public function purokCodeExists($purok_code) {
        $sql = "SELECT COUNT(*) as count FROM puroks WHERE purok_code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $purok_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function purokNameExists($purok_name) {
        $sql = "SELECT COUNT(*) as count FROM puroks WHERE purok_name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $purok_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function insertPurok($purok_name, $araw, $purok_pres) {
        try {
            // Include barangay configuration
            require_once __DIR__ . '/../config/barangay-config.php';
            
            // Validate purok name format (letters, spaces, and numbers only)
            if (!preg_match('/^[A-Za-z0-9\s]+$/', $purok_name)) {
                throw new Exception("Purok name can only contain letters, numbers, and spaces.");
            }

            // Capitalize first letter of each word in purok name and ensure first letter is capital
            $purok_name = ucwords(strtolower($purok_name));
            $purok_name = ucfirst($purok_name);
            
            // Validate against allowed puroks list
            if (!isPurokAllowed($purok_name)) {
                $allowedList = implode(', ', getAllowedPuroks());
                throw new Exception("Purok name '{$purok_name}' is not in the allowed list. Allowed puroks: {$allowedList}");
            }

            // Check if purok name already exists
            if ($this->purokNameExists($purok_name)) {
                throw new Exception("A purok with this name already exists.");
            }

            // Validate date (must be 2025 or later)
            $date = new DateTime($araw);
            $minDate = new DateTime('2025-01-01');
            if ($date < $minDate) {
                throw new Exception("Araw ng Purok must be in 2025 or later.");
            }

            // Generate unique purok code
            $purok_code = $this->generatePurokCode($purok_name);
            
            // Check if code already exists
            while ($this->purokCodeExists($purok_code)) {
                $purok_code = $this->generatePurokCode($purok_name);
            }

            $sql = "INSERT INTO puroks (purok_name, araw, purok_pres, purok_code) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssss", $purok_name, $araw, $purok_pres, $purok_code);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in insertPurok: " . $e->getMessage());
            throw $e; // Re-throw the exception to handle it in the calling code
        }
    }

    public function verifyPurokCode($purok_code) {
        $sql = "SELECT purokID, purok_name FROM puroks WHERE purok_code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $purok_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function updatePurok($purokID, $purok_name, $araw, $purok_pres) {
        try {
            // Include barangay configuration
            require_once __DIR__ . '/../config/barangay-config.php';
            
            // Capitalize first letter of each word in purok name
            $purok_name = ucwords(strtolower($purok_name));
            $purok_name = ucfirst($purok_name);
            
            // Validate against allowed puroks list
            if (!isPurokAllowed($purok_name)) {
                $allowedList = implode(', ', getAllowedPuroks());
                throw new Exception("Purok name '{$purok_name}' is not in the allowed list. Allowed puroks: {$allowedList}");
            }
            
            $sql = "UPDATE puroks SET purok_name = ?, araw = ?, purok_pres = ? WHERE purokID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssi", $purok_name, $araw, $purok_pres, $purokID);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in updatePurok: " . $e->getMessage());
            throw $e;
        }
    }

    public function deletePurok($purokID) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // First delete all households associated with this purok
            $household_sql = "DELETE FROM household WHERE purokID = ?";
            $household_stmt = $this->conn->prepare($household_sql);
            $household_stmt->bind_param("i", $purokID);
            $household_stmt->execute();

            // Then delete the purok
            $purok_sql = "DELETE FROM puroks WHERE purokID = ?";
            $purok_stmt = $this->conn->prepare($purok_sql);
            $purok_stmt->bind_param("i", $purokID);
            $result = $purok_stmt->execute();

            // Commit transaction
            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error in deletePurok: " . $e->getMessage());
            return false;
        }
    }

    public function getAllPuroks($page = 1, $per_page = 10) {
        require_once __DIR__ . '/../config/barangay-config.php';
        
        $offset = ($page - 1) * $per_page;
        $allowedPuroks = getAllowedPuroks();
        
        // Filter puroks to only include allowed ones
        $placeholders = str_repeat('?,', count($allowedPuroks) - 1) . '?';
        $sql = "SELECT purokID, purok_name, araw, purok_pres, purok_code 
                FROM puroks 
                WHERE purok_name IN ($placeholders)
                ORDER BY purok_name LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($allowedPuroks)) . 'ii';
        $params = array_merge($allowedPuroks, [$per_page, $offset]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $puroks = array();
        while ($row = $result->fetch_assoc()) {
            $puroks[] = $row;
        }
        return $puroks;
    }
    
    /**
     * Get all puroks without pagination (for dropdowns)
     * Returns only allowed puroks
     */
    public function getAllPuroksForDropdown() {
        require_once __DIR__ . '/../config/barangay-config.php';
        
        $allowedPuroks = getAllowedPuroks();
        $placeholders = str_repeat('?,', count($allowedPuroks) - 1) . '?';
        $sql = "SELECT purokID, purok_name, araw, purok_pres, purok_code 
                FROM puroks 
                WHERE purok_name IN ($placeholders)
                ORDER BY purok_name";
        
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($allowedPuroks));
        $stmt->bind_param($types, ...$allowedPuroks);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $puroks = array();
        while ($row = $result->fetch_assoc()) {
            $puroks[] = $row;
        }
        return $puroks;
    }

    public function getTotalPuroksCount() {
        require_once __DIR__ . '/../config/barangay-config.php';
        
        $allowedPuroks = getAllowedPuroks();
        $placeholders = str_repeat('?,', count($allowedPuroks) - 1) . '?';
        $sql = "SELECT COUNT(*) as total FROM puroks WHERE purok_name IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($allowedPuroks));
        $stmt->bind_param($types, ...$allowedPuroks);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function getPurokById($purokID) {
        $sql = "SELECT purokID, purok_name, araw, purok_pres, purok_code FROM puroks WHERE purokID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $purokID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    // Request Operations
    public function createRequest($type, $message, $userID) {

        $random =str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $requestID = "{$random}";

        $stmt = $this->conn->prepare("INSERT INTO requests (requestID, type, message, userID, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $status = "pending";
        $stmt->bind_param("sssis", $requestID, $type, $message, $userID, $status);   
        if ($stmt->execute()) {
            return $requestID;
        }
        return false;
    }

    public function getRequestsByUserID($userID) {
        try {
            $requests = array(
                'pending' => array(),
                'finished' => array(),
                'declined' => array()
            );

            $sql = "SELECT requestID, type, message, userID, status, staff_comment, created_at, updated_at
                    FROM requests
                    WHERE userID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $key = strtolower($row['status']);
                if (!isset($requests[$key])) {
                    $requests[$key] = [];
                }
                $requests[$key][] = $row;
            }
            $stmt->close();

            foreach ($requests as $status => &$statusRequests) {
                usort($statusRequests, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }
            return $requests;
        } catch (Exception $e) {
            return array(
                'pending' => array(),
                'finished' => array(),
                'declined' => array()
            );
        }
    }

    public function updateRequestStatus($requestID, $status, $staff_comment) {
        error_log("Starting updateRequestStatus for request ID: " . $requestID . " with status: " . $status);
        
        try {
            $this->conn->begin_transaction();
            
            $select_sql = "SELECT * FROM requests WHERE requestID = ?";
            $select_stmt = $this->conn->prepare($select_sql);
            if (!$select_stmt) {
                throw new Exception('Prepare select failed: ' . $this->conn->error);
            }
            $select_stmt->bind_param("i", $requestID);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            $request_data = $result->fetch_assoc();
            $select_stmt->close();
            
            if (!$request_data) {
                throw new Exception('Request not found');
            }
            
            error_log("Retrieved request data: " . print_r($request_data, true));

            $update_sql = "UPDATE requests SET status = ?, staff_comment = ?, updated_at = NOW() WHERE requestID = ?";
            $update_stmt = $this->conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception('Prepare update failed: ' . $this->conn->error);
            }
            $update_stmt->bind_param("ssi", $status, $staff_comment, $requestID);
            if (!$update_stmt->execute()) {
                throw new Exception('Update requests failed: ' . $update_stmt->error);
            }
            $update_stmt->close();
            
            $notification_content = "Your " . $request_data['type'] . " request has been " . strtolower($status) . ".";
            if (!empty($staff_comment)) {
                $notification_content .= " Staff comment: " . $staff_comment;
            }
            $this->addNotification($request_data['userID'], $notification_content, $staff_comment);
            
            // Send email notification if user has enabled email notifications
            $emailPrefs = $this->getUserEmailPreferences($request_data['userID']);
            if ($emailPrefs && $emailPrefs['email_notifications'] && !empty($emailPrefs['email'])) {
                try {
                    require_once __DIR__ . '/../includes/email-helper.php';
                    $emailHelper = new EmailHelper();
                    $emailStatus = strtolower($status);
                    $emailHelper->sendRequestStatusEmail(
                        $emailPrefs['email'],
                        $request_data['type'],
                        $emailStatus,
                        $staff_comment
                    );
                } catch (Exception $e) {
                    error_log("Failed to send email notification: " . $e->getMessage());
                    // Don't fail the transaction if email fails
                }
            }
            
            $this->conn->commit();
            error_log("Successfully updated request status and added notification");
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in updateRequestStatus: " . $e->getMessage());
            throw $e;
        }
    }

    public function getRequestByID($requestID) {
        $sql = "SELECT requestID, type, message, userID, status, created_at FROM requests WHERE requestID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function getAllRequests() {
        $sql = "SELECT r.requestID, r.type, r.message, r.userID, r.status, r.created_at, u.firstname, u.lastname 
                FROM requests r 
                JOIN user u ON r.userID = u.userID 
                ORDER BY r.created_at DESC";
        $result = $this->conn->query($sql);
        
        $requests = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
        }
        return $requests;
    }

    // Household Operations
    public function getHouseholdsByPurok($purokID, $offset = 0, $limit = 10) {
        // Get household heads from the household table with pagination
        $sql = "SELECT h.householdID, u.firstname, u.middlename, u.lastname
                FROM household h
                JOIN user u ON h.userID = u.userID
                WHERE h.purokID = ?
                ORDER BY h.householdID
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $purokID, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $households = array();
        while ($row = $result->fetch_assoc()) {
            $households[] = [
                'householdID' => $row['householdID'],
                'household_head' => $row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']
            ];
        }
        return $households;
    }

    public function getHouseholdCountByPurok($purokID) {
        $sql = "SELECT COUNT(*) as total FROM household WHERE purokID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $purokID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    // Helper method to get member count for a household
    private function getMemberCount($householdID) {
        $sql = "SELECT COUNT(*) as count FROM members WHERE householdID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $householdID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }

    public function getUserHouseholdInfo($userID) {
        $sql = "SELECT h.householdID, h.purokID, p.purok_name 
                FROM household h 
                LEFT JOIN puroks p ON h.purokID = p.purokID 
                WHERE h.userID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function getProfilePictureByUserID($userID) {
        $sql = "SELECT path FROM user_pfp WHERE userID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['path'];
        }
        return false;
    }

    public function getHouseholdMembersCount($householdID) {
        $sql = "SELECT COUNT(*) as count FROM members WHERE householdID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $householdID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    public function insertProfilePicture($userID, $fileDestination) {

        $sqlCheck = "SELECT * FROM user_pfp WHERE userID = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $userID);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();

        if ($result->num_rows > 0) {
            // Update existing record
            $sql = "UPDATE user_pfp SET path = ? WHERE userID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $fileDestination, $userID);
        } else {
            // Insert new record
            $sql = "INSERT INTO user_pfp (userID, path) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $userID, $fileDestination);
        }
        return $stmt->execute();
    }

    public function getUserIDByUsername($username) {
        $sql = "SELECT userID FROM user WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['userID'];
        }
        return false;
    }

    public function getUserEmailPreferences($userID) {
        $sql = "SELECT email, email_notifications FROM user WHERE userID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return [
                'email' => $row['email'],
                'email_notifications' => (bool)$row['email_notifications']
            ];
        }
        return false;
    }

    public function insertHouseholdMember($householdID, $firstname, $middlename, $lastname, $sex, $birthdate, $relationship) {
        // Calculate age from birthdate
        $birthDate = new DateTime($birthdate);
        $today = new DateTime('today');
        $age = $birthDate->diff($today)->y;

        $sql = "INSERT INTO members (householdID, firstname, middlename, lastname, age, sex, birthdate, relationship) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        // householdID (int), firstname (string), middlename (string), lastname (string), age (int), sex (string), birthdate (string), relationship (string)
        $stmt->bind_param("isssisss", $householdID, $firstname, $middlename, $lastname, $age, $sex, $birthdate, $relationship);
        return $stmt->execute();
    }

    public function getHouseholdIDByUserID($userID) {
        $sql = "SELECT householdID FROM household WHERE userID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['householdID'];
        }
        return null;
    }

    public function getHouseholdMembers($householdID) {
        $sql = "SELECT * FROM members WHERE householdID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $householdID);
        $stmt->execute();
        $result = $stmt->get_result();
        $members = array();
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        return $members;
    }
    // Add this method to fetch the barangay profile
    public function getBaranggayProfile() {
        try {
            $sql = "SELECT * FROM baranggay_profile LIMIT 1";
            $result = $this->conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $profile = $result->fetch_assoc();
                // Ensure consistent field names
                if (isset($profile['current_capitan'])) {
                    $profile['current_captain'] = $profile['current_capitan'];
                    unset($profile['current_capitan']);
                }
                return $profile;
            }
            return null;
        } catch (Exception $e) {
            error_log("Error in getBaranggayProfile: " . $e->getMessage());
            return null;
        }
    }

    public function updateBaranggayProfile($id, $baranggay_name, $baranggay_capital, $city, $araw_ng_barangay, $current_captain) {
        // Include barangay configuration
        require_once __DIR__ . '/../config/barangay-config.php';
        
        // Enforce hard-coded barangay information
        $baranggay_name = BARANGAY_NAME;
        $city = BARANGAY_CITY;
        
        $sql = "UPDATE baranggay_profile SET 
                baranggay_name = ?, 
                baranggay_capital = ?, 
                city = ?, 
                araw_ng_barangay = ?, 
                current_captain = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $baranggay_name, $baranggay_capital, $city, $araw_ng_barangay, $current_captain, $id);
        return $stmt->execute();
    }

    public function insertBaranggayProfile($baranggay_name, $baranggay_capital, $city, $araw_ng_barangay, $current_captain) {
        // Include barangay configuration
        require_once __DIR__ . '/../config/barangay-config.php';
        
        // Enforce hard-coded barangay information
        $baranggay_name = BARANGAY_NAME;
        $city = BARANGAY_CITY;
        
        $sql = "INSERT INTO baranggay_profile (baranggay_name, baranggay_capital, city, araw_ng_barangay, current_captain) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssss", $baranggay_name, $baranggay_capital, $city, $araw_ng_barangay, $current_captain);
        return $stmt->execute();
    }

    public function checkBaranggayProfileExists() {
        $sql = "SELECT COUNT(*) as count FROM baranggay_profile";
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        }
        return false;
    }

    // Returns the total population: all users + all household members
    public function getTotalPopulation() {
        // Count all users
        $userCount = 0;
        $sqlUsers = "SELECT COUNT(*) as count FROM user";
        $resultUsers = $this->conn->query($sqlUsers);
        if ($resultUsers) {
            $row = $resultUsers->fetch_assoc();
            $userCount = (int)$row['count'];
        }
        // Count all members
        $memberCount = 0;
        $sqlMembers = "SELECT COUNT(*) as count FROM members";
        $resultMembers = $this->conn->query($sqlMembers);
        if ($resultMembers) {
            $row = $resultMembers->fetch_assoc();
            $memberCount = (int)$row['count'];
        }
        // Total population is users + members
        return $userCount + $memberCount;
    }

    // Delete a household by ID
    public function deleteHousehold($householdID) {
        $sql = "DELETE FROM household WHERE householdID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $householdID);
        return $stmt->execute();
    }

    // Appointment Operations
    public function getAppointmentsCountForDate($date) {
        try {
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ? AND status IN ('pending', 'approved')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getAppointmentsCountForDate: " . $e->getMessage());
            return 0;
        }
    }

    public function checkExistingAppointment($appointment_date, $appointment_time, $user_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ? AND userID = ? AND status IN ('pending','approved')";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("si", $appointment_date, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) return true;

            $sql = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status IN ('pending','approved')";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("ss", $appointment_date, $appointment_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error in checkExistingAppointment: " . $e->getMessage());
            return false;
        }
    }

    public function insertAppointment($user_id, $appointment_date, $appointment_time, $purpose) {
        try {
            // Check if appointment already exists for this date and time or if user has another appointment on this day
            if ($this->checkExistingAppointment($appointment_date, $appointment_time, $user_id)) {
                return false;
            }

            $sql = "INSERT INTO appointments (userID, appointment_date, appointment_time, purpose, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("isss", $user_id, $appointment_date, $appointment_time, $purpose);
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error in insertAppointment: " . $e->getMessage());
            return false;
        }
    }

    // Appointment Queue Operations
    public function moveAppointmentToFinished($appointment_id, $staff_comment) {
        try {
            $sql = "UPDATE appointments SET status = 'approved', staff_comment = ?, updated_at = NOW() WHERE appointment_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare update failed: " . $this->conn->error);
            }
            $stmt->bind_param("si", $staff_comment, $appointment_id);
            $stmt->execute();

            // Fetch appointment for notification
            $appt = $this->getAppointmentById($appointment_id);
            if ($appt) {
                $content = "Your appointment scheduled for " . date('F d, Y', strtotime($appt['appointment_date'])) . " at " . $appt['appointment_time'] . " has been approved.";
                $this->addNotification($appt['userID'], $content, $staff_comment);
            }
            return true;
        } catch (Exception $e) {
            error_log("Error in moveAppointmentToFinished: " . $e->getMessage());
            return false;
        }
    }

    public function moveAppointmentToDeclined($appointment_id, $staff_comment) {
        try {
            $sql = "UPDATE appointments SET status = 'declined', staff_comment = ?, updated_at = NOW() WHERE appointment_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare update failed: " . $this->conn->error);
            }
            $stmt->bind_param("si", $staff_comment, $appointment_id);
            $stmt->execute();

            $appt = $this->getAppointmentById($appointment_id);
            if ($appt) {
                $content = "Your appointment scheduled for " . date('F d, Y', strtotime($appt['appointment_date'])) . " at " . $appt['appointment_time'] . " has been declined.";
                $this->addNotification($appt['userID'], $content, $staff_comment);
            }
            return true;
        } catch (Exception $e) {
            error_log("Error in moveAppointmentToDeclined: " . $e->getMessage());
            return false;
        }
    }

    public function getFinishedAppointments() {
        $sql = "SELECT * FROM appointments WHERE status = 'approved' ORDER BY updated_at DESC";
        $result = $this->conn->query($sql);
        $appts = [];
        while ($row = $result->fetch_assoc()) {
            $appts[] = $row;
        }
        return $appts;
    }

    // Announcement Functions
    public function hasUserSeenAnnouncement($annID, $userID) {
        try {
            $query = "SELECT COUNT(*) as count FROM seen_announcement WHERE annID = ? AND userID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $annID, $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error in hasUserSeenAnnouncement: " . $e->getMessage());
            return false;
        }
    }

    public function markAnnouncementAsSeen($annID, $userID) {
        try {
            // Check if already seen
            if ($this->hasUserSeenAnnouncement($annID, $userID)) {
                return true;
            }

            // Mark as seen
            $query = "INSERT INTO seen_announcement (annID, userID) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $annID, $userID);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in markAnnouncementAsSeen: " . $e->getMessage());
            return false;
        }
    }

    public function getAnnouncements($limit = null) {
        try {
            $query = "SELECT a.*, bp.baranggay_name,
                     (SELECT COUNT(*) FROM announcement_likes WHERE annID = a.annID) as like_count,
                     (SELECT COUNT(*) FROM seen_announcement WHERE annID = a.annID) as seen_count
                     FROM announcement a 
                     CROSS JOIN baranggay_profile bp 
                     ORDER BY a.datetime DESC";

            if ($limit !== null) {
                $query .= " LIMIT ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $limit);
            } else {
                $stmt = $this->conn->prepare($query);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $announcements = [];
            while ($row = $result->fetch_assoc()) {
                $announcements[] = [
                    'annID' => $row['annID'],
                    'title' => $row['baranggay_name'],
                    'date' => date('M d, Y h:i A', strtotime($row['datetime'])),
                    'message' => $row['content'],
                    'like_count' => $row['like_count'],
                    'seen_count' => $row['seen_count']
                ];
            }
            return $announcements;
        } catch (Exception $e) {
            error_log("Error in getAnnouncements: " . $e->getMessage());
            return [];
        }
    }

    public function hasUserLikedAnnouncement($annID, $userID) {
        try {
            $query = "SELECT COUNT(*) as count FROM announcement_likes WHERE annID = ? AND userID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $annID, $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error in hasUserLikedAnnouncement: " . $e->getMessage());
            return false;
        }
    }

    public function toggleAnnouncementLike($annID, $userID) {
        try {
            // Check if like exists
            $hasLiked = $this->hasUserLikedAnnouncement($annID, $userID);
            
            if ($hasLiked) {
                // Remove like
                $query = "DELETE FROM announcement_likes WHERE annID = ? AND userID = ?";
            } else {
                // Add like
                $query = "INSERT INTO announcement_likes (annID, userID) VALUES (?, ?)";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $annID, $userID);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in toggleAnnouncementLike: " . $e->getMessage());
            return false;
        }
    }

    public function addAnnouncement($content) {
        try {
            $query = "INSERT INTO announcement (content) VALUES (?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $content);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in addAnnouncement: " . $e->getMessage());
            return false;
        }
    }

    // Notification Functions
    public function addNotification($userID, $content, $staff_comment = null) {
        try {
            error_log("Attempting to add notification for userID: " . $userID);
            error_log("Notification content: " . $content);
            error_log("Staff comment: " . ($staff_comment ?? 'none'));

            // First check if the tables exist
            $check_unread = "SHOW TABLES LIKE 'unread_notifications'";
            $check_read = "SHOW TABLES LIKE 'read_notifications'";
            $unread_result = $this->conn->query($check_unread);
            $read_result = $this->conn->query($check_read);
            
            if ($unread_result->num_rows === 0) {
                error_log("unread_notifications table does not exist, creating it...");
                // Create the unread_notifications table if it doesn't exist
                $create_unread = "CREATE TABLE unread_notifications (
                    notifID INT PRIMARY KEY AUTO_INCREMENT,
                    userID INT NOT NULL,
                    content TEXT NOT NULL,
                    staff_comment TEXT,
                    datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (userID) REFERENCES user(userID)
                )";
                
                if (!$this->conn->query($create_unread)) {
                    throw new Exception("Failed to create unread_notifications table: " . $this->conn->error);
                }
                error_log("unread_notifications table created successfully");
            }

            if ($read_result->num_rows === 0) {
                error_log("read_notifications table does not exist, creating it...");
                // Create the read_notifications table if it doesn't exist
                $create_read = "CREATE TABLE read_notifications (
                    notifID INT PRIMARY KEY AUTO_INCREMENT,
                    userID INT NOT NULL,
                    content TEXT NOT NULL,
                    staff_comment TEXT,
                    datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (userID) REFERENCES user(userID)
                )";
                
                if (!$this->conn->query($create_read)) {
                    throw new Exception("Failed to create read_notifications table: " . $this->conn->error);
                }
                error_log("read_notifications table created successfully");
            }

            $query = "INSERT INTO unread_notifications (userID, content, staff_comment) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Failed to prepare statement: " . $this->conn->error);
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("iss", $userID, $content, $staff_comment);
            
            if (!$stmt->execute()) {
                error_log("Failed to execute statement: " . $stmt->error);
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }

            error_log("Notification added successfully. Insert ID: " . $this->conn->insert_id);
            return true;
        } catch (Exception $e) {
            error_log("Error in addNotification: " . $e->getMessage());
            return false;
        }
    }

    // Add method to get user's notifications
    public function getUserNotifications($userID) {
        try {
            // Get both unread and read notifications
            $unread_query = "SELECT notifID, userID, content, staff_comment, datetime FROM unread_notifications WHERE userID = ? ORDER BY datetime DESC";
            $stmt = $this->conn->prepare($unread_query);
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $unread_result = $stmt->get_result();
            
            $read_query = "SELECT notifID, userID, content, staff_comment, datetime FROM read_notifications WHERE userID = ? ORDER BY datetime DESC";
            $stmt = $this->conn->prepare($read_query);
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $read_result = $stmt->get_result();
            
            $notifications = [];
            
            // Add unread notifications
            while ($row = $unread_result->fetch_assoc()) {
                $row['is_read'] = false;
                $notifications[] = $row;
            }
            
            // Add read notifications
            while ($row = $read_result->fetch_assoc()) {
                $row['is_read'] = true;
                $notifications[] = $row;
            }
            
            // Sort by datetime, most recent first
            usort($notifications, function($a, $b) {
                return strtotime($b['datetime']) - strtotime($a['datetime']);
            });
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error in getUserNotifications: " . $e->getMessage());
            return [];
        }
    }

    // Update markNotificationAsRead to move to read_notifications table
    public function markNotificationAsRead($notifID) {
        try {
            error_log("Starting markNotificationAsRead for notification ID: " . $notifID);
            
            // Start transaction
            $this->conn->begin_transaction();
            error_log("Transaction started");
            
            // Get the notification from unread_notifications
            $query = "SELECT * FROM unread_notifications WHERE notifID = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare select statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $notifID);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute select statement: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $notification = $result->fetch_assoc();
            
            if (!$notification) {
                $this->conn->rollback();
                error_log("Notification not found with ID: " . $notifID);
                throw new Exception("Notification not found or already marked as read");
            }
            
            error_log("Found notification: " . json_encode($notification));
            
            // Insert into read_notifications with the same notifID
            $insert_query = "INSERT INTO read_notifications (notifID, userID, content, staff_comment, datetime) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($insert_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare insert statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("iisss", 
                $notifID,
                $notification['userID'], 
                $notification['content'], 
                $notification['staff_comment'],
                $notification['datetime']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into read_notifications: " . $stmt->error);
            }
            
            error_log("Successfully inserted into read_notifications");
            
            // Delete from unread_notifications
            $delete_query = "DELETE FROM unread_notifications WHERE notifID = ?";
            $stmt = $this->conn->prepare($delete_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare delete statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $notifID);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete from unread_notifications: " . $stmt->error);
            }
            
            $deleted_rows = $stmt->affected_rows;
            error_log("Deleted rows from unread_notifications: " . $deleted_rows);
            
            if ($deleted_rows === 0) {
                throw new Exception("Failed to delete notification from unread_notifications - no rows affected");
            }
            
            error_log("Successfully deleted from unread_notifications");
            
            // Commit transaction
            $this->conn->commit();
            error_log("Transaction committed successfully");
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in markNotificationAsRead: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e; // Re-throw the exception to be handled by the caller
        }
    }

    // Update getReadNotifications to use the correct table name
    public function getReadNotifications($userID) {
        try {
            $query = "SELECT notifID, userID, content, staff_comment, datetime FROM read_notifications WHERE userID = ? ORDER BY datetime DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $row['is_read'] = true;
                $notifications[] = $row;
            }
            return $notifications;
        } catch (Exception $e) {
            error_log("Error in getReadNotifications: " . $e->getMessage());
            return [];
        }
    }

    // Add methods to get request counts by status
    public function getRequestCountByStatus($status) {
        try {
            $query = "SELECT COUNT(*) as count FROM requests WHERE status = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'];
        } catch (Exception $e) {
            error_log("Error in getRequestCountByStatus: " . $e->getMessage());
            return 0;
        }
    }

    // Add method to get count of upcoming appointments
    public function getUpcomingAppointmentsCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date >= CURDATE() AND status IN ('pending','approved')";
            $result = $this->conn->query($query);
            if ($result) {
                $row = $result->fetch_assoc();
                return $row['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error in getUpcomingAppointmentsCount: " . $e->getMessage());
            return 0;
        }
    }
    
    // Delete finished and declined requests that are older than 30 seconds
    public function deleteOldRequests() {
        try {
            error_log("Starting deleteOldRequests function at " . date('Y-m-d H:i:s'));
            
            $this->conn->begin_transaction();
            error_log("Transaction started");

            $deleteQuery = "DELETE FROM requests WHERE status IN ('FINISHED','DECLINED','APPROVED','approved','declined','finished') AND TIMESTAMPDIFF(SECOND, created_at, NOW()) >= 30";
            $result = $this->conn->query($deleteQuery);
            if ($result) {
                $deleted_count = $this->conn->affected_rows;
                error_log("Successfully deleted " . $deleted_count . " old handled requests");
            } else {
                error_log("Error deleting old handled requests: " . $this->conn->error);
            }

            $this->conn->commit();
            error_log("Transaction committed successfully");
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error in deleteOldRequests: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function getStaffDetails($modID) {
        try {
            $sql = "SELECT m.modID, m.username, m.role, 
                           s.firstname, s.middlename, s.lastname, 
                           s.birthdate, s.age, s.gender
                    FROM moderators m
                    JOIN staff_details s ON m.modID = s.modID
                    WHERE m.modID = ? AND m.role = 'staff'";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare staff details query: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $modID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error in getStaffDetails: " . $e->getMessage());
            return false;
        }
    }

    // Complaint Operations
    public function createComplaint($type, $message, $userID, $complainedPerson) {
        $random = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $complaintID = "{$random}";
        $stmt = $this->conn->prepare("INSERT INTO complaints (complaintID, type, message, userID, complained_person, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("sssis", $complaintID, $type, $message, $userID, $complainedPerson);
        if ($stmt->execute()) {
            return $complaintID;
        }
        return false;
    }

    public function getComplaintsByUserID($userID) {
        try {
            error_log("Fetching complaints for userID: " . $userID);
            $complaints = [
                'pending' => [],
                'resolved' => [],
                'declined' => []
            ];

            $sql = "SELECT complaintID, type, message, userID, complained_person, status, staff_comment, created_at 
                    FROM complaints 
                    WHERE userID = ?
                    ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $statusKey = strtolower($row['status']);
                if (!isset($complaints[$statusKey])) {
                    $complaints[$statusKey] = [];
                }
                $complaints[$statusKey][] = $row;
            }
            $stmt->close();

            return $complaints;
        } catch (Exception $e) {
            error_log("Error in getComplaintsByUserID: " . $e->getMessage());
            return [
                'pending' => [],
                'resolved' => [],
                'declined' => []
            ];
        }
    }

    public function updateComplaintStatus($complaintID, $status, $staff_comment = null) {
        $sql = "UPDATE complaints SET status = ?, staff_comment = ?, updated_at = NOW() WHERE complaintID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $staff_comment, $complaintID);
        return $stmt->execute();
    }

    public function getComplaintByID($complaintID) {
        $sql = "SELECT complaintID, type, message, userID, complained_person, status, created_at FROM complaints WHERE complaintID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $complaintID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function getAllComplaints() {
        $sql = "SELECT complaintID, type, message, userID, complained_person, status, created_at FROM complaints ORDER BY created_at DESC";
        $result = $this->conn->query($sql);
        $complaints = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $complaints[] = $row;
            }
        }
        return $complaints;
    }

    // Delete old notifications and complaints
    public function deleteOldNotifications() {
        try {
            error_log("Starting deleteOldNotifications function");
            
            // Start transaction
            $this->conn->begin_transaction();
            
            // Check if read_notifications table exists
            $check_read = "SHOW TABLES LIKE 'read_notifications'";
            $read_exists = $this->conn->query($check_read)->num_rows > 0;
            error_log("read_notifications table exists: " . ($read_exists ? "yes" : "no"));
            
            if ($read_exists) {
                // Count records before deletion
                $count_query = "SELECT COUNT(*) as count FROM read_notifications";
                $count_result = $this->conn->query($count_query);
                $count_row = $count_result->fetch_assoc();
                error_log("Total read notifications before deletion: " . $count_row['count']);
                
                // Delete read notifications that are exactly 30 seconds old
                $readQuery = "DELETE FROM read_notifications WHERE TIMESTAMPDIFF(SECOND, datetime, NOW()) >= 30";
                error_log("Executing query: " . $readQuery);
                $result = $this->conn->query($readQuery);
                if ($result) {
                    $deleted_count = $this->conn->affected_rows;
                    error_log("Successfully deleted " . $deleted_count . " old read notifications");
                } else {
                    error_log("Error deleting old read notifications: " . $this->conn->error);
                }
            }
            
            // Commit transaction
            $this->conn->commit();
            error_log("Transaction committed successfully for notifications cleanup");
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error in deleteOldNotifications: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function deleteOldComplaints() {
        try {
            error_log("Starting deleteOldComplaints function");
            
            $this->conn->begin_transaction();
            
            $deleteQuery = "DELETE FROM complaints WHERE status IN ('resolved','declined','RESOLVED','DECLINED') AND created_at < DATE_SUB(NOW(), INTERVAL 30 SECOND)";
            $result = $this->conn->query($deleteQuery);
            if ($result) {
                $deleted_count = $this->conn->affected_rows;
                error_log("Successfully deleted " . $deleted_count . " old handled complaints");
            }
            
            $this->conn->commit();
            error_log("Transaction committed successfully for complaints cleanup");
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in deleteOldComplaints: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function getAppointmentById($appointment_id) {
        $sql = "SELECT a.*, CONCAT(u.firstname, ' ', u.lastname) as userName 
                FROM appointments a 
                JOIN user u ON a.userID = u.userID 
                WHERE a.appointment_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Get all users who opted into email notifications and have a valid email
     */
    public function getEmailSubscribers() {
        $subscribers = [];
        $sql = "SELECT userID, email, firstname, lastname 
                FROM user 
                WHERE email_notifications = 1 AND email IS NOT NULL AND email != ''";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $subscribers[] = $row;
            }
        }
        return $subscribers;
    }
}
