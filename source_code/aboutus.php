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
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, #1a237e, #0000FF);
            color: #FFFFFF;
            padding: 25px 0;
            font-size: 28px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #fff, transparent);
        }

        .welcome {
            margin: 60px auto;
            font-size: 42px;
            color: #1a237e;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            max-width: 800px;
            padding: 0 20px;
            animation: fadeIn 1.5s ease-in;
            position: relative;
        }

        .welcome::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #1a237e, #0000FF);
            margin: 20px auto;
            border-radius: 2px;
        }

        .para {
            margin: 40px auto;
            font-size: 20px;
            color: #333;
            text-align: center;
            max-width: 900px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            line-height: 1.8;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .para:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 50px auto;
            flex-wrap: wrap;
        }

        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            width: 250px;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card h3 {
            color: #1a237e;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #666;
            font-size: 16px;
        }

         .back-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,51,204,0.2);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,51,204,0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .welcome {
                font-size: 32px;
            }
            .para {
                font-size: 18px;
                padding: 20px;
            }
            .features {
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        ABOUT US
    </header>

    <div class="container">
        <h2 class="welcome">Welcome to the Barangay Population Management System</h2>

        <h3 class="para">Our mission is to provide a secure and efficient platform for managing population records within the barangay. This system simplifies the process of recording, updating, and monitoring population data, ensuring accurate and up-to-date information for better community management. We are committed to promoting transparency, improving administrative efficiency, and supporting data-driven decision-making for barangay officials and residents.</h3>

        <div class="features">
            <div class="feature-card">
                <h3>Secure Records</h3>
                <p>Advanced security measures to protect sensitive population data</p>
            </div>
            <div class="feature-card">
                <h3>Easy Access</h3>
                <p>User-friendly interface for quick and efficient data management</p>
            </div>
            <div class="feature-card">
                <h3>Real-time Updates</h3>
                <p>Instant updates and modifications to population records</p>
            </div>
        </div>
    </div>
     <button class="back-button" onclick="location.href='welcome.php'">Back to Home</button>
</body>
</html>
