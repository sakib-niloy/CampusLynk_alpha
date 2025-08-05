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
    
    if (isset($_GET['event_id'])) {
        $event_id = $_GET['event_id'];
        
        $query = "SELECT * FROM events WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            throw new Exception("Event not found");
        }
    } else {
        throw new Exception("No event selected");
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/events.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="header-content">
                <a href="eventpage.php" class="back-button">
                    <i class='bx bx-arrow-back'></i>
                    <span>Back to Events</span>
                </a>
                <div class="header-text">
                    <h1>Event Details</h1>
                    <p class="text-muted">View event information and details</p>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php else: ?>
            <div class="event-details-container">
                <div class="event-card">
                    <div class="event-header">
                        <div class="event-icon">
                            <i class='bx bx-calendar-event'></i>
                        </div>
                        <div class="event-title-section">
                            <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                            <div class="event-meta">
                                <span class="event-date">
                                    <i class='bx bxs-calendar'></i>
                                    <?php echo date('F j, Y', strtotime($event['date'])); ?>
                                </span>
                                <?php if (!empty($event['time'])): ?>
                                    <span class="event-time">
                                        <i class='bx bxs-time'></i>
                                        <?php echo htmlspecialchars($event['time']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($event['location'])): ?>
                                    <span class="event-location">
                                        <i class='bx bxs-map'></i>
                                        <?php echo htmlspecialchars($event['location']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="event-content">
                        <?php if (!empty($event['description'])): ?>
                            <div class="event-description">
                                <h3>Description</h3>
                                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['organizer'])): ?>
                            <div class="event-organizer">
                                <h3>Organizer</h3>
                                <p><?php echo htmlspecialchars($event['organizer']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['contact'])): ?>
                            <div class="event-contact">
                                <h3>Contact Information</h3>
                                <p><?php echo htmlspecialchars($event['contact']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="event-actions">
                        <?php if (!empty($event['registration_link'])): ?>
                            <a href="<?php echo htmlspecialchars($event['registration_link']); ?>" class="btn btn-primary" target="_blank">
                                <i class='bx bx-link-external'></i>
                                Register Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>