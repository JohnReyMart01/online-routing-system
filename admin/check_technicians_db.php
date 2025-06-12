<?php
define('SECURE_ACCESS', true);

require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    redirect('../index.php');
}

// This script checks the content of the technicians table

echo "<h1>Technicians Table Content</h1>";

try {
    // Get all entries from the technicians table
    $stmt = $conn->query("SELECT * FROM technicians");
    $technicians_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($technicians_data) > 0) {
        echo "<p>Found " . count($technicians_data) . " entries in the technicians table:</p>";
        echo "<table border=\"1\"><tr><th>ID</th><th>user_id</th><th>specialization</th><th>availability</th><th>created_at</th><th>updated_at</th></tr>";
        foreach ($technicians_data as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
            echo "<td>" . htmlspecialchars($row['availability']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($row['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>The technicians table is empty.</p>";
    }

} catch (PDOException $e) {
    echo "<p style=\"color: red;\">Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style=\"color: red;\">An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href=\"technicians.php\">Back to Manage Technicians</a>";

?> 