<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'librarian'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

// 1. KPI Cards Data
$total_librarians = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'librarian'")->fetch_assoc()['total'];
$total_members = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'member'")->fetch_assoc()['total'];
$total_books = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$total_borrowed = $conn->query("SELECT COUNT(*) AS total FROM borrowed_books")->fetch_assoc()['total'];

// 2. Chart Data: Books by Category
$cat_query = "SELECT c.name, COUNT(b.id) as count FROM categories c LEFT JOIN books b ON c.id = b.category_id GROUP BY c.id";
$cat_result = $conn->query($cat_query);
$cat_labels = [];
$cat_data = [];
while($row = $cat_result->fetch_assoc()) {
    $cat_labels[] = $row['name'];
    $cat_data[] = $row['count'];
}

// 3. Chart Data: Monthly Borrow Activity
$activity_query = "
    SELECT DATE_FORMAT(requested_at, '%Y-%m') as month_year, COUNT(*) as count 
    FROM borrow_requests 
    GROUP BY month_year 
    ORDER BY month_year DESC 
    LIMIT 6";
$activity_result = $conn->query($activity_query);
$months = [];
$monthly_counts = [];
if ($activity_result) {
    while($row = $activity_result->fetch_assoc()) {
        $months[] = date("M Y", strtotime($row['month_year'] . "-01"));
        $monthly_counts[] = $row['count'];
    }
}
$months = array_reverse($months);
$monthly_counts = array_reverse($monthly_counts);

// 4. Top Borrowed Books
$top_books_query = "
    SELECT b.title, COUNT(br.id) as requests 
    FROM borrow_requests br 
    JOIN books b ON br.book_id = b.id 
    GROUP BY br.book_id 
    ORDER BY requests DESC 
    LIMIT 5";
$top_books_result = $conn->query($top_books_query);

// 5. Report Generation Logic
$report_type = $_GET['report_type'] ?? 'books'; // Default to books
$report_data = [];

// Fetch Categories for Filter
$categories = $conn->query("SELECT * FROM categories");

if (isset($_GET['generate'])) {
    if ($report_type == 'books') {
        $where = [];
        if (!empty($_GET['category_id'])) {
            $cat_id = intval($_GET['category_id']);
            $where[] = "b.category_id = $cat_id";
        }
        if (!empty($_GET['search'])) {
            $search = $conn->real_escape_string($_GET['search']);
            $where[] = "(b.title LIKE '%$search%' OR b.author LIKE '%$search%')";
        }
        $sql = "SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $report_data = $conn->query($sql);
    } elseif ($report_type == 'users') {
        $where = [];
        if (!empty($_GET['role_filter'])) {
            $role = $conn->real_escape_string($_GET['role_filter']);
            $where[] = "role = '$role'";
        }
        $sql = "SELECT * FROM users";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $report_data = $conn->query($sql);
    }
}
?>

