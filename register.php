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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 480px;
            padding: 40px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }
        .back-home:hover {
            color: var(--primary-color);
        }
        .form-control {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            height: 52px;
            padding-left: 20px;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }
        .input-group-text {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-left: none;
            border-radius: 0 12px 12px 0;
            cursor: pointer;
            color: #64748b;
        }
        .password-field {
            border-right: none;
            border-radius: 12px 0 0 12px;
        }
        .btn-register {
            height: 52px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 12px;
            background: var(--primary-color);
            border: none;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(67, 97, 238, 0.3);
            background: var(--secondary-color);
        }
        .brand-logo {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            color: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>

<div class="register-card">
    <a href="index.php" class="back-home">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <div class="text-center mt-3">
        <div class="brand-logo">
            <i class="bi bi-person-plus-fill"></i>
        </div>
        <h2 class="fw-bold text-dark mb-1">Create Account</h2>
        <p class="text-muted mb-4">Join our library community today</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 mb-4 d-flex align-items-center">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="John Doe" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required autocomplete="email">
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold small text-secondary">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control password-field" placeholder="Create a password" required>
                <span class="input-group-text" onclick="togglePassword()">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </span>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-register mb-4">Create Account</button>
    </form>
    
    <div class="text-center">
        <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login</a></p>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    }
</script>

</body>
</html>

