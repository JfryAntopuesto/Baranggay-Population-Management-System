<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../../login.php");
    exit();
}

include "../../../database/database-connection.php";
include "../../../database/database-operations.php";

// Initialize DatabaseOperations
$request = new DatabaseOperations($conn);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = isset($_POST['type']) ? $_POST['type'] : null;
    $message = isset($_POST['message']) ? $_POST['message'] : null;
    $userID = $_SESSION['userID'];

    if ($type && $message) {
        $request->createRequest($type, $message, $userID);
        $_SESSION['request_message'] = "Submitted successfully.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['request_error'] = "Error: Please fill in all required fields.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Display messages if they exist
$message = '';
if (isset($_SESSION['request_message'])) {
    $message = '<script>alert("' . $_SESSION['request_message'] . '");</script>';
    unset($_SESSION['request_message']);
} elseif (isset($_SESSION['request_error'])) {
    $message = '<script>alert("' . $_SESSION['request_error'] . '");</script>';
    unset($_SESSION['request_error']);
}

// Fetch user's requests
$userID = $_SESSION['userID'];
$requests = $request->getRequestsByUserID($userID);

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Requests and Complaints - Barangay Population Management System</title>
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
            color: #ffd700;
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

        .request-panel {
            flex: 1;
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 51, 204, 0.04);
            border: 2px solid #0033cc;
            max-height: 600px;
            overflow-y: auto;
        }

        .request-panel h4 {
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

        .request-card {
            border: 2px solid #0033cc;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
            text-align: left;
            background: #f8faff;
            transition: box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0, 51, 204, 0.04);
        }

        .request-card .auto-gen {
            color: #888;
            font-style: italic;
        }

        .request-card .status {
            float: right;
            font-weight: bold;
            font-size: 15px;
        }

        .request-card .pending {
            color: #ffd700;
        }

        .request-card .finished {
            color: #00b300;
        }
        .request-card .declined {
            color:rgb(255, 0, 0);
        }

        .request-card label {
            color: #0033cc;
            font-weight: bold;
        }

        .request-card .type {
            color: #0033cc;
            font-weight: bold;
        }

        .request-card .message {
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

            .request-panel {
                max-height: none;
            }
        }
    </style>
</head>

