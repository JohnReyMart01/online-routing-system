<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Debug session and role
error_log("Session data: " . print_r($_SESSION, true));
error_log("Current script: " . $_SERVER['PHP_SELF']);

// Check role and redirect if needed
check_role(['admin']);

$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'list';
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? sanitize($_GET['priority']) : '';
$college_filter = isset($_GET['college']) ? sanitize($_GET['college']) : '';
$error = '';
$success = '';

// Handle different actions
if ($action === 'assign' && $request_id > 0) {
    // Assign request to technician - handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $technician_id = intval($_POST['technician_id']);
        $deadline = sanitize($_POST['deadline']);
        
        try {
            $conn->beginTransaction();
            
            // Get current request status
            $stmt = $conn->prepare("SELECT status FROM requests WHERE id = :id");
            $stmt->bindParam(':id', $request_id);
            $stmt->execute();
            $current_status = $stmt->fetchColumn();
            
            if ($current_status !== 'pending') {
                throw new Exception("Request is not in pending status and cannot be assigned.");
            }
            
            // Check technician availability
            $stmt = $conn->prepare("SELECT availability FROM technicians WHERE id = :id");
            $stmt->bindParam(':id', $technician_id);
            $stmt->execute();
            $is_available = $stmt->fetchColumn();
            
            if (!$is_available) {
                throw new Exception("Selected technician is not available for new tasks.");
            }
            
            // Update request
            $stmt = $conn->prepare("UPDATE requests 
                                   SET status = 'assigned', 
                                       assigned_technician_id = :tech_id,
                                       deadline = :deadline,
                                       updated_at = NOW()
                                   WHERE id = :id");
            $stmt->bindParam(':tech_id', $technician_id);
            $stmt->bindParam(':deadline', $deadline);
            $stmt->bindParam(':id', $request_id);
            $stmt->execute();
            
            // Log the assignment
            $stmt = $conn->prepare("INSERT INTO task_logs (request_id, technician_id, action, notes) 
                                   VALUES (:request_id, :technician_id, 'assigned', 'Request assigned to technician')");
            $stmt->bindParam(':request_id', $request_id);
            $stmt->bindParam(':technician_id', $technician_id);
            $stmt->execute();
            
            $conn->commit();
            $_SESSION['success'] = "Request assigned successfully!";
            redirect('requests.php');
            
        } catch(Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
    
    // Get request data for assignment
    try {
        $stmt = $conn->prepare("SELECT r.*, u.first_name, u.last_name, c.name as college_name
                              FROM requests r
                              JOIN users u ON r.requester_id = u.id
                              JOIN colleges c ON r.college_id = c.id
                              WHERE r.id = :id");
        $stmt->bindParam(':id', $request_id);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            $_SESSION['error'] = "Request not found.";
            redirect('requests.php');
        }
        
        // Get available technicians
        $stmt = $conn->query("SELECT t.id, u.first_name, u.last_name, t.specialization
                             FROM technicians t
                             JOIN users u ON t.user_id = u.id
                             WHERE t.availability = 1
                             AND u.is_active = 1
                             ORDER BY u.first_name, u.last_name");
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error fetching request: " . $e->getMessage();
        redirect('requests.php');
    }
} elseif ($action === 'view' && $request_id > 0) {
    // View request details
    try {
        $stmt = $conn->prepare("SELECT r.*, 
                               u.first_name as requester_first, u.last_name as requester_last, 
                               t.id as tech_id, ut.first_name as tech_first, ut.last_name as tech_last,
                               c.name as college_name
                              FROM requests r
                              JOIN users u ON r.requester_id = u.id
                              JOIN colleges c ON r.college_id = c.id
                              LEFT JOIN technicians t ON r.assigned_technician_id = t.id
                              LEFT JOIN users ut ON t.user_id = ut.id
                              WHERE r.id = :id");
        $stmt->bindParam(':id', $request_id);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            $_SESSION['error'] = "Request not found.";
            redirect('requests.php');
        }
        
        // Get task logs
        $stmt = $conn->prepare("SELECT tl.*, u.first_name, u.last_name
                               FROM task_logs tl
                               LEFT JOIN technicians t ON tl.technician_id = t.id
                               LEFT JOIN users u ON t.user_id = u.id
                               WHERE tl.request_id = :request_id
                               ORDER BY tl.created_at DESC");
        $stmt->bindParam(':request_id', $request_id);
        $stmt->execute();
        $task_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error fetching request: " . $e->getMessage();
        redirect('requests.php');
    }
}

// Get all requests for listing
$requests = [];
$colleges = [];
$tech_users = []; // To fetch technician names for filter (optional but good to have)

try {
    // First, verify database connection
    if (!$conn) {
        throw new PDOException("Database connection not established");
    }

    // Test query to check if requests table exists and has data
    $test_stmt = $conn->query("SELECT COUNT(*) FROM requests");
    $request_count = $test_stmt->fetchColumn();
    error_log("Total number of requests in database: " . $request_count);

    // Get colleges for filter
    $stmt = $conn->query("SELECT id, name FROM colleges ORDER BY name");
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build the base SQL query
    $sql = "SELECT r.*, 
           u.first_name as requester_first, u.last_name as requester_last,
           t.id as tech_id, ut.first_name as tech_first, ut.last_name as tech_last,
           c.name as college_name
           FROM requests r
           LEFT JOIN users u ON r.requester_id = u.id
           LEFT JOIN colleges c ON r.college_id = c.id
           LEFT JOIN technicians t ON r.assigned_technician_id = t.id
           LEFT JOIN users ut ON t.user_id = ut.id";
    
    $params = [];
    $where_clauses = [];

    // Add filters
    if ($status_filter) {
        $where_clauses[] = "r.status = :status";
        $params[':status'] = $status_filter;
    }
    if ($priority_filter) {
        $where_clauses[] = "r.priority = :priority";
        $params[':priority'] = $priority_filter;
    }
     if ($college_filter) {
        $where_clauses[] = "r.college_id = :college_id";
        $params[':college_id'] = $college_filter;
    }
    // Add more filters as needed (e.g., technician)

    // Combine where clauses
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Add ordering
    $sql .= " ORDER BY r.priority DESC, r.created_at DESC"; // Prioritize high urgency
    
    // Debug: Print the SQL query
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r($params, true));
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Print the number of results
    error_log("Number of requests found: " . count($requests));
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching requests: " . $e->getMessage();
} catch(Exception $e) {
    error_log("General Error: " . $e->getMessage());
    $_SESSION['error'] = "An unexpected error occurred: " . $e->getMessage();
}

// After all your processing code, before the HTML starts, add this debug:
error_log("Action: $action, Request ID: $request_id");

// Start HTML output
echo '<!-- DEBUG: Reached HTML output -->';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Requests - Online Routing System</title>
    <?php include '../includes/header_links.php'; ?>
    <style>
        /* --- Requests Page Enhanced Styles --- */
        .content-wrapper {
            margin-left: 250px;
            padding: 30px 20px 20px 20px;
            min-height: 100vh;
            background: #f8f9fa;
            color: #222;
        }
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
            background: #fff;
            color: #222;
            border: none;
        }
        .card-header {
            border-radius: 0.5rem 0.5rem 0 0;
            background: #f4f6f9;
            border-bottom: 1px solid #e3e6ea;
            padding: 1rem 1.25rem;
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        .card-body {
            padding: 1.25rem;
        }
        /* Filter form */
        .card form.row {
            gap: 0.5rem 0;
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
        .form-select, .form-control {
            border-radius: 0.3rem;
            border: 1px solid #ced4da;
            font-size: 0.95rem;
        }
        .btn-primary {
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            border-radius: 0.3rem;
        }
        /* Table styles */
        .table {
            background: #fff;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 0;
        }
        .table th, .table td {
            vertical-align: middle;
            font-size: 0.97rem;
            padding: 0.7rem 0.75rem;
        }
        .table th {
            background: #f4f6f9;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #e3e6ea;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #e3e6ea;
        }
        .table-hover tbody tr:hover {
            background: #f1f7ff;
        }
        .table .btn-group .btn {
            padding: 0.3rem 0.7rem;
            font-size: 0.9rem;
            border-radius: 0.2rem;
        }
        .badge {
            font-size: 0.9em;
            padding: 0.4em 0.7em;
            border-radius: 0.3em;
        }
        /* No requests found row */
        .table td.text-center {
            color: #888;
            font-size: 1.05em;
            font-style: italic;
            background: #f8f9fa;
        }
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .content-wrapper {
                margin-left: 0;
                padding: 15px 5px;
            }
            .card-body, .card-header {
                padding: 1rem 0.5rem;
            }
            .table th, .table td {
                padding: 0.5rem 0.4rem;
            }
        }
    </style>
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php 
        include '../includes/admin_navbar.php';
        include '../includes/admin_sidebar.php';
        ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Manage Requests</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="content">
                <div class="container-fluid">
                    <?php display_alerts(); ?>
                    
                    <!-- Filters Card -->
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" class="form-select">
                                        <option value="">All Priorities</option>
                                        <option value="high">High</option>
                                        <option value="medium">Medium</option>
                                        <option value="low">Low</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">College</label>
                                    <select name="college" class="form-select">
                                        <option value="">All Colleges</option>
                                        <option value="cas">College of Arts and Sciences</option>
                                        <option value="cba">College of Business Administration</option>
                                        <option value="coe">College of Engineering</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Requests Table Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Request List</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Requester</th>
                                            <th>College</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Assigned Tech</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($requests)): ?>
                                            <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['requester_first'] . ' ' . $request['requester_last']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['college_name']); ?></td>
                                                    <td>
                                                        <span class="badge priority-<?php echo strtolower($request['priority']); ?>">
                                                            <?php echo ucfirst($request['priority']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge status-<?php echo str_replace(' ', '-', strtolower($request['status'])); ?>">
                                                            <?php echo ucwords(str_replace('_', ' ', $request['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                                    <td>
                                                        <?php echo $request['tech_first'] ? htmlspecialchars($request['tech_first'] . ' ' . $request['tech_last']) : 'Not Assigned'; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="requests.php?action=view&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($request['status'] === 'pending'): ?>
                                                                <a href="requests.php?action=assign&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-user-plus"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No requests found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <?php include '../includes/scripts.php'; ?>
</body>
</html>