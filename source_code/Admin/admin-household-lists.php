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

$purokID = intval($_GET['purokID']);
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Lists - Barangay Population Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        h1 {
            font-size: 1.8rem;
            margin: 0;
        }
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: white;
            color: #0033cc;
        }
        .purok-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .purok-info h2 {
            color: #0033cc;
            margin-bottom: 10px;
        }
        .search-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .search-form input {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .search-form input:focus {
            outline: none;
            border-color: #0033cc;
        }
        .search-form button {
            padding: 10px 20px;
            background: #0033cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        .search-form button:hover {
            background: #0055ff;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .loading::after {
            content: '...';
            animation: dots 1.5s steps(4, end) infinite;
        }
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #c62828;
        }
        .households-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .households-table {
            width: 100%;
            border-collapse: collapse;
        }
        .households-table thead {
            background: #0033cc;
            color: white;
        }
        .households-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        .households-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .households-table tbody tr:hover {
            background: #f5f5f5;
        }
        .households-table tbody tr:last-child td {
            border-bottom: none;
        }
        .view-btn {
            background: #0033cc;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        .view-btn:hover {
            background: #0055ff;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <h1>Household Lists</h1>
                <a href="admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </header>

        <!-- Purok Information Section -->
        <div class="purok-info" id="purokInfo">
            <div class="loading">Loading purok information</div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <form class="search-form" id="searchForm" onsubmit="handleSearch(event)">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Search households by head name..." 
                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                >
                <button type="submit">Search</button>
                <button type="button" onclick="clearSearch()" style="background: #6c757d;">Clear</button>
            </form>
        </div>

        <!-- Households Container -->
        <div class="households-container">
            <div id="householdsContent">
                <div class="loading">Loading households</div>
            </div>
        </div>
    </div>

    <!-- Include the Purok API utility -->
    <script src="../js/purok-api.js"></script>
    
    <script>
        const purokID = <?php echo $purokID; ?>;
        let currentSearchQuery = '<?php echo htmlspecialchars($searchQuery); ?>';
        let households = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadPurokInfo();
            loadHouseholds();
        });

        /**
         * Load purok information from API
         */
        async function loadPurokInfo() {
            const purokInfoEl = document.getElementById('purokInfo');
            
            try {
                const purok = await PurokAPI.getPurokById(purokID);
                
                purokInfoEl.innerHTML = `
                    <h2>${escapeHtml(purok.purok_name)}</h2>
                    <p><strong>Purok Code:</strong> ${escapeHtml(purok.purok_code)}</p>
                    <p><strong>Purok President:</strong> ${escapeHtml(purok.purok_pres)}</p>
                    <p><strong>Araw ng Purok:</strong> ${formatDate(purok.araw)}</p>
                `;
            } catch (error) {
                purokInfoEl.innerHTML = `
                    <div class="error-message">
                        <strong>Error:</strong> Failed to load purok information. ${error.message}
                    </div>
                `;
            }
        }

        /**
         * Load households from PHP endpoint (since we need household data, not just puroks)
         */
        async function loadHouseholds() {
            const householdsContentEl = document.getElementById('householdsContent');
            
            try {
                const response = await fetch(`get-households.php?purokID=${purokID}`);
                
                // Check if response is OK
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                }
                
                // Check content type
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Expected JSON but got:', contentType, text.substring(0, 200));
                    throw new Error('Server returned non-JSON response. Check console for details.');
                }
                
                households = await response.json();
                
                // Validate response is an array
                if (!Array.isArray(households)) {
                    console.error('Expected array but got:', households);
                    throw new Error('Invalid response format: expected array');
                }
                
                // Filter households if search query exists
                let filteredHouseholds = households;
                if (currentSearchQuery.trim()) {
                    const searchLower = currentSearchQuery.toLowerCase();
                    filteredHouseholds = households.filter(h => 
                        h.household_head && h.household_head.toLowerCase().includes(searchLower)
                    );
                }
                
                renderHouseholds(filteredHouseholds);
            } catch (error) {
                console.error('Error loading households:', error);
                householdsContentEl.innerHTML = `
                    <div class="error-message">
                        <strong>Error:</strong> Failed to load households.<br>
                        <small>${escapeHtml(error.message)}</small><br>
                        <small style="margin-top: 10px; display: block;">
                            Check browser console (F12) for more details.
                        </small>
                    </div>
                `;
            }
        }

        /**
         * Render households table
         */
        function renderHouseholds(householdsToRender) {
            const householdsContentEl = document.getElementById('householdsContent');
            
            if (householdsToRender.length === 0) {
                householdsContentEl.innerHTML = `
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3>No households found</h3>
                        <p>${currentSearchQuery ? 'No households match your search criteria.' : 'No households are registered in this purok yet.'}</p>
                    </div>
                `;
                return;
            }
            
            let tableHTML = `
                <table class="households-table">
                    <thead>
                        <tr>
                            <th>Household ID</th>
                            <th>Household Head</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            householdsToRender.forEach(household => {
                tableHTML += `
                    <tr>
                        <td>${household.householdID}</td>
                        <td>${escapeHtml(household.household_head)}</td>
                        <td>
                            <button class="view-btn" onclick="viewHousehold(${household.householdID})">
                                View Details
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableHTML += `
                    </tbody>
                </table>
            `;
            
            householdsContentEl.innerHTML = tableHTML;
        }

        /**
         * Handle search form submission
         */
        function handleSearch(event) {
            event.preventDefault();
            const searchInput = document.getElementById('searchInput');
            currentSearchQuery = searchInput.value.trim();
            
            // Filter existing households
            if (currentSearchQuery) {
                const searchLower = currentSearchQuery.toLowerCase();
                const filtered = households.filter(h => 
                    h.household_head.toLowerCase().includes(searchLower)
                );
                renderHouseholds(filtered);
            } else {
                renderHouseholds(households);
            }
        }

        /**
         * Clear search
         */
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            currentSearchQuery = '';
            renderHouseholds(households);
        }

        /**
         * View household details
         */
        function viewHousehold(householdID) {
            window.location.href = `admin-household-view.php?householdID=${householdID}`;
        }

        /**
         * Utility: Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Utility: Format date
         */
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
    </script>
</body>
</html>
