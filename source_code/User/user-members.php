<?php
session_start();
require_once '../../database/database-connection.php';
require_once '../../database/database-operations.php';

if (!isset($_SESSION['userID'])) {
    die('User not logged in.');
}
$userID = $_SESSION['userID'];
$db = new DatabaseOperations($conn);
$householdID = $db->getHouseholdIDByUserID($userID);
if (!$householdID) {
    die('No household found for user.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $sex = $_POST['sex'];
    $birthdate = $_POST['birthdate']; // store as string (YYYY-MM-DD)
    $relationship = $_POST['relationship'];
    $db->insertHouseholdMember($householdID, $firstname, $middlename, $lastname, $sex, $birthdate, $relationship);
    header('Location: user-household.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Barangay Population Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        }

        header {
            position: absolute;
            top: 0;
            width: 100%;
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: #FFFFFF;
            padding: 20px 0;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 450px;
            margin-top: 80px;
            animation: slideIn 0.5s ease-out;
        }

        .form-container h2 {
            color: #0033cc;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }

        .form-container label {
            display: block;
            font-weight: 600;
            color: #0033cc;
            margin-bottom: 8px;
            font-size: 14px;
            text-align: left;
        }

        .form-container input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-container input:focus {
            border-color: #0033cc;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,51,204,0.1);
        }

        .form-container input::placeholder {
            color: #999;
            font-style: italic;
        }

        .form-container select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            appearance: none; /* Remove default dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: white;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%230033cc" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>'); /* Add custom arrow */
            background-repeat: no-repeat;
            background-position: right 10px top 50%;
            background-size: 16px auto;
        }

        .form-container select:focus {
            border-color: #0033cc;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,51,204,0.1);
        }

        .form-container button {
            width: 100%;
            padding: 14px;
            font-size: 18px;
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .form-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,51,204,0.2);
        }

        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .button-container button {
            flex: 1;
        }

        .cancel-button {
            background: linear-gradient(135deg, #cc0000, #ff0000) !important;
        }

        .cancel-button:hover {
            box-shadow: 0 4px 12px rgba(204,0,0,0.2) !important;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-container {
                width: 90%;
                padding: 30px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <header>BARANGAY POPULATION MANAGEMENT SYSTEM</header>

    <div class="form-container">
        <h2>Add Household Member</h2>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">First Name:</label>
                    <input type="text" id="firstname" name="firstname" placeholder="Enter first name" required>
                </div>
                <div class="form-group">
                    <label for="middlename">Middle Name:</label>
                    <input type="text" id="middlename" name="middlename" placeholder="Enter middle name">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" placeholder="Enter last name" required>
                </div>
                <div class="form-group">
                    <label for="sex">Sex:</label>
                    <select id="sex" name="sex" required>
                        <option value="">-- Select Sex --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="birthdate">Birthdate:</label>
                <input type="date" id="birthdate" name="birthdate" required>
            </div>
            <div class="form-group">
                <label for="relationship">Relationship to Head:</label>
                <select id="relationship" name="relationship" required>
                    <option value="">-- Select Relationship --</option>
                    <option value="Father">Father</option>
                    <option value="Mother">Mother</option>
                    <option value="Son">Son</option>
                    <option value="Daughter">Daughter</option>
                    <option value="Sibling">Sibling</option>
                    <option value="Grandchild">Grandchild</option>
                    <option value="Parent">Parent</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="button-container">
                <button type="submit">Add Member</button>
                <button type="button" class="cancel-button" onclick="window.location.href='user-household.php'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
