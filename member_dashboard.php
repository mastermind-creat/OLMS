<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header('Location: login.php');
    exit();
}

// Include the database connection file
include 'db_connection.php';

// Fetch categories and books
$categories_sql = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_sql);

// Fetch borrowing history for the logged-in member using the borrow_requests table
$user_id = $_SESSION['user_id'];
$history_sql = "
    SELECT books.title, books.author, borrow_requests.days, borrow_requests.total_cost, borrow_requests.requested_at 
    FROM borrow_requests
    INNER JOIN books ON borrow_requests.book_id = books.id
    WHERE borrow_requests.user_id = $user_id AND borrow_requests.status = 'Book Issued'
    ORDER BY borrow_requests.requested_at DESC";
$history_result = $conn->query($history_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .category-section {
            margin-top: 30px;
        }

        .book-card {
            margin-bottom: 20px;
        }

        .book-card img {
            height: 150px;
            object-fit: cover;
        }

        .history-section {
            margin-top: 50px;
        }
    </style>
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
                    <li class="nav-item"><a class="nav-link active" href="member_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Help</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Welcome, <?= $_SESSION['name']; ?>!</h2>
        <p>This is your member dashboard.</p>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Search books or categories...">
        </div>

        <!-- Categories and Books -->
        <div id="booksContainer">
            <?php if ($categories_result->num_rows > 0): ?>
                <?php while ($category = $categories_result->fetch_assoc()): ?>
                    <div class="category-section">
                        <h4><?= htmlspecialchars($category['name']); ?></h4>
                        <div class="row">
                            <?php
                            $category_id = $category['id'];
                            $books_sql = "SELECT * FROM books WHERE category_id = $category_id";
                            $books_result = $conn->query($books_sql);
                            ?>
                            <?php if ($books_result->num_rows > 0): ?>
                                <?php while ($book = $books_result->fetch_assoc()): ?>
                                    <div class="col-md-3 book-card">
                                        <div class="card">
                                            <?php if ($book['cover_photo']): ?>
                                                <img src="<?= $book['cover_photo']; ?>" class="card-img-top" alt="Book Cover">
                                            <?php else: ?>
                                                <img src="default_cover.jpg" class="card-img-top" alt="Default Cover">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($book['title']); ?></h5>
                                                <p class="card-text">Author: <?= htmlspecialchars($book['author']); ?></p>
                                                <p class="card-text">ISBN: <?= htmlspecialchars($book['isbn']); ?></p>
                                                <p class="card-text">Available Copies: <?= $book['quantity']; ?></p>
                                                <?php if ($book['quantity'] > 0): ?>
                                                    <a href="borrow_book.php?book_id=<?= $book['id']; ?>" class="btn btn-primary w-100">Borrow</a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary w-100" disabled>Out of Stock</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted">No books available in this category.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No categories found.</p>
            <?php endif; ?>
        </div>

        <!-- Borrowing History Section -->
        <div class="history-section">
            <h4>Your Borrowing History</h4>
            <?php if ($history_result->num_rows > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Days Borrowed</th>
                            <!-- <th>Total Cost (Ksh)</th> -->
                            <th>Borrowed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($history = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($history['title']); ?></td>
                                <td><?= htmlspecialchars($history['author']); ?></td>
                                <td><?= $history['days']; ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($history['requested_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">You have not borrowed any books yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Real-time search functionality
        $(document).ready(function() {
            $('#searchInput').on('keyup', function() {
                var searchValue = $(this).val().toLowerCase();
                $('.category-section').each(function() {
                    var categoryName = $(this).find('h4').text().toLowerCase();
                    var booksFound = false;

                    $(this).find('.book-card').each(function() {
                        var bookTitle = $(this).find('.card-title').text().toLowerCase();
                        var bookAuthor = $(this).find('.card-text').text().toLowerCase();

                        if (bookTitle.includes(searchValue) || bookAuthor.includes(searchValue) || categoryName.includes(searchValue)) {
                            $(this).show();
                            booksFound = true;
                        } else {
                            $(this).hide();
                        }
                    });

                    if (booksFound || categoryName.includes(searchValue)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>