<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);
$error_message = '';
$success_message = '';

// Check if editing existing purok
$purok = null;
if(isset($_GET['id'])) {
    $purok = $db->getPurokById($_GET['id']);
    if(!$purok) {
        header("Location: admin-dashboard.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $purok_name = $_POST['purok_name'];
    $araw = $_POST['araw_purok'];
    $purok_pres = $_POST['purok_president'];

    try {
        if(isset($_POST['purokID'])) {
            // Update existing purok
            if($db->updatePurok($_POST['purokID'], $purok_name, $araw, $purok_pres)) {
                header("Location: admin-purok-list.php?success=1");
                exit();
            } else {
                $error_message = "Failed to update purok.";
            }
        } else {
            // Insert new purok
            if($db->insertPurok($purok_name, $araw, $purok_pres)) {
                header("Location: admin-purok-list.php?success=1");
                exit();
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($purok) ? 'Edit' : 'Add'; ?> Purok - Barangay Population Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background-color: #fff;
            min-height: 100vh;
        }
        header {
            background: #2342f5;
            color: white;
            text-align: center;
            padding: 32px 0 24px 0;
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 2px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .center-card {
            margin: 48px auto 0 auto;
            max-width: 900px;
            min-width: 340px;
            background: #fff;
            border: 2.5px solid #2342f5;
            border-radius: 32px;
            box-shadow: 0 4px 32px rgba(35,66,245,0.08);
            padding: 60px 60px 40px 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        form {
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }
        label {
            color: #2342f5;
            font-weight: 600;
            font-size: 1.15rem;
            margin-bottom: 6px;
        }
        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 12px 10px;
            border: 2px solid #2342f5;
            border-radius: 8px;
            font-size: 1.1rem;
            color: #2342f5;
            background: #fff;
            outline: none;
            transition: border 0.2s;
        }
        input[type="text"]:focus,
        input[type="date"]:focus {
            border: 2px solid #0033cc;
        }
        .btn-done {
            margin: 32px auto 0 auto;
            padding: 12px 40px; /* Increased padding for a better look */
            background: #2342f5; /* Changed background to match the theme */
            color: #fff; /* Changed text color to white for better contrast */
            border: none; /* Removed border for a cleaner look */
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s; /* Added transform for a subtle effect */
            display: block;
        }
        
        .btn-done:hover {
            background: #0033cc;
            transform: translateY(-2px);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center; /* Center buttons horizontally */
            margin-top: 32px;
            width: 100%; /* Keep full width to allow centering within the container */
            align-items: center;
        }
        
        .btn-done, .btn-cancel {
             width: 120px;
             text-align: center; /* Ensure text is centered */
             margin: 0;
             display: inline-block;
        }

        .btn-cancel {
            padding: 12px 20px; /* Adjusted horizontal padding */
            background: #fff;
            color: #d00000;
            border: 2px solid #d00000;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background 0.3s, color 0.3s, transform 0.2s; /* Added transform for a subtle effect */
        }

        .btn-cancel:hover {
            background: #d00000; /* Change background on hover */
            color: #fff; /* Change text color on hover */
            transform: translateY(-2px); /* Slight lift effect on hover */
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        @media (max-width: 700px) {
            .center-card {
                padding: 24px 8vw 24px 8vw;
                min-width: 0;
            }
            form {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>BARANGAY POPULATION MANAGEMENT SYSTEM</header>
    <div class="center-card">
        <?php if($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <?php if(isset($purok)): ?>
                <input type="hidden" name="purokID" value="<?php echo $purok['purokID']; ?>">
            <?php endif; ?>
            <div>
                <label for="purok_name">PUROK NAME:</label>
                <input type="text" id="purok_name" name="purok_name" required 
                    pattern="[A-Za-z0-9\s]+" 
                    title="Purok name can only contain letters, numbers, and spaces"
                    value="<?php echo isset($purok) ? htmlspecialchars($purok['purok_name']) : ''; ?>">
            </div>
            <div>
                <label for="araw_purok">ARAW NG PUROK:</label>
                <input type="date" id="araw_purok" name="araw_purok" required 
                    min="2025-01-01"
                    value="<?php echo isset($purok) ? htmlspecialchars($purok['araw']) : ''; ?>">
            </div>
            <div>
                <label for="purok_president">CURRENT PUROK PRESIDENT:</label>
                <input type="text" id="purok_president" name="purok_president" required 
                    pattern="[A-Za-z\s]+" 
                    title="Please enter a valid name (letters and spaces only)"
                    oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').replace(/\b\w/g, l => l.toUpperCase())"
                    value="<?php echo isset($purok) ? htmlspecialchars($purok['purok_pres']) : ''; ?>">
            </div>
            <div class="button-group">
                <button type="submit" class="btn-done"><?php echo isset($purok) ? 'UPDATE' : 'ADD'; ?></button>
                <button type="button" class="btn-cancel" onclick="window.location.href='admin-purok-list.php'">CANCEL</button>
            </div>
        </form>
    </div>
</body>
</html> 