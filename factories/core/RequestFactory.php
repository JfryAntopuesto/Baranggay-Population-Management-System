<?php
/**
 * RequestFactory - Static Facade for Backward Compatibility
 * 
 * This class maintains the original static interface while using the proper 
 * Factory Method pattern with separated classes.
 * 
 * All components are now in separate files:
 * - Product (Abstract): factories/core/products/Request.php
 * - Concrete Products: factories/core/products/DocumentRequest.php, etc.
 * - Creator (Abstract): factories/core/creators/RequestCreator.php
 * - Concrete Creator: factories/core/creators/RequestFactoryCreator.php
 */

require_once __DIR__ . '/creators/RequestFactoryCreator.php';

class RequestFactory {
    /**
     * Create a request object based on the type
     * Uses the proper Factory Method pattern internally
     * 
     * @param string $type The request type
     * @param mysqli $conn Database connection
     * @return Request The created request object
     */
    public static function createRequest($type, $conn) {
        $creator = new RequestFactoryCreator($conn);
        return $creator->createRequest($type);
    }

    /**
     * Get all available request types
     * 
     * @return array List of available request types
     */
    public static function getAvailableRequestTypes() {
        $creator = new RequestFactoryCreator(null);
        return $creator->getAvailableRequestTypes();
    }
}
?>
