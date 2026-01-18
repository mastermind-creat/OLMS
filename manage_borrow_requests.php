<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header('Location: login.php');
    exit();
}

include 'includes/db_connection.php';
include 'includes/header.php';

// Handle Actions (Approve, Reject, Return)
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $action = $_GET['action'];
    $request_id = intval($_GET['request_id']);
    $success_msg = "";
    $error_msg = "";

    // Helper to get request details
    $get_request_details = function($conn, $request_id) {
        $sql = "SELECT br.user_id, br.book_id, b.title as book_title, br.status 
                FROM borrow_requests br 
                JOIN books b ON br.book_id = b.id 
                WHERE br.id = $request_id";
        return $conn->query($sql)->fetch_assoc();
    };

    // Consolidate Restocking Logic
    $restock_book = function($conn, $request_id) {
        $check_sql = "SELECT book_id, status FROM borrow_requests WHERE id = $request_id";
        $check_result = $conn->query($check_sql);
        if ($check_result && $check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            // Important: Only restock if it was previously Issued or Pending to avoid double increment
            // Actually, we usually restock on Reject (if it was Pending) or Return (if it was Issued)
            $book_id = $row['book_id'];
            $update_quantity_sql = "UPDATE books SET quantity = quantity + 1 WHERE id = $book_id";
            return $conn->query($update_quantity_sql);
        }
        return false;
    };

    if ($action === 'approve') {
        $details = $get_request_details($conn, $request_id);
        $approve_sql = "UPDATE borrow_requests SET status = 'Book Issued' WHERE id = $request_id";
        if ($details && $conn->query($approve_sql) === TRUE) {
            logAudit($conn, $_SESSION['user_id'], "Approve Borrow", "Approved request ID $request_id");
            addNotification($conn, $details['user_id'], "Your request for '{$details['book_title']}' has been approved. Please collect it.");
            $success_msg = "Request approved! Book issued successfully.";
        } else {
            $error_msg = "Error approving request: " . $conn->error;
        }
    } elseif ($action === 'reject') {
        $details = $get_request_details($conn, $request_id);
        if ($details['status'] === 'Pending') {
            $reject_sql = "UPDATE borrow_requests SET status = 'Rejected' WHERE id = $request_id";
            if ($conn->query($reject_sql) === TRUE) {
                logAudit($conn, $_SESSION['user_id'], "Reject Borrow", "Rejected request ID $request_id");
                $restock_book($conn, $request_id);
                addNotification($conn, $details['user_id'], "Your request for '{$details['book_title']}' was rejected.");
                $success_msg = "Request rejected. Book stock restored.";
            } else {
                $error_msg = "Error rejecting request: " . $conn->error;
            }
        } else {
            $error_msg = "Cannot reject a request that is already " . $details['status'];
        }
    } elseif ($action === 'return') {
        $details = $get_request_details($conn, $request_id);
        if ($details['status'] === 'Book Issued') {
            $return_sql = "UPDATE borrow_requests SET status = 'Book Returned' WHERE id = $request_id";
            if ($conn->query($return_sql) === TRUE) {
                logAudit($conn, $_SESSION['user_id'], "Book Return", "Processed return for request ID $request_id");
                $restock_book($conn, $request_id);
                addNotification($conn, $details['user_id'], "You have successfully returned '{$details['book_title']}'. Thank you!");
                $success_msg = "Book marked as returned. Quantity updated.";
            } else {
                $error_msg = "Error processing return: " . $conn->error;
            }
        } else {
            $error_msg = "Cannot return a book that is already " . $details['status'];
        }
    }

    // Redirect to prevent form resubmission/refresh issues
    if ($success_msg) {
        $_SESSION['success_msg'] = $success_msg;
        echo "<script>window.location.href='manage_borrow_requests.php';</script>";
        exit();
    }
}

// Support for session-based messages
$success_msg = $_SESSION['success_msg'] ?? "";
unset($_SESSION['success_msg']);

// Fetch borrow requests
$borrow_requests_sql = "
    SELECT borrow_requests.id, books.title, users.name AS member_name, borrow_requests.days, borrow_requests.total_cost, borrow_requests.status, borrow_requests.requested_at 
    FROM borrow_requests
    INNER JOIN books ON borrow_requests.book_id = books.id
    INNER JOIN users ON borrow_requests.user_id = users.id
    ORDER BY CASE WHEN borrow_requests.status = 'Pending' THEN 1 ELSE 2 END, borrow_requests.requested_at DESC";
$borrow_requests_result = $conn->query($borrow_requests_sql);
?>

<div class="container-fluid fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary">Manage Requests</h2>
            <p class="text-muted">Approve issues and process returns.</p>
        </div>
        <div>
            <a href="librarian_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($success_msg) && $success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $success_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error_msg) && $error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <div class="card p-4 shadow-sm border-0">
          <!-- Search -->
        <div class="row mb-3">
             <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search requests...">
            </div>
        </div>

        <div class="table-responsive">
            <?php if ($borrow_requests_result->num_rows > 0): ?>
                <table class="table table-hover align-middle" id="requestsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Book Title</th>
                            <th>Member</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($request = $borrow_requests_result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold book-title"><?= htmlspecialchars($request['title']); ?></td>
                                <td class="member-name">
                                    <div class="d-flex align-items-center">
                                         <div class="bg-light-info rounded-circle d-flex align-items-center justify-content-center text-info fw-bold me-2" style="width: 30px; height: 30px;">
                                            <?= substr(htmlspecialchars($request['member_name']), 0, 1) ?>
                                        </div>
                                        <?= htmlspecialchars($request['member_name']); ?>
                                    </div>
                                </td>
                                <td><?= $request['days']; ?> days</td>
                                <td>
                                    <?php 
                                    $statusParams = match($request['status']) {
                                        'Pending' => ['bg-warning text-dark', 'Pending'],
                                        'Book Issued' => ['bg-success', 'Issued'],
                                        'Book Returned' => ['bg-secondary', 'Returned'],
                                        'Rejected' => ['bg-danger', 'Rejected'],
                                        default => ['bg-light text-dark', $request['status']]
                                    };
                                    ?>
                                    <span class="badge <?= $statusParams[0] ?>"><?= $statusParams[1] ?></span>
                                </td>
                                <td><?= date('M d, H:i', strtotime($request['requested_at'])); ?></td>
                                <td>
                                    <?php if (strtolower($request['status']) === 'pending'): ?>
                                        <div class="btn-group">
                                            <a href="manage_borrow_requests.php?action=approve&request_id=<?= $request['id']; ?>" class="btn btn-success btn-sm" title="Approve Issue"><i class="bi bi-check-lg"></i></a>
                                            <a href="manage_borrow_requests.php?action=reject&request_id=<?= $request['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this request?');" title="Reject"><i class="bi bi-x-lg"></i></a>
                                        </div>
                                    <?php elseif ($request['status'] === 'Book Issued'): ?>
                                        <a href="manage_borrow_requests.php?action=return&request_id=<?= $request['id']; ?>" class="btn btn-primary btn-sm" title="Mark as Returned"><i class="bi bi-arrow-return-left"></i> Return</a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="mt-3 text-muted">No borrow requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Live Search Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('requestsTable');
        
        if(searchInput && table) {
            searchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const bookTitle = row.querySelector('.book-title').textContent.toLowerCase();
                    const memberName = row.querySelector('.member-name').textContent.toLowerCase();
                    
                    if (bookTitle.includes(searchValue) || memberName.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>