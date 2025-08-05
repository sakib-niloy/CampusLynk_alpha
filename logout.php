<?php
session_start();

// Clear student session variables
unset($_SESSION['useremail']);
unset($_SESSION['username']);
unset($_SESSION['role']);

// Destroy the session
session_destroy();

// Redirect to student login
header("Location: login.php?success=You have been logged out successfully.");
exit();
?> 