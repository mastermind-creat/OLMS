<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header('Location: login.php');
    exit();
}

// Include the database connection file
include 'db_connection.php';

$message = "";
$book = null;

// Fetch book details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM books WHERE id = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
    } else {
        $message = "Book not found.";
    }
}

// Fetch categories for the dropdown
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category_id = intval($_POST['category_id']);
    $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
    $quantity = intval($_POST['quantity']);

    // Handle file upload
    $cover_photo = $book['cover_photo']; // Keep the existing cover photo by default
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
        $target_dir = "uploads/book_covers/";
        $cover_photo = $target_dir . basename($_FILES['cover_photo']['name']);
        move_uploaded_file($_FILES['cover_photo']['tmp_name'], $cover_photo);
    }

    $sql = "UPDATE books SET 
                title = '$title', 
                author = '$author', 
                category_id = $category_id, 
                isbn = '$isbn', 
                quantity = $quantity, 
                cover_photo = '$cover_photo' 
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        $message = "Book updated successfully!";
    } else {
        $message = "Error updating book: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
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
    <h2 class="text-center mb-4">Edit Book</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($book): ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $book['id'] ?>">
                    <div class="mb-3">
                        <label for="title" class="form-label">Book Title</label>
                        <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($book['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" name="author" id="author" class="form-control" value="<?= htmlspecialchars($book['author']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?= $category['id'] ?>" <?= $book['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" name="isbn" id="isbn" class="form-control" value="<?= htmlspecialchars($book['isbn']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="<?= $book['quantity'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="cover_photo" class="form-label">Cover Photo</label>
                        <input type="file" name="cover_photo" id="cover_photo" class="form-control">
                        <?php if ($book['cover_photo']): ?>
                            <img src="<?= $book['cover_photo'] ?>" alt="Cover Photo" class="img-thumbnail mt-2" style="max-height: 150px;">
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Book</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <p class="text-center">No book found to edit.</p>
    <?php endif; ?>

    <div class="mt-3 text-center">
        <a href="manage_books.php" class="btn btn-secondary">Back to Manage Books</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>