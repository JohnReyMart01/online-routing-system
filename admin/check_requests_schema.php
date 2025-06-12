<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SECURE_ACCESS', true);

require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    redirect('../index.php');
}

// This script checks the schema of the requests table

echo "<h1>Requests Table Schema</h1>";

try {
    // Describe the requests table
    $stmt = $conn->query("DESCRIBE requests");
    $schema = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($schema) > 0) {
        echo "<p>Schema for the requests table:</p>";
        echo "<table border=\"1\"><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($schema as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Could not retrieve schema for the requests table. It might not exist.</p>";
    }

} catch (PDOException $e) {
    echo "<p style=\"color: red;\">Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style=\"color: red;\">An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href=\"technicians.php\">Back to Manage Technicians</a>";

?> 