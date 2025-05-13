<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header('Location: login.php');
    exit();
}

// Include the database connection file
include 'db_connection.php';

// Fetch borrow requests
$borrow_requests_sql = "
    SELECT borrow_requests.id, books.title, users.name AS member_name, borrow_requests.days, borrow_requests.total_cost, borrow_requests.status, borrow_requests.requested_at 
    FROM borrow_requests
    INNER JOIN books ON borrow_requests.book_id = books.id
    INNER JOIN users ON borrow_requests.user_id = users.id
    ORDER BY borrow_requests.requested_at ASC";
$borrow_requests_result = $conn->query($borrow_requests_sql);

// Handle actions
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $action = $_GET['action'];
    $request_id = intval($_GET['request_id']);

    if ($action === 'approve') {
        // Approve the request and set status to "Book Issued"
        $approve_sql = "UPDATE borrow_requests SET status = 'Book Issued' WHERE id = $request_id";
        if ($conn->query($approve_sql) === TRUE) {
            echo "<script>alert('Borrow request approved and status updated to Book Issued!'); window.location.href='manage_borrow_requests.php';</script>";
        } else {
            echo "<script>alert('Error approving request: " . $conn->error . "');</script>";
        }
    } elseif ($action === 'reject') {
        // Reject the request and set status to "Rejected"
        $reject_sql = "UPDATE borrow_requests SET status = 'Rejected' WHERE id = $request_id";
        if ($conn->query($reject_sql) === TRUE) {
            echo "<script>alert('Borrow request rejected successfully!'); window.location.href='manage_borrow_requests.php';</script>";
        } else {
            echo "<script>alert('Error rejecting request: " . $conn->error . "');</script>";
        }
    } elseif ($action === 'return') {
        // Mark the book as returned and set status to "Book Returned"
        $return_sql = "UPDATE borrow_requests SET status = 'Book Returned' WHERE id = $request_id";
        if ($conn->query($return_sql) === TRUE) {
            // Increment the quantity of the book by 1
            $book_id_sql = "SELECT book_id FROM borrow_requests WHERE id = $request_id";
            $book_id_result = $conn->query($book_id_sql);
            if ($book_id_result->num_rows > 0) {
                $book_id = $book_id_result->fetch_assoc()['book_id'];
                $update_quantity_sql = "UPDATE books SET quantity = quantity + 1 WHERE id = $book_id";
                $conn->query($update_quantity_sql);
            }

            echo "<script>alert('Book marked as returned successfully!'); window.location.href='manage_borrow_requests.php';</script>";
        } else {
            echo "<script>alert('Error marking book as returned: " . $conn->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrow Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Manage Borrow Requests</h2>

        <!-- Search Bar -->
        <div class="mt-4 mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by member name or book title...">
        </div>

        <!-- Borrow Requests Table -->
        <div class="mt-4">
            <?php if ($borrow_requests_result->num_rows > 0): ?>
                <table class="table table-striped" id="borrowRequestsTable">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Member Name</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Requested At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($request = $borrow_requests_result->fetch_assoc()): ?>
                            <tr>
                                <td class="book-title"><?= htmlspecialchars($request['title']); ?></td>
                                <td class="member-name"><?= htmlspecialchars($request['member_name']); ?></td>
                                <td><?= $request['days']; ?></td>
                                <td><?= htmlspecialchars($request['status']); ?></td>
                                <td><?= date('d M Y, h:i A', strtotime($request['requested_at'])); ?></td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <a href="manage_borrow_requests.php?action=approve&request_id=<?= $request['id']; ?>" class="btn btn-success btn-sm">Approve</a>
                                        <a href="manage_borrow_requests.php?action=reject&request_id=<?= $request['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this request?');">Reject</a>
                                    <?php elseif ($request['status'] === 'Book Issued'): ?>
                                        <a href="manage_borrow_requests.php?action=return&request_id=<?= $request['id']; ?>" class="btn btn-primary btn-sm">Mark as Returned</a>
                                    <?php else: ?>
                                        <span class="text-muted">No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center">No borrow requests found.</p>
            <?php endif; ?>
        </div>

        <div class="mt-3 text-center">
            <a href="librarian_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Live Search Functionality
        $(document).ready(function() {
            $('#searchInput').on('keyup', function() {
                var searchValue = $(this).val().toLowerCase();
                $('#borrowRequestsTable tbody tr').filter(function() {
                    $(this).toggle(
                        $(this).find('.book-title').text().toLowerCase().includes(searchValue) ||
                        $(this).find('.member-name').text().toLowerCase().includes(searchValue)
                    );
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>