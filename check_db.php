<?php
require_once 'includes/config/db.php';

try {
    // Check database connection
    echo "Database connection successful!\n";
    
    // Check colleges table
    $stmt = $conn->query("SELECT * FROM colleges");
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nColleges in database:\n";
    foreach ($colleges as $college) {
        echo "ID: {$college['id']}, Name: {$college['name']}, Code: {$college['code']}\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 