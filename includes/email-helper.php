<?php
// Best-effort load PHPMailer; never fatal if missing
$phpmailerAvailable = false;

// 1) Try local bundled PHPMailer (if you drop it into includes/PHPMailer)
$localMailerBase = __DIR__ . '/PHPMailer';
if (file_exists($localMailerBase . '/PHPMailer.php')) {
    @require_once $localMailerBase . '/PHPMailer.php';
    @require_once $localMailerBase . '/SMTP.php';
    @require_once $localMailerBase . '/Exception.php';
}

// 2) Try Composer autoload if fully available (avoid partial vendor crashes)
if (
    !$phpmailerAvailable &&
    file_exists(__DIR__ . '/../vendor/autoload.php') &&
    file_exists(__DIR__ . '/../vendor/react/promise/src/functions_include.php')
) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
    } catch (Exception $e) {
        error_log("PHPMailer autoload failed: " . $e->getMessage());
    }
}

// Check class availability
if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $phpmailerAvailable = true;
} else {
    error_log("EmailHelper: PHPMailer not found (no vendor/ or local PHPMailer folder). Emails will be skipped.");
}

class EmailHelper {
    private $mail;
    private $phpmailerAvailable;
    private $config;
    
    public function __construct() {
        // Load email configuration
        $this->config = require __DIR__ . '/email-config.php';
        
        // Check if PHPMailer is available
        $this->phpmailerAvailable = $GLOBALS['phpmailerAvailable'] ?? class_exists('PHPMailer\PHPMailer\PHPMailer');
        
        if (!$this->phpmailerAvailable) {
            error_log("EmailHelper: PHPMailer is not installed. Run 'composer install' to install PHPMailer.");
            return;
        }
        
        // Check if email password is configured
        if (empty($this->config['smtp_password'])) {
            error_log("EmailHelper: SMTP password is not configured in email-config.php");
            return;
        }
        
        try {
            if (!$this->phpmailerAvailable) {
                throw new \Exception("PHPMailer class not available");
            }
            $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings - Configure these based on your email server
            // For XAMPP, you can use Gmail SMTP or local mail server
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['smtp_username'];
            $this->mail->Password = $this->config['smtp_password'];
            $this->mail->SMTPSecure = $this->config['smtp_encryption'] === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = $this->config['smtp_port'];
            
            // Sender info
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mail->isHTML(true);
            
            error_log("EmailHelper: Initialized successfully");
        } catch (\Exception $e) {
            error_log("EmailHelper: Failed to initialize PHPMailer: " . $e->getMessage());
            $this->phpmailerAvailable = false;
        }
    }
    
    /**
     * Send email notification to user
     * @param string $to Email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $body) {
        // Check if PHPMailer is available
        if (!$this->phpmailerAvailable) {
            error_log("EmailHelper: Cannot send email - PHPMailer not available");
            return false;
        }
        
        // Check if email is configured
        if (empty($this->config['smtp_password'])) {
            error_log("EmailHelper: Cannot send email - SMTP password not configured");
            return false;
        }
        
        // Validate email address
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("EmailHelper: Invalid email address: " . $to);
            return false;
        }
        
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body); // Plain text version
            
            $result = $this->mail->send();
            if ($result) {
                error_log("EmailHelper: Email sent successfully to: " . $to);
            } else {
                error_log("EmailHelper: Email send returned false. Error: " . $this->mail->ErrorInfo);
            }
            return $result;
        } catch (\Exception $e) {
            error_log("EmailHelper: Email sending failed: " . $e->getMessage());
            error_log("EmailHelper: PHPMailer ErrorInfo: " . ($this->mail ? $this->mail->ErrorInfo : 'N/A'));
            return false;
        }
    }
    
    /**
     * Send notification email for request status change
     * @param string $to Email address
     * @param string $requestType Type of request
     * @param string $status New status (approved/declined)
     * @param string $staffComment Optional staff comment
     * @return bool Success status
     */
    public function sendRequestStatusEmail($to, $requestType, $status, $staffComment = '') {
        $subject = "Request Status Update - " . ucfirst($status);
        $statusText = ucfirst(strtolower($status));
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0033cc; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .status { font-weight: bold; color: #0033cc; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Barangay Population Management System</h2>
                </div>
                <div class='content'>
                    <p>Dear Resident,</p>
                    <p>Your <strong>{$requestType}</strong> request has been <span class='status'>{$statusText}</span>.</p>";
        
        if (!empty($staffComment)) {
            $body .= "<p><strong>Staff Comment:</strong> " . htmlspecialchars($staffComment) . "</p>";
        }
        
        $body .= "
                    <p>Thank you for using our services.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Send notification email for complaint status change
     * @param string $to Email address
     * @param string $complaintType Type of complaint
     * @param string $status New status (resolved/declined)
     * @param string $staffComment Optional staff comment
     * @return bool Success status
     */
    public function sendComplaintStatusEmail($to, $complaintType, $status, $staffComment = '') {
        $subject = "Complaint Status Update - " . ucfirst($status);
        $statusText = ucfirst(strtolower($status));
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0033cc; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .status { font-weight: bold; color: #0033cc; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Barangay Population Management System</h2>
                </div>
                <div class='content'>
                    <p>Dear Resident,</p>
                    <p>Your <strong>{$complaintType}</strong> complaint has been <span class='status'>{$statusText}</span>.</p>";
        
        if (!empty($staffComment)) {
            $body .= "<p><strong>Staff Comment:</strong> " . htmlspecialchars($staffComment) . "</p>";
        }
        
        $body .= "
                    <p>Thank you for reporting this matter.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Send notification email for appointment status change
     * @param string $to Email address
     * @param string $appointmentDate Appointment date
     * @param string $appointmentTime Appointment time
     * @param string $status New status (approved/declined)
     * @param string $staffComment Optional staff comment
     * @return bool Success status
     */
    public function sendAppointmentStatusEmail($to, $appointmentDate, $appointmentTime, $status, $staffComment = '') {
        $subject = "Appointment Status Update - " . ucfirst($status);
        $statusText = ucfirst(strtolower($status));
        $formattedDate = date('F d, Y', strtotime($appointmentDate));
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0033cc; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .status { font-weight: bold; color: #0033cc; }
                .appointment-info { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #0033cc; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Barangay Population Management System</h2>
                </div>
                <div class='content'>
                    <p>Dear Resident,</p>
                    <p>Your appointment has been <span class='status'>{$statusText}</span>.</p>
                    <div class='appointment-info'>
                        <p><strong>Date:</strong> {$formattedDate}</p>
                        <p><strong>Time:</strong> {$appointmentTime}</p>
                    </div>";
        
        if (!empty($staffComment)) {
            $body .= "<p><strong>Staff Comment:</strong> " . htmlspecialchars($staffComment) . "</p>";
        }
        
        $body .= "
                    <p>Thank you for scheduling with us.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $this->sendEmail($to, $subject, $body);
    }
}
