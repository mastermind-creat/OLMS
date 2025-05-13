<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Include the database connection file
include 'db_connection.php';

$message = "";

// Fetch categories for the filter dropdown
$sql = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql);

// Fetch books based on selected category
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$books_sql = "SELECT books.*, categories.name AS category_name 
              FROM books 
              LEFT JOIN categories ON books.category_id = categories.id";
if ($category_filter) {
    $books_sql .= " WHERE books.category_id = $category_filter";
}
$books_sql .= " ORDER BY books.title ASC";
$books_result = $conn->query($books_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books</title>
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
        <h2 class="text-center mb-4">Manage Books</h2>

        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-8">
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php if ($categories_result->num_rows > 0): ?>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>ISBN</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($books_result->num_rows > 0): ?>
                            <?php while ($book = $books_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $book['id'] ?></td>
                                    <td><?= htmlspecialchars($book['title']) ?></td>
                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                    <td><?= htmlspecialchars($book['category_name']) ?></td>
                                    <td><?= htmlspecialchars($book['isbn']) ?></td>
                                    <td><?= $book['quantity'] ?></td>
                                    <td>
                                        <a href="edit_book.php?id=<?= $book['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="manage_books.php?delete_id=<?= $book['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No books found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3 text-center">
            <a href="librarian_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>