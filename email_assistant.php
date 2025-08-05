<?php
session_start();

if (!isset($_SESSION["useremail"]) || empty($_SESSION["useremail"])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

// Fetch user info for autofill
$db = (new Database())->getConnection();
$user = $db->prepare("SELECT * FROM users WHERE email = ?");
$user->execute([$_SESSION["useremail"]]);
$user = $user->fetch();

// Fetch student university_id
$university_id = '';
if ($user && $user['role'] === 'student') {
    $stmt = $db->prepare("SELECT university_id FROM student_id_table WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if ($row) $university_id = $row['university_id'];
}
// Fetch enrolled courses/sections with teacher info
$courses = [];
if ($user && $user['role'] === 'student') {
    $stmt = $db->prepare("SELECT uc.course_code, uc.course_title, se.section, uc.faculty_name AS teacher FROM student_enrollments se JOIN upcoming_courses uc ON se.course_id = uc.id WHERE se.student_id = ? GROUP BY uc.course_code, se.section");
    $stmt->execute([$user['id']]);
    $courses = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Assistant - CampusLynk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/email_assistant.css">
</head>

<body>
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main id="main-content" class="main-content fade-transition fade-out" style="">
    <section class="welcome-section">
        <h1>Email Assistant</h1>
        <p class="text-muted">Generate professional emails for your academic needs with AI</p>
    </section>

    <div class="card">
        <form id="emailForm" class="space-y-4" method="post">
            <div class="form-group">
                <label class="form-label">What should the email be about?</label>
                <div class="input-with-icon">
                    <i class='bx bx-message-square-detail'></i>
                    <textarea id="email_prompt" name="email_prompt" class="form-input" rows="4" placeholder="e.g., Write an email to my professor asking for an extension for an assignment." required></textarea>
                </div>
            </div>

            <?php if ($user && $user['role'] === 'student' && !empty($courses)): ?>
            <div class="form-group">
                <label class="form-label">Recipient (Course, Section & Faculty)</label>
                <div class="input-with-icon">
                    <i class='bx bx-user'></i>
                    <select name="course_code" id="course_code_select" class="form-input" onchange="updateTeacherField()">
                        <option value="">Select course, section & faculty (Optional)</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['course_code']); ?>|<?php echo htmlspecialchars($c['section']); ?>|<?php echo htmlspecialchars($c['teacher']); ?>">
                                <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_title'] . ' (Section ' . $c['section'] . ') - ' . $c['teacher']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group" id="teacher-group" style="display:none;">
                <label class="form-label">Faculty</label>
                <div class="input-with-icon">
                    <i class='bx bx-user'></i>
                    <input type="text" id="teacher_name" class="form-input" readonly>
                </div>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Generate Email</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_prompt'])): ?>
            <?php
            $email_prompt = trim($_POST['email_prompt']);
            
            $selected_course = '';
            $selected_section = '';
            $selected_teacher = '';
            if (isset($_POST['course_code']) && !empty($_POST['course_code'])) {
                list($selected_course, $selected_section, $selected_teacher) = explode('|', $_POST['course_code']);
            }

            // Load the API key from the secrets file.
            $secrets = require __DIR__ . '/config/secrets.php';
            $apiKey = $secrets['GROQ_API_KEY'];

            // Construct a detailed prompt for the AI.
            $prompt_for_ai = "Generate a professional email subject and body based on the following details\n\n" .
                           "Student Name: " . ($user['name'] ?? 'N/A') . "\n" .
                           "Student ID: " . ($university_id ?? 'N/A') . "\n" .
                           "Course: " . ($selected_course ?: 'N/A') . "\n" .
                           "Section: " . ($selected_section ?: 'N/A') . "\n" .
                           "Recipient/Teacher: " . ($selected_teacher ?: 'N/A') . "\n\n" .
                           "User's instruction: " . $email_prompt . "\n\n" .
                           "Generate a JSON object with 'subject' and 'body' keys. Do not include any other text or formatting.";

            $data = [
                'model' => 'llama3-8b-8192',
                'messages' => [['role' => 'user', 'content' => $prompt_for_ai]],
                'response_format' => ['type' => 'json_object']
            ];

            $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $subject = 'Error: Could not generate email';
            $body = 'There was an issue communicating with the AI service. Please check the API key and network connection.';

            if ($response) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    $email_content_raw = $result['choices'][0]['message']['content'];
                    $email_content = json_decode($email_content_raw, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($email_content['subject']) && isset($email_content['body'])) {
                        $subject = htmlspecialchars($email_content['subject']);
                        $body = nl2br(htmlspecialchars($email_content['body']));
                    } else {
                        $body = 'The AI returned an invalid format. Raw response: <pre>' . htmlspecialchars($email_content_raw) . '</pre>';
                    }
                } elseif (isset($result['error'])) {
                    $body = 'API Error: ' . htmlspecialchars($result['error']['message']);
                }
            }
            ?>
            <div id="emailPreview" class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Generated Email</h3>
                <div class="card bg-muted p-4">
                    <strong>Subject:</strong> <?php echo $subject; ?><br><br>
                    <div><?php echo $body; ?></div>
                </div>
                <button onclick="copyEmailContent()" class="btn btn-secondary mt-4">Copy to Clipboard</button>
            </div>
            <script>
            function copyEmailContent() {
                const el = document.createElement('textarea');
                // Create a temporary div to decode HTML entities for the body
                var tempDiv = document.createElement("div");
                tempDiv.innerHTML = `<?php echo addslashes($body); ?>`;
                var decodedBody = tempDiv.textContent || tempDiv.innerText || "";
                // Replace <br> and <br /> with newlines for plain text copy
                var plainTextBody = decodedBody.replace(/<br\s*\/?>/gi, '\n');

                el.value = `Subject: <?php echo addslashes($subject); ?>\n\n${plainTextBody}`;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                alert('Email copied to clipboard!');
            }
            function updateTeacherField() {
                var select = document.getElementById('course_code_select');
                var teacherGroup = document.getElementById('teacher-group');
                var teacherInput = document.getElementById('teacher_name');
                if (select.value) {
                    var parts = select.value.split('|');
                    if (parts.length > 2) {
                        teacherInput.value = parts[2];
                        teacherGroup.style.display = '';
                    } else {
                        teacherInput.value = '';
                        teacherGroup.style.display = 'none';
                    }
                } else {
                    teacherInput.value = '';
                    teacherGroup.style.display = 'none';
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                updateTeacherField();
            });
            </script>
            <?php
        endif; ?>
    </div>
  </main>
</div>

<script src="email_assistant.js"></script>
</body>

</html>