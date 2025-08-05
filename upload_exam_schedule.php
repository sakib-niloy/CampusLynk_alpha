<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['exam_schedule_file'])) {
    $targetDir = 'uploads/';
    // Sanitize the filename to prevent security issues
    $fileName = preg_replace("/[^a-zA-Z0-9.\-\_]/", "", basename($_FILES['exam_schedule_file']['name']));
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Allow only Excel files
    $allowedTypes = array('xlsx', 'xls');
    if (in_array($fileType, $allowedTypes)) {
        // Ensure the uploads directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['exam_schedule_file']['tmp_name'], $targetFilePath)) {
            // Call the NEW Python script to process the file
            $command = escapeshellcmd("python import_exam_schedule.py " . escapeshellarg($targetFilePath));
            $output = shell_exec($command . " 2>&1");
            
            echo "<h3>File Upload and Processing Status</h3>";
            echo "<a href='admin_dashboard.php'>Back to Admin Dashboard</a><br><br>";
            if ($output !== null) {
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            } else {
                echo "<p>No output from script, or an error occurred that prevented output.</p>";
            }
        } else {
            echo "<h3>Sorry, there was an error uploading your file.</h3>";
        }
    } else {
        echo "<h3>Invalid file type. Only Excel files (.xlsx, .xls) are allowed.</h3>";
    }
} else {
    echo "<h3>No file uploaded or invalid request.</h3>";
}
?>
<a href="admin_dashboard.php">Back to Admin Dashboard</a> 