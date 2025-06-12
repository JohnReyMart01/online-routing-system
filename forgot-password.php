<?php
require_once 'includes/config/db.php';
require_once 'includes/config/functions.php';

$error = '';
$success = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check if email exists in database
            $stmt = $conn->prepare("SELECT id, username, first_name FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (:user_id, :token, :expiry)");
                $stmt->bindParam(':user_id', $user['id']);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expiry', $expiry);
                
                if ($stmt->execute()) {
                    // Send reset email
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
                    $to = $email;
                    $subject = "Password Reset Request";
                    $message = "Dear " . $user['first_name'] . ",\n\n";
                    $message .= "You have requested to reset your password. Click the link below to reset your password:\n\n";
                    $message .= $reset_link . "\n\n";
                    $message .= "This link will expire in 1 hour.\n\n";
                    $message .= "If you did not request this password reset, please ignore this email.\n\n";
                    $message .= "Best regards,\nOnline Routing System Team";
                    $headers = "From: noreply@routing-system.edu";
                    
                    if (mail($to, $subject, $message, $headers)) {
                        $success = "Password reset instructions have been sent to your email.";
                    } else {
                        $error = "Failed to send reset email. Please try again.";
                    }
                } else {
                    $error = "Error processing your request. Please try again.";
                }
            } else {
                $error = "No account found with that email address.";
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Online Routing System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="forgot-password-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card animate__animated animate__fadeIn">
                    <div class="auth-header">
                        <h3><i class="fas fa-key me-2"></i>Forgot Password</h3>
                    </div>
                    <div class="auth-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-4">Enter your email address and we'll send you instructions to reset your password.</p>
                        
                        <form action="forgot-password.php" method="POST">
                            <div class="form-group mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Send Reset Link</button>
                            <div class="text-center">
                                <a href="index.php" class="text-decoration-none" onclick="window.location.href='index.php'; return false;">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add a parameter to the URL when going back to index
        document.querySelector('.text-decoration-none').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'index.php?showLogin=true';
        });
    </script>
</body>
</html> 