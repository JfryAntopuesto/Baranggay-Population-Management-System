<?php
/**
 * Barangay Configuration
 * Single-barangay system configuration for Barangay Don Martin Marundan
 */

// Barangay Information
define('BARANGAY_NAME', 'Barangay Don Martin Marundan');
define('BARANGAY_CITY', 'City of Mati');
define('BARANGAY_PROVINCE', 'Davao Oriental');
define('BARANGAY_REGION', 'Davao Region (Region XI)');
define('BARANGAY_COUNTRY', 'Philippines');

// Allowed Puroks - Only these puroks are permitted in the system
define('ALLOWED_PUROKS', [
    'Tamia 1',
    'Tamia 2',
    'Marba',
    'Magay',
    '8-7',
    'Centro 1',
    'Centro 2',
    'Centro 3',
    'Caguisocan',
    'Bagnan',
    'Doña Concepcion',
    'Tulay',
    'Dalaguit',
    'Cuanas'
]);

/**
 * Get allowed puroks list
 * @return array
 */
function getAllowedPuroks() {
    return ALLOWED_PUROKS;
}

/**
 * Check if a purok name is allowed
 * @param string $purokName
 * @return bool
 */
function isPurokAllowed($purokName) {
    $normalized = normalizePurokName($purokName);
    $allowed = array_map('normalizePurokName', ALLOWED_PUROKS);
    return in_array($normalized, $allowed);
}

/**
 * Normalize purok name for comparison (case-insensitive, trim whitespace)
 * @param string $name
 * @return string
 */
function normalizePurokName($name) {
    return trim($name);
}

/**
 * Get barangay full address
 * @return string
 */
function getBarangayFullAddress() {
    return BARANGAY_NAME . ', ' . BARANGAY_CITY . ', ' . BARANGAY_PROVINCE . ', ' . BARANGAY_REGION . ', ' . BARANGAY_COUNTRY;
}
