<?php
require_once __DIR__ . '/event-dispatcher.php';
require_once __DIR__ . '/email-helper.php';

/**
 * Register listeners (observers) for domain events.
 * Each listener is a callable receiving $payload array.
 */
$dispatcher = get_event_dispatcher();

// Listener for request status changes
$dispatcher->addListener('request_status_changed', function(array $payload) {
    if (!isset($payload['email'], $payload['requestType'], $payload['status'])) {
        return;
    }
    $comment = $payload['staff_comment'] ?? '';
    $helper = new EmailHelper();
    $helper->sendRequestStatusEmail($payload['email'], $payload['requestType'], $payload['status'], $comment);
});

// Listener for complaint status changes
$dispatcher->addListener('complaint_status_changed', function(array $payload) {
    if (!isset($payload['email'], $payload['complaintType'], $payload['status'])) {
        return;
    }
    $comment = $payload['staff_comment'] ?? '';
    $helper = new EmailHelper();
    $helper->sendComplaintStatusEmail($payload['email'], $payload['complaintType'], $payload['status'], $comment);
});

// Listener for appointment status changes
$dispatcher->addListener('appointment_status_changed', function(array $payload) {
    if (!isset($payload['email'], $payload['appointment_date'], $payload['appointment_time'], $payload['status'])) {
        return;
    }
    $comment = $payload['staff_comment'] ?? '';
    $helper = new EmailHelper();
    $helper->sendAppointmentStatusEmail(
        $payload['email'],
        $payload['appointment_date'],
        $payload['appointment_time'],
        $payload['status'],
        $comment
    );
});

// Listener for announcements (broadcast to all opted-in users)
$dispatcher->addListener('announcement_created', function(array $payload) {
    if (!isset($payload['db'], $payload['content'])) {
        return;
    }
    $db = $payload['db']; // DatabaseOperations instance
    $content = $payload['content'];
    $users = $db->getEmailSubscribers();
    if (!$users) return;

    $helper = new EmailHelper();
    foreach ($users as $user) {
        if (empty($user['email'])) {
            continue;
        }
        $subject = "New Barangay Announcement";
        $body = "
        <html>
        <body>
            <p>Dear {$user['firstname']} {$user['lastname']},</p>
            <p>There is a new announcement:</p>
            <p><strong>{$content}</strong></p>
            <p>Thank you.</p>
        </body>
        </html>";
        $helper->sendEmail($user['email'], $subject, $body);
    }
});
?>
