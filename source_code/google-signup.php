<?php
session_start();
include "../database/database-connection.php";
include "../database/database-operations.php";

$db = new DatabaseOperations($conn);

// Check if user has Google OAuth data
if (!isset($_SESSION['google_oauth_data'])) {
    error_log("Google signup: No session data found. Redirecting to login.");
    // Show error message before redirect
    $_SESSION['google_signup_error'] = 'Please sign in with Google first to complete your registration.';
    header("Location: login.php");
    exit();
}

$googleData = $_SESSION['google_oauth_data'];

// Validate that we have required Google data
if (empty($googleData['email']) || empty($googleData['firstname']) || empty($googleData['lastname'])) {
    error_log("Google signup: Incomplete session data. Redirecting to login.");
    $_SESSION['google_signup_error'] = 'Invalid Google sign-in data. Please try signing in with Google again.';
    unset($_SESSION['google_oauth_data']);
    header("Location: login.php");
    exit();
}

// Debug: Log session data
error_log("Google signup: Session data found for email: " . ($googleData['email'] ?? 'unknown'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $googleData['firstname'];
    $lastname = $googleData['lastname'];
    $middlename = $_POST['middlename'] ?? '';
    $birthdate = $_POST['birthdate'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $email = $googleData['email'];
    $email_notifications = isset($_POST['email_notifications']) ? true : false;

    // Check if passwords match
    if ($password !== $confirmPassword) {
        $error_message = "Passwords do not match!";
    } else {
        // Check if username already exists
        if ($db->checkUsernameExists($username)) {
            $error_message = "Username already exists!";
        } else {
            // Insert new user
            if ($db->insertUser($firstname, $lastname, $middlename, $birthdate, $username, $password, $email, $email_notifications)) {
                // Save profile picture if available
                if (!empty($googleData['profile_picture'])) {
                    $userID = $db->getUserIDByUsername($username);
                    if ($userID) {
                        // Download and save Google profile picture
                        $imageData = file_get_contents($googleData['profile_picture']);
                        if ($imageData) {
                            $uploadDir = "uploads/profile-pictures/";
                            $filename = $userID . "_" . time() . ".jpg";
                            $filePath = $uploadDir . $filename;

                            if (file_put_contents($filePath, $imageData)) {
                                $db->insertProfilePicture($userID, $filePath);
                            }
                        }
                    }
                }

                // Clear session data
                unset($_SESSION['google_oauth_data']);

                // Log the user in
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = 'user';
                $_SESSION['userID'] = $db->getUserIDByUsername($username);
                $_SESSION['firstname'] = $firstname;
                $_SESSION['lastname'] = $lastname;
                $_SESSION['middlename'] = $middlename;

                // Check if user has a household
                $userID = $_SESSION['userID'];
                $householdCheck = $conn->query("SELECT * FROM household WHERE userID = $userID");
                if ($householdCheck && $householdCheck->num_rows > 0) {
                    header("Location: User/user-dashboard.php");
                } else {
                    header("Location: User/user-purok.php");
                }
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
    <title>Barangay Population Management System - Complete Sign Up</title>
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
            gap: 60px;
            margin-top: 80px;
            padding: 20px;
        }

        .welcome {
            font-size: 64px;
            font-weight: bold;
            color: #0033cc;
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

        .profile-section {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .profile-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: block;
            border: 3px solid #0033cc;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 600;
            color: #0033cc;
            margin-bottom: 5px;
        }

        .profile-email {
            color: #666;
            font-size: 14px;
        }

        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #0033cc;
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

        .password-container {
            position: relative;
            margin-bottom: 20px;
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

        .form-container input[type="password"] {
            padding-right: 35px;
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

        .form-container button {
            width: 100%;
            padding: 14px;
            font-size: 18px;
            background: linear-gradient(135deg, #0033cc, #0066ff);
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
            box-shadow: 0 4px 12px rgba(0,51,204,0.2);
        }

        .form-container .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 15px;
        }

        .form-container .login-link a {
            text-decoration: none;
            color: #0033cc;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-container .login-link a:hover {
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

        .error-message {
            color: red;
            font-size: 12px;
            margin-top: -15px;
            margin-bottom: 15px;
            display: none;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>BARANGAY POPULATION MANAGEMENT SYSTEM</header>

    <div class="container">
        <h1 class="welcome">WELCOME!</h1>

        <div class="form-container">
            <div class="profile-section">
                <?php if (!empty($googleData['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($googleData['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                <?php endif; ?>
                <div class="profile-name"><?php echo htmlspecialchars($googleData['firstname'] . ' ' . $googleData['lastname']); ?></div>
                <div class="profile-email"><?php echo htmlspecialchars($googleData['email']); ?></div>
            </div>

            <form method="POST" action="" id="googleSignupForm" onsubmit="return validateForm()">
                <label>MIDDLENAME (Optional):</label>
                <input type="text" id="middlename" name="middlename" placeholder="Enter your middlename (if applicable)">
                <div id="middlenameError" class="error-message">Middlename should only contain letters and spaces</div>

                <label>BIRTHDATE:</label>
                <input type="date" id="birthdate" name="birthdate" required>
                <div id="birthdateError" class="error-message">You must be at least 18 years old to register</div>

                <label>USERNAME:</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required>
                <div id="usernameError" class="error-message">Username should be 4-20 characters long and can only contain letters, numbers, and underscores</div>

                <label>PASSWORD:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <span class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <div id="passwordError" class="error-message">Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number</div>

                <label>CONFIRM PASSWORD:</label>
                <div class="password-container">
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                    <span class="password-toggle" onclick="togglePassword('confirmPassword')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <div id="confirmPasswordError" class="error-message">Passwords do not match</div>

                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="email_notifications" name="email_notifications" value="1" style="width: auto; margin-right: 8px; margin-bottom: 0;">
                        <span style="font-weight: normal; color: #333;">Allow this system to send an email</span>
                    </label>
                    <div style="font-size: 12px; color: #666; margin-top: 5px; margin-left: 24px;">
                        You will receive email notifications when there are changes to your requests, complaints, and appointments.
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                    <div style="color: red; margin-bottom: 15px; text-align: center;"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <button type="submit">COMPLETE SIGNUP</button>
                <div class="login-link">Already have an account? <a href="login.php">LOGIN</a></div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
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

        function validateForm() {
            let isValid = true;

            // Middlename validation (optional, but if provided, letters and spaces only)
            const middlename = document.getElementById('middlename');
            const middlenameError = document.getElementById('middlenameError');
            const nameRegex = /^[A-Za-z\s]*$/;
            if (middlename.value && !nameRegex.test(middlename.value)) {
                middlenameError.style.display = 'block';
                isValid = false;
            } else {
                middlenameError.style.display = 'none';
            }

            // Username validation (4-20 chars, letters, numbers, underscores)
            const username = document.getElementById('username');
            const usernameError = document.getElementById('usernameError');
            const usernameRegex = /^[a-zA-Z0-9_]{4,20}$/;
            if (!usernameRegex.test(username.value)) {
                usernameError.style.display = 'block';
                isValid = false;
            } else {
                usernameError.style.display = 'none';
            }

            // Password validation
            const password = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;
            if (!passwordRegex.test(password.value)) {
                passwordError.style.display = 'block';
                isValid = false;
            } else {
                passwordError.style.display = 'none';
            }

            // Confirm password validation
            const confirmPassword = document.getElementById('confirmPassword');
            const confirmPasswordError = document.getElementById('confirmPasswordError');
            if (password.value !== confirmPassword.value) {
                confirmPasswordError.style.display = 'block';
                isValid = false;
            } else {
                confirmPasswordError.style.display = 'none';
            }

            // Birthdate validation (must be at least 18 years old)
            const birthdate = document.getElementById('birthdate');
            const birthdateError = document.getElementById('birthdateError');
            const today = new Date();
            const birthDate = new Date(birthdate.value);
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 18) {
                birthdateError.style.display = 'block';
                isValid = false;
            } else {
                birthdateError.style.display = 'none';
            }

            return isValid;
        }

        // Add input event listeners for real-time validation
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', validateForm);
        });
    </script>
</body>
</html>
