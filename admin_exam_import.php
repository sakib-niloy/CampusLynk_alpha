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
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['exam_schedule_file'])
) {
    $file = $_FILES['exam_schedule_file'];
    $allowed_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $error = 'Only Excel files (.xlsx, .xls) are allowed.';
        } else {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $excel_path = $upload_dir . 'exam_schedule_import.' . $ext;
            move_uploaded_file($file['tmp_name'], $excel_path);

            // Call the Python script to process the file
            $output = [];
            $return_var = 0;
            exec('python import_exam_schedule.py ' . escapeshellarg($excel_path) . ' 2>&1', $output, $return_var);

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

$examSchedules = [];
try {
    $stmt = $db->query("SELECT * FROM exam_schedules ORDER BY id DESC LIMIT 50");
    $examSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching exam schedules: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Schedule Importer - CampusLynk</title>
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
                <h1 class="text-2xl font-bold">Exam Schedule Importer</h1>
                <p class="text-muted">Upload and manage exam schedules</p>
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
                <h3 class="card-title">Upload Exam Schedule (Excel)</h3>
                <form method="POST" enctype="multipart/form-data" class="mt-4" id="exam-import-form">
                    <div class="form-group">
                        <label class="form-label">Select Excel File (.xlsx, .xls)</label>
                        <input type="file" name="exam_schedule_file" class="form-input" accept=".xlsx,.xls" required>
                    </div>
                    <div class="alert alert-warning mt-2">Importing a new exam schedule will <b>delete all previous exam schedules</b> from the database. Please proceed with caution.</div>
                    <button type="submit" class="btn btn-primary mt-4">Upload & Import</button>
                </form>
                <script>
                document.getElementById('exam-import-form').addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to import this exam schedule? This will DELETE all previous exam schedules from the database.')) {
                        e.preventDefault();
                    }
                });
                </script>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Recent Exam Schedule Imports</h3>
                <div class="table-responsive mt-4" style="overflow-x:auto;">
                    <table class="table" style="min-width:1000px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Department</th>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Section</th>
                                <th>Teacher</th>
                                <th>Exam Date</th>
                                <th>Exam Time</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examSchedules as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['id']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['department']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['section']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['teacher']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_date']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_time']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['room']); ?></td>
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