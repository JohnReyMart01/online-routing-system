<?php
// Common functions used throughout the application

/**
 * Get the base URL of the application
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = $protocol . '://' . $host . $script_name;
    return rtrim($base_url, '/');
}

/**
 * Sanitize input data with improved security
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Secure redirect with URL validation
 */
function redirect($location) {
    // Validate the URL to prevent header injection
    if (strpos($location, "\n") !== false || strpos($location, "\r") !== false) {
        die('Invalid redirect URL');
    }
    
    // Handle relative paths
    if (!preg_match('/^https?:\/\//i', $location)) {
        // Get the current script's directory
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        // Remove any leading slashes from the location
        $location = ltrim($location, '/');
        // Combine the paths
        $location = $current_dir . '/' . $location;
    }
    
    if (!headers_sent()) {
        header("Location: $location");
        exit();
    } else {
        echo "<script>window.location.href='$location';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$location'></noscript>";
        exit();
    }
}

/**
 * Check if user is authenticated
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verify user role with session validation
 */
function check_role($allowed_roles) {
    if (!is_logged_in()) {
        $_SESSION['error'] = "Please login to access this page.";
        header("Location: ../../index.php");
        exit();
    }
    
    if (!isset($_SESSION['role'])) {
        $_SESSION['error'] = "User role not set.";
        header("Location: ../../index.php");
        exit();
    }
    
    if (!in_array($_SESSION['role'], (array)$allowed_roles)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: ../../index.php");
        exit();
    }
}

/**
 * Display flash messages with improved styling
 */
function display_alerts() {
    $html = '';
    $alert_types = ['success', 'error', 'warning', 'info'];
    
    foreach ($alert_types as $type) {
        if (isset($_SESSION[$type])) {
            $icon = '';
            switch ($type) {
                case 'success': $icon = 'check-circle'; break;
                case 'error': $icon = 'exclamation-triangle'; break;
                case 'warning': $icon = 'exclamation-circle'; break;
                case 'info': $icon = 'info-circle'; break;
            }
            
            $html .= <<<HTML
<div class="alert alert-{$type} alert-dismissible fade show" role="alert">
    <i class="fas fa-{$icon} me-2"></i>
    {$_SESSION[$type]}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
HTML;
            unset($_SESSION[$type]);
        }
    }
    
    return $html;
}

/**
 * Secure file upload with more validation
 */
function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    // Debug input
    error_log("Upload file called with target_dir: " . $target_dir);
    
    // Validate input
    if (!isset($file['error']) || is_array($file['error'])) {
        error_log("Invalid file parameters");
        return ['success' => false, 'message' => 'Invalid file parameters.'];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            error_log("File exceeds maximum size");
            return ['success' => false, 'message' => 'File exceeds maximum size.'];
        case UPLOAD_ERR_PARTIAL:
            error_log("File only partially uploaded");
            return ['success' => false, 'message' => 'File only partially uploaded.'];
        case UPLOAD_ERR_NO_FILE:
            error_log("No file was uploaded");
            return ['success' => false, 'message' => 'No file was uploaded.'];
        default:
            error_log("Unknown upload error: " . $file['error']);
            return ['success' => false, 'message' => 'Unknown upload error.'];
    }
    
    // Verify file is actually uploaded
    if (!is_uploaded_file($file['tmp_name'])) {
        error_log("Possible file upload attack");
        return ['success' => false, 'message' => 'Possible file upload attack.'];
    }
    
    // Sanitize filename
    $filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($file['name']));
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($file_ext, $allowed_types)) {
        error_log("Invalid file type: " . $file_ext);
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
    }
    
    // Create secure target directory if needed
    if (!file_exists($target_dir)) {
        error_log("Creating directory: " . $target_dir);
        if (!mkdir($target_dir, 0755, true)) {
            error_log("Failed to create directory: " . $target_dir);
            return ['success' => false, 'message' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate unique filename
    $target_file = rtrim($target_dir, '/') . '/' . uniqid() . '_' . $filename;
    error_log("Target file path: " . $target_file);
    
    // Check MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $valid_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    if (!in_array($mime, $valid_mimes) || $file_ext !== array_search($mime, $valid_mimes)) {
        error_log("File type mismatch. MIME: " . $mime . ", Extension: " . $file_ext);
        return ['success' => false, 'message' => 'File type mismatch.'];
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        error_log("File moved successfully to: " . $target_file);
        
        // Convert the file path to a web-accessible URL
        $web_path = str_replace('../../', '/online-routing-system/', $target_file);
        error_log("Web path: " . $web_path);
        
        // Ensure the web path starts with /online-routing-system/
        if (!str_starts_with($web_path, '/online-routing-system/')) {
            $web_path = '/online-routing-system/' . ltrim($web_path, '/');
        }
        
        return [
            'success' => true, 
            'path' => $target_file,
            'web_path' => $web_path
        ];
    }
    
    error_log("Failed to move uploaded file");
    return ['success' => false, 'message' => 'File upload failed.'];
}

/**
 * Improved time ago function with localization support
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $units = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    $parts = [];
    foreach ($units as $k => $v) {
        if ($diff->$k) {
            $parts[] = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        }
    }

    if (!$full) $parts = array_slice($parts, 0, 1);
    return $parts ? implode(', ', $parts) . ' ago' : 'just now';
}

/**
 * Check for feedback with prepared statement
 */
function has_feedback($conn, $request_id) {
    try {
        $stmt = $conn->prepare("SELECT 1 FROM feedback WHERE request_id = :request_id LIMIT 1");
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("Feedback check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Secure logout with session cleanup
 */
function handle_logout() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login page with show_login parameter
    header('Location: http://localhost:8000/index.php?show_login=1');
    exit();
}

/**
 * Enhanced role-based redirect with path security
 */
function redirect_role_based() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
    
    $role_pages = [
        'admin' => 'admin/dashboard.php',
        'technician' => 'technician/dashboard.php',
        'requester' => 'requester/dashboard.php'
    ];
    
    if (isset($_SESSION['role']) && isset($role_pages[$_SESSION['role']])) {
        header("Location: " . $role_pages[$_SESSION['role']]);
        exit();
    }
    
    header("Location: index.php");
    exit();
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Password hashing wrapper
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Password verification wrapper
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
}