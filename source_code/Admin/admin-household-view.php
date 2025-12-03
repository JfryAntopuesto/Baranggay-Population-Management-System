<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../database/database-connection.php';
require_once '../../database/database-operations.php';

if (!isset($_GET['householdID'])) {
    die('No household ID provided.');
}

$db = new DatabaseOperations($conn);
$householdID = $_GET['householdID'];

// Get household head details
$sql = "SELECT u.firstname, u.middlename, u.lastname 
        FROM household h 
        JOIN user u ON h.userID = u.userID 
        WHERE h.householdID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $householdID);
$stmt->execute();
$result = $stmt->get_result();
$householdHead = $result->fetch_assoc();
$userName = $householdHead['firstname'] . ' ' . $householdHead['middlename'] . ' ' . $householdHead['lastname'];

$members = $db->getHouseholdMembers($householdID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Household Details</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            background: #fff;
        }
        header {
            background: #0033cc;
            color: #fff;
            padding: 30px 0 20px 0;
            font-size: 28px;
            font-weight: bold;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .back-arrow {
            font-size: 32px;
            margin: 0 30px 0 30px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .back-arrow:hover {
            color: #ffd700;
        }
        .container {
            max-width: 90vw;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 30px;
        }
        .info {
            color: #0033cc;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .info span {
            font-style: italic;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1.5px solid #0033cc;
            padding: 12px 8px;
            text-align: center;
            font-size: 16px;
        }
        th {
            background: #f0f4ff;
            color: #0033cc;
            font-weight: bold;
            letter-spacing: 1px;
        }
        tr:nth-child(even) {
            background: #f8faff;
        }
        @media (max-width: 900px) {
            .container { padding: 10px; }
            th, td { font-size: 14px; padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <header>
        <span class="back-arrow" onclick="history.back()">&#8592;</span>
        <?php echo htmlspecialchars($userName); ?>
    </header>
    <div class="container">
        <div class="info">
            HOUSEHOLD ID: <span><?php echo htmlspecialchars($householdID); ?></span><br>
            TOTAL HOUSEHOLD MEMBERS: <span id="totalMembers"><?php echo count($members); ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>NAME</th>
                    <th>AGE</th>
                    <th>SEX</th>
                    <th>BIRTHDATE</th>
                    <th>RELATIONSHIP</th>
                </tr>
            </thead>
            <tbody id="membersTable">
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?php
                            $fullName = htmlspecialchars($member['firstname']);
                            if (!empty($member['middlename'])) {
                                $fullName .= ' ' . htmlspecialchars($member['middlename']);
                            }
                            $fullName .= ' ' . htmlspecialchars($member['lastname']);
                            echo $fullName;
                        ?></td>
                        <td><?php echo htmlspecialchars($member['age']); ?></td>
                        <td><?php echo htmlspecialchars($member['sex']); ?></td>
                        <td><?php echo htmlspecialchars($member['birthdate']); ?></td>
                        <td><?php echo htmlspecialchars($member['relationship']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 