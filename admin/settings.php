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

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        
        // Update system settings
        $settings = [
            'site_name' => sanitize($_POST['site_name']),
            'site_description' => sanitize($_POST['site_description']),
            'max_file_size' => intval($_POST['max_file_size']),
            'allowed_file_types' => sanitize($_POST['allowed_file_types']),
            'request_auto_assign' => isset($_POST['request_auto_assign']) ? 1 : 0,
            'enable_email_notifications' => isset($_POST['enable_email_notifications']) ? 1 : 0,
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                  VALUES (:key, :value) 
                                  ON DUPLICATE KEY UPDATE setting_value = :value");
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
        
        $conn->commit();
        $success = "Settings updated successfully!";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
try {
    $stmt = $conn->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch(PDOException $e) {
    $error = "Error fetching settings: " . $e->getMessage();
}

$page_title = "System Settings";
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
                            <h1 class="m-0">System Settings</h1>
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
                        <div class="col-md-8">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">General Settings</h3>
                                </div>
                                <form action="settings.php" method="POST">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="site_name">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="site_description">Site Description</label>
                                            <textarea class="form-control" id="site_description" name="site_description"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="max_file_size">Maximum File Size (MB)</label>
                                            <input type="number" class="form-control" id="max_file_size" name="max_file_size" value="<?php echo htmlspecialchars($settings['max_file_size'] ?? 5); ?>" min="1">
                                        </div>
                                        <div class="form-group">
                                            <label for="allowed_file_types">Allowed File Types (comma-separated)</label>
                                            <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" value="<?php echo htmlspecialchars($settings['allowed_file_types'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx'); ?>">
                                            <small class="form-text text-muted">Example: jpg,jpeg,png,gif,pdf,doc,docx</small>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-8">
                             <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">System Features</h3>
                                </div>
                                 <form action="settings.php" method="POST">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="hidden" name="request_auto_assign" value="0"> <!-- Hidden field for unchecked checkbox -->
                                                <input type="checkbox" class="custom-control-input" id="request_auto_assign" name="request_auto_assign" value="1" <?php echo ($settings['request_auto_assign'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="request_auto_assign">Enable Automatic Request Assignment</label>
                                            </div>
                                            <small class="form-text text-muted">Automatically assign requests to available technicians</small>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="hidden" name="enable_email_notifications" value="0"> <!-- Hidden field for unchecked checkbox -->
                                                <input type="checkbox" class="custom-control-input" id="enable_email_notifications" name="enable_email_notifications" value="1" <?php echo ($settings['enable_email_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="enable_email_notifications">Enable Email Notifications</label>
                                            </div>
                                            <small class="form-text text-muted">Send email notifications for request updates</small>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                 <input type="hidden" name="maintenance_mode" value="0"> <!-- Hidden field for unchecked checkbox -->
                                                <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="maintenance_mode">Maintenance Mode</label>
                                            </div>
                                            <small class="form-text text-muted">Enable maintenance mode to restrict access to administrators only</small>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
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