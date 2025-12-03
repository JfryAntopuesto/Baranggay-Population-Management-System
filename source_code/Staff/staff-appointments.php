<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff - Manage Appointments</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; margin: 0; }
        .appt-header { background: #0033cc; color: #fff; padding: 24px 0 16px 0; border-radius: 0 0 32px 32px; font-size: 1.3rem; font-weight: bold; letter-spacing: 1px; }
        .appt-header .back { font-size: 1.5rem; margin-left: 24px; vertical-align: middle; cursor: pointer; }
        .appt-header span { margin-left: 12px; }
        .appt-board { display: flex; justify-content: space-between; margin: 32px auto 0 auto; max-width: 1200px; }
        .appt-col { flex: 1; margin: 0 16px; }
        .appt-col-header { text-align: center; font-size: 1.2rem; font-weight: bold; margin-bottom: 18px; }
        .pending { color: #e6b800; }
        .approved { color: #00b300; }
        .declined { color: #d00000; }
        .appt-card { 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 20px; 
            background: #fff; 
            border: 2px solid #0033cc; 
            color: #0033cc; 
            font-size: 1.08rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .appt-card.approved { 
            border-color: #00b300; 
            color: #333;
            background: #f0fff0;
        }
        .appt-card.declined { 
            border-color: #d00000; 
            color: #333;
            background: #fff0f0;
        }
        .appt-card.pending { 
            border-color: #0033cc; 
            color: #0033cc;
            background: #f0f4ff;
            cursor: pointer; 
            transition: all 0.3s ease;
        }
        .appt-card.pending:hover { 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .appt-card label { 
            color: inherit; 
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
            margin-right: 8px;
        }
        .appt-card span { 
            color: inherit;
            font-style: italic;
            opacity: 0.9;
        }
        .appt-card.approved label { 
            color: #00b300; 
        }
        .appt-card.declined label { 
            color: #d00000; 
        }
        .appt-card textarea { 
            width: 100%; 
            border-radius: 8px; 
            border: 1px solid currentColor; 
            margin: 8px 0; 
            padding: 12px; 
            font-size: 1rem; 
            color: inherit;
            background: rgba(255,255,255,0.8);
            resize: vertical;
            min-height: 80px;
        }
        .appt-card.approved textarea { 
            border-color: #00b300; 
            color: #00b300; 
        }
        .appt-card.declined textarea { 
            border-color: #d00000; 
            color: #d00000; 
        }
        .appt-card button { 
            margin-right: 10px; 
            border-radius: 8px; 
            border: none; 
            padding: 10px 20px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .approve { 
            background: #00b300; 
            color: #fff;
        }
        .approve:hover {
            background: #008000;
            box-shadow: 0 2px 8px rgba(0,179,0,0.3);
        }
        .decline { 
            background: #d00000; 
            color: #fff;
        }
        .decline:hover {
            background: #b00000;
            box-shadow: 0 2px 8px rgba(208,0,0,0.3);
        }
        .delete { 
            background: #888; 
            color: #fff;
        }
        .delete:hover {
            background: #666;
            box-shadow: 0 2px 8px rgba(136,136,136,0.3);
        }
        /* Modal Styles */
        .modal-bg {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.25);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-staff-header {
            background: #0033cc;
            color: #fff;
            padding: 28px 0 18px 0;
            border-radius: 0 0 32px 32px;
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
        .modal-staff-header span { margin-left: 0; }
        .modal {
            background: #fff;
            border-radius: 14px;
            padding: 0 0 0 0;
            min-width: 900px;
            max-width: 98vw;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            position: relative;
            margin-top: 40px;
        }
        .modal-content-staff {
            padding: 48px 48px 32px 48px;
        }
        .modal-content-staff label {
            color: #0033cc;
            font-weight: bold;
            font-size: 1.15rem;
            display: inline-block;
            margin-bottom: 0.2em;
        }
        .modal-content-staff span {
            font-style: italic;
            color: #6c6cff;
            font-size: 1.08rem;
        }
        .modal-content-staff textarea {
            width: 100%;
            min-height: 120px;
            border-radius: 12px;
            border: 2px solid #0033cc;
            margin: 8px 0 32px 0;
            padding: 16px;
            font-size: 1.15rem;
            color: #6c6cff;
            font-style: italic;
            background: #fff;
            resize: vertical;
        }
        .modal-content-staff .modal-btn-row {
            display: flex;
            justify-content: flex-end;
            gap: 18px;
        }
        .modal-content-staff button {
            border: 2px solid #0033cc;
            background: #fff;
            color: #0033cc;
            font-weight: bold;
            font-size: 1.15rem;
            border-radius: 8px;
            padding: 10px 36px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .modal-content-staff button:hover {
            background: #0033cc;
            color: #fff;
        }
        @media (max-width: 1000px) {
            .modal { min-width: 98vw; }
            .modal-content-staff { padding: 24px 8vw 24px 8vw; }
        }
        @media (max-width: 900px) {
            .appt-board { flex-direction: column; }
            .appt-col { margin: 0 0 32px 0; }
        }
    </style>
</head>
<body>
    <div class="appt-header">
        <span class="back" onclick="window.location.href='staff-appointments-dashboard.php'">&#8592;</span>
        <span>APPOINTMENTS</span>
    </div>
    <div class="appt-board">
        <div class="appt-col">
            <div class="appt-col-header pending">PENDING</div>
            <div id="pendingCol"></div>
        </div>
        <div class="appt-col">
            <div class="appt-col-header approved">APPROVED</div>
            <div id="approvedCol"></div>
        </div>
        <div class="appt-col">
            <div class="appt-col-header declined">DECLINED</div>
            <div id="declinedCol"></div>
        </div>
    </div>
    <div class="modal-bg" id="apptModalBg" style="display:none;">
        <div class="modal">
            <div class="modal-staff-header">
                <span class="back" onclick="closeApptModal()">&#8592;</span>
                <span>APPOINTMENT DETAILS</span>
            </div>
            <div id="apptModalContent" class="modal-content-staff"></div>
        </div>
    </div>
    <script>
        let currentAppt = null;
        function renderStaffAppointments() {
            // Fetch and display pending appointments
            fetch('appointments.php?status=PENDING')
                .then(response => response.json())
                .then(result => {
                    const pendingCol = document.getElementById('pendingCol');
                    pendingCol.innerHTML = '';
                    if (!result.success) {
                        pendingCol.innerHTML = '<div style="color:red;">Failed to load appointments</div>';
                        return;
                    }
                    result.data.forEach((appt, idx) => {
                        let card = document.createElement('div');
                        card.className = 'appt-card pending';
                        card.innerHTML = `
                            <label>APPOINTMENT ID:</label> <span style="font-style:italic;">${appt.appointmentID}</span><br>
                            <label>USER:</label> <span style="font-style:italic;">${appt.userName}</span><br>
                            <label>DATE:</label> <span style="font-style:italic;">${appt.appointment_date}</span><br>
                            <label>TIME:</label> <span style="font-style:italic;">${appt.appointment_time}</span><br>
                            <label>PURPOSE:</label> <span style="font-style:italic;">${appt.purpose}</span><br>
                            <label>CREATED:</label> <span style="font-style:italic;">${new Date(appt.created_at).toLocaleString()}</span><br>
                            ${appt.staff_comment ? `<label>STAFF COMMENT:</label> <span style="font-style:italic;">${appt.staff_comment}</span><br>` : ''}
                        `;
                        card.onclick = () => openApptModal(appt);
                        pendingCol.appendChild(card);
                    });
                    if (result.data.length === 0) {
                        pendingCol.innerHTML = '<div>No pending appointments.</div>';
                    }
                });

            // Fetch and display approved appointments
            fetch('appointments.php?status=APPROVED')
                .then(response => response.json())
                .then(result => {
                    const approvedCol = document.getElementById('approvedCol');
                    approvedCol.innerHTML = '';
                    if (!result.success) {
                        approvedCol.innerHTML = '<div style="color:red;">Failed to load appointments</div>';
                        return;
                    }
                    result.data.forEach(appt => {
                        let card = document.createElement('div');
                        card.className = 'appt-card approved';
                        card.innerHTML = `
                            <label>APPOINTMENT ID:</label> <span style="font-style:italic;">${appt.appointmentID}</span><br>
                            <label>USER:</label> <span style="font-style:italic;">${appt.userName}</span><br>
                            <label>DATE:</label> <span style="font-style:italic;">${appt.appointment_date}</span><br>
                            <label>TIME:</label> <span style="font-style:italic;">${appt.appointment_time}</span><br>
                            <label>PURPOSE:</label> <span style="font-style:italic;">${appt.purpose}</span><br>
                            <label>CREATED:</label> <span style="font-style:italic;">${new Date(appt.created_at).toLocaleString()}</span><br>
                            ${appt.staff_comment ? `<label>STAFF COMMENT:</label> <span style="font-style:italic;">${appt.staff_comment}</span><br>` : ''}
                        `;
                        approvedCol.appendChild(card);
                    });
                    if (result.data.length === 0) {
                        approvedCol.innerHTML = '<div>No approved appointments.</div>';
                    }
                });

            // Fetch and display declined appointments
            fetch('appointments.php?status=DECLINED')
                .then(response => response.json())
                .then(result => {
                    const declinedCol = document.getElementById('declinedCol');
                    declinedCol.innerHTML = '';
                    if (!result.success) {
                        declinedCol.innerHTML = '<div style="color:red;">Failed to load appointments</div>';
                        return;
                    }
                    result.data.forEach(appt => {
                        let card = document.createElement('div');
                        card.className = 'appt-card declined';
                        card.innerHTML = `
                            <label>APPOINTMENT ID:</label> <span style="font-style:italic;">${appt.appointmentID}</span><br>
                            <label>USER:</label> <span style="font-style:italic;">${appt.userName}</span><br>
                            <label>DATE:</label> <span style="font-style:italic;">${appt.appointment_date}</span><br>
                            <label>TIME:</label> <span style="font-style:italic;">${appt.appointment_time}</span><br>
                            <label>PURPOSE:</label> <span style="font-style:italic;">${appt.purpose}</span><br>
                            <label>CREATED:</label> <span style="font-style:italic;">${new Date(appt.created_at).toLocaleString()}</span><br>
                            ${appt.staff_comment ? `<label>STAFF COMMENT:</label> <span style="font-style:italic;">${appt.staff_comment}</span><br>` : ''}
                        `;
                        declinedCol.appendChild(card);
                    });
                    if (result.data.length === 0) {
                        declinedCol.innerHTML = '<div>No declined appointments.</div>';
                    }
                });
        }

        function openApptModal(appt) {
            currentAppt = appt;
            const modalBg = document.getElementById('apptModalBg');
            const modalContent = document.getElementById('apptModalContent');
            modalContent.innerHTML = `
                <div style="margin-bottom: 18px;">
                    <label>APPOINTMENT ID:</label> <span>${appt.appointmentID}</span><br>
                    <label>USER ID:</label> <span>${appt.userID}</span><br>
                    <label>DATE:</label> <span>${appt.appointment_date}</span><br>
                    <label>TIME:</label> <span>${appt.appointment_time}</span><br>
                    <label>PURPOSE:</label> <span>${appt.purpose}</span><br>
                    <label>CREATED:</label> <span>${new Date(appt.created_at).toLocaleString()}</span><br>
                    <label style="margin-top:18px;">STAFF COMMENT:</label><br>
                    <textarea id="apptModalComment" placeholder="Type your message..."></textarea>
                </div>
                <div class="modal-btn-row">
                    <button onclick="updateApptStatus('APPROVED')">APPROVE</button>
                    <button onclick="updateApptStatus('DECLINED')">DECLINE</button>
                </div>
            `;
            modalBg.style.display = 'flex';
        }

        function closeApptModal() {
            document.getElementById('apptModalBg').style.display = 'none';
            currentAppt = null;
        }

        function updateApptStatus(status) {
            if (!currentAppt) return;
            const staffComment = document.getElementById('apptModalComment').value;
            if (!staffComment.trim()) {
                alert('Please provide a comment before approving or declining the appointment.');
                return;
            }

            // If approving, first check for existing appointments at the same date and time
            if (status === 'APPROVED') {
                fetch('check-appointment-conflict.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        appointment_date: currentAppt.appointment_date,
                        appointment_time: currentAppt.appointment_time,
                        appointment_id: currentAppt.appointmentID // Exclude current appointment from check
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.hasConflict) {
                        alert('Cannot approve this appointment. There is already an approved appointment scheduled for this date and time.');
                        return;
                    }
                    // If no conflict, proceed with approval
                    proceedWithStatusUpdate(status, staffComment);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while checking for conflicts. Please try again.');
                });
            } else {
                // For declining, proceed directly
                proceedWithStatusUpdate(status, staffComment);
            }
        }

        function proceedWithStatusUpdate(status, staffComment) {
            fetch('update-appointment-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    appointment_id: currentAppt.appointmentID,
                    status: status,
                    staff_comment: staffComment
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    closeApptModal();
                    renderStaffAppointments();
                } else {
                    alert('Failed to update appointment status: ' + (result.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the appointment. Please try again.');
            });
        }

        // Refresh appointments every 30 seconds
        window.onload = function() {
            renderStaffAppointments();
            setInterval(renderStaffAppointments, 30000);
        };
    </script>
</body>
</html> 