<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
    $server = "localhost";
    $username = "root";
    $password = "";
    $database = "students";

    try {
        $pdo = new PDO("mysql:host=$server;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $enrollment = $_POST['enrollment'];

        // check if the enrollment number exists
        $query = "SELECT * FROM student WHERE en = :enrollment";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':enrollment' => $enrollment]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {

            $_SESSION['enrollment'] = $enrollment;
            header("Location: generate_pdf.php");
            exit();
        } else {
            $error = "Enrollment number not found. Please try again.";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Enter Enrollment Number</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
    <h2>Enter Enrollment Number</h2>
    <form method="post">
        <div class="form-group">
            <label for="enrollment">Enrollment Number:</label>
            <input type="text" class="form-control" id="enrollment" name="enrollment" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    <?php if (isset($error)) { echo "<p class='text-danger'>$error</p>"; } ?>
</div>
</body>
</html>