<?php
session_start();
require_once '../../database/database-connection.php';
require_once '../../database/database-operations.php';

$db = new DatabaseOperations($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete']) && isset($_POST['memberID'])) {
        // Delete member
        $memberID = intval($_POST['memberID']);
        $stmt = $conn->prepare('DELETE FROM members WHERE memberID = ?');
        $stmt->bind_param('i', $memberID);
        $stmt->execute();
        header('Location: user-household.php');
        exit();
    }
    if (isset($_POST['update']) && isset($_POST['memberID'])) {
        // Update member
        $memberID = intval($_POST['memberID']);
        $firstname = $_POST['firstname'];
        $middlename = $_POST['middlename'];
        $lastname = $_POST['lastname'];
        $sex = $_POST['sex'];
        $birthdate = $_POST['birthdate'];
        $relationship = $_POST['relationship'];
        $stmt = $conn->prepare('UPDATE members SET firstname=?, middlename=?, lastname=?, sex=?, birthdate=?, relationship=? WHERE memberID=?');
        $stmt->bind_param('ssssssi', $firstname, $middlename, $lastname, $sex, $birthdate, $relationship, $memberID);
        $stmt->execute();
        header('Location: user-household.php');
        exit();
    }
}

// If GET, show update form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit']) && isset($_GET['memberID'])) {
    $memberID = intval($_GET['memberID']);
    $stmt = $conn->prepare('SELECT * FROM members WHERE memberID = ?');
    $stmt->bind_param('i', $memberID);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    if (!$member) {
        die('Member not found.');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Edit Member</title>
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
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                background-color: white;
                background-image: url('data:image/svg+xml;utf8,<svg fill="%230033cc" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
                background-repeat: no-repeat;
                background-position: right 10px top 50%;
                background-size: 16px auto;
                cursor: pointer;
            }
            .form-container select:focus {
                border-color: #0033cc;
                outline: none;
                box-shadow: 0 0 0 3px rgba(0,51,204,0.1);
            }
            .form-container select option {
                padding: 12px;
                font-size: 16px;
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
        <header>BARANGAY DON MARTIN MARUNDAN POPULATION MANAGEMENT SYSTEM</header>
        <div class="form-container">
            <h2>Edit Household Member</h2>
            <form method="post">
                <input type="hidden" name="memberID" value="<?php echo htmlspecialchars($member['memberID']); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First Name:</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($member['firstname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="middlename">Middle Name:</label>
                        <input type="text" id="middlename" name="middlename" value="<?php echo htmlspecialchars($member['middlename']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($member['lastname']); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="sex">Sex:</label>
                        <select id="sex" name="sex" required>
                            <option value="Male" <?php echo $member['sex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $member['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="birthdate">Birthdate:</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($member['birthdate']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="relationship">Relationship to Head:</label>
                    <select id="relationship" name="relationship" required>
                        <option value="Father" <?php echo $member['relationship'] === 'Father' ? 'selected' : ''; ?>>Father</option>
                        <option value="Mother" <?php echo $member['relationship'] === 'Mother' ? 'selected' : ''; ?>>Mother</option>
                        <option value="Son" <?php echo $member['relationship'] === 'Son' ? 'selected' : ''; ?>>Son</option>
                        <option value="Daughter" <?php echo $member['relationship'] === 'Daughter' ? 'selected' : ''; ?>>Daughter</option>
                        <option value="Sibling" <?php echo $member['relationship'] === 'Sibling' ? 'selected' : ''; ?>>Sibling</option>
                        <option value="Grandchild" <?php echo $member['relationship'] === 'Grandchild' ? 'selected' : ''; ?>>Grandchild</option>
                        <option value="Parent" <?php echo $member['relationship'] === 'Parent' ? 'selected' : ''; ?>>Parent</option>
                        <option value="Other" <?php echo $member['relationship'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="button-container">
                    <button type="submit" name="update">Update Member</button>
                    <button type="button" class="cancel-button" onclick="window.location.href='user-household.php'">Cancel</button>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}
