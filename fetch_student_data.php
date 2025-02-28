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
    $query_student = "SELECT en, fn, sn, name, email, photo FROM student WHERE en = :enrollment";
    $stmt_student = $pdo->prepare($query_student);
    $stmt_student->execute([':enrollment' => $enrollment]);
    $student = $stmt_student->fetch(PDO::FETCH_ASSOC);

    // Fetch course and marks data from registration table
    $query_courses = "SELECT c1, m1, c2, m2, c3, m3, c4, m4, c5, m5, c6, m6, c7, m7, c8, m8 FROM registration WHERE en = :enrollment";
    $stmt_courses = $pdo->prepare($query_courses);
    $stmt_courses->execute([':enrollment' => $enrollment]);
    $registration_data = $stmt_courses->fetch(PDO::FETCH_ASSOC);

    $pdf = new TCPDF();
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);

    $pdf->Cell(0, 10, 'Student Registration Card', 0, 1, 'C');
    $pdf->Ln(15);

    if (!empty($student['photo'])) {
        $photo = $student['photo'];
        $pdf->Image('@' . $photo, 150, 40, 40, 40, '', '', '', false, 300, '', false, false, 0, false, false, false);
    } else {
        $pdf->Cell(0, 10, 'Photo not available', 0, 1, 'C');
    }

    // Display student details
    $pdf->Cell(50, 10, 'Enrollment Number:', 0, 0);
    $pdf->Cell(50, 10, $student['en'], 0, 1);

    $pdf->Cell(50, 10, 'Name:', 0, 0);
    $pdf->Cell(50, 10, $student['name'], 0, 1);

    $pdf->Cell(50, 10, 'Email:', 0, 0);
    $pdf->Cell(50, 10, $student['email'], 0, 1);

    $pdf->Cell(50, 10, 'Faculty Number:', 0, 0);
    $pdf->Cell(50, 10, $student['fn'], 0, 1);

    $pdf->Cell(50, 10, 'Serial Number:', 0, 0);
    $pdf->Cell(50, 10, $student['sn'], 0, 1);
    $pdf->Ln(10);

    $qrText = "Enrollment: " . $student['en'] . "\nName: " . $student['name'];
    $style = array(
        'border' => 0,
        'padding' => 1,
        'fgcolor' => array(0,0,0),
        'bgcolor' => false
    );
    $pdf->write2DBarcode($qrText, 'QRCODE,H', 150, 90, 40, 40, $style, 'N');
    $pdf->Text(150, 135, 'Scan for Student Info');

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(10, 8, 'S.No', 1, 0, 'C', false, '', 1);
    $pdf->Cell(18, 8, 'Cat', 1, 0, 'C', false, '', 1);
    $pdf->Cell(28, 8, 'Code', 1, 0, 'C', false, '', 1); 
    $pdf->Cell(55, 8, 'Title', 1, 0, 'C', false, '', 1);
    $pdf->Cell(15, 8, 'Type', 1, 0, 'C', false, '', 1);
    $pdf->Cell(15, 8, 'Credit', 1, 0, 'C', false, '', 1);
    $pdf->Cell(20, 8, 'Marks', 1, 1, 'C', false, '', 1);

    $pdf->SetFont('helvetica', '', 9); 
    $sno = 1;
    $cellHeight = 8; 
    $totalCredits = 0;

    // Loop through the courses and marks columns in registration data
    for ($i = 1; $i <= 8; $i++) {
        $course_title = $registration_data['c' . $i];
        $marks = $registration_data['m' . $i];

        // Only display non-empty courses
        if (!empty($course_title)) {
            // Fetch course details based on course title from the course table
            $query_course_details = "SELECT code, title, cat, type, credit FROM course WHERE title = :title";
            $stmt_course_details = $pdo->prepare($query_course_details);
            $stmt_course_details->execute([':title' => $course_title]);
            $course_details = $stmt_course_details->fetch(PDO::FETCH_ASSOC);

            if ($course_details) {
                $pdf->Cell(10, $cellHeight, $sno, 1, 0, 'C');
                $pdf->Cell(18, $cellHeight, $course_details['cat'], 1, 0, 'C');
                $pdf->Cell(28, $cellHeight, $course_details['code'], 1, 0, 'C');
                $pdf->Cell(55, $cellHeight, $course_details['title'], 1, 0, 'L');
                $pdf->Cell(15, $cellHeight, $course_details['type'], 1, 0, 'C');
                $pdf->Cell(15, $cellHeight, $course_details['credit'], 1, 0, 'C');
                $pdf->Cell(20, $cellHeight, $marks, 1, 1, 'C');

                // Add to total credits
                $totalCredits += (int)$course_details['credit'];
            } else {
                // Output a row with "Not Found" or similar to indicate missing course details
                $pdf->Cell(10, $cellHeight, $sno, 1, 0, 'C');
                $pdf->Cell(18, $cellHeight, 'N/A', 1, 0, 'C');
                $pdf->Cell(28, $cellHeight, 'Not Found', 1, 0, 'L');
                $pdf->Cell(55, $cellHeight, $course_title, 1, 0, 'C');
                $pdf->Cell(15, $cellHeight, 'N/A', 1, 0, 'C');
                $pdf->Cell(15, $cellHeight, '', 1, 0, 'C');
                $pdf->Cell(20, $cellHeight, $marks, 1, 1, 'C');
            }

            // Increment the serial number for each course
            $sno++;
        }
    }

    // Display total credits at the bottom of the table
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(126, 8, 'Total Credits', 1, 0, 'R');
    $pdf->Cell(15, 8, $totalCredits, 1, 0, 'C');
    $pdf->Cell(20, 8, '', 1, 1, 'C'); // Empty cell for alignment

    // Output PDF to browser
    $pdf->Output('registration_card.pdf', 'I');

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>