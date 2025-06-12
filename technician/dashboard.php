<?php
define('SECURE_ACCESS', true);

require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

check_role(['technician']);

// Get technician ID
$technician_id = $_SESSION['user_id'];
$stats = [];
$assigned_tasks = [];
$recent_activities = [];

try {
    // Get technician details
    $stmt = $conn->prepare("SELECT t.* FROM technicians t WHERE t.user_id = :user_id");
    $stmt->bindParam(':user_id', $technician_id);
    $stmt->execute();
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$technician) {
        $_SESSION['error'] = "Technician profile not found.";
        redirect('profile.php');
    }
    
    // Get stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total_tasks FROM requests WHERE assigned_technician_id = :tech_id");
    $stmt->bindParam(':tech_id', $technician['id']);
    $stmt->execute();
    $stats['total_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_tasks'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as pending_tasks FROM requests 
                           WHERE assigned_technician_id = :tech_id AND status = 'assigned'");
    $stmt->bindParam(':tech_id', $technician['id']);
    $stmt->execute();
    $stats['pending_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_tasks'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as in_progress_tasks FROM requests 
                           WHERE assigned_technician_id = :tech_id AND status = 'in_progress'");
    $stmt->bindParam(':tech_id', $technician['id']);
    $stmt->execute();
    $stats['in_progress_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['in_progress_tasks'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as completed_tasks FROM requests 
                           WHERE assigned_technician_id = :tech_id AND status = 'completed'");
    $stmt->bindParam(':tech_id', $technician['id']);
    $stmt->execute();
    $stats['completed_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['completed_tasks'];
    
    // Get assigned tasks
    $stmt = $conn->prepare("SELECT r.*, u.first_name, u.last_name, c.name as college_name
                           FROM requests r
                           JOIN users u ON r.requester_id = u.id
                           JOIN colleges c ON r.college_id = c.id
                           WHERE r.assigned_technician_id = :tech_id
                           AND r.status IN ('assigned', 'in_progress')
                           ORDER BY r.priority DESC, r.deadline ASC
                           LIMIT 5");
    $stmt->bindParam(':tech_id', $technician['id']);
    $stmt->execute();
    $assigned_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activities
    $stmt = $conn->prepare("SELECT tl.*, r.title as request_title
                           FROM task_logs tl
                           JOIN requests r ON tl.request_id = r.id
                           WHERE tl.technician_id = :tech_id
                           ORDER BY tl.created_at DESC
                           LIMIT 5");
    $stmt->bindParam(':tech_id', $technician['id']);
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title>Technician Dashboard - Online Routing System</title>
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../includes/navar.php'; ?>
        <?php include '../includes/technician_sidebar.php'; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Technician Dashboard</h1>
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
                                    <h3><?php echo $stats['total_tasks'] ?? 0; ?></h3>
                                    <p>Total Tasks</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <a href="tasks.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo $stats['pending_tasks'] ?? 0; ?></h3>
                                    <p>Pending Tasks</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <a href="tasks.php?status=assigned" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?php echo $stats['in_progress_tasks'] ?? 0; ?></h3>
                                    <p>In Progress</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-spinner"></i>
                                </div>
                                <a href="tasks.php?status=in_progress" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $stats['completed_tasks'] ?? 0; ?></h3>
                                    <p>Completed Tasks</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <a href="tasks.php?status=completed" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                    </div>
                    <!-- /.row -->

                    <div class="row">
                        <div class="col-md-6">
                            <!-- Assigned Tasks -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Your Current Tasks</h3>
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
                                                    <th>Requester</th>
                                                    <th>Priority</th>
                                                    <th>Deadline</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assigned_tasks as $task): ?>
                                                <tr>
                                                    <td><?php echo $task['id']; ?></td>
                                                    <td><a href="tasks.php?action=view&id=<?php echo $task['id']; ?>"><?php echo $task['title']; ?></a></td>
                                                    <td><?php echo $task['first_name'] . ' ' . $task['last_name']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            switch($task['priority']) {
                                                                case 'high': echo 'danger'; break;
                                                                case 'medium': echo 'warning'; break;
                                                                case 'low': echo 'success'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>">
                                                            <?php echo ucfirst($task['priority']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : 'None'; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="tasks.php" class="uppercase">View All Tasks</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Recent Activity -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Your Recent Activity</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="products-list product-list-in-card pl-2 pr-2">
                                        <?php foreach ($recent_activities as $activity): ?>
                                        <li class="item">
                                            <div class="product-info">
                                                <a href="javascript:void(0)" class="product-title">
                                                    <?php echo $activity['request_title']; ?>
                                                    <span class="badge float-right bg-<?php 
                                                        switch($activity['action']) {
                                                            case 'assigned': echo 'info'; break;
                                                            case 'accepted': echo 'success'; break;
                                                            case 'declined': echo 'warning'; break;
                                                            case 'started': echo 'primary'; break;
                                                            case 'completed': echo 'success'; break;
                                                            case 'cancelled': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($activity['action']); ?>
                                                    </span>
                                                </a>
                                                <span class="product-description">
                                                    <?php echo $activity['notes'] ?? 'No additional notes'; ?>
                                                    <small class="float-right text-muted"><?php echo time_elapsed_string($activity['created_at']); ?></small>
                                                </span>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
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
</body>
</html>