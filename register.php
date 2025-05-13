<?php
session_start();

$conn = new mysqli("localhost", "root", "", "library_db");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  // Basic validation
  if ($password !== $confirm_password) {
    $error = "Passwords do not match.";
  } else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if user already exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
      $error = "An account with this email already exists.";
    } else {
      $sql = "INSERT INTO users (name, email, password, role)
              VALUES ('$name', '$email', '$hashedPassword', 'member')";

      if ($conn->query($sql) === TRUE) {
        $success = "Registration successful! You can now <a href='login.php'>log in</a>.";
      } else {
        $error = "Something went wrong. Please try again.";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Online Library</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .register-container {
      margin-top: 80px;
      max-width: 450px;
    }
  </style>
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center">
  <div class="register-container bg-white p-4 shadow rounded">
    <h3 class="text-center mb-4">Create an Account</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>

    <div class="mt-3 text-center">
      <a href="login.php">Already have an account? Login</a>
    </div>
  </div>
</div>

</body>
</html>
