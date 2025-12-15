<?php
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
    <title>Barangay Population Management System - Staff Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="js/auto-refresh.js"></script>
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
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            height: fit-content;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 180px;
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
            transition: background 0.2s, color 0.2s, border-left 0.2s;
            text-decoration: none;
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
        .middle-panel {
            width: 56%;
            background: white;
            padding: 25px 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            min-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .middle-panel h3 {
            color: #0033cc;
            margin-bottom: 18px;
            font-size: 22px;
            border-bottom: 2px solid #0033cc;
            padding-bottom: 10px;
        }
        .dashboard-cards {
            display: flex;
            gap: 32px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .dashboard-card {
            background: #f8f9fa;
            box-shadow: 0 2px 10px rgba(0,51,204,0.06);
            border-radius: 12px;
            padding: 28px 38px;
            min-width: 180px;
            color: #0033cc;
            font-size: 1.13rem;
            font-weight: 600;
            border: 2px solid #0033cc;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .dashboard-card .count {
            font-size: 2.0rem;
            font-weight: bold;
            color: #0033cc;
            margin-bottom: 8px;
        }
        .recent-announcements {
            background: #f8f9fa;
            box-shadow: 0 2px 10px rgba(0,51,204,0.06);
            border-radius: 12px;
            padding: 22px 28px;
            color: #0033cc;
            font-size: 1.08rem;
            max-width: 700px;
        }
        .recent-announcements h3 {
            margin-top: 0;
            color: #0033cc;
            font-size: 1.2rem;
            font-weight: 600;
            border-bottom: 2px solid #0033cc;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .announcement-item {
            border-bottom: 1px solid #e0e0e0;
            padding: 12px 0;
        }
        .announcement-item:last-child { border-bottom: none; }
        .announcement-title { font-weight: 600; }
        .announcement-date { color: #888; font-size: 0.98rem; }
        .right-panel {
            width: 22%;
            background: white;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            min-width: 180px;
            min-height: 200px;
            /* Placeholder for future content */
        }
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
                gap: 20px;
            }
            .left-panel, .middle-panel, .right-panel {
                width: 100%;
                min-width: 0;
            }
            .dashboard-cards {
                flex-direction: column;
                gap: 18px;
            }
        }
        .logout-btn {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: 2px solid white;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
    <script>
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</head>
<body>
    <header>
        BARANGAY DON MARTIN MARUNDAN POPULATION MANAGEMENT SYSTEM
        <button class="logout-btn" onclick="location.href='../logout.php'">LOGOUT</button>
    </header>
    <div class="container">
        <div class="left-panel">
            <nav class="sidebar-nav">
                <a href="staff-dashboard.php" class="sidebar-btn active">DASHBOARD</a>
                <a href="staff-announcement.php" class="sidebar-btn">ANNOUNCEMENT</a>
                <a href="staff-request.php" class="sidebar-btn">REQUESTS <span class="badge" id="pendingRequestsBadge">0</span></a>
                <a href="complaint/staff-complaint.php" class="sidebar-btn">COMPLAINT <span class="badge" id="pendingComplaintsBadge">0</span></a>
                <a href="staff-appointments-dashboard.php" class="sidebar-btn">APPOINTMENTS <span class="badge" id="pendingAppointmentsBadge">0</span></a>
            </nav>
        </div>
        <div class="middle-panel">
            <h3>STAFF DASHBOARD</h3>
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="count" id="pendingCount">0</div>
                    Pending Requests
                </div>
                <div class="dashboard-card">
                    <div class="count" id="pendingAppointmentsCount">0</div>
                    Pending Appointments
                </div>
                <div class="dashboard-card">
                    <div class="count" id="pendingComplaintsCount">0</div>
                    Pending Complaints
                </div>
            </div>
            <div class="recent-announcements">
                <h3>Recent Announcements</h3>
                <div id="announcementList"></div>
            </div>
        </div>
        <div class="right-panel">
            <!-- Placeholder for future right-side content -->
        </div>
    </div>
    <script>
        function renderAnnouncements(announcements) {
            const list = document.getElementById('announcementList');
            if (!announcements || announcements.length === 0) {
                list.innerHTML = '<div style="color:#888;">No recent announcements.</div>';
                return;
            }
            list.innerHTML = announcements.map(a => `
                <div class="announcement-item">
                    <div class="announcement-title">${a.title}</div>
                    <div class="announcement-date">${a.datetime}</div>
                    <div>${a.content}</div>
                </div>
            `).join('');
        }

        function loadDashboardData() {
            fetch('staff-dashboard-handler.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received dashboard data:', data); // Debug log
                    
                    if (data.error) {
                        console.error('Error loading dashboard data:', data.error);
                        return;
                    }
                    
                    // Update counters with null check
                    document.getElementById('pendingCount').textContent = data.pendingRequests || '0';
                    document.getElementById('pendingAppointmentsCount').textContent = data.pendingAppointments || '0';
                    document.getElementById('pendingComplaintsCount').textContent = data.pendingComplaints || '0';
                    
                    // Render announcements if they exist
                    if (data.recentAnnouncements) {
                        renderAnnouncements(data.recentAnnouncements);
                    }

                    // Update badge counts
                    document.getElementById('pendingRequestsBadge').textContent = data.pendingRequests || '0';
                    document.getElementById('pendingComplaintsBadge').textContent = data.pendingComplaints || '0';
                    document.getElementById('pendingAppointmentsBadge').textContent = data.pendingAppointments || '0';

                })
                .catch(error => {
                    console.error('Error fetching dashboard data:', error);
                    // Set default values on error
                    document.getElementById('pendingCount').textContent = '0';
                    document.getElementById('pendingAppointmentsCount').textContent = '0';
                    document.getElementById('pendingComplaintsCount').textContent = '0';
                });
        }

        // Initial data load on page load
        window.onload = function() {
            console.log('Initializing staff dashboard...'); // Debug log
            loadDashboardData();
            
            // Initialize auto-refresh functionality
            const refreshFunctions = [
                loadDashboardData
            ];
            
            // Create auto-refresh instance with 30-second interval
            const autoRefresh = initializeAutoRefresh(refreshFunctions, 30000);
            
            // Log auto-refresh status
            console.log('Auto-refresh initialized for staff dashboard');
        };

    </script>
</body>
</html> 