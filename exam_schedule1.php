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
    
    // Fetch exam schedules
    $query = "SELECT * FROM exam_schedule ORDER BY exam_date ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <section class="welcome-section">
            <h1>Exam Schedule</h1>
            <p class="text-muted">View your upcoming exams and their details</p>
        </section>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Exam Date</th>
                            <th>Time</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($schedules)): ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['course']); ?></td>
                                    <td><?php echo date('F j, Y', strtotime($schedule['exam_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['exam_time']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No exam schedules available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>