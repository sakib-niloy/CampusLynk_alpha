<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_email']) || empty($_SESSION['admin_email'])) {
    header('Location: admin_login.php');
    exit();
}

$db = (new Database())->getConnection();
$message = '';
$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['schedule_file'])
) {
    $file = $_FILES['schedule_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Only PDF or Excel files are allowed.';
        } else {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $target_path = $upload_dir . 'class_schedule_import.' . $ext;
            move_uploaded_file($file['tmp_name'], $target_path);

            // Automatically run the Python script (to be implemented: import_class_schedule.py)
            $output = [];
            $return_var = 0;
            exec('python import_class_schedule.py 2>&1', $output, $return_var);

            if ($return_var === 0) {
                $message = "File processed and imported successfully.<br>" . implode('<br>', $output);
            } else {
                $output_str = implode('\n', $output);
                if (strpos($output_str, "'python' is not recognized as an internal or external command") !== false) {
                    $error = "Error: Python is not installed or not in your system PATH.<br>" .
                        "Please install Python from <a href='https://www.python.org/downloads/' target='_blank'>python.org</a> and ensure it is added to your PATH environment variable.";
                } else {
                    $error = "Error running import script:<br>" . implode('<br>', $output);
                }
            }
        }
    } else {
        $error = 'Error uploading file. Please try again.';
    }
}

$schedules = [];
try {
    $stmt = $db->query("SELECT * FROM upcoming_courses ORDER BY id DESC");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching schedules: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Class Schedule - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <section class="welcome-section">
        <h1>Ongoing Class Schedule Import</h1>
        <p class="text-muted">Import the current class routine from a PDF or Excel file into the system.</p>
    </section>
    <?php if ($message): ?>
        <div class="alert alert-success mb-4"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="card mb-6">
        <div class="card-body">
            <h3 class="card-title">Upload Class Schedule (PDF or Excel)</h3>
            <form method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="form-group">
                    <label class="form-label">Select File</label>
                    <input type="file" name="schedule_file" class="form-input" accept=".pdf,.xlsx,.xls" required>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Upload & Import</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">Recent Imports</h3>
            <div class="table-responsive mt-4" style="overflow-x:auto;">
                <table class="table" style="min-width:1200px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program</th>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Section</th>
                            <th>Room 1</th>
                            <th>Room 2</th>
                            <th>Day 1</th>
                            <th>Day 2</th>
                            <th>Time 1</th>
                            <th>Time 2</th>
                            <th>Faculty Name</th>
                            <th>Faculty Initial</th>
                            <th>Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['program']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_title']); ?></td>
                            <td><?php echo htmlspecialchars($row['section']); ?></td>
                            <td><?php echo htmlspecialchars($row['room1']); ?></td>
                            <td><?php echo htmlspecialchars($row['room2']); ?></td>
                            <td><?php echo htmlspecialchars($row['day1']); ?></td>
                            <td><?php echo htmlspecialchars($row['day2']); ?></td>
                            <td><?php echo htmlspecialchars($row['time1']); ?></td>
                            <td><?php echo htmlspecialchars($row['time2']); ?></td>
                            <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['faculty_initial']); ?></td>
                            <td><?php echo htmlspecialchars($row['credit']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html> 