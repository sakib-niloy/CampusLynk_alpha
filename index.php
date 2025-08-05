<?php
session_start();
require_once 'config/database.php';
$user = null;
$profile_link = 'profile.php';
if (isset($_SESSION['useremail']) && !empty($_SESSION['useremail'])) {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT username, name, role FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['useremail']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        if ($user['role'] === 'admin') {
            $profile_link = 'admin_dashboard.php';
        } elseif ($user['role'] === 'faculty') {
            $profile_link = 'faculty_dashboard.php';
        } else {
            $profile_link = 'dashboard.php';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLynk - Your Digital Campus Companion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Preloader to prevent white flash */
        .page-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse 80% 60% at 20% 20%, #3a5ba0 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 80% 30%, #1e2a47 0%, transparent 80%),
                radial-gradient(ellipse 60% 40% at 60% 80%, #223a6d 0%, transparent 80%),
                linear-gradient(120deg, #101624 0%, #1a2233 100%);
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        .page-preloader.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            background: var(--background, #fff);
            border-radius: 2rem;
            padding: 0.35rem 1.1rem 0.35rem 0.5rem;
            box-shadow: 0 1.5px 6px 0 rgba(0,124,240,0.07);
            border: 1px solid #e2e8f0;
            color: #223a6d;
            font-weight: 500;
            font-size: 1rem;
            text-decoration: none;
            transition: background 0.18s, box-shadow 0.18s;
        }
        .profile-btn:hover {
            background: #f1f5f9;
            box-shadow: 0 4px 16px 0 rgba(0,124,240,0.10);
        }
        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #0066cc;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .profile-username {
            font-weight: 600;
            font-size: 1rem;
            color: #223a6d;
        }
    </style>
</head>
<body>
    <!-- Preloader overlay -->
    <div class="page-preloader" id="pagePreloader"></div>
    
    <header class="landing-header">
        <nav class="landing-navbar">
            <div class="navbar-container">
                <a href="index.php" class="navbar-brand smooth-white"><span>CampusLynk</span></a>
                <div class="nav-links">
                    <?php if ($user): ?>
                        <a href="<?php echo $profile_link; ?>" class="profile-btn">
                            <span class="profile-avatar"><i class='bx bxs-user'></i></span>
                            <span class="profile-username"><?php echo htmlspecialchars($user['username']); ?></span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-login">Login</a>
                        <a href="signup.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    <main class="landing-main">
        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title"><span class="hero-light">Your Digital</span> <br>Campus<span class="campus-gradient"> Companion</span> </h1>
                <p class="hero-subtitle">All your study materials, schedules, and campus events in one beautiful, easy-to-use platform.</p>
                <div class="hero-cta">
                    <a href="signup.php" class="btn btn-primary btn-lg">Get Started</a>
                </div>
            </div>
        </section>
        <section class="features" id="features">
            <h2 class="features-title">Everything You Need for Campus Life</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bxs-book'></i></div>
                    <h3>Study Materials</h3>
                    <p>Access course materials, lecture notes, and study resources anytime, anywhere.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bx-book-content'></i></div>
                    <h3>Course Management</h3>
                    <p>Manage your enrolled courses, track progress, and access course-specific resources.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bxs-calendar'></i></div>
                    <h3>Class Routine</h3>
                    <p>View your daily class schedule, room assignments, and never miss a lecture.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bxs-time'></i></div>
                    <h3>Exam Schedule</h3>
                    <p>Stay updated with exam dates, times, and locations to plan your study sessions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bxs-calendar-event'></i></div>
                    <h3>Campus Events</h3>
                    <p>Discover workshops, seminars, and social events happening on campus.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bxs-user-badge'></i></div>
                    <h3>Faculty Directory</h3>
                    <p>Connect with professors, view their profiles, and schedule counselling sessions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bxs-envelope'></i></div>
                    <h3>Email Generator</h3>
                    <p>Generate professional emails to faculty and staff with our smart assistant.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class='bx bxs-calendar-check'></i></div>
                    <h3>Routine Suggestor</h3>
                    <p>Get personalized study routine suggestions based on your schedule and preferences.</p>
                </div>
            </div>
        </section>
    </main>
    <footer class="footer">
        <div class="footer-container">
            <p>© 2024 CampusLynk. All rights reserved.</p>
            <div class="footer-social">
                <a href="#" title="Twitter"><i class='bx bxl-twitter'></i></a>
                <a href="#" title="Facebook"><i class='bx bxl-facebook'></i></a>
                <a href="#" title="Instagram"><i class='bx bxl-instagram'></i></a>
                <a href="#" title="LinkedIn"><i class='bx bxl-linkedin'></i></a>
            </div>
            <div style="width:100%;text-align:center;margin:1.5rem 0 0 0;">
        <a href="admin_login.php" style="color:#b0bedc;font-size:0.98rem;opacity:0.7;text-decoration:none;transition:color 0.2s;">Enter to mlobby</a>
            </div>
        </div>
    </footer>
    
    <script>
    // Preloader functionality
    document.addEventListener('DOMContentLoaded', function() {
        const preloader = document.getElementById('pagePreloader');
        
        // Hide preloader after page is fully loaded
        window.addEventListener('load', function() {
            setTimeout(() => {
                preloader.classList.add('fade-out');
            }, 200);
        });
        
        // Fallback: hide preloader after 1 second if load event doesn't fire
        setTimeout(() => {
            preloader.classList.add('fade-out');
        }, 1000);
    });
    
    // Automatic scrolling for the features section
    const featuresGrid = document.querySelector('.features-grid');
    let scrollDirection = 1; // 1 for right, -1 for left
    let scrollSpeed = 1; // pixels per frame
    let isScrolling = true;
    
    function autoScroll() {
        if (!isScrolling) return;
        
        const maxScroll = featuresGrid.scrollWidth - featuresGrid.clientWidth;
        
        if (featuresGrid.scrollLeft >= maxScroll) {
            scrollDirection = -1; // Change direction to left
        } else if (featuresGrid.scrollLeft <= 0) {
            scrollDirection = 1; // Change direction to right
        }
        
        featuresGrid.scrollLeft += scrollSpeed * scrollDirection;
        requestAnimationFrame(autoScroll);
    }
    
    // Pause auto-scroll on hover
    featuresGrid.addEventListener('mouseenter', () => {
        isScrolling = false;
    });
    
    featuresGrid.addEventListener('mouseleave', () => {
        isScrolling = true;
        autoScroll();
    });
    
    // Start auto-scroll when page loads
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            autoScroll();
        }, 1000); // Start after 1 second
    });
    
    // Manual scroll still works
    featuresGrid.addEventListener('wheel', function(e) {
      if (e.deltaY === 0) return;
      e.preventDefault();
      featuresGrid.scrollLeft += e.deltaY;
    }, { passive: false });
    </script>
</body>
</html>