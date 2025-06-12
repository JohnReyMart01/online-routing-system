<?php
// Prevent direct access to this file
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to get current user's role
function get_user_role() {
    return $_SESSION['role'] ?? null;
}

// Function to get current user's ID
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user's name
function get_user_name() {
    return isset($_SESSION['first_name'], $_SESSION['last_name']) 
        ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
        : '';
}

// Function to check if user is admin
function is_admin() {
    return get_user_role() === 'admin';
}

// Function to check if user is technician
function is_technician() {
    return get_user_role() === 'technician';
}

// Function to check if user is requester
function is_requester() {
    return get_user_role() === 'requester';
} 