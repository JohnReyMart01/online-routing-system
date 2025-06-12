<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top" style="background-color: #343a40 !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
   <!-- <div class="container-fluid" style="background-color: #343a40 !important;">
        <!-- Logo on the left -->
        <a class="navbar-brand" href="#">
            <img src="../assets/img/logo1.png" alt="Logo" style="height: 60px; width: auto;">
        </a>

        <button class="navbar-toggler d-block d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <!-- User dropdown (right side) -->
            <ul class="navbar-nav align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding-right: 20px;">
                        <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
                        <span style="margin-right: 10px;"><?php 
                            if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                                echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                            } else {
                                echo htmlspecialchars($_SESSION['username'] ?? 'User');
                            }
                        ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- /.navbar -->