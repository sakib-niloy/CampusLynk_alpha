<?php
session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $query = $db->prepare("SELECT * FROM faculty");
    $query->execute();
    $facultyList = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $facultyList = [];
    $error = "Database Error: " . htmlspecialchars($e->getMessage());
}

$pinnedFacultyIds = [];
$user = null;
if (isset($_SESSION["useremail"])) {
    $userQuery = $db->prepare("SELECT * FROM users WHERE email = ?");
    $userQuery->execute([$_SESSION["useremail"]]);
    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
}
if ($user && $user['role'] === 'student') {
    // Get faculty names from enrolled courses
    $facultyStmt = $db->prepare("
        SELECT DISTINCT f.id
        FROM student_enrollments se
        JOIN upcoming_courses uc ON se.course_id = uc.id
        JOIN faculty f ON uc.faculty_name = f.name
        WHERE se.student_id = ?
    ");
    $facultyStmt->execute([$user['id']]);
    $pinnedFacultyIds = array_column($facultyStmt->fetchAll(PDO::FETCH_ASSOC), 'id');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/faculty.css">
</head>

<body>
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main id="main-content" class="main-content fade-transition fade-out" style="">
    <section class="welcome-section">
        <h1>Faculty Members</h1>
        <p class="text-muted">View all faculty members and their details</p>
    </section>
    <div class="faculty-search-bar" style="margin-bottom: 1.5rem; max-width: 400px;">
        <input type="text" id="facultySearch" class="form-input" placeholder="Search faculty by name, email, or designation..." oninput="filterFaculty()">
    </div>
    <div class="faculty-grid" id="facultyGrid">
        <?php
        $pinned = [];
        $others = [];
        foreach ($facultyList as $faculty) {
            if (in_array($faculty['id'], $pinnedFacultyIds)) {
                $pinned[] = $faculty;
            } else {
                $others[] = $faculty;
            }
        }
        ?>
        <?php if (!empty($pinned)): ?>
            <div class="faculty-section-label" style="grid-column: 1 / -1; font-weight: 600; color: var(--primary); margin-bottom: 0.5rem;">Pinned Faculty</div>
            <?php foreach ($pinned as $faculty): ?>
                <div class="faculty-card" data-name="<?php echo htmlspecialchars(strtolower($faculty['name'])); ?>" data-email="<?php echo htmlspecialchars(strtolower($faculty['email'])); ?>" data-title="<?php echo htmlspecialchars(strtolower($faculty['designation'] ?? '')); ?>">
                    <div class="faculty-avatar">
                        <i class='bx bxs-user-circle'></i>
                    </div>
                    <h3 class="faculty-name"><?php echo htmlspecialchars($faculty['name']); ?> <span title="Enrolled Faculty" style="color: gold; font-size: 1.1em;"><i class='bx bxs-star'></i></span></h3>
                    <p class="faculty-title"><?php echo htmlspecialchars($faculty['designation'] ?? 'Faculty Member'); ?></p>
                    <div class="faculty-actions">
                        <a href="mailto:<?php echo htmlspecialchars($faculty['email']); ?>" class="btn btn-outline btn-sm">
                            <i class='bx bx-envelope'></i>
                            Contact
                        </a>
                        <button class="btn btn-secondary btn-sm counselling-btn" data-faculty-id="<?php echo $faculty['id']; ?>" data-faculty-name="<?php echo htmlspecialchars($faculty['name']); ?>">
                            <i class='bx bx-time'></i>
                            Counselling
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($others)): ?>
            <div class="faculty-section-label" style="grid-column: 1 / -1; font-weight: 600; color: var(--muted-foreground); margin: 1.5rem 0 0.5rem 0;">Other Faculty</div>
            <?php foreach ($others as $faculty): ?>
                <div class="faculty-card" data-name="<?php echo htmlspecialchars(strtolower($faculty['name'])); ?>" data-email="<?php echo htmlspecialchars(strtolower($faculty['email'])); ?>" data-title="<?php echo htmlspecialchars(strtolower($faculty['designation'] ?? '')); ?>">
                    <div class="faculty-avatar">
                        <i class='bx bxs-user-circle'></i>
                    </div>
                    <h3 class="faculty-name"><?php echo htmlspecialchars($faculty['name']); ?></h3>
                    <p class="faculty-title"><?php echo htmlspecialchars($faculty['designation'] ?? 'Faculty Member'); ?></p>
                    <div class="faculty-actions">
                        <a href="mailto:<?php echo htmlspecialchars($faculty['email']); ?>" class="btn btn-outline btn-sm">
                            <i class='bx bx-envelope'></i>
                            Contact
                        </a>
                        <button class="btn btn-secondary btn-sm counselling-btn" data-faculty-id="<?php echo $faculty['id']; ?>" data-faculty-name="<?php echo htmlspecialchars($faculty['name']); ?>">
                            <i class='bx bx-time'></i>
                            Counselling
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (empty($facultyList)): ?>
            <div class="empty-state">
                <i class='bx bx-user-x'></i>
                <p>No faculty members found</p>
                <?php if (isset($error)): ?>
                    <p class="error-message"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal for Counselling Times -->
    <div id="counsellingModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalFacultyName"></h2>
                <button id="closeModal" class="modal-close">&times;</button>
            </div>
            <div id="modalBody" class="modal-body">
                <!-- Content will be loaded here via JS -->
            </div>
            <div class="modal-footer" id="modalFooter">
                <!-- Request button will be injected here -->
            </div>
        </div>
    </div>

    <script>
    function filterFaculty() {
        var input = document.getElementById('facultySearch').value.toLowerCase();
        var cards = document.querySelectorAll('.faculty-card');
        cards.forEach(function(card) {
            var name = card.getAttribute('data-name');
            var email = card.getAttribute('data-email');
            var title = card.getAttribute('data-title');
            if (name.includes(input) || email.includes(input) || title.includes(input)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('counsellingModal');
        const closeModal = document.getElementById('closeModal');
        const modalFacultyName = document.getElementById('modalFacultyName');
        const modalBody = document.getElementById('modalBody');
        const modalFooter = document.getElementById('modalFooter');

        document.querySelectorAll('.counselling-btn').forEach(button => {
            button.addEventListener('click', function() {
                const facultyId = this.dataset.facultyId;
                const facultyName = this.dataset.facultyName;
                
                modalFacultyName.textContent = `Counselling Hours for ${facultyName}`;
                modalBody.innerHTML = '<p>Loading...</p>';
                modalFooter.innerHTML = ''; // Clear footer
                modal.style.display = 'flex';

                fetch(`get_counselling_times.php?faculty_id=${facultyId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            modalBody.innerHTML = `<p class="error-message">${data.error}</p>`;
                        } else if (data.length === 0) {
                            modalBody.innerHTML = '<p>No counselling hours have been set by this faculty member.</p>';
                        } else {
                            let formHtml = '<form id="requestForm">';
                            formHtml += '<p>Please select a time slot to request a counselling session.</p>';
                            formHtml += '<table class="counselling-schedule-table"><thead><tr><th>Day</th><th>Time</th><th>Select</th></tr></thead><tbody>';
                            data.forEach(slot => {
                                const timeValue = `${slot.day_of_week} ${slot.start_time}-${slot.end_time}`;
                                formHtml += `<tr><td>${slot.day_of_week}</td><td>${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</td><td><input type="radio" name="requested_time" value="${timeValue}"></td></tr>`;
                            });
                            formHtml += '</tbody></table></form>';
                            modalBody.innerHTML = formHtml;
                            modalFooter.innerHTML = '<button type="submit" form="requestForm" id="submitRequestBtn" class="btn btn-primary" disabled>Send Request</button>';
                            
                            const requestForm = document.getElementById('requestForm');
                            const submitRequestBtn = document.getElementById('submitRequestBtn');

                            requestForm.addEventListener('change', function() {
                                submitRequestBtn.disabled = false;
                            });

                            requestForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                submitRequestBtn.disabled = true;
                                submitRequestBtn.textContent = 'Sending...';

                                const selectedTime = new FormData(requestForm).get('requested_time');

                                fetch('request_counselling.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        faculty_id: facultyId,
                                        requested_time: selectedTime
                                    })
                                })
                                .then(res => res.json())
                                .then(result => {
                                    if (result.success) {
                                        modalBody.innerHTML = `<div class="alert alert-success">${result.success}</div>`;
                                        modalFooter.innerHTML = '';
                                    } else {
                                        modalBody.innerHTML = `<div class="alert alert-error">${result.error || 'An unknown error occurred.'}</div>`;
                                        submitRequestBtn.disabled = false;
                                        submitRequestBtn.textContent = 'Send Request';
                                    }
                                })
                                .catch(err => {
                                    modalBody.innerHTML = `<div class="alert alert-error">An error occurred while sending the request.</div>`;
                                    submitRequestBtn.disabled = false;
                                    submitRequestBtn.textContent = 'Send Request';
                                });
                            });
                        }
                    })
                    .catch(error => {
                        modalBody.innerHTML = '<p class="error-message">Failed to load schedule. Please try again later.</p>';
                        console.error('Error fetching counselling times:', error);
                    });
            });
        });

        function formatTime(time) {
            const [h, m] = time.split(':');
            const hours = parseInt(h, 10);
            const minutes = m;
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const formattedHours = hours % 12 || 12;
            return `${formattedHours}:${minutes} ${ampm}`;
        }

        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
  </main>
</div>
</body>

</html>