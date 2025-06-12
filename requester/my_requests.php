<?php
define('SECURE_ACCESS', true);
session_start();
require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has requester role
if (!is_requester()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$requests = [];

try {
    // Base query
    $query = "SELECT r.*, c.name as college_name, t.first_name as tech_first_name, t.last_name as tech_last_name
              FROM requests r
              JOIN colleges c ON r.college_id = c.id
              LEFT JOIN technicians t ON r.technician_id = t.id
              WHERE r.requester_id = :user_id";
    
    // Add status filter if specified
    if ($status_filter !== 'all') {
        $query .= " AND r.status = :status";
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($status_filter !== 'all') {
        $stmt->bindParam(':status', $status_filter);
    }
    
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching requests: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title>My Requests - Online Routing System</title>
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../includes/navar.php'; ?>
        <?php include '../includes/sidebar.php'; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">My Requests</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php display_alerts(); ?>
                    
                    <!-- Status Filter -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="btn-group">
                                <a href="?status=all" class="btn btn-default <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                                <a href="?status=pending" class="btn btn-warning <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                                <a href="?status=assigned" class="btn btn-info <?php echo $status_filter === 'assigned' ? 'active' : ''; ?>">Assigned</a>
                                <a href="?status=in_progress" class="btn btn-primary <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                                <a href="?status=completed" class="btn btn-success <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                                <a href="?status=cancelled" class="btn btn-danger <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Requests Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Request List</h3>
                            <div class="card-tools">
                                <a href="new_request.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> New Request
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>College</th>
                                            <th>Status</th>
                                            <th>Technician</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($requests)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No requests found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td><?php echo $request['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['college_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            switch($request['status']) {
                                                                case 'pending': echo 'warning'; break;
                                                                case 'assigned': echo 'info'; break;
                                                                case 'in_progress': echo 'primary'; break;
                                                                case 'completed': echo 'success'; break;
                                                                case 'cancelled': echo 'danger'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>">
                                                            <?php echo ucwords(str_replace('_', ' ', $request['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if ($request['technician_id']) {
                                                            echo htmlspecialchars($request['tech_first_name'] . ' ' . $request['tech_last_name']);
                                                        } else {
                                                            echo '<span class="text-muted">Not assigned</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <button class="btn btn-danger btn-sm cancel-request" data-id="<?php echo $request['id']; ?>">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
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

        <?php include '../includes/footer.php'; ?>
    </div>
    
    <?php include '../includes/scripts.php'; ?>
    <script>
        $(document).ready(function() {
            // Cancel request button handler
            $('.cancel-request').click(function() {
                const requestId = $(this).data('id');
                const button = $(this);
                
                if (confirm('Are you sure you want to cancel this request?')) {
                    $.ajax({
                        url: '../includes/ajax/cancel_request.php',
                        method: 'POST',
                        data: { request_id: requestId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Show success message and remove row
                                alert(response.message);
                                button.closest('tr').fadeOut(400, function() {
                                    $(this).remove();
                                });
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('Error cancelling request. Please try again.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>