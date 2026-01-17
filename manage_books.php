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

// Delete Book Logic
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // First, get the cover photo path to delete the file
    $book_query = $conn->query("SELECT cover_photo FROM books WHERE id = $delete_id");
    if ($book_query && $book_query->num_rows > 0) {
        $book_data = $book_query->fetch_assoc();
        if ($book_data['cover_photo'] && file_exists($book_data['cover_photo'])) {
            unlink($book_data['cover_photo']);
        }
    }
    
    $delete_sql = "DELETE FROM books WHERE id = $delete_id";
    if ($conn->query($delete_sql) === TRUE) {
        $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                      Book deleted successfully!
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search Logic
$search_query = "";
$search_sql = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = " WHERE (books.title LIKE '%$search_query%' OR books.author LIKE '%$search_query%' OR books.isbn LIKE '%$search_query%')";
}

// Category Filter Logic
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$cat_sql_part = "";
if ($category_filter) {
    if (empty($search_sql)) {
        $cat_sql_part = " WHERE books.category_id = $category_filter";
    } else {
        $cat_sql_part = " AND books.category_id = $category_filter";
    }
}

// Main Query
$sql = "SELECT books.*, categories.name as category_name 
        FROM books 
        LEFT JOIN categories ON books.category_id = categories.id
        $search_sql $cat_sql_part
        ORDER BY books.title ASC LIMIT $start, $limit";
$result = $conn->query($sql);

// Count Total for Pagination
$count_query_sql = "SELECT COUNT(*) as total FROM books $search_sql $cat_sql_part";
$total_result = $conn->query($count_query_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch categories for filter dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<div class="container-fluid fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">Manage Books</h2>
            <p class="text-muted">View, edit, or delete books from the library.</p>
        </div>
        <div>
            <a href="add_book.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New Book</a>
        </div>
    </div>

    <?= $message ?>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by Title, Author, ISBN..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2">
                 <a href="manage_books.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>

    <!-- Books Table -->
    <div class="card p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Qty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($book = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $book['id'] ?></td>
                                <td>
                                    <?php if ($book['cover_photo']): ?>
                                        <img src="<?= $book['cover_photo'] ?>" alt="Cover" style="height: 50px; width: 40px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center text-muted border" style="height: 50px; width: 40px; border-radius: 4px;"><i class="bi bi-book"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($book['category_name']) ?></span></td>
                                <td><?= htmlspecialchars($book['isbn']) ?></td>
                                <td>
                                    <?php if($book['quantity'] < 5): ?>
                                        <span class="text-danger fw-bold"><?= $book['quantity'] ?></span>
                                    <?php else: ?>
                                        <?= $book['quantity'] ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_book.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="manage_books.php?delete_id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No books found matching criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>&category_id=<?= htmlspecialchars($_GET['category_id'] ?? '') ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>&category_id=<?= htmlspecialchars($_GET['category_id'] ?? '') ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>&category_id=<?= htmlspecialchars($_GET['category_id'] ?? '') ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<?php include 'includes/footer.php'; ?>