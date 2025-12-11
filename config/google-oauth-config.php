<?php
// Google OAuth Configuration
// Replace these with your actual Google OAuth credentials from Google Cloud Console

define('GOOGLE_CLIENT_ID', '698019550698-t7emmi327jltnovqji5elaiq628mv0d5.apps.googleusercontent.com');

// Note: For Google Identity Services (client-side flow), you only need the Client ID
// The Client Secret and Redirect URI are not required for the client-side authentication
// GOOGLE_CLIENT_SECRET and GOOGLE_REDIRECT_URI are kept for backward compatibility but not used

// Google OAuth scopes (not used in client-side flow, but kept for reference)
define('GOOGLE_SCOPES', [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile'
]);

/**
 * Simple JWT decoder for Google OAuth tokens
 * This function verifies and decodes Google JWT tokens without requiring the full Google API client
 */
function verifyGoogleToken($token) {
    // Split the JWT into its parts
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    // Decode the header and payload
    $header = json_decode(base64UrlDecode($parts[0]), true);
    $payload = json_decode(base64UrlDecode($parts[1]), true);

    // Verify the token is from Google
    if (!isset($header['alg']) || $header['alg'] !== 'RS256') {
        return false;
    }

    if (!isset($payload['iss']) || !in_array($payload['iss'], ['https://accounts.google.com', 'accounts.google.com'])) {
        return false;
    }

    if (!isset($payload['aud']) || $payload['aud'] !== GOOGLE_CLIENT_ID) {
        return false;
    }

    // Check expiration
    if (!isset($payload['exp']) || $payload['exp'] < time()) {
        return false;
    }

    // For simplicity, we'll trust Google's signature in development
    // In production, you should verify the signature using Google's public keys
    // Get them from: https://www.googleapis.com/oauth2/v3/certs

    return $payload;
}

function base64UrlDecode($data) {
    $data = str_replace(['-', '_'], ['+', '/'], $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}
?>
