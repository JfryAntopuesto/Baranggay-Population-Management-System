<?php
session_start();
error_log("Welcome.php started at " . date('Y-m-d H:i:s'));

require_once '../database/database-connection.php';
error_log("Database connection loaded");

require_once '../database/database-operations.php';
error_log("Database operations loaded");

require_once '../includes/functions.php';
error_log("Functions loaded");

require_once '../includes/cleanup-handler.php';
error_log("Cleanup handler loaded");

// Initialize database connection
$db = new DatabaseOperations($conn);
error_log("Database operations initialized");

// Initialize cleanup handler
$cleanupHandler = new CleanupHandler($db);
error_log("Cleanup handler initialized");

// Check and run cleanup if needed
error_log("About to run cleanup check");
$cleanupHandler->checkAndRunCleanup();
error_log("Cleanup check completed");
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
            text-align: center;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            min-height: 100vh;
            position: relative;
        }

        header {
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: #FFFFFF;
            padding: 20px 0;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .main-content {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome {
            margin-top: 60px;
            font-size: 72px;
            font-weight: bold;
            color: #0033cc;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            animation: fadeIn 1s ease-in;
        }

        .welcome-subtitle {
            font-size: 24px;
            color: #666;
            margin-top: 20px;
            margin-bottom: 40px;
            animation: fadeIn 1.2s ease-in;
        }

        .get-started {
            margin-top: 40px;
            animation: fadeIn 1.4s ease-in;
        }

        .get-started button {
            font-size: 20px;
            padding: 15px 40px;
            border: none;
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: #FFFFFF;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,51,204,0.2);
        }

        .get-started button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,51,204,0.3);
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 60px;
            padding: 0 20px;
            animation: fadeIn 1.6s ease-in;
        }

        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 250px;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card h3 {
            color: #0033cc;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: white;
            padding: 15px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        footer a {
            text-decoration: none;
            color: #0033cc;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }

        footer a:hover {
            background: #f0f4ff;
            color: #0066ff;
        }

        @keyframes fadeIn {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .welcome {
                font-size: 48px;
            }
            .welcome-subtitle {
                font-size: 20px;
            }
            .features {
                flex-direction: column;
                align-items: center;
            }
            .feature-card {
                width: 90%;
                max-width: 300px;
            }
            .footer-links {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        BARANGAY POPULATION MANAGEMENT SYSTEM
    </header>

    <div class="main-content">
        <h1 class="welcome">WELCOME!</h1>
        <p class="welcome-subtitle">Your trusted platform for efficient barangay population management</p>

        <div class="features">
            <div class="feature-card">
                <h3>Easy Registration</h3>
                <p>Simple and quick process to register your household information</p>
            </div>
            <div class="feature-card">
                <h3>Secure Data</h3>
                <p>Your information is protected with advanced security measures</p>
            </div>
            <div class="feature-card">
                <h3>Real-time Updates</h3>
                <p>Stay informed with instant updates and notifications</p>
            </div>
        </div>

        <div class="get-started">
            <button type="button" onclick="location.href='login.php';">GET STARTED</button>
        </div>
    </div>

    <footer>
        <div class="footer-links">
            <a href="aboutus.php">ABOUT</a>
            <a href="contact.php">CONTACT</a>
            <a href="login.php">LOGIN</a>
        </div>
    </footer>
</body>
</html>
