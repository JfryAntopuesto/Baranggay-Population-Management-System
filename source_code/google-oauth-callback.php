<?php
session_start();
require_once "../config/google-oauth-config.php";
include "../database/database-connection.php";
include "../database/database-operations.php";

$db = new DatabaseOperations($conn);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Log the raw input for debugging
        $rawInput = file_get_contents('php://input');
        error_log("Google OAuth Raw Input: " . $rawInput);

        $data = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit();
        }

        if (isset($data['credential'])) {
            // Verify the JWT token using our custom function
            $payload = verifyGoogleToken($data['credential']);

            if ($payload) {
                $googleUser = $payload;

                $email = $googleUser['email'];
                $firstName = $googleUser['given_name'];
                $lastName = $googleUser['family_name'];
                $googleId = $googleUser['sub'];
                $profilePicture = $googleUser['picture'] ?? '';

                // Check if user already exists by email
                $existingUser = $db->getUserByEmail($email);

                if ($existingUser) {
                    // User exists, log them in
                    $_SESSION['username'] = $existingUser['username'];
                    $_SESSION['user_type'] = 'user';
                    $_SESSION['userID'] = $existingUser['userID'];
                    $_SESSION['firstname'] = $existingUser['firstname'];
                    $_SESSION['lastname'] = $existingUser['lastname'];
                    $_SESSION['middlename'] = $existingUser['middlename'];

                    // Check if user has a household
                    $userID = $existingUser['userID'];
                    $householdCheck = $conn->query("SELECT * FROM household WHERE userID = $userID");
                    if ($householdCheck && $householdCheck->num_rows > 0) {
                        echo json_encode(['success' => true, 'new_user' => false, 'user_type' => 'user']);
                    } else {
                        echo json_encode(['success' => true, 'new_user' => false, 'user_type' => 'user']);
                    }
                    exit();
                } else {
                    // New user, store Google data in session
                    $_SESSION['google_oauth_data'] = [
                        'email' => $email,
                        'firstname' => $firstName,
                        'lastname' => $lastName,
                        'google_id' => $googleId,
                        'profile_picture' => $profilePicture
                    ];

                    echo json_encode(['success' => true, 'new_user' => true]);
                    exit();
                }
            } else {
                error_log("Token verification failed");
                echo json_encode(['success' => false, 'message' => 'Invalid Google token']);
                exit();
            }
        } else {
            error_log("No credential in request data");
            echo json_encode(['success' => false, 'message' => 'No credential provided']);
            exit();
        }
    } else {
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Google OAuth Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An error occurred during Google authentication: ' . $e->getMessage()]);
    exit();
}
?>
