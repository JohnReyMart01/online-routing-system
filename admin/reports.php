<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SECURE_ACCESS', true);

require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get statistics
$total_requests = 0;
$avg_completion_time = 0;
$requests_by_status = [];
$requests_by_college = [];

try {
    // Total requests
    $query = "SELECT COUNT(*) as total FROM requests WHERE created_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $total_requests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Average completion time
    $query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_time 
              FROM requests 
              WHERE status = 'completed' 
              AND created_at BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $avg_completion_time = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_time'] ?? 0, 1);

    // Requests by status
    $query = "SELECT status, COUNT(*) as count 
              FROM requests 
              WHERE created_at BETWEEN ? AND ?
              GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $requests_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Requests by college
    $query = "SELECT c.name as college, COUNT(*) as count 
              FROM requests r 
              JOIN colleges c ON r.college_id = c.id 
              WHERE r.created_at BETWEEN ? AND ?
              GROUP BY c.name";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $requests_by_college = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching report data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Online Routing System</title>
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
                            <h1 class="m-0">Reports</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <!-- Date Range Filter -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Date Range</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-6 col-md-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $total_requests; ?></h3>
                                    <p>Total Requests</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $avg_completion_time; ?></h3>
                                    <p>Avg. Completion Time (hours)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Requests by Status</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Requests by College</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="collegeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($status_data)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($status_data)); ?>,
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // College Chart
        const collegeCtx = document.getElementById('collegeChart').getContext('2d');
        new Chart(collegeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($college_data)); ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?php echo json_encode(array_values($college_data)); ?>,
                    backgroundColor: '#17a2b8'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>


