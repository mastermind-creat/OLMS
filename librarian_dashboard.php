<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isLibrarian = (isset($_SESSION['role']) && $_SESSION['role'] === 'librarian') || (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'librarian');
if (!$isLoggedIn || !$isLibrarian) {
  header('Location: login.php');
  exit();
}

include 'includes/db_connection.php';
// Include header after logic to prevent output before headers
include 'includes/header.php';

// Fetch comprehensive statistics
$total_books = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$total_borrowed = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Book Issued'")->fetch_assoc()['total'];

// Borrow Requests Breakdown
$pending_requests = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Pending'")->fetch_assoc()['total'];
$approved_requests = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Book Issued'")->fetch_assoc()['total'];
$returned_requests = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Book Returned'")->fetch_assoc()['total'];

// Recent Books added
$recent_books = $conn->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 5");

// Recent Pending Requests for Table
$requests_sql = "SELECT br.id, u.name as user_name, b.title as book_title, br.requested_at 
                 FROM borrow_requests br 
                 JOIN users u ON br.user_id = u.id 
                 JOIN books b ON br.book_id = b.id 
                 WHERE br.status = 'Pending' 
                 ORDER BY br.requested_at ASC LIMIT 5";
$requests_result = $conn->query($requests_sql);
?>

<div class="container-fluid fade-in">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-primary">Librarian Dashboard</h2>
            <p class="text-muted">Manage library assets, track issues, and process requests.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['success_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm bg-light text-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75">Library Assets</p>
                        <h3 class="fw-bold mb-0"><?= $total_books ?> Books</h3>
                    </div>
                    <div class="card-icon bg-white bg-opacity-25 text-white">
                        <i class="bi bi-book-half"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
             <div class="card p-3 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Currently Borrowed</p>
                        <h3 class="fw-bold mb-0 text-success"><?= $total_borrowed ?></h3>
                    </div>
                    <div class="card-icon bg-light-success text-success">
                         <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 border-0 shadow-sm">
                <p class="text-muted mb-3">Borrow Requests Overview</p>
                <div class="row text-center">
                    <div class="col-4 border-end">
                        <h4 class="fw-bold mb-0 text-warning"><?= $pending_requests ?></h4>
                        <small class="text-muted">Pending</small>
                    </div>
                    <div class="col-4 border-end">
                        <h4 class="fw-bold mb-0 text-success"><?= $approved_requests ?></h4>
                        <small class="text-muted">Issued</small>
                    </div>
                    <div class="col-4">
                        <h4 class="fw-bold mb-0 text-secondary"><?= $returned_requests ?></h4>
                        <small class="text-muted">Returned</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Main Actions & Requests -->
        <div class="col-md-8">
            <div class="card p-4 border-0 shadow-sm mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Action Required: Pending Requests</h5>
                    <a href="manage_borrow_requests.php" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr class="smaller">
                                <th>User</th>
                                <th>Book Requested</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if ($requests_result->num_rows > 0): ?>
                                <?php while($req = $requests_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($req['user_name']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($req['book_title']) ?></td>
                                        <td class="text-muted"><?= date('M d, H:i', strtotime($req['requested_at'])) ?></td>
                                        <td class="text-end">
                                            <a href="manage_borrow_requests.php?action=approve&request_id=<?= $req['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-check"></i> Approve</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">All caught up! No pending requests.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recently Added Books -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Recently Added Books</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody class="small">
                                <?php while($book = $recent_books->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3" style="width: 50px;">
                                            <img src="<?= $book['cover_photo'] ?? 'assets/img/default_book.png' ?>" class="rounded" style="width: 35px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($book['title']) ?></div>
                                            <div class="text-muted smaller"><?= htmlspecialchars($book['author']) ?></div>
                                        </td>
                                        <td>
                                            <div class="badge bg-light text-dark smaller"><?= $book['isbn'] ?></div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="text-muted smaller"><?= date('M d, Y', strtotime($book['created_at'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions & System Status -->
        <div class="col-md-4">
            <div class="card p-4 border-0 shadow-sm mb-4">
                <h6 class="fw-bold mb-3 text-muted">Issued Books Trend</h6>
                <canvas id="issuedChart" style="max-height: 200px;"></canvas>
            </div>

            <div class="card p-4 border-0 shadow-sm mb-4">
                <h6 class="fw-bold mb-3 text-muted">Quick Management</h6>
                <div class="d-grid gap-2">
                    <a href="send_overdue_notifications.php" class="btn btn-warning d-flex align-items-center justify-content-between text-start">
                        <span><i class="bi bi-bell me-2"></i> Check Overdue</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="add_book.php" class="btn btn-primary d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-plus-circle me-2"></i> Add Book</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="manage_books.php" class="btn btn-outline-primary d-flex align-items-center justify-content-between text-start">
                        <span><i class="bi bi-collection me-2"></i> Inventory</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="manage_categories.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-between text-start">
                        <span><i class="bi bi-tags me-2"></i> Categories</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
            </div>

            <div class="card p-4 border-0 shadow-sm mb-4">
                <h6 class="fw-bold mb-3 text-muted">Tracking</h6>
                <div class="list-group list-group-flush small">
                    <a href="issued_books.php" class="list-group-item list-group-item-action border-0 px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-journal-arrow-right text-info me-2"></i> Issued Books</span>
                        <span class="badge bg-info rounded-pill"><?= $approved_requests ?></span>
                    </a>
                    <a href="returned_books.php" class="list-group-item list-group-item-action border-0 px-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-journal-arrow-left text-secondary me-2"></i> Returned Books</span>
                        <span class="badge bg-secondary rounded-pill"><?= $returned_requests ?></span>
                    </a>
                </div>
            </div>

            <div class="card p-4 border-0 shadow-sm">
                <h6 class="fw-bold mb-3 text-muted">System Alerts</h6>
                <div class="small">
                    <?php if ($pending_requests > 0): ?>
                        <div class="alert alert-warning py-2 px-3 border-0 d-flex align-items-center mb-2">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <span><?= $pending_requests ?> requests need attention</span>
                        </div>
                    <?php else: ?>
                        <div class="text-muted text-center py-2">
                             <i class="bi bi-check2-all fs-4 mb-2 d-block"></i>
                             No urgent alerts
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple mock chart for Issued Books Trend
    const ctxIssued = document.getElementById('issuedChart').getContext('2d');
    new Chart(ctxIssued, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Books Issued',
                data: [12, 19, 3, 5, 2, 3, 7], // Placeholder data - ideally fetch from DB
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>