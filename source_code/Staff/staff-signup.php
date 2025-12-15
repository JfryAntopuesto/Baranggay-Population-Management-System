<?php
include "../database/database-connection.php";
include "../database/database-operations.php";
include "../person.php"; // Include the Person class

$db = new DatabaseOperations($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $person = new staff(); // Create an instance of the staff class

    // Set the input values
    $person->setModID($_POST['modID']);
    $person->setUsername($_POST['username']);
    $person->setPassword($_POST['password']);
    $confirmPassword = $_POST['confirmPassword'];

    // Check if passwords match
    if ($person->getPassword() !== $confirmPassword) {
        $error_message = "Passwords do not match!";
    } else {
        // Check if username already exists
        if ($db->checkUsernameExists($person->getUsername())) {
            $error_message = "Username already exists!";
        } else {
            // Insert new staff using getters
            if ($db->insertStaff(
                $person->getModID(),
                $person->getUsername(),
                $person->getPassword(),
                $person->getRole() // Role is automatically determined by modID
            )) {
                // Redirect to login page on successful registration
                header("Location: login.php");
                exit();
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barangay Population Management System - Staff Sign Up</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        }

        header {
            position: absolute;
            top: 0;
            width: 100%;
            background: linear-gradient(135deg, #006633, #009933);
            color: #FFFFFF;
            padding: 20px 0;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 60px;
            margin-top: 80px;
            padding: 20px;
        }

        .welcome {
            font-size: 64px;
            font-weight: bold;
            color: #006633;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            animation: fadeIn 1s ease-in;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 400px;
            animation: slideIn 1s ease-out;
        }

        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #006633;
            font-size: 14px;
        }

        .form-container .helper-text {
            font-size: 12px;
            color: #666;
            margin-top: -15px;
            margin-bottom: 15px;
            font-style: italic;
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
            border-color: #006633;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,102,51,0.1);
        }

        .form-container input::placeholder {
            color: #999;
            font-style: italic;
        }

        .form-container button {
            width: 100%;
            padding: 14px;
            font-size: 18px;
            background: linear-gradient(135deg, #006633, #009933);
            color: #FFFFFF;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .form-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,102,51,0.2);
        }

        .form-container .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 15px;
        }

        .form-container .login-link a {
            text-decoration: none;
            color: #006633;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-container .login-link a:hover {
            color: #009933;
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateX(-20px);
            }
            to { 
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                gap: 30px;
            }
            .welcome {
                font-size: 48px;
                text-align: center;
            }
            .form-container {
                width: 90%;
                max-width: 400px;
            }
        }
    </style>
</head>
<body>
    <header>BARANGAY DON MARTIN MARUNDAN POPULATION MANAGEMENT SYSTEM - STAFF REGISTRATION</header>

    <div class="container">
        <h1 class="welcome">STAFF<br>SIGNUP</h1>

        <div class="form-container">
            <?php if (isset($error_message)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label>MOD ID:</label>
                <input type="text" id="modID" name="modID" placeholder="Enter your Moderator ID" required>

                <label>USERNAME:</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required>

                <label>PASSWORD:</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required>

                <label>CONFIRM PASSWORD:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>

                <button type="submit">REGISTER AS STAFF</button>
                <div class="login-link">Already have an account? <a href="login.php">LOGIN</a></div>
            </form>
        </div>
    </div>
</body>
</html> 