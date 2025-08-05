<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['myemail'] ?? '');
    $password = $_POST['mypass'] ?? '';
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && (password_verify($password, $user['password']) || $user['password'] === md5($password))) {
            $_SESSION['useremail'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                    header('Location: admin_dashboard.php');
                    break;
                case 'faculty':
                    header('Location: faculty_dashboard.php');
                    break;
                case 'student':
                default:
                    header('Location: dashboard.php');
                    break;
            }
            exit();
        } else {
            header('Location: login.php?error=Invalid credentials');
            exit();
        }
    } catch (PDOException $e) {
        header('Location: login.php?error=Database error');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-page">
    <!-- Page transition overlay -->
    <div class="page-transition-overlay" id="transitionOverlay"></div>
    
    <div class="auth-container">
        <!-- Left Box - Branding and Message -->
        <div class="auth-left">
            
            <div class="auth-left-content">
            <a href="index.php" class="back-home auth-switch">
                    <i class='bx bx-arrow-back'></i>
                    Back to Home
                </a>
            <h1 class="text-3xl font-bold" style="margin-bottom: 1.5rem;">Sign In</h1>
                <h2 class="text-2xl font-semibold mb-4" style="margin-bottom: 1.2rem;">Continue to CampusLynk!</h2>
                <p class="text-lg text-muted" style="color: #eaf1fb; margin-bottom: 2.5rem;">Sign in to access your personalized student dashboard and stay connected with your academic journey.</p>
            </div>
        </div>

        <!-- Right Box - Login Form -->
        <div class="auth-right">
            <div class="auth-form-container">
                <?php
                if(isset($_GET['error'])) {
                    echo '<div class="alert alert-error mb-4">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                if(isset($_GET['success'])) {
                    echo '<div class="alert alert-success mb-4">' . htmlspecialchars($_GET['success']) . '</div>';
                }
                ?>
                <form action="login.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <div class="input-with-icon">
                            <i class='bx bx-envelope'></i>
                            <input type="email" name="myemail" required class="form-input" placeholder="Enter your email">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-with-icon" style="position:relative;">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" name="mypass" id="login-password" required class="form-input" placeholder="Enter your password">
                            <button type="button" onclick="togglePassword('login-password', this)" tabindex="-1" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                    </div>
                    <div class="forgot-password-link" style="text-align:right; margin-bottom:10px;">
                        <a href="forgot_password.php" class="text-primary font-medium">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Sign In</button>
                    
                    <p class="text-muted text-center mt-6">
                        Don't have an account? <a href="signup.php" class="text-primary font-medium auth-switch">Sign Up</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    } else {
        input.type = 'password';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    }
}

// Page transition functionality
document.addEventListener('DOMContentLoaded', function() {
    const authSwitches = document.querySelectorAll('.auth-switch');
    const overlay = document.getElementById('transitionOverlay');
    
    // Hide overlay after page loads
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 100);
    
    authSwitches.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const body = document.querySelector('.auth-page');
            
            // Show overlay immediately to prevent white flash
            overlay.classList.remove('hidden');
            overlay.classList.add('active');
            
            // Check if it's the back-to-home link
            if (this.href.includes('index.php')) {
                body.classList.add('zoom-out');
                setTimeout(() => {
                    window.location.href = this.href;
                }, 400);
            } else {
                // Regular fade-out for other links
                body.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = this.href;
                }, 300);
            }
        });
    });
});
</script>
</html>