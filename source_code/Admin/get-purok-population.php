<?php
// Prevent any output before JSON response
ob_start();

// Set error handling to return JSON instead of HTML
function handleError($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $errstr]);
    exit;
}
set_error_handler('handleError');

// Set headers first
header('Content-Type: application/json');

try {
    include "../../database/database-connection.php";
    include "../../database/database-operations.php";

    $db = new DatabaseOperations($conn);

    // Get all puroks and their population
    $puroks = $db->getAllPuroks();
    if (!$puroks) {
        throw new Exception('No puroks found');
    }

    $data = [
        'labels' => [],
        'values' => [],
        'percentages' => []
    ];

    // First, get total population across all puroks (households + their members)
    $total_sql = "SELECT 
        (SELECT COUNT(*) FROM household) + 
        (SELECT COUNT(*) FROM members) as total";
    $total_result = $conn->query($total_sql);
    if (!$total_result) {
        throw new Exception('Error getting total population: ' . $conn->error);
    }
    $total_population = $total_result->fetch_assoc()['total'];

    // Then get population for each purok
    foreach ($puroks as $purok) {
        $purokID = $purok['purokID'];
        $purokName = $purok['purok_name'];

        // Count households in this purok and their members
        $sql = "SELECT 
            (SELECT COUNT(*) FROM household WHERE purokID = ?) + 
            (SELECT COUNT(*) FROM members m 
             JOIN household h ON m.householdID = h.householdID 
             WHERE h.purokID = ?) as count";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error preparing statement: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $purokID, $purokID);
        if (!$stmt->execute()) {
            throw new Exception('Error executing statement: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $population = $row['count'];

        // Calculate percentage
        $percentage = $total_population > 0 ? ($population / $total_population) * 100 : 0;
        
        $data['labels'][] = $purokName;
        $data['values'][] = (int)$population;
        $data['percentages'][] = round($percentage, 2);
    }

    // Ensure we have data
    if (empty($data['labels'])) {
        throw new Exception('No population data found');
    }

    // Clear any output buffer
    ob_end_clean();
    
    // Send JSON response
    echo json_encode($data);

} catch (Exception $e) {
    // Clear any output buffer
    ob_end_clean();
    
    // Send error response
    echo json_encode(['error' => $e->getMessage()]);
} 