<?php
require_once 'config/database.php';
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && (password_verify($password, $admin['password']) || $admin['password'] === md5($password))) {
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['useremail'] = $admin['email'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $message = 'Invalid credentials.';
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="auth-page">
    <div class="auth-container">
        <!-- Left Box - Branding and Message -->
        <div class="auth-left">
            <a href="index.php" class="back-home">
                <i class='bx bx-arrow-back'></i>
                Back to Home
            </a>
            <div class="auth-brand">
                <h1 class="text-3xl font-bold">CampusLynk</h1>
            </div>
            <div class="auth-message">
                <h2 class="text-2xl font-semibold mb-4">Admin Portal</h2>
                <p class="text-lg text-muted">Access the administrative dashboard to manage campus resources and schedules.</p>
            </div>
        </div>

        <!-- Right Box - Login Form -->
        <div class="auth-right">
            <div class="auth-form-container">
                <h2 class="text-2xl font-semibold mb-6">Admin Login</h2>
                <?php if ($message): ?>
                    <div class="alert alert-error mb-4"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <div class="input-with-icon">
                            <i class='bx bx-envelope'></i>
                            <input type="email" name="email" required class="form-input" placeholder="Enter your email">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-with-icon password-with-toggle">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" name="password" id="admin-login-password" required class="form-input" placeholder="Enter your password">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('admin-login-password', this)" tabindex="-1">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>
<style>
.password-with-toggle {
    position: relative;
}
.password-toggle-btn {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0;
    margin: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    color: #64748b;
    height: 2.5rem;
    width: 2.5rem;
    z-index: 2;
}
.password-toggle-btn:focus {
    outline: none;
}
.password-toggle-btn i {
    font-size: 1.25rem;
    transition: color 0.2s;
}
.password-toggle-btn:hover i {
    color: #0066cc;
}
.password-with-toggle .form-input {
    padding-right: 2.5rem;
}
</style>
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
</script>
</html> 