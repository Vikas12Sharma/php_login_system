<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection and navigation
include 'partials/_dbconnect.php';
include 'partials/_nav.php';
date_default_timezone_set('Asia/Kolkata');

// Initialize error/success messages
$showError = false;
$showSuccess = false;

// Check if a reset link request is being made
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    // Retrieve the faculty number and email
    $facultyNumber = $_POST['fn'];
    $email = $_POST['email'];

    // Validate if the user exists in the database
    $sql = "SELECT * FROM users WHERE fn='$facultyNumber' AND email='$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // User exists; generate a token and expiry
        $token = bin2hex(random_bytes(32));
        $token_expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Update the token and expiry in the database
        $updateToken = "UPDATE users SET token='$token', token_expiry='$token_expiry' WHERE fn='$facultyNumber'";
        if (mysqli_query($conn, $updateToken)) {
            // Send reset link (mock email process)
            $reset_link = "http://localhost/cwh/reset_password.php?token=" . $token;
            
            // Display the reset link (email sending functionality can be added here)
            $showSuccess = "A reset link has been sent to your email: <a href='$reset_link'>$reset_link</a>";
        } else {
            $showError = "Error generating reset link: " . mysqli_error($conn);
        }
    } else {
        $showError = "No account found with the provided Faculty Number and Email.";
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    // Validate token
    $sql = "SELECT * FROM users WHERE token='$token' AND token_expiry > NOW() LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // Token is valid, update the password
        $row = mysqli_fetch_assoc($result);
        $facultyNumber = $row['fn'];

        $updatePassword = "UPDATE users SET password='$new_password', token=NULL, token_expiry=NULL WHERE fn='$facultyNumber'";
        if (mysqli_query($conn, $updatePassword)) {
            // Redirect after successful password reset
            header("Location: login.php");
            exit();
        } else {
            $showError = "Error updating password: " . mysqli_error($conn);
        }
    } else {
        $showError = "Invalid or expired reset token.";
    }
}

// Extract token from URL if available
$token = isset($_GET['token']) ? $_GET['token'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-4">
    <h2 class="text-center">Reset Password</h2>

    <?php if ($showError): ?>
        <div class="alert alert-danger"><?php echo $showError; ?></div>
    <?php endif; ?>

    <?php if ($showSuccess): ?>
        <div class="alert alert-success"><?php echo $showSuccess; ?></div>
    <?php endif; ?>

    <?php if ($token): ?>
        <!-- Reset Password Form -->
        <form method="POST" action="reset_password.php">
            <input type="hidden" name="reset_password" value="1">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>

            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
    <?php else: ?>
        <!-- Request Reset Link Form -->
        <form method="POST" action="reset_password.php">
            <input type="hidden" name="request_reset" value="1">

            <div class="mb-3">
                <label for="fn" class="form-label">Faculty Number</label>
                <input type="text" class="form-control" id="fn" name="fn" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <button type="submit" class="btn btn-primary">Request Password Reset</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>