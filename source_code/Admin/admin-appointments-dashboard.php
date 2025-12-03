<?php
session_start();
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
    <title>Barangay Population Management System - Admin Appointments</title>
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
        .container {
            display: flex;
            padding: 30px;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
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
            padding: 32px 24px 24px 24px;
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
        .set-appt-btn { 
            position: fixed; 
            bottom: 32px; 
            right: 40px; 
            z-index: 100; 
            background: #fff; 
            border: 2px solid #0033cc; 
            color: #0033cc; 
            font-weight: bold; 
            font-size: 1.1rem; 
            border-radius: 30px; 
            padding: 12px 32px; 
            text-decoration: none; 
            box-shadow: 0 2px 12px rgba(0,51,204,0.07); 
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .set-appt-btn:hover { 
            background: #0033cc; 
            color: #fff; 
        }
        .set-appt-btn .badge { 
            background: #d00000; 
            color: #fff; 
            border-radius: 50%; 
            font-size: 0.95rem; 
            padding: 2px 6px; 
            position: absolute; 
            right: -18px; 
            top: -8px; 
            font-weight: bold; 
        }
        @media (max-width: 1000px) {
            .container {
                flex-direction: column;
                gap: 20px;
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
            padding: 18px 12px 12px 12px;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
        }
        .fc {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: #f8fbff;
            border-radius: 18px;
        }
        .fc-toolbar-title {
            color: #0033cc;
            font-size: 1.5rem;
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
            position: relative;
            min-height: 100px;
        }
        .fc-daygrid-day.fc-day-today {
            background: linear-gradient(135deg, #e3f0ff 60%, #cce0ff 100%);
            border: 2px solid #4285f4;
            box-shadow: 0 0 12px #4285f444;
        }
        .fc-daygrid-day.has-appointments {
            background: #f0fff0;
            border: 2px solid #00b300;
        }
        .appointment-badge {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            background: #00b300;
            color: white;
            border-radius: 50%;
            min-width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,179,0,0.3);
            z-index: 1;
            cursor: pointer;
            padding: 0 4px;
            border: 2px solid white;
        }
        .appointment-dot {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            width: 8px;
            height: 8px;
            background: #00b300;
            border-radius: 50%;
            box-shadow: 0 0 6px #00b30088;
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
            padding: 4px 8px;
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
        }
        .fc-daygrid-day-number {
            color: #0033cc;
            font-weight: 600;
        }
        .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            color: #4285f4;
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
        .back-btn {
            position: absolute;
            left: 20px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .back-btn svg {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
    <header>
        <button class="back-btn" onclick="window.location.href='admin-dashboard.php'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            BACK TO DASHBOARD
        </button>
        BARANGAY POPULATION MANAGEMENT SYSTEM
    </header>
    <div class="container">
        <div class="calendar-section">
            <div id="calendar"></div>
        </div>
        <div class="right-panel">
            <h3>SCHEDULED APPOINTMENTS</h3>
            <div id="scheduledAppts"></div>
        </div>
    </div>
    <button class="set-appt-btn" onclick="window.location.href='admin-appointments.php'">
        VIEW ALL APPOINTMENTS
        <span class="badge" id="pendingCount">0</span>
    </button>
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
    let pendingCount = 0;

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
                <div style="margin-bottom: 20px; color: #00b300; font-weight: bold; font-size: 1.2rem;">${dateStr}</div>
                ${appts.map(a => `
                    <div class="appt-list-card" style="border-color: #00b300; color: #00b300; margin-bottom: 15px; padding: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 10px;">
                            <div>
                                <label style="color: #00b300; font-weight: bold;">USER:</label>
                                <span style="margin-left: 8px;">${a.userName}</span>
                            </div>
                            <div>
                                <label style="color: #00b300; font-weight: bold;">TIME:</label>
                                <span style="margin-left: 8px;">${a.appointment_time}</span>
                            </div>
                        </div>
                        <div>
                            <label style="color: #00b300; font-weight: bold;">PURPOSE:</label>
                            <span style="margin-left: 8px;">${a.purpose}</span>
                        </div>
                    </div>
                `).join('')}
            `;
        }
        modalBg.style.display = 'flex';
    }

    function loadPendingCount() {
        fetch('admin-appointments.php?ajax=1&status=PENDING')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    pendingCount = result.data.length;
                    document.getElementById('pendingCount').textContent = pendingCount;
                }
            })
            .catch(error => console.error('Error loading pending count:', error));
    }

    function loadAppointments() {
        fetch('admin-appointments.php?ajax=1&status=APPROVED')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Appointments response:', result); // Debug log
                
                if (!result.success) {
                    console.error('Failed to load appointments:', result.error);
                    return;
                }
                
                approvedAppointments = result.data;
                
                // Group appointments by date
                const appointmentsByDate = {};
                result.data.forEach(appt => {
                    if (!appt.appointment_date) {
                        console.error('Invalid appointment data:', appt);
                        return;
                    }
                    const date = appt.appointment_date;
                    if (!appointmentsByDate[date]) {
                        appointmentsByDate[date] = [];
                    }
                    appointmentsByDate[date].push(appt);
                });

                // Create events for the calendar
                const events = Object.entries(appointmentsByDate).map(([date, appts]) => ({
                    start: date,
                    display: 'background',
                    backgroundColor: 'transparent',
                    extendedProps: {
                        count: appts.length,
                        appointments: appts
                    }
                }));

                calendar.removeAllEvents();
                calendar.addEventSource(events);
                
                // Update today's appointments
                loadTodaysApprovedAppointments();
            })
            .catch(error => {
                console.error('Error loading appointments:', error);
                document.getElementById('scheduledAppts').innerHTML = 
                    '<div style="color:#d00000;text-align:center;padding:20px;">Error loading appointments. Please try again later.</div>';
            });
    }

    function loadTodaysApprovedAppointments() {
        const today = formatDateLocal(new Date());
        const todays = approvedAppointments.filter(appt => appt.appointment_date === today);
        
        const scheduledAppts = document.getElementById('scheduledAppts');
        if (todays.length === 0) {
            scheduledAppts.innerHTML = '<div style="color:#666;text-align:center;padding:20px;">No approved appointments for today.</div>';
            return;
        }

        scheduledAppts.innerHTML = todays.map(appt => `
            <div class="appt-list-card" style="border-color: #00b300; color: #00b300; padding: 15px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 10px;">
                    <div>
                        <label style="color: #00b300; font-weight: bold;">USER:</label>
                        <span style="margin-left: 8px;">${appt.userName}</span>
                    </div>
                    <div>
                        <label style="color: #00b300; font-weight: bold;">TIME:</label>
                        <span style="margin-left: 8px;">${appt.appointment_time}</span>
                    </div>
                </div>
                <div>
                    <label style="color: #00b300; font-weight: bold;">PURPOSE:</label>
                    <span style="margin-left: 8px;">${appt.purpose}</span>
                </div>
            </div>
        `).join('');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            height: 600,
            dayMaxEvents: true,
            eventClick: function(info) {
                if (info.event.extendedProps.appointments) {
                    const dateStr = formatDateLocal(info.event.start);
                    showDayApptModal(info.event.extendedProps.appointments, dateStr);
                }
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.appointments) {
                    const appts = info.event.extendedProps.appointments;
                    const tooltipContent = appts.map(appt => 
                        `${appt.userName} - ${appt.appointment_time}\n${appt.purpose}`
                    ).join('\n\n');
                    info.el.title = tooltipContent;

                    // Add the badge
                    const badge = document.createElement('div');
                    badge.className = 'appointment-badge';
                    badge.textContent = info.event.extendedProps.count;
                    badge.onclick = function(e) {
                        e.stopPropagation();
                        const dateStr = formatDateLocal(info.event.start);
                        showDayApptModal(appts, dateStr);
                    };
                    info.el.appendChild(badge);

                    // Add the dot indicator
                    const dot = document.createElement('div');
                    dot.className = 'appointment-dot';
                    info.el.appendChild(dot);

                    // Add has-appointments class to the day cell
                    info.el.classList.add('has-appointments');
                }
            }
        });

        calendar.render();
        
        // Initial load
        loadAppointments();
        loadPendingCount();
        
        // Refresh data periodically
        setInterval(() => {
            loadAppointments();
            loadPendingCount();
        }, 30000); // Every 30 seconds
    });
    </script>
</body>
</html> 