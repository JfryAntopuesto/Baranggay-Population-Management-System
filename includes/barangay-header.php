<?php
/**
 * Barangay Header Helper
 * Provides consistent header text across the system
 */
require_once __DIR__ . '/../config/barangay-config.php';

/**
 * Get the system header text
 * @return string
 */
function getSystemHeader() {
    return BARANGAY_NAME . ' - Population Management System';
}

/**
 * Get the short header text (for page titles)
 * @return string
 */
function getSystemTitle() {
    return BARANGAY_NAME;
}
