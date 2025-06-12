<!-- Main Header -->
<nav class="main-header navbar navbar-expand navbar-dark sticky-top" style="background-color: #343a40; z-index: 1030;">

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle mr-2"></i>
                <?php 
                // Check if session variables exist before accessing them
                $firstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
                $lastName = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
                echo htmlspecialchars($firstName . ' ' . $lastName); 
                ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog mr-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../includes/auth/logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>

<style>
/* Navbar styles */
.main-header {
    padding: 0.3rem 1rem;
    min-height: 45px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.navbar-nav .nav-link {
    padding: 0.3rem 0.8rem;
    font-size: 0.9rem;
}

.navbar-nav .dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-nav .dropdown-toggle i {
    font-size: 1rem;
}

.dropdown-menu {
    font-size: 0.9rem;
    padding: 0.5rem 0;
    margin-top: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.dropdown-item {
    padding: 0.4rem 1rem;
}

.dropdown-item i {
    width: 1.2rem;
    text-align: center;
    margin-right: 0.5rem;
}

.dropdown-divider {
    margin: 0.3rem 0;
}

/* Ensure content doesn't hide under sticky navbar */
.content-wrapper {
    padding-top: 56px !important;
}
</style> 