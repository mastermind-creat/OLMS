<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$issued_books_count = $conn->query("SELECT COUNT(*) as count FROM borrow_requests WHERE user_id = $user_id AND status = 'Book Issued'")->fetch_assoc()['count'];
$can_borrow = $issued_books_count < 2;

// Pagination setup
$limit = 12; // 12 items for grid (3x4 or 4x3)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search Logic
$search_query = "";
$search_sql = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = " AND (books.title LIKE '%$search_query%' OR books.author LIKE '%$search_query%' OR books.isbn LIKE '%$search_query%')";
}

// Category Filter Logic
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$cat_sql_part = "";
if ($category_filter) {
    $cat_sql_part = " AND books.category_id = $category_filter";
}

// Main Query (Ensure we only show active/available books logic if needed, usually just quantity > 0 check in display or query)
// Showing all books even if out of stock is fine, just disable button.
$sql = "SELECT books.*, categories.name as category_name 
        FROM books 
        LEFT JOIN categories ON books.category_id = categories.id
        WHERE 1=1 $search_sql $cat_sql_part
        ORDER BY books.title ASC LIMIT $start, $limit";
$result = $conn->query($sql);

// Count Total for Pagination
$count_query_sql = "SELECT COUNT(*) as total FROM books WHERE 1=1 $search_sql $cat_sql_part";
$total_result = $conn->query($count_query_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch categories for filter dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<div class="container fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">Browse Library</h2>
            <p class="text-muted">Find your next read from our collection.</p>
        </div>
        <?php if (!$can_borrow): ?>
            <div class="alert alert-warning py-2 shadow-sm border-0 d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span>You've reached your limit of 2 books. Return some to borrow more.</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-5 shadow-sm border-0">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by Title, Author, ISBN..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
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
                <button type="submit" class="btn btn-primary w-100 fw-bold">Search</button>
            </div>
            <div class="col-md-2">
                 <a href="browse_books.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <!-- Books Grid -->
    <div class="row g-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while($book = $result->fetch_assoc()): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 shadow-hover border-0 overflow-hidden">
                        <div class="position-relative" style="height: 250px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <?php if ($book['cover_photo']): ?>
                                <img src="<?= $book['cover_photo'] ?>" alt="<?= htmlspecialchars($book['title']) ?>" style="height: 100%; width: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="bi bi-book display-1 text-secondary opacity-50"></i>
                            <?php endif; ?>
                            <?php if ($book['quantity'] <= 0): ?>
                                <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center">
                                    <span class="badge bg-danger fs-6 px-3 py-2">Out of Stock</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small"><?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?></span>
                            </div>
                            <h5 class="card-title fw-bold text-dark text-truncate" title="<?= htmlspecialchars($book['title']) ?>"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="card-text text-muted small mb-3">by <?= htmlspecialchars($book['author']) ?></p>
                            
                            <div class="mt-auto">
                                <?php if($book['quantity'] > 0): ?>
                                    <?php if($can_borrow): ?>
                                        <a href="borrow_book.php?book_id=<?= $book['id'] ?>" class="btn btn-outline-primary w-100 fw-bold">Borrow Book</a>
                                    <?php else: ?>
                                        <button class="btn btn-light text-muted w-100 fw-bold" disabled title="Limit Reached">Borrow Book</button>
                                    <?php     endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-light text-muted w-100" disabled>Unavailable</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-emoji-frown display-1 text-muted"></i>
                <h3 class="mt-4 text-muted">No books found.</h3>
                <p class="text-muted">Try adjusting your search filters.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav class="mt-5 mb-5">
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
    <?php endif; ?>
</div>

<style>
    .shadow-hover {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .shadow-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style>

<?php include 'includes/footer.php'; ?>
