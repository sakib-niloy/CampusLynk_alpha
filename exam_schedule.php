<?php
session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$exams = [];

try {
    // Fetch user id
    $userQuery = "SELECT id FROM users WHERE email = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$_SESSION["useremail"]]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['id'];

        // Fetch all enrolled courses (course_code, section)
        $enrollStmt = $db->prepare("SELECT uc.course_code, uc.course_title, se.section FROM student_enrollments se JOIN upcoming_courses uc ON se.course_id = uc.id WHERE se.student_id = ?");
        $enrollStmt->execute([$userId]);
        $enrolledCourses = $enrollStmt->fetchAll(PDO::FETCH_ASSOC);

        // For each enrolled course, fetch the matching future exam
        $examStmt = $db->prepare("SELECT * FROM exam_schedules WHERE course_code LIKE CONCAT('%', ?, '%') AND section = ? AND exam_date >= CURDATE() ORDER BY exam_date, exam_time LIMIT 1");
        foreach ($enrolledCourses as $course) {
            $examStmt->execute([$course['course_code'], $course['section']]);
            $exam = $examStmt->fetch(PDO::FETCH_ASSOC);
            if ($exam) {
                $exams[] = $exam;
            }
        }
    }
} catch(PDOException $e) {
    // A more user-friendly error message
    echo "<p>Could not retrieve exam schedule. Please try again later.</p>";
    // For debugging, you can uncomment the next line
    // echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Schedule - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/exam-schedule.css">
</head>

<body>
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main id="main-content" class="main-content fade-transition fade-out" style="">
    <section class="welcome-section">
        <h1>Exam Schedule</h1>
        <p class="text-muted">View your upcoming exams and their details</p>
    </section>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Code</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Section</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($exams)): ?>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam['course_title']); ?></td>
                            <td><?php echo htmlspecialchars($exam['course_code']); ?></td>
                            <td class="exam-date"><?php echo date('M j, Y', strtotime($exam['exam_date'])); ?></td>
                            <td><?php echo htmlspecialchars($exam['exam_time']); ?></td>
                            <td><?php echo htmlspecialchars($exam['section']); ?></td>
                            <td><?php echo htmlspecialchars($exam['room']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class='bx bx-calendar-x'></i>
                                <p>No exams scheduled for your enrolled courses</p>
                                <small class="text-muted">Please check back later or contact your department for updated exam schedules.</small>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
  </main>
</div>
</body>
</html> 