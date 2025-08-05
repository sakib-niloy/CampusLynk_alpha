<?php
session_start();

// Clear admin session variables
unset($_SESSION['admin_email']);
unset($_SESSION['admin_id']);

// Destroy the session
session_destroy();

// Redirect to admin login
header('Location: admin_login.php');
exit();
?>

<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/layout.css">
<link rel="stylesheet" href="css/components.css"> 