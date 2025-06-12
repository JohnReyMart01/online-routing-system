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
                                       technician_id = :tech_id,
                                       deadline = :deadline,
                                       updated_at = NOW()
                                   WHERE id = :id");
            $stmt->bindParam(':tech_id', $technician_id);
            $stmt->bindParam(':deadline', $deadline);
            $stmt->bindParam(':id', $request_id);
            $stmt->execute();
            
            // Log the assignment
            $stmt = $conn->prepare("INSERT INTO task_logs (request_id, technician_id, action, details) 
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

    // Display the assignment form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Assign Technician - Online Routing System</title>
        <?php include '../includes/header_links.php'; ?>
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
                                <h1 class="m-0">Assign Technician</h1>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main content -->
                <div class="content">
                    <div class="container-fluid">
                        <?php display_alerts(); ?>
                        
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Assign Technician to Request</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($error): ?>
                                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                        <?php endif; ?>

                                        <div class="request-details mb-4">
                                            <h4>Request Details</h4>
                                            <p><strong>Title:</strong> <?php echo htmlspecialchars($request['title']); ?></p>
                                            <p><strong>Requester:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                                            <p><strong>College:</strong> <?php echo htmlspecialchars($request['college_name']); ?></p>
                                        </div>

                                        <form method="POST" action="requests.php?action=assign&id=<?php echo $request_id; ?>">
                                            <div class="form-group mb-3">
                                                <label for="technician_id">Select Technician</label>
                                                <select name="technician_id" id="technician_id" class="form-select" required>
                                                    <option value="">Select a technician...</option>
                                                    <?php foreach ($technicians as $tech): ?>
                                                        <option value="<?php echo $tech['id']; ?>">
                                                            <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name'] . ' (' . $tech['specialization'] . ')'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="deadline">Deadline</label>
                                                <input type="datetime-local" name="deadline" id="deadline" class="form-control" required>
                                            </div>

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">Assign Technician</button>
                                                <a href="requests.php" class="btn btn-secondary">Cancel</a>
                                            </div>
                                        </form>
                                    </div>
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
    <?php
    exit();
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
                              LEFT JOIN technicians t ON r.technician_id = t.id
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

    // Display the request details
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>View Request - Online Routing System</title>
        <?php include '../includes/header_links.php'; ?>
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
                                <h1 class="m-0">Request Details</h1>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main content -->
                <div class="content">
                    <div class="container-fluid">
                        <?php display_alerts(); ?>
                        
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Request Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="request-details">
                                            <h4>Basic Information</h4>
                                            <p><strong>Title:</strong> <?php echo htmlspecialchars($request['title']); ?></p>
                                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                                            <p><strong>Requester:</strong> <?php echo htmlspecialchars($request['requester_first'] . ' ' . $request['requester_last']); ?></p>
                                            <p><strong>College:</strong> <?php echo htmlspecialchars($request['college_name']); ?></p>
                                            <p><strong>Status:</strong> <span class="badge status-<?php echo str_replace(' ', '-', strtolower($request['status'])); ?>"><?php echo ucwords(str_replace('_', ' ', $request['status'])); ?></span></p>
                                            <p><strong>Priority:</strong> <span class="badge priority-<?php echo strtolower($request['priority']); ?>"><?php echo ucfirst($request['priority']); ?></span></p>
                                            <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></p>
                                            
                                            <?php if ($request['tech_first']): ?>
                                                <p><strong>Assigned Technician:</strong> <?php echo htmlspecialchars($request['tech_first'] . ' ' . $request['tech_last']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($request['deadline']) && $request['deadline']): ?>
                                                <p><strong>Deadline:</strong> <?php echo date('M d, Y H:i', strtotime($request['deadline'])); ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($task_logs)): ?>
                                            <div class="task-logs mt-4">
                                                <h4>Task History</h4>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Action</th>
                                                                <th>Technician</th>
                                                                <th>Details</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($task_logs as $log): ?>
                                                                <tr>
                                                                    <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                                                    <td><?php echo ucwords(str_replace('_', ' ', $log['action'])); ?></td>
                                                                    <td><?php echo $log['first_name'] ? htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) : 'N/A'; ?></td>
                                                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mt-4">
                                            <a href="requests.php" class="btn btn-secondary">Back to Requests</a>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <a href="requests.php?action=assign&id=<?php echo $request_id; ?>" class="btn btn-primary">Assign Technician</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
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
    <?php
    exit();
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
    echo "<!-- Debug: Total requests in database: " . $request_count . " -->";

    // Get colleges for filter
    $stmt = $conn->query("SELECT id, name FROM colleges ORDER BY name");
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build the base SQL query
    $sql = "SELECT r.*, 
           u.first_name as requester_first, u.last_name as requester_last,
           t.id as tech_id, ut.first_name as tech_first, ut.last_name as tech_last,
           c.name as college_name
           FROM requests r
           INNER JOIN users u ON r.requester_id = u.id
           INNER JOIN colleges c ON r.college_id = c.id
           LEFT JOIN technicians t ON r.technician_id = t.id
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

    // Combine where clauses
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Add ordering
    $sql .= " ORDER BY r.priority DESC, r.created_at DESC"; // Prioritize high urgency
    
    // Debug: Print the SQL query
    echo "<!-- Debug: SQL Query: " . htmlspecialchars($sql) . " -->";
    echo "<!-- Debug: Parameters: " . htmlspecialchars(print_r($params, true)) . " -->";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Print the raw $requests array
    echo "<!-- Debug: Raw requests array: " . htmlspecialchars(print_r($requests, true)) . " -->";
    
    // Debug: Print the number of results
    echo "<!-- Debug: Number of requests found: " . count($requests) . " -->";
    if (count($requests) === 0) {
        echo "<!-- Debug: No requests found. SQL: " . htmlspecialchars($sql) . " -->";
        echo "<!-- Debug: Parameters: " . htmlspecialchars(print_r($params, true)) . " -->";
        
        // Let's check if the data exists in the tables
        $check_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $check_colleges = $conn->query("SELECT COUNT(*) FROM colleges")->fetchColumn();
        $check_requests = $conn->query("SELECT COUNT(*) FROM requests")->fetchColumn();
        
        echo "<!-- Debug: Users count: " . $check_users . " -->";
        echo "<!-- Debug: Colleges count: " . $check_colleges . " -->";
        echo "<!-- Debug: Requests count: " . $check_requests . " -->";
        
        // Check if the specific users exist
        $check_specific_users = $conn->query("SELECT id, first_name, last_name FROM users WHERE id IN (7, 10)")->fetchAll(PDO::FETCH_ASSOC);
        echo "<!-- Debug: Specific users: " . htmlspecialchars(print_r($check_specific_users, true)) . " -->";
    }
    
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
            margin: 0 2px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .table .btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table .btn-group .btn i {
            font-size: 1rem;
        }
        .table .btn-group .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .table .btn-group .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        .table .btn-group .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
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
                                        <?php foreach (
                                            $colleges as $college): ?>
                                            <option value="<?php echo $college['id']; ?>" <?php if ($college_filter == $college['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($college['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
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
                                                            <a href="requests.php?action=view&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($request['status'] === 'pending'): ?>
                                                                <a href="requests.php?action=assign&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary" title="Assign Technician">
                                                                    <i class="fas fa-user-plus"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($request['status'] === 'assigned' || $request['status'] === 'in_progress'): ?>
                                                                <a href="requests.php?action=update&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-warning" title="Update Status">
                                                                    <i class="fas fa-edit"></i>
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