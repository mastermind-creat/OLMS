<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';

    // Use prepared statements for security
    $update_sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        if ($stmt->execute()) {
            $_SESSION['name'] = $name; // Update session name
            $message = "Profile updated successfully!";
            logAudit($conn, $user_id, "Update Profile", "Updated own profile details.");
        } else {
            $error = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Fetch current user details
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Fetch borrowing history (using borrow_requests table for consistency)
$history_sql = "
    SELECT books.title, books.author, borrow_requests.days, borrow_requests.total_cost, borrow_requests.requested_at, borrow_requests.status
    FROM borrow_requests
    INNER JOIN books ON borrow_requests.book_id = books.id
    WHERE borrow_requests.user_id = $user_id
    ORDER BY borrow_requests.requested_at DESC";
$history_result = $conn->query($history_sql);
?>

<div class="container fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">My Profile</h2>
            <p class="text-muted">Manage your personal information and view history.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-lg text-center p-4 h-100">
                <div class="mb-3">
                     <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto shadow" style="width: 100px; height: 100px; font-size: 2.5rem;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                <p class="text-muted mb-3"><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                <div class="badge bg-light-primary text-primary px-3 py-2 rounded-pill"><?= ucfirst($user['role']) ?></div>
                
                <hr class="my-4">
                
                <div class="text-start">
                    <p class="mb-2"><strong class="text-dark">Member Since:</strong> <span class="text-muted float-end"><?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?></span></p>
                    <p class="mb-2"><strong class="text-dark">Status:</strong> <span class="text-success float-end"><i class="bi bi-check-circle-fill"></i> Active</span></p>
                </div>
            </div>
        </div>

        <!-- Edit Profile & History -->
        <div class="col-md-8">
            <!-- Edit Profile -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Details</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?= $message ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
                        </div>
                         <div class="col-md-12">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1234567890">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Borrowing History -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity</h5>
                </div>
                <div class="card-body p-4">
                     <?php if ($history_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Book</th>
                                        <th>Requested Date</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($history = $history_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($history['title']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($history['author']) ?></small>
                                            </td>
                                            <td><?= date('d M Y', strtotime($history['requested_at'])); ?></td>
                                            <td><?= $history['days']; ?> days</td>
                                            <td>
                                                <?php 
                                                $statusClass = match($history['status']) {
                                                    'Book Issued' => 'bg-success',
                                                    'Book Returned' => 'bg-secondary',
                                                    'Rejected' => 'bg-danger',
                                                    default => 'bg-warning text-dark'
                                                };
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $history['status'] ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No borrowing history found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>