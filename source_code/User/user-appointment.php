<?php
session_start();

// Handle POST requests for submitting appointments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Temporarily enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    // Use default PHP error log
    ini_set('error_log', 'php_errors.log');

    // Start output buffering to capture any unintended output
    ob_start();

    include '../../database/database-connection.php';
    include '../../database/database-operations.php';

    // Check if database connection is successful
    if (!$conn) {
        error_log('Database connection failed in user-appointment.php POST handler.');
        // Clear any buffered output and send JSON error
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
        exit();
    }

    // You must have the user ID in session or another way
    $user_id = $_SESSION['userID'] ?? null;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $purpose = $_POST['purpose'] ?? '';

    if ($user_id && $date && $time && $purpose) {
        $db = new DatabaseOperations($conn);
        // Set timezone for correct time handling, assuming your server time might differ
        date_default_timezone_set('Asia/Manila'); // Or your appropriate timezone
        
        $result = $db->insertAppointment($user_id, $date, $time, $purpose);
        
        if ($result) {
            // Clear any buffered output and send JSON success
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Appointment added!']);
        } else {
            // Check if the failure was due to an existing appointment
            if ($db->checkExistingAppointment($date, $time, $user_id)) {
                ob_clean();
                header('Content-Type: application/json');
                // Check if user already has an appointment on this day (either pending or approved)
                $sql = "SELECT COUNT(*) as count FROM (
                    SELECT appointment_date FROM appointments WHERE appointment_date = ? AND userID = ?
                    UNION ALL
                    SELECT appointment_date FROM approved_appointments WHERE appointment_date = ? AND userID = ?
                ) as user_appointments";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisi", $date, $user_id, $date, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['count'] > 0) {
                    echo json_encode(['success' => false, 'error' => 'You already have an appointment scheduled for this day.']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'This time slot is already taken. Please choose another time.']);
                }
            } else {
                // Log the database error for debugging on the server side
                error_log('Database insertion error in user-appointment.php: ' . $conn->error);
                // Clear any buffered output and send JSON error
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error: Could not insert appointment.']);
            }
        }
    } else {
        error_log('Missing required fields in user-appointment.php POST handler.');
        // Clear any buffered output and send JSON error
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error: Missing required fields.']);
    }
    $conn->close(); // Close connection
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Set Appointment - Barangay Population Management System</title>
  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      margin: 0;
      background-color: #f5f5f5;
    }
    header {
      background: linear-gradient(135deg, #0033cc, #0066ff);
      color: white;
      text-align: left;
      padding: 25px 40px;
      font-size: 24px;
      font-weight: bold;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      gap: 18px;
    }
    .back-arrow {
      font-size: 28px;
      cursor: pointer;
      margin-right: 10px;
      transition: color 0.2s;
    }
    .back-arrow:hover {
      color: #ffeb3b;
    }
    .container {
      display: flex;
      justify-content: center;
      gap: 30px;
      max-width: 1400px;
      margin: 40px auto 0 auto;
      min-height: 600px;
    }
    .form-panel {
      width: 32%;
      background: white;
      padding: 30px 25px 25px 25px;
      border-radius: 15px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.1);
      height: fit-content;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .form-panel label {
      font-weight: bold;
      color: #0033cc;
      margin-bottom: 4px;
    }
    .form-panel input, .form-panel select, .form-panel textarea {
      width: 100%;
      padding: 10px;
      border: 2px solid #0033cc;
      border-radius: 8px;
      font-size: 16px;
      margin-bottom: 10px;
      background: #f8f9fa;
      color: #222;
      outline: none;
      transition: border 0.2s;
    }
    .form-panel input:focus, .form-panel select:focus, .form-panel textarea:focus {
      border: 2px solid #0066ff;
    }
    .form-panel textarea {
      min-height: 110px;
      resize: vertical;
    }
    .form-panel .auto {
      color: #888;
      font-style: italic;
      font-size: 15px;
      margin-left: 8px;
    }
    .form-panel button {
      width: 120px;
      align-self: flex-end;
      padding: 10px 0;
      background: #fff;
      color: #0033cc;
      border: 2px solid #0033cc;
      border-radius: 8px;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }
    .form-panel button:hover {
      background: #0033cc;
      color: #fff;
    }
    .status-panel {
      width: 32%;
      background: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.1);
      min-height: 400px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .status-title {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 10px;
      letter-spacing: 1px;
    }
    .pending {
      color: #ffc107;
    }
    .approved {
      color: #43a047;
    }
    .declined {
      color: #e53935;
    }
    .appt-card {
      border: 2px solid #0033cc;
      border-radius: 10px;
      padding: 16px 18px;
      margin-bottom: 10px;
      background: #f8f9fa;
      color: #222;
      font-size: 16px;
      transition: box-shadow 0.2s;
    }
    .appt-card.approved {
      border-color: #43a047;
      background: #e8f5e9;
    }
    .appt-card.declined {
      border-color: #e53935;
      background: #ffebee;
    }
    .appt-card strong {
      color: #0033cc;
    }
    .appt-card .auto {
      color: #888;
      font-style: italic;
      font-size: 15px;
    }
    @media (max-width: 1024px) {
      .container {
        flex-direction: column;
        gap: 20px;
      }
      .form-panel, .status-panel {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <header>
    <span class="back-arrow" onclick="window.location.href='user-dashboard.php'">&#8592;</span>
    SET APPOINTMENT
  </header>
  <div class="container">
    <form class="form-panel" id="appointmentForm" autocomplete="off">
      <div>
        <label for="date">DATE:</label>
        <input type="date" id="date" name="date" required>
      </div>
      <div>
        <label for="time">TIME:</label>
        <input type="time" id="time" name="time" required>
      </div>
      <div>
        <label for="purpose">PURPOSE:</label>
        <textarea id="purpose" name="purpose" maxlength="200" required></textarea>
      </div>
      <button type="submit">SEND</button>
    </form>
    <div class="status-panel">
      <div class="status-title pending">PENDING</div>
      <div id="pendingList"></div>
    </div>
    <div class="status-panel">
      <div class="status-title approved">APPROVED<span class="declined">/DECLINED</span></div>
      <div id="approvedList"></div>
      <div id="declinedList"></div>
    </div>
  </div>
  
  <script>
    function loadAppointments() {
      fetch('get-user-appointments.php')
        .then(response => response.json())
        .then(result => {
          if (!result.success) {
            document.getElementById('pendingList').innerHTML = '<div style="color:red;">' + (result.error || 'Failed to load appointments') + '</div>';
            return;
          }
          // Pending
          const pending = (result.pending || []).map(appt => `
            <div class="appt-card pending">
              <strong>APPOINTMENT #:</strong> ${appt.appointment_id}<br>
              <strong>DATE:</strong> ${appt.appointment_date}<br>
              <strong>TIME:</strong> ${appt.appointment_time}<br>
              <strong>PURPOSE:</strong> ${appt.purpose}<br>
            </div>
          `);
          document.getElementById('pendingList').innerHTML = pending.join('') || '<div>No pending appointments.</div>';
          // Approved
          const approved = (result.approved || []).map(appt => `
            <div class="appt-card approved">
              <strong>APPOINTMENT #:</strong> ${appt.appointment_id}<br>
              <strong>DATE:</strong> ${appt.appointment_date}<br>
              <strong>TIME:</strong> ${appt.appointment_time}<br>
              <strong>PURPOSE:</strong> ${appt.purpose}<br>
              <strong>STAFF COMMENT:</strong> ${appt.staff_comment || ''}<br>
            </div>
          `);
          document.getElementById('approvedList').innerHTML = approved.join('') || '<div>No approved appointments.</div>';
          // Declined
          const declined = (result.declined || []).map(appt => `
            <div class="appt-card declined">
              <strong>APPOINTMENT #:</strong> ${appt.appointment_id}<br>
              <strong>DATE:</strong> ${appt.appointment_date}<br>
              <strong>TIME:</strong> ${appt.appointment_time}<br>
              <strong>PURPOSE:</strong> ${appt.purpose}<br>
              <strong>STAFF COMMENT:</strong> ${appt.staff_comment || ''}<br>
            </div>
          `);
          document.getElementById('declinedList').innerHTML = declined.join('') || '';
        });
    }

    document.getElementById('appointmentForm').addEventListener('submit', function(event) {
      event.preventDefault();
      const form = this;
      const data = new FormData(form);

      fetch('user-appointment.php', {
        method: 'POST',
        body: data
      })
      .then(response => response.json()) // Expecting JSON response
      .then(result => {
        if (result.success) {
          alert(result.message);
          form.reset();
          loadAppointments();
        } else {
          alert('Error: ' + (result.error || 'Unknown error occurred.'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again later.');
      });
    });

    document.addEventListener('DOMContentLoaded', loadAppointments);
  </script>
</body>
</html> 