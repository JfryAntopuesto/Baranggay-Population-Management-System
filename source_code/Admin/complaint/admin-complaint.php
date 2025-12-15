<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
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
    <title>Barangay Population Management System - Staff Complaints</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e9f0ff 0%, #f5f7fa 100%);
            margin: 0;
            height: 100vh;
        }

        header {
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: white;
            text-align: center;
            padding: 25px;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            animation: headerDown 0.7s cubic-bezier(.77, 0, .18, 1);
            position: relative;
        }

        @keyframes headerDown {
            from {
                opacity: 0;
                transform: translateY(-40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-flex {
            display: flex;
            height: calc(100vh - 80px);
            min-height: 0;
        }

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
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
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

        .sidebar-btn.active,
        .sidebar-btn:focus {
            background: linear-gradient(90deg, #0033cc 80%, #e9f0ff 100%);
            color: #fff;
            border-left: 5px solid #0033cc;
        }

        .sidebar-btn:hover:not(.active) {
            background: #f0f4ff;
            color: #0033cc;
        }

        .sidebar-btn .badge {
            background: #d00000;
            color: #fff;
            border-radius: 50%;
            font-size: 0.95vw;
            padding: 2px 6px;
            position: absolute;
            right: 14px;
            top: 10px;
            font-weight: bold;
        }

        .middle-panel {
            width: 76%;
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 1px 6px rgba(0, 51, 204, 0.04);
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
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .pending-header {
            color: #e6b800;
        }

        .finished-header {
            color: #00b300;
        }

        .declined-header {
            color: #d00000;
        }

        #pendingComplaintsList,
        #finishedComplaintsList,
        #declinedComplaintsList {
            overflow-y: auto;
            flex: 1;
            padding: 10px 0;
        }

        .complaint-card {
            border: 1.5px solid #0033cc;
            border-radius: 7px;
            padding: 10px 8px 8px 8px;
            margin-bottom: 10px;
            background: #fff;
            color: #0033cc;
            font-size: 0.97rem;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 1px 4px rgba(0, 51, 204, 0.04);
        }

        .complaint-card:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 4px 12px rgba(0, 51, 204, 0.08);
        }

        .complaint-card label {
            color: #0033cc;
            font-weight: 600;
            display: inline-block;
            min-width: 120px;
            margin-bottom: 2px;
            font-size: 0.97rem;
        }

        .complaint-card .sender {
            color: #444;
            font-weight: 500;
            font-size: 0.85rem;
            margin-bottom: 2px;
            display: block;
            letter-spacing: 0.2px;
        }

        .complaint-card span {
            font-style: italic;
            color: #6c6cff;
            display: inline-block;
            word-wrap: break-word;
            font-size: 0.97rem;
        }

        /* Scrollbar styling */
        #pendingComplaintsList::-webkit-scrollbar,
        #finishedComplaintsList::-webkit-scrollbar,
        #declinedComplaintsList::-webkit-scrollbar {
            width: 8px;
        }

        #pendingComplaintsList::-webkit-scrollbar-track,
        #finishedComplaintsList::-webkit-scrollbar-track,
        #declinedComplaintsList::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #pendingComplaintsList::-webkit-scrollbar-thumb,
        #finishedComplaintsList::-webkit-scrollbar-thumb,
        #declinedComplaintsList::-webkit-scrollbar-thumb {
            background: #0033cc;
            border-radius: 4px;
        }

        /* Modal improvements */
        .modal-content-complaint {
            padding: 32px;
        }

        .modal-content-complaint label {
            color: #0033cc;
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
            margin-bottom: 12px;
        }

        .modal-content-complaint span {
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
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-complaint {
            background: #fff;
            border-radius: 10px;
            padding: 0;
            width: 500px;
            max-width: 90vw;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-complaint-header {
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

        .modal-complaint-header .close-btn {
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
            <a href="../admin-dashboard.php" style="color: white; text-decoration: none; font-size: 16px; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; transition: background 0.3s;">← Back to Dashboard</a>
            <span>BARANGAY DON MARTIN MARUNDAN POPULATION MANAGEMENT SYSTEM</span>
            <div style="width: 120px;"></div> <!-- Spacer for balance -->
        </div>
    </header>
    <div class="container">
        <div class="left-panel">
            <div class="sidebar-nav">
                <a href="../admin-request.php" class="sidebar-btn">
                    <span>REQUESTS</span>
                </a>
                <a href="admin-complaint.php" class="sidebar-btn active">
                    COMPLAINT
                </a>
            </div>
        </div>
        <div class="content-area">
            <div class="column">
                <div class="col-header pending-header">PENDING</div>
                <div id="pendingComplaintsList"></div>
            </div>
            <div class="column">
                <div class="col-header finished-header">FINISHED</div>
                <div id="finishedComplaintsList"></div>
            </div>
            <div class="column">
                <div class="col-header declined-header">DECLINED</div>
                <div id="declinedComplaintsList"></div>
            </div>
        </div>
    </div>
    <div class="modal-bg" id="modalBg">
        <div class="modal-complaint">
            <div class="modal-complaint-header">
                <span>Complaint Details</span>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>
    <script>
        let pendingComplaints = [];
        let approvedComplaints = [];
        let declinedComplaints = [];

        // Function to format date in a readable format
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }

        // Function to create a complaint card element
        function createComplaintCard(complaint, isPending = false) {
            const card = document.createElement('div');
            card.className = 'complaint-card';
            const senderName = [
                complaint.firstname || '',
                complaint.middlename || '',
                complaint.lastname || ''
            ].filter(Boolean).join(' ');

            card.innerHTML = `
                <span class="status ${complaint.status.toLowerCase()}">${complaint.status.toUpperCase()}</span>
                <span class="sender">${senderName}</span>
                <label>COMPLAINT #:</label> <span>${complaint.complaintID || 'N/A'}</span><br>
                <label>TYPE:</label> <span>${complaint.type ? complaint.type.toUpperCase() : 'N/A'}</span><br>
                <label>MESSAGE:</label> <span>${complaint.message || 'N/A'}</span><br>
                ${isPending ? `<label>DATE SUBMITTED:</label> <span>${formatDate(complaint.created_at || 'N/A')}</span>` : `<label>DATE ${complaint.status.toUpperCase()}:</label> <span>${formatDate(complaint.created_at || 'N/A')}</span>`}
            `;

            return card;
        }

        // Function to render complaints in the respective panels
        function renderComplaints() {
            const pendingDiv = document.getElementById('pendingComplaintsList');
            const finishedDiv = document.getElementById('finishedComplaintsList');
            const declinedDiv = document.getElementById('declinedComplaintsList');

            pendingDiv.innerHTML = '';
            finishedDiv.innerHTML = '';
            declinedDiv.innerHTML = '';

            if (pendingComplaints && pendingComplaints.length > 0) {
                pendingComplaints.forEach(complaint => {
                    pendingDiv.appendChild(createComplaintCard(complaint, true));
                });
            } else {
                pendingDiv.innerHTML = '<p style="text-align: center; color: #666;">No pending complaints</p>';
            }

            if (approvedComplaints && approvedComplaints.length > 0) {
                approvedComplaints.forEach(complaint => {
                    finishedDiv.appendChild(createComplaintCard(complaint));
                });
            } else {
                finishedDiv.innerHTML = '<p style="text-align: center; color: #666;">No resolved complaints</p>';
            }

            if (declinedComplaints && declinedComplaints.length > 0) {
                declinedComplaints.forEach(complaint => {
                    declinedDiv.appendChild(createComplaintCard(complaint));
                });
            } else {
                declinedDiv.innerHTML = '<p style="text-align: center; color: #666;">No declined complaints</p>';
            }
        }

        function openComplaintModal(complaint) {
            const modalBg = document.getElementById('modalBg');
            const modalContent = document.getElementById('modalContent');

            // Build sender name
            const senderName = [
                complaint.firstname || '',
                complaint.middlename || '',
                complaint.lastname || ''
            ].filter(Boolean).join(' ');

            modalContent.innerHTML = `
                <div class="modal-content">
                    <label style="color:#0033cc;">SENDER:</label> <span style="color:#0033cc; font-weight:600;">${senderName}</span><br>
                    <label>COMPLAINT NUMBER:</label> <span>${complaint.complaintID || 'N/A'}</span><br>
                    <label>TYPE:</label> <span>${complaint.type ? complaint.type.toUpperCase() : 'N/A'}</span><br>
                    <label>COMPLAINED PERSON:</label> <span>${complaint.complained_person || 'N/A'}</span><br>
                    <label>DATE SUBMITTED:</label> <span>${complaint.created_at || 'N/A'}</span><br>
                    <label>MESSAGE:</label> <span>${complaint.message || 'N/A'}</span><br>
                    <label>STAFF COMMENT:</label><br>
                    <textarea id="modalComment" placeholder="Type your comment here..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="approve-btn" onclick="updateComplaintStatus('${complaint.complaintID}', 'resolved')">RESOLVE</button>
                    <button type="button" class="decline-btn" onclick="updateComplaintStatus('${complaint.complaintID}', 'declined')">DECLINE</button>
                </div>
            `;

            modalBg.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('modalBg').style.display = 'none';
        }

        async function updateComplaintStatus(complaintId, status) {
            const staffComment = document.getElementById('modalComment').value;

            try {
                const response = await fetch('./update_complaint_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        complaintID: complaintId,
                        status: status,
                        staff_comment: staffComment
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    closeModal();
                    // Refresh all lists after status update
                    fetchPendingComplaints();
                    fetchApprovedComplaints();
                    fetchDeclinedComplaints();
                } else {
                    alert('Failed to update complaint status: ' + (data.message || response.statusText));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the complaint status.');
            }
        }

        // Fetch approved complaints from the server
        async function fetchApprovedComplaints() {
            try {
                const response = await fetch('fetch_approved_complaints.php');
                if (response.ok) {
                    const data = await response.json();
                    approvedComplaints = data;
                    renderComplaints();
                } else {
                    console.error('Error fetching approved complaints:', response.statusText);
                    approvedComplaints = [];
                    renderComplaints();
                }
            } catch (e) {
                console.error('Error fetching approved complaints:', e);
                approvedComplaints = [];
                renderComplaints();
            }
        }

        // Fetch declined complaints from the server
        async function fetchDeclinedComplaints() {
            try {
                const response = await fetch('fetch_declined_complaints.php');
                if (response.ok) {
                    const data = await response.json();
                    declinedComplaints = data;
                    renderComplaints();
                } else {
                    console.error('Error fetching declined complaints:', response.statusText);
                    declinedComplaints = [];
                    renderComplaints();
                }
            } catch (e) {
                console.error('Error fetching declined complaints:', e);
                declinedComplaints = [];
                renderComplaints();
            }
        }

        // Fetch pending complaints from the server
        async function fetchPendingComplaints() {
            try {
                const response = await fetch('fetch_pending_complaints.php');
                if (response.ok) {
                    const data = await response.json();
                    pendingComplaints = data;
                    renderComplaints();
                } else {
                    console.error('Error fetching pending complaints:', response.statusText);
                    pendingComplaints = [];
                    renderComplaints();
                }
            } catch (e) {
                console.error('Error fetching pending complaints:', e);
                pendingComplaints = [];
                renderComplaints();
            }
        }

        window.onload = function() {
            fetchPendingComplaints();
            fetchApprovedComplaints();
            fetchDeclinedComplaints();
        };

        // Poll for new complaints every 5 seconds
        setInterval(fetchPendingComplaints, 5000);
    </script>
</body>

</html>