<div class="container-fluid fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">System Reports</h2>
            <p class="text-muted">Analytics and comprehensive reporting.</p>
        </div>
        <div>
            <button class="btn btn-outline-primary d-print-none" onclick="window.print()"><i class="bi bi-printer"></i> Print View</button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4 d-print-none" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= (!isset($_GET['generate'])) ? 'active' : '' ?>" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview Dashboard</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= (isset($_GET['generate'])) ? 'active' : '' ?>" id="detailed-tab" data-bs-toggle="tab" data-bs-target="#detailed" type="button" role="tab">Detailed Reports</button>
        </li>
    </ul>

    <div class="tab-content" id="reportTabsContent">
        
        <!-- Overview Tab -->
        <div class="tab-pane fade <?= (!isset($_GET['generate'])) ? 'show active' : '' ?>" id="overview" role="tabpanel">
            <!-- KPI Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                     <div class="card p-3 border-0 shadow-sm bg-light-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-primary mb-1 fw-bold">Total Books</p>
                                <h3 class="fw-bold mb-0"><?= $total_books ?></h3>
                            </div>
                            <i class="bi bi-book-half fs-1 text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
                <!-- ... Other KPI Cards (Members, Issued, Librarians) -->
                <div class="col-md-3">
                     <div class="card p-3 border-0 shadow-sm bg-light-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-success mb-1 fw-bold">Active Members</p>
                                <h3 class="fw-bold mb-0"><?= $total_members ?></h3>
                            </div>
                            <i class="bi bi-people-fill fs-1 text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
                 <div class="col-md-3">
                     <div class="card p-3 border-0 shadow-sm bg-light-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-warning mb-1 fw-bold">Currently Issued</p>
                                <h3 class="fw-bold mb-0 text-dark"><?= $total_borrowed ?></h3>
                            </div>
                            <i class="bi bi-journal-check fs-1 text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
                 <div class="col-md-3">
                     <div class="card p-3 border-0 shadow-sm bg-light-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-info mb-1 fw-bold">Librarians</p>
                                <h3 class="fw-bold mb-0"><?= $total_librarians ?></h3>
                            </div>
                            <i class="bi bi-person-badge fs-1 text-info opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-5">
                <div class="col-md-8">
                    <div class="card p-4 h-100 shadow-sm border-0">
                        <h5 class="card-title fw-bold mb-4">Monthly Borrowing Activity</h5>
                        <canvas id="activityChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100 shadow-sm border-0">
                        <h5 class="card-title fw-bold mb-4">Book Categories</h5>
                        <canvas id="categoryChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Books -->
            <div class="row mb-5">
                <div class="col-md-12">
                     <div class="card p-4 shadow-sm border-0">
                        <h5 class="card-title fw-bold mb-3">Top Requested Books</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Total Requests</th>
                                        <th>Popularity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($top_books_result && $top_books_result->num_rows > 0): ?>
                                        <?php while($book = $top_books_result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($book['title']) ?></td>
                                                <td><?= $book['requests'] ?> requests</td>
                                                <td>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: 75%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted">No borrowing history yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Reports Tab -->
        <div class="tab-pane fade <?= (isset($_GET['generate'])) ? 'show active' : '' ?>" id="detailed" role="tabpanel">
            <div class="card shadow-sm border-0 p-4 mb-4 d-print-none">
                <h5 class="fw-bold mb-3">Report Configuration</h5>
                <form method="GET" action="reports.php" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select name="report_type" id="reportTypeSelect" class="form-select" onchange="toggleFilters()">
                            <option value="books" <?= ($report_type == 'books') ? 'selected' : '' ?>>Books Report</option>
                            <option value="users" <?= ($report_type == 'users') ? 'selected' : '' ?>>Users Report</option>
                        </select>
                    </div>
                    
                    <!-- Book Filters -->
                    <div class="col-md-3 report-filter book-filter" style="<?= ($report_type != 'books') ? 'display:none' : '' ?>">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            <?php while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($_GET['category_id']) && $_GET['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 report-filter book-filter" style="<?= ($report_type != 'books') ? 'display:none' : '' ?>">
                        <label class="form-label">Search (Title/Author)</label>
                        <input type="text" name="search" class="form-control" placeholder="Keywords..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>

                    <!-- User Filters -->
                    <div class="col-md-3 report-filter user-filter" style="<?= ($report_type != 'users') ? 'display:none' : '' ?>">
                        <label class="form-label">Role</label>
                        <select name="role_filter" class="form-select">
                            <option value="">All Roles</option>
                            <option value="member" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'member') ? 'selected' : '' ?>>Member</option>
                            <option value="librarian" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'librarian') ? 'selected' : '' ?>>Librarian</option>
                            <option value="admin" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <input type="hidden" name="tab" value="detailed">
                        <button type="submit" name="generate" value="1" class="btn btn-primary w-100"><i class="bi bi-search"></i> Generate</button>
                    </div>
                </form>
            </div>

            <?php if (isset($_GET['generate'])): ?>
                <div class="card shadow-sm border-0 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold m-0"><?= ucfirst($report_type) ?> Report Results</h5>
                        <a href="export_report.php?<?= http_build_query($_GET) ?>" class="btn btn-success d-print-none"><i class="bi bi-file-earmark-excel"></i> Export to CSV</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-dark">
                                <?php if ($report_type == 'books'): ?>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>ISBN</th>
                                        <th>Qty</th>
                                        <th>Added Date</th>
                                    </tr>
                                <?php elseif ($report_type == 'users'): ?>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined Date</th>
                                    </tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php if ($report_data && $report_data->num_rows > 0): ?>
                                    <?php while($row = $report_data->fetch_assoc()): ?>
                                        <tr>
                                            <?php if ($report_type == 'books'): ?>
                                                <td><?= htmlspecialchars($row['title']) ?></td>
                                                <td><?= htmlspecialchars($row['author']) ?></td>
                                                <td><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($row['isbn']) ?></td>
                                                <td><span class="badge bg-secondary"><?= $row['quantity'] ?></span></td>
                                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                            <?php elseif ($report_type == 'users'): ?>
                                                <td><?= htmlspecialchars($row['name']) ?></td>
                                                <td><?= htmlspecialchars($row['email']) ?></td>
                                                <td><span class="badge bg-<?= ($row['role']=='admin')?'danger':(($row['role']=='librarian')?'info':'success') ?>"><?= ucfirst($row['role']) ?></span></td>
                                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted">No records found matching criteria.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleFilters() {
    const type = document.getElementById('reportTypeSelect').value;
    document.querySelectorAll('.report-filter').forEach(el => el.style.display = 'none');
    if (type === 'books') {
        document.querySelectorAll('.book-filter').forEach(el => el.style.display = 'block');
    } else if (type === 'users') {
        document.querySelectorAll('.user-filter').forEach(el => el.style.display = 'block');
    }
}

// Chart Initialization (Existing logic)
const ctxActivity = document.getElementById('activityChart').getContext('2d');
new Chart(ctxActivity, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Borrow Requests',
            data: <?= json_encode($monthly_counts) ?>,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67, 97, 238, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#4361ee',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { grid: { borderDash: [5, 5] }, beginAtZero: true }, x: { grid: { display: false } } }
    }
});

const ctxCat = document.getElementById('categoryChart').getContext('2d');
new Chart(ctxCat, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($cat_labels) ?>,
        datasets: [{
            data: <?= json_encode($cat_data) ?>,
            backgroundColor: ['#4cc9f0', '#4361ee', '#3f37c9', '#f72585', '#7209b7'],
            hoverOffset: 10,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } }
    }
});
</script>

<style>
@media print {
    .sidebar, .navbar, .d-print-none { display: none !important; }
    .content { margin: 0 !important; width: 100% !important; }
    .card { border: none !important; shadow: none !important; }
}
</style>

<?php include 'includes/footer.php'; ?>