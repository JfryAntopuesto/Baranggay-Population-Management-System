<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);
// Puroks will be loaded dynamically from API via JavaScript
$error_message = '';

function generateUniqueHouseholdID($conn) {
    do {
        $id = rand(100000, 999999); // 6-digit random number
        $result = $conn->query("SELECT 1 FROM household WHERE householdID = $id");
    } while ($result && $result->num_rows > 0);
    return $id;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_purok = $_POST['purokID'];
    $entered_purok_code = $_POST['purokCode'];
    $userID = $_SESSION['userID'];
    
    // Verify the entered purok code
    $verified_purok = $db->verifyPurokCode($entered_purok_code);

    if ($verified_purok && $verified_purok['purokID'] == $selected_purok) {
        // Code is valid and matches the selected purok, proceed with household insertion
        $householdID = generateUniqueHouseholdID($conn);

        // Insert new household
        $stmt = $conn->prepare("INSERT INTO household (householdID, purokID, userID) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $householdID, $selected_purok, $userID);
        if ($stmt->execute()) {
            echo "<script>";
            echo "localStorage.setItem('purok_update', Date.now());";
            echo "setTimeout(function() {";
            echo "    window.location.href='user-dashboard.php';";
            echo "}, 300);";
            echo "</script>";
            exit();
        } else {
            $error_message = "Failed to assign household. Please try again.";
        }
    } else {
        // Invalid purok code or code does not match the selected purok
        $error_message = "Invalid Purok Code or the code does not match the selected Purok.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purok Selection - Barangay Population Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        header {
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: white;
            text-align: center;
            padding: 32px 0 24px 0;
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 2px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            width: 100vw;
        }
        .center-card {
            margin: 48px auto 0 auto;
            max-width: 500px;
            background: #fff;
            border: 2.5px solid #2342f5;
            border-radius: 32px;
            box-shadow: 0 4px 32px rgba(35,66,245,0.08);
            padding: 40px 40px 30px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        form {
            width: 100%;
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
        select {
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
        select:focus {
            border: 2px solid #0033cc;
        }
        .btn-done {
            margin: 32px auto 0 auto;
            padding: 10px 38px;
            background: #fff;
            color: #2342f5;
            border: 2px solid #2342f5;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            display: block;
        }
        .btn-done:hover {
            background: #2342f5;
            color: #fff;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
    </style>
</head>
<body>
    <header>BARANGAY POPULATION MANAGEMENT SYSTEM</header>
    <div class="center-card">
        <h2>Select Your Purok</h2>
        <?php if($error_message): ?>
            <div class="message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="POST" action="" id="purokForm">
            <div>
                <label for="purokID">Choose your purok:</label>
                <select id="purokID" name="purokID" required>
                    <option value="">-- Loading Puroks... --</option>
                </select>
            </div>
            <div>
                <label for="purokCode">Enter Purok Code:</label>
                <input type="text" id="purokCode" name="purokCode" required placeholder="Enter the code provided by the admin" style="width: 100%; padding: 12px 10px; border: 2px solid #2342f5; border-radius: 8px; font-size: 1.1rem; color: #2342f5; background: #fff; outline: none; transition: border 0.2s;">
            </div>
            <button type="submit" class="btn-done">Submit</button>
        </form>
    </div>

    <!-- Include the Purok API utility -->
    <script src="../js/purok-api.js"></script>
    
    <script>
        // Load puroks from API when page loads
        document.addEventListener('DOMContentLoaded', async function() {
            const selectElement = document.getElementById('purokID');
            
            try {
                // Show loading state
                selectElement.innerHTML = '<option value="">Loading puroks...</option>';
                
                // Fetch puroks from API
                const puroks = await PurokAPI.getAllPuroks();
                
                // Populate select dropdown
                selectElement.innerHTML = '<option value="">-- Select Purok --</option>';
                puroks.forEach(purok => {
                    const option = document.createElement('option');
                    option.value = purok.purokID;
                    option.textContent = purok.purok_name;
                    selectElement.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading puroks:', error);
                selectElement.innerHTML = '<option value="">Error loading puroks. Please refresh the page.</option>';
                
                // Show error message to user
                const form = document.getElementById('purokForm');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'message';
                errorDiv.textContent = 'Failed to load puroks. Please refresh the page.';
                form.insertBefore(errorDiv, form.firstChild);
            }
        });

        // Optional: Verify purok code using API before form submission
        document.getElementById('purokForm').addEventListener('submit', async function(e) {
            const purokID = document.getElementById('purokID').value;
            const purokCode = document.getElementById('purokCode').value;
            
            if (!purokID || !purokCode) {
                return; // Let HTML5 validation handle this
            }
            
            // Optional: Pre-verify using API (client-side validation)
            // The server-side validation will still be the final check
            try {
                const verification = await PurokAPI.verifyPurokCode(purokCode);
                if (!verification.valid || verification.data.purokID != purokID) {
                    e.preventDefault();
                    alert('Invalid Purok Code or the code does not match the selected Purok.');
                    return false;
                }
            } catch (error) {
                // If API verification fails, still allow form submission
                // Server-side validation will handle it
                console.warn('API verification failed, proceeding with form submission:', error);
            }
        });
    </script>
</body>
</html>
