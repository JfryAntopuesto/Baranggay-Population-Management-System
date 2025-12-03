<?php
session_start();
include "../database/database-connection.php";
include "../database/database-operations.php";
$db = new DatabaseOperations($conn);

if (!isset($_SESSION['temp_username'])) {
    header("Location: signup.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    $username = $_SESSION['temp_username'];
    $userID = $db->getUserIDByUsername($username);
    
    if ($userID) {
        $uploadDir = "uploads/profile-pictures/";
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $file = $_FILES['profile_picture'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png');
        
        if (in_array($fileExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 5000000) { // 5MB max
                    $fileNameNew = $userID . "_" . uniqid() . "." . $fileExt;
                    $fileDestination = $uploadDir . $fileNameNew;
                    
                    if (move_uploaded_file($fileTmpName, $fileDestination)) {
                        // Store only the relative path for web access
                        $relativePath = 'uploads/profile-pictures/' . $fileNameNew;
                        $db->insertProfilePicture($userID, $relativePath);
                        unset($_SESSION['temp_username']);
                        $_SESSION['userID'] = $userID;
                        header("Location: User/user-dashboard.php");
                        exit();
                    }
                } else {
                    $error_message = "File size is too large. Maximum size is 5MB.";
                }
            } else {
                $error_message = "Error uploading file.";
            }
        } else {
            $error_message = "Invalid file type. Only JPG, JPEG, and PNG files are allowed.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Profile Picture</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        
        button:hover {
            background: #45a049;
        }
        
        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Profile Picture</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_picture">Choose Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png" required>
            </div>
            <button type="submit">Upload Profile Picture</button>
        </form>
    </div>
</body>
</html>
