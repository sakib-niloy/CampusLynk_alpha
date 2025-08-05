<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_email']) || empty($_SESSION['admin_email'])) {
    header('Location: admin_login.php');
    exit();
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['admin_email']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        header('Location: admin_login.php');
        exit();
    }
    // Stats queries
    $totalCourses = $db->query("SELECT COUNT(DISTINCT course_code) FROM upcoming_courses")->fetchColumn();
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalEvents = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $totalPending = $db->query("SELECT COUNT(*) FROM pending_materials WHERE status='pending'")->fetchColumn();
    $totalSchedules = $db->query("SELECT COUNT(*) FROM course_schedules")->fetchColumn();
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-header">
            <div>
                <h1 class="text-2xl font-bold">Admin Dashboard</h1>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($admin['name']); ?></p>
            </div>
        </div>
        <div class="dashboard-overview-grid">
            <div class="overview-card">
                <div class="overview-icon"><i class='bx bxs-book'></i></div>
                <div>
                    <div class="overview-title">Total Courses</div>
                    <div class="overview-main"><?php echo $totalCourses; ?></div>
                    <div class="overview-desc">All unique courses in the system</div>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon"><i class='bx bxs-user'></i></div>
                <div>
                    <div class="overview-title">Total Users</div>
                    <div class="overview-main"><?php echo $totalUsers; ?></div>
                    <div class="overview-desc">Students, faculty, and admins</div>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon"><i class='bx bxs-calendar'></i></div>
                <div>
                    <div class="overview-title">Total Events</div>
                    <div class="overview-main"><?php echo $totalEvents; ?></div>
                    <div class="overview-desc">Campus events and activities</div>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon"><i class='bx bxs-file'></i></div>
                <div>
                    <div class="overview-title">Pending Materials</div>
                    <div class="overview-main"><?php echo $totalPending; ?></div>
                    <div class="overview-desc">Awaiting admin review</div>
                </div>
            </div>
            <div class="overview-card">
                <div class="overview-icon"><i class='bx bxs-time-five'></i></div>
                <div>
                    <div class="overview-title">Total Schedules</div>
                    <div class="overview-main"><?php echo $totalSchedules; ?></div>
                    <div class="overview-desc">Class and exam schedules</div>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 