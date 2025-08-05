<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $new_status = '';

    switch ($action) {
        case 'approve':
            $new_status = 'approved';
            break;
        case 'reject':
            $new_status = 'rejected';
            break;
        case 'cancel':
            $new_status = 'cancelled';
            break;
        default:
            header("Location: faculty_counselling_requests.php?error=Invalid action");
            exit();
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Security: ensure the faculty is authorized to change this request
        $query = $db->prepare("SELECT cr.id FROM counselling_requests cr JOIN faculty f ON cr.faculty_id = f.id WHERE cr.id = ? AND f.email = ?");
        $query->execute([$request_id, $_SESSION['useremail']]);
        if ($query->fetch()) {
            $stmt = $db->prepare("UPDATE counselling_requests SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $request_id]);
            header("Location: faculty_counselling_requests.php?success=Action successful");
        } else {
            header("Location: faculty_counselling_requests.php?error=Unauthorized action");
        }
        exit();

    } catch (PDOException $e) {
        header("Location: faculty_counselling_requests.php?error=Database Error: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: faculty_counselling_requests.php");
    exit();
} 