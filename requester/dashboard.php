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
$stats = [];
$recent_requests = [];
$pending_requests = [];

try {
    // Get user stats
    $stmt = $conn->prepare("SELECT 
                           COUNT(*) as total_requests,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                           SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_requests,
                           SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests
                           FROM requests 
                           WHERE requester_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent requests (last 5)
    $stmt = $conn->prepare("SELECT r.*, c.name as college_name
                          FROM requests r
                          JOIN colleges c ON r.college_id = c.id
                          WHERE r.requester_id = :user_id
                          ORDER BY r.created_at DESC
                          LIMIT 5");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending requests that can be cancelled
    $stmt = $conn->prepare("SELECT r.*, c.name as college_name
                          FROM requests r
                          JOIN colleges c ON r.college_id = c.id
                          WHERE r.requester_id = :user_id
                          AND r.status = 'pending'
                          ORDER BY r.created_at DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title>Requester Dashboard - Online Routing System</title>
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
                            <h1 class="m-0 fw-bold">Requester Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php display_alerts(); ?>
                    
                    <!-- Small boxes (Stat box) -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $stats['total_requests'] ?? 0; ?></h3>
                                    <p>Total Requests</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <a href="my_requests.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo $stats['pending_requests'] ?? 0; ?></h3>
                                    <p>Pending Requests</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <a href="my_requests.php?status=pending" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?php echo $stats['in_progress_requests'] ?? 0; ?></h3>
                                    <p>In Progress</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-spinner"></i>
                                </div>
                                <a href="my_requests.php?status=in_progress" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $stats['completed_requests'] ?? 0; ?></h3>
                                    <p>Completed</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <a href="my_requests.php?status=completed" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                    </div>
                    <!-- /.row -->

                    <div class="row">
                        <div class="col-md-6">
                            <!-- Recent Requests -->
                            <div class="card animate__animated animate__fadeIn">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Requests</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Title</th>
                                                    <th>College</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_requests as $request): ?>
                                                <tr>
                                                    <td><?php echo $request['id']; ?></td>
                                                    <td><?php echo $request['title']; ?></td>
                                                    <td><?php echo $request['college_name']; ?></td>
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
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="my_requests.php" class="uppercase">View All Requests</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Pending Requests -->
                            <div class="card animate__animated animate__fadeIn">
                                <div class="card-header">
                                    <h3 class="card-title">Pending Requests</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($pending_requests)): ?>
                                        <div class="p-3 text-center text-muted">
                                            No pending requests that can be cancelled.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Title</th>
                                                        <th>College</th>
                                                        <th>Created</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pending_requests as $request): ?>
                                                    <tr>
                                                        <td><?php echo $request['id']; ?></td>
                                                        <td><?php echo $request['title']; ?></td>
                                                        <td><?php echo $request['college_name']; ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-danger cancel-request" data-id="<?php echo $request['id']; ?>">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="new_request.php" class="btn btn-primary">
                                        <i class="fas fa-plus mr-2"></i> Create New Request
                                    </a>
                                </div>
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
                        url: '../../includes/ajax/cancel_request.php',
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

    <style>
    /* Dashboard specific styles */
    .content-wrapper {
        background-color: #f4f6f9;
    }

    .content-header {
        padding-top: 0px;
    }

    .content-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #343a40;
        margin: 0;
    }

    .content {
        padding: 0;
    }

    /* Small box styles */
    .small-box {
        border-radius: 0.5rem;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        transition: transform 0.2s ease-in-out;
        margin-top: 1rem;
    }

    .small-box:hover {
        transform: translateY(-3px);
    }

    .small-box .inner {
        padding: 1.5rem;
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
        margin: 0;
    }

    .small-box .icon {
        color: rgba(0, 0, 0, 0.15);
        z-index: 0;
    }

    .small-box .icon i {
        font-size: 4rem;
        top: 20px;
        right: 20px;
    }

    .small-box-footer {
        padding: 0.75rem 1.5rem;
        background: rgba(0, 0, 0, 0.1);
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: 0 0 0.5rem 0.5rem;
    }

    .small-box-footer:hover {
        background: rgba(0, 0, 0, 0.15);
        color: #fff;
    }

    /* Card styles */
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        margin-bottom: 1.5rem;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid rgba(0,0,0,.125);
        padding: 1rem 1.5rem;
    }

    .card-header h3 {
        font-size: 1.1rem;
        font-weight: 500;
        margin: 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }

    .table td, .table th {
        padding: 0.75rem 1rem;
        vertical-align: middle;
    }

    .card-footer {
        padding: 1rem 1.5rem;
        background-color: #fff;
        border-top: 1px solid rgba(0,0,0,.125);
    }

    /* Badge styles */
    .badge {
        padding: 0.5em 0.75em;
        font-weight: 500;
    }

    /* Button styles */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .content {
            padding: 0 1rem 1rem;
        }
        
        .content-header {
            padding: 1rem;
        }
        
        .small-box .inner {
            padding: 1rem;
        }
        
        .small-box h3 {
            font-size: 1.8rem;
        }
    }
    </style>
</body>
</html>