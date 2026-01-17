<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Fetch stats for member
$borrowed_books_count = $conn->query("SELECT COUNT(*) as count FROM borrow_requests WHERE user_id = $user_id AND status = 'Book Issued'")->fetch_assoc()['count'];
$pending_requests_count = $conn->query("SELECT COUNT(*) as count FROM borrow_requests WHERE user_id = $user_id AND status = 'Pending'")->fetch_assoc()['count'];
$can_borrow = $borrowed_books_count < 2;

// Fetch available books with category names
$books_sql = "SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.quantity > 0 ORDER BY b.id DESC LIMIT 8";
$books_result = $conn->query($books_sql);

// Fetch borrowing history
$history_sql = "SELECT b.title, b.author, br.status, br.requested_at 
                FROM borrow_requests br 
                JOIN books b ON br.book_id = b.id 
                WHERE br.user_id = $user_id 
                ORDER BY br.requested_at DESC LIMIT 5";
$history_result = $conn->query($history_sql);
?>

<div class="container-fluid fade-in">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-primary">Member Dashboard</h2>
            <p class="text-muted">Browse books and manage your borrowings.</p>
        </div>
    </div>

    <!-- User Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card p-3 bg-light-primary border-0">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-white rounded-circle shadow-sm me-3">
                         <i class="bi bi-journal-arrow-up fs-3 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 text-dark">Currently Borrowed</h5>
                        <h3 class="fw-bold text-primary mb-0"><?= $borrowed_books_count ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 bg-light-warning border-0">
                 <div class="d-flex align-items-center">
                    <div class="p-3 bg-white rounded-circle shadow-sm me-3">
                         <i class="bi bi-clock-history fs-3 text-warning"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 text-dark">Pending Requests</h5>
                        <h3 class="fw-bold text-warning mb-0"><?= $pending_requests_count ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Books -->
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold">New Arrivals</h4>
            <a href="browse_books.php" class="btn btn-outline-primary btn-sm">View All Books</a>
        </div>
        
        <?php if ($books_result->num_rows > 0): ?>
            <?php while($book = $books_result->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div style="height: 200px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center;">
                            <?php if ($book['cover_photo']): ?>
                                <img src="<?= $book['cover_photo'] ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>" style="height: 100%; width: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="bi bi-book display-1 text-secondary"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-info bg-opacity-10 text-info mb-2 align-self-start"><?= htmlspecialchars($book['category_name']) ?></span>
                            <h6 class="card-title fw-bold text-truncate" title="<?= htmlspecialchars($book['title']) ?>"><?= htmlspecialchars($book['title']) ?></h6>
                            <p class="card-text small text-muted mb-auto">By <?= htmlspecialchars($book['author']) ?></p>
                            
                             <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="badge bg-success">In Library: <?= $book['quantity'] ?></span>
                                <?php if($can_borrow): ?>
                                    <a href="borrow_book.php?book_id=<?= $book['id'] ?>" class="btn btn-primary btn-sm">Borrow</a>
                                <?php else: ?>
                                    <button class="btn btn-light btn-sm text-muted" disabled title="Limit Reached">Borrow</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-emoji-frown display-4"></i>
                <p class="mt-3">No books available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent History -->
    <div class="row">
        <div class="col-md-12">
            <div class="card p-4">
                <h5 class="card-title mb-3">Recent Activity</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Book</th>
                                <th>Author</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history_result->num_rows > 0): ?>
                                <?php while($hist = $history_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($hist['title']) ?></td>
                                        <td><?= htmlspecialchars($hist['author']) ?></td>
                                        <td><?= date('d M Y', strtotime($hist['requested_at'])) ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'bg-secondary';
                                            if ($hist['status'] == 'Approved') $statusClass = 'bg-success';
                                            elseif ($hist['status'] == 'Pending') $statusClass = 'bg-warning text-dark';
                                            elseif ($hist['status'] == 'Rejected') $statusClass = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= $hist['status'] ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No recent activity.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>