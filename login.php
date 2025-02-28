<?php
require 'phpspreadsheet/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$login = false;
$showError = false;

$servername = "localhost";
$username = "root";
$password = "";
$database = "users";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_number = $_POST["faculty_number"];
    $password = $_POST["password"];

    // Check if the user exists in the database
    $sql = "SELECT * FROM users WHERE fn = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $faculty_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists in the database; validate password
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
    
            session_start();
            $_SESSION['loggedin'] = true;
            $_SESSION['faculty_number'] = $faculty_number;
            $_SESSION['username'] = $user['fn'];

            header("Location: welcome.php");
            exit();
        } else {
            $showError = "Invalid Credentials";
        }
    } else {
        // First-time login: Validate credentials from Excel
        $filePath = 'C:/xampp/htdocs/cwh/St_file.xlsx'; // Path to Excel file
        if (file_exists($filePath)) {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $isValidUser = false;
            $username = ''; // Store the username from column 6

            // Iterate through rows to find matching faculty number and password
            foreach ($worksheet->getRowIterator(2) as $row) { // Start from row 2 to skip headers
                $facultyCell = $worksheet->getCell("A" . $row->getRowIndex())->getValue();
                $enrollmentCell = $worksheet->getCell("B" . $row->getRowIndex())->getValue();
                $usernameCell = $worksheet->getCell("F" . $row->getRowIndex())->getValue(); // Username from column 6

                if ($faculty_number == $facultyCell && $password == $enrollmentCell) {
                    $isValidUser = true;
                    $username = $usernameCell;
                    break;
                }
            }

            if ($isValidUser) {
                // Store user details in the database
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $currentDate = date('Y-m-d H:i:s');
                $token = bin2hex(random_bytes(16)); // Generate random token
                $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

                $insertSql = "INSERT INTO users (fn, password, dt, token, token_expiry) VALUES (?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("sssss", $faculty_number, $hashedPassword, $currentDate, $token, $tokenExpiry);
                $insertStmt->execute();

                // Successful first-time login
                session_start();
                $_SESSION['loggedin'] = true;
                $_SESSION['faculty_number'] = $faculty_number;
                $_SESSION['username'] = $username;

                header("Location: welcome.php");
                exit();
            } else {
                $showError = "Invalid Credentials for first-time login.";
            }
        } else {
            $showError = "Error: Excel file not found at $filePath";
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" crossorigin="anonymous">
    <title>Login</title>
</head>
<body>
    <?php require 'partials/_nav.php' ?>

    <?php
    if ($login) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> You are logged in
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }
    if ($showError) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> ' . $showError . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }
    ?>

    <div class="container my-4">
        <h1 class="text-center">Login to our website</h1>
        <form action="" method="post">
            <div class="form-group">
                <label for="faculty_number">Faculty Number</label>
                <input type="text" class="form-control" id="faculty_number" name="faculty_number" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
            <a href="forgot_password.php" class="btn btn-secondary ml-2">Forgot Password?</a>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>
</body>
</html>