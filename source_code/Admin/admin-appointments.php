<?php
// Start output buffering for AJAX requests to prevent any output before JSON
if (isset($_GET['ajax'])) {
    ob_start();
}

session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    if (isset($_GET['ajax'])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    header("Location: ../login.php");
    exit();
}

require_once '../../database/database-connection.php';
require_once '../../database/database-operations.php';

// Function to get appointments
function getAppointments($status) {
    global $conn;
    
    $query = "";
    switch($status) {
        case 'PENDING':
            $query = "SELECT a.appointment_id, a.userID, a.appointment_date, a.appointment_time, a.purpose, 
                            u.firstname, u.lastname, a.created_at
                     FROM appointments a 
                     JOIN user u ON a.userID = u.userID 
                     WHERE a.status = 'pending'
                     ORDER BY a.appointment_date, a.appointment_time";
            break;
        case 'APPROVED':
            $query = "SELECT a.appointment_id, a.userID, a.appointment_date, a.appointment_time, a.purpose, 
                            u.firstname, u.lastname, a.staff_comment, a.updated_at as approved_at
                     FROM appointments a 
                     JOIN user u ON a.userID = u.userID 
                     WHERE a.status = 'approved'
                     ORDER BY a.appointment_date, a.appointment_time";
            break;
        case 'DECLINED':
            $query = "SELECT a.appointment_id, a.userID, a.appointment_date, a.appointment_time, a.purpose, 
                            u.firstname, u.lastname, a.staff_comment, a.updated_at as declined_at
                     FROM appointments a 
                     JOIN user u ON a.userID = u.userID 
                     WHERE a.status = 'declined'
                     ORDER BY a.appointment_date, a.appointment_time";
            break;
    }

    $result = $conn->query($query);
    if (!$result) {
        error_log("SQL Error in getAppointments: " . $conn->error);
        return ['success' => false, 'error' => 'Database query failed'];
    }

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointment = [
            'appointmentID' => $row['appointment_id'],
            'userID' => $row['userID'],
            'userName' => $row['firstname'] . ' ' . $row['lastname'],
            'appointment_date' => $row['appointment_date'],
            'appointment_time' => $row['appointment_time'],
            'purpose' => $row['purpose']
        ];

        // Add optional fields if they exist
        if (isset($row['created_at'])) {
            $appointment['created_at'] = $row['created_at'];
        }
        if (isset($row['staff_comment'])) {
            $appointment['staff_comment'] = $row['staff_comment'];
        }
        if (isset($row['approved_at'])) {
            $appointment['approved_at'] = $row['approved_at'];
        }
        if (isset($row['declined_at'])) {
            $appointment['declined_at'] = $row['declined_at'];
        }

        $appointments[] = $appointment;
    }

    return ['success' => true, 'data' => $appointments];
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    try {
        // Clean any output that might have been generated
        ob_clean();
        header('Content-Type: application/json');
        $status = isset($_GET['status']) ? strtoupper($_GET['status']) : 'PENDING';
        $result = getAppointments($status);
        echo json_encode($result);
    } catch (Exception $e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - View Appointments</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; margin: 0; }
        .appt-header { background: #0033cc; color: #fff; padding: 24px 0 16px 0; border-radius: 0 0 32px 32px; font-size: 1.3rem; font-weight: bold; letter-spacing: 1px; }
        .appt-header .back { font-size: 1.5rem; margin-left: 24px; vertical-align: middle; cursor: pointer; }
        .appt-header span { margin-left: 12px; }
        .appt-board { display: flex; justify-content: space-between; margin: 32px auto 0 auto; max-width: 1200px; }
        .appt-col { flex: 1; margin: 0 16px; }
        .appt-col-header { text-align: center; font-size: 1.2rem; font-weight: bold; margin-bottom: 18px; }
        .pending { color: #e6b800; }
        .approved { color: #00b300; }
        .declined { color: #d00000; }
        .appt-card { 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 20px; 
            background: #fff; 
            border: 2px solid #0033cc; 
            color: #0033cc; 
            font-size: 1.08rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .appt-card.approved { 
            border-color: #00b300; 
            color: #333;
            background: #f0fff0;
        }
        .appt-card.declined { 
            border-color: #d00000; 
            color: #333;
            background: #fff0f0;
        }
        .appt-card.pending { 
            border-color: #0033cc; 
            color: #0033cc;
            background: #f0f4ff;
        }
        .appt-card label { 
            color: inherit; 
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
            margin-right: 8px;
        }
        .appt-card span { 
            color: inherit;
            font-style: italic;
            opacity: 0.9;
        }
        .appt-card.approved label { 
            color: #00b300; 
        }
        .appt-card.declined label { 
            color: #d00000; 
        }
        @media (max-width: 900px) {
            .appt-board { flex-direction: column; }
            .appt-col { margin: 0 0 32px 0; }
        }
    </style>
</head>
<body>
    <div class="appt-header">
        <span class="back" onclick="window.location.href='admin-appointments-dashboard.php'">&#8592;</span>
        <span>APPOINTMENTS</span>
    </div>
    <div class="appt-board">
        <div class="appt-col">
            <div class="appt-col-header pending">PENDING</div>
            <div id="pendingCol"></div>
        </div>
        <div class="appt-col">
            <div class="appt-col-header approved">APPROVED</div>
            <div id="approvedCol"></div>
        </div>
        <div class="appt-col">
            <div class="appt-col-header declined">DECLINED</div>
            <div id="declinedCol"></div>
        </div>
    </div>
    <script>
        function renderAdminAppointments() {
            // Fetch and display pending appointments
            fetch('admin-appointments.php?ajax=1&status=PENDING')
                .then(response => response.json())
                .then(result => {
                    const pendingCol = document.getElementById('pendingCol');
                    pendingCol.innerHTML = '';
                    if (!result.success) {
                        pendingCol.innerHTML = '<div style="color:red;">Failed to load appointments: ' + (result.error || 'Unknown error') + '</div>';
                        return;
                    }
                    
                    result.data.forEach(appt => {
                        let card = document.createElement('div');
                        card.className = 'appt-card pending';
                        card.innerHTML = `
                            <label>APPOINTMENT ID:</label> <span>${appt.appointmentID}</span><br>
                            <label>USER:</label> <span>${appt.userName}</span><br>
                            <label>DATE:</label> <span>${appt.appointment_date}</span><br>
                            <label>TIME:</label> <span>${appt.appointment_time}</span><br>
                            <label>PURPOSE:</label> <span>${appt.purpose}</span><br>
                            ${appt.created_at ? `<label>CREATED:</label> <span>${new Date(appt.created_at).toLocaleString()}</span><br>` : ''}
                        `;
                        pendingCol.appendChild(card);
                    });
                    if (result.data.length === 0) {
                        pendingCol.innerHTML = '<div style="text-align: center; color: #666; padding: 20px;">No pending appointments.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching pending appointments:', error);
                    document.getElementById('pendingCol').innerHTML = 
                        '<div style="color:red; text-align: center; padding: 20px;">Error loading appointments: ' + error.message + '</div>';
                });

            // Fetch and display approved appointments
            fetch('admin-appointments.php?ajax=1&status=APPROVED')
                .then(response => response.json())
                .then(result => {
                    const approvedCol = document.getElementById('approvedCol');
                    approvedCol.innerHTML = '';
                    if (!result.success) {
                        approvedCol.innerHTML = '<div style="color:red;">Failed to load appointments: ' + (result.error || 'Unknown error') + '</div>';
                        return;
                    }
                    result.data.forEach(appt => {
                        let card = document.createElement('div');
                        card.className = 'appt-card approved';
                        card.innerHTML = `
                            <label>APPOINTMENT ID:</label> <span>${appt.appointmentID}</span><br>
                            <label>USER:</label> <span>${appt.userName}</span><br>
                            <label>DATE:</label> <span>${appt.appointment_date}</span><br>
                            <label>TIME:</label> <span>${appt.appointment_time}</span><br>
                            <label>PURPOSE:</label> <span>${appt.purpose}</span><br>
                            ${appt.approved_at ? `<label>APPROVED:</label> <span>${new Date(appt.approved_at).toLocaleString()}</span><br>` : ''}
                            ${appt.staff_comment ? `<label>STAFF COMMENT:</label> <span>${appt.staff_comment}</span><br>` : ''}
                        `;
                        approvedCol.appendChild(card);
                    });
                    if (result.data.length === 0) {
                        approvedCol.innerHTML = '<div style="text-align: center; color: #666; padding: 20px;">No approved appointments.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching approved appointments:', error);
                    document.getElementById('approvedCol').innerHTML = 
                        '<div style="color:red; text-align: center; padding: 20px;">Error loading appointments: ' + error.message + '</div>';
                });

            // Fetch and display declined appointments
            fetch('admin-appointments.php?ajax=1&status=DECLINED')
                .then(response => response.json())
                .then(result => {
                    const declinedCol = document.getElementById('declinedCol');
                    declinedCol.innerHTML = '';
                    if (!result.success) {
                        declinedCol.innerHTML = '<div style="color:red;">Failed to load appointments: ' + (result.error || 'Unknown error') + '</div>';
                        return;
                    }
                    result.data.forEach(appt => {
                        let card = document.createElement('div');
                        card.className = 'appt-card declined';
                        card.innerHTML = `
                            <label>APPOINTMENT ID:</label> <span>${appt.appointmentID}</span><br>
                            <label>USER:</label> <span>${appt.userName}</span><br>
                            <label>DATE:</label> <span>${appt.appointment_date}</span><br>
                            <label>TIME:</label> <span>${appt.appointment_time}</span><br>
                            <label>PURPOSE:</label> <span>${appt.purpose}</span><br>
                            ${appt.declined_at ? `<label>DECLINED:</label> <span>${new Date(appt.declined_at).toLocaleString()}</span><br>` : ''}
                            ${appt.staff_comment ? `<label>STAFF COMMENT:</label> <span>${appt.staff_comment}</span><br>` : ''}
                        `;
                        declinedCol.appendChild(card);
                    });
                    if (result.data.length === 0) {
                        declinedCol.innerHTML = '<div style="text-align: center; color: #666; padding: 20px;">No declined appointments.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching declined appointments:', error);
                    document.getElementById('declinedCol').innerHTML = 
                        '<div style="color:red; text-align: center; padding: 20px;">Error loading appointments: ' + error.message + '</div>';
                });
        }

        // Refresh appointments every 30 seconds
        window.onload = function() {
            renderAdminAppointments();
            setInterval(renderAdminAppointments, 30000);
        };
    </script>
</body>
</html> 