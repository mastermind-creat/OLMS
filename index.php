<?php
session_start();
include 'db_connection.php';

// Fetch the 3 most borrowed books using the borrow_requests table
$popular_books_sql = "
    SELECT books.*, COUNT(borrow_requests.book_id) AS borrow_count 
    FROM books
    LEFT JOIN borrow_requests ON books.id = borrow_requests.book_id
    WHERE borrow_requests.status = 'Book Issued' -- Only count approved borrow requests
    GROUP BY books.id
    ORDER BY borrow_count DESC
    LIMIT 3"; // Limit to 3 books
$popular_books_result = $conn->query($popular_books_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Library Management System</title>
    <link rel="stylesheet" href="/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        font-family: Arial, sans-serif;
    }

    .hero {
        background: url('assets/images/b2182435-28b5-47ee-b893-c7772220b7b7.jpg') no-repeat center center/cover;
        color: white;
        padding: 100px 20px;
        text-align: center;
        position: relative;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
    }

    .hero h1,
    .hero p,
    .hero a {
        position: relative;
        z-index: 2;
    }

    .book-card img {
        height: 200px;
        object-fit: cover;
    }

    footer {
        background: #212529;
        color: white;
        padding: 20px 0;
    }

    footer p {
        margin: 0;
    }
    </style>
</head>

<body>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">📚 MyLibrary</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Welcome to the Online Library</h1>
        <p class="lead">Borrow. Read. Learn. Anywhere, anytime.</p>
        <a href="login.php" class="btn btn-primary m-2">Login</a>
        <a href="register.php" class="btn btn-outline-light m-2">Register</a>
    </section>

    <!-- Featured Books -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Popular Books</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if ($popular_books_result->num_rows > 0): ?>
            <?php while ($book = $popular_books_result->fetch_assoc()): ?>
            <div class="col">
                <div class="card book-card">
                    <img src="<?= $book['cover_photo'] ?: 'https://via.placeholder.com/300x200'; ?>"
                        class="card-img-top" alt="Book Cover">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($book['title']); ?></h5>
                        <p class="card-text">Author: <?= htmlspecialchars($book['author']); ?></p>
                        <p class="card-text"><small class="text-muted">Borrowed <?= $book['borrow_count']; ?>
                                times</small></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <p class="text-center">No popular books available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Online Library System. All rights reserved.</p>
            <p>Email: support@library.com | Phone: +254-700-000000</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>