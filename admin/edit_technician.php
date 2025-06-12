<?php
define('SECURE_ACCESS', true);

require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    redirect('../index.php');
}

$tech_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Check if technician ID is provided
if ($tech_id === 0) {
    $_SESSION['error'] = "Technician ID not specified.";
    redirect('technicians.php');
}

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $specialization = sanitize($_POST['specialization']);
    // Map checkbox value to 'status' enum
    $availability_status = isset($_POST['availability']) && $_POST['availability'] == 1 ? 'available' : 'busy';
    
    try {
        $stmt = $conn->prepare("UPDATE technicians 
                               SET specialization = :specialization, 
                                   status = :status 
                               WHERE id = :id");
        $stmt->bindParam(':specialization', $specialization);
        $stmt->bindParam(':status', $availability_status);
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

$page_title = "Edit Technician";
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
                            <h1 class="m-0">Edit Technician: <?php echo htmlspecialchars($technician['first_name'] . ' ' . $technician['last_name']); ?></h1>
                        </div>
                        <div class="col-sm-6">
                            
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
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Technician Details</h3>
                                </div>
                                <form action="edit_technician.php?id=<?php echo htmlspecialchars($tech_id); ?>" method="POST">
                                    <div class="card-body">
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
                                                <input type="hidden" name="availability" value="0"> <!-- Hidden field for unchecked checkbox -->
                                                <input type="checkbox" class="custom-control-input" id="availability" name="availability" value="1" <?php echo (isset($technician['status']) && $technician['status'] === 'available') ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="availability">Available for new tasks</label>
                                            </div>
                                        </div>
                                    </div>
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