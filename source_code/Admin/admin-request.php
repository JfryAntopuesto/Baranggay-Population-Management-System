<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

error_log("Accessing admin-request.php. Session user_type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Not set'));

if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Population Management System - Admin Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e9f0ff 0%, #f5f7fa 100%);
            margin: 0; height: 100vh;
        }
        header {
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: white;
            text-align: center;
            padding: 25px;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            animation: headerDown 0.7s cubic-bezier(.77,0,.18,1);
            position: relative;
        }
        @keyframes headerDown {
            from { opacity: 0; transform: translateY(-40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .main-flex { display: flex; height: calc(100vh - 80px); min-height: 0; }
        .container {
            display: flex;
            padding: 30px;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .left-panel {
            width: 22%;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            height: fit-content;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 180px;
        }
        .dashboard-header {
            margin-bottom: 20px;
        }
        .dashboard-header h4 {
            color: #0033cc;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 8px 0;
        }
        .dashboard-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
        .sidebar-nav {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: 10px;
        }
        .sidebar-btn {
            width: 100%;
            padding: 12px 0 12px 18px;
            color: #0033cc;
            font-weight: 600;
            font-size: 16px;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            border-left: 5px solid transparent;
            border-radius: 0 12px 12px 0;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, border-left 0.2s;
        }
        .sidebar-btn.active, .sidebar-btn:focus {
            background: linear-gradient(90deg, #0033cc 80%, #e9f0ff 100%);
            color: #fff;
            border-left: 5px solid #0033cc;
        }
        .sidebar-btn:hover:not(.active) {
            background: #f0f4ff;
            color: #0033cc;
        }
        .sidebar-btn .badge { background: #d00000; color: #fff; border-radius: 50%; font-size: 0.95vw; padding: 2px 6px; position: absolute; right: 14px; top: 10px; font-weight: bold; }
        .middle-panel {
            width: 76%;
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            min-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .content-area {
            display: flex;
            flex-direction: row;
            gap: 24px;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .column {
            flex: 1 1 0;
            min-width: 260px;
            max-width: 340px;
            margin: 0 6px;
            background: #f7faff;
            box-shadow: 0 1px 6px rgba(0,51,204,0.04);
            padding: 14px 8px 18px 8px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            height: 520px;
        }
        .col-header {
            font-size: 1.15rem;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-align: left;
            padding: 0 8px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .pending-header { color: #e6b800; }
        .finished-header { color: #00b300; }
        .declined-header { color: #d00000; }
        #pendingRequests, #finishedRequests, #declinedRequests {
            overflow-y: auto;
            flex: 1;
            padding: 10px 0;
        }
        .request-card {
            border: 1.5px solid #0033cc;
            border-radius: 7px;
            padding: 10px 8px 8px 8px;
            margin-bottom: 10px;
            background: #fff;
            color: #0033cc;
            font-size: 0.97rem;
            cursor: default;
            box-shadow: 0 1px 4px rgba(0, 51, 204, 0.04);
        }
        .request-card label {
            color: #0033cc;
            font-weight: 600;
            display: inline-block;
            min-width: 90px;
            margin-bottom: 2px;
            font-size: 0.97rem;
        }
        .request-card .sender {
            color: #444;
            font-weight: 500;
            font-size: 0.85rem;
            margin-bottom: 2px;
            display: block;
            letter-spacing: 0.2px;
        }
        .request-card span {
            font-style: italic;
            color: #6c6cff;
            display: inline-block;
            word-wrap: break-word;
            font-size: 0.97rem;
        }
        /* Scrollbar styling */
        #pendingRequests::-webkit-scrollbar,
        #finishedRequests::-webkit-scrollbar,
        #declinedRequests::-webkit-scrollbar {
            width: 8px;
        }
        #pendingRequests::-webkit-scrollbar-track,
        #finishedRequests::-webkit-scrollbar-track,
        #declinedRequests::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        #pendingRequests::-webkit-scrollbar-thumb,
        #finishedRequests::-webkit-scrollbar-thumb,
        #declinedRequests::-webkit-scrollbar-thumb {
            background: #0033cc;
            border-radius: 4px;
        }
        /* Modal improvements */
        .modal-content-request {
            padding: 32px;
        }
        .modal-content-request label {
            color: #0033cc;
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
            margin-bottom: 12px;
        }
        .modal-content-request span {
            font-style: italic;
            color: #6c6cff;
            display: inline-block;
            word-wrap: break-word;
        }
        @media (max-width: 1000px) {
            .content-area {
                flex-direction: column;
                height: auto;
                margin-left: 0;
                padding: 10px;
                max-width: 100%;
            }
            .column {
                max-width: 100%;
                min-width: 0;
                margin-bottom: 12px;
                height: auto;
            }
        }
        /* Modal Styles */
        .modal-bg { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100vw; 
            height: 100vh; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            justify-content: center; 
            align-items: center;
        }
        .modal-request {
            background: #fff;
            border-radius: 10px;
            padding: 0;
            width: 500px;
            max-width: 90vw;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .modal-request-header {
            background: #0033cc;
            color: #fff;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-request-header .close-btn {
            cursor: pointer;
            font-size: 1.5rem;
            color: white;
            background: none;
            border: none;
            padding: 0 10px;
        }
        .modal-content {
            padding: 20px;
        }
        .modal-content label {
            color: #0033cc;
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
            margin-bottom: 10px;
        }
        .modal-content span {
            color: #666;
        }
        .modal-content textarea {
            width: 95%;
            min-height: 100px;
            border: 2px solid #0033cc;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0 20px 0;
            font-size: 1rem;
        }
        .modal-footer {
            padding: 0 20px 20px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .modal-footer button {
            padding: 8px 20px;
            border-radius: 5px;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        .approve-btn {
            background: #00b300;
            color: white;
        }
        .decline-btn {
            background: #d00000;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
            <a href="admin-dashboard.php" style="color: white; text-decoration: none; font-size: 16px; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; transition: background 0.3s;">← Back to Dashboard</a>
            <span>BARANGAY POPULATION MANAGEMENT SYSTEM</span>
            <div style="width: 120px;"></div> <!-- Spacer for balance -->
        </div>
    </header>
    <div class="container">
        <div class="left-panel">
            <div class="sidebar-nav">
                <a href="admin-request.php" class="sidebar-btn active">
                    <span>REQUESTS</span>
                </a>
                <a href="complaint/admin-complaint.php" class="sidebar-btn">
                    COMPLAINT
                </a>
            </div>
        </div>
        <div class="content-area">
            <div class="column">
                <div class="col-header pending-header">PENDING</div>
                <div id="pendingRequests"></div>
            </div>
            <div class="column">
                <div class="col-header finished-header">FINISHED</div>
                <div id="finishedRequests"></div>
            </div>
            <div class="column">
                <div class="col-header declined-header">DECLINED</div>
                <div id="declinedRequests"></div>
            </div>
        </div>
    </div>
    <div class="modal-bg" id="modalBg">
        <div class="modal-request">
            <div class="modal-request-header">
                <span>Request Details</span>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>
    <script>
        let currentModalIdx = null;
        let requests = [];
        let approvedRequests = [];
        let declinedRequests = [];

        function renderRequests() {
            const pendingDiv = document.getElementById('pendingRequests');
            const finishedDiv = document.getElementById('finishedRequests');
            const declinedDiv = document.getElementById('declinedRequests');
            
            pendingDiv.innerHTML = '';
            finishedDiv.innerHTML = '';
            declinedDiv.innerHTML = '';

            // Display pending requests
            requests.forEach((request, idx) => {
                const card = document.createElement('div');
                card.className = 'request-card';
                const senderName = [
                    request.firstname || '',
                    request.middlename || '',
                    request.lastname || ''
                ].filter(Boolean).join(' ');
                card.innerHTML = `
                    <span class="sender">${senderName}</span>
                    <label>REQUEST #:</label> <span>${request.requestID || 'N/A'}</span><br>
                    <label>TYPE:</label> <span>${request.type ? request.type.toUpperCase() : 'N/A'}</span><br>
                    <label>MESSAGE:</label> <span>${request.message || 'N/A'}</span><br>
                    <label>DATE:</label> <span>${request.created_at || 'N/A'}</span>
                `;
                
                pendingDiv.appendChild(card);
            });
            
            // Display approved requests in the FINISHED column
            if (approvedRequests && approvedRequests.length > 0) {
                approvedRequests.forEach((request) => {
                    const card = document.createElement('div');
                    card.className = 'request-card';
                    card.innerHTML = `
                        <span class="sender">${(request.firstname || '') + ' ' + (request.middlename || '') + ' ' + (request.lastname || '')}</span>
                        <label>REQUEST #:</label> <span>${request.requestID}</span><br>
                        <label>TYPE:</label> <span>${request.type ? request.type.toUpperCase() : 'N/A'}</span><br>
                        <label>MESSAGE:</label> <span>${request.message || 'N/A'}</span><br>
                        <label>DATE APPROVED:</label> <span>${request.created_at || 'N/A'}</span>
                    `;
                    finishedDiv.appendChild(card);
                });
            }
            
            // Display declined requests in the DECLINED column
            if (declinedRequests && declinedRequests.length > 0) {
                declinedRequests.forEach((request) => {
                    const card = document.createElement('div');
                    card.className = 'request-card';
                    card.innerHTML = `
                        <span class="sender">${(request.firstname || '') + ' ' + (request.middlename || '') + ' ' + (request.lastname || '')}</span>
                        <label>REQUEST #:</label> <span>${request.requestID}</span><br>
                        <label>TYPE:</label> <span>${request.type ? request.type.toUpperCase() : 'N/A'}</span><br>
                        <label>MESSAGE:</label> <span>${request.message || 'N/A'}</span><br>
                        <label>DATE DECLINED:</label> <span>${request.updated_at || request.created_at || 'N/A'}</span>
                    `;
                    declinedDiv.appendChild(card);
                });
            }
        }

        function openModal(idx) {
            const request = requests[idx];
            const modalBg = document.getElementById('modalBg');
            const modalContent = document.getElementById('modalContent');
            
            // Use the correct request ID field from the request object
            const requestId = request.requestID;
            
            // Build sender name
            const senderName = [
                request.firstname || '',
                request.middlename || '',
                request.lastname || ''
            ].filter(Boolean).join(' ');
            
            // Update modal content
            document.querySelector('.modal-request-header span').textContent = `Request #${requestId}`;
            
            modalContent.innerHTML = `
                <div class="modal-content">
                    <label style="color:#0033cc;">SENDER:</label> <span style="color:#0033cc; font-weight:600;">${senderName}</span><br>
                    <label>REQUEST NUMBER:</label> <span>${requestId}</span><br>
                    <label>TYPE:</label> <span>${request.type ? request.type.toUpperCase() : 'N/A'}</span><br>
                    <label>DATE SUBMITTED:</label> <span>${request.created_at || 'N/A'}</span><br>
                    <label>MESSAGE:</label> <span>${request.message || 'N/A'}</span><br>
                    <label>STAFF COMMENT:</label><br>
                    <textarea id="modalReply" placeholder="Type your comment here..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="approve-btn" onclick="updateRequestStatus('${requestId}', 'FINISHED')">FINISH</button>
                    <button type="button" class="decline-btn" onclick="updateRequestStatus('${requestId}', 'DECLINED')">DECLINE</button>
                </div>
            `;
            
            modalBg.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('modalBg').style.display = 'none';
        }

        async function updateRequestStatus(requestId, status) {
            const staffComment = document.getElementById('modalReply').value;
            
            // DEBUG: Log what is being sent
            console.log('Sending:', {
                requestID: requestId,
                status: status,
                staff_comment: staffComment
            });

            try {
                const response = await fetch('admin-requests/update_request_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        requestID: requestId,
                        status: status,
                        staff_comment: staffComment
                    })
                });

                const responseText = await response.text();
                console.log('Response:', responseText);

                if (response.ok) {
                    closeModal();
                    // Remove the request from the requests array and re-render
                    requests = requests.filter(r => {
                        // Use the correct request ID field for comparison
                        return r.requestID != requestId;
                    });
                    renderRequests();
                } else {
                    alert('Failed to update request status. Please try again.\n' + responseText);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the request.');
            }
        }

        // Fetch approved requests from the server
        async function fetchApprovedRequests() {
            try {
                const response = await fetch('admin-requests/fetch_approved_requests.php');
                if (response.ok) {
                    const data = await response.json();
                    approvedRequests = data;
                    renderRequests();
                }
            } catch (e) {
                console.error('Error fetching approved requests:', e);
            }
        }

        // Fetch declined requests from the server
        async function fetchDeclinedRequests() {
            try {
                const response = await fetch('admin-requests/fetch_declined_requests.php');
                if (response.ok) {
                    const data = await response.json();
                    declinedRequests = data;
                    renderRequests();
                }
            } catch (e) {
                console.error('Error fetching declined requests:', e);
            }
        }

        // Fetch pending requests from the server
        async function fetchPendingRequests() {
            try {
                const response = await fetch('admin-requests/fetch_pending_requests.php');
                if (response.ok) {
                    const data = await response.json();
                    requests = data;
                    renderRequests();
                }
            } catch (e) {
                console.error('Error fetching pending requests:', e);
            }
        }

        window.onload = function() {
            fetchPendingRequests();
            fetchApprovedRequests();
            fetchDeclinedRequests();
        };

        // Poll for new approved requests every 5 seconds
        setInterval(fetchApprovedRequests, 5000);

        // Poll for new declined requests every 5 seconds
        setInterval(fetchDeclinedRequests, 5000);

        // Poll for new requests every 5 seconds
        setInterval(fetchPendingRequests, 5000);
    </script>
</body>
</html> 