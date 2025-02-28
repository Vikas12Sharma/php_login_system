<?php
session_start();

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true){
    header("location: login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <title>Welcome - <?php echo $_SESSION['username']?></title>
  </head>
  <body>
    <?php require 'partials/_nav.php' ?>
    
    <div class="container my-3">
      <div class="alert alert-success" role="alert">
        <h4 class="alert-heading">Welcome - <?php echo $_SESSION['username']?></h4>
        <p>Welcome. You are logged in as <?php echo $_SESSION['username']?>.</p>
        <hr>
        <p class="mb-0">Whenever you need to, be sure to logout <a href="/cwh/logout.php">using this link.</a></p>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Course Registration</h5>
          <p class="card-text">Register for your courses by clicking below.</p>
          <a href="register.php" class="btn btn-primary">Register Now</a>
        </div>
      </div>

      <!-- Generate PDF Button -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Generate Registration PDF</h5>
          <p class="card-text">Click below to generate a PDF of your registration details.</p>
          <a href="generate_pdf.php" class="btn btn-danger">Generate PDF</a>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
  </body>
</html>