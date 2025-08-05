<?php
session_start();
if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"]) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}
require_once 'config/database.php';
$db = (new Database())->getConnection();
$success = $error = '';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($title && $date && $description) {
        $stmt = $db->prepare("INSERT INTO events (title, date, description) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $date, $description])) {
            $success = "Event added successfully.";
        } else {
            $error = "Failed to add event.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
// Fetch events
$events = $db->query("SELECT * FROM events ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - CampusLynk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <section class="welcome-section">
        <h1>Event Management</h1>
        <p class="text-muted">Add and manage campus events and their email notifications</p>
    </section>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="card" style="max-width: 600px; margin-bottom: 2rem;">
        <h2 class="text-lg font-semibold mb-3">Add New Event</h2>
        <form method="post" class="space-y-4">
            <div class="form-group">
                <label class="form-label">Event Title</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Event Date</label>
                <input type="date" name="date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Event</button>
        </form>
    </div>
    <div class="card">
        <h2 class="text-lg font-semibold mb-3">Existing Events</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                    <td><?php echo htmlspecialchars($event['date']); ?></td>
                    <td><?php echo htmlspecialchars($event['description']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html> 