<?php
global $conn;
$page_title = "Login";
require_once '../includes/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'farmer') {
        redirect(SITE_URL . '/pages/farmer-dashboard.php');
    } elseif ($_SESSION['user_type'] == 'buyer') {
        redirect(SITE_URL . '/pages/buyer-dashboard.php');
    } elseif ($_SESSION['user_type'] == 'admin') {
        redirect(SITE_URL . '/pages/admin-dashboard.php');
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        show_error_message("Username and password are required");
    } else {
        // Check if user exists
        $sql = "SELECT id, username, password, user_type FROM users WHERE username = '$username'";
        $result = $conn->query($sql);
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password - in real world use password_verify()
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Redirect based on user type
                if ($user['user_type'] == 'farmer') {
                    redirect(SITE_URL . '/pages/farmer-dashboard.php');
                } elseif ($user['user_type'] == 'buyer') {
                    redirect(SITE_URL . '/pages/buyer-dashboard.php');
                } elseif ($user['user_type'] == 'admin') {
                    redirect(SITE_URL . '/pages/admin-dashboard.php');
                }
            } else {
                show_error_message("Invalid password");
            }
        } else {
            show_error_message("User not found");
        }
    }
}

// Include header
include_once '../includes/header.php';
?>

<!-- Login Section -->
<section class="section login-section">
    <div class="container">
        <div class="form-container">
            <h2>Login to Your Account</h2>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </div>
                
                <div class="form-footer">
                    <p>Don't have an account? 
                        <a href="<?php echo SITE_URL; ?>/pages/farmer-register.php">Register as Farmer</a> or 
                        <a href="<?php echo SITE_URL; ?>/pages/buyer-register.php">Register as Buyer</a>
                    </p>
                    <p><a href="<?php echo SITE_URL; ?>/pages/forgot-password.php">Forgot Password?</a></p>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
// Include footer
include_once '../includes/footer.php';
?> 