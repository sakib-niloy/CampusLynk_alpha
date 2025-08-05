<?php
require_once 'config/database.php';
session_start();
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'faculty'");
$stmt->execute([$_SESSION['useremail']]);
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Calendar - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/faculty.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <section class="welcome-section">
            <h1>Faculty Counselling Calendar</h1>
            <p class="text-muted">A calendar view of your accepted counselling sessions will appear here soon.</p>
        </section>
        <section class="card mt-6">
            <div class="text-center text-muted" style="padding: 3rem 0; font-size: 1.2rem;">
                <i class='bx bx-calendar' style="font-size:2rem;"></i><br>
                Calendar feature coming soon!
            </div>
        </section>
    </main>
</body>
</html> 