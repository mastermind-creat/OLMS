<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'librarian' && $_SESSION['role'] != 'admin')) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

$message = "";

// Fetch categories
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category_id = intval($_POST['category_id']);
    $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
    $quantity = intval($_POST['quantity']);

    // Handle file upload
    $cover_photo = null;
    $upload_error = "";
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['name'] != "") {
        if ($_FILES['cover_photo']['error'] == 0) {
            $target_dir = "uploads/book_covers/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_extension = strtolower(pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp' , 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
                $target_file = $target_dir . $file_name;
                
                if(move_uploaded_file($_FILES['cover_photo']['tmp_name'], $target_file)) {
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
        $sql = "INSERT INTO books (title, author, category_id, isbn, quantity, cover_photo) 
                VALUES ('$title', '$author', $category_id, '$isbn', $quantity, '$cover_photo')";

        if ($conn->query($sql) === TRUE) {
            $message = '<div class="alert alert-success">Book added successfully!</div>';
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
        <div class="card-header bg-primary text-white p-4 text-center">
            <h3 class="mb-0 fw-bold"><i class="bi bi-book-fill"></i> Add New Book</h3>
        </div>
        <div class="card-body p-5">
            <?= $message ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label fw-bold">Book Title</label>
                        <input type="text" name="title" id="title" class="form-control" required placeholder="Enter book title">
                    </div>
                    <div class="col-md-6">
                        <label for="author" class="form-label fw-bold">Author</label>
                        <input type="text" name="author" id="author" class="form-control" required placeholder="Enter author name">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fw-bold">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Select a Category</option>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="isbn" class="form-label fw-bold">ISBN</label>
                        <div class="input-group">
                            <input type="text" name="isbn" id="isbn" class="form-control" required placeholder="ISBN Number">
                            <button class="btn btn-outline-primary" type="button" onclick="generateISBN()">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label for="quantity" class="form-label fw-bold">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" required min="1" value="1">
                    </div>
                    <div class="col-md-6">
                         <label for="cover_photo" class="form-label fw-bold">Cover Photo</label>
                         <input type="file" name="cover_photo" id="cover_photo" class="form-control" accept="image/*" onchange="previewImage(event)">
                    </div>
                </div>

                <!-- Image Preview -->
                <div class="mt-3 text-center" id="imagePreviewContainer" style="display: none;">
                    <img id="imagePreview" src="#" alt="Cover Preview" class="img-thumbnail" style="max-height: 200px;">
                </div>

                <div class="d-grid mt-5">
                    <button type="submit" class="btn btn-primary btn-lg">Add Book</button>
                    <a href="manage_books.php" class="btn btn-outline-secondary mt-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (!document.getElementById('isbn').value) {
            generateISBN();
        }
    });

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