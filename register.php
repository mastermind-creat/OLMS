<?php
session_start();
include 'includes/db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = 'member'; // Default role

    // Check if email already exists
    $check_email = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        $message = "Email already exists.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')";

        if ($conn->query($sql) === TRUE) {
            header("Location: login.php?registered=true");
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .form-control {
            background-color: #f0f4ff;
            border: none;
            height: 50px;
            padding-left: 20px;
        }
        .form-control:focus {
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
    </style>
</head>
<body>

<div class="register-card text-center">
    <h2 class="fw-bold text-primary mb-1">Create Account</h2>
    <p class="text-muted mb-4">Join our library community</p>

    <?php if($message): ?>
        <div class="alert alert-danger py-2"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3 text-start">
            <label class="form-label fw-bold small text-muted">Full Name</label>
            <input type="text" name="name" class="form-control rounded-3" placeholder="John Doe" required>
        </div>
        <div class="mb-3 text-start">
            <label class="form-label fw-bold small text-muted">Email Address</label>
            <input type="email" name="email" class="form-control rounded-3" placeholder="name@example.com" required>
        </div>
        <div class="mb-4 text-start">
            <label class="form-label fw-bold small text-muted">Password</label>
            <input type="password" name="password" class="form-control rounded-3" placeholder="Create a password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg shadow mb-3" style="border-radius: 10px;">Sign Up</button>
    </form>
    
    <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login</a></p>
</div>

</body>
</html>
