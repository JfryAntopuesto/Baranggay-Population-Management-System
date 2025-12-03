<?php

include 'database-connection.php';

$sql = "CREATE TABLE IF NOT EXISTS announcement (
    announcementID INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

if ($conn->query($sql) === TRUE) {
    echo "Table 'announcement' created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();

?> 