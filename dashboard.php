<?php

session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = $db->prepare("SELECT * FROM users WHERE email = ?");
    $query->execute([$_SESSION['useremail']]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header("Location: login.php?error=User not found");
        exit();
    }

    $course_query = $db->query("SELECT course_code, MIN(course_title) as course_title FROM upcoming_courses GROUP BY course_code ORDER BY MIN(course_title)");
    $courses = $course_query->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user id
    $userId = $user['id'];

    // 1. Count enrolled courses
    $courseCount = 0;
    $courseStmt = $db->prepare("SELECT COUNT(DISTINCT se.course_id) FROM student_enrollments se WHERE se.student_id = ?");
    $courseStmt->execute([$userId]);
    $courseCount = $courseStmt->fetchColumn();

    // 2. Count requests (counselling_requests for this student)
    $requestCount = 0;
    $requestStmt = $db->prepare("SELECT COUNT(*) FROM counselling_requests WHERE student_id = ?");
    $requestStmt->execute([$userId]);
    $requestCount = $requestStmt->fetchColumn();

    // 3. Count study materials (all PDFs in enrolled courses' folders)
    $materialCount = 0;
    $materialQuery = $db->prepare("
        SELECT uc.course_code
        FROM student_enrollments se
        JOIN upcoming_courses uc ON se.course_id = uc.id
        WHERE se.student_id = ?
        GROUP BY uc.course_code
    ");
    $materialQuery->execute([$userId]);
    $materialCourses = $materialQuery->fetchAll(PDO::FETCH_COLUMN);
    foreach ($materialCourses as $courseCode) {
        $dir = __DIR__ . "/study_materials/" . $courseCode;
        if (is_dir($dir)) {
            $files = glob($dir . "/*.pdf");
            $materialCount += count($files);
        }
    }

    // 4. Count events (all events)
    $eventCount = 0;
    $eventStmt = $db->query("SELECT COUNT(*) FROM events");
    $eventCount = $eventStmt->fetchColumn();

    // Fetch next upcoming event
    $eventStmt = $db->query("SELECT * FROM events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 1");
    $nextEvent = $eventStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch next upcoming exam for this student
    $examStmt = $db->prepare("
        SELECT es.* FROM student_enrollments se
        JOIN upcoming_courses uc ON se.course_id = uc.id
        JOIN exam_schedules es ON es.course_code = uc.course_code AND es.section = se.section
        WHERE se.student_id = ? AND es.exam_date >= CURDATE()
        ORDER BY es.exam_date ASC, es.exam_time ASC LIMIT 1
    ");
    $examStmt->execute([$userId]);
    $nextExam = $examStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch most recent study material for enrolled courses
    $latestMaterial = null;
    $latestMaterialTime = 0;
    foreach ($materialCourses as $courseCode) {
        $dir = __DIR__ . "/study_materials/" . $courseCode;
        if (is_dir($dir)) {
            $files = glob($dir . "/*.pdf");
            foreach ($files as $file) {
                $fileTime = filemtime($file);
                if ($fileTime > $latestMaterialTime) {
                    $latestMaterialTime = $fileTime;
                    $latestMaterial = [
                        'course_code' => $courseCode,
                        'file' => basename($file),
                        'date' => date('M d, Y H:i', $fileTime)
                    ];
                }
            }
        }
    }

    // Fetch most recent routine update (from upcoming_courses table by latest id or updated_at if available)
    $routineStmt = $db->query("SELECT * FROM upcoming_courses ORDER BY id DESC LIMIT 1");
    $latestRoutine = $routineStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header("Location: login.php?error=Database Error: " . urlencode($e->getMessage()));
    exit();
}

// Exam Schedule Section for Students
if (isset($_SESSION['department']) && isset($_SESSION['section'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT * FROM exam_schedules WHERE department = ? AND section = ? ORDER BY exam_date, exam_time");
    $stmt->execute([$_SESSION['department'], $_SESSION['section']]);
    $examSchedules = $stmt->fetchAll();
    if ($examSchedules) {
        echo '<h2>Your Exam Schedule</h2>';
        echo '<table border="1"><tr><th>Course Code</th><th>Course Title</th><th>Teacher</th><th>Exam Date</th><th>Exam Time</th><th>Room</th></tr>';
        foreach ($examSchedules as $exam) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($exam['course_code']) . '</td>';
            echo '<td>' . htmlspecialchars($exam['course_title']) . '</td>';
            echo '<td>' . htmlspecialchars($exam['teacher']) . '</td>';
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
    <title>Dashboard - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main id="main-content" class="main-content fade-transition fade-out" style="">
    <section class="welcome-section">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
        <p class="text-muted">Access your student dashboard</p>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
         <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="quick-access-row">
        <div class="quick-access-card glow-purple">
            <div class="quick-access-card-content">
                <div class="quick-access-number"><?php echo $courseCount; ?></div>
                <div class="quick-access-label">Courses</div>
            </div>
            <div class="quick-access-icon purple">
                <i class='bx bxs-book'></i>
            </div>
        </div>

        <div class="quick-access-card glow-yellow">
            <div class="quick-access-card-content">
                <div class="quick-access-number"><?php echo $requestCount; ?></div>
                <div class="quick-access-label">Requests</div>
            </div>
            <div class="quick-access-icon yellow">
                <i class='bx bxs-user'></i>
            </div>
        </div>

        <div class="quick-access-card glow-pink">
            <div class="quick-access-card-content">
                <div class="quick-access-number"><?php echo $materialCount; ?></div>
                <div class="quick-access-label">Materials</div>
            </div>
            <div class="quick-access-icon pink">
                <i class='bx bxs-file'></i>
            </div>
        </div>

        <div class="quick-access-card glow-blue">
            <div class="quick-access-card-content">
                <div class="quick-access-number"><?php echo $eventCount; ?></div>
                <div class="quick-access-label">Events</div>
            </div>
            <div class="quick-access-icon blue">
                <i class='bx bxs-calendar'></i>
            </div>
        </div>
    </div>

    <div class="content-stack">
        <div class="card">
            <h2 class="text-xl font-semibold mb-4">Overview</h2>
            <div class="dashboard-overview-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <?php if ($nextEvent): ?>
                <div class="overview-card">
                    <div class="overview-icon"><i class='bx bxs-calendar text-primary'></i></div>
                    <div class="overview-content">
                        <h3 class="overview-title">Upcoming Event</h3>
                        <div class="overview-main"><?php echo htmlspecialchars($nextEvent['title']); ?></div>
                        <div class="overview-desc"><?php echo htmlspecialchars($nextEvent['description']); ?></div>
                        <div class="overview-date"><?php echo date('M d, Y', strtotime($nextEvent['date'])); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($nextExam): ?>
                <div class="overview-card">
                    <div class="overview-icon"><i class='bx bxs-time-five text-primary'></i></div>
                    <div class="overview-content">
                        <h3 class="overview-title">Upcoming Exam</h3>
                        <div class="overview-main"><?php echo htmlspecialchars($nextExam['course_title']); ?></div>
                        <div class="overview-desc">Date: <?php echo date('M d, Y', strtotime($nextExam['exam_date'])); ?> | Time: <?php echo htmlspecialchars($nextExam['exam_time']); ?> | Room: <?php echo htmlspecialchars($nextExam['room']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($latestMaterial): ?>
                <div class="overview-card">
                    <div class="overview-icon"><i class='bx bxs-book text-primary'></i></div>
                    <div class="overview-content">
                        <h3 class="overview-title">New Study Material</h3>
                        <div class="overview-main"><?php echo htmlspecialchars($latestMaterial['file']); ?></div>
                        <div class="overview-desc">Course: <?php echo htmlspecialchars($latestMaterial['course_code']); ?> | Uploaded: <?php echo $latestMaterial['date']; ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($latestRoutine): ?>
                <div class="overview-card">
                    <div class="overview-icon"><i class='bx bxs-calendar-check text-primary'></i></div>
                    <div class="overview-content">
                        <h3 class="overview-title">Routine Updated</h3>
                        <div class="overview-main"><?php echo htmlspecialchars($latestRoutine['course_title']); ?> (<?php echo htmlspecialchars($latestRoutine['course_code']); ?>)</div>
                        <div class="overview-desc">Section: <?php echo htmlspecialchars($latestRoutine['section']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!$nextEvent && !$nextExam && !$latestMaterial && !$latestRoutine): ?>
                <div class="overview-card empty"><div class="overview-content"><div class="overview-title">No recent updates.</div></div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
  </main>
</div>

    <script>
        document.getElementById('course').addEventListener('change', function() {
            const courseCode = this.value;
            const sectionSelect = document.getElementById('section');
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';

            if (courseCode) {
                fetch('get_sections.php?course_code=' + courseCode)
                    .then(response => response.json())
                    .then(data => {
                        sectionSelect.innerHTML = '<option value="">-- Select a Section --</option>';
                        data.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.section;
                            option.textContent = 'Section ' + section.section;
                            sectionSelect.appendChild(option);
                        });
                        sectionSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error fetching sections:', error);
                        sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    });
            } else {
                sectionSelect.innerHTML = '<option value="">-- Select a Course First --</option>';
            }
        });
    </script>

</body>

</html>