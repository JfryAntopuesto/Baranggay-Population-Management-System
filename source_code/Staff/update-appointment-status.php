<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once '../../database/database-connection.php';
require_once '../../database/database-operations.php';
require_once '../../includes/event-listeners.php';

header('Content-Type: application/json');

try {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception("Invalid JSON data");
    }

    // Validate required fields
    if (!isset($data['appointment_id']) || !isset($data['status']) || !isset($data['staff_comment'])) {
        throw new Exception("Missing required fields");
    }

    $appointment_id = $data['appointment_id'];
    $status = strtoupper($data['status']);
    $staff_comment = $data['staff_comment'];

    // Validate status
    if (!in_array($status, ['APPROVED', 'DECLINED'])) {
        throw new Exception("Invalid status");
    }

    // Connect to database
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $db = new DatabaseOperations($conn);

    // Update appointment status
    if ($status === 'APPROVED') {
        $success = $db->moveAppointmentToFinished($appointment_id, $staff_comment);
    } else {
        $success = $db->moveAppointmentToDeclined($appointment_id, $staff_comment);
    }

    if (!$success) {
        throw new Exception("Failed to update appointment status");
    }

    // Get appointment details for notification
    $appointment = $db->getAppointmentById($appointment_id);
    
    if ($appointment) {
        // Create notification message
        $notification_content = "Your appointment scheduled for {$appointment['appointment_date']} at {$appointment['appointment_time']} has been " . 
            strtolower($status) . ". Staff comment: {$staff_comment}";
        
        // Add notification to unread_notifications table
        $notification_result = $db->addNotification(
            $appointment['userID'],
            $notification_content,
            $staff_comment
        );

        // Send email notification via observer if user enabled
        $emailPrefs = $db->getUserEmailPreferences($appointment['userID']);
        if ($emailPrefs) {
            $emailEnabled = ($emailPrefs['email_notifications'] == 1 || $emailPrefs['email_notifications'] === true || $emailPrefs['email_notifications'] === '1') && !empty($emailPrefs['email']);
            if ($emailEnabled) {
                $dispatcher = get_event_dispatcher();
                $dispatcher->dispatch('appointment_status_changed', [
                    'email' => $emailPrefs['email'],
                    'appointment_date' => $appointment['appointment_date'],
                    'appointment_time' => $appointment['appointment_time'],
                    'status' => strtolower($status),
                    'staff_comment' => $staff_comment
                ]);
            } else {
                error_log("Email notification skipped - Not enabled or no email address");
            }
        }

        // If WebSocket server is running, send real-time notification
        if ($notification_result) {
            $notification_data = [
                'type' => 'notification_update',
                'notifications' => [
                    [
                        'notifID' => $notification_result,
                        'content' => $notification_content,
                        'datetime' => date('Y-m-d H:i:s'),
                        'is_read' => 0
                    ]
                ]
            ];

            // Send to WebSocket server
            $ch = curl_init('http://localhost:8081');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Appointment status updated successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in update-appointment-status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 