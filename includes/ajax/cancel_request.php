<?php
require_once '../config/db.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$is_admin = isset($_POST['admin']) ? true : false;

if ($request_id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Invalid request ID']));
}

try {
    $conn->beginTransaction();
    
    // Get request details
    $stmt = $conn->prepare("SELECT r.*, r.requester_id, r.assigned_technician_id
                           FROM requests r
                           WHERE r.id = :id");
    $stmt->bindParam(':id', $request_id);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception("Request not found.");
    }
    
    // Check permissions
    if (!$is_admin && $request['requester_id'] != $_SESSION['user_id']) {
        throw new Exception("You don't have permission to cancel this request.");
    }
    
    // Check if request can be cancelled
    if (!in_array($request['status'], ['pending', 'assigned', 'in_progress'])) {
        throw new Exception("Request cannot be cancelled in its current status.");
    }
    
    // Update request status
    $stmt = $conn->prepare("UPDATE requests 
                           SET status = 'cancelled', 
                               updated_at = NOW(),
                               completed_at = NOW()
                           WHERE id = :id");
    $stmt->bindParam(':id', $request_id);
    $stmt->execute();
    
    // Log the cancellation
    $action = $is_admin ? 'admin_cancelled' : 'cancelled';
    $notes = $is_admin ? 'Request cancelled by admin' : 'Request cancelled by requester';
    
    $stmt = $conn->prepare("INSERT INTO task_logs (request_id, technician_id, action, notes) 
                           VALUES (:request_id, :technician_id, :action, :notes)");
    $stmt->bindParam(':request_id', $request_id);
    $stmt->bindParam(':technician_id', $request['assigned_technician_id'], PDO::PARAM_INT);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':notes', $notes);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Request cancelled successfully!']);
    
} catch(Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>