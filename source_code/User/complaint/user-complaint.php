<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../../login.php");
    exit();
}

include "../../../database/database-connection.php";
include "../../../database/database-operations.php";

// Initialize DatabaseOperations
$complaint = new DatabaseOperations($conn);

// Get user's full name at the start
$userID = $_SESSION['userID'];
$user_sql = "SELECT CONCAT(firstname, ' ', lastname) as full_name FROM user WHERE userID = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $userID);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_full_name = $user_data['full_name'];

// Handle form submission via traditional POST for initial load message
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = isset($_POST['type']) ? $_POST['type'] : null;
    $message = isset($_POST['message']) ? $_POST['message'] : null;
    $complainedPerson = isset($_POST['complained_person']) ? $_POST['complained_person'] : null;

    if ($type && $message && $complainedPerson) {
        // Check if user is trying to complain about themselves
        if (strcasecmp($complainedPerson, $user_full_name) === 0) {
            $_SESSION['complaint_error'] = "Error: You cannot file a complaint against yourself.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $_SESSION['complaint_error'] = "Error: Please fill in all required fields.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Display messages if they exist (from traditional POST redirect)
$message = '';
if (isset($_SESSION['complaint_message'])) {
    $message = '<script>alert("'. $_SESSION['complaint_message'] .'");</script>';
    unset($_SESSION['complaint_message']);
} elseif (isset($_SESSION['complaint_error'])) {
    $message = '<script>alert("'. $_SESSION['complaint_error'] .'");</script>';
    unset($_SESSION['complaint_error']);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Complaints - Barangay Population Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            background: #fff;
        }

        header {
            background: #0033cc;
            color: #fff;
            padding: 30px 0 20px 0;
            font-size: 28px;
            font-weight: bold;
            display: flex;
            align-items: center;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .back-arrow {
            font-size: 32px;
            margin: 0 30px 0 30px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .back-arrow:hover {
            color: #0066ff;
        }

        .header-title {
            letter-spacing: 1px;
        }

        .main-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 40px auto 0 auto;
            max-width: 1400px;
            gap: 20px;
        }

        .form-section {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
            padding: 32px 28px;
            width: 300px;
            border: 2px solid #0033cc;
        }

        .form-section h2 {
            color: #0033cc;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }

        .form-section label {
            font-weight: bold;
            color: #0033cc;
            display: block;
            margin-bottom: 6px;
            margin-top: 18px;
            text-align: left;
            width: 100%;
        }

        .form-section input,
        .form-section select,
        .form-section textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #0033cc;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .form-section textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-section .send-btn {
            width: 120px;
            padding: 10px 0;
            font-size: 18px;
            background: #fff;
            color: #0033cc;
            border: 2px solid #0033cc;
            border-radius: 30px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            transition: background 0.2s, color 0.2s;
        }

        .form-section .send-btn:hover {
            background: #0033cc;
            color: #fff;
        }

        .form-section .auto-gen {
            font-style: italic;
            color: #888;
            font-weight: normal;
        }

        .status-section {
            flex: 1;
            display: flex;
            gap: 20px;
            margin-left: 20px;
        }

        .ticket-panel {
            flex: 1;
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 51, 204, 0.06);
            border: 2px solid #0033cc;
            max-height: 600px;
            overflow-y: auto;
        }

        .ticket-panel h4 {
            color: #0033cc;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0033cc;
            text-align: center;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
        }

        .ticket-card {
            border: 2px solid #0033cc;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
            text-align: left;
            background: #fff8f8;
            transition: box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0, 51, 204, 0.06);
        }

        .ticket-card .auto-gen {
            color: #888;
            font-style: italic;
        }

        .ticket-card .status {
            float: right;
            font-weight: bold;
            font-size: 15px;
        }

        .ticket-card .pending {
            color:rgb(255, 238, 0);
        }

        .ticket-card .resolved {
            color: #00b300;
        }

        .ticket-card .declined {
            color:rgb(255, 0, 0);
        }

        .ticket-card label {
            color: #0033cc;
            font-weight: bold;
        }

        .ticket-card .type {
            color: #0033cc;
            font-weight: bold;
        }

        .ticket-card .message {
            color: #0033cc;
            font-style: italic;
        }

        @media (max-width: 1200px) {
            .main-content {
                flex-direction: column;
                align-items: stretch;
            }

            .form-section {
                width: 100%;
                max-width: 100%;
            }

            .status-section {
                flex-direction: column;
                margin-left: 0;
            }

            .ticket-panel {
                max-height: none;
            }
        }
    </style>
</head>

<body>
    <?php echo $message; // Output any messages 
    ?>
    <header>
        <span class="back-arrow" onclick="window.location.href='../user-dashboard.php'">&#8592;</span>
        <span class="header-title">COMPLAINT</span>
    </header>
    <div class="main-content">
        <!-- Complaint Form -->
        <div class="form-section">
            <h2>FILE A COMPLAINT</h2>
            <div style="margin-bottom: 10px; width: 100%; text-align: left;">
                <label>COMPLAINT NUMBER: <span class="auto-gen">AUTO-GENERATED</span></label>
            </div>
            <form id="ticketForm">
                <label for="type">TYPE:</label>
                <select id="type" name="type" required>
                    <option value="Noise Complaint">Noise Complaint</option>
                    <option value="Property Damage">Property Damage</option>
                    <option value="Public Safety">Public Safety</option>
                    <option value="Sanitation">Sanitation</option>
                    <option value="Street Lighting">Street Lighting</option>
                    <option value="Traffic">Traffic</option>
                    <option value="Water Supply">Water Supply</option>
                    <option value="Waste Management">Waste Management</option>
                    <option value="Public Disturbance">Public Disturbance</option>
                    <option value="Other">Other</option>
                </select>

                <label for="complained_person">NAME OF PERSON COMPLAINED ABOUT:</label>
                <input type="text" id="complained_person" name="complained_person" required 
                    placeholder="Enter the full name (first name and last name)" 
                    pattern="[A-Za-z\s]{2,}\s[A-Za-z\s]{2,}" 
                    title="Please enter both first name and last name"
                    data-user-name="<?php echo htmlspecialchars($user_full_name); ?>"
                    oninput="validateFullName(this)">

                <label for="message">DETAILS:</label>
                <textarea id="message" name="message" required placeholder="Please provide detailed information about your complaint..."></textarea>

                <div style="text-align:center;">
                    <button class="send-btn" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>

        <!-- Complaint Status -->
        <div class="status-section">
            <div class="ticket-panel">
                <h4>PENDING COMPLAINTS</h4>
                <div id="pendingTickets"></div>
            </div>
            <div class="ticket-panel">
                <h4>RESOLVED COMPLAINTS</h4>
                <div id="finishedTickets"></div>
            </div>
            <div class="ticket-panel">
                <h4>DECLINED COMPLAINTS</h4>
                <div id="declinedTickets"></div>
            </div>
        </div>
    </div>

    <script>
        // Add this function at the start of your script section
        function validateFullName(input) {
            const name = input.value.trim();
            const nameParts = name.split(/\s+/);
            
            if (nameParts.length < 2) {
                input.setCustomValidity('Please enter both first name and last name');
            } else {
                input.setCustomValidity('');
            }
        }

        // Function to submit complaint using Ajax
        document.getElementById('ticketForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent normal form submission

            const form = event.target;
            const submitButton = form.querySelector('.send-btn');
            const complainedPerson = form.querySelector('#complained_person').value.trim();
            const userFullName = '<?php echo htmlspecialchars($user_full_name); ?>';

            // Remove all spaces from both names for comparison
            const complainedPersonNoSpaces = complainedPerson.replace(/\s+/g, '');
            const userFullNameNoSpaces = userFullName.replace(/\s+/g, '');

            console.log('Complained Person:', complainedPerson);
            console.log('User Full Name:', userFullName);
            console.log('Complained Person (no spaces):', complainedPersonNoSpaces);
            console.log('User Full Name (no spaces):', userFullNameNoSpaces);

            // Check if user is trying to complain about themselves
            if (complainedPersonNoSpaces.toLowerCase() === userFullNameNoSpaces.toLowerCase()) {
                console.log('Self-complaint detected');
                alert("Error: You cannot file a complaint against yourself.");
                return;
            }

            // Disable submit button while processing
            submitButton.disabled = true;
            submitButton.style.opacity = '0.7';
            submitButton.textContent = 'SENDING...';

            // Get form data
            const formData = new FormData(form);

            // Send Ajax request
            fetch('user-submit-complaint.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Submitted successfully.');
                        
                        // Clear the form
                        form.reset();
                        // Update complaint list
                        updateComplaints(); // Fetch and display latest complaints
                    } else {
                        // Show error message
                        alert(data.message || 'Error submitting complaint');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting complaint. Please try again.');
                })
                .finally(() => {
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.style.opacity = '1';
                    submitButton.textContent = 'SUBMIT';
                });
        });

        // Function to format date in a readable format
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }

        // Function to create a complaint card element
        function createComplaintCard(complaint) {
            return `
                <div class="ticket-card">
                    <span class="status ${complaint.status.toLowerCase()}">${complaint.status.toUpperCase()}</span>
                    <div>
                        <label>COMPLAINT #:</label>
                        <span class="auto-gen">${complaint.complaintID}</span>
                    </div>
                    <div>
                        <label>DATE:</label>
                        <span class="auto-gen">${formatDate(complaint.created_at)}</span>
                    </div>
                    <div>
                        <label>TYPE:</label>
                        <span class="type">${complaint.type}</span>
                    </div>
                    <div>
                        <label>COMPLAINED ABOUT:</label>
                        <span class="type">${complaint.complained_person}</span>
                    </div>
                    <div>
                        <label>DETAILS:</label>
                        <span>${complaint.message}</span>
                    </div>
                </div>
            `;
        }

        // Function to display complaints in the respective panels
        function displayComplaints(complaints) {
            const pendingDiv = document.getElementById('pendingTickets');
            const finishedDiv = document.getElementById('finishedTickets');
            const declinedDiv = document.getElementById('declinedTickets');

            if (pendingDiv) {
                pendingDiv.innerHTML = complaints.pending && complaints.pending.length > 0 ?
                    complaints.pending.map(complaint => createComplaintCard(complaint)).join('') :
                    '<p style="text-align: center; color: #666;">No pending complaints</p>';
            }

            if (finishedDiv) {
                finishedDiv.innerHTML = complaints.resolved && complaints.resolved.length > 0 ?
                    complaints.resolved.map(complaint => createComplaintCard(complaint)).join('') :
                    '<p style="text-align: center; color: #666;">No resolved complaints</p>';
            }

            if (declinedDiv) {
                declinedDiv.innerHTML = complaints.declined && complaints.declined.length > 0 ?
                    complaints.declined.map(complaint => createComplaintCard(complaint)).join('') :
                    '<p style="text-align: center; color: #666;">No declined complaints</p>';
            }
        }

        // Function to fetch and update complaints from the server
        function updateComplaints() {
            fetch('user-get-complaints.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        displayComplaints(result.complaints);
                    } else {
                        console.error('Error fetching complaints:', result.error);
                         const errorMessage = '<p style="text-align: center; color: red;">Failed to load complaints: ' + (result.error || 'Unknown error') + '</p>';
                         document.getElementById('pendingTickets').innerHTML = errorMessage;
                         document.getElementById('finishedTickets').innerHTML = errorMessage;
                         document.getElementById('declinedTickets').innerHTML = errorMessage;
                    }
                })
                .catch(error => {
                    console.error('Error fetching complaints:', error);
                    const errorMessage = '<p style="text-align: center; color: red;">Error loading complaints: ' + error.message + '</p>';
                    document.getElementById('pendingTickets').innerHTML = errorMessage;
                    document.getElementById('finishedTickets').innerHTML = errorMessage;
                    document.getElementById('declinedTickets').innerHTML = errorMessage;
                });
        }

        // Initial load of complaints using AJAX
        updateComplaints();

        // Update complaints every 10 seconds
        setInterval(updateComplaints, 10000);

    </script>
</body>

</html>