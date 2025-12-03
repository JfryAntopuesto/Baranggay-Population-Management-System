<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);
$puroks = $db->getAllPuroks();

// Get population count per purok
$purokPopulations = [];
foreach ($puroks as $purok) {
    $purokID = $purok['purokID'];
    $result = $conn->query("SELECT COUNT(*) as count FROM household WHERE purokID = $purokID");
    $row = $result->fetch_assoc();
    $purokPopulations[] = [
        'name' => $purok['purok_name'],
        'count' => (int)$row['count']
    ];
}

// Get staff count
$staffCount = $db->getStaffCount();
$purokPopulations[] = [
    'name' => 'Staff Members',
    'count' => (int)$staffCount
];

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Population Management System - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background-color: #f5f5f5;
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
            min-height: calc(100vh - 100px);
        }
        .left-panel, .middle-panel, .right-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            height: fit-content;
        }
        .left-panel { 
            width: 25%; 
            min-width: 250px;
        }
        .middle-panel { 
            width: 50%;
            flex-grow: 1;
        }
        .right-panel { 
            width: 25%;
            min-width: 280px;
        }
        /* Purok styles */
        .purok-card {
            background: #f8f9fa;
            box-shadow: 0 2px 10px rgba(0,51,204,0.06);
            border-radius: 12px;
            padding: 15px 18px;
            margin-bottom: 10px;
            color: #0033cc;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 2px solid #0033cc;
            transition: all 0.2s;
        }
        .purok-card:hover {
            background: #f0f2ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,51,204,0.1);
        }
        .purok-name {
            cursor: pointer;
            flex-grow: 1;
            padding-right: 10px;
        }
        .purok-name:hover {
            color: #0055ff;
        }
        .purok-actions {
            display: flex;
            gap: 12px;
        }
        .purok-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.3rem;
            padding: 0 4px;
            transition: color 0.2s;
        }
        .purok-actions .edit-btn { color: #0033cc; }
        .purok-actions .edit-btn:hover { color: #0055ff; }
        .purok-actions .delete-btn { color: #d00000; }
        .purok-actions .delete-btn:hover { color: #ff3333; }
        .add-btn {
            color: #0033cc;
            font-weight: 600;
            font-size: 1.1rem;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            align-self: flex-start;
            margin-top: 8px;
        }
        .add-btn:hover { color: #0055ff; text-decoration: underline; }
        /* Overview styles */
        .population-card {
            background: #f8f9fa;
            box-shadow: 0 2px 10px rgba(0,51,204,0.06);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 20px;
            color: #0033cc;
            font-size: 1.15rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .population-card em {
            margin-left: 10px;
            color: #6a7cff;
            font-style: italic;
            font-size: 1.08rem;
            font-weight: 400;
        }
        .charts-row {
            display: flex;
            gap: 25px;
            margin-top: 20px;
            width: 100%;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .chart-container {
            width: 100%;
            height: 600px;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,51,204,0.06);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .chart-circle {
            border: 2.5px solid #0033cc;
            border-radius: 50%;
            width: 200px;
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #0033cc;
            font-size: 1.1rem;
            text-align: center;
            background: #fff;
            position: relative;
            box-shadow: 0 6px 32px rgba(0,51,204,0.10);
            transition: box-shadow 0.2s, border-color 0.2s;
            margin-bottom: 10px;
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }
        .chart-circle:hover {
            box-shadow: 0 10px 40px rgba(0,51,204,0.18);
            border-color: #0055ff;
        }
        .chart-label {
            position: absolute;
            bottom: 18px;
            left: 0;
            width: 100%;
            text-align: center;
            color: #0033cc;
            font-weight: 600;
            font-size: 1.01rem;
            letter-spacing: 1px;
        }
        /* Profile styles */
        .profile-card {
            background: #f8f9fa;
            box-shadow: 0 2px 10px rgba(0,51,204,0.06);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 15px;
            color: #0033cc;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        .profile-card span {
            font-weight: 600;
            display: inline-block;
            min-width: 180px;
        }
        .profile-card em {
            color: #6a7cff;
            font-style: italic;
            font-weight: 400;
        }
        .edit-btn {
            margin-top: auto;
            color: #0033cc;
            font-weight: 600;
            font-size: 1.1rem;
            background: none;
            border: 2px solid #0033cc;
            border-radius: 8px;
            padding: 10px 38px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            align-self: flex-start;
        }
        .edit-btn:hover {
            background: #0033cc;
            color: #fff;
        }
        @media (max-width: 1200px) {
            .container {
                flex-direction: column;
                gap: 20px;
            }
            .left-panel, .middle-panel, .right-panel {
                width: 100%;
                min-width: 0;
            }
            .charts-row {
                justify-content: center;
            }
            .chart-container {
                height: 400px;
            }
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .chart-container {
                height: 300px;
            }
            .profile-card span {
                min-width: 150px;
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
        .alert {
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            min-width: 320px;
            max-width: 90vw;
            z-index: 2000;
            margin: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            font-size: 1.1rem;
            opacity: 1;
            transition: opacity 0.4s;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Custom Alert Styles */
        .custom-alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #0033cc;
            color: white;
            padding: 20px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
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
            min-width: 500px;
            max-width: 98vw;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            position: relative;
        }
        .modal-staff-header {
            background: #0033cc;
            color: #fff;
            padding: 28px 0 18px 0;
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
            padding: 48px 48px 32px 48px;
        }
        .modal-content-staff label {
            color: #0033cc;
            font-weight: bold;
            font-size: 1.15rem;
            display: block;
            margin-bottom: 0.2em;
        }
        .modal-content-staff input {
            width: 100%;
            padding: 12px;
            border: 2px solid #0033cc;
            border-radius: 8px;
            font-size: 1.1rem;
            margin-bottom: 24px;
            color: #0033cc;
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
            .modal {
                min-width: 98vw;
            }
            .modal-content-staff {
                padding: 24px 8vw 24px 8vw;
            }
        }
        .staff-link {
            color: #0033cc;
            text-decoration: none;
            font-weight: 500;
        }
        .staff-link:hover {
            text-decoration: underline;
        }
        .add-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            color: #666;
        }
        .add-btn:disabled:hover {
            color: #666;
            text-decoration: none;
        }
    </style>
    <script>
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };

        function showCustomAlert(message) {
            const alert = document.createElement('div');
            alert.className = 'custom-alert';
            alert.textContent = message;
            document.body.appendChild(alert);
            alert.style.display = 'block';

            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    document.body.removeChild(alert);
                }, 300);
            }, 1500);
        }

        function deletePurok(purokId) {
            showCustomAlert('Purok deleted successfully!');
            window.location.href = 'delete-purok.php?id=' + purokId;
        }
    </script>
</head>
<body>
    <header>
        BARANGAY POPULATION MANAGEMENT SYSTEM
        <button class="logout-btn" onclick="location.href='../logout.php'">LOGOUT</button>
    </header>
    <div class="container">
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success" id="topAlert">
            Operation completed successfully!
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('topAlert');
                if(alert) {
                    alert.style.opacity = 0;
                    setTimeout(function() {
                        alert.remove();
                    }, 400);
                }
            }, 2500);
        </script>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-error" id="topAlert">
            <?php 
            if ($_GET['error'] === 'username_exists') {
                echo 'Username already exists. Please choose a different username.';
            } else {
                echo 'An error occurred. Please try again.';
            }
            ?>
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('topAlert');
                if(alert) {
                    alert.style.opacity = 0;
                    setTimeout(function() {
                        alert.remove();
                    }, 400);
                }
            }, 2500);
        </script>
        <?php endif; ?>
        
        <div class="left-panel">
            <h3>PUROKS</h3>
            <?php 
            if (empty($puroks)): 
            ?>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 12px; border: 2px dashed #0033cc;">
                    <p style="color: #666; margin-bottom: 15px;">No puroks added yet</p>
                    <button class="add-btn" onclick="window.location.href='admin-purok-list.php'">ADD PUROK</button>
                </div>
            <?php else:
                $purokCount = 0;
                foreach($puroks as $purok): 
                    if($purokCount >= 2) break;
            ?>
                <div class="purok-card">
                    <div class="purok-name" onclick="window.location.href='admin-household-lists.php?purokID=<?php echo $purok['purokID']; ?>'">
                        <?php echo htmlspecialchars($purok['purok_name']); ?>
                        <div style="font-size: 0.8rem; color: #666; margin-top: 4px;">
                            Code: <?php echo htmlspecialchars($purok['purok_code']); ?>
                        </div>
                    </div>
                    <div class="purok-actions">
                        <button class="edit-btn" onclick="window.location.href='admin-purok-edit.php?id=<?php echo $purok['purokID']; ?>'" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-width="2" d="M16.475 5.408l2.117-2.116a1.5 1.5 0 1 1 2.122 2.12l-2.117 2.117m-2.122-2.12L4.5 17.5V21h3.5l11.97-11.97m-2.122-2.12 2.122 2.12"/>
                            </svg>
                        </button>
                        <button class="delete-btn" onclick="deletePurok(<?php echo $purok['purokID']; ?>)" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7h12z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php 
                $purokCount++;
                endforeach; 
            ?>
            <a href="admin-purok-list.php" class="view-all-btn" style="color: #0033cc; font-weight: 600; font-size: 1.1rem; text-decoration: none; display: inline-block; margin-top: 10px; transition: color 0.2s;">View All Puroks →</a>
            <?php endif; ?>

            <h3 style="margin-top: 30px;">STAFF MEMBERS</h3>
            <?php
            // Get all staff members
            $staff_sql = "SELECT m.modID, m.username, s.firstname, s.middlename, s.lastname, s.age, s.gender 
                         FROM moderators m 
                         JOIN staff_details s ON m.modID = s.modID 
                         WHERE m.role = 'staff'";
            $staff_result = $conn->query($staff_sql);
            while($staff = $staff_result->fetch_assoc()):
            ?>
            <div class="purok-card">
                <div style="display: flex; flex-direction: column; cursor: pointer;" onclick="window.location.href='view-staff.php?id=<?php echo $staff['modID']; ?>'">
                    <span style="font-weight: 600;"><?php echo htmlspecialchars($staff['username']); ?></span>
                    <span style="font-size: 0.9rem; color: #666;">
                        <?php echo htmlspecialchars($staff['firstname'] . ' ' . $staff['middlename'] . ' ' . $staff['lastname']); ?>
                    </span>
                </div>
                <div class="purok-actions">
                    <button class="delete-btn" onclick="event.stopPropagation(); deleteStaff(<?php echo $staff['modID']; ?>)" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7h12z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
            <button class="add-btn" onclick="showAddStaffModal()" <?php echo $staffCount >= 2 ? 'disabled' : ''; ?> style="<?php echo $staffCount >= 2 ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>">ADD STAFF</button>
        </div>
        <div class="middle-panel">
            <h3>ADMIN DASHBOARD</h3>
            <div class="population-card">
                POPULATION TOTAL: <em><?php echo $db->getTotalPopulation(); ?></em>
            </div>
            <div class="charts-row" style="margin-top: 40px;">
                <div class="chart-container" style="min-height: 400px;">
                    <canvas id="purokPopulationPieChart"></canvas>
                </div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 30px; width: 25%; min-width: 280px; height: 100%;">
            <div class="right-panel" style="flex: 1; display: flex; flex-direction: column;">
                <h3>PROFILE</h3>
                <div class="profile-card" style="flex: 1;">
                    <?php
                    $profile = $db->getBaranggayProfile();
                    if ($profile) {
                        echo "<span>BARANGGAY NAME:</span> <em>" . htmlspecialchars($profile['baranggay_name']) . "</em><br>";
                        echo "<span>CAPITAL:</span> <em>" . htmlspecialchars($profile['baranggay_capital']) . "</em><br>";
                        echo "<span>CITY:</span> <em>" . htmlspecialchars($profile['city']) . "</em><br>";
                        echo "<span>ARAW NG BARANGGAY:</span> <em>" . date('F j', strtotime($profile['araw_ng_barangay'])) . "</em><br>";
                        if (isset($profile['current_captain'])) {
                            echo "<span>BARANGGAY CAPTAIN:</span> <em>" . htmlspecialchars($profile['current_captain']) . "</em>";
                        }
                    } else {
                        echo "<p>No profile data available. Please set up the profile first.</p>";
                    }
                    ?>
                </div>
                <button class="edit-btn" onclick="window.location.href='admin-barangay-profile.php'">EDIT</button>
            </div>
            <div class="right-panel" style="flex: 1; display: flex; flex-direction: column;">
                <h3>QUICK ACCESS</h3>
                <div class="profile-card" style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
                    <button onclick="window.location.href='admin-appointments-dashboard.php'" style="background: #0033cc; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        VIEW APPOINTMENTS
                    </button>
                    <button onclick="window.location.href='admin-request.php'" style="background: #0033cc; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        VIEW REQUESTS/COMPLAINTS
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    let chartInstance = null;

    function updatePopulationTotal() {
        fetch('get-total-population.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const totalElement = document.querySelector('.population-card em');
                if (totalElement) {
                    totalElement.textContent = data.total || '0';
                }
            })
            .catch(error => {
                console.error('Error updating population total:', error);
                const totalElement = document.querySelector('.population-card em');
                if (totalElement) {
                    totalElement.textContent = 'Error loading data';
                }
            });
    }

    function updatePieChart() {
        console.log('Fetching population data...');
        fetch('get-purok-population.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Server error:', data.error);
                    document.querySelector('.chart-container').innerHTML = 
                        `<div style="color: #d00000; text-align: center; padding: 20px;">
                            Error: ${data.error}
                        </div>`;
                    return;
                }

                const ctx = document.getElementById('purokPopulationPieChart');
                if (!ctx) {
                    console.error('Chart canvas not found');
                    return;
                }

                // Format labels with percentages
                const labels = data.labels.map((label, i) => 
                    `${label} (${data.percentages[i]}%)`
                );

                if (chartInstance) {
                    chartInstance.data.labels = labels;
                    chartInstance.data.datasets[0].data = data.values;
                    chartInstance.update();
                } else {
                    chartInstance = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data.values,
                                backgroundColor: [
                                    '#0033cc', '#0066ff', '#6a7cff', '#00b894', '#fdcb6e', '#e17055', '#d63031', '#00bcd4',
                                    '#0984e3', '#00cec9', '#6c5ce7', '#a29bfe', '#ffeaa7', '#fab1a0', '#ff7675', '#74b9ff'
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 20,
                                    bottom: 20
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'right',
                                    align: 'center',
                                    labels: {
                                        font: {
                                            family: 'Poppins',
                                            size: 14
                                        },
                                        padding: 20,
                                        color: '#0033cc',
                                        boxWidth: 15,
                                        boxHeight: 15
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Population Distribution by Purok',
                                    font: {
                                        family: 'Poppins',
                                        size: 18,
                                        weight: 'bold'
                                    },
                                    color: '#0033cc',
                                    padding: {
                                        top: 20,
                                        bottom: 20
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const percentage = data.percentages[context.dataIndex];
                                            return `${label}: ${value} residents (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                document.querySelector('.chart-container').innerHTML = 
                    `<div style="color: #d00000; text-align: center; padding: 20px;">
                        Error loading chart: ${error.message}
                    </div>`;
            });
    }

    // Initialize chart when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initial update
        updatePieChart();
        updatePopulationTotal();
        
        // Update every 30 seconds instead of every second
        setInterval(() => {
            updatePieChart();
            updatePopulationTotal();
        }, 30000);
    });
    </script>

    <div class="modal-bg" id="addStaffModal" style="display: none;">
        <div class="modal">
            <div class="modal-staff-header">
                <span class="back" onclick="closeAddStaffModal()">&#8592;</span>
                <span>ADD NEW STAFF</span>
            </div>
            <div class="modal-content-staff" style="max-height: 70vh; overflow-y: auto;">
                <form id="addStaffForm" method="POST" action="add-staff.php" onsubmit="return validateAge()">
                    <label>FIRST NAME:</label>
                    <input type="text" name="firstname" required pattern="[A-Za-z\s]+" title="Please enter only letters and spaces">

                    <label>MIDDLE NAME:</label>
                    <input type="text" name="middlename" pattern="[A-Za-z\s]+" title="Please enter only letters and spaces">

                    <label>LAST NAME:</label>
                    <input type="text" name="lastname" required pattern="[A-Za-z\s]+" title="Please enter only letters and spaces">

                    <label>BIRTHDATE:</label>
                    <input type="date" name="birthdate" id="birthdate" required onchange="checkAge()">
                    <div id="ageError" style="color: #d00000; font-size: 0.9rem; margin-top: 5px; display: none;"></div>

                    <label>GENDER:</label>
                    <select name="gender" required style="background: #f8f9fa; border: 2px solid #0033cc; border-radius: 8px; padding: 10px; font-size: 1.05rem; color: #0033cc; margin-bottom: 18px;">
                        <option value="Male">&#9794; Male</option>
                        <option value="Female">&#9792; Female</option>
                        <option value="Other">&#9893; Other</option>
                    </select>

                    <label>USERNAME:</label>
                    <input type="text" name="username" required pattern="[A-Za-z0-9_]+" title="Please enter only letters, numbers, and underscores">

                    <label>PASSWORD:</label>
                    <input type="password" name="password" required>

                    <div class="modal-btn-row">
                        <button type="button" onclick="closeAddStaffModal()">CANCEL</button>
                        <button type="submit">ADD STAFF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddStaffModal() {
            const staffCount = <?php echo $staffCount; ?>;
            if (staffCount >= 2) {
                return; // Don't show modal if staff limit reached
            }
            document.getElementById('addStaffModal').style.display = 'flex';
        }

        function closeAddStaffModal() {
            document.getElementById('addStaffModal').style.display = 'none';
            // Reset form and error message when closing
            document.getElementById('addStaffForm').reset();
            document.getElementById('ageError').style.display = 'none';
        }

        function deleteStaff(modID) {
            if(confirm('Are you sure you want to delete this staff member?')) {
                window.location.href = 'delete-staff.php?id=' + modID;
            }
        }

        function calculateAge(birthdate) {
            const today = new Date();
            const birth = new Date(birthdate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        }

        function checkAge() {
            const birthdate = document.getElementById('birthdate').value;
            if (!birthdate) return;

            const age = calculateAge(birthdate);
            const ageError = document.getElementById('ageError');
            
            if (age < 18) {
                ageError.textContent = 'Staff must be at least 18 years old';
                ageError.style.display = 'block';
                return false;
            } else if (age > 65) {
                ageError.textContent = 'Staff must be 65 years old or younger';
                ageError.style.display = 'block';
                return false;
            } else {
                ageError.style.display = 'none';
                return true;
            }
        }

        function validateAge() {
            return checkAge();
        }
    </script>
</body>
</html> 
