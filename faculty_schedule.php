<?php
if (isset($_POST['submit'])) {
    $facultyName = $_POST['faculty_name'];
    $file = $_FILES['routine_file'];

    // File details
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Upload folder
    $uploadDir = 'uploads/';

    // Check for upload errors
    if ($fileError === 0) {
        // Check file type (PDF only)
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt === 'pdf') {
            // Unique file name
            $newFileName = uniqid('routine_', true) . '.' . $fileExt;
            $fileDestination = $uploadDir . $newFileName;

            // Create folder if not exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Move file
            if (move_uploaded_file($fileTmp, $fileDestination)) {
                echo "Routine uploaded successfully!<br>";
                echo "Faculty: " . htmlspecialchars($facultyName) . "<br>";
                echo "File saved as: " . $newFileName;

                // Optional: Save info in database
                /*
                $conn = mysqli_connect("localhost", "root", "", "your_database");
                $query = "INSERT INTO routines (faculty_name, file_path) VALUES ('$facultyName', '$fileDestination')";
                mysqli_query($conn, $query);
                */
            } else {
                echo "Error uploading the file.";
            }
        } else {
            echo "Only PDF files are allowed.";
        }
    } else {
        echo "File upload error. Code: " . $fileError;
    }
}
?>
