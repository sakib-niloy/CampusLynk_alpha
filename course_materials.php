<?php
session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['course_code']) || empty($_GET['course_code'])) {
    header("Location: study-materials.php?error=No course selected.");
    exit();
}

$course_code = $_GET['course_code'];
$course_title = "Materials for " . htmlspecialchars($course_code); // Default title

// You could optionally fetch the real course title from the DB for a better header
// require_once 'config/database.php';
// $database = new Database();
// $db = $database->getConnection();
// $query = $db->prepare("SELECT course_title FROM upcoming_courses WHERE course_code = ? LIMIT 1");
// $query->execute([$course_code]);
// $course = $query->fetch(PDO::FETCH_ASSOC);
// if ($course) {
//     $course_title = htmlspecialchars($course['course_title']);
// }

$materials_path = "study_materials/" . $course_code;
$files = [];

if (is_dir($materials_path)) {
    // Scan for pdf files
    $all_files = scandir($materials_path);
    foreach ($all_files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
            $files[] = $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $course_title; ?> - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/materials-list.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <section class="page-header">
            <a href="study-materials.php" class="back-link"><i class='bx bx-arrow-back'></i> Back to Courses</a>
            <h1><?php echo $course_title; ?></h1>
            <p class="text-muted">Available study materials for this course</p>
        </section>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert <?php echo isset($_GET['error']) ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="add-material-section" style="margin-bottom:2rem;">
            <button id="show-upload-form" class="btn btn-primary" style="margin-bottom:1rem;">Add Study Material</button>
            <form id="upload-form" action="upload_study_material.php" method="post" enctype="multipart/form-data" style="display:none;" onsubmit="return confirm('Upload this file?');">
                <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course_code); ?>">
                <label for="material_file">Select PDF file:</label>
                <input type="file" name="material_file" id="material_file" accept="application/pdf" required>
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>
        <script>
        document.getElementById('show-upload-form').onclick = function() {
            document.getElementById('upload-form').style.display = 'block';
            this.style.display = 'none';
        };
        </script>

        <div class="materials-container">
            <?php if (empty($files)): ?>
                <div class="empty-state">
                    <i class='bx bx-file-blank'></i>
                    <p>No study materials have been uploaded for this course yet.</p>
                </div>
            <?php else: ?>
                <ul class="materials-list">
                    <?php foreach ($files as $file): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($materials_path . '/' . $file); ?>" target="_blank" class="material-item">
                                <i class='bx bxs-file-pdf'></i>
                                <span><?php echo htmlspecialchars($file); ?></span>
                                <i class='bx bx-link-external link-icon'></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 