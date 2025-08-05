<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Adding Sample Future Exam Data ===\n";

// Sample future exam data
$sampleExams = [
    [
        'department' => 'BSCSE',
        'course_code' => 'CSE 1115',
        'course_title' => 'Object Oriented Programming',
        'section' => 'A',
        'teacher' => 'MTR',
        'exam_date' => '2025-02-15',
        'exam_time' => '09:00 AM - 11:00 AM',
        'room' => '401 (011201001-011201050)'
    ],
    [
        'department' => 'BSCSE',
        'course_code' => 'CSE 1115',
        'course_title' => 'Object Oriented Programming',
        'section' => 'B',
        'teacher' => 'MdRIm',
        'exam_date' => '2025-02-15',
        'exam_time' => '09:00 AM - 11:00 AM',
        'room' => '402 (011201051-011201100)'
    ],
    [
        'department' => 'BSCSE',
        'course_code' => 'BDS 1201',
        'course_title' => 'History of the Emergence of Bangladesh',
        'section' => 'AA',
        'teacher' => 'FaAM',
        'exam_date' => '2025-02-16',
        'exam_time' => '11:30 AM - 01:30 PM',
        'room' => '403 (011201101-011201150)'
    ],
    [
        'department' => 'BSCSE',
        'course_code' => 'BDS 1201',
        'course_title' => 'History of the Emergence of Bangladesh',
        'section' => 'AB',
        'teacher' => 'MD',
        'exam_date' => '2025-02-16',
        'exam_time' => '11:30 AM - 01:30 PM',
        'room' => '404 (011201151-011201200)'
    ],
    [
        'department' => 'BSCSE',
        'course_code' => 'CSE 1325',
        'course_title' => 'Digital Logic Design',
        'section' => 'A',
        'teacher' => 'RtAm',
        'exam_date' => '2025-02-17',
        'exam_time' => '02:00 PM - 04:00 PM',
        'room' => '405 (011201201-011201250)'
    ]
];

try {
    // Check if these exams already exist
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM exam_schedules WHERE exam_date >= '2025-02-01'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "Future exams already exist. Skipping insertion.\n";
    } else {
        // Insert sample exams
        $insertStmt = $db->prepare("
            INSERT INTO exam_schedules 
            (department, course_code, course_title, section, teacher, exam_date, exam_time, room, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $inserted = 0;
        foreach ($sampleExams as $exam) {
            $insertStmt->execute([
                $exam['department'],
                $exam['course_code'],
                $exam['course_title'],
                $exam['section'],
                $exam['teacher'],
                $exam['exam_date'],
                $exam['exam_time'],
                $exam['room']
            ]);
            $inserted++;
        }
        
        echo "Successfully inserted $inserted sample future exams.\n";
    }
    
    // Verify the insertion
    $stmt = $db->query("SELECT COUNT(*) as count FROM exam_schedules WHERE exam_date >= CURDATE()");
    $result = $stmt->fetch();
    echo "Total future exams in database: " . $result['count'] . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "=== Sample Data Addition Complete ===\n";
?> 