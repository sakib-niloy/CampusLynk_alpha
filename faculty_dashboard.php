<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'faculty'");
    $query->execute([$_SESSION['useremail']]);
    $faculty = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$faculty) {
        session_destroy();
        header("Location: login.php?error=Faculty not found");
        exit();
    }
    
    // Fetch active courses for this faculty
    $courses_query = "SELECT * FROM course_schedules WHERE faculty_name = ?";
    $courses_stmt = $db->prepare($courses_query);
    $courses_stmt->execute([$faculty['name']]);
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total students (count distinct section+course_code for this faculty)
    $students_query = "SELECT COUNT(DISTINCT section, course_code) as total FROM course_schedules WHERE faculty_name = ?";
    $students_stmt = $db->prepare($students_query);
    $students_stmt->execute([$faculty['name']]);
    $total_students = $students_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get faculty.id from faculty table for foreign key
    $faculty_id = null;
    $faculty_id_stmt = $db->prepare("SELECT id FROM faculty WHERE email = ?");
    $faculty_id_stmt->execute([$faculty['email']]);
    $faculty_id_row = $faculty_id_stmt->fetch(PDO::FETCH_ASSOC);
    if ($faculty_id_row) {
        $faculty_id = $faculty_id_row['id'];
    } else {
        // fallback: do not allow schedule add if not found
        $faculty_id = null;
    }

    // Handle add counselling time POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_counselling']) && $faculty_id) {
        $day = $_POST['day_of_week'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $stmt = $db->prepare("INSERT INTO counselling_times (faculty_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$faculty_id, $day, $start, $end]);
        header("Location: faculty_dashboard.php?success=Counselling time added");
        exit();
    }

    // Get this faculty's counselling times
    $counselling_times = $db->prepare("SELECT * FROM counselling_times WHERE faculty_id = ?");
    $counselling_times->execute([$faculty_id]);
    $counselling_times = $counselling_times->fetchAll(PDO::FETCH_ASSOC);

    // Get pending requests for this faculty
    $pending_requests = $db->prepare("SELECT cr.*, u.name as student_name, u.email as student_email FROM counselling_requests cr JOIN users u ON cr.student_id = u.id WHERE cr.faculty_id = ? AND cr.status = 'pending'");
    $pending_requests->execute([$faculty_id]);
    $pending_requests = $pending_requests->fetchAll(PDO::FETCH_ASSOC);

    // Get accepted requests for calendar
    $accepted_requests = $db->prepare("SELECT cr.*, u.name as student_name FROM counselling_requests cr JOIN users u ON cr.student_id = u.id WHERE cr.faculty_id = ? AND cr.status = 'approved'");
    $accepted_requests->execute([$faculty_id]);
    $accepted_requests = $accepted_requests->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header("Location: login.php?error=Database Error: " . urlencode($e->getMessage()));
    exit();
}

// Exam Schedule Section for Teachers
if (isset($_SESSION['faculty_name'])) {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT * FROM exam_schedules WHERE teacher = ? ORDER BY exam_date, exam_time");
    $stmt->execute([$_SESSION['faculty_name']]);
    $examSchedules = $stmt->fetchAll();
    if ($examSchedules) {
        echo '<h2>Your Exam Schedule</h2>';
        echo '<table border="1"><tr><th>Course Code</th><th>Course Title</th><th>Section</th><th>Exam Date</th><th>Exam Time</th><th>Room</th></tr>';
        foreach ($examSchedules as $exam) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($exam['course_code']) . '</td>';
            echo '<td>' . htmlspecialchars($exam['course_title']) . '</td>';
            echo '<td>' . htmlspecialchars($exam['section']) . '</td>';
            echo '<td>' . htmlspecialchars($exam['exam_date']) . '</td>';
            echo '<td>' . htmlspecialchars($exam['exam_time']) . '</td>';
            echo '<td>' . htmlspecialchars($exam['room']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<h2>Your Exam Schedule</h2><p>No exam schedule found.</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/faculty.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>
<body class="dashboard-page">
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <section class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($faculty['name']); ?></h1>
                <p class="text-muted">Manage your courses and student requests</p>
            </section>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid" style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 2rem; align-items: start;">
                <!-- Left Column: Quick Access, Course Overview, Recent Activities -->
                <div class="content-stack" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div class="card">
                        <h2 class="text-xl font-semibold mb-4"><i class='bx bxs-bolt'></i> Quick Access</h2>
                        <div class="space-y-4">
                            <a href="faculty_counselling_requests.php" class="btn btn-primary w-full">
                                <i class='bx bx-check-square'></i> Manage Counselling Requests
                            </a>
                            <a href="manage_courses.php" class="btn btn-secondary w-full">
                                <i class='bx bx-book'></i> Manage Courses
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="text-xl font-semibold mb-4"><i class='bx bxs-bar-chart-alt-2'></i> Course Overview</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-muted">Active Courses</span>
                                <span class="font-semibold"><?php echo count($courses); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted">Total Students</span>
                                <span class="font-semibold"><?php echo $total_students; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="text-xl font-semibold mb-4"><i class='bx bxs-time-five'></i> Recent Activities</h2>
                        <div class="space-y-4">
                            <?php if (!empty($courses)): ?>
                                <?php foreach (array_slice($courses, 0, 3) as $course): ?>
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-1">
                                            <h3 class="font-semibold"><?php echo htmlspecialchars($course['name']); ?></h3>
                                            <p class="text-sm text-muted"><?php echo htmlspecialchars($course['code']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class='bx bx-info-circle'></i>
                                    <p class="text-muted">No active courses</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Right Column: Add Counselling, Pending Requests, Accepted Calendar -->
                <div class="content-stack" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div class="card">
                        <h2 class="text-xl font-semibold mb-4"><i class='bx bxs-time-five'></i> Counselling Schedule</h2>
                        <p class="text-muted mb-4">Your available time slots for student counselling.</p>
                        <!-- Counselling times will be listed here -->
                    </div>
                </div>
            </div>
            <style>
            @media (max-width: 1024px) {
                .dashboard-grid { grid-template-columns: 1fr; }
            }
            </style>
        </main>
    </div>

    <script src="faculty_dashboard.js"></script>
</body>

</html>