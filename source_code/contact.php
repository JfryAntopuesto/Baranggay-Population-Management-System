<!DOCTYPE html>
<html>
<head>
    <title>Barangay Population Management System - Contact Us</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            min-height: 100vh;
        }

        header {
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: #FFFFFF;
            padding: 20px 0;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .contact-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            display: flex;
            gap: 40px;
            animation: fadeIn 0.5s ease-out;
        }

        .contact-info {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .contact-info h2 {
            color: #0033cc;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .contact-details {
            margin-bottom: 30px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .contact-item:hover {
            transform: translateX(10px);
            background: #f0f4ff;
        }

        .contact-item i {
            font-size: 24px;
            color: #0033cc;
            margin-right: 15px;
        }

        .contact-item div {
            flex: 1;
        }

        .contact-item h3 {
            color: #0033cc;
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .contact-item p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }

        .office-hours {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .office-hours h3 {
            color: #0033cc;
            margin-bottom: 15px;
        }

        .office-hours p {
            color: #666;
            margin: 5px 0;
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
            .contact-container {
                flex-direction: column;
                padding: 15px;
            }
            .contact-info {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        BARANGAY POPULATION MANAGEMENT SYSTEM
    </header>

    <div class="contact-container">
        <div class="contact-info">
            <h2>Contact Information</h2>
            
            <div class="contact-details">
                <div class="contact-item">
                    <i>📍</i>
                    <div>
                        <h3>Address</h3>
                        <p>Barangay Dahican, Maritnez, Mati City, Davao Oriental 
                    </div>
                </div>

                <div class="contact-item">
                    <i>📞</i>
                    <div>
                        <h3>Phone Number</h3>
                        <p>+63 123 456 7890</p>
                    </div>
                </div>

                <div class="contact-item">
                    <i>✉️</i>
                    <div>
                        <h3>Email</h3>
                        <p>barangay.martines@gmail.com
                    </div>
                </div>

                <div class="contact-item">
                    <i>💬</i>
                    <div>
                        <h3>Social Media</h3>
                        <p>Facebook: Barangay Matinez </p>
                    </div>
                </div>
            </div>

            <div class="office-hours">
                <h3>Office Hours</h3>
                <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
                <p>Saturday: 8:00 AM - 12:00 PM</p>
                <p>Sunday: Closed</p>
            </div>
        </div>
    </div>

    <button class="back-button" onclick="location.href='welcome.php'">Back to Home</button>
</body>
</html> 