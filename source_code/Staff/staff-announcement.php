<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Database connection
include '../../database/database-connection.php';
include '../../database/database-operations.php';

// Create an instance of DatabaseOperations
$db = new DatabaseOperations($conn);

// Handle AJAX request to save announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_announcement') {
    header('Content-Type: application/json');

    try {
        $content = $_POST['content'];
        if ($db->addAnnouncement($content)) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to save announcement");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle AJAX request to get announcements
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_announcements') {
    header('Content-Type: application/json');

    try {
        $announcements = $db->getAnnouncements();
        echo json_encode($announcements);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Barangay Population Management System - Staff Announcements</title>
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

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #fff;
            margin: 0;
        }

        .header-bar {
            background: #0033cc;
            color: #fff;
            padding: 32px 0 18px 0;
            text-align: center;
            font-size: 2.1rem;
            font-weight: bold;
            letter-spacing: 2px;
            border-radius: 0 0 32px 32px;
        }

        .container {
            display: flex;
            padding: 30px;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .left-panel {
            width: 250px;
            background: white;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            height: fit-content;
            flex-shrink: 0;
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

        .middle-panel {
            flex: 1;
            min-width: 320px;
            background: white;
            padding: 25px 30px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .middle-panel h3 {
            color: #0033cc;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0033cc;
        }

        .like-count,
        .seen-count {
            position: absolute;
            bottom: 25px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
            background: rgba(0, 51, 204, 0.05);
        }

        .like-count {
            right: 25px;
        }

        .seen-count {
            right: 120px;
        }

        .announcement-list {
            margin-top: 24px;
        }

        .announcement-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #0033cc;
            position: relative;
            padding-bottom: 60px;
        }

        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .announcement-card strong {
            color: #0033cc;
            font-size: 1.2rem;
            font-weight: 600;
            display: block;
            margin-bottom: 12px;
        }

        .announcement-card small {
            color: #666;
            display: block;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .announcement-card p {
            color: #444;
            line-height: 1.6;
            margin: 0;
            font-size: 1.05rem;
        }

        .main-flex {
            display: flex;
            height: calc(100vh - 80px);
            min-height: 0;
        }

        .left-panel {
            width: 22%;
            background: white;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
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

        .middle-panel {
            width: 76%;
            background: white;
            padding: 25px 30px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            min-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .middle-panel h3 {
            color: #0033cc;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0033cc;
        }

        .like-count {
            position: absolute;
            right: 25px;
            bottom: 25px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
            background: rgba(0, 51, 204, 0.05);
        }

        .like-count i {
            color: #0033cc;
            font-size: 1.1rem;
        }

        .like-count span {
            font-weight: 500;
        }

        .seen-count {
            position: absolute;
            right: 25px;
            bottom: 60px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
            background: rgba(0, 51, 204, 0.05);
        }

        .seen-count i {
            color: #0033cc;
            font-size: 1.1rem;
        }

        .seen-count span {
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
                padding: 20px;
            }

            .left-panel {
                width: 100%;
                margin-bottom: 20px;
            }

            .middle-panel {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .left-panel,
            .middle-panel {
                padding: 20px;
            }

            .announcement-card {
                padding: 20px;
            }

            .like-count,
            .seen-count {
                position: static;
                display: inline-flex;
                margin-top: 15px;
                margin-right: 15px;
            }
        }

        @media (max-width: 480px) {

            .like-count,
            .seen-count {
                position: static;
                display: inline-flex;
                margin-top: 15px;
                margin-right: 15px;
            }

            .announcement-card {
                padding-bottom: 25px;
            }
        }

        .form-section {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid rgba(0, 51, 204, 0.1);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .form-section label {
            color: #0033cc;
            font-weight: 600;
            font-size: 1.2rem;
            display: block;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section label i {
            font-size: 1.3rem;
        }

        .form-section textarea {
            width: 100%;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1.1rem;
            background: #fff;
            color: #444;
            outline: none;
            transition: all 0.3s ease;
            resize: vertical;
            min-height: 150px;
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
        }

        .form-section textarea:focus {
            border-color: #0033cc;
            box-shadow: 0 0 0 4px rgba(0, 51, 204, 0.1);
        }

        .form-section textarea::placeholder {
            color: #999;
            font-style: italic;
        }

        .form-section button {
            width: 100%;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-section button:hover {
            background: linear-gradient(135deg, #002299, #0052cc);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 51, 204, 0.2);
        }

        .form-section button:active {
            transform: translateY(0);
        }

        .form-section button i {
            font-size: 1.2rem;
        }

        .form-section .char-count {
            text-align: right;
            color: #666;
            font-size: 0.9rem;
            margin-top: -15px;
            margin-bottom: 15px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
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
            <div class="sidebar-nav">
                <a href="staff-dashboard.php" class="sidebar-btn">
                    <span>DASHBOARD</span>
                </a>
                <a href="staff-announcement.php" class="sidebar-btn active">
                    <span>ANNOUNCEMENT</span>
                </a>
                <a href="staff-request.php" class="sidebar-btn">
                    <span>REQUESTS</span>
                </a>
                <a href="complaint/staff-complaint.php" class="sidebar-btn">
                    <span>COMPLAINT</span>
                </a>
                <a href="staff-appointments-dashboard.php" class="sidebar-btn">
                    <span>APPOINTMENTS</span>
                </a>
            </div>
        </div>
        <div class="middle-panel">
            <h3>Announcements</h3>
            <div class="form-section">
                <label>
                    <i class="fas fa-bullhorn"></i>
                    POST AN ANNOUNCEMENT
                </label>
                <textarea id="announcementText" placeholder="Type your announcement here..." required></textarea>
                <div class="char-count">
                    <span id="charCount">0</span> characters
                </div>
                <button onclick="postAnnouncement()">
                    <i class="fas fa-paper-plane"></i>
                    POST ANNOUNCEMENT
                </button>
            </div>
            <hr style="border:1px solid #0033cc; margin:32px 0;">
            <div class="announcement-list" id="announcementList"></div>
        </div>
    </div>
    <script>
        // Add refresh interval variable
        let refreshInterval;

        function getAnnouncements() {
            fetch('../announcement-handler.php?action=get_announcements')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        document.getElementById('announcementList').innerHTML =
                            '<div style="color:#ff0000;">Error loading announcements. Please try again.</div>';
                        return;
                    }
                    renderAnnouncements(Array.isArray(data) ? data : []);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('announcementList').innerHTML =
                        '<div style="color:#ff0000;">Error loading announcements. Please try again.</div>';
                });
        }

        function postAnnouncement() {
            const text = document.getElementById('announcementText').value.trim();
            if (!text) return;

            const formData = new FormData();
            formData.append('action', 'save_announcement');
            formData.append('content', text);

            fetch('../announcement-handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('announcementText').value = '';
                        getAnnouncements();
                    } else {
                        alert('Error saving announcement: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving announcement. Please try again.');
                });
        }

        function renderAnnouncements(announcements) {
            const list = document.getElementById('announcementList');
            if (!Array.isArray(announcements) || announcements.length === 0) {
                list.innerHTML = '<div style="color:#888;">No announcements yet.</div>';
                return;
            }
            list.innerHTML = announcements.map(a => `
                <div class="announcement-card" data-announcement-id="${a.annID}">
                    <strong>${a.title}</strong>
                    <small>${a.date}</small>
                    <p>${a.message}</p>
                    <div class="seen-count">
                        <i class="fas fa-eye"></i>
                        <span class="seen-count-number">${a.seen_count} ${a.seen_count === 1 ? 'view' : 'views'}</span>
                    </div>
                    <div class="like-count">
                        <i class="fas fa-heart"></i>
                        <span class="like-count-number">${a.like_count} ${a.like_count === 1 ? 'like' : 'likes'}</span>
                    </div>
                </div>
            `).join('');
        }

        function updateCounts() {
            fetch('../announcement-handler.php?action=get_announcements')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    data.forEach(announcement => {
                        const card = document.querySelector(`[data-announcement-id="${announcement.annID}"]`);
                        if (card) {
                            const likeCount = card.querySelector('.like-count-number');
                            const seenCount = card.querySelector('.seen-count-number');

                            if (likeCount) {
                                likeCount.textContent = `${announcement.like_count} ${announcement.like_count === 1 ? 'like' : 'likes'}`;
                            }
                            if (seenCount) {
                                seenCount.textContent = `${announcement.seen_count} ${announcement.seen_count === 1 ? 'view' : 'views'}`;
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error updating counts:', error);
                });
        }

        // Start auto-refresh when page loads
        window.onload = function() {
            getAnnouncements();
            // Update counts every 5 seconds
            refreshInterval = setInterval(updateCounts, 5000);
        };

        // Clean up interval when page is unloaded
        window.onunload = function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        };

        // Add character count functionality
        document.getElementById('announcementText').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount;
        });
    </script>
</body>

</html>