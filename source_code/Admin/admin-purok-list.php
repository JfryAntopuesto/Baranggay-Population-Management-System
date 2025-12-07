<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../../database/database-connection.php";
include "../../database/database-operations.php";

$db = new DatabaseOperations($conn);

$searchTerm = '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $puroks = $db->searchPuroks($searchTerm);
} else {
    $puroks = $db->getAllPuroks($current_page, $per_page);
    $total_puroks = $db->getTotalPuroksCount();
    $total_pages = ceil($total_puroks / $per_page);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Puroks - Barangay Population Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0; height: 100vh;
        }
        header {
            background: linear-gradient(135deg, #0033cc, #0066ff);
            color: white;
            text-align: center;
            padding: 25px;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            animation: headerDown 0.7s cubic-bezier(.77,0,.18,1);
            position: relative;
        }
        @keyframes headerDown {
            from { opacity: 0; transform: translateY(-40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
            margin-bottom: 70px; /* Add space for fixed pagination */
        }
        .purok-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-top: 20px;
        }
        .purok-list h3 {
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: 600;
        }

        tbody tr:hover {
            background-color: #f9f9f9;
        }

        td button {
            padding: 6px 12px;
            border: 1px solid #0033cc;
            border-radius: 4px;
            background-color: #fff;
            color: #0033cc;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
            margin-right: 5px; /* Add space between buttons */
        }

        td button:hover {
            background-color: #0033cc;
            color: #fff;
            border-color: #0033cc;
        }

        td button.delete-btn {
            border-color: #d00000;
            color: #d00000;
        }

        td button.delete-btn:hover {
            background-color: #d00000;
            color: #fff;
        }

        td:last-child {
            white-space: nowrap; /* Prevent buttons from wrapping */
        }
        .back-btn {
            color: #0033cc;
            font-weight: 600;
            font-size: 1.1rem;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover { color: #0055ff; text-decoration: underline; }
        .add-btn {
            color: #0033cc;
            font-weight: 600;
            font-size: 1.1rem;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .add-btn:hover { color: #0055ff; text-decoration: underline; }
        .button-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-container input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        .search-container button {
            padding: 10px 15px;
            background-color: #0033cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease;
        }
        .search-container button:hover {
            background-color: #0055ff;
        }
        .pagination {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .page-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #0033cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        .page-btn:hover {
            background: #0055ff;
        }
        .page-btn.active {
            background: #0055ff;
        }
    </style>
</head>
<body>
    <header>
        <?php require_once '../../config/barangay-config.php'; echo BARANGAY_NAME; ?> - Population Management System
        <button class="logout-btn" onclick="location.href='../logout.php'" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: 2px solid white; color: white; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">LOGOUT</button>
    </header>
    <div class="container">
        <div class="button-container">
            <a href="admin-dashboard.php" class="back-btn">← Back to Dashboard</a>
            <a href="admin-purok-edit.php" class="add-btn">+ Add New Purok</a>
        </div>
        
        <form method="GET" action="admin-purok-list.php">
            <div class="search-container">
                <input type="text" name="search" placeholder="Search puroks..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit">Search</button>
                <?php if(!empty($searchTerm)): ?>
                    <a href="admin-purok-list.php" class="clear-btn" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; text-decoration: none; transition: background-color 0.2s ease;">Clear Search</a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
        <?php endif; ?>
        
        <div class="purok-list">
            <h3>ALL PUROKS</h3>
            <table>
                <thead>
                    <tr>
                        <th>Purok Name</th>
                        <th>Purok Code</th>
                        <th>Araw</th>
                        <th>Purok President</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($puroks as $purok): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($purok['purok_name']); ?></td>
                        <td style="font-family: monospace; font-weight: 600;"><?php echo htmlspecialchars($purok['purok_code']); ?></td>
                        <td><?php echo htmlspecialchars($purok['araw']); ?></td>
                        <td><?php echo htmlspecialchars($purok['purok_pres']); ?></td>
                        <td>
                            <button class="edit-btn" onclick="window.location.href='admin-purok-edit.php?id=<?php echo $purok['purokID']; ?>'">Edit</button>
                            <button class="delete-btn" onclick="deletePurok(<?php echo $purok['purokID']; ?>)">Delete</button>
                            <button class="view-btn" onclick="window.location.href='admin-household-lists.php?purokID=<?php echo $purok['purokID']; ?>'">View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if(!isset($_GET['search']) || empty($_GET['search'])): ?>
    <div class="pagination">
        <?php if($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>" class="page-btn">Previous</a>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo $i === $current_page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php if($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>" class="page-btn">Next</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <script>
        function deletePurok(purokId) {
            if(confirm('Are you sure you want to delete this purok?')) {
                window.location.href = 'delete-purok.php?id=' + purokId;
            }
        }
    </script>
</body>
</html> 