<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Data fetching is now done within the body or via includes, 
// but we need to fetch stats for the dashboard here.
include 'includes/db_connection.php';

// Fetch statistics
$total_librarians = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'librarian'")->fetch_assoc()['total'];
$total_members = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'member'")->fetch_assoc()['total'];
$total_books = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$total_borrowed = $conn->query("SELECT COUNT(*) AS total FROM borrowed_books")->fetch_assoc()['total'];

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

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Librarians</p>
                        <h3 class="fw-bold mb-0"><?= $total_librarians ?></h3>
                    </div>
                    <div class="card-icon bg-light-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Active Members</p>
                        <h3 class="fw-bold mb-0"><?= $total_members ?></h3>
                    </div>
                    <div class="card-icon bg-light-success">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Books</p>
                        <h3 class="fw-bold mb-0"><?= $total_books ?></h3>
                    </div>
                    <div class="card-icon bg-light-danger">
                        <i class="bi bi-book-half"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Books Borrowed</p>
                        <h3 class="fw-bold mb-0"><?= $total_borrowed ?></h3>
                    </div>
                    <div class="card-icon bg-light-warning">
                        <i class="bi bi-journal-arrow-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-5">
        <div class="col-md-8">
            <div class="card p-4 h-100">
                <h5 class="card-title mb-4">Library Analytics</h5>
                <!-- Placeholder for a more complex chart if needed, reusing simple stats for now -->
                <canvas id="libraryStatsChart"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5 class="card-title mb-4">Books by Category</h5>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity / Quick Actions -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="card-title mb-3">Quick Actions</h5>
                <div class="d-grid gap-2 col-6 mx-auto">
                    <a href="add_librarian.php" class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> Add New Librarian</a>
                    <a href="manage_users.php" class="btn btn-outline-success"><i class="bi bi-people"></i> Manage All Users</a>
                    <a href="reports.php" class="btn btn-outline-dark"><i class="bi bi-file-earmark-bar-graph"></i> View System Reports</a>
                </div>
            </div>
        </div>
         <div class="col-md-6">
            <div class="card p-4">
                <h5 class="card-title mb-3">System Status</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Database Connection
                        <span class="badge bg-success rounded-pill">Active</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        System Version
                        <span class="text-muted">v2.0 Premium</span>
                    </li>
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