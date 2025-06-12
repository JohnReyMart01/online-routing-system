<?php
require_once 'db.php';

try {
    // Create requests table if it doesn't exist
    $create_requests_table = "CREATE TABLE IF NOT EXISTS requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requester_id INT NOT NULL,
        technician_id INT,
        college_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (requester_id) REFERENCES users(id),
        FOREIGN KEY (technician_id) REFERENCES users(id),
        FOREIGN KEY (college_id) REFERENCES colleges(id)
    )";
    $conn->exec($create_requests_table);

    // Check if college_id column exists
    $check_column = "SHOW COLUMNS FROM requests LIKE 'college_id'";
    $result = $conn->query($check_column);
    if ($result->rowCount() === 0) {
        // Add college_id column if it doesn't exist
        $add_column = "ALTER TABLE requests ADD COLUMN college_id INT NOT NULL AFTER technician_id";
        $conn->exec($add_column);
    }

    // Create colleges table if it doesn't exist
    $create_colleges_table = "CREATE TABLE IF NOT EXISTS colleges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        college_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($create_colleges_table);

    // Insert default college if none exists
    $check_colleges = "SELECT COUNT(*) as count FROM colleges";
    $result = $conn->query($check_colleges);
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count == 0) {
        $insert_college = "INSERT INTO colleges (college_name) VALUES 
            ('College of Business and Accountancy'),
            ('College of Engineering'),
            ('College of Arts and Sciences'),
            ('College of Education'),
            ('College of Information Technology')";
        $conn->exec($insert_college);
    }

    // Create users table if it doesn't exist
    $create_users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        contact_number VARCHAR(20),
        role ENUM('admin', 'requester', 'technician') NOT NULL,
        profile_photo VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($create_users_table);

    // Create technicians table if it doesn't exist
    $create_technicians_table = "CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        specialization VARCHAR(100) DEFAULT 'General',
        phone VARCHAR(20) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        profile_photo VARCHAR(255) DEFAULT NULL,
        availability BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($create_technicians_table);

    // Insert test technician if none exists
    $check_technicians = "SELECT COUNT(*) as count FROM users WHERE role = 'technician'";
    $result = $conn->query($check_technicians);
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count == 0) {
        $password_hash = password_hash('technician123', PASSWORD_DEFAULT);
        $insert_technician = "INSERT INTO users (username, password, first_name, last_name, email, role) 
            VALUES ('technician', :password, 'Test', 'Technician', 'technician@test.com', 'technician')";
        $stmt = $conn->prepare($insert_technician);
        $stmt->bindParam(':password', $password_hash);
        $stmt->execute();
    }

    // Insert test requester if none exists
    $check_requesters = "SELECT COUNT(*) as count FROM users WHERE role = 'requester'";
    $result = $conn->query($check_requesters);
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];

    if ($count == 0) {
        $password_hash = password_hash('requester123', PASSWORD_DEFAULT);
        $insert_requester = "INSERT INTO users (username, password, first_name, last_name, email, role) 
            VALUES ('requester', :password, 'Test', 'Requester', 'requester@test.com', 'requester')";
        $stmt = $conn->prepare($insert_requester);
        $stmt->bindParam(':password', $password_hash);
        $stmt->execute();
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 