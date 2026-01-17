<?php
// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

// Determine Dashboard Link based on role
$dashboard_link = '#';
if ($role == 'admin') $dashboard_link = 'admin_dashboard.php';
elseif ($role == 'librarian') $dashboard_link = 'librarian_dashboard.php';
elseif ($role == 'member') $dashboard_link = 'member_dashboard.php';
?>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <i class="bi bi-book-half"></i> OLMS
    </div>
    
    <div class="d-flex flex-column">
        <a href="<?= $dashboard_link ?>" class="<?= strpos($current_page, 'dashboard') !== false ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <?php if ($role == 'admin'): ?>
            <a href="manage_users.php" class="<?= $current_page == 'manage_users.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="manage_books.php" class="<?= $current_page == 'manage_books.php' ? 'active' : '' ?>">
                <i class="bi bi-book"></i> Books
            </a>
            <a href="manage_categories.php" class="<?= $current_page == 'manage_categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags"></i> Categories
            </a>
            <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <a href="add_librarian.php" class="<?= $current_page == 'add_librarian.php' ? 'active' : '' ?>">
                <i class="bi bi-person-plus"></i> Add Librarian
            </a>
        <?php endif; ?>

        <?php if ($role == 'librarian'): ?>
             <a href="manage_books.php" class="<?= $current_page == 'manage_books.php' ? 'active' : '' ?>">
                <i class="bi bi-book"></i> Books
            </a>
            <a href="manage_categories.php" class="<?= $current_page == 'manage_categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags"></i> Categories
            </a>
            <a href="issued_books.php" class="<?= $current_page == 'issued_books.php' ? 'active' : '' ?>">
                <i class="bi bi-journal-check"></i> Issued Books
            </a>
            <a href="returned_books.php" class="<?= $current_page == 'returned_books.php' ? 'active' : '' ?>">
                <i class="bi bi-journal-arrow-down"></i> Returns
            </a>
            <a href="manage_borrow_requests.php" class="<?= $current_page == 'manage_borrow_requests.php' ? 'active' : '' ?>">
                <i class="bi bi-bell"></i> Requests
            </a>
        <?php endif; ?>

        <?php if ($role == 'member'): ?>
            <a href="member_dashboard.php" class="<?= $current_page == 'member_dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-search"></i> Browse Books
            </a>
            <a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <i class="bi bi-person"></i> Profile
            </a>
        <?php endif; ?>

        <a href="logout.php" class="text-danger mt-5">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
