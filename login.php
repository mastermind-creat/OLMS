<?php
session_start();
include 'includes/db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if password matches (hashed or plain text)
        $password_valid = false;
        $password_needs_rehash = false;

        if (password_verify($password, $user['password'])) {
            $password_valid = true;
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $password_needs_rehash = true;
            }
        } elseif ($password == $user['password']) {
            // Fallback for legacy plain text passwords
            $password_valid = true;
            $password_needs_rehash = true;
        }

        if ($password_valid) {
            // Store session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'] ?? $user['username'] ?? 'User';
            $_SESSION['username'] = $user['username'] ?? $user['name'];

            // Upgrade password hash if needed (for legacy plain text users)
            if ($password_needs_rehash) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = '$new_hash' WHERE id = " . $user['id'];
                $conn->query($update_sql);
            }

            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] == 'librarian') {
                header("Location: librarian_dashboard.php");
            } else {
                header("Location: member_dashboard.php");
            }
            exit();
        } else {
            $message = "Invalid password.";
        }
    } else {
        $message = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6366f1 0%, #4361ee 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 420px;
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
        .btn-login {
            height: 52px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 12px;
            background: var(--primary-color);
            border: none;
            transition: all 0.3s;
        }
        .btn-login:hover {
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

<div class="login-card">
    <a href="index.php" class="back-home">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <div class="text-center mt-3">
        <div class="brand-logo">
            <i class="bi bi-book-half"></i>
        </div>
        <h2 class="fw-bold text-dark mb-1">Welcome Back</h2>
        <p class="text-muted mb-4">Login to access your library</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-danger border-0 shadow-sm py-2 mb-4 d-flex align-items-center">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success border-0 shadow-sm py-2 mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i>
            Account created! Please login.
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required autocomplete="email">
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold small text-secondary">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control password-field" placeholder="••••••••" required>
                <span class="input-group-text" onclick="togglePassword()">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </span>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-login mb-4">Sign In</button>
    </form>
    
    <div class="text-center">
        <p class="text-muted mb-0">Don't have an account? <a href="register.php" class="text-primary fw-bold text-decoration-none">Sign Up</a></p>
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