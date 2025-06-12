<?php
// ini_set('display_errors', 1); // Removed debugging line
// ini_set('display_startup_errors', 1); // Removed debugging line
// error_reporting(E_ALL); // Removed debugging line

define('SECURE_ACCESS', true);

// --- Debugging Checkpoint Before Includes ---
// echo "<p>Checkpoint BEFORE Includes: Script started.</p>"; // Removed debugging echo
// --- End Debugging Checkpoint Before Includes ---

require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// --- Debugging Checkpoint After Includes ---
// echo "<p>Checkpoint AFTER Includes: Includes processed.</p>"; // Removed debugging echo
// --- End Debugging Checkpoint After Includes ---

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    redirect('../index.php');
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'list';
$tech_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// --- Debugging Checkpoint A ---
// echo "<p>Checkpoint A: Passed authentication and action check. Proceeding to fetch technicians.</p>"; // Removed debugging echo
// --- End Debugging Checkpoint A ---

// --- Temporarily Commented Out Action Handling Block ---
/*
// Handle different actions
if ($action === 'delete' && $tech_id > 0) {
    // Delete technician
    try {
        $conn->beginTransaction();
        
        // Check if technician has assigned tasks
        $stmt = $conn->prepare("SELECT COUNT(*) FROM requests 
                               WHERE assigned_technician_id = :tech_id 
                               AND status IN ('assigned', 'in_progress')");
        $stmt->bindParam(':tech_id', $tech_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete technician because they have assigned tasks. Reassign tasks first.");
        }
        
        // Get user_id before deleting
        $stmt = $conn->prepare("SELECT user_id FROM technicians WHERE id = :id");
        $stmt->bindParam(':id', $tech_id);
        $stmt->execute();
        $user_id = $stmt->fetchColumn();
        
        if (!$user_id) {
            throw new Exception("Technician not found.");
        }
        
        // Delete technician
        $stmt = $conn->prepare("DELETE FROM technicians WHERE id = :id");
        $stmt->bindParam(':id', $tech_id);
        $stmt->execute();
        
        // Update user role to requester
        $stmt = $conn->prepare("UPDATE users SET role = 'requester' WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Technician deleted successfully and user role updated to requester!";
        
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    redirect('technicians.php');
} elseif ($action === 'edit' && $tech_id > 0) {
    // Edit technician - handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $specialization = sanitize($_POST['specialization']);
        $availability = isset($_POST['availability']) ? 1 : 0;
        
        try {
            $stmt = $conn->prepare("UPDATE technicians 
                                   SET specialization = :specialization, 
                                       availability = :availability 
                                   WHERE id = :id");
            $stmt->bindParam(':specialization', $specialization);
            $stmt->bindParam(':availability', $availability, PDO::PARAM_INT);
            $stmt->bindParam(':id', $tech_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $_SESSION['success'] = "Technician updated successfully!";
            redirect('technicians.php');
            
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Get technician data for editing
    try {
        $stmt = $conn->prepare("SELECT t.*, u.username, u.first_name, u.last_name, u.email, u.role
                              FROM technicians t
                              JOIN users u ON t.user_id = u.id
                              WHERE t.id = :id");
        $stmt->bindParam(':id', $tech_id);
        $stmt->execute();
        $technician = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$technician) {
            $_SESSION['error'] = "Technician not found.";
            redirect('technicians.php');
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error fetching technician: " . $e->getMessage();
        redirect('technicians.php');
    }

    // For edit action, display the edit form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <?php include '../includes/header.php'; ?>
        <title>Edit Technician - Online Routing System</title>
    </head>
    <body class="sidebar-mini layout-fixed">
        <div class="wrapper">
            <?php include '../includes/admin_sidebar.php'; ?>
            <?php include '../includes/navar.php'; ?>

            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">
                <!-- Content Header (Page header) -->
                <div class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1 class="m-0">Edit Technician</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="technicians.php">Technicians</a></li>
                                    <li class="breadcrumb-item active">Edit Technician</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main content -->
                <section class="content">
                    <div class="container-fluid">
                        <?php display_alerts(); ?>
                        
                        <div class="row">
                            <div class="col-md-6 mx-auto">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Technician Details</h3>
                                    </div>
                                    <!-- /.card-header -->
                                    <!-- form start -->
                                    <form action="technicians.php?action=edit&id=<?php echo htmlspecialchars($tech_id); ?>" method="POST">
                                        <div class="card-body">
                                            <?php if ($error): ?>
                                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                            <?php endif; ?>
                                            
                                            <div class="form-group">
                                                <label>Name</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($technician['first_name'] . ' ' . $technician['last_name']); ?></p>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Username</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($technician['username']); ?></p>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Email</label>
                                                <p class="form-control-static"><?php echo htmlspecialchars($technician['email']); ?></p>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="specialization">Specialization</label>
                                                <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($technician['specialization']); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="availability" name="availability" <?php echo $technician['availability'] ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="availability">Available for new tasks</label>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- /.card-body -->

                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                            <a href="technicians.php" class="btn btn-default float-right">Cancel</a>
                                        </div>
                                    </form>
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
    <?php
    exit(); // Exit after displaying edit form
}
*/
// --- End Temporarily Commented Out Action Handling Block ---

