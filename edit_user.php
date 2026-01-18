<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';


$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = null;
$error = "";
$success = "";

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        $error = "User not found.";
    }
} else {
    $error = "Invalid user ID.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($role)) {
        $error = "Name, Email, and Role are required.";
    } else {
        // Check if email exists for another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Email already exists for another user.";
        } else {
            // Update User
            $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $name, $email, $phone, $role, $user_id);
            
            if ($update_stmt->execute()) {
                $details = "Updated user ID $user_id: Name ($name), Email ($email), Phone ($phone), Role ($role)";
                logAudit($conn, $_SESSION['user_id'], "Update User", $details);
                $success = "User updated successfully.";
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['role'] = $role;
            } else {
                $error = "Error updating user: " . $conn->error;
            }
        }
    }
}
?>

<div class="container fade-in" style="max-width: 600px; margin-top: 50px;">
    <div class="card shadow border-0">
        <div class="card-header bg-white py-3">
             <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">Edit User</h5>
                <a href="manage_users.php" class="btn btn-outline-secondary btn-sm">Back to List</a>
            </div>
        </div>
        <div class="card-body p-4">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($user): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+1234567890">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                            <option value="member" <?= $user['role'] == 'member' ? 'selected' : '' ?>>Member</option>
                            <option value="librarian" <?= $user['role'] == 'librarian' ? 'selected' : '' ?>>Librarian</option>
                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                            <input type="hidden" name="role" value="<?= $user['role'] ?>">
                            <div class="form-text text-muted">You cannot change your own role.</div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Update User</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">User data could not be loaded.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
