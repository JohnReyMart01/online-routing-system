<?php
require_once '../includes/config/functions.php';
require_once '../includes/config/db.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    redirect('../index.php');
}

// Fetch statistics
try {
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Active requests
    $stmt = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status IN ('pending', 'in_progress')");
    $active_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total technicians
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'technician'");
    $total_technicians = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Completion rate
    $stmt = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM requests");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completion_rate = $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100) : 0;

    // Recent requests
    $stmt = $conn->prepare("SELECT r.*, u.first_name, u.last_name 
        FROM requests r 
        JOIN users u ON r.requester_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 5");
    $stmt->execute();
    $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activities
    $stmt = $conn->prepare("SELECT 
        'user_registration' as type,
        u.first_name,
        u.last_name,
        u.created_at as timestamp,
        CONCAT('New ', u.role, ' account created') as description
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        UNION ALL
        SELECT 
        'request_update' as type,
        u.first_name,
        u.last_name,
        r.updated_at as timestamp,
        CONCAT('Request status updated to ', r.status) as description
        FROM requests r
        JOIN users u ON r.requester_id = u.id
        WHERE r.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY timestamp DESC
        LIMIT 5");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Routing System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Theme style -->
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../includes/admin_sidebar.php'; ?>
        <?php include '../includes/admin_navbar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $total_users; ?></h3>
                                    <p>Total Users</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $active_requests; ?></h3>
                                    <p>Active Requests</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo $total_technicians; ?></h3>
                                    <p>Technicians</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo $completion_rate; ?>%</h3>
                                    <p>Completion Rate</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Requests -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Requests</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Requester</th>
                                                    <th>Status</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_requests as $request): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($request['status']) {
                                                                'pending' => 'warning',
                                                                'in_progress' => 'info',
                                                                'completed' => 'success',
                                                                'cancelled' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst($request['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo time_elapsed_string($request['created_at']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Notifications -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">System Notifications</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_activities as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></h6>
                                                <small class="text-muted"><?php echo time_elapsed_string($activity['timestamp']); ?></small>
                                            </div>
                                            <p class="mb-1">By: <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></p>
                                        </div>
                                        <?php endforeach; ?>
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