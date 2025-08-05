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
    
    // Get faculty ID from users table, then from faculty table
    $query = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'faculty'");
    $query->execute([$_SESSION['useremail']]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php?error=Faculty not found");
        exit();
    }
    
    $faculty_id_stmt = $db->prepare("SELECT id FROM faculty WHERE email = ?");
    $faculty_id_stmt->execute([$_SESSION['useremail']]);
    $faculty_id_row = $faculty_id_stmt->fetch(PDO::FETCH_ASSOC);
    $faculty_id = $faculty_id_row ? $faculty_id_row['id'] : null;

    if (!$faculty_id) {
        throw new Exception("Could not find faculty-specific ID.");
    }
    
    // Get pending requests for this faculty
    $pending_requests = $db->prepare("SELECT cr.*, u.name as student_name, u.email as student_email FROM counselling_requests cr JOIN users u ON cr.student_id = u.id WHERE cr.faculty_id = ? AND cr.status = 'pending'");
    $pending_requests->execute([$faculty_id]);
    $pending_requests = $pending_requests->fetchAll(PDO::FETCH_ASSOC);

    // Get accepted requests for this faculty
    $accepted_requests = $db->prepare("SELECT cr.*, u.name as student_name FROM counselling_requests cr JOIN users u ON cr.student_id = u.id WHERE cr.faculty_id = ? AND cr.status = 'approved'");
    $accepted_requests->execute([$faculty_id]);
    $accepted_requests = $accepted_requests->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Counselling Requests - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/faculty.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="main-header">
            <h1>Manage Counselling Requests</h1>
            <p class="text-muted">Review, approve, or reject student requests for counselling sessions.</p>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-stack" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <section class="card">
                <h2 class="text-xl font-semibold mb-4"><i class='bx bx-envelope'></i> Pending Counselling Requests</h2>
                <?php if (empty($pending_requests)): ?>
                    <div class="empty-state"><i class='bx bx-message-square-x'></i> No pending requests.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="table-auto w-full">
                            <thead><tr><th>Student</th><th>Email</th><th>Requested Time</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($pending_requests as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['student_email']); ?></td>
                                    <td><?php echo htmlspecialchars(date('D, M j, g:i A', strtotime($req['requested_time']))); ?></td>
                                    <td>
                                        <form method="POST" action="faculty_counselling_action.php" style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                            <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2 class="text-xl font-semibold mb-4"><i class='bx bx-calendar-check'></i> Accepted Counselling Sessions</h2>
                <?php if (empty($accepted_requests)): ?>
                    <div class="empty-state"><i class='bx bx-calendar-x'></i> No accepted counselling sessions.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="table-auto w-full">
                            <thead><tr><th>Student</th><th>Scheduled Time</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($accepted_requests as $ar): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ar['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars(date('D, M j, g:i A', strtotime($ar['requested_time']))); ?></td>
                                    <td>
                                        <form method="POST" action="faculty_counselling_action.php" style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $ar['id']; ?>">
                                            <button name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html> 