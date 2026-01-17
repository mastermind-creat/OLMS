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
$category = null;

// Fetch category details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM categories WHERE id = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();
    } else {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Category not found.</div><a href='manage_categories.php' class='btn btn-secondary'>Back to Categories</a></div>";
        include 'includes/footer.php';
        exit();
    }
} else {
    header('Location: manage_categories.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $sql = "UPDATE categories SET name = '$category_name', description = '$description' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        $message = '<div class="alert alert-success">Category updated successfully! <a href="manage_categories.php" class="alert-link">Return to list</a></div>';
        // Refresh data
        $category = $conn->query("SELECT * FROM categories WHERE id = $id")->fetch_assoc();
    } else {
        $message = '<div class="alert alert-danger">Error updating category: ' . $conn->error . '</div>';
    }
}
?>

<div class="container fade-in" style="max-width: 700px;">
    <div class="d-flex align-items-center mb-4 text-warning">
        <a href="manage_categories.php" class="btn btn-link text-decoration-none p-0 me-3">
            <i class="bi bi-arrow-left-circle fs-2 text-warning"></i>
        </a>
        <h2 class="fw-bold mb-0 text-dark">Edit Category</h2>
    </div>

    <div class="card shadow-lg border-0 overflow-hidden">
        <div class="card-header bg-warning text-dark p-4 text-center">
            <h3 class="mb-0 fw-bold"><i class="bi bi-pencil-fill"></i> Modify Category</h3>
        </div>
        <div class="card-body p-5">
            <?= $message ?>

            <form method="POST">
                <div class="mb-4">
                    <label for="category_name" class="form-label fw-bold">Category Name</label>
                    <input type="text" name="category_name" id="category_name" class="form-control form-control-lg" value="<?= htmlspecialchars($category['name']) ?>" required>
                </div>
                <div class="mb-4">
                    <label for="description" class="form-label fw-bold">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" required><?= htmlspecialchars($category['description']) ?></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning btn-lg fw-bold">
                        <i class="bi bi-save me-2"></i> Update Category
                    </button>
                    <a href="manage_categories.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>