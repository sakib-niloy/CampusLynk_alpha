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
    
    // Fetch all events from the correct table name
    $query = "SELECT * FROM events ORDER BY date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/events.css">
</head>

<body>
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main id="main-content" class="main-content fade-transition fade-out" style="">
    <section class="welcome-section">
        <h1>Events</h1>
        <p class="text-muted">Stay updated with campus events and activities</p>
    </section>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class='bx bx-error-circle'></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="events-grid">
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-content">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                        <div class="event-meta">
                            <span class="event-date">
                                <i class='bx bxs-calendar'></i>
                                <?php echo date('F j, Y', strtotime($event['date'])); ?>
                            </span>
                            <a href="eventpage-p2.php?event_id=<?php echo $event['id']; ?>" 
                               class="btn-view-details">
                                <span>View Details</span>
                                <i class='bx bx-right-arrow-alt'></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-calendar-x'></i>
                <p>No events available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html> 