<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Include the database connection file
include 'db_connection.php';

// Fetch statistics
$total_librarians_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'librarian'";
$total_librarians_result = $conn->query($total_librarians_sql);
$total_librarians = $total_librarians_result->fetch_assoc()['total'];

$total_members_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'member'";
$total_members_result = $conn->query($total_members_sql);
$total_members = $total_members_result->fetch_assoc()['total'];

$total_books_sql = "SELECT COUNT(*) AS total FROM books";
$total_books_result = $conn->query($total_books_sql);
$total_books = $total_books_result->fetch_assoc()['total'];

$total_borrowed_books_sql = "SELECT COUNT(*) AS total FROM borrowed_books";
$total_borrowed_books_result = $conn->query($total_borrowed_books_sql);
$total_borrowed_books = $total_borrowed_books_result->fetch_assoc()['total'];

// Fetch borrowing trends
$frequent_categories_sql = "
    SELECT categories.name AS category_name, COUNT(borrowed_books.id) AS borrow_count
    FROM borrowed_books
    INNER JOIN books ON borrowed_books.book_id = books.id
    INNER JOIN categories ON books.category_id = categories.id
    GROUP BY categories.name
    ORDER BY borrow_count DESC";
$frequent_categories_result = $conn->query($frequent_categories_sql);

// Prepare data for the chart
$category_names = [];
$category_borrow_counts = [];
while ($row = $frequent_categories_result->fetch_assoc()) {
    $category_names[] = $row['category_name'];
    $category_borrow_counts[] = $row['borrow_count'];
}

// Handle user search and filter
$search_query = isset($_GET['search_query']) ? mysqli_real_escape_string($conn, $_GET['search_query']) : '';
$role_filter = isset($_GET['role_filter']) ? mysqli_real_escape_string($conn, $_GET['role_filter']) : '';

$user_search_sql = "SELECT * FROM users WHERE name LIKE '%$search_query%'";
if ($role_filter) {
    $user_search_sql .= " AND role = '$role_filter'";
}
$user_search_result = $conn->query($user_search_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">📚 MyLibrary</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reports.php">Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="text-center">Library Reports</h2>

        <!-- Frequently Borrowed Categories Section -->
        <div class="mt-5">
            <h4>Frequently Borrowed Categories</h4>
            <?php if (!empty($category_names)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Borrow Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_names as $index => $category_name): ?>
                            <tr>
                                <td><?= htmlspecialchars($category_name); ?></td>
                                <td><?= $category_borrow_counts[$index]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No data available for borrowed categories.</p>
            <?php endif; ?>
        </div>

        <!-- Statistics Section -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Librarians</h5>
                        <p class="card-text display-6"><?= $total_librarians; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Members</h5>
                        <p class="card-text display-6"><?= $total_members; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Books</h5>
                        <p class="card-text display-6"><?= $total_books; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Books Borrowed</h5>
                        <p class="card-text display-6"><?= $total_borrowed_books; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrowing Trends Chart -->
        <div class="mt-5">
            <h4>Borrowing Trends</h4>
            <canvas id="borrowingTrendsChart"></canvas>
        </div>

        <!-- User Search and Filter Section -->
        <div class="mt-5">
            <h4>Search and Filter Users</h4>
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search_query" class="form-control" placeholder="Search by name" value="<?= htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-4">
                    <select name="role_filter" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" <?= $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="librarian" <?= $role_filter == 'librarian' ? 'selected' : ''; ?>>Librarian</option>
                        <option value="member" <?= $role_filter == 'member' ? 'selected' : ''; ?>>Member</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>

            <div class="mt-4">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($user_search_result->num_rows > 0): ?>
                            <?php while ($user = $user_search_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['name']); ?></td>
                                    <td><?= htmlspecialchars($user['email']); ?></td>
                                    <td><?= htmlspecialchars($user['role']); ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?= $user['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete_user.php?id=<?= $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Borrowing Trends Chart
        const ctx = document.getElementById('borrowingTrendsChart').getContext('2d');
        const borrowingTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($borrowing_trends, 'borrow_date')); ?>,
                datasets: [{
                    label: 'Books Borrowed',
                    data: <?= json_encode(array_column($borrowing_trends, 'borrow_count')); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Books Borrowed'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>