<?php
require_once 'config/database.php';
session_start();

$message = '';
$error = '';

// Check if user is already logged in as admin
if (isset($_SESSION['useremail']) && !empty($_SESSION['useremail'])) {
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT role FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['useremail']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            $db = (new Database())->getConnection();
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email already exists.';
            } else {
                // Hash password and create admin account
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$name, $email, $hash]);
                $message = 'Admin registered successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
</head>
<body>
    <main class="main-content" style="max-width: 400px; margin: 3rem auto;">
        <h1 class="text-2xl font-bold mb-6 text-center">Admin Registration</h1>
        <?php if ($message): ?>
            <div class="alert alert-success text-center mb-4"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error text-center mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" class="card p-4">
            <div class="form-group mb-4">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-input w-full" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input w-full" required>
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="admin-register-password" class="form-input w-full" required minlength="8">
                    <button type="button" onclick="togglePassword('admin-register-password', this)" tabindex="-1" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;">
                        <i class='bx bx-show'></i>
                    </button>
                </div>
            </div>
            <div class="form-group mb-6">
                <label class="form-label">Confirm Password</label>
                <div style="position:relative;">
                    <input type="password" name="confirm_password" id="admin-register-confirm-password" class="form-input w-full" required minlength="8">
                    <button type="button" onclick="togglePassword('admin-register-confirm-password', this)" tabindex="-1" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;">
                        <i class='bx bx-show'></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-full">Register</button>
        </form>
        <p class="text-center mt-4"><a href="admin_login.php" class="text-primary hover:underline">Back to Admin Login</a></p>
    </main>
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
</script>
</html> 