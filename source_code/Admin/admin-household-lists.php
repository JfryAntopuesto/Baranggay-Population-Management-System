<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if purokID is provided
if (!isset($_GET['purokID'])) {
    header("Location: admin-dashboard.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);
$purokID = $_GET['purokID'];
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

$purok = $db->getPurokById($purokID);

if (!$purok) {
    die("Error: Purok not found for ID: " . $purokID);
}

// Get all households for this purok
$households = $db->getHouseholdsByPurok($purokID);
$purokName = isset($purok['purok_name']) ? htmlspecialchars($purok['purok_name']) : 'Unknown Purok';

// Determine which XML file to use based on whether there's a search
$xmlFilename = !empty($searchQuery) ? "search_households.xml" : "households_purok_{$purokID}.xml";

// Create XML document
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

// Add XML stylesheet reference
$xmlStylesheet = $dom->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="households.xsl"');
$dom->appendChild($xmlStylesheet);

// Root element
$householdsElem = $dom->createElement('households');
$dom->appendChild($householdsElem);

// Purok element
$purokElem = $dom->createElement('purok');
$householdsElem->appendChild($purokElem);

// Add purok details
$nameElem = $dom->createElement('name', $purokName);
$purokElem->appendChild($nameElem);

$idElem = $dom->createElement('id', $purokID);
$purokElem->appendChild($idElem);

// Add search query if exists
if (!empty($searchQuery)) {
    $searchElem = $dom->createElement('search', htmlspecialchars($searchQuery));
    $purokElem->appendChild($searchElem);
}

// Household list
$listElem = $dom->createElement('household-list');
$purokElem->appendChild($listElem);

if (empty($households)) {
    $emptyElem = $dom->createElement('empty', empty($searchQuery) ? 
        'No households found in this purok.' : 
        'No households found matching your search.');
    $listElem->appendChild($emptyElem);
} else {
    // If there's a search query, filter the households
    if (!empty($searchQuery)) {
        $filteredHouseholds = array();
        foreach ($households as $household) {
            if (stripos($household['household_head'], $searchQuery) !== false) {
                $filteredHouseholds[] = $household;
            }
        }
        $households = $filteredHouseholds;
    }

    // Add households to XML
    foreach ($households as $household) {
        $householdElem = $dom->createElement('household');
        $idElem = $dom->createElement('id', htmlspecialchars($household['householdID']));
        $headElem = $dom->createElement('head', htmlspecialchars($household['household_head']));
        $householdElem->appendChild($idElem);
        $householdElem->appendChild($headElem);
        $listElem->appendChild($householdElem);
    }
}

// Save the XML file
$dom->save($xmlFilename);

// Redirect to the generated XML file
header("Location: $xmlFilename");
exit();
?>
