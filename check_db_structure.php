<?php
require_once 'includes/config/db.php';

try {
    // Check database connection
    echo "Database connection successful!\n";
    
    // Check if users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "Users table does not exist!\n";
        exit;
    }
    
    // Get users table structure
    $stmt = $conn->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUsers table columns:\n";
    foreach ($columns as $column) {
        echo "{$column['Field']} ({$column['Type']})\n";
    }
    
    // Test user insertion
    $username = 'testuser' . time();
    $email = 'test' . time() . '@example.com';
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $first_name = 'Test';
    $last_name = 'User';
    $college_id = 1;
    $role = 'requester';
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, college_id, role) 
                           VALUES (:username, :email, :password, :first_name, :last_name, :college_id, :role)");
    
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':college_id', $college_id);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        echo "\nTest user created successfully!\n";
        echo "Username: $username\n";
        echo "Email: $email\n";
    } else {
        echo "\nError creating test user\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 