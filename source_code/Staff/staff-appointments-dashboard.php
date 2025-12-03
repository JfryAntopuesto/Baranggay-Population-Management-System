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
    <title>Barangay Population Management System - Staff Appointments</title>
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
            width: 250px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            height: fit-content;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex-shrink: 0;
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
            position: relative;
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
        .sidebar-btn .badge {
            background: #d00000;
            color: #fff;
            border-radius: 50%;
            font-size: 0.8rem;
            padding: 2px 6px;
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
        }
        .calendar-section {
            background: #f8fbff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,51,204,0.08);
            padding: 16px 12px 12px 12px;
            margin: 24px 0;
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 0;
            height: 100%;
            box-sizing: border-box;
            justify-content: center;
        }
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            gap: 24px;
        }
        .calendar-header button {
            background: #fff;
            border: 2px solid #0033cc;
            color: #0033cc;
            font-size: 1.3rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            box-shadow: 0 2px 8px #0033cc11;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .calendar-header button:hover {
            background: #0033cc;
            color: #fff;
            box-shadow: 0 4px 16px #0033cc33;
        }
        #calendarTitle {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0033cc;
            letter-spacing: 1px;
        }
        .calendar-table {
            border-collapse: separate;
            border-spacing: 8px;
            background: transparent;
            width: 100%;
            margin: 0 auto;
        }
        .calendar-table th, .calendar-table td {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,51,204,0.05);
            font-size: 1.1rem;
            color: #0033cc;
            font-weight: 500;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            height: 56px;
            width: 56px;
            position: relative;
            cursor: pointer;
        }
        .calendar-table th {
            background: #e9f0ff;
            color: #0033cc;
            font-weight: bold;
            font-size: 1rem;
            border-bottom: 2px solid #cce0ff;
        }
        .calendar-table td.today {
            background: linear-gradient(135deg, #e3f0ff 60%, #cce0ff 100%);
            color: #0033cc;
            border: 2px solid #4285f4;
            box-shadow: 0 0 12px #4285f444;
        }
        .calendar-table td.event-day {
            background: linear-gradient(135deg, #fff7e6 60%, #ffe0b2 100%);
            border-bottom: 4px solid #34a853;
            color: #222;
        }
        .calendar-table td .event-dot {
            width: 10px;
            height: 10px;
            background: #34a853;
            border-radius: 50%;
            display: inline-block;
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 0 4px #34a85388;
        }
        .calendar-table td:hover, .calendar-table td.event-day:hover {
            background: #e3f0ff;
            color: #0033cc;
            box-shadow: 0 4px 16px #0033cc22;
            z-index: 2;
        }
        .right-panel { width: 20vw; min-width: 120px; border-left: 2px solid #0033cc; padding: 18px 0 0 18px; height: 100%; box-sizing: border-box; display: flex; flex-direction: column; }
        .right-panel h3 { color: #0033cc; font-size: 1.2vw; font-weight: bold; margin-bottom: 12px; }
        .appt-list-card {
            display: flex;
            align-items: center;
            border: 1px solid #0033cc;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 8px;
            background: #fff;
            color: #0033cc;
            font-size: 0.9rem;
            transition: transform 0.2s, box-shadow 0.2s;
            flex-wrap: wrap;
            gap: 4px;
        }
        .appt-list-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,51,204,0.1);
        }
        .appt-list-card label {
            color: #0033cc;
            font-weight: bold;
            margin-right: 4px;
            min-width: 100px;
        }
        .appt-list-card span {
            font-style: italic;
            color: #6c6cff;
            margin-right: 16px;
        }
        .appt-list-card .appt-num {
            min-width: 120px;
        }
        .appt-list-card .appt-time {
            min-width: 100px;
        }
        .appt-list-card .appt-purpose {
            flex-grow: 1;
            min-width: 200px;
        }
        .set-appt-btn { position: fixed; bottom: 32px; right: 40px; z-index: 100; background: #fff; border: 2px solid #0033cc; color: #0033cc; font-weight: bold; font-size: 1.1rem; border-radius: 30px; padding: 12px 32px; text-decoration: none; box-shadow: 0 2px 12px rgba(0,51,204,0.07); transition: background 0.2s, color 0.2s, box-shadow 0.2s; }
        .set-appt-btn:hover { background: #0033cc; color: #fff; }
        .set-appt-btn .badge { background: #d00000; color: #fff; border-radius: 50%; font-size: 0.95rem; padding: 2px 6px; position: absolute; right: -18px; top: -8px; font-weight: bold; }
        @media (max-width: 1000px) {
            .container {
                flex-direction: column;
                gap: 20px;
            }
            .left-panel {
                width: 100%;
                min-width: 0;
            }
            .sidebar-btn {
                font-size: 14px;
                padding: 10px 0 10px 15px;
            }
            .sidebar-btn .badge {
                font-size: 0.7rem;
                padding: 1px 5px;
            }
            .calendar-section { padding: 0; height: auto; }
            .calendar-table th, .calendar-table td { width: 32px; height: 28px; font-size: 0.85rem; }
            .calendar-title { font-size: 1.1rem; }
            .appt-list-card { font-size: 0.92rem; padding: 7px; }
            .set-appt-btn { right: 10px; bottom: 10px; padding: 10px 18px; font-size: 0.95rem; }
        }
        @media (max-width: 700px) {
            .header-bar { font-size: 1.1rem; padding: 18px 0 10px 0; }
            .calendar-title { font-size: 0.98rem; }
            .calendar-table th, .calendar-table td { width: 20px; height: 18px; font-size: 0.7rem; }
            .appt-list-card { font-size: 0.8rem; padding: 4px; }
            .right-panel h3 { font-size: 0.9rem; }
        }
        /* --- FullCalendar Custom Theme --- */
        #calendar {
            width: 100%;
            min-width: 0;
            max-width: 1150px;
            margin: 0 auto;
            background: #f8fbff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,51,204,0.08);
            padding: 8px 8px 8px 8px;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
        }
        .fc {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: #f8fbff;
            border-radius: 18px;
        }
        .fc-toolbar-title {
            color: #0033cc;
            font-size: 1.2rem !important;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .fc-button {
            background: #0033cc;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            font-weight: 600;
            box-shadow: 0 2px 8px #0033cc11;
            transition: background 0.2s, color 0.2s;
            padding: 0.3em 0.6em !important;
        }
        .fc-button:hover, .fc-button:focus {
            background: #0066ff;
            color: #fff;
        }
        .fc-daygrid-day {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,51,204,0.05);
            margin: 2px;
            transition: box-shadow 0.2s;
            height: 90px !important;
            position: relative;
        }
        .fc-daygrid-day.fc-day-today {
            background: linear-gradient(135deg, #e3f0ff 60%, #cce0ff 100%);
            border: 2px solid #4285f4;
            box-shadow: 0 0 12px #4285f444;
        }
        .fc-daygrid-event {
            background: #0033cc;
            color: #fff;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            box-shadow: 0 2px 8px #0033cc22;
            margin-bottom: 2px;
        }
        .fc-daygrid-event:hover {
            background: #0066ff;
            color: #fff;
        }
        .fc-scrollgrid {
            border-radius: 12px;
            overflow: hidden;
        }
        .fc-col-header-cell {
            background: #e9f0ff;
            color: #0033cc;
            font-weight: bold;
            font-size: 1rem;
            border-bottom: 2px solid #cce0ff;
            padding: 4px 0;
        }
        .fc-col-header-cell-cushion {
            padding: 4px 0;
            font-size: 0.9rem;
        }
        .fc-daygrid-day-number {
            color: #0033cc;
            font-weight: 600;
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.9rem;
        }
        .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            color: #4285f4;
        }
        .fc-toolbar {
            margin-bottom: 0.5em !important;
        }
        /* Responsive adjustments */
        @media (max-width: 1000px) {
            #calendar { padding: 4px; }
            .fc-toolbar-title { font-size: 1.1rem; }
        }
        @media (max-width: 700px) {
            #calendar { padding: 0; }
            .fc-toolbar-title { font-size: 0.98rem; }
        }
        /* Modal Styles */
        .modal-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.25);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal {
            background: #fff;
            border-radius: 14px;
            padding: 0;
            min-width: 800px;
            max-width: 90vw;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            position: relative;
        }
        .modal-staff-header {
            background: #0033cc;
            color: #fff;
            padding: 20px 0;
            border-radius: 14px 14px 0 0;
            font-size: 1.3rem;
            font-weight: bold;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }
        .modal-staff-header .back {
            font-size: 2rem;
            margin-left: 32px;
            margin-right: 18px;
            vertical-align: middle;
            cursor: pointer;
        }
        .modal-staff-header span {
            margin-left: 0;
        }
        .modal-content-staff {
            padding: 24px 32px;
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
        .calendar-section {
            background: #f8fbff;
        }
        /* Specific styling for appointment count badge on calendar */
        .appointment-count-badge {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background-color: #00b300;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <header>
        BARANGAY POPULATION MANAGEMENT SYSTEM
    </header>
    <div class="container">
        <div class="left-panel">
            <div class="sidebar-nav">
                <a href="staff-dashboard.php" class="sidebar-btn">DASHBOARD</a>
                <a href="staff-announcement.php" class="sidebar-btn">ANNOUNCEMENT</a>
                <a href="staff-request.php" class="sidebar-btn">REQUESTS <span class="badge" id="pendingRequestsBadge">0</span></a>
                <a href="complaint/staff-complaint.php" class="sidebar-btn">COMPLAINT <span class="badge" id="pendingComplaintsBadge">0</span></a>
                <a href="staff-appointments-dashboard.php" class="sidebar-btn active">APPOINTMENTS <span class="badge" id="pendingAppointmentsBadge">0</span></a>
            </div>
        </div>
        <div class="calendar-section">
            <div id="calendar"></div>
        </div>
        <div class="right-panel">
            <h3>SCHEDULED APPOINTMENTS</h3>
            <div id="scheduledAppts"></div>
            <a href="staff-appointments.php" class="set-appt-btn">SET APPOINTMENTS <span class="badge" id="apptBadge">0</span></a>
        </div>
    </div>
    <!-- Modal for day appointments -->
    <div class="modal-bg" id="dayApptModalBg" style="display:none;">
        <div class="modal" style="min-width:400px;max-width:90vw;">
            <div class="modal-staff-header">
                <span class="back" onclick="closeDayApptModal()">&#8592;</span>
                <span>APPOINTMENTS FOR THIS DAY</span>
            </div>
            <div id="dayApptModalContent" class="modal-content-staff"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
    let calendar;
    let approvedAppointments = [];
    let appointmentCountsForCalendar = {}; // Variable to store counts for dayCellDidMount

    function formatDateLocal(dateObj) {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function closeDayApptModal() {
        document.getElementById('dayApptModalBg').style.display = 'none';
    }

    function showDayApptModal(appts, dateStr) {
        const modalBg = document.getElementById('dayApptModalBg');
        const modalContent = document.getElementById('dayApptModalContent');
        
        if (appts.length === 0) {
            modalContent.innerHTML = `<div style="text-align:center;color:#666;padding:20px;">No approved appointments for ${dateStr}.</div>`;
        } else {
            modalContent.innerHTML = `
                <div style="margin-bottom: 20px; color: #00b300; font-weight: bold;">${dateStr}</div>
                ${appts.map(a => `
                    <div class="appt-list-card" style="border-color: #00b300; color: #00b300; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <label style="color: #00b300;">USER:</label>
                                <span>${a.userName}</span>
                            </div>
                            <div>
                                <label style="color: #00b300;">TIME:</label>
                                <span>${a.appointment_time}</span>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <label style="color: #00b300;">PURPOSE:</label>
                            <span>${a.purpose}</span>
                        </div>
                    </div>
                `).join('')}
            `;
        }
        modalBg.style.display = 'flex';
    }

    function loadAppointments() {
        return fetch('appointments.php?status=APPROVED')
            .then(response => response.json())
            .then(result => {
                if (!result.success) return;
                
                approvedAppointments = result.data;
                
                // Group appointments by date and count them
                const countsByDate = {};
                result.data.forEach(appt => {
                    const date = appt.appointment_date;
                    if (!countsByDate[date]) {
                        countsByDate[date] = 0;
                    }
                    countsByDate[date]++;
                });

                // Store counts in the accessible variable
                appointmentCountsForCalendar = countsByDate;

                console.log('Appointment counts by date:', appointmentCountsForCalendar); // Add logging

                // Update the scheduled appointments list in the right panel
                renderScheduledAppointments(approvedAppointments);
            })
            .catch(error => {
                console.error('Error loading appointments:', error);
            });
    }

    function renderScheduledAppointments(appts) {
        const listDiv = document.getElementById('scheduledAppts');
        listDiv.innerHTML = ''; // Clear previous list
        
        // Get current date in YYYY-MM-DD format
        const today = new Date();
        const currentDate = today.getFullYear() + '-' + 
                           String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(today.getDate()).padStart(2, '0');
        
        // Filter appointments for current date
        const todayAppts = appts.filter(appt => appt.appointment_date === currentDate);
        
        if (todayAppts.length === 0) {
            listDiv.innerHTML = '<div style="color:#666; text-align:center; padding:10px;">No appointments scheduled for today.</div>';
            return;
        }

        // Sort by time
        todayAppts.sort((a, b) => {
            const [h1, m1] = a.appointment_time.split(':').map(Number);
            const [h2, m2] = b.appointment_time.split(':').map(Number);
            if (h1 !== h2) return h1 - h2;
            return m1 - m2;
        });

        todayAppts.forEach(appt => {
            const card = document.createElement('div');
            card.className = 'appt-list-card';
            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; width: 100%;">
                    <span style="font-weight: bold; color: #0033cc;">${appt.appointment_time}</span>
                    <span style="font-style: normal; color: #00b300; font-weight: bold;">${appt.userName}</span>
                </div>
                <div style="width: 100%; margin-top: 8px;">
                    <label>PURPOSE:</label> <span>${appt.purpose}</span>
                </div>
            `;
            listDiv.appendChild(card);
        });
    }

    function updatePendingAppointmentsCount() {
        fetch('appointments.php?status=PENDING')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const badge = document.getElementById('apptBadge');
                    badge.textContent = result.count;
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: [], // Keep as empty, we are adding badges directly
            dateClick: function(info) {
                // Find appointments for the clicked date from the loaded data
                const clickedDate = info.dateStr;
                const appointmentsForDay = approvedAppointments.filter(appt => appt.appointment_date === clickedDate);
                showDayApptModal(appointmentsForDay, clickedDate);
            },
            datesSet: function(info) {
                // Update the scheduled appointments when calendar view changes
                renderScheduledAppointments(approvedAppointments);
            },
            dayCellDidMount: function(info) {
                // Add a custom badge for the appointment count
                const dateObj = info.date; // Use the native Date object from FullCalendar
                // Ensure dateStr is in YYYY-MM-DD format to match data keys
                const dateStr = dateObj.getFullYear() + '-' + 
                               (String(dateObj.getMonth() + 1).padStart(2, '0')) + '-' + 
                               (String(dateObj.getDate()).padStart(2, '0'));
                               
                const count = appointmentCountsForCalendar[dateStr] || 0; // Read from the accessible variable

                console.log(`Day: ${dateStr}, Count: ${count}`); // Add logging for each day cell

                if (count > 0) {
                    const badge = document.createElement('div');
                    badge.className = 'appointment-count-badge';
                    badge.textContent = count;
                    info.el.appendChild(badge);
                }
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            }
        });

        // First load the appointments data
        loadAppointments().then(() => {
            // Then render the calendar after data is loaded
            calendar.render();
        });
        
        // Fetch pending counts on page load
        fetchPendingCounts();
        
        // Initialize auto-refresh functionality
        const refreshFunctions = [
            loadAppointments,
            fetchPendingCounts
        ];
        
        // Create auto-refresh instance with 30-second interval
        const autoRefresh = initializeAutoRefresh(refreshFunctions, 30000);
        
        // Log auto-refresh status
        console.log('Auto-refresh initialized for appointments dashboard');
    });

    // Function to fetch and update pending counts for badges
    function fetchPendingCounts() {
        fetch('../Staff/staff-dashboard-handler.php') // Use the dashboard handler to get all counts
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

                // Update the specific badge for the 'SET APPOINTMENTS' button if needed, though it's currently showing pending, not total.
                // If this badge is meant to show *all* appointments, we would need a different handler or adjust this one.
                // For now, let's update it with pending appointments count for consistency with other badges.
                document.getElementById('apptBadge').textContent = data.pendingAppointments || '0';

            })
            .catch(error => {
                console.error('Error fetching pending counts:', error);
            });
    }
    </script>
</body>
</html> 