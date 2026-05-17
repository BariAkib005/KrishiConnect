<?php
// Database connection configuration
$servername = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$dbname = "krishiconnect_db";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Site settings
define('SITE_NAME', 'KrishiConnect');
define('SITE_URL', 'http://localhost/krishiconnect'); // Change to your domain in production
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/krishiconnect/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Image paths
define('IMAGE_PATH', SITE_URL . '/images/');
define('PRODUCT_IMAGE_PATH', IMAGE_PATH . 'products/');
define('FARMER_IMAGE_PATH', IMAGE_PATH . 'farmers/');

// Session configuration
session_start();

// Helper functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect(SITE_URL . '/pages/login.php');
    }
}

function is_admin() {
    return (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin');
}

function is_farmer() {
    return (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'farmer');
}

function is_buyer() {
    return (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'buyer');
}

function get_user_data($user_id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}

// Error and success messages
function show_error_message($message) {
    $_SESSION['error_message'] = $message;
}

function show_success_message($message) {
    $_SESSION['success_message'] = $message;
}

function display_messages() {
    $output = '';
    
    if (isset($_SESSION['error_message'])) {
        $output .= '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        $output .= '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    
    return $output;
}
?> 