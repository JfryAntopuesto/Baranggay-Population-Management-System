<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);

if (!isset($_GET['id'])) {
    header("Location: admin-dashboard.php");
    exit();
}

$staff = $db->getStaffDetails($_GET['id']);
if (!$staff) {
    header("Location: admin-dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Details - Barangay Population Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .back-btn {
            color: #0033cc;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
        .staff-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .info-value {
            color: #0033cc;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .full-width {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Staff Details</h2>
            <a href="admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
        
        <div class="staff-info">
            <div class="info-group">
                <div class="info-label">Username</div>
                <div class="info-value"><?php echo htmlspecialchars($staff['username']); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Role</div>
                <div class="info-value"><?php echo htmlspecialchars($staff['role']); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">First Name</div>
                <div class="info-value"><?php echo htmlspecialchars($staff['firstname']); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Middle Name</div>
                <div class="info-value"><?php echo htmlspecialchars($staff['middlename']); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Last Name</div>
                <div class="info-value"><?php echo htmlspecialchars($staff['lastname']); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Birthdate</div>
                <div class="info-value"><?php echo date('F d, Y', strtotime($staff['birthdate'])); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Age</div>
                <div class="info-value"><?php echo htmlspecialchars($staff['age']); ?> years old</div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Gender</div>
                <div class="info-value"><?php echo htmlspecialchars($staff['gender']); ?></div>
            </div>
        </div>
    </div>
</body>
</html> 