<body>
    <?php echo $message; // Output any messages ?>
    <header>
        <span class="back-arrow" onclick="window.history.back()">&#8592;</span>
        <span class="header-title">REQUESTS</span>
    </header>
    <div class="main-content">
        <!-- Request Form -->
        <div class="form-section">
            <h2>SEND A REQUEST</h2>
            <div style="margin-bottom: 10px; width: 100%; text-align: left;">
                <label>REQUEST NUMBER: <span class="auto-gen">AUTO-GENERATED</span></label>
            </div>
            <form id="requestForm" onsubmit="submitRequest(event)">
                <label for="type">TYPE:</label>
                <select id="type" name="type" required>
                <option value="Barangay Clearance">Barangay Clearance</option>
                    <option value="Barangay Certificate of Residency">Barangay Certificate of Residency</option>
                    <option value="Barangay Certificate of Indigency">Barangay Certificate of Indigency</option>
                    <option value="Barangay Certificate of Good Moral Character">Barangay Certificate of Good Moral Character</option>
                    <option value="Barangay Business Clearance">Barangay Business Clearance</option>
                    <option value="Certificate of Solo Parent">Certificate of Solo Parent</option>
                    <option value="Certificate of Non-Tenancy or Non-Ownership">Certificate of Non-Tenancy or Non-Ownership</option>
                    <option value="Certificate of Live-in Partnership or Cohabitation">Certificate of Live-in Partnership or Cohabitation</option>
                    <option value="Certificate of Calamity Victim">Certificate of Calamity Victim</option>
                    <option value="Financial Assistance">Financial Assistance</option>
                </select>
                
                <label for="message">MESSAGE:</label>
                <textarea id="message" name="message" required></textarea>

                <div style="text-align:center;">
                    <button class="send-btn" type="submit">SEND</button>
                </div>
            </form>
        </div>

        <!-- Request Status -->
        <div class="status-section">
            <div class="request-panel">
                <h4>PENDING REQUEST</h4>
                <div id="pendingRequests"></div>
            </div>
            <div class="request-panel">
                <h4>FINISHED REQUEST</h4>
                <div id="finishedRequests"></div>
            </div>
            <div class="request-panel">
                <h4>DECLINED REQUEST</h4>
                <div id="declinedRequests"></div>
            </div>
        </div>
    </div>

    <script>
        // Function to submit request using Ajax
        function submitRequest(event) {
            event.preventDefault(); // Prevent normal form submission
            
            const form = document.getElementById('requestForm');
            const submitButton = form.querySelector('.send-btn');
            
            // Disable submit button while processing
            submitButton.disabled = true;
            submitButton.style.opacity = '0.7';
            submitButton.textContent = 'SENDING...';

            // Get form data
            const formData = new FormData(form);

            // Send Ajax request
            fetch('user-submit-request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Submitted successfully.');
                    
                    // Clear the form
                    form.reset();
                    // Update request list
                    updateRequests();
                } else {
                    // Show error message
                    alert(data.message || 'Error submitting request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting request. Please try again.');
            })
            .finally(() => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.style.opacity = '1';
                submitButton.textContent = 'SEND';
            });
        }

        // Function to format date in a readable format
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }

        // Function to create a request card element
        function createRequestCard(request) {
            let staffCommentHtml = '';
            if (request.staff_comment) {
                staffCommentHtml = `
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #0033cc;">
                        <label>STAFF COMMENT:</label>
                        <span style="color: #0033cc;">${request.staff_comment}</span>
                    </div>
                `;
            }

            return `
                <div class="request-card">
                    <span class="status ${request.status.toLowerCase()}">${request.status.toUpperCase()}</span>
                    <div>
                        <label>REQUEST #:</label> 
                        <span class="auto-gen">${request.requestID}</span>
                    </div>
                    <div>
                        <label>DATE:</label> 
                        <span class="auto-gen">${formatDate(request.created_at)}</span>
                    </div>
                    <div>
                        <label>TYPE:</label> 
                        <span class="type">${request.type}</span>
                    </div>
                    <div>
                        <label>MESSAGE:</label>
                        <span>${request.message}</span>
                    </div>
                    ${staffCommentHtml}
                </div>
            `;
        }

        // Function to fetch and update requests
        function updateRequests() {
            fetch('user-get-requests.php', {
                method: 'GET',
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(result => {
                    if (!result.success) {
                        document.getElementById('pendingRequests').innerHTML = 
                            '<p style="text-align: center; color: red;">' + (result.error || 'Failed to load requests') + '</p>';
                        document.getElementById('finishedRequests').innerHTML = 
                            '<p style="text-align: center; color: red;">' + (result.error || 'Failed to load requests') + '</p>';
                        document.getElementById('declinedRequests').innerHTML = 
                            '<p style="text-align: center; color: red;">' + (result.error || 'Failed to load requests') + '</p>';
                        return;
                    }

                    const requests = result.requests || {
                        pending: [],
                        finished: [],
                        declined: []
                    };

                    // Update each panel
                    const pendingDiv = document.getElementById('pendingRequests');
                    const finishedDiv = document.getElementById('finishedRequests');
                    const declinedDiv = document.getElementById('declinedRequests');

                    if (pendingDiv) {
                        pendingDiv.innerHTML = requests.pending.length === 0 
                            ? '<p style="text-align: center; color: #666;">No pending requests</p>'
                            : requests.pending.map(request => createRequestCard(request)).join('');
                    }

                    if (finishedDiv) {
                        finishedDiv.innerHTML = requests.finished.length === 0 
                            ? '<p style="text-align: center; color: #666;">No finished requests</p>'
                            : requests.finished.map(request => createRequestCard(request)).join('');
                    }

                    if (declinedDiv) {
                        declinedDiv.innerHTML = requests.declined.length === 0 
                            ? '<p style="text-align: center; color: #666;">No declined requests</p>'
                            : requests.declined.map(request => createRequestCard(request)).join('');
                    }
                })
                .catch(error => {
                    document.getElementById('pendingRequests').innerHTML = 
                        '<p style="text-align: center; color: red;">Error loading requests. Please try again later.</p>';
                    document.getElementById('finishedRequests').innerHTML = 
                        '<p style="text-align: center; color: red;">Error loading requests. Please try again later.</p>';
                    document.getElementById('declinedRequests').innerHTML = 
                        '<p style="text-align: center; color: red;">Error loading requests. Please try again later.</p>';
                });
        }

        // Initial load of requests
        updateRequests();

        // Update requests every 10 seconds
        setInterval(updateRequests, 10000);
    </script>
</body>

</html>