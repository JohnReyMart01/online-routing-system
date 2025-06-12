<?php
require_once '../includes/config/functions.php';
session_start();

// If user is logged in and is admin, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    redirect('dashboard.php');
}

// Otherwise redirect to main login page
redirect('../index.php');
?> 