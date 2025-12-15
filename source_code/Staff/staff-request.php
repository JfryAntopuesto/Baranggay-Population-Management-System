<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
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
    <title>Barangay Population Management System - Staff Requests</title>
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
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 1px 4px rgba(0,51,204,0.04);
        }
        .request-card:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 4px 12px rgba(0, 51, 204, 0.08);
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
        /* Badge styling */
        .badge {
            background-color: #ff0000; /* Red background */
            color: white;
            border-radius: 50%; /* Circular shape */
            padding: 2px 6px; /* Adjust padding for size */
            font-size: 0.75em; /* Smaller font size relative to parent */
            font-weight: bold;
            position: relative;
            top: -2px; /* Adjust vertical alignment */
            margin-left: 5px; /* Space between text and badge */
            min-width: 16px; /* Ensure circular shape for single digits */
            text-align: center;
            display: inline-block;
            line-height: 1.2; /* Adjust line height */
        }

        .sidebar-btn .badge { /* Specific styling for badges within sidebar buttons */
            position: static; /* Remove absolute positioning */
            top: auto;
            right: auto;
            margin-left: 8px; /* Adjust margin */
        }
    </style>
</head>
<body>
    <header>
        BARANGAY DON MARTIN MARUNDAN POPULATION MANAGEMENT SYSTEM
    </header>
    <div class="container">
        <div class="left-panel">
            <div class="sidebar-nav">
                <a href="staff-dashboard.php" class="sidebar-btn">DASHBOARD</a>
                <a href="staff-announcement.php" class="sidebar-btn">ANNOUNCEMENT</a>
                <a href="staff-request.php" class="sidebar-btn active">REQUESTS <span class="badge" id="pendingRequestsBadge">0</span></a>
                <a href="complaint/staff-complaint.php" class="sidebar-btn">COMPLAINT <span class="badge" id="pendingComplaintsBadge">0</span></a>
                <a href="staff-appointments-dashboard.php" class="sidebar-btn">APPOINTMENTS <span class="badge" id="pendingAppointmentsBadge">0</span></a>
            </div>
        </div>
        <div class="content-area">
            <div class="column">
                <div class="col-header pending-header">PENDING</div>
                <div id="pendingRequests"></div>
            </div>
            <div class="column">
                <div class="col-header finished-header">RESOLVED</div>
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
                card.onclick = () => openModal(idx);
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
            currentModalIdx = idx;
        }

        function closeModal() {
            document.getElementById('modalBg').style.display = 'none';
            currentModalIdx = null;
            // Reload requests after closing modal to reflect any status changes
            fetchRequests();
        }

        function fetchRequests() {
            fetch('staff-request-handler.php?action=get_all_requests')
                .then(response => {
                    // Check if response is actually JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Non-JSON response received:', text.substring(0, 200));
                            throw new Error('Server returned non-JSON response. Check console for details.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Error fetching requests:', data.error);
                        alert('Error fetching requests: ' + data.error);
                        return;
                    }
                    // Assuming data contains pending, finished, and declined requests
                    requests = data.pending || [];
                    approvedRequests = data.finished || []; // Match to finished
                    declinedRequests = data.declined || [];
                    renderRequests();
                })
                .catch(error => {
                    console.error('Error fetching requests:', error);
                    alert('Error fetching requests: ' + error.message);
                });
        }

        function updateRequestStatus(requestID, status) {
            const comment = document.getElementById('modalReply').value;
            
            // Basic validation for comment if declining
            if (status === 'DECLINED' && !comment.trim()) {
                alert('Please provide a staff comment when declining a request.');
                return;
            }

            fetch('staff-request-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    requestID: requestID,
                    status: status,
                    staff_comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Request status updated successfully!', data);
                    closeModal();
                    // Refresh badge counts after updating status
                    fetchPendingCounts();
                } else {
                    console.error('Error updating request status:', data.error);
                    alert('Failed to update request status: ' + data.error);
                    // Still try to fetch requests to update the list
                    fetchRequests();
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An error occurred while updating request status.');
                // Still try to fetch requests to update the list
                fetchRequests();
            });
        }

        // Function to fetch and update pending counts for badges
        function fetchPendingCounts() {
            fetch('staff-dashboard-handler.php') // Use the dashboard handler to get all counts
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading pending counts:', data.error);
                        return;
                    }
                    // Update badge text content
                    document.getElementById('pendingRequestsBadge').textContent = data.pendingRequests || '0';
                    document.getElementById('pendingComplaintsBadge').textContent = data.pendingComplaints || '0';
                    document.getElementById('pendingAppointmentsBadge').textContent = data.pendingAppointments || '0';
                })
                .catch(error => {
                    console.error('Error fetching pending counts:', error);
                });
        }

        // Initial fetch on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetchRequests();
            fetchPendingCounts(); // Fetch and display pending counts on load
        });

    </script>
</body>
</html> 