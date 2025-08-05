<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday'];
$timeslots = [
    '08:30:00-09:50:00',
    '09:51:00-11:10:00',
    '11:11:00-12:30:00',
    '12:31:00-13:50:00',
    '13:51:00-15:10:00',
    '15:11:00-16:30:00'
];

// Determine mode (view or edit)
$mode = isset($_GET['mode']) && $_GET['mode'] === 'edit' ? 'edit' : 'view';

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'faculty'");
    $query->execute([$_SESSION['useremail']]);
    $faculty_user = $query->fetch(PDO::FETCH_ASSOC);

    if (!$faculty_user) {
        session_destroy();
        header("Location: login.php?error=Faculty not found");
        exit();
    }
    
    $faculty_id_stmt = $db->prepare("SELECT id FROM faculty WHERE email = ?");
    $faculty_id_stmt->execute([$faculty_user['email']]);
    $faculty = $faculty_id_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$faculty) {
        // This case should ideally not happen if user is in users table with role faculty
        header("Location: faculty_dashboard.php?error=Faculty profile not found.");
        exit();
    }
    $faculty_id = $faculty['id'];
    $faculty_name = $faculty_user['name'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'edit') {
        // Clear existing schedule for this faculty
        $delete_stmt = $db->prepare("DELETE FROM counselling_times WHERE faculty_id = ?");
        $delete_stmt->execute([$faculty_id]);

        if (isset($_POST['schedule'])) {
            $insert_stmt = $db->prepare("INSERT INTO counselling_times (faculty_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            foreach ($_POST['schedule'] as $day => $times) {
                foreach ($times as $time_slot) {
                    list($start_time, $end_time) = explode('-', $time_slot);
                    $insert_stmt->execute([$faculty_id, $day, $start_time, $end_time]);
                }
            }
        }
        header("Location: faculty_counselling_schedule.php?success=Schedule updated successfully");
        exit();
    }

    // Initialize the schedule grid
    $schedule_grid = [];
    foreach ($days as $day) {
        foreach ($timeslots as $slot) {
            $schedule_grid[$day][$slot] = 'free';
        }
    }

    // Fetch faculty's class schedule
    $class_stmt = $db->prepare("SELECT day1, time1, day2, time2 FROM course_schedules WHERE faculty_name = ?");
    $class_stmt->execute([$faculty_name]);
    $classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

    $day_map = ['Sat' => 'Saturday', 'Sun' => 'Sunday', 'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday'];

    foreach ($classes as $class) {
        $class_days = array_filter([$class['day1'], $class['day2']]);
        $class_times = array_filter([$class['time1'], $class['time2']]);
    
        if (empty($class_times)) continue;
    
        // Assuming time1 and time2 might be the same or different. For simplicity, we use the first one.
        // A more complex logic would be needed if a class has different times on different days.
        list($start_str, $end_str) = explode(' - ', $class_times[0]);
        $class_start_time = date('H:i:s', strtotime($start_str));
        $class_end_time = date('H:i:s', strtotime($end_str));

        foreach ($class_days as $day_abbr) {
            $full_day_name = $day_map[trim($day_abbr)] ?? null;
            if ($full_day_name && in_array($full_day_name, $days)) {
                foreach ($timeslots as $slot) {
                    list($slot_start_str, $slot_end_str) = explode('-', $slot);
                    // Check for overlap
                    if ($class_start_time < $slot_end_str && $class_end_time > $slot_start_str) {
                         $schedule_grid[$full_day_name][$slot] = 'class';
                    }
                }
            }
        }
    }


    // Fetch existing counselling schedule and mark them
    $schedule_stmt = $db->prepare("SELECT day_of_week, start_time, end_time FROM counselling_times WHERE faculty_id = ?");
    $schedule_stmt->execute([$faculty_id]);
    $counselling_schedule = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counselling_schedule as $slot) {
        $timeslot_key = $slot['start_time'] . '-' . $slot['end_time'];
        if (isset($schedule_grid[$slot['day_of_week']][$timeslot_key])) {
            $schedule_grid[$slot['day_of_week']][$timeslot_key] = 'counselling';
        }
    }

} catch (PDOException $e) {
    // Handle DB error
    $error_message = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselling Schedule - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/faculty.css">
    <style>
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .schedule-table th, .schedule-table td {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: center;
            height: 60px;
        }
        .schedule-table th {
            background-color: #f7f7f7;
        }
        .time-slot-label {
            display: block;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .slot-class { background-color: #ef9a9a; color: #b71c1c; font-weight: 600; }
        .slot-counselling { background-color: #a5d6a7; color: #1b5e20; font-weight: 600;}
        .slot-free { background-color: #ffffff; }
        .slot-disabled {
            background-color: #fce4e4;
            color: #d32f2f;
            cursor: not-allowed;
        }
        .slot-disabled input {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="main-header">
            <h1>Counselling Schedule</h1>
            <p class="text-muted">Select your available time slots for student counselling.</p>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <?php if ($mode === 'view'): ?>
                <div class="table-container">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <?php foreach ($timeslots as $slot): 
                                    list($start, $end) = explode('-', $slot);
                                    echo '<th>' . date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end)) . '</th>';
                                endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days as $day): ?>
                                <tr>
                                    <td><?php echo $day; ?></td>
                                    <?php foreach ($timeslots as $slot):
                                        $status = $schedule_grid[$day][$slot];
                                        
                                        if ($status === 'counselling') {
                                            $class = 'slot-counselling';
                                            $text = 'Counselling';
                                        } else {
                                            $class = 'slot-class';
                                            $text = 'Class';
                                        }
                                        ?>
                                        <td class="<?php echo $class; ?>"><?php echo $text; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1.5rem; text-align: right;">
                    <a href="faculty_counselling_schedule.php?mode=edit" class="btn btn-primary">Update Schedule</a>
                </div>
            <?php else: // Edit mode ?>
                <form action="faculty_counselling_schedule.php?mode=edit" method="POST">
                    <div class="table-container">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <?php foreach ($timeslots as $slot): 
                                        list($start, $end) = explode('-', $slot);
                                        echo '<th>' . date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end)) . '</th>';
                                    endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($days as $day): ?>
                                    <tr>
                                        <td><?php echo $day; ?></td>
                                        <?php foreach ($timeslots as $slot):
                                            $status = $schedule_grid[$day][$slot];
                                            $is_class = $status === 'class';
                                            $is_counselling = $status === 'counselling';
                                            ?>
                                            <td class="<?php if ($is_class) echo 'slot-disabled'; ?>">
                                                <?php if ($is_class): ?>
                                                    Class
                                                <?php else: ?>
                                                <label class="time-slot-label">
                                                    <input type="checkbox" name="schedule[<?php echo $day; ?>][]" value="<?php echo $slot; ?>"
                                                        <?php if ($is_counselling) echo 'checked'; ?>>
                                                </label>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 1.5rem; text-align: right;">
                        <a href="faculty_counselling_schedule.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 