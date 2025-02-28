<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';
include('partials/_dbconnect.php');

function send_password_reset($get_name,$get_email,$token)
{
$mail = new PHPMailer(true);
$mail->isSMTP ();                                       
    $mail->SMTPAuth   = true; 

    $mail->Host       = 'smtp.gmail.com'; 
    $mail->Username   = 'sharmavikas019908@gmail.com';
    $mail->Password   = 'qwis tqcl sapb ubzg
';

    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;  

    //Recipients
    $mail->setFrom("sharmavikas019908@gmail.com", $get_name);
    $mail->addAddress($get_email);

        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = "Reset Password Notification";

        $email_template = "
        <h2>Hello</h2>
        <h3>You are recieving this email beacause we recieved a password reset request for your account.</h3>
        <br/><br/>
        <a href= 'https://localhost/iSecure/register-login-with-verification/password-change.php?token=$token&$get_email'> Click Me </a>
        ";
        
        $mail->Body    = $email_template;
    
        $mail->send();
}

if(isset($_POST['password_reset_link']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $token = md5(rand());

    $check_email = "SELECT email FROM users WHERE email= '$email' LIMIT 1";
    $check_email_run = mysqli_query($conn, $check_email);
}

if (mysqli_num_rows($check_email_run) > 0) {
    $row = mysqli_fetch_array($check_email_run);
    
    if (isset($row['name'])) {
        $get_name = $row['name'];
        $get_email = $row['email'];

        $update_token = "UPDATE users SET verify_token= '$token' WHERE email='$get_email' LIMIT 1";
        $update_token_run = mysqli_query($conn, $update_token);

        if ($update_token_run) {
            send_password_reset($get_name, $get_email, $token);
            $_SESSION['status'] = "We e-mailed you a password reset link";
            header("location: forgot.php");
            exit(0);
        } else {
            $_SESSION['status'] = "Something went wrong. #1";
            header("location: forgot.php");
            exit(0);
        }
    } else {
        $_SESSION['status'] = "User name not found.";
        header("location: forgot.php");
        exit(0);
    }
}


?>