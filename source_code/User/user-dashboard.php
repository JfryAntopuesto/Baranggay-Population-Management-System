<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include the database connection and operations class
include "../../database/database-connection.php";
include "../../database/database-operations.php";
// Create an instance of DatabaseOperations
$db = new DatabaseOperations($conn);
// Retrieve the user ID from the session
$userID = $_SESSION['userID']; // Ensure you have stored userID in the session

// Fetch user information from session
$userInfo = array(
    'firstname' => $_SESSION['firstname'] ?? '',
    'lastname' => $_SESSION['lastname'] ?? '',
    'middlename' => $_SESSION['middlename'] ?? '',
    'username' => $_SESSION['username'] ?? ''
);

// Get household and purok information from database
$householdInfo = $db->getUserHouseholdInfo($userID);
$profilePicturePath = $db->getProfilePictureByUserID($userID);
if ($profilePicturePath && !empty($profilePicturePath)) {
    // Remove any leading ../ or / or . from the path
    $profilePicturePath = '../' . ltrim($profilePicturePath, '/.');
} else {
    $profilePicturePath = '../assets/default-profile.png';
}
$householdMembersCount = $householdInfo ? $db->getHouseholdMembersCount($householdInfo['householdID']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Barangay Population Management System</title>
  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      margin: 0;
      background-color: #f5f5f5;
    }

    .profile-image {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 4px solid #0033cc;
      padding: 3px;
      margin-bottom: 20px;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .profile-image:hover {
      transform: scale(1.05);
    }

    #userName {
      color: #0033cc;
      font-size: 22px;
      margin-bottom: 5px;
    }

    .user-role {
      color: #666;
      font-size: 14px;
      margin: 0;
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
      from { opacity: 0; transform: translateuY(-40px);}
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
      width: 25%;
      background: white;
      padding: 25px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.1);
      height: fit-content;
    }

    .left-panel img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      border: 4px solid #0033cc;
      padding: 3px;
      margin-bottom: 20px;
      transition: transform 0.3s ease;
    }

    .left-panel img:hover {
      transform: scale(1.05);
    }

    .left-panel h4 {
      color: #0033cc;
      font-size: 20px;
      margin: 15px 0;
    }

    .left-panel h4 em {
      color: #666;
      font-size: 16px;
    }

    .info {
      margin: 20px 0;
      text-align: left;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 10px;
    }

    .info p {
      margin: 10px 0;
      color: #444;
    }

    .info strong {
      display: inline-block;
      width: 140px;
      color: #0033cc;
    }

    .buttons button {
      display: block;
      margin: 15px auto;
      padding: 12px;
      width: 90%;
      border: none;
      background: #0033cc;
      color: white;
      cursor: pointer;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .buttons button:hover {
      background: #002699;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .middle-panel {
      width: 50%;
      background: white;
      padding: 25px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }

    .middle-panel h3 {
      color: #0033cc;
      margin-bottom: 20px;
      font-size: 22px;
      border-bottom: 2px solid #0033cc;
      padding-bottom: 10px;
    }

    .announcement-box {
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      border-left: 4px solid #0033cc;
      position: relative;
    }

    .announcement-box:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .announcement-box strong {
      color: #0033cc;
      font-size: 1.2rem;
      font-weight: 600;
      display: block;
      margin-bottom: 12px;
    }

    .announcement-box small {
      color: #666;
      display: block;
      margin-bottom: 15px;
      font-size: 0.9rem;
    }

    .announcement-box p {
      color: #444;
      line-height: 1.6;
      margin: 0;
      font-size: 1.05rem;
    }

    .right-panel {
      width: 25%;
      background: white;
      padding: 25px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }

    .right-panel h3 {
      color: #0033cc;
      margin-bottom: 20px;
      font-size: 22px;
      border-bottom: 2px solid #0033cc;
      padding-bottom: 10px;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .container {
        flex-direction: column;
      }
      .left-panel, .middle-panel, .right-panel {
        width: 100%;
      }
    }

    .fade-in {
      animation: fadeIn 1s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px);}
      to { opacity: 1; transform: translateY(0);}
    }

    .slide-in {
      animation: slideIn 0.8s cubic-bezier(.77,0,.18,1);
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-60px);}
      to { opacity: 1; transform: translateX(0);}
    }

    .pop-in {
      animation: popIn 0.6s cubic-bezier(.77,0,.18,1);
    }
    @keyframes popIn {
      0% { opacity: 0; transform: scale(0.8);}
      80% { opacity: 1; transform: scale(1.05);}
      100% { opacity: 1; transform: scale(1);}
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

    .middle-panel h3 {
      color: #0033cc;
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 25px;
      padding-bottom: 12px;
      border-bottom: 2px solid #0033cc;
    }

    .like-button {
      position: absolute;
      right: 25px;
      bottom: 25px;
      background: none;
      border: none;
      color: #666;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.9rem;
      padding: 5px 10px;
      border-radius: 20px;
      transition: all 0.3s ease;
    }

    .like-button:hover {
      background: rgba(0, 51, 204, 0.1);
    }

    .like-button.liked {
      color: #0033cc;
    }

    .like-button i {
      font-size: 1.1rem;
    }

    .like-count {
      font-weight: 500;
    }

    .notification-panel {
      padding: 20px;
    }

    .notification-item {
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 10px;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      border-left: 4px solid #0033cc;
      position: relative;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
    }

    .notification-item.unread {
      background: #f8f9ff;
      border-left: 4px solid #0066ff;
    }

    .notification-item.unread .notification-content {
      font-weight: 600;
    }

    .notification-content {
      color: #444;
      line-height: 1.5;
    }

    .notification-time {
      color: #666;
      font-size: 0.9rem;
      margin-top: 5px;
      margin-bottom: 10px;
    }

    .mark-read-btn {
      position: static;
      margin-top: auto;
      align-self: flex-end;
      padding: 8px 15px;
      background: #0033cc;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    .mark-read-btn:hover {
      background: #002299;
      transform: scale(1.05);
    }

    .no-notifications {
      color: #666;
      text-align: center;
      padding: 20px;
      font-style: italic;
    }

    .staff-message {
      margin-top: 10px;
      padding: 10px;
      background: #f0f4ff;
      border-left: 3px solid #0033cc;
      border-radius: 4px;
      font-size: 0.95rem;
      color: #444;
    }

    .staff-message strong {
      color: #0033cc;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="../js/websocket-client.js"></script>
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
    BARANGAY POPULATION MANAGEMENT SYSTEM
    <button class="logout-btn" onclick="location.href='../logout.php'">LOGOUT</button>
  </header>

  <div class="container">
    <div class="left-panel">
      <img src="<?php echo $profilePicturePath; ?>" alt="Profile Picture" class="profile-image">

      <h4 id="userName">
        <?php
          // Show full name with middle name below the profile picture
          $fullName = trim(
            (isset($userInfo['firstname']) ? $userInfo['firstname'] : '') . ' ' .
            (isset($userInfo['middlename']) && $userInfo['middlename'] ? $userInfo['middlename'] . ' ' : '') .
            (isset($userInfo['lastname']) ? $userInfo['lastname'] : '')
          );
          echo htmlspecialchars($fullName ? $fullName : 'Loading...');
        ?>
      </h4>
      <p class="user-role">Resident</p>
    <div class="info">
        <!-- <p><strong>Full Name:</strong> <span><?php echo htmlspecialchars(trim((isset($userInfo['firstname']) ? $userInfo['firstname'] : '') . ' ' . (isset($userInfo['middlename']) ? $userInfo['middlename'] : '') . ' ' . (isset($userInfo['lastname']) ? $userInfo['lastname'] : ''))); ?></span></p> -->
        <p><strong>Purok ID:</strong> <span><?php echo $householdInfo ? $householdInfo['purokID'] : 'Not assigned'; ?></span></p>
        <p><strong>Purok Name:</strong> <span><?php echo $householdInfo ? $householdInfo['purok_name'] : 'Not assigned'; ?></span></p>
        <p><strong>Household ID:</strong> <span><?php echo $householdInfo ? $householdInfo['householdID'] : 'Not assigned'; ?></span></p>
        <p><strong>Household Members:</strong> <span><?php echo $householdMembersCount; ?></span></p>
    </div>
    <!-- Debug: Show profile picture path -->
    <!-- <div style="color:red; font-size:12px;">Profile path: <?php echo $profilePicturePath; ?></div> -->

      <div class="buttons">
        <button type="button" onclick="location.href='user-household.php'">MEMBERS</button>
        <button type="button" onclick="location.href='user-appointment.php'">APPOINTMENTS</button>
        <button type="button" onclick="location.href='tickets/user-ticket.php'">REQUEST</button>
        <button type="button" onclick="location.href='complaint/user-complaint.php'">COMPLAINT</button>
      </div>
    </div>

    <div class="middle-panel">
      <h3>ANNOUNCEMENTS</h3>
      <div id="announcementsList"></div>
    </div>

    <div class="right-panel">
      <h3>NOTIFICATIONS</h3>
      <div class="notification-panel" id="notificationPanel">
        <!-- Notifications will be loaded here -->
      </div>
    </div>
  </div>

  <script>
    let wsClient;

    // Function to fetch and display announcements
    function getAnnouncements() {
        fetch('../announcement-handler.php?action=get_announcements')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    document.getElementById('announcementsList').innerHTML = 
                        '<div style="color:#ff0000;">Error loading announcements. Please try again.</div>';
                    return;
                }
                renderAnnouncements(Array.isArray(data) ? data : []);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('announcementsList').innerHTML = 
                    '<div style="color:#ff0000;">Error loading announcements. Please try again.</div>';
            });
    }

    function toggleLike(annID) {
        const formData = new FormData();
        formData.append('action', 'toggle_like');
        formData.append('annID', annID);

        fetch('../announcement-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the like button and count for this announcement
                const likeButton = document.querySelector(`[data-announcement-id="${annID}"]`);
                const likeCount = likeButton.querySelector('.like-count');
                
                if (data.announcement.user_has_liked) {
                    likeButton.classList.add('liked');
                } else {
                    likeButton.classList.remove('liked');
                }
                
                likeCount.textContent = data.announcement.like_count;
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function renderAnnouncements(announcements) {
        const list = document.getElementById('announcementsList');
        if (!Array.isArray(announcements) || announcements.length === 0) {
            list.innerHTML = '<div style="color:#888;">No announcements yet.</div>';
            return;
        }
        list.innerHTML = announcements.map(a => `
            <div class="announcement-box">
                <strong>${a.title}</strong>
                <small>${a.date}</small>
                <p>${a.message}</p>
                <button class="like-button ${a.user_has_liked ? 'liked' : ''}" 
                        onclick="toggleLike(${a.annID})" 
                        data-announcement-id="${a.annID}">
                    <i class="fas fa-heart"></i>
                    <span class="like-count">${a.like_count}</span>
                </button>
            </div>
        `).join('');
    }

    // Track if a notification update is in progress to prevent overlapping updates
    let notificationUpdateInProgress = false;
    
    // Function to load notifications
    function loadNotifications() {
        // Prevent multiple simultaneous notification updates
        if (notificationUpdateInProgress) {
            console.log('Notification update already in progress, skipping');
            return;
        }
        
        notificationUpdateInProgress = true;
        console.log('Loading notifications from server...');
        
        fetch('../notification-handler.php?action=get_notifications')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                renderNotifications(data);
            })
            .catch(error => {
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset the flag when done, regardless of success or failure
                notificationUpdateInProgress = false;
            });
    }

    // Function to mark notification as read
    // Track notifications that are currently being marked as read to prevent duplicates
    const notificationsBeingMarkedAsRead = new Set();
    
    function markAsRead(notifID, event) {
        // Prevent default action and event bubbling
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        } else {
            console.error('Event object is missing');
            return;
        }
        
        console.log(`Attempting to mark notification ${notifID} as read`);
        
        // Find the notification item and button
        const button = event.target.closest('.mark-read-btn');
        const notificationItem = event.target.closest('.notification-item');
        
        if (!button || !notificationItem) {
            console.error('Button or notification item not found');
            return;
        }
        
        // Check if the notification is already marked as read
        if (!notificationItem.classList.contains('unread')) {
            console.log(`Notification ${notifID} is already marked as read`);
            return;
        }
        
        // Prevent duplicate mark as read operations for the same notification
        if (notificationsBeingMarkedAsRead.has(notifID)) {
            console.log(`Notification ${notifID} is already being marked as read, skipping`);
            return;
        }

        // Add this notification to the tracking set
        notificationsBeingMarkedAsRead.add(notifID);
        
        // Disable the button and update text
        button.disabled = true;
        button.textContent = 'Marking...';
        
        // Make the AJAX request
        fetch('../notification-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_read&notifID=${notifID}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data); // Debug log
            
            if (data.success) {
                console.log(`Successfully marked notification ${notifID} as read`);
                
                // Update the UI to show the notification as read
                notificationItem.classList.remove('unread');
                
                // Remove the mark as read button
                button.remove();
                
                // Update our local tracking to mark this notification as read
                if (currentNotifications && Array.isArray(currentNotifications)) {
                    currentNotifications = currentNotifications.map(n => {
                        if (n.notifID == notifID) {
                            console.log(`Updating notification ${notifID} in local data`);
                            return { ...n, is_read: true };
                        }
                        return n;
                    });
                    
                    // Force a refresh of the notifications display
                    console.log('Refreshing notifications display');
                    renderNotifications(currentNotifications);
                }
                
                // Trigger a fresh load of notifications from the server
                setTimeout(() => {
                    console.log('Reloading notifications from server');
                    loadNotifications();
                }, 500);
                
                // Notify other clients via WebSocket that this notification was read
                if (wsClient && wsClient.ws && wsClient.ws.readyState === WebSocket.OPEN && typeof WebSocket !== 'undefined') {
                    wsClient.send({
                        type: 'notification_read',
                        notifID: notifID,
                        userId: <?php echo json_encode($_SESSION['userID'] ?? 0); ?>
                    });
                }
            } else {
                throw new Error(data.error || data.debug || 'Failed to mark notification as read');
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
            if (button && !button.isConnected) {
                console.log('Button is no longer in the DOM, cannot update it');
            } else if (button) {
                button.disabled = false;
                button.textContent = 'Mark as Read';
            }
            alert('Failed to mark notification as read: ' + error.message);
        })
        .finally(() => {
            // Remove this notification from the tracking set when done
            notificationsBeingMarkedAsRead.delete(notifID);
            console.log(`Removed notification ${notifID} from tracking set`);
        });
    }

    // Keep track of notifications we've already seen to prevent duplicates
    let currentNotifications = [];
    
    function renderNotifications(notifications) {
        console.log('Rendering notifications:', notifications); // Debug log
        
        if (!Array.isArray(notifications)) {
            console.log('Notifications is not an array:', notifications);
            notifications = [];
        }
        
        // Remove duplicates and prioritize read notifications over unread ones
        const uniqueNotifications = [];
        const seenIds = new Set();
        
        // First pass: collect the best version of each notification (prioritize read ones)
        const notificationMap = new Map();
        
        for (const notification of notifications) {
            if (!notification || !notification.notifID) {
                console.log('Invalid notification object:', notification);
                continue;
            }
            
            const notifID = notification.notifID.toString();
            const isRead = notification.is_read === true || notification.is_read === 1 || notification.is_read === '1';
            
            // If we haven't seen this ID yet, or if this one is read and the previous one wasn't
            if (!notificationMap.has(notifID) || (isRead && !notificationMap.get(notifID).isRead)) {
                notificationMap.set(notifID, {
                    notification: notification,
                    isRead: isRead
                });
            }
        }
        
        // Convert map back to array
        const dedupedNotifications = Array.from(notificationMap.values()).map(item => item.notification);
        
        // Sort by datetime, most recent first
        dedupedNotifications.sort((a, b) => {
            return new Date(b.datetime) - new Date(a.datetime);
        });
        
        // Store the deduplicated notifications for reference
        currentNotifications = dedupedNotifications;
        
        // Update the UI
        const panel = document.getElementById('notificationPanel');
        if (dedupedNotifications.length === 0) {
            console.log('No notifications to display after deduplication');
            panel.innerHTML = '<div class="no-notifications">No notifications</div>';
            return;
        }
        
        panel.innerHTML = dedupedNotifications.map(notification => {
            // Remove staff comment from content if it exists
            let content = notification.content;
            if (notification.staff_comment) {
                content = content.replace(notification.staff_comment, '').trim();
            }
            
            // Convert is_read to boolean to ensure proper type comparison
            const isRead = notification.is_read === true || notification.is_read === 1 || notification.is_read === '1';
            
            return `
                <div class="notification-item ${isRead ? '' : 'unread'}" data-notif-id="${notification.notifID}">
                    <div>
                        <div class="notification-content">${content}</div>
                        ${notification.staff_comment ? `
                            <div class="staff-message">
                                <strong>Staff Response:</strong> ${notification.staff_comment}
                            </div>
                        ` : ''}
                        <div class="notification-time">${formatDate(notification.datetime)}</div>
                    </div>
                    ${!isRead ? `
                        <button class="mark-read-btn" onclick="markAsRead(${notification.notifID}, event)">
                            Mark as Read
                        </button>
                    ` : ''}
                </div>
            `;
        }).join('');
        
        console.log(`Rendered ${dedupedNotifications.length} unique notifications out of ${notifications.length} total`);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        });
    }

    // Initialize WebSocket connection
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing dashboard...'); // Debug log
        
        // Initialize WebSocket with user ID (if WebSocketClient is available)
        const userId = <?php echo json_encode($_SESSION['userID'] ?? 0); ?>;
        
        // Check if WebSocketClient is available
        if (typeof WebSocketClient !== 'undefined') {
            try {
                wsClient = new WebSocketClient(userId);
            } catch (error) {
                console.warn('WebSocket initialization failed:', error);
                wsClient = null;
            }
        } else {
            console.warn('WebSocketClient is not available. Real-time updates will be disabled.');
            wsClient = null;
        }

        // Handle real-time updates (only if WebSocket is available)
        if (wsClient) {
            wsClient.on('announcement_update', (data) => {
                console.log('Received announcement update:', data); // Debug log
                if (data.announcements) {
                    renderAnnouncements(data.announcements);
                }
            });

            wsClient.on('notification_update', (data) => {
                console.log('Received notification update:', data); // Debug log
                if (data.notifications) {
                    renderNotifications(data.notifications);
                }
            });
        }
        
        // Set up periodic notification refresh to catch database deletions
        // This will refresh notifications every 10 seconds for faster updates
        const notificationRefreshInterval = setInterval(() => {
            console.log('Performing periodic notification refresh');
            // Only refresh if no notification updates are in progress
            if (!notificationUpdateInProgress) {
                loadNotifications();
            } else {
                console.log('Skipping periodic refresh - update already in progress');
            }
        }, 10000); // 10 seconds

        // Initial data load
        getAnnouncements();
        loadNotifications();

        // Add connection status indicator
        const statusIndicator = document.createElement('div');
        statusIndicator.style.position = 'fixed';
        statusIndicator.style.bottom = '10px';
        statusIndicator.style.right = '10px';
        statusIndicator.style.padding = '5px 10px';
        statusIndicator.style.borderRadius = '5px';
        statusIndicator.style.fontSize = '12px';
        statusIndicator.style.transition = 'all 0.3s ease';
        document.body.appendChild(statusIndicator);

        // Update connection status
        function updateConnectionStatus(connected) {
            statusIndicator.style.backgroundColor = connected ? '#4CAF50' : '#f44336';
            statusIndicator.style.color = 'white';
            statusIndicator.textContent = connected ? 'Connected' : 'Disconnected';
        }

        // Add connection status handlers (only if WebSocket is available)
        if (wsClient) {
            wsClient.on('connect', () => {
                console.log('WebSocket connected');
                updateConnectionStatus(true);
            });

            wsClient.on('disconnect', () => {
                console.log('WebSocket disconnected');
                updateConnectionStatus(false);
            });

            // Initial status
            updateConnectionStatus(wsClient.ws && wsClient.ws.readyState === WebSocket.OPEN);
        } else {
            // Hide connection status if WebSocket is not available
            statusIndicator.style.display = 'none';
        }

        // Cleanup on page unload using modern event listener
        window.addEventListener('beforeunload', function() {
            if (wsClient) {
                wsClient.disconnect();
            }
            // Clear the notification refresh interval to prevent memory leaks
            if (notificationRefreshInterval) {
                clearInterval(notificationRefreshInterval);
            }
        });
    });
  </script>
</body>
</html>
