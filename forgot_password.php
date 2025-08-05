<?php
require_once 'config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            header('Location: reset_password.php?email=' . urlencode($email));
            exit();
        } else {
            $error = 'No account found with that email.';
        }
    } else {
        $error = 'Please enter your email address.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/auth.css">
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
                <span class="text-4xl">🎓</span>
                <h1 class="text-3xl font-bold">CampusLynk</h1>
            </div>
            <div class="auth-message">
                <h2 class="text-2xl font-semibold mb-4">Forgot your password?</h2>
                <p class="text-lg text-muted">No worries! Enter your email and we'll help you reset it.</p>
            </div>
        </div>
        <!-- Right Box - Forgot Password Form -->
        <div class="auth-right">
            <div class="auth-form-container" style="box-shadow: var(--shadow-md); background: var(--background); border-radius: var(--radius); padding: 2rem;">
                <h2 class="text-2xl font-semibold mb-6">Forgot Password</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error mb-4"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form action="forgot_password.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-with-icon">
                            <i class='bx bx-envelope'></i>
                            <input type="email" name="email" required class="form-input" placeholder="Enter your email">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
                </form>
                <p class="text-muted text-center mt-6">
                    <a href="login.php" class="text-primary font-medium">Back to Sign In</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 