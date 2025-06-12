<?php
date_default_timezone_set('Asia/Manila'); // Set your desired timezone
define('SECURE_ACCESS', true);

require_once '../config/db.php';
require_once '../config/functions.php';
require_once '../auth/authenticate.php';

header('Content-Type: application/json'); // Set header to return JSON

$response = ['success' => false, 'message' => ''];

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit();
}

// Check if the request method is POST and required data is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['technician_id'], $_POST['status'])) {
    $technicianId = intval($_POST['technician_id']);
    $newStatus = sanitize($_POST['status']);
    
    // Validate the new status against allowed values
    $allowedStatuses = ['available', 'busy', 'offline'];
    if (!in_array($newStatus, $allowedStatuses)) {
         $response['message'] = "Invalid status value.";
        echo json_encode($response);
        exit();
    }

    // Ensure a technician with this ID exists
    try {
        $stmt = $conn->prepare("SELECT id FROM technicians WHERE id = :id");
        $stmt->bindParam(':id', $technicianId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
             $response['message'] = "Technician not found.";
            echo json_encode($response);
            exit();
        }
    } catch(PDOException $e) {
        // Log error instead of exposing it directly in production
        error_log("Database error checking technician existence: " . $e->getMessage());
        $response['message'] = "Database error.";
        echo json_encode($response);
        exit();
    }

    // Update the technician status
    try {
        $stmt = $conn->prepare("UPDATE technicians SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $technicianId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = "Technician status updated successfully.";
        } else {
             $response['message'] = "Technician status did not change or update failed.";
        }
        
    } catch(PDOException $e) {
        // Log error instead of exposing it directly in production
        error_log("Database error updating technician status: " . $e->getMessage());
        $response['message'] = "Database error.";
    }
} else {
    $response['message'] = "Invalid request method or missing data.";
}

echo json_encode($response);

?> 