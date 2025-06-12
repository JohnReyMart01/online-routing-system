<?php
require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    redirect('../index.php');
}

$success = '';
$error = '';

// Handle user actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = sanitize($_POST['password']);
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $role = sanitize($_POST['role']);
        $college_id = intval($_POST['college_id']);

        // Validate password strength
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            try {
                // Check if username or email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $error = "Username or email already exists.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, college_id) 
                                          VALUES (:username, :email, :password, :first_name, :last_name, :role, :college_id)");
                    
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':college_id', $college_id);

                    if ($stmt->execute()) {
                        $success = "User added successfully.";
                    } else {
                        $error = "Error adding user.";
                    }
                }
            } catch(PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_user'])) {
        $user_id = intval($_POST['user_id']);
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $role = sanitize($_POST['role']);
        $college_id = intval($_POST['college_id']);
        $new_password = sanitize($_POST['new_password']);

        try {
            // Check if email is being changed to one that already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Email already exists for another user.";
            } else {
                if (!empty($new_password)) {
                    if (strlen($new_password) < 8) {
                        $error = "Password must be at least 8 characters long.";
                    } else {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, 
                            email = :email, role = :role, college_id = :college_id, password = :password 
                            WHERE id = :id");
                        $stmt->bindParam(':password', $hashed_password);
                    }
                } else {
                    // Update without password
                    $stmt = $conn->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, 
                        email = :email, role = :role, college_id = :college_id WHERE id = :id");
                }

                if (empty($error)) {
                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':college_id', $college_id);
                    $stmt->bindParam(':id', $user_id);

                    if ($stmt->execute()) {
                        $success = "User updated successfully.";
                    } else {
                        $error = "Error updating user.";
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        try {
            // Prevent deleting yourself
            if ($user_id == $_SESSION['user_id']) {
                $error = "You cannot delete your own account.";
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success = "User deleted successfully.";
                } else {
                    $error = "Error deleting user.";
                }
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Store messages in session to persist across redirect
    if ($success) $_SESSION['success'] = $success;
    if ($error) $_SESSION['error'] = $error;
    
    // Redirect to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Fetch messages from session if they exist
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Fetch all users
try {
    $stmt = $conn->query("SELECT u.*, c.name as college_name 
                         FROM users u 
                         LEFT JOIN colleges c ON u.college_id = c.id 
                         ORDER BY u.created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
    $users = [];
}

// Fetch colleges for dropdown
try {
    $stmt = $conn->query("SELECT * FROM colleges ORDER BY name");
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching colleges: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Online Routing System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Theme style -->
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../includes/admin_navbar.php'; ?>
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <div class="content-wrapper">
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

                <!-- Users Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card animate__animated animate__fadeIn">
                            <div class="card-header">
                                <h3 class="card-title">All Users</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                        <i class="fas fa-plus"></i> Add New User
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>College</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($user['role']) {
                                                                'admin' => 'danger',
                                                                'technician' => 'primary',
                                                                'requester' => 'success',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['college_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge bg-success">Active</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-info edit-btn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editUserModal<?php echo $user['id']; ?>"
                                                                    data-userid="<?php echo $user['id']; ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <button type="button" class="btn btn-sm btn-danger ms-1 delete-btn" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteUserModal<?php echo $user['id']; ?>"
                                                                        data-userid="<?php echo $user['id']; ?>">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- Edit User Modal -->
                                                <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" 
                                                     aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <form method="POST" action="">
                                                                <div class="modal-header bg-info text-white">
                                                                    <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">
                                                                        Edit User: <?php echo htmlspecialchars($user['username']); ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" 
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <?php if (isset($error) && strpos($error, 'update') !== false): ?>
                                                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <input type="hidden" name="edit_user" value="1">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="edit_first_name<?php echo $user['id']; ?>">First Name</label>
                                                                            <input type="text" class="form-control" id="edit_first_name<?php echo $user['id']; ?>" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="edit_last_name<?php echo $user['id']; ?>">Last Name</label>
                                                                            <input type="text" class="form-control" id="edit_last_name<?php echo $user['id']; ?>" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="edit_email<?php echo $user['id']; ?>">Email address</label>
                                                                            <input type="email" class="form-control" id="edit_email<?php echo $user['id']; ?>" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="edit_role<?php echo $user['id']; ?>">Role</label>
                                                                            <select class="form-control" id="edit_role<?php echo $user['id']; ?>" name="role" required>
                                                                                <option value="requester" <?php echo $user['role'] === 'requester' ? 'selected' : ''; ?>>Requester</option>
                                                                                <option value="technician" <?php echo $user['role'] === 'technician' ? 'selected' : ''; ?>>Technician</option>
                                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                            </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="edit_college_id<?php echo $user['id']; ?>">College</label>
                                                                            <select class="form-control" id="edit_college_id<?php echo $user['id']; ?>" name="college_id">
                                                                                <option value="">Select College (Optional)</option>
                                                                                <?php foreach ($colleges as $college): ?>
                                                                                    <option value="<?php echo $college['id']; ?>" <?php echo $user['college_id'] == $college['id'] ? 'selected' : ''; ?>>
                                                                                        <?php echo htmlspecialchars($college['name']); ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="edit_new_password<?php echo $user['id']; ?>">New Password (leave blank to keep current)</label>
                                                                            <input type="password" class="form-control" id="edit_new_password<?php echo $user['id']; ?>" name="new_password" placeholder="Enter new password (min 8 chars)">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Delete User Modal -->
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1" 
                                                     aria-labelledby="deleteUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <form method="POST" action="">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title" id="deleteUserModalLabel<?php echo $user['id']; ?>">
                                                                        Delete User: <?php echo htmlspecialchars($user['username']); ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close btn-close-white" 
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <?php if (isset($error) && strpos($error, 'delete') !== false): ?>
                                                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <input type="hidden" name="delete_user" value="1">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['username']); ?></strong>? This action cannot be undone.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Delete User</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add User Modal -->
                <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="add_user" value="1">
                                    <div class="mb-3">
                                        <label for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email">Email address</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                        <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="first_name">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="role">Role</label>
                                        <select class="form-control" id="role" name="role" required>
                                            <option value="requester">Requester</option>
                                            <option value="technician">Technician</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="college_id">College</label>
                                        <select class="form-control" id="college_id" name="college_id">
                                            <option value="">Select College (Optional)</option>
                                            <?php foreach ($colleges as $college): ?>
                                                <option value="<?php echo $college['id']; ?>"><?php echo htmlspecialchars($college['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Add User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- System Notifications -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">System Notifications</h3>
                            </div>
                            <div class="card-body">
                                <div class="notification">
                                    <h5>New technician account created</h5>
                                    <p class="text-muted">2 hours ago</p>
                                    <p>By: Rey Mart</p>
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
    <script>
    $(document).ready(function() {
        // Initialize all Bootstrap tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Enhanced modal handling
        $('.modal').on('show.bs.modal', function (event) {
            // Remove any existing modal backdrops
            $('.modal-backdrop').remove();
            
            // Ensure proper z-index
            $(this).css('z-index', 1050);
            
            // Center modals vertically
            $(this).css('display', 'block');
            var $dialog = $(this).find(".modal-dialog");
            var offset = ($(window).height() - $dialog.height()) / 2;
            $dialog.css("margin-top", offset);
        });
        
        // Handle modal backdrop
        $('.modal').on('shown.bs.modal', function () {
            // Ensure backdrop is properly positioned
            $('.modal-backdrop').css('z-index', 1040);
            
            // Auto-focus first input
            $(this).find('input:visible:first').focus();
        });
        
        // Clean up when modal is hidden
        $('.modal').on('hidden.bs.modal', function () {
            // Remove the modal backdrop
            $('.modal-backdrop').remove();
            
            // Reset form and clear messages
            $(this).find('form').trigger('reset');
            $(this).find('.alert').remove();
        });
        
        // Handle edit button clicks
        $('.edit-btn').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('userid');
            var modal = $('#editUserModal' + userId);
            
            // Remove any existing modal backdrops
            $('.modal-backdrop').remove();
            
            // Show the modal
            modal.modal('show');
        });
        
        // Handle delete button clicks
        $('.delete-btn').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('userid');
            var modal = $('#deleteUserModal' + userId);
            
            // Remove any existing modal backdrops
            $('.modal-backdrop').remove();
            
            // Show the modal
            modal.modal('show');
        });
        
        // Show specific modal if there are errors
        <?php if ($error): ?>
            $(function() {
                // Remove any existing modal backdrops
                $('.modal-backdrop').remove();
                
                <?php if (isset($_POST['add_user'])): ?>
                    $('#addUserModal').modal('show');
                <?php elseif (isset($_POST['edit_user'])): ?>
                    $('#editUserModal<?php echo $_POST['user_id'] ?? ''; ?>').modal('show');
                <?php elseif (isset($_POST['delete_user'])): ?>
                    $('#deleteUserModal<?php echo $_POST['user_id'] ?? ''; ?>').modal('show');
                <?php endif; ?>
            });
        <?php endif; ?>
    });

    // Success message handling
    <?php if ($success): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide all open modals
        $('.modal').modal('hide');
        
        // Remove any existing modal backdrops
        $('.modal-backdrop').remove();
        
        // Reset all forms
        $('form').trigger('reset');
        
        // Clear any existing alerts
        $('.alert').remove();
    });
    <?php endif; ?>
    </script>

    <!-- Add this CSS to ensure proper modal display -->
    <style>
    /* Make Bootstrap modal wider and improve header appearance */
    .modal-dialog {
    max-width: 600px;
    margin: 1.75rem auto;
    height: calc(100% - 3.5rem);
    display: flex;
    flex-direction: column;
    justify-content: center;
    }
    .modal-header {
        padding: 1rem 1rem;
        border-top-left-radius: .5rem;
        border-top-right-radius: .5rem;
        background: linear-gradient(90deg, #1976d2 0%, #21cbf3 100%);
        color: #fff;
    }
    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        white-space: normal;
        word-break: break-word;
    }
    .btn-close.btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    /* Make modal use more vertical space and allow scrolling if content is long */
    .modal-dialog {
        max-width: 600px;
        margin: 1.75rem auto;
        height: calc(100% - 3.5rem);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .modal-content {
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        flex-shrink: 0;
    }

    .modal-body {
        overflow-y: auto;
        flex: 1 1 auto;
        min-height: 100px;
        max-height: 60vh;
        padding-bottom: 1rem;
    }

    /* Ensure main content is not hidden under the fixed navbar */
    .content-wrapper,
    .container-fluid {
        padding-top: 0 !important;
    }

    .main-header.navbar,
    .navbar,
    nav.navbar,
    .navbar.navbar-expand,
    .navbar.navbar-light,
    .navbar.navbar-dark {
        min-height: 56px !important;
        height: 56px !important;
        line-height: 56px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
    .navbar .navbar-brand,
    .navbar .navbar-nav .nav-link,
    .navbar .navbar-text {
        line-height: 56px !important;
        height: 56px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
    .navbar .container,
    .navbar .container-fluid {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        height: 56px !important;
    }
    </style>
</body>
</html> 
