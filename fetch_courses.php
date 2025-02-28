<?php
require 'phpspreadsheet/vendor/autoload.php';  // PhpSpreadsheet autoload file
use PhpOffice\PhpSpreadsheet\IOFactory;

// Function to match the whole faculty number with Br_Code in the Excel file
function getBranchFromFacultyNumber($faculty_number) {
    $filePath = 'C:/xampp/htdocs/cwh/Course_File.xlsx';  // Update this path as needed

    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();

    // Loop through the Excel rows to find matching Br_Code in the faculty number
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE); 

        $courseData = [];
        foreach ($cellIterator as $cell) {
            $courseData[] = $cell->getValue();
        }

        // Assuming Br_Code is in the 3rd column (index 2)
        if (isset($courseData[2])) {
            $branch_code = $courseData[2];  // Adjust if needed

            // If the faculty number contains the branch code (Br_Code), return the branch code
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

    // Loop through the Excel rows and fetch relevant courses for the selected branch and semester
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE); 

        $courseData = [];
        foreach ($cellIterator as $cell) {
            $courseData[] = $cell->getValue();
        }

        // Assuming column structure: [Course Number, Title, Branch Code, Semester, Credits]
        if (isset($courseData[2]) && isset($courseData[3]) &&
            $courseData[2] == $branch_code && $courseData[3] == $semester) {  
            $courses[] = [
                'CrsN' => $courseData[0],  // Course Number
                'Title' => $courseData[1],  // Course Title
                'Credits' => $courseData[4]  // Credits
            ];
        }
    }

    return $courses;
}

// Fetch courses after form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_number = htmlspecialchars(strtoupper($_POST['faculty_number']));
    $semester = htmlspecialchars($_POST['semester']);
    error_log("Faculty Number: " . $faculty_number);
    error_log("Semester: " . $semester);

    // Extract branch code from faculty number by matching Br_Code
    $branch_code = getBranchFromFacultyNumber($faculty_number);

    error_log("Extracted Branch Code: " . ($branch_code ?? "None"));

    if ($branch_code) {
        $courses = getCoursesByBranchAndSemester($branch_code, $semester);
    } else {
        $courses = []; 
        $error_message = "No matching branch found for the provided faculty number.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fetched Courses</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Available Courses</h1>
        
        <?php if (isset($error_message)): ?>
            <p><?php echo $error_message; ?></p>
        <?php else: ?>
            <form action="register.php" method="POST">
                <div class="form-control">
                    <label>Select Courses *</label>
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): ?>
                            <div>
                                <input type="checkbox" name="course[]" 
                                       value="<?php echo $course['CrsN']; ?>" 
                                       data-credits="<?php echo $course['Credits']; ?>">
                                <?php echo $course['CrsN'] . ': ' . $course['Title'] . ' (' . $course['Credits'] . ' Credits)'; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No courses available for this semester.</p>
                    <?php endif; ?>
                </div>
                
                <div class="form-control">
                    <button type="submit">Register Selected Courses</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>