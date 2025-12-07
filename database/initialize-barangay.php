<?php
/**
 * Database Initialization Script
 * Initializes the barangay profile and seeds allowed puroks
 * 
 * Run this script once after converting to single-barangay system
 * Usage: php database/initialize-barangay.php
 */

require_once __DIR__ . '/database-connection.php';
require_once __DIR__ . '/database-operations.php';
require_once __DIR__ . '/../config/barangay-config.php';

echo "Initializing Barangay Don Martin Marundan System...\n\n";

$db = new DatabaseOperations($conn);

// 1. Initialize/Update Barangay Profile
echo "1. Setting up barangay profile...\n";
$profileExists = $db->checkBaranggayProfileExists();

if ($profileExists) {
    $profile = $db->getBaranggayProfile();
    if ($profile) {
        // Update existing profile with hard-coded values
        $result = $db->updateBaranggayProfile(
            $profile['id'],
            BARANGAY_NAME,
            $profile['baranggay_capital'] ?? '',
            BARANGAY_CITY,
            $profile['araw_ng_barangay'] ?? date('Y-m-d'),
            $profile['current_captain'] ?? ''
        );
        if ($result) {
            echo "   ✓ Barangay profile updated.\n";
        } else {
            echo "   ✗ Failed to update barangay profile.\n";
        }
    }
} else {
    // Insert new profile
    $result = $db->insertBaranggayProfile(
        BARANGAY_NAME,
        '',
        BARANGAY_CITY,
        date('Y-m-d'),
        ''
    );
    if ($result) {
        echo "   ✓ Barangay profile created.\n";
    } else {
        echo "   ✗ Failed to create barangay profile.\n";
    }
}

// 2. Seed Allowed Puroks (only if they don't exist)
echo "\n2. Seeding allowed puroks...\n";
$allowedPuroks = getAllowedPuroks();
$seededCount = 0;
$skippedCount = 0;

foreach ($allowedPuroks as $purokName) {
    if ($db->purokNameExists($purokName)) {
        echo "   - Skipping '{$purokName}' (already exists)\n";
        $skippedCount++;
        continue;
    }
    
    try {
        // Generate a default date (2025-01-01) and empty president
        $result = $db->insertPurok($purokName, '2025-01-01', '');
        if ($result) {
            echo "   ✓ Created purok: {$purokName}\n";
            $seededCount++;
        } else {
            echo "   ✗ Failed to create purok: {$purokName}\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error creating purok '{$purokName}': " . $e->getMessage() . "\n";
    }
}

echo "\n3. Summary:\n";
echo "   - Barangay profile: " . ($profileExists ? "Updated" : "Created") . "\n";
echo "   - Puroks seeded: {$seededCount}\n";
echo "   - Puroks skipped: {$skippedCount}\n";
echo "   - Total allowed puroks: " . count($allowedPuroks) . "\n";

echo "\n✓ Initialization complete!\n";
echo "\nNote: You may need to update purok presidents and dates manually through the admin panel.\n";
