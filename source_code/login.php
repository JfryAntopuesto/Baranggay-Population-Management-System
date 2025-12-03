<?php
session_start();
include "../database/database-connection.php";
include "../database/database-operations.php";

$db = new DatabaseOperations($conn);

// If user is already logged in, redirect to appropriate dashboard
if(isset($_SESSION['user_type'])) {
    switch($_SESSION['user_type']) {
        case 'admin':
            header("Location: Admin/admin-dashboard.php");
            break;
        case 'staff':
            header("Location: Staff/staff-dashboard.php");
            break;
        case 'user':
            header("Location: User/user-dashboard.php");
            break;
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // First check moderator table
    $moderator = $db->checkModeratorLogin($username, $password);
    if ($moderator) {
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = $moderator['role'];
        
        error_log("Moderator logged in. Session user_type: " . $_SESSION['user_type'] . ", Role from DB: " . $moderator['role']);

        if ($moderator['role'] == 'admin') {
            // Check if admin dashboard has predefined profile
            $_SESSION['userID'] = $moderator['modID'];
            $predefinedProfile = $db->checkBaranggayProfileExists();
            
            if ($predefinedProfile) {
                // If predefined profile exists, go directly to dashboard
                header("Location: Admin/admin-dashboard.php");
            } else {
                // Check if baranggay profile exists
                $profileExists = $db->checkBaranggayProfileExists();
                
                if (!$profileExists) {
                    // If no profile exists, go to profile setup
                    header("Location: Admin/admin-barangay-profile.php?first_login=1");
                } else {
                    // If profile exists, go to dashboard
                    header("Location: Admin/admin-dashboard.php");
                }
            }
            exit();
        } else if ($moderator['role'] == 'staff') {
            header("Location: Staff/staff-dashboard.php");
            exit();
        }
    } else {
        // Check user table if not found in moderator
        $user = $db->checkUserLogin($username, $password);
        if ($user) {
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = 'user';
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['middlename'] = $user['middlename'];
            // Check if user has a household
            $userID = $user['userID'];
            $householdCheck = $conn->query("SELECT * FROM household WHERE userID = $userID");
            if ($householdCheck && $householdCheck->num_rows > 0) {
                header("Location: User/user-dashboard.php");
            } else {
                header("Location: User/user-purok.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barangay Population Management System - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
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

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 90%;
            max-width: 1000px;
            gap: 60px;
            margin-top: 80px;
            padding: 20px;
        }

        .welcome {
            flex: 1;
            font-size: 64px;
            font-weight: bold;
            color: #0033cc;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            animation: fadeIn 1s ease-in;
        }

        .form-container {
            flex: 1;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            animation: slideIn 1s ease-out;
            max-width: 400px;
        }

        .form-container label {
            display: block;
            font-weight: 600;
            color: #0033cc;
            margin-bottom: 8px;
            font-size: 14px;
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

        .password-container {
            position: relative;
            margin-bottom: 20px;
        }

        .password-container input {
            margin-bottom: 0;
            padding-right: 35px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 14px;
            cursor: pointer;
            z-index: 1;
        }

        .password-toggle:hover {
            color: #0033cc;
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
            margin-bottom: 20px;
        }

        .form-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,51,204,0.2);
        }

        .form-container .signup-link {
            text-align: center;
            color: #666;
            font-size: 15px;
        }

        .form-container .signup-link a {
            text-decoration: none;
            color: #0033cc;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-container .signup-link a:hover {
            color: #0066ff;
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateX(20px);
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
                width: 100%;
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <header>BARANGAY POPULATION MANAGEMENT SYSTEM</header>

    <div class="container">
        <div class="welcome">WELCOME!</div>

        <div class="form-container">
            <?php if (isset($error_message)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="username">USERNAME:</label>
                <input id="username" type="text" name="username" placeholder="Enter your username" required>

                <label for="password">PASSWORD:</label>
                <div class="password-container">
                    <input id="password" type="password" name="password" placeholder="Enter your password" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <button type="submit">LOGIN</button>
            </form>

            <div class="signup-link">Don't have an account? <a href="signup.php">SIGNUP</a></div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
