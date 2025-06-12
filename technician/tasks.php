<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/config/setup_database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../login.php');
    exit();
}

// Get technician's tasks
$technician_id = $_SESSION['user_id'];

try {
    // Debug information
    error_log("Starting tasks.php execution");
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Technician ID: " . $technician_id);
    
    // Verify database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    error_log("Database connection successful");
    
    // First verify the technician exists
    $check_query = "SELECT id, first_name, last_name FROM users WHERE id = :technician_id AND role = 'technician'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    $technician = $check_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$technician) {
        error_log("Technician not found in database. ID: " . $technician_id);
        throw new Exception("Technician not found in database");
    }
    
    error_log("Technician found: " . $technician['first_name'] . ' ' . $technician['last_name']);

    // Check if there are any tasks in the database
    $check_tasks_query = "SELECT COUNT(*) as total FROM requests WHERE technician_id IS NOT NULL";
    $check_tasks_stmt = $conn->query($check_tasks_query);
    $total_tasks = $check_tasks_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    error_log("Total tasks in database: " . $total_tasks);

    // Get tasks with proper error handling
    $query = "SELECT r.*, u.first_name, u.last_name, c.name as college_name 
              FROM requests r 
              LEFT JOIN users u ON r.requester_id = u.id 
              LEFT JOIN colleges c ON r.college_id = c.id 
              WHERE r.technician_id = :technician_id 
              ORDER BY 
                CASE 
                    WHEN r.status = 'pending' THEN 1
                    WHEN r.status = 'in_progress' THEN 2
                    WHEN r.status = 'completed' THEN 3
                END,
                r.created_at DESC";

    error_log("Executing query: " . $query);
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Number of tasks found for technician: " . count($tasks));

    // If no tasks exist, create a test task
    if (count($tasks) === 0) {
        error_log("No tasks found, creating test task");
        // Get a random requester
        $requester_query = "SELECT id FROM users WHERE role = 'requester' LIMIT 1";
        $requester_stmt = $conn->query($requester_query);
        $requester = $requester_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$requester) {
            error_log("No requesters found in database");
            throw new Exception("No requesters available to create test task");
        }
        
        // Get a random college
        $college_query = "SELECT id FROM colleges LIMIT 1";
        $college_stmt = $conn->query($college_query);
        $college = $college_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$college) {
            error_log("No colleges found in database");
            throw new Exception("No colleges available to create test task");
        }
        
        // Insert test task
        $insert_query = "INSERT INTO requests (requester_id, technician_id, college_id, title, description, priority, status, created_at) 
                       VALUES (:requester_id, :technician_id, :college_id, 'Test Task', 'This is a test task', 'medium', 'pending', NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(':requester_id', $requester['id'], PDO::PARAM_INT);
        $insert_stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
        $insert_stmt->bindParam(':college_id', $college['id'], PDO::PARAM_INT);
        $insert_stmt->execute();
        
        error_log("Test task created successfully");
        
        // Fetch the newly created task
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Initialize counts
    $counts = [
        'total' => count($tasks),
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0
    ];

    // Count tasks by status
    foreach ($tasks as $task) {
        if (isset($task['status'])) {
            $counts[$task['status']]++;
        }
    }

} catch(Exception $e) {
    // Log the specific error
    error_log("Error in tasks.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Set user-friendly error message
    $_SESSION['error'] = "Unable to fetch tasks. Please try again later. Error: " . $e->getMessage();
    
    // Initialize empty arrays
    $tasks = [];
    $counts = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0
    ];
}

// Set page title
$page_title = "My Tasks";

// Include header
include_once '../includes/header.php';
include_once '../includes/navar.php';
include_once '../includes/technician_sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">My Tasks</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Task Statistics -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $counts['total']; ?></h3>
                            <p>Total Tasks</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $counts['pending']; ?></h3>
                            <p>Pending Tasks</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?php echo $counts['in_progress']; ?></h3>
                            <p>In Progress</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $counts['completed']; ?></h3>
                            <p>Completed Tasks</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Tasks</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Requester</th>
                                    <th>College</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No tasks found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($task['id']); ?></td>
                                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                                        <td><?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($task['college_name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $task['priority'] === 'high' ? 'danger' : 
                                                    ($task['priority'] === 'medium' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($task['priority'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $task['status'] === 'completed' ? 'success' : 
                                                    ($task['status'] === 'in_progress' ? 'primary' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($task['status']))); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($task['created_at'])); ?></td>
                                        <td>
                                            <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($task['status'] === 'pending'): ?>
                                            <a href="update_status.php?id=<?php echo $task['id']; ?>&status=in_progress" class="btn btn-sm btn-primary">
                                                <i class="fas fa-play"></i> Start
                                            </a>
                                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                            <a href="update_status.php?id=<?php echo $task['id']; ?>&status=completed" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Complete
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.small-box {
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
}

.small-box > .inner {
    padding: 10px;
}

.small-box h3 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
    white-space: nowrap;
    padding: 0;
}

.small-box p {
    font-size: 1rem;
}

.small-box .icon {
    color: rgba(0,0,0,.15);
    z-index: 0;
}

.small-box .icon > i {
    font-size: 70px;
    position: absolute;
    right: 15px;
    top: 15px;
    transition: transform .3s linear;
}

.small-box:hover .icon > i {
    transform: scale(1.1);
}

.table th {
    background-color: #f4f6f9;
}

.badge {
    padding: 0.5em 0.75em;
    font-size: 0.85em;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.btn-sm i {
    margin-right: 0.25rem;
}
</style>

<?php include_once '../includes/footer.php'; ?>