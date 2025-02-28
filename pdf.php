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

    // Retrieve student information
    $enrollment = $_SESSION['enrollment'];
    $query_student = "SELECT * FROM student WHERE en = :enrollment";
    $stmt_student = $pdo->prepare($query_student);
    $stmt_student->execute([':enrollment' => $enrollment]);
    $student = $stmt_student->fetch(PDO::FETCH_ASSOC);

    // Retrieve registered courses
    $query_courses = "SELECT course.code, course.title, course.credit 
                      FROM course 
                      JOIN registration ON registration.en = course.code 
                      WHERE registration.en = :enrollment";
    $stmt_courses = $pdo->prepare($query_courses);
    $stmt_courses->execute([':enrollment' => $enrollment]);
    $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    $pdf->Cell(0, 10, 'Student Registration Card', 0, 1, 'C');
    $pdf->Ln(10);

    // Student details
    $pdf->Cell(50, 10, 'Enrollment Number: ', 0, 0);
    $pdf->Cell(50, 10, $student['en'], 0, 1);
    $pdf->Cell(50, 10, 'Name: ', 0, 0);
    $pdf->Cell(50, 10, $student['fn'] . ' ' . $student['sn'], 0, 1);
    $pdf->Cell(50, 10, 'Email: ', 0, 0);
    $pdf->Cell(50, 10, $student['email'], 0, 1);
    $pdf->Ln(10);

    // Registered courses
    $pdf->Cell(0, 10, 'Registered Courses', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    // Table headers
    $pdf->Cell(30, 10, 'Course Code', 1);
    $pdf->Cell(70, 10, 'Course Name', 1);
    $pdf->Cell(30, 10, 'Credits', 1);
    $pdf->Ln();

    // Table data
    foreach ($courses as $course) {
        $pdf->Cell(30, 10, $course['course_code'], 1);
        $pdf->Cell(70, 10, $course['course_name'], 1);
        $pdf->Cell(30, 10, $course['credits'], 1);
        $pdf->Ln();
    }

    // Output PDF to browser
    $pdf->Output('registration_card.pdf', 'I');
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
