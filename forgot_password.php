<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

require 'vendor/autoload.php';
$config = require 'config.php';
require 'phpspreadsheet/vendor/autoload.php';

// database connection
include 'partials/_dbconnect.php';
include("partials/_nav.php");
date_default_timezone_set('Asia/Kolkata');

$showError = false;
$showSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $facultyNumber = $_POST['fn']; 

    $filePath = 'C:/xampp/htdocs/cwh/St_file.xlsx'; 

    try {
        // load the Excel file
        
       
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $isEmailFound = false;

        // iterate over rows to find matching email and faculty number
        foreach ($worksheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            $facultyCell = $worksheet->getCell('A' . $rowIndex)->getValue(); 
            $emailCell = $worksheet->getCell('H' . $rowIndex)->getValue(); 

            if ($facultyCell == $facultyNumber && $emailCell == $email) {
                $isEmailFound = true;
                break;
            }
        }

        if ($isEmailFound) {
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // Update the SQL query to use facultyNumber (or fn) instead of email
            $updateToken = "UPDATE users SET token='$token', token_expiry='$expiry' WHERE fn='$facultyNumber'";
            mysqli_query($conn, $updateToken);


            $mail = new PHPMailer(true);
            try {
                // server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'sharmavikas019908@gmail.com'; 
                $mail->Password = $config['smtp_password']; 
                $mail->SMTPSecure = "tls";
                $mail->Port = 587;

                require 'vendor/autoload.php';

                $mail->setFrom("your_email@gmail.com", 'Admin'); // Replace with your email
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $resetLink = "http://localhost/cwh/reset_password.php?token=$token";
                $mail->Body = "Click the link below to reset your password:<br><br>
                              <a href='$resetLink'>$resetLink</a><br><br>
                              The link will expire in 1 hour.";

                $mail->send();
                $showSuccess = "A password reset link has been sent to your email.";
            } catch (Exception $e) {
                $showError = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $showError = "Email address or faculty number not found in the spreadsheet.";
        }
    } catch (Exception $e) {
        $showError = "Error loading file: " . $e->getMessage();
    }
} 
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" crossorigin="anonymous">

    <title>Forgot Password</title>
</head>
<body>
    <div class="container my-4">
        <h1 class="text-center">Forgot Password</h1>
        <?php
        if ($showSuccess) {
            echo '<div class="alert alert-success" role="alert">' . $showSuccess . '</div>';
        }
        if ($showError) {
            echo '<div class="alert alert-danger" role="alert">' . $showError . '</div>';
        }
        ?>
        <form action="forgot_password.php" method="post">
            <div class="form-group">
                <label for="fn">Faculty Number</label>
                <input type="text" class="form-control" id="fn" name="fn" required>
            </div>
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>
</body>
</html>