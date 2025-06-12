<?php
require_once 'includes/config/db.php';

try {
    // Check database connection
    echo "Database connection successful!\n";
    
    // Get users table structure
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUsers table structure:\n";
    foreach ($columns as $column) {
        echo "Field: {$column['Field']}, Type: {$column['Type']}, Null: {$column['Null']}, Key: {$column['Key']}, Default: {$column['Default']}\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 