// Get all technicians for listing
$technicians = [];
try {
    $stmt = $conn->query("SELECT t.*, u.username, u.first_name, u.last_name, u.email, u.is_active,
                         (SELECT COUNT(*) FROM requests WHERE technician_id = t.id AND status = 'assigned') as assigned_tasks,
                         (SELECT COUNT(*) FROM requests WHERE technician_id = t.id AND status = 'in_progress') as in_progress_tasks
                         FROM technicians t
                         JOIN users u ON t.user_id = u.id
                         ORDER BY u.first_name, u.last_name");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Debugging Checkpoint After Fetch --- 
    // echo "<p>Checkpoint AFTER Fetch: Data fetched.</p>"; // Removed debugging echo
    // --- End Debugging Checkpoint After Fetch ---

    // --- Debugging Output Start ---
    // echo "<h2>Debugging Technicians Array</h2>"; // Removed debugging echo
    // echo "<p>Number of items in \$technicians array: " . count($technicians) . "</p>"; // Removed debugging echo
    // if (count($technicians) > 0) { // Removed debugging echo
    //     echo "<p>First item in \$technicians array:</p>"; // Removed debugging echo
    //     echo "<pre>\n"; // Added newline to pre tag
    //     print_r($technicians[0]); // Removed debugging echo
    //     echo "\n</pre>\n"; // Added newline to pre tag
    // } else { // Removed debugging echo
    //     echo "<p>The \$technicians array is empty.</p>"; // Removed debugging echo
    // }
    // echo "<hr>"; // Removed debugging echo
    // --- Debugging Output End ---

    // exit(); // Removed temporary exit

} catch(PDOException $e) {
    echo "<p style=\"color: red;\">Database error during fetch: " . htmlspecialchars($e->getMessage()) . "</p>"; // Added echo for error message
    $_SESSION['error'] = "Error fetching technicians: " . $e->getMessage();
} catch (Exception $e) {
     echo "<p style=\"color: red;\">An unexpected error occurred: " . htmlspecialchars($e->getMessage()) . "</p>
"; // Added echo for unexpected errors
    $_SESSION['error'] = "An unexpected error occurred: " . $e->getMessage();
}

$page_title = "Manage Technicians";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?> - Online Routing System</title>
    <?php include '../includes/header_links.php'; ?>
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php 
        include '../includes/admin_navbar.php';
        include '../includes/admin_sidebar.php';
        ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Manage Technicians</h1>
                        </div>
                        <div class="col-sm-6 text-right">
                            <a href="add_technician.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Technician
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Technician List</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Username</th>
                                                    <th>Specialization</th>
                                                    <th>Assigned Tasks</th>
                                                    <th>In Progress</th>
                                                    <th>Status</th>
                                                    <th>User Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($technicians)): ?>
                                                    <?php foreach ($technicians as $tech): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($tech['id']); ?></td>
                                                            <td><?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($tech['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($tech['specialization']); ?></td>
                                                            <td class="text-center"><?php echo $tech['assigned_tasks']; ?></td>
                                                            <td class="text-center"><?php echo $tech['in_progress_tasks']; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo match($tech['status'] ?? '') {
                                                                        'available' => 'success',
                                                                        'busy' => 'warning',
                                                                        'offline' => 'danger',
                                                                        default => 'secondary'
                                                                    };
                                                                ?>">
                                                                    <?php echo htmlspecialchars(ucfirst($tech['status'] ?? 'Unknown')); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo ($tech['is_active'] ?? 0) ? 'success' : 'danger'; ?>">
                                                                    <?php echo ($tech['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group">
                                                                    <a href="view_technician.php?id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="edit_technician.php?id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <button type="button" class="btn btn-sm btn-<?php echo ($tech['status'] ?? '') === 'available' ? 'warning' : 'success'; ?> toggle-availability" 
                                                                            data-id="<?php echo $tech['id']; ?>" 
                                                                            data-current-status="<?php echo htmlspecialchars($tech['status'] ?? ''); ?>"
                                                                            title="<?php echo ($tech['status'] ?? '') === 'available' ? 'Set Unavailable' : 'Set Available'; ?>">
                                                                        <i class="fas fa-<?php echo ($tech['status'] ?? '') === 'available' ? 'ban' : 'check'; ?>"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center">No technicians found</td>
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
            </section>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <?php include '../includes/scripts.php'; ?>
    <script>
    $(document).ready(function() {
        // Toggle technician availability
        $('.toggle-availability').click(function() {
            const button = $(this);
            const techId = button.data('id');
            const currentStatus = button.data('current-status');
            const newStatus = currentStatus === 'available' ? 'busy' : 'available';
            
            if (confirm('Are you sure you want to ' + (newStatus === 'busy' ? 'mark as busy' : 'mark as available') + ' this technician?')) {
                $.ajax({
                    url: '../includes/ajax/toggle_technician_availability.php',
                    method: 'POST',
                    data: {
                        technician_id: techId,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update button appearance
                            button.data('current-status', newStatus);
                            button.removeClass('btn-warning btn-success')
                                  .addClass(newStatus === 'busy' ? 'btn-warning' : 'btn-success');
                            button.find('i').removeClass('fa-ban fa-check')
                                          .addClass(newStatus === 'busy' ? 'fa-ban' : 'fa-check');
                            
                            // Update status badge
                            const statusCell = button.closest('tr').find('td:nth-child(7) .badge');
                            statusCell.removeClass('bg-success bg-danger bg-warning')
                                    .addClass(newStatus === 'busy' ? 'bg-warning' : 'bg-success')
                                    .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                            
                            // Show success message
                            alert('Technician status updated successfully');
                        } else {
                            alert('Error updating technician status');
                        }
                    },
                    error: function() {
                        alert('Error updating technician status');
                    }
                });
            }
        });
    });
    </script>
</body>
</html>