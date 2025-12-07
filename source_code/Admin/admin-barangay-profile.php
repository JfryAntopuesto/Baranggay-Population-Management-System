<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Population Management System - Edit Barangay Profile</title>
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
        }
        @keyframes headerDown {
            from { opacity: 0; transform: translateY(-40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        h3 {
            color: #0033cc;
            margin-bottom: 18px;
            font-size: 22px;
            border-bottom: 2px solid #0033cc;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            color: #0033cc;
            font-weight: 600;
            font-size: 16px;
            display: block;
            margin-bottom: 8px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #0033cc;
            border-radius: 8px;
            font-size: 16px;
            background: #fff;
            color: #0033cc;
            outline: none;
            transition: border 0.2s;
        }
        input:focus {
            border: 2px solid #0066ff;
        }
        input[readonly] {
            background-color: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .save-btn, .cancel-btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .save-btn {
            background: #0033cc;
            color: white;
            border: 2px solid #0033cc;
        }
        .save-btn:hover {
            background: #002299;
            border-color: #002299;
        }
        .cancel-btn {
            background: white;
            color: #0033cc;
            border: 2px solid #0033cc;
        }
        .cancel-btn:hover {
            background: #f5f5f5;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border: 1px solid #3c763d;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            border: 1px solid #a94442;
            margin-bottom: 20px;
        }
    </style>
</head>
<?php
session_start();
require_once '../../database/database-operations.php';
require_once '../../database/database-connection.php';
require_once '../../config/barangay-config.php';

$db = new DatabaseOperations($conn);

$profileExists = $db->checkBaranggayProfileExists();

// Fetch barangay profile if exists, otherwise use defaults
$profile = $profileExists ? $db->getBaranggayProfile() : null;
if (!$profile) {
    // Set default values from configuration
    $profile = [
        'baranggay_name' => BARANGAY_NAME,
        'city' => BARANGAY_CITY,
        'baranggay_capital' => '',
        'araw_ng_barangay' => '',
        'current_captain' => ''
    ];
}

// Ensure hard-coded values are always used
$profile['baranggay_name'] = BARANGAY_NAME;
$profile['city'] = BARANGAY_CITY;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $baranggay_capital = $_POST['baranggay_capital'];
    $araw_ng_barangay = $_POST['araw_ng_barangay'];
    $current_captain = $_POST['current_captain'];
    
    // Barangay name and city are hard-coded, not from form
    $baranggay_name = BARANGAY_NAME;
    $city = BARANGAY_CITY;

    if ($profileExists) {
        // Update existing profile
        if ($db->updateBaranggayProfile($id, $baranggay_name, $baranggay_capital, $city, $araw_ng_barangay, $current_captain)) {
            $_SESSION['success'] = true;
            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = "Failed to update profile";
        }
    } else {
        // Insert new profile
        if ($db->insertBaranggayProfile($baranggay_name, $baranggay_capital, $city, $araw_ng_barangay, $current_captain)) {
            $_SESSION['success'] = true;
            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = "Failed to create profile";
        }
    }
}
?>

<body>
    <header><?php echo BARANGAY_NAME; ?> - Population Management System</header>
    <div class="container">
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                Profile updated successfully!
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Add hidden ID field -->
            <input type="hidden" name="id" value="<?php echo isset($profile['id']) ? htmlspecialchars($profile['id']) : ''; ?>">
            <div class="form-group">
                <label>BARANGGAY NAME:</label>
                <input type="text" name="baranggay_name" 
                       value="<?php echo htmlspecialchars($profile['baranggay_name']); ?>" 
                       readonly 
                       title="Barangay name is fixed and cannot be changed">
                <small style="color: #666; font-size: 12px;">This field is fixed for this system.</small>
            </div>
            <div class="form-group">
                <label>CITY:</label>
                <input type="text" name="city" 
                       value="<?php echo htmlspecialchars($profile['city']); ?>" 
                       readonly 
                       title="City is fixed and cannot be changed">
                <small style="color: #666; font-size: 12px;">This field is fixed for this system.</small>
            </div>
            <div class="form-group">
                <label>PROVINCE:</label>
                <input type="text" value="<?php echo htmlspecialchars(BARANGAY_PROVINCE); ?>" readonly>
                <small style="color: #666; font-size: 12px;">This field is fixed for this system.</small>
            </div>
            <div class="form-group">
                <label>REGION:</label>
                <input type="text" value="<?php echo htmlspecialchars(BARANGAY_REGION); ?>" readonly>
                <small style="color: #666; font-size: 12px;">This field is fixed for this system.</small>
            </div>
            <div class="form-group">
                <label>BARANGGAY CAPITAL:</label>
                <input type="text" name="baranggay_capital" 
                       value="<?php echo isset($profile['baranggay_capital']) ? htmlspecialchars($profile['baranggay_capital']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>ARAW NG BARANGGAY:</label>
                <input type="date" name="araw_ng_barangay" 
                       value="<?php echo isset($profile['araw_ng_barangay']) ? htmlspecialchars($profile['araw_ng_barangay']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>CURRENT BARANGGAY CAPTAIN:</label>
                <input type="text" name="current_captain" 
                       value="<?php echo isset($profile['current_captain']) ? htmlspecialchars($profile['current_captain']) : ''; ?>" required>
            </div>
            <div class="button-group">
                <button type="submit" class="save-btn">SAVE</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='admin-dashboard.php'">CANCEL</button>
            </div>
        </form>
    </div>
</body>
</html>