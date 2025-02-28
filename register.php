<?php
session_start();

require 'phpspreadsheet/vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\IOFactory;

// Function to fetch student details (Name and Enrollment Number) from Excel based on Faculty Number
function getStudentDetailsFromExcel($faculty_number) {
    $filePath = 'C:/xampp/htdocs/cwh/St_file.xlsx';  // Update this path as needed
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getSheetByName('Complete');

    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE);

        $studentData = [];
        foreach ($cellIterator as $cell) {
            $studentData[] = $cell->getValue();
        }

        // Assuming column mapping: F_No (0), En_No (1), Name (5)
        if (isset($studentData[0]) && $studentData[0] === $faculty_number) {
            return [
                'name' => $studentData[5] ?? '',
                'enrollment_number' => $studentData[1] ?? ''
            ];
        }
    }

    return null;
}

// Function to match the whole faculty number with Br_Code in the Excel file
function getBranchFromFacultyNumber($faculty_number) {
    $filePath = 'C:/xampp/htdocs/cwh/Course_File.xlsx';  // Update this path as needed
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();

    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE);

        $courseData = [];
        foreach ($cellIterator as $cell) {
            $courseData[] = $cell->getValue();
        }

        if (isset($courseData[2])) {
            $branch_code = $courseData[2];  // Adjust if needed

            if (strpos($faculty_number, $branch_code) !== false) {
                return $branch_code;
            }
        }
    }

    return null;
}

// Function to fetch courses based on branch and semester from the Excel file
function getCoursesByBranchAndSemester($branch_code, $semester) {
    $filePath = 'C:/xampp/htdocs/cwh/Course_File.xlsx';  // Update this path as needed
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();

    $courses = [];

    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE);

        $courseData = [];
        foreach ($cellIterator as $cell) {
            $courseData[] = $cell->getValue();
        }

        if (isset($courseData[2]) && isset($courseData[3]) &&
            $courseData[2] == $branch_code && $courseData[3] == $semester) {
            $courses[] = [
                'CrsN' => $courseData[0],
                'Title' => $courseData[1],
                'Credits' => $courseData[4]  // Include Credits
            ];
        }
    }

    return $courses;
}

// Initialize variables for course fetching
$courses = [];
$error_message = "";

// Check if the user is logged in
if (!isset($_SESSION['faculty_number'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Autofetch faculty number from session
$faculty_number = $_SESSION['faculty_number'];

// Fetch student details (Name and Enrollment Number) from Excel
$student_details = getStudentDetailsFromExcel($faculty_number);
if ($student_details) {
    $username = $student_details['name'];
    $enrollment_number = $student_details['enrollment_number'];
} else {
    $username = "";
    $enrollment_number = "";
    $error_message = "Student details not found for the provided faculty number.";
}

// Preserve input fields
$email = $_POST['email'] ?? "";
$mobile_number = $_POST['mobile'] ?? "";
$mothers_name = $_POST['mother_name'] ?? "";
$semester = $_POST['semester'] ?? "";

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['semester'])) {
        // Extract branch code from faculty number by matching Br_Code
        $branch_code = getBranchFromFacultyNumber($faculty_number);

        if ($branch_code) {
            // Fetch courses based on branch code and semester
            $courses = getCoursesByBranchAndSemester($branch_code, $semester);
        } else {
            $error_message = "No matching branch found for the provided faculty number.";
        }

        // Store details in the database (Update this section based on your database configuration)
        $conn = new mysqli("localhost", "root", "", "course_registration"); // Replace with your DB credentials

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO students (faculty_number, enrollment_number, email, mobile, mother_name, semester) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE email=?, mobile=?, mother_name=?, semester=?");
        $stmt->bind_param("ssssssssss", $faculty_number, $enrollment_number, $email, $mobile_number, $mothers_name, $semester, $email, $mobile_number, $mothers_name, $semester);

        $stmt->execute();
        $stmt->close();
        $conn->close();
    }

    if (isset($_POST['course'])) {
        header("Location: thankyou.php"); // Redirect to thankyou.php
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: white;
        }
        .container {
            margin: 20px auto;
            width: 50%;
            padding: 20px;
            background-color: #222;
            border-radius: 8px;
        }
        .form-control {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="email"], input[type="tel"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: white;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Course Registration</h1>

        <!-- Student Registration Form -->
        <form action="" method="POST">
            <div class="form-control">
                <label for="username">Name of Student *</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
            </div>
            <div class="form-control">
                <label for="faculty_number">Faculty Number *</label>
                <input type="text" id="faculty_number" name="faculty_number" value="<?php echo htmlspecialchars($faculty_number); ?>" readonly>
            </div>
            <div class="form-control">
                <label for="enrollment_number">Enrollment No *</label>
                <input type="text" id="enrollment_number" name="enrollment_number" value="<?php echo htmlspecialchars($enrollment_number); ?>" readonly>
            </div>
            <div class="form-control">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="form-control">
                <label for="mobile">Mobile Number *</label>
                <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($mobile_number); ?>">
            </div>
            <div class="form-control">
                <label for="mother_name">Mother's Name *</label>
                <input type="text" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($mothers_name); ?>">
            </div>
            <div class="form-control">
                <label for="semester">Semester *</label>
                <select id="semester" name="semester">
                    <option value="" disabled selected>Select Semester</option>
                    <option value="1" <?php echo ($semester == "1") ? "selected" : ""; ?>>1</option>
                    <option value="2" <?php echo ($semester == "2") ? "selected" : ""; ?>>2</option>
                    <option value="3" <?php echo ($semester == "3") ? "selected" : ""; ?>>3</option>
                    <option value="4" <?php echo ($semester == "4") ? "selected" : ""; ?>>4</option>
                    <option value="5" <?php echo ($semester == "5") ? "selected" : ""; ?>>5</option>
                    <option value="6" <?php echo ($semester == "6") ? "selected" : ""; ?>>6</option>
                    <option value="7" <?php echo ($semester == "7") ? "selected" : ""; ?>>7</option>
                    <option value="8" <?php echo ($semester == "8") ? "selected" : ""; ?>>8</option>
                </select>
            </div>
            <button type="submit" name="fetch_courses">Fetch Courses</button>

            <?php if (!empty($courses)): ?>
                <h2>Available Courses</h2>
                <ul>
                    <?php foreach ($courses as $course): ?>
                        <li class="checkbox-container">
                            <input type="checkbox" name="course[]" value="<?php echo htmlspecialchars($course['CrsN']); ?>">
                            <?php echo htmlspecialchars($course['CrsN']) . " - " . htmlspecialchars($course['Title']) . " (" . htmlspecialchars($course['Credits']) . " credits)"; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button type="submit" name="course_submit">Submit Courses</button>
            <?php endif; ?>

        </form>

        <?php if (!empty($error_message)): ?>
            <p style="color: red;">Error: <?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
