<?php
require_once __DIR__ . '/includes/config/db.php';
require_once __DIR__ . '/includes/config/functions.php';
require_once __DIR__ . '/includes/auth/authenticate.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    
    // Handle profile photo upload
    $profile_photo = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/profile_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $upload_result = upload_file($_FILES['profile_photo'], $upload_dir);
        if (!$upload_result['success']) {
            $error = $upload_result['message'];
        } else {
            $profile_photo = $upload_result['path'];
            
            // Delete old profile photo if exists
            $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $old_photo = $stmt->fetchColumn();
            
            if ($old_photo && file_exists(__DIR__ . '/' . $old_photo)) {
                unlink(__DIR__ . '/' . $old_photo);
            }
        }
    }
    
    if (!$error) {
        try {
            $sql = "UPDATE users 
                   SET first_name = :first_name, 
                       last_name = :last_name, 
                       email = :email, 
                       updated_at = NOW()";
            
            $params = [
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':id' => $user_id
            ];
            
            if ($profile_photo) {
                $sql .= ", profile_photo = :profile_photo";
                $params[':profile_photo'] = $profile_photo;
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            if ($profile_photo) {
                $_SESSION['profile_photo'] = $profile_photo;
            }
            
            $success = "Profile updated successfully!";
            
        } catch(PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = sanitize($_POST['current_password']);
    $new_password = sanitize($_POST['new_password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $hashed_password = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $hashed_password)) {
                $error = "Current password is incorrect.";
            } else {
                // Update password
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $new_hashed_password);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                
                $success = "Password changed successfully!";
            }
        } catch(PDOException $e) {
            $error = "Error changing password: " . $e->getMessage();
        }
    }
}

// Get user data
try {
    $stmt = $conn->prepare("SELECT u.*, c.name as college_name 
                           FROM users u
                           LEFT JOIN colleges c ON u.college_id = c.id
                           WHERE u.id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        redirect('/online-routing-system/admin/dashboard.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching user data: " . $e->getMessage();
    redirect('/online-routing-system/admin/dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <title>My Profile - Online Routing System</title>
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include __DIR__ . '/includes/navbar.php'; ?>
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">My Profile</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/online-routing-system/admin/dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Profile</li>
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
                        <div class="col-md-4">
                            <!-- Profile Card -->
                            <div class="card card-primary card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <?php if ($user['profile_photo']): ?>
                                            <img class="profile-user-img img-fluid img-circle" src="/online-routing-system/<?php echo $user['profile_photo']; ?>" alt="User profile picture">
                                        <?php else: ?>
                                            <div class="profile-user-img img-fluid img-circle bg-primary d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                                <span class="text-white" style="font-size: 3rem;"><?php echo strtoupper(substr($user['first_name'], 0, 1) . strtoupper(substr($user['last_name'], 0, 1))); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="profile-username text-center"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h3>
                                    
                                    <p class="text-muted text-center">
                                        <span class="badge bg-<?php 
                                            switch($user['role']) {
                                                case 'admin': echo 'danger'; break;
                                                case 'technician': echo 'info'; break;
                                                case 'requester': echo 'success'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </p>
                                    
                                    <ul class="list-group list-group-unbordered mb-3">
                                        <li class="list-group-item">
                                            <b>Username</b> <a class="float-right"><?php echo $user['username']; ?></a>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Email</b> <a class="float-right"><?php echo $user['email']; ?></a>
                                        </li>
                                        <?php if ($user['college_name']): ?>
                                            <li class="list-group-item">
                                                <b>College</b> <a class="float-right"><?php echo $user['college_name']; ?></a>
                                            </li>
                                        <?php endif; ?>
                                        <li class="list-group-item">
                                            <b>Member Since</b> <a class="float-right"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <!-- Profile Update Form -->
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Update Profile</h3>
                                </div>
                                <form action="/online-routing-system/profile.php" method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <?php if ($error && isset($_POST['update_profile'])): ?>
                                            <div class="alert alert-danger"><?php echo $error; ?></div>
                                        <?php endif; ?>
                                        <?php if ($success && isset($_POST['update_profile'])): ?>
                                            <div class="alert alert-success"><?php echo $success; ?></div>
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label for="first_name">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="last_name">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="profile_photo">Profile Photo</label>
                                            <div class="input-group">
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="profile_photo" name="profile_photo" accept="image/*">
                                                    <label class="custom-file-label" for="profile_photo">Choose file</label>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Leave empty to keep current photo</small>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Change Password Form -->
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Change Password</h3>
                                </div>
                                <form action="/online-routing-system/profile.php" method="POST">
                                    <div class="card-body">
                                        <?php if ($error && isset($_POST['change_password'])): ?>
                                            <div class="alert alert-danger"><?php echo $error; ?></div>
                                        <?php endif; ?>
                                        <?php if ($success && isset($_POST['change_password'])): ?>
                                            <div class="alert alert-success"><?php echo $success; ?></div>
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label for="current_password">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="new_password">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <small class="form-text text-muted">Password must be at least 8 characters long</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="confirm_password">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>
    
    <?php include __DIR__ . '/includes/scripts.php'; ?>
    
    <!-- bs-custom-file-input -->
    <script src="/online-routing-system/assets/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <script>
        $(document).ready(function () {
            bsCustomFileInput.init();
        });
    </script>
</body>
</html>