<?php
session_start();

// Check if enrollment number is set in session
if (!isset($_SESSION['enrollment'])) {
    header("Location: validate_enrollment.php");
    exit;
}

$server = "localhost";
$username = "root";
$password = "";
$database = "students";

try {
    $pdo = new PDO("mysql:host=$server;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    require_once('tcpdf/tcpdf.php');

    $enrollment = $_SESSION['enrollment'];

    // Fetch student information
    $query_student = "SELECT en, fn, sn, name, email, photo, hall FROM student WHERE en = :enrollment";
    $stmt_student = $pdo->prepare($query_student);
    $stmt_student->execute([':enrollment' => $enrollment]);
    $student = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception('Student not found.');
    }

    // Fetch course titles and marks data from registration table
    $query_courses = "SELECT c1, m1, c2, m2, c3, m3, c4, m4, c5, m5, c6, m6, c7, m7, c8, m8 FROM registration WHERE en = :enrollment";
    $stmt_courses = $pdo->prepare($query_courses);
    $stmt_courses->execute([':enrollment' => $enrollment]);
    $registration_data = $stmt_courses->fetch(PDO::FETCH_ASSOC);

    // Initialize PDF
    $pdf = new TCPDF();
    $pdf->SetMargins(20, 10, 20);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);

    // Title Section
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'ALIGARH MUSLIM UNIVERSITY, ALIGARH', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, 'Session : 2024-25', 0, 1, 'C');
    $pdf->Cell(0, 8, 'Registration Card', 0, 1, 'C');

    // Student Photo
    if (!empty($student['photo'])) {
        $pdf->Image('@' . $student['photo'], 150, 40, 30, 30, '', '', '', false, 300, '', false, false, 0, false, false, false);
    } else {
        $pdf->Cell(0, 8, 'Photo not available', 0, 1, 'C');
    }

    // Student Details Section
    $pdf->SetFont('helvetica', '', 11);
    $details = [
        'Enrollment Number' => $student['en'],
        'Name' => $student['name'],
        'Email' => $student['email'],
        'Faculty Number' => $student['fn'],
        'Serial Number' => $student['sn'],
        'Hall' => $student['hall']
    ];

    foreach ($details as $label => $value) {
        $pdf->Cell(50, 8, $label . ':', 0, 0);
        $pdf->Cell(50, 8, $value, 0, 1);
    }

    $pdf->Ln(5);

    // Course Table Header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(12, 8, 'S.No', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Categ.', 1, 0, 'C');
    $pdf->Cell(35, 8, 'Course No.', 1, 0, 'C');
    $pdf->Cell(75, 8, 'Course Title', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Mode', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Credits', 1, 1, 'C');

    // Course Table Content
    $pdf->SetFont('helvetica', '', 10);
    $totalCredits = 0;
    $sno = 1;
    $cellHeight = 8;

    for ($i = 1; $i <= 8; $i++) {
        $course_title = $registration_data['c' . $i];
        if (!empty($course_title)) {
            $query_course_details = "SELECT code, title, cat, type, credit FROM course WHERE title = :title";
            $stmt_course_details = $pdo->prepare($query_course_details);
            $stmt_course_details->execute([':title' => $course_title]);
            $course_details = $stmt_course_details->fetch(PDO::FETCH_ASSOC);

            if ($course_details) {
                $pdf->Cell(12, $cellHeight, $sno, 1, 0, 'C');
                $pdf->Cell(20, $cellHeight, $course_details['cat'], 1, 0, 'C');
                $pdf->Cell(35, $cellHeight, $course_details['code'], 1, 0, 'C');
                $pdf->Cell(75, $cellHeight, $course_details['title'], 1, 0, 'L');
                $pdf->Cell(20, $cellHeight, $course_details['type'], 1, 0, 'C');
                $pdf->Cell(20, $cellHeight, $course_details['credit'], 1, 1, 'C');
                $totalCredits += $course_details['credit'];
            } else {
                $pdf->Cell(12, $cellHeight, $sno, 1, 0, 'C');
                $pdf->Cell(20, $cellHeight, 'N/A', 1, 0, 'C');
                $pdf->Cell(35, $cellHeight, 'N/A', 1, 0, 'C');
                $pdf->Cell(75, $cellHeight, $course_title, 1, 0, 'L');
                $pdf->Cell(20, $cellHeight, 'N/A', 1, 0, 'C');
                $pdf->Cell(20, $cellHeight, '', 1, 1, 'C');
            }
            $sno++;
        }
    }

    // Total Credits
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, 'TOTAL CREDITS: ' . $totalCredits, 0, 1, 'R');

    // Important Rules Section
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'IMPORTANT RULES:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $rules = [
        "For Registration Mode 'a', you must attend classes and obtain a minimum of 75% attendance to appear in the End-semester Examination. Failure to meet the attendance requirement results in an 'F' grade.",
        "For Registration Mode 'b', class attendance is not required, but it is expected to complete the coursework.",
        "For both registration modes, new sessional marks must be obtained through coursework and Mid-Semester Examination. Previous sessional marks are not considered.",
        "If attendance is met but you do not appear in the End-Semester Examination, an 'I' grade will be awarded.",
        "For Registration Mode 'c', you must appear only in the End-semester examination. Previous sessional marks will be used for grading."
    ];

    foreach ($rules as $index => $rule) {
        $pdf->MultiCell(0, 6, ($index + 1) . ". " . $rule, 0, 'L', false, 1);
    }

    $pdf->Output('registration_card.pdf', 'I');
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}
?>