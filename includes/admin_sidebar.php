<!-- Main Sidebar Container - Admin -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link">
        <img src="../assets/img/logo1.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Routing System</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <h6 class="px-3 py-2 text-white-50">Admin Menu</h6>
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Users Management -->
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Users</p>
                    </a>
                </li>

                <!-- Requests Management -->
                <li class="nav-item">
                    <a href="requests.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'requests.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Requests</p>
                    </a>
                </li>

                <!-- Technicians Management -->
                <li class="nav-item">
                    <a href="technicians.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'technicians.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tools"></i>
                        <p>Technicians</p>
                    </a>
                </li>

                <!-- Reports -->
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Reports</p>
                    </a>
                </li>

                <!-- Settings -->
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Settings</p>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item mt-auto">
                    <a href="#" class="nav-link text-danger logout-link">
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

<style>
/* Sidebar styles */
.main-sidebar {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #343a40;
}

.sidebar {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Brand logo styles */
.brand-link {
    display: flex;
    align-items: center;
    padding: 1rem;
    color: #fff;
    text-decoration: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.brand-link img {
    width: 2.5rem;
    height: 2.5rem;
    margin-right: 0.75rem;
    transition: all 0.3s ease;
}

.brand-link:hover img {
    transform: scale(1.1);
}

.brand-text {
    font-size: 1.1rem;
    font-weight: 300;
}

/* Menu section */
.nav-sidebar h6 {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    color: rgba(255, 255, 255, 0.5);
}

/* Navigation items */
.nav-sidebar .nav-item {
    margin: 0.2rem 0;
}

.nav-sidebar .nav-link {
    color: #c2c7d0 !important;
    padding: 0.8rem 1rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.nav-sidebar .nav-link:hover {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.1) !important;
    transform: translateX(5px);
}

.nav-sidebar .nav-link.active {
    background-color: #007bff !important;
    color: #fff !important;
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
}

.nav-sidebar .nav-link i {
    margin-right: 0.5rem;
    width: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
}

.nav-sidebar .nav-link:hover i {
    transform: scale(1.1);
}

/* Logout button */
.nav-item.mt-auto {
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.nav-link.text-danger {
    color: #dc3545 !important;
}

.nav-link.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.nav-link.text-danger i {
    color: #dc3545;
}

/* Responsive styles */
@media (max-width: 767.98px) {
    .main-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        width: 250px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .main-sidebar.show {
        transform: translateX(0);
    }
}
</style> 