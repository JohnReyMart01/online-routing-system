<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '12M@sterkill');
define('DB_NAME', 'online_routing_system');

// Create connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Database configuration
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Define BASE_URL
if (!defined('BASE_URL')) {
    // Determine the base URL dynamically or set it manually
    // You might need to adjust this based on your server configuration
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = pathinfo($script, PATHINFO_DIRNAME);
    // Adjust the path if your project is in a subdirectory (e.g., /online-services-routing)
    // If the project is directly in htdocs, $path might be "/" or empty.
    // If it's in htdocs/online-services-routing, $path would be "/online-services-routing/admin" for admin pages
    // We want the base path, which is likely one or two levels up from the current script.
    
    // Simple approach: assume the project is in a subdirectory like /online-services-routing
    // You might need to adjust this if your setup is different.
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $current_dir = str_replace('\\', '/', __DIR__);
    $base_path = str_replace($doc_root, '', $current_dir);
    // Go up two levels to get to the project root relative to htdocs
    $base_url = $protocol . "://" . $host . "/" . basename(dirname(dirname(__FILE__)));
    
    // More robust approach to find base URL:
    $self = $_SERVER['PHP_SELF'];
    $base_dir  = str_replace('\\', '/', dirname($self));
    // Assuming project is one level above includes
    $base_url = $protocol . "://" . $host . dirname($base_dir);

     // You may need to manually set this if the above logic is complex for your setup
     // Example manual setting:
     // define('BASE_URL', 'http://localhost/online-services-routing');
     
     // For your specific case, it looks like it might be at http://localhost/online-services-routing
     define('BASE_URL', 'http://localhost/online-services-routing');
}
?>