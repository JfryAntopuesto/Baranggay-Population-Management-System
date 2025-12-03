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

// Get user details
$sql = "SELECT firstname, lastname, middlename FROM user WHERE userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$userName = $user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname'];

$members = $db->getHouseholdMembers($householdID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Household ID</title>
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
        .add-btn {
            color: #0033cc;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            display: inline-block;
            transition: color 0.2s;
        }
        .add-btn:hover {
            color: #0066ff;
            text-decoration: underline;
        }
        @media (max-width: 900px) {
            .container { padding: 10px; }
            th, td { font-size: 14px; padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <header>
        <span class="back-arrow" onclick="window.location.href='user-dashboard.php'">&#8592;</span>
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
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="membersTable">
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['middlename'] . ' ' . $member['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($member['age']); ?></td>
                        <td><?php echo htmlspecialchars($member['sex']); ?></td>
                        <td><?php echo htmlspecialchars($member['birthdate']); ?></td>
                        <td><?php echo htmlspecialchars($member['relationship']); ?></td>
                        <td>
                            <a href="member-action.php?edit=1&memberID=<?php echo urlencode($member['memberID']); ?>" style="color: #0033cc; font-weight: bold; margin-right: 10px; text-decoration: none;">Update</a>
                            <form method="post" action="member-action.php" style="display:inline;">
                                <input type="hidden" name="memberID" value="<?php echo htmlspecialchars($member['memberID']); ?>">
                                <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this member?');" style="color: #cc0000; background: none; border: none; font-weight: bold; cursor: pointer;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <span class="add-btn" onclick="window.location.href='user-members.php'">ADD+</span>
    </div>
    
</body>
</html> 