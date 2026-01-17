<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/db_connection.php';
include_once __DIR__ . '/functions.php';

$user_id = $_SESSION['user_id'] ?? 0;
$unread_count = getUnreadCount($conn, $user_id);
$notifications = getUnreadNotifications($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLMS - Library System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="d-flex align-items-center">
            <h4 class="mb-0">Library Management</h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Notifications -->
            <div class="dropdown">
                <div class="notification-icon" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 300px;">
                    <li class="dropdown-header">Notifications</li>
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <li><a class="dropdown-item text-wrap small" href="#"><?= htmlspecialchars($notif['message']) ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small text-primary" href="mark_read.php">Mark all as read</a></li>
                    <?php else: ?>
                        <li><span class="dropdown-item text-muted small">No new notifications</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Profile Dropdown -->
             <div class="dropdown">
                <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="ms-2 fw-bold"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                </a>
                <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content flows below here in each page -->
