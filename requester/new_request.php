<?php
define('SECURE_ACCESS', true);
session_start();
require_once '../includes/config/db.php';
require_once '../includes/config/functions.php';
require_once '../includes/auth/authenticate.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has requester role
if (!is_requester()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$colleges = [];
$error = '';
$success = '';

try {
    // Get all colleges
    $stmt = $conn->query("SELECT id, name FROM colleges ORDER BY name");
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching colleges: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $college_id = (int)$_POST['college_id'];
    $priority = sanitize($_POST['priority']);
    
    if (empty($title) || empty($description) || empty($college_id)) {
        $error = "All fields are required";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO requests (title, description, requester_id, college_id, priority, status, created_at) 
                                  VALUES (:title, :description, :requester_id, :college_id, :priority, 'pending', NOW())");
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':requester_id', $user_id);
            $stmt->bindParam(':college_id', $college_id);
            $stmt->bindParam(':priority', $priority);
            
            if ($stmt->execute()) {
                $success = "Request submitted successfully";
                // Clear form data
                $title = $description = '';
                $college_id = $priority = null;
            } else {
                $error = "Error submitting request";
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
    <?php include '../includes/header.php'; ?>
    <title>New Request - Online Routing System</title>
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
        .btn-default {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #fff;
        }
        .btn-default:hover {
            background-color: #5a6268;
            border-color: #545b62;
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
        select.form-control {
            height: calc(2.5rem + 2px);
            cursor: pointer;
        }
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        .form-group .btn {
            margin-right: 0.75rem;
        }
        .form-group .btn i {
            margin-right: 0.5rem;
        }
        .form-control::placeholder {
            color: #adb5bd;
            opacity: 0.8;
        }
    </style>
</head>
<body class="sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php include '../includes/navar.php'; ?>
        <?php include '../includes/sidebar.php'; ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">New Request</h1>
                        </div>
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
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="title">Request Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           placeholder="Enter a descriptive title for your request"
                                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="college">College</label>
                                    <select class="form-control" id="college" name="college_id" required>
                                        <option value="">Select College</option>
                                        <?php foreach ($colleges as $college): ?>
                                            <option value="<?php echo $college['id']; ?>" 
                                                    <?php echo (isset($college_id) && $college_id == $college['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($college['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="priority">Priority Level</label>
                                    <select class="form-control" id="priority" name="priority" required>
                                        <option value="low" <?php echo (isset($priority) && $priority === 'low') ? 'selected' : ''; ?>>Low Priority</option>
                                        <option value="medium" <?php echo (isset($priority) && $priority === 'medium') ? 'selected' : ''; ?>>Medium Priority</option>
                                        <option value="high" <?php echo (isset($priority) && $priority === 'high') ? 'selected' : ''; ?>>High Priority</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="description">Request Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required
                                              placeholder="Please provide detailed information about your request"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Submit Request
                                    </button>
                                    <a href="my_requests.php" class="btn btn-default">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
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
</body>
</html>