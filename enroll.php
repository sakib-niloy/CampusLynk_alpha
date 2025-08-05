<?php
session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_code']) || !isset($_POST['section'])) {
    header("Location: dashboard.php?error=Invalid request");
    exit();
}

require_once 'config/database.php';

$course_code = $_POST['course_code'];
$section = $_POST['section'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user ID
    $user_query = $db->prepare("SELECT id FROM users WHERE email = ?");
    $user_query->execute([$_SESSION['useremail']]);
    $user = $user_query->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }
    $student_id = $user['id'];

    // Get course ID from upcoming_courses
    $course_query = $db->prepare("SELECT id FROM upcoming_courses WHERE course_code = ? AND section = ? LIMIT 1");
    $course_query->execute([$course_code, $section]);
    $course = $course_query->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        throw new Exception("Course section not found.");
    }
    $course_id = $course['id'];

    // Check if already enrolled
    $check_enrollment_query = $db->prepare("SELECT id FROM student_enrollments WHERE student_id = ? AND course_id = ?");
    $check_enrollment_query->execute([$student_id, $course_id]);
    if ($check_enrollment_query->fetch()) {
        header("Location: dashboard.php?message=Already enrolled in this course section.");
        exit();
    }

    // Insert into student_enrollments
    $enroll_query = $db->prepare("INSERT INTO student_enrollments (student_id, course_id, section) VALUES (?, ?, ?)");
    $enroll_query->execute([$student_id, $course_id, $section]);

    header("Location: dashboard.php?success=Successfully enrolled!");
    exit();

} catch (Exception $e) {
    header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
    exit();
} 