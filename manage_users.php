<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search Logic
$search_query = "";
$search_sql = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = " WHERE name LIKE '%$search_query%' OR email LIKE '%$search_query%' OR role LIKE '%$search_query%'";
}

// Fetch users
$sql = "SELECT * FROM users $search_sql ORDER BY id DESC LIMIT $start, $limit";
$users_result = $conn->query($sql);

// Count Total for Pagination
$count_sql = "SELECT COUNT(*) as total FROM users $search_sql";
$total_result = $conn->query($count_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>

<div class="container-fluid fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">Manage Users</h2>
            <p class="text-muted">Administrate platform users and roles.</p>
        </div>
        <div>
            <a href="add_librarian.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Librarian</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <form method="GET" class="row g-3">
             <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by Name, Email, or Role..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>
    </div>

    <div class="card p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result->num_rows > 0): ?>
                        <?php while($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($user['name'] ?? $user['full_name'] ?? 'User') ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php 
                                        $roleClass = 'bg-secondary';
                                        if($user['role'] == 'admin') $roleClass = 'bg-danger';
                                        if($user['role'] == 'librarian') $roleClass = 'bg-info text-dark';
                                        if($user['role'] == 'member') $roleClass = 'bg-success';
                                    ?>
                                    <span class="badge <?= $roleClass ?>"><?= ucfirst($user['role']) ?></span>
                                </td>
                                <td>
                                    <?php if ($user['role'] != 'admin' || $user['id'] != $_SESSION['user_id']): ?>
                                        <a href="manage_users.php?delete_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?');"><i class="bi bi-trash"></i> Delete</a>
                                    <?php else: ?>
                                        <span class="text-muted small">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= htmlspecialchars($_GET['search'] ?? '') ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<?php 
// Handle Delete Logic
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id != $_SESSION['user_id']) { // Prevent self-delete
        $conn->query("DELETE FROM users WHERE id = $delete_id");
        echo "<script>window.location.href='manage_users.php';</script>";
    }
}

include 'includes/footer.php'; 
?>