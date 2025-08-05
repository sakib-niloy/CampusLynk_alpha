<?php
session_start();
require_once 'config/database.php';

echo "<h2>Test Exam Schedules for Enrolled Courses</h2>";

// Check if user is logged in
if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    echo "<p style='color: red;'>❌ User is NOT logged in!</p>";
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user id
$userQuery = "SELECT id FROM users WHERE email = ?";
$userStmt = $db->prepare($userQuery);
$userStmt->execute([$_SESSION["useremail"]]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p style='color: red;'>❌ User not found in database!</p>";
    exit();
}

// Get enrolled courses
$enrollStmt = $db->prepare("SELECT uc.course_code, uc.course_title, se.section FROM student_enrollments se JOIN upcoming_courses uc ON se.course_id = uc.id WHERE se.student_id = ?");
$enrollStmt->execute([$user['id']]);
$enrolledCourses = $enrollStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Enrolled Courses:</h3>";
echo "<table border='1'>";
echo "<tr><th>Course Code</th><th>Course Title</th><th>Section</th></tr>";
foreach ($enrolledCourses as $course) {
    echo "<tr>";
    echo "<td>" . $course['course_code'] . "</td>";
    echo "<td>" . $course['course_title'] . "</td>";
    echo "<td>" . $course['section'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// For each enrolled course, fetch the matching future exam
$examStmt = $db->prepare("SELECT * FROM exam_schedules WHERE course_code LIKE CONCAT('%', ?, '%') AND section = ? AND exam_date >= CURDATE() ORDER BY exam_date, exam_time");
echo "<h3>Exam Schedule Matches:</h3>";
echo "<table border='1'>";
echo "<tr><th>Course Code</th><th>Section</th><th>Exam Date</th><th>Exam Time</th><th>Room</th><th>Status</th></tr>";
foreach ($enrolledCourses as $course) {
    $examStmt->execute([$course['course_code'], $course['section']]);
    $exam = $examStmt->fetch(PDO::FETCH_ASSOC);
    echo "<tr>";
    echo "<td>" . $course['course_code'] . "</td>";
    echo "<td>" . $course['section'] . "</td>";
    if ($exam) {
        echo "<td>" . $exam['exam_date'] . "</td>";
        echo "<td>" . $exam['exam_time'] . "</td>";
        echo "<td>" . $exam['room'] . "</td>";
        echo "<td style='color: green;'>Found</td>";
    } else {
        echo "<td colspan='3'></td>";
        echo "<td style='color: red;'>Not Found</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Show all future exams for each enrolled course code (regardless of section)
echo "<h3>All Future Exams for Each Enrolled Course Code:</h3>";
foreach ($enrolledCourses as $course) {
    $allExamsStmt = $db->prepare("SELECT * FROM exam_schedules WHERE course_code = ? AND exam_date >= CURDATE() ORDER BY section, exam_date, exam_time");
    $allExamsStmt->execute([$course['course_code']]);
    $allExams = $allExamsStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<b>" . $course['course_code'] . ":</b> ";
    if (empty($allExams)) {
        echo "<span style='color: orange;'>No future exams found for this course code</span><br>";
    } else {
        echo "<table border='1' style='margin-bottom:10px;'>";
        echo "<tr><th>Section</th><th>Exam Date</th><th>Exam Time</th><th>Room</th></tr>";
        foreach ($allExams as $exam) {
            echo "<tr>";
            echo "<td>" . $exam['section'] . "</td>";
            echo "<td>" . $exam['exam_date'] . "</td>";
            echo "<td>" . $exam['exam_time'] . "</td>";
            echo "<td>" . $exam['room'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
?> 