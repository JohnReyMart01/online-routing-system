<?php
require_once '../includes/config/config.php';
require_once '../includes/config/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get all requests with user information
$query = "SELECT r.*, u.fullname as requester_name, u.college 
          FROM requests r 
          JOIN users u ON r.user_id = u.id 
          ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background: #f4f6f9;
        }
        .card {
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            margin-bottom: 1rem;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 0.5em 0.75em;
            border-radius: 50px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending { background-color: #ffeeba; color: #856404; }
        .status-in-progress { background-color: #b8daff; color: #004085; }
        .status-completed { background-color: #c3e6cb; color: #155724; }
        .status-cancelled { background-color: #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <!-- Include Admin Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>

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

        <!-- Main Content -->
        <div class="content">
            <div class="container-fluid">
                <!-- Filters Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filters</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">College</label>
                                <select name="college" class="form-select">
                                    <option value="">All Colleges</option>
                                    <option value="CIT">CIT</option>
                                    <option value="CET">CET</option>
                                    <option value="CAS">CAS</option>
                                    <option value="COE">COE</option>
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
                            <table class="table table-hover">
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
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo htmlspecialchars($row['requester_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['college']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['priority'] === 'high' ? 'danger' : 
                                                            ($row['priority'] === 'medium' ? 'warning' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst(htmlspecialchars($row['priority'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($row['status'])); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_request.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteRequest(<?php echo $row['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No requests found</td>
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

    <!-- Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b>Version</b> 1.0.0
        </div>
        <strong>Copyright &copy; 2025 <a href="#">Online Routing System</a>.</strong> All rights reserved.
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteRequest(id) {
            if (confirm('Are you sure you want to delete this request?')) {
                // Add delete functionality here
                console.log('Deleting request:', id);
            }
        }
    </script>
</body>
</html> 