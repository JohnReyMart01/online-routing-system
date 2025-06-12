<?php
$user_role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="../<?php echo $user_role; ?>/dashboard.php" class="brand-link">
        <img src="../assets/img/logo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Routing System</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <h6 class="px-3 py-2 text-white-50"><?php echo ucfirst($user_role); ?> Menu</h6>
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="../<?php echo $user_role; ?>/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <?php if ($user_role === 'requester'): ?>
                <li class="nav-item">
                    <a href="../<?php echo $user_role; ?>/my_requests.php" class="nav-link <?php echo $current_page === 'my_requests.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-list"></i>
                        <p>My Requests</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../<?php echo $user_role; ?>/new_request.php" class="nav-link <?php echo $current_page === 'new_request.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-plus"></i>
                        <p>New Request</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($user_role === 'technician'): ?>
                <li class="nav-item">
                    <a href="../<?php echo $user_role; ?>/tasks.php" class="nav-link <?php echo $current_page === 'tasks.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>My Tasks</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="../<?php echo $user_role; ?>/profile.php" class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Profile</p>
                    </a>
                </li>

                <!-- Logout Button -->
                <li class="nav-item mt-auto">
                    <a href="../logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<!-- Floating Login Form -->
<div id="loginForm" class="login-form-overlay" style="display: none;">
    <div class="login-form-container">
        <div class="login-form-header">
            <h4>Login</h4>
            <button type="button" class="close-btn" onclick="hideLoginForm()">&times;</button>
        </div>
        <form action="../login.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </div>
</div>

<style>
/* Sidebar specific styles */
.main-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 250px;
    z-index: 1000;
    background-color: #343a40 !important;
    transition: all 0.3s ease;
    overflow-y: auto;
}

.sidebar {
    padding-top: 0px; /* Add space for navbar */
}

.sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
    background-color: #007bff !important;
    color: #fff !important;
}

.nav-sidebar .nav-link {
    color: #c2c7d0 !important;
    padding: 0.8rem 1rem;
    transition: all 0.3s ease;
}

.nav-sidebar .nav-link:hover {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.1) !important;
}

.nav-sidebar .nav-link i {
    margin-right: 0.5rem;
    width: 1.25rem;
    text-align: center;
}

.brand-link {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background-color: #343a40;
    z-index: 1001;
}

.brand-link .brand-image {
    width: 35px;
    height: 35px;
    object-fit: contain;
    background-color: #fff;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

.brand-link .brand-text {
    font-weight: 300;
    color: #fff;
    font-size: 1.1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Menu section */
.nav-sidebar h6 {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    color: rgba(255, 255, 255, 0.5);
    padding: 0.5rem 1rem;
}

/* Active link styles */
.nav-sidebar .nav-link.active {
    background-color: #007bff !important;
    color: #fff !important;
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
}

/* Hover effects */
.nav-sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
    transform: translateX(5px);
}

/* Icon styles */
.nav-sidebar .nav-link i {
    transition: all 0.3s ease;
}

.nav-sidebar .nav-link:hover i {
    transform: scale(1.1);
}

.nav-item.mt-auto {
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.nav-link.text-danger {
    color: #dc3545 !important;
}

.nav-link.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1);
}

.nav-link.text-danger i {
    color: #dc3545;
}

/* Adjust main content wrapper */
.content-wrapper {
    margin-left: 250px;
    min-height: 100vh;
    padding-top: 60px; /* Add space for navbar */
    transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .main-sidebar {
        transform: translateX(-250px);
    }
    
    .content-wrapper {
        margin-left: 0;
    }
    
    .sidebar-open .main-sidebar {
        transform: translateX(0);
    }
    
    .sidebar-open .content-wrapper {
        margin-left: 250px;
    }
}

/* Login Form Styles */
.login-form-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.login-form-container {
    background-color: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
}

.login-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.login-form-header h4 {
    margin: 0;
    color: #333;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
    padding: 0;
}

.close-btn:hover {
    color: #333;
}

.login-form .form-group {
    margin-bottom: 1rem;
}

.login-form label {
    display: block;
    margin-bottom: 0.5rem;
    color: #555;
}

.login-form .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: border-color 0.3s ease;
}

.login-form .form-control:focus {
    border-color: #007bff;
    outline: none;
}

.login-form .btn-primary {
    margin-top: 1rem;
    padding: 0.75rem;
    font-weight: 500;
}
</style>

<script>
function handleLogout() {
    // Show loading state
    const logoutBtn = document.querySelector('.nav-link.text-danger');
    const originalContent = logoutBtn.innerHTML;
    logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
    
    // Perform the logout
    fetch('../../logout.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // After successful logout, show the login form
            showLoginForm();
        } else {
            throw new Error(data.message || 'Logout failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Logout failed. Please try again.');
    })
    .finally(() => {
        // Restore button state
        logoutBtn.innerHTML = originalContent;
    });
}

function showLoginForm() {
    document.getElementById('loginForm').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideLoginForm() {
    document.getElementById('loginForm').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close login form when clicking outside
document.getElementById('loginForm').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLoginForm();
    }
});

// Handle form submission
document.querySelector('.login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Login failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});
</script>