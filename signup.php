<?php
require_once 'config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $university_id = $_POST['university_id'] ?? null;
    $designation = $_POST['designation'] ?? null;
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($password2)) {
        $error = 'All fields are required.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($role === 'student' && (empty($university_id) || !preg_match('/^0[0-9]{8,}$/', $university_id))) {
        $error = 'A valid University ID is required for students.';
    } elseif ($role === 'faculty' && empty($designation)) {
        $error = 'Designation is required for faculty.';
    } else {
        try {
            $db = (new Database())->getConnection();
            $db->beginTransaction();

            // Check if email or username already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email or username already exists.';
                $db->rollBack();
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $username, $email, $hash, $role]);
                
                if ($role === 'faculty') {
                    $stmt = $db->prepare("INSERT INTO faculty (name, designation, email) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $designation, $email]);
                } else {
                    $user_id = $db->lastInsertId();
                    $stmt_student = $db->prepare("INSERT INTO student_id_table (user_id, university_id) VALUES (?, ?)");
                    $stmt_student->execute([$user_id, $university_id]);
                }

                $db->commit();
                header('Location: login.php?success=Account created successfully! Please sign in.');
                exit();
            }
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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
    <title>Sign Up - CampusLynk</title>
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
            <div class="auth-left-content" style="display: flex; flex-direction: column; align-items: flex-start; justify-content: center; height: 100vh; max-width: 360px; margin: 0 auto;">
                <a href="index.php" class="back-home auth-switch" style="margin-bottom: 2rem;">
                    <i class='bx bx-arrow-back'></i>
                    Back to Home
                </a>
                <h1 class="text-3xl font-bold" style="margin-bottom: 1.2rem;">Sign Up</h1>
                <h2 class="text-2xl font-semibold mb-4" style="margin-bottom: 1.2rem;">Join CampusLynk!</h2>
                <p class="text-lg text-muted" style="color: #eaf1fb;">Create your account to access study materials, connect with faculty, and stay updated with campus events.</p>
            </div>
        </div>

        <!-- Right Box - Signup Form -->
        <div class="auth-right">
            <div class="auth-form-container">
                <?php
                if(isset($_GET['error'])) {
                    echo '<div class="alert alert-error mb-4">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                ?>
                <form action="signup.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="input-with-icon">
                            <i class='bx bx-user'></i>
                            <input type="text" name="name" required class="form-input" placeholder="Enter your full name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-with-icon">
                            <i class='bx bx-user-circle'></i>
                            <input type="text" name="username" required class="form-input" placeholder="Choose a username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <div class="input-with-icon">
                            <i class='bx bx-envelope'></i>
                            <input type="email" name="email" required class="form-input" placeholder="Enter your email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <div class="input-with-icon">
                            <i class='bx bx-user-pin'></i>
                            <select name="role" id="role" class="form-input" required onchange="toggleFields()">
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="student-id-group">
                        <label class="form-label">University ID</label>
                        <div class="input-with-icon">
                            <i class='bx bx-id-card'></i>
                            <input type="text" name="university_id" id="university_id" class="form-input" pattern="0[0-9]{8,}" maxlength="20" placeholder="e.g. 011221521">
                        </div>
                    </div>
                    <div class="form-group" id="designation-group" style="display:none;">
                        <label class="form-label">Designation</label>
                        <div class="input-with-icon">
                            <i class='bx bx-briefcase'></i>
                            <select name="designation" id="designation" class="form-input">
                                <option value="">Select Designation</option>
                                <option value="Professor">Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Lecturer">Lecturer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-with-icon" style="position:relative;">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" name="password" id="signup-password" required class="form-input" placeholder="Create a password">
                            <button type="button" onclick="togglePassword('signup-password', this)" tabindex="-1" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-with-icon" style="position:relative;">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" name="password2" id="signup-password2" required class="form-input" placeholder="Confirm your password">
                            <button type="button" onclick="togglePassword('signup-password2', this)" tabindex="-1" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Create Account</button>
                    
                    <p class="text-muted text-center mt-6">
                        Already have an account? <a href="login.php" class="text-primary font-medium auth-switch">Sign In</a>
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
function toggleFields() {
    var role = document.getElementById('role').value;
    var studentGroup = document.getElementById('student-id-group');
    var designationGroup = document.getElementById('designation-group');
    var universityIdInput = document.getElementById('university_id');
    var designationInput = document.getElementById('designation');

    if (role === 'student') {
        studentGroup.style.display = 'block';
        universityIdInput.required = true;
        designationGroup.style.display = 'none';
        designationInput.required = false;
    } else if (role === 'faculty') {
        studentGroup.style.display = 'none';
        universityIdInput.required = false;
        designationGroup.style.display = 'block';
        designationInput.required = true;
    }
}
document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
    
    // Page transition functionality
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