<?php
define('SECURE_ACCESS', true);
session_start();
require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Check if user is logged in and has technician role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !is_technician()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Create upload directory if it doesn't exist
$upload_dir = '../uploads/profile_photos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    // Get user information
    $stmt = $conn->prepare("SELECT u.*, t.specialization, t.status, t.rating, t.bio, t.phone 
                           FROM users u 
                           LEFT JOIN technicians t ON u.id = t.user_id 
                           WHERE u.id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ../index.php');
        exit();
    }

    // Set default values if not set
    $user['specialization'] = $user['specialization'] ?? 'General';
    $user['status'] = $user['status'] ?? 'available';
    $user['rating'] = $user['rating'] ?? '0.00';
    $user['bio'] = $user['bio'] ?? 'N/A';
    $user['phone'] = $user['phone'] ?? '';
    $user['profile_photo'] = $user['profile_photo'] ?? 'assets/img/default-avatar.png';
    $user['first_name'] = $user['first_name'] ?? '';
    $user['last_name'] = $user['last_name'] ?? '';
    $user['email'] = $user['email'] ?? '';
    $user['created_at'] = $user['created_at'] ?? date('Y-m-d H:i:s');

    // Create technician profile if it doesn't exist
    if (!isset($user['specialization'])) {
        $stmt = $conn->prepare("INSERT INTO technicians (user_id, specialization, status, phone, bio) 
                               VALUES (:user_id, 'General', 'available', '', 'N/A')");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT u.*, t.specialization, t.status, t.rating, t.bio, t.phone 
                               FROM users u 
                               LEFT JOIN technicians t ON u.id = t.user_id 
                               WHERE u.id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Handle profile photo upload
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['profile_photo']['type'], $allowed_types)) {
        $error = "Invalid file type. Please upload a JPG, PNG, or GIF image.";
    } elseif ($_FILES['profile_photo']['size'] > $max_size) {
        $error = "File is too large. Maximum size is 5MB.";
    } else {
        $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
            try {
                // Delete old profile photo if exists
                if (!empty($user['profile_photo']) && file_exists('../' . $user['profile_photo'])) {
                    unlink('../' . $user['profile_photo']);
                }
                
                $profile_photo_path = 'uploads/profile_photos/' . $new_filename;
                $stmt = $conn->prepare("UPDATE users SET profile_photo = :profile_photo WHERE id = :user_id");
                $stmt->bindParam(':profile_photo', $profile_photo_path);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success = "Profile photo updated successfully";
                    $user['profile_photo'] = $profile_photo_path;
                    $_SESSION['profile_photo'] = $profile_photo_path;
                } else {
                    $error = "Error updating profile photo";
                }
            } catch(PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Error uploading file";
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['profile_photo'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $specialization = sanitize($_POST['specialization']);
    $bio = sanitize($_POST['bio']);
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "Required fields cannot be empty";
    } else {
        try {
            $conn->beginTransaction();
            
            // Update user information
            $stmt = $conn->prepare("UPDATE users SET 
                                  first_name = :first_name,
                                  last_name = :last_name,
                                  email = :email,
                                  phone = :phone,
                                  updated_at = NOW()
                                  WHERE id = :user_id");
            
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            // Update technician information
            $stmt = $conn->prepare("UPDATE technicians SET 
                                  specialization = :specialization,
                                  bio = :bio
                                  WHERE user_id = :user_id");
            
            $stmt->bindParam(':specialization', $specialization);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $conn->commit();
            
            $success = "Profile updated successfully";
            
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT u.*, t.specialization, t.status, t.rating, t.bio, t.phone 
                                   FROM users u 
                                   LEFT JOIN technicians t ON u.id = t.user_id 
                                   WHERE u.id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get task statistics
$tasks_completed = 0;
$active_tasks = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE technician_id = :user_id AND status = 'completed'");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $tasks_completed = $result['count'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE technician_id = :user_id AND status IN ('assigned', 'in_progress')");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $active_tasks = $result['count'] ?? 0;
} catch(PDOException $e) {
    // Silently fail, we'll just show 0 for these values
}

// Function to get profile photo path
function getProfilePhotoPath($user) {
    if (!empty($user['profile_photo']) && file_exists('../' . $user['profile_photo'])) {
        return '../' . $user['profile_photo'];
    }
    return '../assets/img/default-avatar.png';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title>Profile - Online Routing System</title>
    <style>
        .content-wrapper {
            background-color: #f4f6f9;
            min-height: 100vh;
        }
        .card {
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            margin-bottom: 1rem;
            border: none;
            border-radius: 0.5rem;
        }
        .card-body {
            padding: 2rem;
        }
        .profile-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem;
            border-radius: 0.5rem 0.5rem 0 0;
            text-align: center;
            position: relative;
        }
        .profile-header .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .profile-header .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            transition: all 0.3s ease;
        }
        .profile-header .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .profile-header .upload-overlay:hover {
            opacity: 1;
        }
        .profile-header .upload-overlay i {
            color: white;
            font-size: 1.5rem;
        }
        .profile-header .upload-overlay span {
            color: white;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .profile-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .profile-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .form-control {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.625rem 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.15);
        }
        .btn {
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
            font-size: 0.95rem;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 0.375rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .content-header {
            padding: 1.5rem 1.5rem 0.75rem;
        }
        .content-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #343a40;
            margin: 0;
        }
        .profile-info {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .profile-info p {
            margin-bottom: 0.5rem;
        }
        .profile-info strong {
            color: #495057;
            margin-right: 0.5rem;
        }
        #profile-photo-input {
            display: none;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin: 1.5rem 0;
        }
        .stat-card {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin: 0 0.5rem;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #007bff;
        }
        .stat-card p {
            margin: 0.5rem 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
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
                            <h1 class="m-0">My Profile</h1>
                        </div>
                        <div class="col-sm-6">
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="profile-header">
                            <form id="photo-upload-form" method="POST" enctype="multipart/form-data">
                                <input type="file" id="profile-photo-input" name="profile_photo" accept="image/*">
                                <div class="profile-image-container">
                                    <img src="<?php echo htmlspecialchars(getProfilePhotoPath($user)); ?>" 
                                         alt="Profile Photo" class="profile-image" id="profile-image-preview">
                                    <label for="profile-photo-input" class="upload-overlay">
                                        <div class="text-center">
                                            <i class="fas fa-camera"></i>
                                            <div>Change Photo</div>
                                        </div>
                                    </label>
                                </div>
                            </form>
                            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p><?php echo htmlspecialchars(ucfirst($user['specialization'])); ?> Technician</p>
                        </div>
                        <div class="card-body">
                            <div class="stats-container">
                                <div class="stat-card">
                                    <h3><?php echo $tasks_completed; ?></h3>
                                    <p>Tasks Completed</p>
                                </div>
                                <div class="stat-card">
                                    <h3><?php echo $active_tasks; ?></h3>
                                    <p>Active Tasks</p>
                                </div>
                                <div class="stat-card">
                                    <h3><?php echo number_format($user['rating'], 1); ?></h3>
                                    <p>Rating</p>
                                </div>
                            </div>

                            <div class="profile-info">
                                <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($user['status'])); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not provided'; ?></p>
                                <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                            </div>

                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="first_name">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" required
                                                   value="<?php echo isset($user['first_name']) ? htmlspecialchars($user['first_name']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="last_name">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" required
                                                   value="<?php echo isset($user['last_name']) ? htmlspecialchars($user['last_name']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           placeholder="Enter your phone number">
                                </div>

                                <div class="form-group">
                                    <label for="specialization">Specialization</label>
                                    <select class="form-control" id="specialization" name="specialization" required>
                                        <option value="General" <?php echo (isset($user['specialization']) && $user['specialization'] === 'General') ? 'selected' : ''; ?>>General</option>
                                        <option value="Electrical" <?php echo (isset($user['specialization']) && $user['specialization'] === 'Electrical') ? 'selected' : ''; ?>>Electrical</option>
                                        <option value="Plumbing" <?php echo (isset($user['specialization']) && $user['specialization'] === 'Plumbing') ? 'selected' : ''; ?>>Plumbing</option>
                                        <option value="HVAC" <?php echo (isset($user['specialization']) && $user['specialization'] === 'HVAC') ? 'selected' : ''; ?>>HVAC</option>
                                        <option value="Carpentry" <?php echo (isset($user['specialization']) && $user['specialization'] === 'Carpentry') ? 'selected' : ''; ?>>Carpentry</option>
                                        <option value="IT" <?php echo (isset($user['specialization']) && $user['specialization'] === 'IT') ? 'selected' : ''; ?>>IT</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="bio">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4"
                                              placeholder="Tell us about your skills and experience"><?php echo isset($user['bio']) ? htmlspecialchars($user['bio']) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>
    
    <?php include '../includes/scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileImage = document.getElementById('profile-image-preview');
            const photoInput = document.getElementById('profile-photo-input');
            const photoForm = document.getElementById('photo-upload-form');

            photoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileImage.src = e.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                    photoForm.submit();
                }
            });
        });
    </script>
</body>
</html>