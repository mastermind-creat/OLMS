<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header('Location: login.php');
    exit();
}

// Include the database connection file
include 'db_connection.php';

// Fetch all returned books
$returned_books_sql = "
    SELECT borrow_requests.id, books.title, users.name AS member_name, borrow_requests.days, borrow_requests.total_cost, borrow_requests.requested_at, borrow_requests.updated_at 
    FROM borrow_requests
    INNER JOIN books ON borrow_requests.book_id = books.id
    INNER JOIN users ON borrow_requests.user_id = users.id
    WHERE borrow_requests.status = 'Book Returned'
    ORDER BY borrow_requests.updated_at DESC";
$returned_books_result = $conn->query($returned_books_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returned Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Returned Books</h2>

        <!-- Search Bar -->
        <div class="mt-4 mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by member name or book title...">
        </div>

        <!-- Returned Books Table -->
        <div class="mt-4">
            <?php if ($returned_books_result->num_rows > 0): ?>
                <table class="table table-striped" id="returnedBooksTable">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Member Name</th>
                            <th>Days</th>
                            <!-- <th>Total Cost (Ksh)</th> -->
                            <th>Returned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $returned_books_result->fetch_assoc()): ?>
                            <tr>
                                <td class="book-title"><?= htmlspecialchars($book['title']); ?></td>
                                <td class="member-name"><?= htmlspecialchars($book['member_name']); ?></td>
                                <td><?= $book['days']; ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($book['updated_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center">No returned books found.</p>
            <?php endif; ?>
        </div>

        <div class="mt-3 text-center">
            <a href="librarian_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Live Search Functionality
        $(document).ready(function() {
            $('#searchInput').on('keyup', function() {
                var searchValue = $(this).val().toLowerCase();
                $('#returnedBooksTable tbody tr').filter(function() {
                    $(this).toggle(
                        $(this).find('.book-title').text().toLowerCase().includes(searchValue) ||
                        $(this).find('.member-name').text().toLowerCase().includes(searchValue)
                    );
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>