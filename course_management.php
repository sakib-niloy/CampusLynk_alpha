<?php
session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$user = null;
$enrolled_courses = [];
$all_courses = [];
$success = $error = '';

try {
    // Fetch user data
    $userQuery = "SELECT * FROM users WHERE email = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$_SESSION["useremail"]]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['id'];

        // Handle enroll/unenroll actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['enroll_course_id'])) {
                $course_id = intval($_POST['enroll_course_id']);
                $section = isset($_POST['enroll_section']) ? trim($_POST['enroll_section']) : '';
                if ($section === '') {
                    $error = "Please select a section.";
                } else {
                    // Check if already enrolled
                    $checkStmt = $db->prepare("SELECT COUNT(*) FROM student_enrollments WHERE student_id = ? AND course_id = ?");
                    $checkStmt->execute([$userId, $course_id]);
                    if ($checkStmt->fetchColumn() == 0) {
                        $enrollStmt = $db->prepare("INSERT INTO student_enrollments (student_id, course_id, section) VALUES (?, ?, ?)");
                        $enrollStmt->execute([$userId, $course_id, $section]);
                        $success = "Successfully enrolled in course.";
                    } else {
                        $error = "Already enrolled in this course.";
                    }
                }
            } elseif (isset($_POST['unenroll_id'])) {
                $enrollment_id = intval($_POST['unenroll_id']);
                $delStmt = $db->prepare("DELETE FROM student_enrollments WHERE id = ? AND student_id = ?");
                $delStmt->execute([$enrollment_id, $userId]);
                if ($delStmt->rowCount() > 0) {
                    $success = "Successfully unenrolled from the course.";
                } else {
                    $error = "Could not unenroll. Please try again.";
                }
            }
        }

        // Fetch enrolled courses
        $coursesQuery = "
            SELECT 
                se.id as enrollment_id,
                uc.course_code,
                uc.course_title,
                uc.section
            FROM student_enrollments se
            JOIN upcoming_courses uc ON se.course_id = uc.id
            WHERE se.student_id = ?
            ORDER BY uc.course_title
        ";
        $coursesStmt = $db->prepare($coursesQuery);
        $coursesStmt->execute([$userId]);
        $enrolled_courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all available courses (not already enrolled)
        $enrolled_ids = array_map(function($c) { return $c['course_code']; }, $enrolled_courses);
        $allCoursesQuery = "SELECT id, course_code, course_title, section FROM upcoming_courses ORDER BY course_title";
        $allCoursesStmt = $db->query($allCoursesQuery);
        $all_courses = $allCoursesStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        session_destroy();
        header("Location: login.php?error=User not found");
        exit();
    }
} catch (PDOException $e) {
    $error = "Database error. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main id="main-content" class="main-content fade-transition fade-out" style="">
    <section class="welcome-section">
        <h1>Course Management</h1>
        <p class="text-muted">Manage your enrolled courses</p>
    </section>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="course-management-grid">
        <section class="enroll-course card-style">
            <h2 style="font-size: 1.35rem; font-weight: 600; margin-bottom: 1.2rem;">Enroll in a New Course</h2>
            <form action="course_management.php" method="POST" class="form-grid">
                <div class="form-group">
                    <label for="enroll_course_code" class="form-label">Course</label>
                    <select name="enroll_course_code" id="enroll_course_code" class="form-input" required>
                        <option value=""> Select a Course </option>
                        <?php
                        $uniqueCourses = [];
                        foreach ($all_courses as $course) {
                            if (!isset($uniqueCourses[$course['course_code']])) {
                                $uniqueCourses[$course['course_code']] = $course['course_title'];
                            }
                        }
                        foreach ($uniqueCourses as $code => $title): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($title) . ' (' . htmlspecialchars($code) . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="enroll_section" class="form-label">Section</label>
                    <select name="enroll_section" id="enroll_section" class="form-input" required disabled>
                        <option value=""> Select a Section </option>
                    </select>
                </div>
                <input type="hidden" name="enroll_course_id" id="enroll_course_id_hidden">
                <button type="submit" class="btn btn-primary">Enroll</button>
            </form>
        </section>
        <section class="enrolled-courses card-style">
            <h2 style="font-size: 1.35rem; font-weight: 600; margin-bottom: 1.2rem;">My Courses</h2>
            <div class="courses-list">
                <?php if (empty($enrolled_courses)): ?>
                    <p>You are not enrolled in any courses.</p>
                <?php else: ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="course-item">
                            <div class="course-details">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></h3>
                                <p class="course-meta"><?php echo htmlspecialchars($course['course_code']); ?> - Section <?php echo htmlspecialchars($course['section']); ?></p>
                            </div>
                            <form action="course_management.php" method="POST" onsubmit="return confirm('Are you sure you want to unenroll from this course?');">
                                <input type="hidden" name="unenroll_id" value="<?php echo $course['enrollment_id']; ?>">
                                <button type="submit" class="btn-unenroll">
                                    <i class='bx bx-trash'></i>
                                    <span>Remove</span>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
  </main>
</div>
<script>
// Prepare course-section mapping
const courseSectionMap = {};
<?php foreach ($all_courses as $course): ?>
    if (!courseSectionMap[<?php echo json_encode($course['course_code']); ?>]) {
        courseSectionMap[<?php echo json_encode($course['course_code']); ?>] = [];
    }
    courseSectionMap[<?php echo json_encode($course['course_code']); ?>].push({
        id: <?php echo json_encode($course['id']); ?>,
        section: <?php echo json_encode($course['section']); ?>
    });
<?php endforeach; ?>

const courseSelect = document.getElementById('enroll_course_code');
const sectionSelect = document.getElementById('enroll_section');
const courseIdHidden = document.getElementById('enroll_course_id_hidden');

courseSelect.addEventListener('change', function() {
    const code = this.value;
    sectionSelect.innerHTML = '<option value="">-- Select a Section --</option>';
    sectionSelect.disabled = true;
    courseIdHidden.value = '';
    if (code && courseSectionMap[code]) {
        courseSectionMap[code].forEach(function(item) {
            const opt = document.createElement('option');
            opt.value = item.section;
            opt.textContent = 'Section ' + item.section;
            opt.dataset.courseId = item.id;
            sectionSelect.appendChild(opt);
        });
        sectionSelect.disabled = false;
    }
});
sectionSelect.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    courseIdHidden.value = selectedOption && selectedOption.dataset.courseId ? selectedOption.dataset.courseId : '';
});
</script>
</body>
</html> 