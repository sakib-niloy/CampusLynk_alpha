<?php
session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$schedule = [];
$timeSlots = [];
$days = ['Sat', 'Sun', 'Tue', 'Wed'];

try {
    $userQuery = "SELECT id FROM users WHERE email = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$_SESSION["useremail"]]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['id'];

        $routineQuery = "
            SELECT cs.course_code, cs.course_title, cs.section, 
                   cs.time1 as time, cs.day1 as day,
                   cs.faculty_name, cs.room1 as room
            FROM student_enrollments se
            JOIN upcoming_courses uc ON se.course_id = uc.id
            JOIN course_schedules cs ON uc.course_code = cs.course_code 
                AND uc.section = cs.section
            WHERE se.student_id = ?
            UNION
            SELECT cs.course_code, cs.course_title, cs.section,
                   cs.time2 as time, cs.day2 as day,
                   cs.faculty_name, cs.room2 as room
            FROM student_enrollments se
            JOIN upcoming_courses uc ON se.course_id = uc.id
            JOIN course_schedules cs ON uc.course_code = cs.course_code 
                AND uc.section = cs.section
            WHERE se.student_id = ? AND cs.day2 IS NOT NULL
        ";
        $routineStmt = $db->prepare($routineQuery);
        $routineStmt->execute([$userId, $userId]);
        $enrolled_courses = $routineStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($enrolled_courses as $course) {
            if (in_array($course['day'], $days)) {
                $schedule[$course['time']][$course['day']] = $course;
                if (!in_array($course['time'], $timeSlots)) {
                    $timeSlots[] = $course['time'];
                }
            }
        }
        
        usort($timeSlots, function ($a, $b) {
            // Use DateTime objects for a reliable sort
            $timeA_str = trim(explode('-', $a)[0]);
            $timeB_str = trim(explode('-', $b)[0]);
            
            $timeA = DateTime::createFromFormat('h:i:A', $timeA_str);
            $timeB = DateTime::createFromFormat('h:i:A', $timeB_str);

            // Fallback for safety
            if ($timeA === false || $timeB === false) {
                return strtotime($timeA_str) <=> strtotime($timeB_str);
            }
            
            return $timeA <=> $timeB;
        });
    }

} catch (PDOException $e) {
    echo "<script>alert('Database Error: " . $e->getMessage() . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Routine - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/class-routine.css">
</head>

<body>
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main id="main-content" class="main-content fade-transition fade-out" style="">
    <section class="welcome-section">
        <h1>Class Routine</h1>
        <p class="text-muted">Your personalized class schedule for the week</p>
    </section>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Time</th>
                    <?php foreach ($days as $day): ?>
                        <th><?php echo htmlspecialchars($day); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($timeSlots)): ?>
                    <tr>
                        <td colspan="<?php echo count($days) + 1; ?>" style="text-align: center;">You are not enrolled in any courses.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($timeSlots as $time): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($time); ?></td>
                            <?php foreach ($days as $day): ?>
                                <td>
                                    <?php if (isset($schedule[$time][$day])): 
                                        $class = $schedule[$time][$day];
                                    ?>
                                        <div class="class-cell">
                                            <span class="class-name"><?php echo htmlspecialchars($class['course_title']); ?></span>
                                            <span class="class-info"><?php echo htmlspecialchars($class['course_code']); ?> [<?php echo htmlspecialchars($class['section']); ?>]</span>
                                            <span class="class-details">Room: <?php echo htmlspecialchars($class['room']); ?></span>
                                            <span class="class-details">Faculty: <?php echo htmlspecialchars($class['faculty_name']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-cell"></div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
  </main>
</div>
</body>

</html>