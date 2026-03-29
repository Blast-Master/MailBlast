<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated or missing required session data
if (!isset($_SESSION['user_id']) || !array_key_exists('company_id', $_SESSION)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Block access if the user is not actively approved
if (isset($_SESSION['status']) && $_SESSION['status'] !== 'active') {
    header("Location: login.php");
    exit;
}

// Global variables for the logged-in user
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];
$user_status = $_SESSION['status'];
?>