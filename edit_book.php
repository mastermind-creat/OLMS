<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'librarian' && $_SESSION['role'] != 'admin')) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";

// Fetch book details
$sql = "SELECT * FROM books WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $book = $result->fetch_assoc();
} else {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Book not found.</div></div>";
    include 'includes/footer.php';
    exit();
}

// Fetch categories
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category_id = intval($_POST['category_id']);
    $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
    $quantity = intval($_POST['quantity']);

    // Handle file upload
    $cover_photo = $book['cover_photo']; // Default to existing
    $upload_error = "";
    
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['name'] != "") {
        if ($_FILES['cover_photo']['error'] == 0) {
            $target_dir = "uploads/book_covers/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
                $target_file = $target_dir . $file_name;
                
                if(move_uploaded_file($_FILES['cover_photo']['tmp_name'], $target_file)) {
                    // Delete old photo if it exists and is different
                    if ($book['cover_photo'] && file_exists($book['cover_photo'])) {
                        unlink($book['cover_photo']);
                    }
                    $cover_photo = $target_file;
                } else {
                    $upload_error = "Failed to move uploaded file. Check folder permissions.";
                }
            } else {
                $upload_error = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
            }
        } else {
            $upload_error = "Upload error code: " . $_FILES['cover_photo']['error'];
        }
    }

    if ($upload_error == "") {
        $update_sql = "UPDATE books SET title='$title', author='$author', category_id=$category_id, isbn='$isbn', quantity=$quantity, cover_photo='$cover_photo' WHERE id=$id";

        if ($conn->query($update_sql) === TRUE) {
            logAudit($conn, $_SESSION['user_id'], "Edit Book", "Updated book ID $id: $title (ISBN: $isbn)");
            $message = '<div class="alert alert-success">Book updated successfully!</div>';
            // Refresh book data
            $book = $conn->query("SELECT * FROM books WHERE id = $id")->fetch_assoc();
        } else {
            $message = '<div class="alert alert-danger">Database Error: ' . $conn->error . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Upload Error: ' . $upload_error . '</div>';
    }
}
?>

<div class="container fade-in" style="max-width: 800px;">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-warning text-dark p-4 text-center">
            <h3 class="mb-0 fw-bold"><i class="bi bi-pencil-square"></i> Edit Book</h3>
        </div>
        <div class="card-body p-5">
            <?= $message ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label fw-bold">Book Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($book['title']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="author" class="form-label fw-bold">Author</label>
                        <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($book['author']) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fw-bold">Category</label>
                        <select name="category_id" class="form-select" required>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?= $category['id'] ?>" <?= $book['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="isbn" class="form-label fw-bold">ISBN</label>
                        <div class="input-group">
                            <input type="text" name="isbn" id="isbn" class="form-control" value="<?= htmlspecialchars($book['isbn']) ?>" required>
                            <button class="btn btn-outline-warning" type="button" onclick="generateISBN()">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label for="quantity" class="form-label fw-bold">Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="<?= $book['quantity'] ?>" required>
                    </div>
                    <div class="col-md-6">
                         <label for="cover_photo" class="form-label fw-bold">Change Cover Photo</label>
                         <input type="file" name="cover_photo" class="form-control" accept="image/*" onchange="previewImage(event)">
                    </div>
                </div>

                 <!-- Current & New Image Preview -->
                <div class="mt-4 text-center">
                    <p class="fw-bold mb-2">Current Cover</p>
                    <?php if ($book['cover_photo']): ?>
                        <img src="<?= $book['cover_photo'] ?>" class="img-thumbnail mb-3" style="max-height: 150px;">
                    <?php else: ?>
                        <p class="text-muted">No cover available</p>
                    <?php endif; ?>
                    
                    <div id="imagePreviewContainer" style="display: none;">
                        <p class="fw-bold mb-2 text-success">New Cover Preview</p>
                        <img id="imagePreview" src="#" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                </div>

                <div class="d-grid mt-5">
                    <button type="submit" class="btn btn-warning btn-lg">Update Book</button>
                    <a href="manage_books.php" class="btn btn-outline-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function generateISBN() {
        // Generate a 13-digit ISBN (simplified: 978 + 10 random digits)
        let isbn = "978" + Math.floor(Math.random() * 10000000000).toString().padStart(10, '0');
        document.getElementById('isbn').value = isbn;
    }

    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('imagePreview');
            output.src = reader.result;
            document.getElementById('imagePreviewContainer').style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

<?php include 'includes/footer.php'; ?>