<?php
session_start();
require_once 'includes/config/db.php';
require_once 'includes/config/functions.php';

if (is_logged_in()) {
    redirect_role_based();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Log the POST data for debugging
    error_log("Registration POST data: " . print_r($_POST, true));
    
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $college_id = sanitize($_POST['college_id']);
    $role = 'requester'; // Default role
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($college_id)) {
        $error = "All fields are required.";
        error_log("Registration error: Missing required fields");
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
        error_log("Registration error: Passwords do not match");
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
        error_log("Registration error: Password too short");
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists.";
                error_log("Registration error: Username or email already exists");
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Start transaction
                $conn->beginTransaction();
                
                try {
                    // Insert new user
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, college_id, role, created_at) 
                                           VALUES (:username, :email, :password, :first_name, :last_name, :college_id, :role, NOW())");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    $stmt->bindParam(':college_id', $college_id);
                    $stmt->bindParam(':role', $role);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $success = "Registration successful! You can now login.";
                        $_SESSION['success'] = $success;
                        error_log("Registration successful for user: " . $username);
                        redirect('login.php');
                    } else {
                        throw new PDOException("Error executing user insert");
                    }
                } catch(PDOException $e) {
                    $conn->rollBack();
                    throw $e;
                }
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
            error_log("Registration database error: " . $e->getMessage());
        }
    }
}

// Get colleges for dropdown
$colleges = [];
try {
    $stmt = $conn->query("SELECT * FROM colleges ORDER BY name");
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching colleges: " . $e->getMessage();
    error_log("Error fetching colleges: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header.php'; ?>
    <title>Register - Online Routing System</title>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg animate__animated animate__fadeIn">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/img/logo.png" alt="Logo" class="img-fluid mb-3" style="max-height: 80px;">
                            <h2 class="card-title">Create an Account</h2>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form action="register.php" method="POST" id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                     <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                     <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                     <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="college_id" class="form-label">College</label>
                                 <div class="input-group">
                                     <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <select class="form-select" id="college_id" name="college_id" required>
                                        <option value="">Select College</option>
                                        <?php foreach ($colleges as $college): ?>
                                            <option value="<?php echo htmlspecialchars($college['id']); ?>">
                                                <?php echo htmlspecialchars($college['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Register</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="index.php?showLogin=true">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>