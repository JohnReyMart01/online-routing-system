<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($title) ? $title : 'Online Routing System'; ?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.3.0/dist/css/adminlte.min.css">
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <!-- Custom styles -->
  <link rel="stylesheet" href="../assets/css/style.css">
  
  <style>
    /* Additional custom styles */
    body {
      padding-top: 56px; /* Height of the fixed navbar */
    }
    .content-wrapper {
      background-color: #f4f6f9;
      min-height: calc(100vh - 56px); /* Subtract navbar height */
      padding-top: 1rem;
    }
    .navbar {
      padding: 0.5rem 1rem;
      margin-bottom: 0;
      height: 60px;
      display: flex;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1030;
      background-color: #343a40 !important;
      box-shadow: 0 2px 4px rgba(0,0,0,.1);
    }
    .navbar .container-fluid {
      background-color: #343a40 !important;
    }
    .navbar-brand {
      color: #fff !important;
      font-weight: 500;
    }
    .navbar .nav-link {
      color: rgba(255, 255, 255, 0.8) !important;
    }
    .navbar .nav-link:hover {
      color: #fff !important;
    }
    .navbar .dropdown-menu {
      background-color: #343a40;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .navbar .dropdown-item {
      color: rgba(255, 255, 255, 0.8);
    }
    .navbar .dropdown-item:hover {
      background-color: rgba(255, 255, 255, 0.1);
      color: #fff;
    }
    .navbar .dropdown-divider {
      border-top-color: rgba(255, 255, 255, 0.1);
    }
    /* Sidebar styles */
    .sidebar {
      position: fixed;
      top: 56px;
      bottom: 0;
      left: 0;
      z-index: 100;
      padding: 0;
      box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
      background-color: #343a40;
      width: 250px;
      transition: all 0.3s;
    }
    .sidebar .nav-link {
      color: #c2c7d0;
      padding: 0.75rem 1rem;
      display: flex;
      align-items: center;
    }
    .sidebar .nav-link:hover {
      color: #fff;
      background-color: rgba(255, 255, 255, 0.1);
    }
    .sidebar .nav-link.active {
      color: #fff;
      background-color: #007bff;
    }
    .sidebar .nav-link i {
      margin-right: 0.5rem;
      width: 20px;
      text-align: center;
    }
    .sidebar .nav-link span {
      font-size: 0.9rem;
    }
    .sidebar h5 {
      color: #fff;
      font-size: 1rem;
      margin: 0;
    }
    /* Adjust main content for sidebar */
    .content-wrapper {
      margin-left: 250px;
      transition: all 0.3s;
    }
    /* Mobile sidebar */
    @media (max-width: 991.98px) {
      .sidebar {
        margin-left: -250px;
      }
      .sidebar.show {
        margin-left: 0;
      }
      .content-wrapper {
        margin-left: 0;
      }
    }
    /* Other styles */
    .card {
      box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
      margin-bottom: 1rem;
    }
    .table th {
      background-color: #f8f9fa;
    }
    .badge {
      padding: 0.5em 0.75em;
    }
    .btn-group .btn {
      margin: 0 2px;
    }
    .modal-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
    }
    .modal-footer {
      background-color: #f8f9fa;
      border-top: 1px solid #dee2e6;
    }
    .breadcrumb {
      background-color: transparent;
      padding: 0.75rem 0;
    }
    .content-header {
      padding: 15px 0.5rem;
    }
  </style>
</head>