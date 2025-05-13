<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    $user_id = $_SESSION['user_id'];

    // Fetch book details
    $book_sql = "SELECT * FROM books WHERE id = $book_id";
    $book_result = $conn->query($book_sql);

    if ($book_result->num_rows > 0) {
        $book = $book_result->fetch_assoc();

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $days = intval($_POST['days']);
            $total_cost = $days * 50; // Ksh. 50 per day

            // Insert borrow request into borrow_requests table
            $sql = "INSERT INTO borrow_requests (book_id, user_id, days, total_cost, status) 
                    VALUES ($book_id, $user_id, $days, $total_cost, 'pending')";
            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Borrow request sent successfully! Please wait for librarian approval.'); window.location.href='member_dashboard.php';</script>";
            } else {
                echo "Error: " . $conn->error;
            }
        }
    } else {
        echo "Book not found.";
    }
} else {
    echo "Invalid book ID.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Borrow Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
                <?php if (isset($book)): ?>
                    <h3 class="card-title">Borrow: <?= htmlspecialchars($book['title']); ?></h3>
                    <p class="card-text">Author: <?= htmlspecialchars($book['author']); ?></p>
                    <p class="card-text">ISBN: <?= htmlspecialchars($book['isbn']); ?></p>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="days" class="form-label">Number of Days</label>
                            <input type="number" name="days" id="days" class="form-control" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Confirm Borrow</button>
                    </form>
                <?php else: ?>
                    <p class="text-danger">Book not found or invalid book ID.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3 text-center">
            <a href="member_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>