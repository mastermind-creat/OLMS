<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Get all notifications for the user
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="container fade-in" style="margin-top: 30px; max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">Notifications</h2>
        <?php if ($result->num_rows > 0): ?>
            <a href="mark_read.php?action=mark_all_read" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-check-all"></i> Mark All as Read
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm border-0">
        <div class="list-group list-group-flush">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="list-group-item list-group-item-action p-4 border-bottom <?= $row['is_read'] ? 'bg-light text-muted' : 'bg-white' ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold <?= $row['is_read'] ? '' : 'text-primary' ?>">
                                <?= $row['is_read'] ? '<i class="bi bi-envelope-open me-2"></i>' : '<i class="bi bi-envelope-fill me-2"></i>' ?>
                                System Notification
                            </h6>
                            <small class="<?= $row['is_read'] ? '' : 'text-primary' ?>"><?= date('M d, H:i', strtotime($row['created_at'])) ?></small>
                        </div>
                        <p class="mb-1 mt-2"><?= htmlspecialchars($row['message']) ?></p>
                        <!-- Optional: Link to related action if we stored entity_type/id -->
                        
                        <?php if (!$row['is_read']): ?>
                            <div class="mt-2 text-end">
                                <a href="mark_read.php?id=<?= $row['id'] ?>" class="btn btn-link btn-sm text-decoration-none p-0">Mark as read</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash display-1 text-muted opacity-50"></i>
                    <p class="mt-3 text-muted">You have no notifications.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
