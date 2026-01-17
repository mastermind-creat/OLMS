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

// Fetch stats
$total_books = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$issued_books = $conn->query("SELECT COUNT(*) AS total FROM borrowed_books")->fetch_assoc()['total'];
// Count pending borrow requests
$pending_requests = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Pending'")->fetch_assoc()['total'];

// Recent Borrow Requests for Table
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
            <p class="text-muted">Manage books, issues, and returns.</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Books</p>
                        <h3 class="fw-bold mb-0"><?= $total_books ?></h3>
                    </div>
                    <div class="card-icon bg-light-primary">
                        <i class="bi bi-book-half"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
             <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Books Issued</p>
                        <h3 class="fw-bold mb-0"><?= $issued_books ?></h3>
                    </div>
                    <div class="card-icon bg-light-success">
                         <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Pending Requests</p>
                        <h3 class="fw-bold mb-0 text-danger"><?= $pending_requests ?></h3>
                    </div>
                    <div class="card-icon bg-light-danger">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Actions -->
        <div class="col-md-8">
            <div class="row g-4 mb-4">
                 <div class="col-md-6">
                    <div class="card h-100 text-center p-4">
                        <div class="card-body">
                             <div class="card-icon bg-light-primary mx-auto">
                                <i class="bi bi-plus-lg"></i>
                            </div>
                            <h5>Add New Book</h5>
                            <p class="text-muted small">Expand the library collection</p>
                            <a href="add_book.php" class="btn btn-primary w-100">Add Book</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                     <div class="card h-100 text-center p-4">
                        <div class="card-body">
                             <div class="card-icon bg-light-warning mx-auto">
                                <i class="bi bi-folder-plus"></i>
                            </div>
                            <h5>Manage Categories</h5>
                            <p class="text-muted small">Organize books by genre</p>
                            <a href="manage_categories.php" class="btn btn-outline-warning w-100">Categories</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Pending Borrow Requests</h5>
                    <a href="manage_borrow_requests.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Book</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($requests_result->num_rows > 0): ?>
                                <?php while($req = $requests_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($req['user_name']) ?></td>
                                        <td><?= htmlspecialchars($req['book_title']) ?></td>
                                        <td><?= date('M d, H:i', strtotime($req['requested_at'])) ?></td>
                                        <td>
                                            <a href="manage_borrow_requests.php?approve=<?= $req['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-check"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No pending requests</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Side Stats / Quick Links -->
        <div class="col-md-4">
            <div class="card p-4 mb-4">
                <h5 class="card-title mb-4">Issued Books Trend</h5>
                <canvas id="issuedChart"></canvas>
            </div>
            
            <div class="card p-3">
                <h6 class="mb-3 text-muted">Quick Navigation</h6>
                <div class="d-grid gap-2">
                    <a href="manage_books.php" class="btn btn-outline-secondary text-start"><i class="bi bi-book me-2"></i> Manage Books</a>
                    <a href="issued_books.php" class="btn btn-outline-secondary text-start"><i class="bi bi-journal-arrow-right me-2"></i> View Issued Books</a>
                    <a href="returned_books.php" class="btn btn-outline-secondary text-start"><i class="bi bi-journal-arrow-left me-2"></i> Process Returns</a>
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