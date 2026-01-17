<?php
session_start();
// Allow both admin and librarian
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'librarian')) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Check if exists
    $check = $conn->query("SELECT id FROM categories WHERE name = '$category_name'");
    if ($check->num_rows > 0) {
        $message = '<div class="alert alert-warning">Category name already exists.</div>';
    } else {
        $sql = "INSERT INTO categories (name, description) VALUES ('$category_name', '$description')";
        if ($conn->query($sql) === TRUE) {
            $message = '<div class="alert alert-success">Category created successfully! <a href="manage_categories.php" class="alert-link">Manage all categories here</a></div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
}
?>

<div class="container fade-in" style="max-width: 700px;">
    <div class="d-flex align-items-center mb-4 text-primary">
        <a href="manage_categories.php" class="btn btn-link text-decoration-none p-0 me-3">
            <i class="bi bi-arrow-left-circle fs-2"></i>
        </a>
        <h2 class="fw-bold mb-0">Create Category</h2>
    </div>

    <div class="card shadow-lg border-0 overflow-hidden">
        <div class="card-header bg-primary text-white p-4 text-center">
            <h3 class="mb-0 fw-bold"><i class="bi bi-tag-fill"></i> New Book Category</h3>
        </div>
        <div class="card-body p-5">
            <?= $message ?>

            <form method="POST">
                <div class="mb-4">
                    <label for="category_name" class="form-label fw-bold">Category Name</label>
                    <input type="text" name="category_name" id="category_name" class="form-control form-control-lg" required placeholder="e.g. Science Fiction, History...">
                </div>
                <div class="mb-4">
                    <label for="description" class="form-label fw-bold">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" required placeholder="Briefly describe what kind of books fall into this category..."></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i> Create Category
                    </button>
                    <a href="manage_categories.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>