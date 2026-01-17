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

// Handle category deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if category is in use
    $check_use = $conn->query("SELECT COUNT(*) as count FROM books WHERE category_id = $delete_id")->fetch_assoc();
    
    if ($check_use['count'] > 0) {
        $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                      Cannot delete category. It is currently assigned to ' . $check_use['count'] . ' books.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    } else {
        $sql = "DELETE FROM categories WHERE id = $delete_id";
        if ($conn->query($sql) === TRUE) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                          Category deleted successfully!
                          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            $message = '<div class="alert alert-danger">Error deleting category: ' . $conn->error . '</div>';
        }
    }
}

// Fetch all categories
$sql = "SELECT categories.*, (SELECT COUNT(*) FROM books WHERE books.category_id = categories.id) as book_count 
        FROM categories ORDER BY name ASC";
$result = $conn->query($sql);
?>

<div class="container-fluid fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">Manage Categories</h2>
            <p class="text-muted">Organize books by creating and managing categories.</p>
        </div>
        <div>
            <a href="create_category.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create New Category</a>
        </div>
    </div>

    <?= $message ?>

    <div class="card p-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th width="120">Books</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($row['description']) ?></td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info fw-bold">
                                        <?= $row['book_count'] ?> Books
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="edit_category.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="manage_categories.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-tags display-1 opacity-25"></i>
                                <p class="mt-3">No categories found. Start by creating one!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>