<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Data fetching is now done within the body or via includes, 
// but we need to fetch stats for the dashboard here.
include 'includes/db_connection.php';

// Fetch comprehensive statistics
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$total_librarians = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'librarian'")->fetch_assoc()['total'];
$total_members = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'member'")->fetch_assoc()['total'];
$total_books = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$total_borrowed = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Book Issued'")->fetch_assoc()['total'];

// Borrow Requests Breakdown
$pending_requests = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Pending'")->fetch_assoc()['total'];
$approved_requests = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Book Issued'")->fetch_assoc()['total'];
$returned_requests = $conn->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'Book Returned'")->fetch_assoc()['total'];

// Recent Books
$recent_books = $conn->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 5");

// Recent Users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Fetch Book Categories Data for Pie Chart
$cat_query = "SELECT c.name, COUNT(b.id) as count FROM categories c LEFT JOIN books b ON c.id = b.category_id GROUP BY c.id";
$cat_result = $conn->query($cat_query);
$cat_labels = [];
$cat_data = [];
while($row = $cat_result->fetch_assoc()) {
    $cat_labels[] = $row['name'];
    $cat_data[] = $row['count'];
}

include 'includes/header.php';
?>

<div class="container-fluid fade-in">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="fw-bold text-primary">Admin Dashboard</h2>
            <p class="text-muted">Overview of the library system performance.</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['success_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm bg-light text-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75">Total Registered Users</p>
                        <h3 class="fw-bold mb-0"><?= $total_users ?></h3>
                        <small><?= $total_librarians ?> Librarians, <?= $total_members ?> Members</small>
                    </div>
                    <div class="card-icon bg-white bg-opacity-25 text-white">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Library Collection</p>
                        <h3 class="fw-bold mb-0 text-primary"><?= $total_books ?> Books</h3>
                        <small class="text-success"><?= $total_borrowed ?> Currently Borrowed</small>
                    </div>
                    <div class="card-icon bg-light-primary text-primary">
                        <i class="bi bi-book-half"></i>
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

    <!-- Charts and Recent Activity Section -->
    <div class="row g-4 mb-4">
        <!-- Analytics Chart -->
        <div class="col-md-8">
            <div class="card p-4 border-0 shadow-sm h-100">
                <h5 class="card-title fw-bold mb-4">System Analytics</h5>
                <canvas id="libraryStatsChart"></canvas>
            </div>
        </div>
        <!-- Category Chart -->
        <div class="col-md-4">
            <div class="card p-4 border-0 shadow-sm h-100">
                <h5 class="card-title fw-bold mb-4">Books by Category</h5>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recently Added Items -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Recently Added Books</h6>
                    <a href="manage_books.php" class="btn btn-sm btn-link">View All</a>
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
                                        <td class="text-end pe-3">
                                            <span class="text-muted smaller"><?= date('M d', strtotime($book['created_at'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Recently Registered Users</h6>
                    <a href="manage_users.php" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody class="small">
                                <?php while($user = $recent_users->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="bg-light-primary text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px;">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                            <div class="badge bg-light text-dark smaller"><?= ucfirst($user['role']) ?></div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="text-muted smaller"><?= date('M d', strtotime($user['created_at'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and System Status -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card p-4 border-0 shadow-sm">
                <h5 class="card-title fw-bold mb-3">Quick Actions</h5>
                <div class="d-grid gap-2 d-md-block">
                    <a href="add_librarian.php" class="btn btn-primary btn-sm me-md-2 mb-2"><i class="bi bi-person-plus"></i> Add Librarian</a>
                    <a href="add_book.php" class="btn btn-success btn-sm me-md-2 mb-2"><i class="bi bi-plus-lg"></i> Add Book</a>
                    <a href="send_overdue_notifications.php" class="btn btn-warning btn-sm me-md-2 mb-2"><i class="bi bi-bell"></i> Check Overdue</a>
                    <a href="audit_trail.php" class="btn btn-info btn-sm text-white me-md-2 mb-2"><i class="bi bi-clock-history"></i> Audit Trail</a>
                    <a href="reports.php" class="btn btn-dark btn-sm mb-2"><i class="bi bi-file-earmark-bar-graph"></i> Data Reports</a>
                </div>
            </div>
        </div>
         <div class="col-md-6">
            <div class="card p-4 border-0 shadow-sm">
                <h5 class="card-title fw-bold mb-3">System Health & Notifications</h5>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 ps-0 pe-0">
                        <span><i class="bi bi-database-check text-success me-2"></i> Database Server</span>
                        <span class="badge bg-success rounded-pill">Optimal</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 ps-0 pe-0">
                        <span><i class="bi bi-cpu text-primary me-2"></i> System Load</span>
                        <span class="text-muted">Stable (0.2%)</span>
                    </li>
                    <?php if ($pending_requests > 0): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 ps-0 pe-0">
                            <span><i class="bi bi-exclamation-circle text-warning me-2"></i> Action Required</span>
                            <span class="text-warning"><?= $pending_requests ?> Borrow Requests Pending</span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

</div>

<!-- Initialize Charts -->
<script>
    // Category Pie Chart
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_data) ?>,
                backgroundColor: ['#4361ee', '#3f37c9', '#4cc9f0', '#f72585', '#7209b7'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Bar Chart
    const ctxStats = document.getElementById('libraryStatsChart').getContext('2d');
    new Chart(ctxStats, {
        type: 'bar',
        data: {
            labels: ['Librarians', 'Members', 'Books', 'Borrowed'],
            datasets: [{
                label: 'Count',
                data: [<?= $total_librarians ?>, <?= $total_members ?>, <?= $total_books ?>, <?= $total_borrowed ?>],
                backgroundColor: [
                    'rgba(67, 97, 238, 0.7)',
                    'rgba(76, 201, 240, 0.7)',
                    'rgba(247, 37, 133, 0.7)',
                    'rgba(255, 193, 7, 0.7)'
                ],
                borderColor: [
                    'rgba(67, 97, 238, 1)',
                    'rgba(76, 201, 240, 1)',
                    'rgba(247, 37, 133, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>