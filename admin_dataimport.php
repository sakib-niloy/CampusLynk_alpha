<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_email']) || empty($_SESSION['admin_email'])) {
    header('Location: admin_login.php');
    exit();
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['admin_email']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        header('Location: admin_login.php');
        exit();
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

$message = '';
$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['schedule_file'])
) {
    $file = $_FILES['schedule_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf'];
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Only PDF files are allowed.';
        } else {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $pdf_path = $upload_dir . 'schedule_import.pdf';
            move_uploaded_file($file['tmp_name'], $pdf_path);

            // Automatically run the Python script
            $output = [];
            $return_var = 0;
            exec('python import_schedule.py 2>&1', $output, $return_var);

            if ($return_var === 0) {
                $message = "File processed and imported successfully.<br>CSV saved as <code>uploads/schedule_import.csv</code>.<br>" . implode('<br>', $output);
            } else {
                // Check for Python not found error
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
    $stmt = $db->query("SELECT * FROM course_schedules ORDER BY id DESC");
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
    <title>Admin Data Import - CampusLynk</title>
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
        <div class="admin-header">
            <div>
                <h1 class="text-2xl font-bold">Data Import</h1>
                <p class="text-muted">Upload and manage class schedules</p>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-success mb-4"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="card mb-6">
            <div class="card-body">
                <h3 class="card-title">Upload Schedule PDF</h3>
                <form method="POST" enctype="multipart/form-data" class="mt-4">
                    <div class="form-group">
                        <label class="form-label">Select PDF File</label>
                        <input type="file" name="schedule_file" class="form-input" accept=".pdf" required>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4">Upload & Import</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Recent Imports</h3>
                <div class="table-responsive mt-4" style="overflow-x:auto;">
                    <table class="table" style="min-width:900px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Course Code</th>
                                <th>Section</th>
                                <th>Day1</th>
                                <th>Day2</th>
                                <th>Time1</th>
                                <th>Time2</th>
                                <th>Faculty</th>
                                <th>Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['id']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['section']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['day1']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['day2']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['time1']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['time2']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['faculty_name']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['credit']); ?></td>
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