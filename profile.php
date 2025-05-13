<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header('Location: login.php');
    exit();
}

// Include the database connection file
include 'db_connection.php';

// Fetch member information
$user_id = $_SESSION['user_id'];
$member_sql = "SELECT * FROM users WHERE id = $user_id";
$member_result = $conn->query($member_sql);
$member = $member_result->fetch_assoc();

// Handle form submission for updating member info
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $update_sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone' WHERE id = $user_id";
    if ($conn->query($update_sql) === TRUE) {
        $_SESSION['name'] = $name; // Update session name
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}

// Fetch borrowing history for the logged-in member
$history_sql = "
    SELECT books.title, books.author, borrowed_books.days, borrowed_books.total_cost, borrowed_books.borrowed_at 
    FROM borrowed_books
    INNER JOIN books ON borrowed_books.book_id = books.id
    WHERE borrowed_books.user_id = $user_id
    ORDER BY borrowed_books.borrowed_at DESC";
$history_result = $conn->query($history_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">📚 MyLibrary</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Help</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2>Profile</h2>

    <!-- Display success or error message -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= $success_message; ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message; ?></div>
    <?php endif; ?>

    <!-- Member Info -->
    <div class="card mb-4">
        <div class="card-body">
            <h4>Update Profile</h4>
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($member['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($member['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($member['phone']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>

    <!-- Borrowing History -->
    <h4>Borrowing History</h4>
    <?php if ($history_result->num_rows > 0): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Days</th>
                    <th>Total Cost (Ksh)</th>
                    <th>Borrowed At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($history = $history_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($history['title']); ?></td>
                        <td><?= htmlspecialchars($history['author']); ?></td>
                        <td><?= $history['days']; ?></td>
                        <td><?= $history['total_cost']; ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($history['borrowed_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted">You have not borrowed any books yet.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>