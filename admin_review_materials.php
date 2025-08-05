<?php
session_start();
if (!isset($_SESSION['admin_email']) || empty($_SESSION['admin_email'])) {
    header('Location: admin_login.php');
    exit();
}
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $stmt = $db->prepare("SELECT * FROM pending_materials WHERE id = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($material && $material['status'] === 'pending') {
        $course_code = $material['course_code'];
        $filename = $material['filename'];
        $original_path = $material['original_path'];
        if ($action === 'approve') {
            $target_dir = __DIR__ . "/study_materials/" . $course_code;
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . '/' . $filename;
            if (rename($original_path, $target_file)) {
                $db->prepare("UPDATE pending_materials SET status='approved' WHERE id=?")->execute([$id]);
                $msg = 'Material approved and published.';
            } else {
                $msg = 'Failed to move file.';
            }
        } elseif ($action === 'reject') {
            $db->prepare("UPDATE pending_materials SET status='rejected' WHERE id=?")->execute([$id]);
            if (file_exists($original_path)) {
                unlink($original_path);
            }
            $msg = 'Material rejected.';
        }
    } else {
        $msg = 'Invalid or already processed.';
    }
    header('Location: admin_review_materials.php?msg=' . urlencode($msg));
    exit();
}

// Fetch all pending materials
$stmt = $db->prepare("SELECT * FROM pending_materials WHERE status='pending' ORDER BY uploaded_at DESC");
$stmt->execute();
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Study Materials - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/materials-list.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <div class="admin-header">
        <h1 class="text-2xl font-bold">Review Study Materials</h1>
        <p class="text-muted">Approve or reject student-uploaded materials</p>
    </div>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <?php if (empty($pending)): ?>
                <div class="empty-state">
                    <i class='bx bx-file-blank'></i>
                    <p>No pending materials for review.</p>
                </div>
            <?php else: ?>
                <table class="materials-list-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>File</th>
                            <th>Uploader</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $mat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mat['course_code']); ?></td>
                            <td><a href="<?php echo str_replace(__DIR__, '', $mat['original_path']); ?>" target="_blank"><?php echo htmlspecialchars($mat['filename']); ?></a></td>
                            <td><?php echo htmlspecialchars($mat['uploader_email']); ?></td>
                            <td><?php echo htmlspecialchars($mat['uploaded_at']); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $mat['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-primary" onclick="return confirm('Approve this material?');">Approve</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $mat['id']; ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-secondary" onclick="return confirm('Reject this material?');">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html> 