<?php
session_start();

// Clear faculty session variables
unset($_SESSION['useremail']);
unset($_SESSION['username']);
unset($_SESSION['role']);

// Destroy the session
session_destroy();

// Redirect to main login
header('Location: login.php?success=You have been logged out successfully.');
exit();
?> 