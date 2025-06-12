<?php
require_once 'includes/config/db.php';

try {
    // Check if college_id column exists
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'college_id'");
    if ($stmt->rowCount() == 0) {
        // Add college_id column
        $conn->exec("ALTER TABLE users ADD COLUMN college_id INT NULL AFTER last_name");
        echo "Added college_id column to users table\n";
        
        // Add foreign key constraint
        $conn->exec("ALTER TABLE users ADD CONSTRAINT fk_users_college FOREIGN KEY (college_id) REFERENCES colleges(id)");
        echo "Added foreign key constraint\n";
    } else {
        echo "college_id column already exists\n";
    }
    
    // Check if the column was added successfully
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'college_id'");
    if ($stmt->rowCount() > 0) {
        echo "college_id column is now present in the users table\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 