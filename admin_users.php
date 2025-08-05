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

    // Handle user actions
    $actionMsg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_user_id'])) {
            $deleteId = intval($_POST['delete_user_id']);
            if ($deleteId !== $admin['id']) { // Prevent self-delete
                $del = $db->prepare("DELETE FROM users WHERE id = ?");
                $del->execute([$deleteId]);
                $actionMsg = 'User deleted successfully.';
            } else {
                $actionMsg = 'You cannot delete your own admin account.';
            }
        } elseif (isset($_POST['edit_user_id'])) {
            $editId = intval($_POST['edit_user_id']);
            $newName = trim($_POST['edit_name']);
            $newEmail = trim($_POST['edit_email']);
            $newRole = $_POST['edit_role'];
            $upd = $db->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $upd->execute([$newName, $newEmail, $newRole, $editId]);
            $actionMsg = 'User updated successfully.';
        }
    }

    // Fetch all users
    $users = $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Management - CampusLynk</title>
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
        <div class="card">
            <h2 class="text-2xl font-bold mb-6">User Management</h2>
            <p class="text-muted mb-6">Manage users and their roles</p>
            <div class="card-body">
                <h3 class="card-title">User List</h3>
                <?php if (!empty($actionMsg)): ?>
                    <div class="alert alert-success mb-4"><?php echo htmlspecialchars($actionMsg); ?></div>
                <?php endif; ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <form method="POST" style="display:contents;">
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><input type="text" name="edit_name" value="<?php echo htmlspecialchars($user['name']); ?>" class="form-input" style="width:120px;"></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><input type="email" name="edit_email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-input" style="width:180px;"></td>
                                    <td>
                                        <select class="form-select" name="edit_role">
                                            <option value="student" <?php if($user['role']==='student') echo 'selected'; ?>>Student</option>
                                            <option value="faculty" <?php if($user['role']==='faculty') echo 'selected'; ?>>Faculty</option>
                                            <option value="admin" <?php if($user['role']==='admin') echo 'selected'; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <input type="hidden" name="edit_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" style="margin-right:4px;">Save</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" <?php if($user['id']===$admin['id']) echo 'disabled'; ?>>Delete</button>
                                    </form>
                                    </td>
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