<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Debug Exam Schedule</h2>";

// Get user info
$userQuery = "SELECT id, email FROM users WHERE email = ?";
$userStmt = $db->prepare($userQuery);
$userStmt->execute([$_SESSION["useremail"]]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>User Info:</h3>";
echo "Email: " . $_SESSION["useremail"] . "<br>";
echo "User ID: " . $user['id'] . "<br><br>";

// Get all enrolled courses
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

// Check all future exams
$futureExamsStmt = $db->prepare("SELECT * FROM exam_schedules WHERE exam_date >= CURDATE() ORDER BY exam_date, exam_time");
$futureExamsStmt->execute();
$futureExams = $futureExamsStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>All Future Exams:</h3>";
echo "<table border='1'>";
echo "<tr><th>Course Code</th><th>Course Title</th><th>Section</th><th>Date</th><th>Time</th></tr>";
foreach ($futureExams as $exam) {
    echo "<tr>";
    echo "<td>" . $exam['course_code'] . "</td>";
    echo "<td>" . $exam['course_title'] . "</td>";
    echo "<td>" . $exam['section'] . "</td>";
    echo "<td>" . $exam['exam_date'] . "</td>";
    echo "<td>" . $exam['exam_time'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// Check matches for each enrolled course
echo "<h3>Matching Logic:</h3>";
$examStmt = $db->prepare("SELECT * FROM exam_schedules WHERE course_code = ? AND section = ? AND exam_date >= CURDATE() ORDER BY exam_date, exam_time LIMIT 1");
$foundExams = [];

foreach ($enrolledCourses as $course) {
    echo "<h4>Checking: " . $course['course_code'] . " Section " . $course['section'] . "</h4>";
    
    $examStmt->execute([$course['course_code'], $course['section']]);
    $exam = $examStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exam) {
        echo "✓ Found exam: " . $exam['exam_date'] . " at " . $exam['exam_time'] . "<br>";
        $foundExams[] = $exam;
    } else {
        echo "✗ No matching exam found<br>";
        
        // Check if there are any exams for this course code (any section)
        $anySectionStmt = $db->prepare("SELECT * FROM exam_schedules WHERE course_code = ? AND exam_date >= CURDATE()");
        $anySectionStmt->execute([$course['course_code']]);
        $anySectionExams = $anySectionStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($anySectionExams)) {
            echo "  Note: Found exams for this course code but different sections:<br>";
            foreach ($anySectionExams as $otherExam) {
                echo "  - Section " . $otherExam['section'] . ": " . $otherExam['exam_date'] . "<br>";
            }
        } else {
            echo "  Note: No future exams found for this course code at all<br>";
        }
    }
    echo "<br>";
}

echo "<h3>Final Result - Exams to Display:</h3>";
echo "Total exams found: " . count($foundExams) . "<br>";
foreach ($foundExams as $exam) {
    echo "- " . $exam['course_code'] . " Section " . $exam['section'] . ": " . $exam['exam_date'] . " at " . $exam['exam_time'] . "<br>";
}
?> 