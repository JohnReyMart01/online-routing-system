<?php
define('SECURE_ACCESS', true);

// Fix file paths
$root_dir = dirname(__DIR__);
require_once $root_dir . '/includes/config/db.php';
require_once $root_dir . '/includes/config/functions.php';
require_once $root_dir . '/includes/auth/authenticate.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    redirect('../index.php');
}

// This script syncs users with 'technician' role to the technicians table
// You can remove this file after running it once successfully.

$synced_count = 0;
$skipped_count = 0;

echo "<h2>Running Technician Sync Script...</h2>";
echo "<p>Attempting to find technician users and sync to technicians table.</p>";

try {
    // Get all users with 'technician' role
    $stmt_users = $conn->prepare("SELECT id FROM users WHERE role = 'technician'");
    $stmt_users->execute();
    $technician_users = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Found " . count($technician_users) . " users with 'technician' role.</p>";

    // Prepare insert statement for technicians table
    $stmt_insert_technician = $conn->prepare("INSERT INTO technicians (user_id, specialization, status) VALUES (?, ?, ?)");
    
    // Prepare check statement for technicians table
    $stmt_check_technician = $conn->prepare("SELECT 1 FROM technicians WHERE user_id = ?");

    foreach ($technician_users as $user_id) {
        // Check if technician already exists
        $stmt_check_technician->execute([$user_id]);
        if ($stmt_check_technician->fetchColumn()) {
            $skipped_count++;
            continue; // Skip if already exists
        }

        // Insert new technician with default values
        // You might want to set default specialization/availability here if needed
        $default_specialization = 'General'; // Or determine based on college/other factor
        $default_status = 'available'; // Use a valid enum value for the status column

        $stmt_insert_technician->execute([$user_id, $default_specialization, $default_status]);
        $synced_count++;
    }

    echo "<p>Sync process finished.</p>";
    echo "<p>{$synced_count} new technicians added.</p>";
    echo "<p>{$skipped_count} technicians skipped (already existed).</p>";

    // Set session message (will be displayed on redirect)
    if ($synced_count > 0) {
        $_SESSION['success'] = "Technician sync complete: {$synced_count} new technicians added, {$skipped_count} skipped.";
    } else {
         $_SESSION['info'] = "Technician sync complete: No new technicians added. {$skipped_count} skipped.";
    }

} catch (PDOException $e) {
    echo "<p style=\"color: red;\">Database error during sync: " . htmlspecialchars($e->getMessage()) . "</p>";
    $_SESSION['error'] = "Database error during sync: " . $e->getMessage();
} catch (Exception $e) {
    echo "<p style=\"color: red;\">An error occurred during sync: " . htmlspecialchars($e->getMessage()) . "</p>";
    $_SESSION['error'] = "An error occurred during sync: " . $e->getMessage();
}

echo "<p><a href=\"technicians.php\">Continue to Manage Technicians</a></p>";
// redirect('technicians.php'); // Temporarily commented out redirect

?>
